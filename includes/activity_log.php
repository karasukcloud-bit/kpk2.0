<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/curriculum.php';
require_once __DIR__ . '/students.php';

function log_activity(
    string $action,
    string $entityType = '',
    ?int $entityId = null,
    ?int $groupId = null,
    array $details = []
): void {
    if (!is_logged_in()) {
        return;
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return;
    }

    $payload = $details === [] ? null : json_encode($details, JSON_UNESCAPED_UNICODE);
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

    $stmt = db()->prepare(
        'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, group_id, details_json, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $action,
        $entityType,
        $entityId,
        $groupId,
        $payload,
        $ip,
    ]);
}

function activity_log_action_options(): array
{
    return [
        '' => 'Все действия',
        'auth.login' => 'Вход в систему',
        'student.create' => 'Добавление студента',
        'student.delete' => 'Удаление студента',
        'journal.lesson_add' => 'Добавление даты в журнал',
        'journal.lesson_update' => 'Изменение даты в журнале',
        'journal.lesson_delete' => 'Удаление даты из журнала',
        'journal.grade_save' => 'Изменение оценки в журнале',
        'manual_brs.entry_save' => 'Изменение оценки в ручном БРС',
        'manual_brs.pa_save' => 'Изменение ПА в ручном БРС',
        'archive.import_json' => 'Импорт архивных ведомостей (JSON)',
    ];
}

/** Обновление времени последней активности на сайте (не чаще раза в минуту). */
function touch_user_activity(?int $userId = null, bool $force = false): void
{
    $userId = $userId ?? (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return;
    }

    $now = time();
    $lastTouch = (int) ($_SESSION['_last_activity_touch'] ?? 0);
    if (!$force && ($now - $lastTouch) < 60) {
        return;
    }

    $_SESSION['_last_activity_touch'] = $now;

    $stmt = db()->prepare('UPDATE users SET last_activity_at = NOW() WHERE id = ?');
    $stmt->execute([$userId]);
}

/**
 * Сводка активности пользователей на сайте.
 *
 * @return list<array{
 *   id: int,
 *   full_name: string,
 *   role: string,
 *   is_active: int,
 *   last_activity_at: ?string,
 *   actions_count: int,
 *   last_action_at: ?string,
 *   last_ip: string,
 *   is_online: bool
 * }>
 */
function list_users_site_activity(int $onlineMinutes = 3): array
{
    $onlineSeconds = max(1, $onlineMinutes) * 60;
    $stmt = db()->query(
        "SELECT u.id, u.full_name, u.role, u.is_active, u.last_activity_at,
                COALESCE(s.actions_count, 0) AS actions_count,
                s.last_action_at,
                COALESCE(s.last_ip, '') AS last_ip
         FROM users u
         LEFT JOIN (
             SELECT user_id,
                    COUNT(*) AS actions_count,
                    MAX(created_at) AS last_action_at,
                    SUBSTRING_INDEX(GROUP_CONCAT(ip_address ORDER BY created_at DESC SEPARATOR ','), ',', 1) AS last_ip
             FROM activity_logs
             GROUP BY user_id
         ) s ON s.user_id = u.id
         WHERE u.role <> 'student'
            OR u.last_activity_at IS NOT NULL
            OR s.actions_count > 0
         ORDER BY COALESCE(u.last_activity_at, s.last_action_at) DESC,
                  u.full_name ASC"
    );

    $rows = $stmt->fetchAll();
    $staffRolesMap = [];
    $staffIds = [];
    foreach ($rows as $row) {
        if ((string) ($row['role'] ?? '') === 'teacher') {
            $staffIds[] = (int) $row['id'];
        }
    }
    if ($staffIds !== []) {
        $placeholders = implode(',', array_fill(0, count($staffIds), '?'));
        $rolesStmt = db()->prepare(
            "SELECT user_id, role FROM user_roles WHERE user_id IN ($placeholders) ORDER BY role ASC"
        );
        $rolesStmt->execute($staffIds);
        foreach ($rolesStmt->fetchAll() as $roleRow) {
            $uid = (int) $roleRow['user_id'];
            $staffRolesMap[$uid][] = (string) $roleRow['role'];
        }
    }

    $now = time();
    $result = [];

    foreach ($rows as $row) {
        $userId = (int) $row['id'];
        $lastAt = (string) ($row['last_activity_at'] ?? '');
        if ($lastAt === '') {
            $lastAt = (string) ($row['last_action_at'] ?? '');
        }
        $lastTs = $lastAt !== '' ? strtotime($lastAt) : false;
        $isOnline = $lastTs !== false && ($now - $lastTs) <= $onlineSeconds;

        $result[] = [
            'id' => $userId,
            'full_name' => (string) $row['full_name'],
            'role' => (string) $row['role'],
            'is_active' => (int) $row['is_active'],
            'last_activity_at' => $row['last_activity_at'] !== null ? (string) $row['last_activity_at'] : null,
            'actions_count' => (int) $row['actions_count'],
            'last_action_at' => $row['last_action_at'] !== null ? (string) $row['last_action_at'] : null,
            'last_ip' => (string) ($row['last_ip'] ?? ''),
            'is_online' => $isOnline,
            'staff_roles' => $staffRolesMap[$userId] ?? [],
        ];
    }

    return $result;
}

