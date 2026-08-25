<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/organization.php';
require_once __DIR__ . '/students.php';
require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/curriculum.php';
require_once __DIR__ . '/archive.php';

function glaz_debt_key(int $studentId, int $curriculumItemId, string $academicYear, string $semester): string
{
    return $studentId . ':' . $curriculumItemId . ':' . $academicYear . ':' . $semester;
}

function glaz_period_label(string $academicYear, string $semester): string
{
    return $academicYear . ' · ' . semester_label($semester);
}

/** ID студентов, исключённых из ГЛАЗ (отчисленные; архивы не затрагиваются). */
function get_student_ids_excluded_from_glaz(): array
{
    static $ids = null;
    if ($ids !== null) {
        return $ids;
    }

    $ids = [];
    if (!db()->query("SHOW TABLES LIKE 'expelled_students'")->fetch()) {
        return $ids;
    }

    $stmt = db()->query(
        'SELECT original_student_id
         FROM expelled_students
         WHERE original_student_id IS NOT NULL'
    );
    foreach ($stmt->fetchAll() as $row) {
        $ids[(int) $row['original_student_id']] = true;
    }

    return $ids;
}

function get_all_academic_debts(): array
{
    $excluded = get_student_ids_excluded_from_glaz();

    $stmt = db()->query(
        "SELECT g.student_id, g.curriculum_item_id, g.group_id,
                ap.academic_year, ap.semester, ap.archived_at,
                ags.full_name AS student_name,
                agg.group_number,
                agsub.subject_name
         FROM archive_gradebook_grades g
         INNER JOIN archive_periods ap
            ON ap.id = g.archive_id AND ap.archive_type = 'gradebook'
         INNER JOIN archive_gradebook_students ags
            ON ags.archive_id = g.archive_id
           AND ags.group_id = g.group_id
           AND ags.student_id = g.student_id
         INNER JOIN archive_gradebook_groups agg
            ON agg.archive_id = g.archive_id AND agg.group_id = g.group_id
         INNER JOIN archive_gradebook_subjects agsub
            ON agsub.archive_id = g.archive_id
           AND agsub.group_id = g.group_id
           AND agsub.curriculum_item_id = g.curriculum_item_id
         WHERE g.grade = 2"
    );

    $debts = [];
    foreach ($stmt->fetchAll() as $row) {
        $studentId = (int) $row['student_id'];
        if (isset($excluded[$studentId])) {
            continue;
        }

        $key = glaz_debt_key(
            $studentId,
            (int) $row['curriculum_item_id'],
            (string) $row['academic_year'],
            (string) $row['semester']
        );
        $debts[] = [
            'student_id' => $studentId,
            'curriculum_item_id' => (int) $row['curriculum_item_id'],
            'group_id' => (int) $row['group_id'],
            'group_number' => (string) $row['group_number'],
            'student_name' => (string) $row['student_name'],
            'subject_name' => (string) $row['subject_name'],
            'academic_year' => (string) $row['academic_year'],
            'semester' => (string) $row['semester'],
            'period_label' => glaz_period_label((string) $row['academic_year'], (string) $row['semester']),
            'debt_key' => $key,
            'archived_at' => $row['archived_at'] !== null ? (string) $row['archived_at'] : null,
        ];
    }

    usort($debts, static function (array $a, array $b): int {
        $groupCmp = strnatcasecmp($a['group_number'], $b['group_number']);
        if ($groupCmp !== 0) {
            return $groupCmp;
        }

        $nameCmp = strcasecmp($a['student_name'], $b['student_name']);
        if ($nameCmp !== 0) {
            return $nameCmp;
        }

        $yearCmp = strcmp($b['academic_year'], $a['academic_year']);
        if ($yearCmp !== 0) {
            return $yearCmp;
        }

        $semCmp = strcmp($a['semester'], $b['semester']);
        if ($semCmp !== 0) {
            return $semCmp;
        }

        return strcasecmp($a['subject_name'], $b['subject_name']);
    });

    return $debts;
}

