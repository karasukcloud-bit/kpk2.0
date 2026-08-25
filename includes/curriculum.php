<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/organization.php';
require_once __DIR__ . '/auth.php';

const CURRICULUM_SEMESTERS = ['1', '2', 'both'];

function semester_label(string $semester): string
{
    if ($semester === '1') {
        return '1 семестр';
    }
    if ($semester === '2') {
        return '2 семестр';
    }
    if ($semester === 'both') {
        return '1 и 2 семестр';
    }

    return $semester;
}

function normalize_academic_year(string $year): ?string
{
    $year = trim($year);

    if (!preg_match('/^\d{4}-\d{4}$/', $year)) {
        return null;
    }

    [$start, $end] = array_map('intval', explode('-', $year));

    if ($end !== $start + 1) {
        return null;
    }

    return $start . '-' . $end;
}

function get_default_academic_year(): string
{
    $month = (int) date('n');
    $year = (int) date('Y');

    if ($month >= 9) {
        return $year . '-' . ($year + 1);
    }

    return ($year - 1) . '-' . $year;
}

function get_academic_years_from_db(): array
{
    $stmt = db()->query(
        'SELECT DISTINCT academic_year FROM curriculum_plans ORDER BY academic_year DESC'
    );

    return array_column($stmt->fetchAll(), 'academic_year');
}

function get_academic_year_options(
    ?string $selected = null,
    int $fromStartYear = 2000,
    ?int $toStartYear = null
): array {
    $selected = normalize_academic_year((string) ($selected ?? '')) ?? get_default_academic_year();
    $default = get_default_academic_year();
    $currentStart = (int) explode('-', $default)[0];
    if ($toStartYear === null) {
        require_once __DIR__ . '/gradebook.php';
        $toStartYear = function_exists('get_academic_year_to_start_year')
            ? get_academic_year_to_start_year()
            : ($currentStart + 20);
    }
    if ($toStartYear < $fromStartYear) {
        $toStartYear = $fromStartYear;
    }

    $years = [];
    for ($start = $toStartYear; $start >= $fromStartYear; $start--) {
        $years[] = $start . '-' . ($start + 1);
    }

    foreach (get_academic_years_from_db() as $year) {
        $normalized = normalize_academic_year((string) $year);
        if ($normalized !== null) {
            $years[] = $normalized;
        }
    }

    $years[] = $selected;
    $years[] = $default;

    $years = array_values(array_unique($years));
    rsort($years, SORT_STRING);

    return $years;
}

function validate_semester(string $semester): bool
{
    return in_array($semester, CURRICULUM_SEMESTERS, true);
}