function format_user_activity_role(array $row): string
{
    $role = (string) ($row['role'] ?? '');
    if ($role === 'admin') {
        return role_label('admin');
    }
    if ($role === 'student') {
        return 'Студент';
    }
    $staffRoles = $row['staff_roles'] ?? [];
    if (is_array($staffRoles) && $staffRoles !== []) {
        return format_staff_roles_list($staffRoles);
    }

    return role_label($role);
}

function activity_log_action_label(string $action): string
{
    return activity_log_action_options()[$action] ?? $action;
}

function activity_log_format_details(array $log): string
{
    $details = [];
    if (!empty($log['details_json'])) {
        $decoded = json_decode((string) $log['details_json'], true);
        if (is_array($decoded)) {
            $details = $decoded;
        }
    }

    $parts = [];
    if (!empty($details['module'])) {
        $parts[] = (string) $details['module'];
    }
    if (!empty($details['group_number'])) {
        $parts[] = 'группа ' . $details['group_number'];
    }
    if (!empty($details['subject_name'])) {
        $parts[] = 'предмет «' . $details['subject_name'] . '»';
    }
    if (!empty($details['academic_year'])) {
        $parts[] = 'год ' . $details['academic_year'];
    }
    if (!empty($details['period_label'])) {
        $parts[] = (string) $details['period_label'];
    }
    if (!empty($details['student_name'])) {
        $parts[] = 'студент ' . $details['student_name'];
    }
    if (!empty($details['lesson_date'])) {
        $parts[] = 'дата ' . $details['lesson_date'];
    }
    if (!empty($details['grade_type_label'])) {
        $parts[] = $details['grade_type_label'];
    }
    if (array_key_exists('mark', $details) || array_key_exists('old_mark', $details)) {
        $old = (string) ($details['old_mark'] ?? '—');
        $new = (string) ($details['mark'] ?? '—');
        if ($old !== $new) {
            $parts[] = 'оценка ' . $old . ' → ' . $new;
        } else {
            $parts[] = 'оценка ' . $new;
        }
    }
    if (array_key_exists('points', $details) || array_key_exists('old_points', $details)) {
        $oldPoints = activity_log_format_optional_number($details['old_points'] ?? null);
        $newPoints = activity_log_format_optional_number($details['points'] ?? null);
        if ($oldPoints !== $newPoints) {
            $parts[] = 'баллы ' . $oldPoints . ' → ' . $newPoints;
        } elseif ($newPoints !== '—') {
            $parts[] = 'баллы ' . $newPoints;
        }
    }
    if (array_key_exists('pa_grade', $details) || array_key_exists('old_pa_grade', $details)) {
        $oldPa = activity_log_format_optional_scalar($details['old_pa_grade'] ?? null);
        $newPa = activity_log_format_optional_scalar($details['pa_grade'] ?? null);
        if ($oldPa !== $newPa) {
            $parts[] = 'ПА ' . $oldPa . ' → ' . $newPa;
        } else {
            $parts[] = 'ПА ' . $newPa;
        }
    }
    if (array_key_exists('current_marks', $details) || array_key_exists('old_current_marks', $details)) {
        $oldMarks = activity_log_format_optional_scalar($details['old_current_marks'] ?? null);
        $newMarks = activity_log_format_optional_scalar($details['current_marks'] ?? null);
        if ($oldMarks !== $newMarks) {
            $parts[] = 'текущие ' . $oldMarks . ' → ' . $newMarks;
        }
    }
    if (array_key_exists('control_marks', $details) || array_key_exists('old_control_marks', $details)) {
        $oldMarks = activity_log_format_optional_scalar($details['old_control_marks'] ?? null);
        $newMarks = activity_log_format_optional_scalar($details['control_marks'] ?? null);
        if ($oldMarks !== $newMarks) {
            $parts[] = 'контрольные ' . $oldMarks . ' → ' . $newMarks;
        }
    }
    if (!empty($details['topic_title'])) {
        $parts[] = 'тема «' . $details['topic_title'] . '»';
    }
    if (!empty($details['extra'])) {
        $parts[] = (string) $details['extra'];
    }

    return $parts !== [] ? implode(', ', $parts) : '—';
}