function get_glaz_schedules_index(): array
{
    $stmt = db()->query(
        'SELECT id, student_id, curriculum_item_id, academic_year, semester,
                liquidation_date, liquidation_time
         FROM glaz_schedules'
    );
    $rows = $stmt->fetchAll();
    if ($rows === []) {
        return ['schedules' => [], 'commission' => []];
    }

    $scheduleIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
    $placeholders = implode(',', array_fill(0, count($scheduleIds), '?'));
    $commStmt = db()->prepare(
        "SELECT gcm.schedule_id, gcm.teacher_id, gcm.sort_order, u.full_name
         FROM glaz_commission_members gcm
         INNER JOIN users u ON u.id = gcm.teacher_id
         WHERE gcm.schedule_id IN ($placeholders)
         ORDER BY gcm.sort_order ASC, u.full_name ASC"
    );
    $commStmt->execute($scheduleIds);

    $commission = [];
    foreach ($commStmt->fetchAll() as $row) {
        $scheduleId = (int) $row['schedule_id'];
        $commission[$scheduleId][] = [
            'teacher_id' => (int) $row['teacher_id'],
            'full_name' => (string) $row['full_name'],
        ];
    }

    $index = [];
    foreach ($rows as $row) {
        $key = glaz_debt_key(
            (int) $row['student_id'],
            (int) $row['curriculum_item_id'],
            (string) $row['academic_year'],
            (string) $row['semester']
        );
        $scheduleId = (int) $row['id'];
        $members = $commission[$scheduleId] ?? [];
        $index[$key] = [
            'id' => $scheduleId,
            'liquidation_date' => $row['liquidation_date'] !== null ? (string) $row['liquidation_date'] : '',
            'liquidation_time' => $row['liquidation_time'] !== null ? substr((string) $row['liquidation_time'], 0, 5) : '',
            'commission' => $members,
            'commission_ids' => array_map(static fn (array $m): int => (int) $m['teacher_id'], $members),
            'commission_label' => implode(', ', array_map(static fn (array $m): string => (string) $m['full_name'], $members)),
        ];
    }

    return ['schedules' => $index, 'commission' => $commission];
}

function build_glaz_table_groups(array $debts, array $schedules): array
{
    $groups = [];

    foreach ($debts as $debt) {
        $groupNumber = $debt['group_number'];
        $studentId = $debt['student_id'];

        if (!isset($groups[$groupNumber])) {
            $groups[$groupNumber] = [
                'group_number' => $groupNumber,
                'students' => [],
                'row_count' => 0,
            ];
        }

        if (!isset($groups[$groupNumber]['students'][$studentId])) {
            $groups[$groupNumber]['students'][$studentId] = [
                'student_id' => $studentId,
                'student_name' => person_last_first_name($debt['student_name']),
                'items' => [],
                'has_expired_debt' => false,
            ];
        }

        $schedule = $schedules[$debt['debt_key']] ?? [
            'liquidation_date' => '',
            'liquidation_time' => '',
            'commission' => [],
            'commission_ids' => [],
            'commission_label' => '',
        ];

        $isExpired = glaz_is_debt_expired(
            (string) $debt['academic_year'],
            (string) $debt['semester'],
            $debt['archived_at'] ?? null
        );

        if ($isExpired) {
            $groups[$groupNumber]['students'][$studentId]['has_expired_debt'] = true;
        }

        $groups[$groupNumber]['students'][$studentId]['items'][] = array_merge($debt, [
            'schedule' => $schedule,
            'is_expired' => $isExpired,
        ]);
        $groups[$groupNumber]['row_count']++;
    }

    $result = [];
    foreach ($groups as $group) {
        $students = [];
        foreach ($group['students'] as $student) {
            $student['rowspan'] = count($student['items']);
            $students[] = $student;
        }

        $result[] = [
            'group_number' => $group['group_number'],
            'rowspan' => $group['row_count'],
            'students' => $students,
        ];
    }

    return $result;
}

function glaz_commission_teacher_ids(array $schedule): array
{
    $ids = array_slice($schedule['commission_ids'] ?? [], 0, 3);

    return $ids !== [] ? $ids : [0];
}

function glaz_academic_year_bounds(string $academicYear): ?array
{
    if (!preg_match('/^(\d{4})-(\d{4})$/', $academicYear, $matches)) {
        return null;
    }

    return [(int) $matches[1], (int) $matches[2]];
}

function glaz_estimated_debt_origin_date(string $academicYear, string $semester): ?DateTimeImmutable
{
    $bounds = glaz_academic_year_bounds($academicYear);
    if ($bounds === null) {
        return null;
    }

    [, $year2] = $bounds;

    if ($semester === '1') {
        return new DateTimeImmutable(sprintf('%d-01-31', $year2));
    }

    if ($semester === '2') {
        return new DateTimeImmutable(sprintf('%d-06-30', $year2));
    }

    return null;
}

function glaz_debt_origin_date(string $academicYear, string $semester, ?string $archivedAt = null): ?DateTimeImmutable
{
    if ($archivedAt !== null && trim($archivedAt) !== '') {
        $datePart = substr(trim($archivedAt), 0, 10);
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $datePart);
        if ($parsed instanceof DateTimeImmutable) {
            return $parsed;
        }
    }

    return glaz_estimated_debt_origin_date($academicYear, $semester);
}

function glaz_is_debt_expired(string $academicYear, string $semester, ?string $archivedAt = null, ?DateTimeImmutable $now = null): bool
{
    $origin = glaz_debt_origin_date($academicYear, $semester, $archivedAt);
    if ($origin === null) {
        return false;
    }

    $now = $now ?? new DateTimeImmutable('today');

    return $now >= $origin->modify('+1 year');
}