function find_subject_by_name(string $name): ?array
{
    $name = trim($name);
    $stmt = db()->prepare('SELECT * FROM subjects WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function get_or_create_subject(string $name): array
{
    $name = trim($name);

    if ($name === '') {
        return ['success' => false, 'error' => 'Укажите название предмета.'];
    }

    $existing = find_subject_by_name($name);
    if ($existing) {
        return ['success' => true, 'subject_id' => (int) $existing['id']];
    }

    $stmt = db()->prepare('INSERT INTO subjects (name) VALUES (?)');
    $stmt->execute([$name]);

    return ['success' => true, 'subject_id' => (int) db()->lastInsertId()];
}

function get_all_subject_names(): array
{
    $stmt = db()->query('SELECT name FROM subjects ORDER BY name ASC');

    return array_column($stmt->fetchAll(), 'name');
}

function get_curriculum_plan(int $groupId, string $academicYear): ?array
{
    $academicYear = normalize_academic_year($academicYear);
    if ($academicYear === null) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT * FROM curriculum_plans WHERE group_id = ? AND academic_year = ? LIMIT 1'
    );
    $stmt->execute([$groupId, $academicYear]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function get_or_create_curriculum_plan(int $groupId, string $academicYear): array
{
    if (get_group_by_id($groupId) === null) {
        return ['success' => false, 'error' => 'Группа не найдена.'];
    }

    $academicYear = normalize_academic_year($academicYear);
    if ($academicYear === null) {
        return ['success' => false, 'error' => 'Некорректный учебный год. Формат: 2025-2026.'];
    }

    $plan = get_curriculum_plan($groupId, $academicYear);
    if ($plan) {
        return ['success' => true, 'plan_id' => (int) $plan['id'], 'plan' => $plan];
    }

    $stmt = db()->prepare(
        'INSERT INTO curriculum_plans (group_id, academic_year) VALUES (?, ?)'
    );
    $stmt->execute([$groupId, $academicYear]);
    $planId = (int) db()->lastInsertId();

    return [
        'success' => true,
        'plan_id' => $planId,
        'plan'    => get_curriculum_plan_by_id($planId),
    ];
}

function get_curriculum_plan_by_id(int $planId): ?array
{
    $stmt = db()->prepare(
        'SELECT cp.*, g.number AS group_number, g.specialty_id,
                s.name AS specialty_name, s.code AS specialty_code
         FROM curriculum_plans cp
         INNER JOIN study_groups g ON g.id = cp.group_id
         INNER JOIN specialties s ON s.id = g.specialty_id
         WHERE cp.id = ?
         LIMIT 1'
    );
    $stmt->execute([$planId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function get_groups_with_curriculum_stats(string $academicYear): array
{
    $academicYear = normalize_academic_year($academicYear);
    if ($academicYear === null) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT g.id, g.number, s.name AS specialty_name, s.code AS specialty_code,
                cp.id AS plan_id,
                COUNT(ci.id) AS subjects_count
         FROM study_groups g
         INNER JOIN specialties s ON s.id = g.specialty_id
         LEFT JOIN curriculum_plans cp
            ON cp.group_id = g.id AND cp.academic_year = ?
         LEFT JOIN curriculum_items ci ON ci.curriculum_plan_id = cp.id
         GROUP BY g.id, g.number, s.name, s.code, cp.id
         ORDER BY g.number ASC'
    );
    $stmt->execute([$academicYear]);

    return $stmt->fetchAll();
}

function get_curriculum_items(int $planId): array
{
    $stmt = db()->prepare(
        'SELECT ci.id, ci.curriculum_plan_id, ci.subject_id, ci.teacher_id, ci.semester, ci.sort_order,
                sub.name AS subject_name,
                u.full_name AS teacher_name
         FROM curriculum_items ci
         INNER JOIN subjects sub ON sub.id = ci.subject_id
         LEFT JOIN users u ON u.id = ci.teacher_id
         WHERE ci.curriculum_plan_id = ?
         ORDER BY ci.sort_order ASC, sub.name ASC'
    );
    $stmt->execute([$planId]);

    return $stmt->fetchAll();
}

function get_curriculum_item_by_id(int $itemId): ?array
{
    $stmt = db()->prepare(
        'SELECT ci.*, sub.name AS subject_name, cp.group_id, cp.academic_year,
                g.number AS group_number,
                u.full_name AS teacher_name,
                u.email AS teacher_email,
                u.phone AS teacher_phone,
                u.position AS teacher_position
         FROM curriculum_items ci
         INNER JOIN subjects sub ON sub.id = ci.subject_id
         INNER JOIN curriculum_plans cp ON cp.id = ci.curriculum_plan_id
         INNER JOIN study_groups g ON g.id = cp.group_id
         LEFT JOIN users u ON u.id = ci.teacher_id
         WHERE ci.id = ?
         LIMIT 1'
    );
    $stmt->execute([$itemId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * Предметы учебного плана группы за год — для ведомостей и других модулей.
 */
function get_group_curriculum_subjects(int $groupId, string $academicYear, ?string $semester = null): array
{
    $plan = get_curriculum_plan($groupId, $academicYear);
    if ($plan === null) {
        return [];
    }

    $items = get_curriculum_items((int) $plan['id']);

    if ($semester === null) {
        return enrich_curriculum_items($items, $plan);
    }

    if (!validate_semester($semester)) {
        return [];
    }

    $filtered = array_filter($items, static function (array $item) use ($semester): bool {
        return $item['semester'] === $semester || $item['semester'] === 'both';
    });

    return enrich_curriculum_items(array_values($filtered), $plan);
}

function enrich_curriculum_items(array $items, array $plan): array
{
    foreach ($items as &$item) {
        $item['curriculum_plan_id'] = (int) $plan['id'];
        $item['group_id'] = (int) $plan['group_id'];
        $item['academic_year'] = $plan['academic_year'];
        $item['curriculum_item_id'] = (int) $item['id'];
    }
    unset($item);

    return $items;
}

function add_curriculum_item(int $planId, string $subjectName, string $semester, ?int $teacherId = null): array
{
    $plan = get_curriculum_plan_by_id($planId);
    if ($plan === null) {
        return ['success' => false, 'error' => 'Учебный план не найден.'];
    }

    if (!validate_semester($semester)) {
        return ['success' => false, 'error' => 'Выберите семестр: 1, 2 или 1 и 2.'];
    }

    $subjectResult = get_or_create_subject($subjectName);
    if (!$subjectResult['success']) {
        return $subjectResult;
    }

    $teacherCheck = normalize_curriculum_teacher_id($teacherId);
    if (!$teacherCheck['success']) {
        return $teacherCheck;
    }
    $teacherId = $teacherCheck['teacher_id'];

    $subjectId = $subjectResult['subject_id'];

    $stmt = db()->prepare(
        'SELECT id FROM curriculum_items
         WHERE curriculum_plan_id = ? AND subject_id = ?
         LIMIT 1'
    );
    $stmt->execute([$planId, $subjectId]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Этот предмет уже есть в учебном плане группы.'];
    }

    $sortOrder = get_next_curriculum_sort_order($planId);

    $stmt = db()->prepare(
        'INSERT INTO curriculum_items (curriculum_plan_id, subject_id, teacher_id, semester, sort_order)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$planId, $subjectId, $teacherId, $semester, $sortOrder]);

    return ['success' => true, 'id' => (int) db()->lastInsertId()];
}

function update_curriculum_item(int $itemId, string $subjectName, string $semester, ?int $teacherId = null): array
{
    $item = get_curriculum_item_by_id($itemId);
    if ($item === null) {
        return ['success' => false, 'error' => 'Запись не найдена.'];
    }

    if (!validate_semester($semester)) {
        return ['success' => false, 'error' => 'Выберите семестр: 1, 2 или 1 и 2.'];
    }

    $subjectResult = get_or_create_subject($subjectName);
    if (!$subjectResult['success']) {
        return $subjectResult;
    }

    $teacherCheck = normalize_curriculum_teacher_id($teacherId);
    if (!$teacherCheck['success']) {
        return $teacherCheck;
    }
    $teacherId = $teacherCheck['teacher_id'];

    $subjectId = $subjectResult['subject_id'];
    $planId = (int) $item['curriculum_plan_id'];

    $stmt = db()->prepare(
        'SELECT id FROM curriculum_items
         WHERE curriculum_plan_id = ? AND subject_id = ? AND id <> ?
         LIMIT 1'
    );
    $stmt->execute([$planId, $subjectId, $itemId]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Этот предмет уже есть в учебном плане группы.'];
    }

    $stmt = db()->prepare(
        'UPDATE curriculum_items SET subject_id = ?, teacher_id = ?, semester = ? WHERE id = ?'
    );
    $stmt->execute([$subjectId, $teacherId, $semester, $itemId]);

    return ['success' => true];
}

function normalize_curriculum_teacher_id(?int $teacherId): array
{
    if ($teacherId === null || $teacherId === 0) {
        return ['success' => true, 'teacher_id' => null];
    }

    $stmt = db()->prepare(
        "SELECT id FROM users WHERE id = ? AND role = 'teacher' AND is_active = 1 LIMIT 1"
    );
    $stmt->execute([$teacherId]);
    if (!$stmt->fetch()) {
        return ['success' => false, 'error' => 'Преподаватель не найден.'];
    }

    return ['success' => true, 'teacher_id' => $teacherId];
}

function delete_curriculum_item(int $itemId): array
{
    if (get_curriculum_item_by_id($itemId) === null) {
        return ['success' => false, 'error' => 'Запись не найдена.'];
    }

    $stmt = db()->prepare('DELETE FROM curriculum_items WHERE id = ?');
    $stmt->execute([$itemId]);

    return ['success' => true];
}

function get_next_curriculum_sort_order(int $planId): int
{
    $stmt = db()->prepare(
        'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM curriculum_items WHERE curriculum_plan_id = ?'
    );
    $stmt->execute([$planId]);

    return (int) $stmt->fetchColumn();
}

function render_semester_options(string $selected = '1'): string
{
    $html = '';

    foreach (CURRICULUM_SEMESTERS as $semester) {
        $isSelected = $semester === $selected ? ' selected' : '';
        $html .= '<option value="' . e($semester) . '"' . $isSelected . '>'
            . e(semester_label($semester)) . '</option>';
    }

    return $html;
}

function render_curriculum_semester_table(array $items, int $groupId, string $academicYear): string
{
    if ($items === []) {
        return '<p class="text-muted">Нет предметов.</p>';
    }

    $html = '<div class="table-wrap"><table class="table"><thead><tr>'
        . '<th>№</th><th>Предмет</th><th>Преподаватель</th><th class="table__actions-col">Действия</th>'
        . '</tr></thead><tbody>';

    foreach ($items as $index => $item) {
        $id = (int) $item['id'];
        $year = e(urlencode($academicYear));
        $html .= '<tr>'
            . '<td>' . ($index + 1) . '</td>'
            . '<td>' . e($item['subject_name']);

        if ($item['semester'] === 'both') {
            $html .= ' ' . render_semester_badge('both');
        }

        $html .= '</td><td>' . e($item['teacher_name'] ?? '—') . '</td><td class="table__actions">'
            . '<button type="button" class="btn btn--ghost btn--sm"'
            . ' data-curriculum-edit-open'
            . ' data-item-id="' . $id . '"'
            . ' data-subject-name="' . e($item['subject_name']) . '"'
            . ' data-semester="' . e((string) $item['semester']) . '"'
            . ' data-teacher-id="' . (int) ($item['teacher_id'] ?? 0) . '"'
            . '>Изменить</button>'
            . '<a href="curriculum_ktp.php?item_id=' . $id . '&group_id=' . $groupId . '&year=' . $year
            . '" class="btn btn--ghost btn--sm">КТП</a>'
            . '<form method="post" class="inline-form" onsubmit="return confirm(\'Удалить предмет из учебного плана?\');">'
            . csrf_field()
            . '<input type="hidden" name="action" value="delete_item">'
            . '<input type="hidden" name="item_id" value="' . $id . '">'
            . '<button type="submit" class="btn btn--danger btn--sm">Удалить</button>'
            . '</form></td></tr>';
    }

    return $html . '</tbody></table></div>';
}

function render_semester_badge(string $semester): string
{
    if ($semester === '1') {
        $class = 'badge--semester-1';
    } elseif ($semester === '2') {
        $class = 'badge--semester-2';
    } elseif ($semester === 'both') {
        $class = 'badge--semester-both';
    } else {
        $class = 'badge--soon';
    }

    return '<span class="badge badge--role ' . $class . '">' . e(semester_label($semester)) . '</span>';
}

function curriculum_edit_url(int $groupId, string $academicYear, string $panel = 'admin'): string
{
    return $panel . '/curriculum_edit.php?group_id=' . $groupId . '&year=' . urlencode($academicYear);
}