function activity_log_format_optional_scalar(mixed $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return (string) $value;
}

function activity_log_format_optional_number(mixed $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    if (is_numeric($value)) {
        $number = (float) $value;
        $formatted = rtrim(rtrim(number_format($number, 1, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    return (string) $value;
}

function search_activity_logs(array $filters): array
{
    $page = max(1, (int) ($filters['page'] ?? 1));
    $perPage = 50;
    $offset = ($page - 1) * $perPage;

    $where = ['1=1'];
    $params = [];

    $action = trim((string) ($filters['action'] ?? ''));
    if ($action !== '') {
        $where[] = 'l.action = ?';
        $params[] = $action;
    }

    $userId = (int) ($filters['user_id'] ?? 0);
    if ($userId > 0) {
        $where[] = 'l.user_id = ?';
        $params[] = $userId;
    }

    $dateFrom = trim((string) ($filters['date_from'] ?? ''));
    if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[] = 'l.created_at >= ?';
        $params[] = $dateFrom . ' 00:00:00';
    }

    $dateTo = trim((string) ($filters['date_to'] ?? ''));
    if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where[] = 'l.created_at <= ?';
        $params[] = $dateTo . ' 23:59:59';
    }

    $query = trim((string) ($filters['q'] ?? ''));
    if ($query !== '') {
        $where[] = '(u.full_name LIKE ? OR l.details_json LIKE ? OR l.action LIKE ?)';
        $like = '%' . $query . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $whereSql = implode(' AND ', $where);

    $countStmt = db()->prepare(
        "SELECT COUNT(*)
         FROM activity_logs l
         LEFT JOIN users u ON u.id = l.user_id
         WHERE $whereSql"
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $listParams = $params;
    $limit = (int) $perPage;
    $offsetInt = (int) $offset;
    $stmt = db()->prepare(
        "SELECT l.*, u.full_name AS user_name, u.role AS user_role
         FROM activity_logs l
         LEFT JOIN users u ON u.id = l.user_id
         WHERE $whereSql
         ORDER BY l.created_at DESC, l.id DESC
         LIMIT $limit OFFSET $offsetInt"
    );
    $stmt->execute($listParams);
    $items = $stmt->fetchAll();

    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'pages' => max(1, (int) ceil($total / $perPage)),
    ];
}

function list_activity_log_users(): array
{
    $stmt = db()->query(
        "SELECT DISTINCT u.id, u.full_name
         FROM activity_logs l
         INNER JOIN users u ON u.id = l.user_id
         ORDER BY u.full_name ASC"
    );

    return $stmt->fetchAll();
}

function activity_log_journal_context(int $curriculumItemId): array
{
    $item = get_curriculum_item_by_id($curriculumItemId);
    if ($item === null) {
        return [];
    }

    return [
        'curriculum_item_id' => $curriculumItemId,
        'group_id' => (int) $item['group_id'],
        'group_number' => (string) $item['group_number'],
        'subject_name' => (string) $item['subject_name'],
        'academic_year' => (string) $item['academic_year'],
    ];
}

function activity_log_lesson_details(array $lesson): array
{
    $ctx = activity_log_journal_context((int) $lesson['curriculum_item_id']);
    $date = (string) ($lesson['lesson_date'] ?? '');
    if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('d.m.Y', (int) strtotime($date));
    }

    return array_merge($ctx, [
        'lesson_id' => (int) ($lesson['id'] ?? 0),
        'lesson_date' => $date,
        'grade_type_label' => ($lesson['grade_type'] ?? 'current') === 'control' ? 'Контрольная' : 'Текущая',
        'topic_title' => trim((string) ($lesson['topic_title'] ?? '')),
        'note' => trim((string) ($lesson['note'] ?? '')),
    ]);
}

function activity_log_journal_grade(
    array $lesson,
    array $student,
    string $oldMark,
    string $newMark,
    bool $activity,
    bool $late
): void {
    $details = activity_log_lesson_details($lesson);
    $details['student_name'] = person_last_first_name((string) $student['full_name']);
    $details['old_mark'] = $oldMark === '' ? '—' : $oldMark;
    $details['mark'] = $newMark === '' ? '—' : $newMark;

    $flags = [];
    if ($activity) {
        $flags[] = 'активность';
    }
    if ($late) {
        $flags[] = 'опоздание';
    }
    if ($flags !== []) {
        $details['extra'] = implode(', ', $flags);
    }

    log_activity(
        'journal.grade_save',
        'journal_grade',
        (int) $lesson['id'],
        (int) $lesson['group_id'],
        $details
    );
}

/**
 * Лог сохранения строки ручного БРС.
 *
 * @param array<string, mixed> $sheet
 * @param array<string, mixed> $student
 * @param array<string, mixed>|null $oldEntry
 * @param array<string, mixed> $calc
 */
function activity_log_manual_brs_entry(
    array $sheet,
    array $student,
    ?array $oldEntry,
    array $calc,
    ?int $entryId = null
): void {
    require_once __DIR__ . '/../modules/manual_brs/includes/periods.php';
    require_once __DIR__ . '/../modules/manual_brs/includes/calculate.php';

    $itemId = (int) ($sheet['curriculum_item_id'] ?? 0);
    $ctx = activity_log_journal_context($itemId);
    $periodMeta = manual_brs_period_meta((int) ($sheet['period'] ?? 1));

    $oldPoints = $oldEntry !== null && $oldEntry['points'] !== null
        ? round((float) $oldEntry['points'], 1)
        : null;
    $newPoints = isset($calc['points']) && $calc['points'] !== null
        ? round((float) $calc['points'], 1)
        : null;
    $oldGrade = $oldEntry !== null && $oldEntry['grade'] !== null
        ? (string) (int) $oldEntry['grade']
        : '—';
    $newGrade = isset($calc['grade']) && $calc['grade'] !== null
        ? (string) (int) $calc['grade']
        : '—';

    $oldCurrent = $oldEntry
        ? manual_brs_marks_to_input(manual_brs_parse_marks((string) ($oldEntry['current_marks'] ?? '')))
        : '';
    $newCurrent = manual_brs_marks_to_input($calc['current_marks'] ?? []);
    $oldControl = $oldEntry
        ? manual_brs_marks_to_input(manual_brs_parse_marks((string) ($oldEntry['control_marks'] ?? '')))
        : '';
    $newControl = manual_brs_marks_to_input($calc['control_marks'] ?? []);

    $oldAbsent = $oldEntry ? (int) ($oldEntry['absent_count'] ?? 0) : 0;
    $newAbsent = (int) ($calc['absent_count'] ?? 0);
    $oldLate = $oldEntry ? (int) ($oldEntry['late_count'] ?? 0) : 0;
    $newLate = (int) ($calc['late_count'] ?? 0);
    $oldActivity = $oldEntry ? (int) ($oldEntry['activity_count'] ?? 0) : 0;
    $newActivity = (int) ($calc['activity_count'] ?? 0);

    $changed = $oldPoints !== $newPoints
        || $oldGrade !== $newGrade
        || $oldCurrent !== $newCurrent
        || $oldControl !== $newControl
        || $oldAbsent !== $newAbsent
        || $oldLate !== $newLate
        || $oldActivity !== $newActivity;

    if (!$changed) {
        return;
    }

    $extraParts = [];
    if ($oldAbsent !== $newAbsent) {
        $extraParts[] = 'Н ' . $oldAbsent . '→' . $newAbsent;
    }
    if ($oldLate !== $newLate) {
        $extraParts[] = 'опоз. ' . $oldLate . '→' . $newLate;
    }
    if ($oldActivity !== $newActivity) {
        $extraParts[] = 'актив. ' . $oldActivity . '→' . $newActivity;
    }

    $details = array_merge($ctx, [
        'module' => 'Ручное БРС',
        'period_label' => (string) ($periodMeta['label'] ?? ''),
        'academic_year' => (string) ($sheet['academic_year'] ?? ($ctx['academic_year'] ?? '')),
        'student_name' => person_last_first_name((string) ($student['full_name'] ?? '')),
        'old_mark' => $oldGrade,
        'mark' => $newGrade,
        'old_points' => $oldPoints,
        'points' => $newPoints,
        'old_current_marks' => $oldCurrent !== '' ? $oldCurrent : null,
        'current_marks' => $newCurrent !== '' ? $newCurrent : null,
        'old_control_marks' => $oldControl !== '' ? $oldControl : null,
        'control_marks' => $newControl !== '' ? $newControl : null,
    ]);
    if ($extraParts !== []) {
        $details['extra'] = implode(', ', $extraParts);
    }

    log_activity(
        'manual_brs.entry_save',
        'manual_brs_entry',
        $entryId,
        (int) ($sheet['group_id'] ?? ($ctx['group_id'] ?? 0)) ?: null,
        $details
    );
}

/**
 * Лог изменения ПА ручного БРС.
 *
 * @param array<string, mixed> $student
 */
function activity_log_manual_brs_pa(
    string $academicYear,
    string $semester,
    int $curriculumItemId,
    array $student,
    ?int $oldGrade,
    ?int $newGrade
): void {
    if ($oldGrade === $newGrade) {
        return;
    }

    $ctx = activity_log_journal_context($curriculumItemId);
    $details = array_merge($ctx, [
        'module' => 'Ручное БРС',
        'period_label' => $semester === '2' ? '2 семестр' : '1 семестр',
        'academic_year' => $academicYear,
        'student_name' => person_last_first_name((string) ($student['full_name'] ?? '')),
        'old_pa_grade' => $oldGrade,
        'pa_grade' => $newGrade,
        'old_mark' => $oldGrade !== null ? (string) $oldGrade : '—',
        'mark' => $newGrade !== null ? (string) $newGrade : '—',
    ]);

    log_activity(
        'manual_brs.pa_save',
        'manual_brs_pa',
        $curriculumItemId,
        (int) ($student['group_id'] ?? ($ctx['group_id'] ?? 0)) ?: null,
        $details
    );
}