function glaz_expiry_warning_title(): string
{
    return 'Прошёл календарный год с момента образования задолженности. '
        . 'Задолженность не ликвидирована — требуется рассмотреть вопрос об отчислении.';
}

function format_glaz_schedule_text(array $schedule): string
{
    $parts = [];
    $date = trim((string) ($schedule['liquidation_date'] ?? ''));
    $time = trim((string) ($schedule['liquidation_time'] ?? ''));

    if ($date !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        $parts[] = $dt ? $dt->format('d.m.Y') : $date;
    }

    if ($time !== '') {
        $parts[] = $time;
    }

    $line = implode(' ', $parts);
    $commission = trim((string) ($schedule['commission_label'] ?? ''));

    if ($commission !== '') {
        if ($line !== '') {
            return $line . "\n" . $commission;
        }

        return $commission;
    }

    return $line;
}

const GLAZ_NOTIFICATION_TITLE = 'ГЛАЗ — график ликвидации задолженности';

function notification_is_glaz(array $notification): bool
{
    return (string) ($notification['title'] ?? '') === GLAZ_NOTIFICATION_TITLE;
}

function notification_glaz_view_path(): string
{
    if (can_use_teacher_panel() || can_use_curator_panel()) {
        return 'teacher/glaz.php';
    }

    return 'deputy/glaz.php';
}

function get_glaz_view_highlights(?int $userId = null): array
{
    $userId = $userId ?? (int) (current_user()['id'] ?? 0);
    $groupNumbers = [];
    $teacherId = null;

    if ($userId > 0 && can_use_teacher_panel()) {
        $teacherId = $userId;
    }

    if ($userId > 0) {
        $curatorGroup = get_curator_group($userId);
        if ($curatorGroup) {
            $groupNumbers[] = (string) $curatorGroup['number'];
        }
    }

    return [
        'teacher_id' => $teacherId,
        'group_numbers' => $groupNumbers,
    ];
}

function render_glaz_highlighted_person_name(string $fullName): string
{
    $parts = split_person_full_name($fullName);
    $lastName = $parts['last_name'];

    if ($lastName === '') {
        return '<span class="glaz-highlight-name">' . e($fullName) . '</span>';
    }

    $rest = trim($parts['first_name'] . ' ' . $parts['middle_name']);
    if ($rest === '') {
        return '<span class="glaz-highlight-name">' . e($lastName) . '</span>';
    }

    return e($rest) . ' <span class="glaz-highlight-name">' . e($lastName) . '</span>';
}

function render_glaz_commission_html(array $commission, ?int $highlightTeacherId = null): string
{
    if ($commission === []) {
        return '';
    }

    $items = [];
    foreach ($commission as $member) {
        $memberId = (int) ($member['teacher_id'] ?? 0);
        $fullName = (string) ($member['full_name'] ?? '');

        if ($highlightTeacherId !== null && $memberId === $highlightTeacherId) {
            $items[] = render_glaz_highlighted_person_name($fullName);
        } else {
            $items[] = e($fullName);
        }
    }

    return implode(', ', $items);
}

function format_glaz_schedule_display_html(array $schedule, ?int $highlightTeacherId = null): string
{
    $parts = [];
    $date = trim((string) ($schedule['liquidation_date'] ?? ''));
    $time = trim((string) ($schedule['liquidation_time'] ?? ''));

    if ($date !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        $parts[] = e($dt ? $dt->format('d.m.Y') : $date);
    }

    if ($time !== '') {
        $parts[] = e($time);
    }

    $html = implode(' ', $parts);
    $commissionHtml = render_glaz_commission_html($schedule['commission'] ?? [], $highlightTeacherId);

    if ($commissionHtml !== '') {
        $html = $html !== '' ? $html . '<br>' . $commissionHtml : $commissionHtml;
    }

    return $html;
}

function send_glaz_to_teachers(): array
{
    if (!can_use_deputy_panel()) {
        return ['success' => false, 'error' => 'Недостаточно прав.'];
    }

    require_once __DIR__ . '/notifications.php';
    require_once __DIR__ . '/teachers.php';

    $teachers = get_all_teachers();
    $recipients = array_filter(
        $teachers,
        static fn (array $teacher): bool => !empty($teacher['is_active'])
    );

    if ($recipients === []) {
        return ['success' => false, 'error' => 'Нет активных преподавателей для рассылки.'];
    }

    $body = 'Завуч направил график ликвидации академической задолженности для ознакомления.';
    $sent = 0;

    foreach ($recipients as $teacher) {
        $result = send_personal_notification(
            (int) $teacher['id'],
            GLAZ_NOTIFICATION_TITLE,
            $body
        );
        if (!empty($result['success'])) {
            $sent++;
        }
    }

    if ($sent === 0) {
        return ['success' => false, 'error' => 'Не удалось отправить оповещения.'];
    }

    return ['success' => true, 'sent' => $sent];
}

