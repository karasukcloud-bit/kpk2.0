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
        'student.create' => 'Добавление студента',
        'student.delete' => 'Удаление студента',
        'journal.lesson_add' => 'Добавление даты в журнал',
        'journal.lesson_update' => 'Изменение даты в журнале',
        'journal.lesson_delete' => 'Удаление даты из журнала',
        'journal.grade_save' => 'Изменение оценки в журнале',
    ];
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
    if (!empty($details['group_number'])) {
        $parts[] = 'группа ' . $details['group_number'];
    }
    if (!empty($details['subject_name'])) {
        $parts[] = 'предмет «' . $details['subject_name'] . '»';
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
    if (!empty($details['topic_title'])) {
        $parts[] = 'тема «' . $details['topic_title'] . '»';
    }
    if (!empty($details['extra'])) {
        $parts[] = (string) $details['extra'];
    }

    return $parts !== [] ? implode(', ', $parts) : '—';
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