function save_glaz_schedule(
    int $studentId,
    int $curriculumItemId,
    string $academicYear,
    string $semester,
    string $liquidationDate,
    string $liquidationTime,
    array $teacherIds
): array {
    if (!can_use_deputy_panel()) {
        return ['success' => false, 'error' => 'Недостаточно прав.'];
    }

    $academicYear = normalize_academic_year($academicYear) ?? '';
    $semester = normalize_gradebook_semester($semester);
    if ($academicYear === '') {
        return ['success' => false, 'error' => 'Некорректный учебный год.'];
    }

    if (get_student_by_id($studentId) === null) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    $liquidationDate = trim($liquidationDate);
    $liquidationTime = trim($liquidationTime);

    if ($liquidationDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $liquidationDate)) {
        return ['success' => false, 'error' => 'Некорректная дата ликвидации.'];
    }

    if ($liquidationTime !== '' && !preg_match('/^\d{2}:\d{2}$/', $liquidationTime)) {
        return ['success' => false, 'error' => 'Некорректное время ликвидации.'];
    }

    $teacherIds = array_values(array_unique(array_filter(array_map('intval', $teacherIds), static fn (int $id): bool => $id > 0)));
    if (count($teacherIds) > 3) {
        return ['success' => false, 'error' => 'В комиссии может быть от 1 до 3 преподавателей.'];
    }
    foreach ($teacherIds as $teacherId) {
        require_once __DIR__ . '/teachers.php';
        if (get_teacher_by_id($teacherId) === null) {
            return ['success' => false, 'error' => 'Преподаватель не найден.'];
        }
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'SELECT id FROM glaz_schedules
             WHERE student_id = ? AND curriculum_item_id = ? AND academic_year = ? AND semester = ?
             LIMIT 1'
        );
        $stmt->execute([$studentId, $curriculumItemId, $academicYear, $semester]);
        $existing = $stmt->fetch();

        $dateValue = $liquidationDate !== '' ? $liquidationDate : null;
        $timeValue = $liquidationTime !== '' ? $liquidationTime . ':00' : null;
        $user = current_user();
        $updatedBy = $user ? (int) $user['id'] : null;

        if ($dateValue === null && $timeValue === null && $teacherIds === []) {
            if ($existing) {
                $del = $pdo->prepare('DELETE FROM glaz_schedules WHERE id = ?');
                $del->execute([(int) $existing['id']]);
            }
            $pdo->commit();

            return [
                'success' => true,
                'schedule' => [
                    'liquidation_date' => '',
                    'liquidation_time' => '',
                    'commission_label' => '',
                    'display_text' => '',
                ],
            ];
        }

        if ($existing) {
            $scheduleId = (int) $existing['id'];
            $upd = $pdo->prepare(
                'UPDATE glaz_schedules
                 SET liquidation_date = ?, liquidation_time = ?, updated_by = ?
                 WHERE id = ?'
            );
            $upd->execute([$dateValue, $timeValue, $updatedBy, $scheduleId]);
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO glaz_schedules
                 (student_id, curriculum_item_id, academic_year, semester, liquidation_date, liquidation_time, updated_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $studentId,
                $curriculumItemId,
                $academicYear,
                $semester,
                $dateValue,
                $timeValue,
                $updatedBy,
            ]);
            $scheduleId = (int) $pdo->lastInsertId();
        }

        $pdo->prepare('DELETE FROM glaz_commission_members WHERE schedule_id = ?')->execute([$scheduleId]);

        if ($teacherIds !== []) {
            $insMember = $pdo->prepare(
                'INSERT INTO glaz_commission_members (schedule_id, teacher_id, sort_order) VALUES (?, ?, ?)'
            );
            foreach ($teacherIds as $index => $teacherId) {
                $insMember->execute([$scheduleId, $teacherId, $index + 1]);
            }
        }

        $pdo->commit();

        require_once __DIR__ . '/teachers.php';
        $names = [];
        foreach ($teacherIds as $teacherId) {
            $teacher = get_teacher_by_id($teacherId);
            if ($teacher) {
                $names[] = (string) $teacher['full_name'];
            }
        }

        $schedule = [
            'liquidation_date' => $liquidationDate,
            'liquidation_time' => $liquidationTime,
            'commission_label' => implode(', ', $names),
        ];

        return [
            'success' => true,
            'schedule' => $schedule + ['display_text' => format_glaz_schedule_text($schedule)],
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();

        return ['success' => false, 'error' => 'Не удалось сохранить данные ликвидации.'];
    }
}
