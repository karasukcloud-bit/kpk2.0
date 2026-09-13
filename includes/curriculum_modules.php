<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/curriculum.php';
require_once __DIR__ . '/organization.php';

const CURRICULUM_PRACTICE_KINDS = ['up', 'pp', 'pdp'];
const CURRICULUM_MODULE_MAX_NUMBER = 15;

function get_course_from_group_number(string $number): int
{
    if (preg_match('/^(.*?)(\d)(\d{2})(.*)$/u', $number, $m)) {
        return max(1, min(4, (int) $m[2]));
    }
    if (preg_match('/(\d)/u', $number, $m)) {
        return max(1, min(4, (int) $m[1]));
    }

    return 1;
}

function curriculum_abs_semester(int $course, int $semesterInCourse): int
{
    return ($course - 1) * 2 + $semesterInCourse;
}

function curriculum_course_from_abs(int $absSemester): int
{
    return (int) ceil($absSemester / 2);
}

function curriculum_semester_in_course_from_abs(int $absSemester): int
{
    return $absSemester % 2 === 0 ? 2 : 1;
}

function curriculum_abs_semester_label(int $absSemester): string
{
    $course = curriculum_course_from_abs($absSemester);

    return $course . ' курс ' . $absSemester . ' семестр';
}

function get_group_program_semesters(int $groupId): int
{
    $group = get_group_by_id($groupId);
    if ($group === null) {
        return 6;
    }
    $value = (int) ($group['program_semesters'] ?? 6);

    return in_array($value, [6, 8], true) ? $value : 6;
}

function set_group_program_semesters(int $groupId, int $semesters): array
{
    if (!in_array($semesters, [6, 8], true)) {
        return ['success' => false, 'error' => 'Срок обучения: 6 семестров (3 курса) или 8 (4 курса).'];
    }
    if (get_group_by_id($groupId) === null) {
        return ['success' => false, 'error' => 'Группа не найдена.'];
    }

    $col = db()->query("SHOW COLUMNS FROM study_groups LIKE 'program_semesters'")->fetch();
    if (!$col) {
        return ['success' => false, 'error' => 'Схема не обновлена. Обновите страницу.'];
    }

    $stmt = db()->prepare('UPDATE study_groups SET program_semesters = ? WHERE id = ?');
    $stmt->execute([$semesters, $groupId]);

    return ['success' => true];
}

function practice_kind_label(string $kind): string
{
    $kind = normalize_practice_kind($kind) ?? $kind;
    if ($kind === 'up') {
        return 'УП';
    }
    if ($kind === 'pp') {
        return 'ПП';
    }
    if ($kind === 'pdp') {
        return 'ПДП';
    }

    return $kind;
}

function normalize_practice_kind(?string $kind): ?string
{
    if ($kind === null || $kind === '') {
        return null;
    }
    $map = [
        'up' => 'up',
        'pp' => 'pp',
        'pdp' => 'pdp',
        'educational' => 'up',
        'industrial' => 'pp',
        'prediploma' => 'pdp',
        'уп' => 'up',
        'пп' => 'pp',
        'пдп' => 'pdp',
    ];
    $key = mb_strtolower(trim($kind), 'UTF-8');

    return $map[$key] ?? null;
}

function format_module_code(int $number): string
{
    return 'ПМ ' . str_pad((string) $number, 2, '0', STR_PAD_LEFT);
}

function format_mdk_code(int $moduleNumber, int $index): string
{
    return 'МДК ' . str_pad((string) $moduleNumber, 2, '0', STR_PAD_LEFT)
        . '.' . str_pad((string) $index, 2, '0', STR_PAD_LEFT);
}

function format_practice_code(string $kind, int $moduleNumber, int $index): string
{
    $prefix = practice_kind_label($kind);
    if ($kind === 'pdp') {
        return $prefix;
    }

    return $prefix . ' ' . str_pad((string) $moduleNumber, 2, '0', STR_PAD_LEFT)
        . '.' . str_pad((string) $index, 2, '0', STR_PAD_LEFT);
}

function curriculum_item_covers_abs(array $item, int $absSemester): bool
{
    $start = (int) ($item['start_abs_semester'] ?? 0);
    $end = (int) ($item['end_abs_semester'] ?? 0);
    if ($start < 1 || $end < 1) {
        return false;
    }

    return $absSemester >= $start && $absSemester <= $end;
}

function curriculum_item_covers_course_semester(array $item, int $course, string $semester): bool
{
    if (!in_array($semester, ['1', '2'], true)) {
        return false;
    }
    $abs = curriculum_abs_semester($course, (int) $semester);

    return curriculum_item_covers_abs($item, $abs);
}

function validate_abs_semester_range(int $start, int $end, int $programSemesters): ?string
{
    $max = max(8, $programSemesters);
    if ($start < 1 || $end < 1 || $start > $max || $end > $max) {
        return 'Укажите семестры в пределах 1–' . $max . '.';
    }
    if ($start > $end) {
        return 'Семестр начала не может быть позже семестра окончания.';
    }

    return null;
}

function semester_enum_from_abs_range(int $start, int $end): string
{
    $startSem = curriculum_semester_in_course_from_abs($start);
    $endSem = curriculum_semester_in_course_from_abs($end);
    $startCourse = curriculum_course_from_abs($start);
    $endCourse = curriculum_course_from_abs($end);

    if ($startCourse === $endCourse) {
        if ($startSem === $endSem) {
            return (string) $startSem;
        }

        return 'both';
    }

    return 'both';
}

function get_curriculum_modules_for_group(int $groupId): array
{
    if (!db()->query("SHOW TABLES LIKE 'curriculum_modules'")->fetch()) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT m.*
         FROM curriculum_modules m
         WHERE m.group_id = ?
         ORDER BY m.number ASC'
    );
    $stmt->execute([$groupId]);
    $modules = $stmt->fetchAll();

    foreach ($modules as &$module) {
        $module['code'] = format_module_code((int) $module['number']);
        $module['children'] = get_curriculum_module_children((int) $module['id'], (int) $module['number']);
    }
    unset($module);

    return $modules;
}

function get_curriculum_module_by_id(int $moduleId): ?array
{
    $stmt = db()->prepare('SELECT * FROM curriculum_modules WHERE id = ? LIMIT 1');
    $stmt->execute([$moduleId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function get_curriculum_module_children(int $moduleId, ?int $moduleNumber = null): array
{
    $typeCol = db()->query("SHOW COLUMNS FROM curriculum_items LIKE 'item_type'")->fetch();
    if (!$typeCol) {
        return [];
    }

    if ($moduleNumber === null) {
        $module = get_curriculum_module_by_id($moduleId);
        $moduleNumber = (int) ($module['number'] ?? 0);
    }

    $stmt = db()->prepare(
        'SELECT ci.*, sub.name AS subject_name, u.full_name AS teacher_name
         FROM curriculum_items ci
         INNER JOIN subjects sub ON sub.id = ci.subject_id
         LEFT JOIN users u ON u.id = ci.teacher_id
         WHERE ci.module_id = ? AND ci.item_type IN (\'mdk\', \'practice\')
         ORDER BY FIELD(ci.item_type, \'mdk\', \'practice\'), ci.practice_kind ASC, ci.component_index ASC, sub.name ASC'
    );
    $stmt->execute([$moduleId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['start_label'] = curriculum_abs_semester_label((int) $row['start_abs_semester']);
        $row['end_label'] = curriculum_abs_semester_label((int) $row['end_abs_semester']);
        if ($row['item_type'] === 'mdk') {
            $row['code'] = format_mdk_code($moduleNumber, (int) $row['component_index']);
        } else {
            $row['code'] = format_practice_code(
                (string) $row['practice_kind'],
                $moduleNumber,
                (int) ($row['component_index'] ?? 1)
            );
        }
    }
    unset($row);

    return $rows;
}

function create_curriculum_module(
    int $groupId,
    int $number,
    string $title,
    ?int $programSemesters = null,
    ?int $planId = null
): array {
    if ($number < 1 || $number > CURRICULUM_MODULE_MAX_NUMBER) {
        return ['success' => false, 'error' => 'Номер ПМ: от 01 до 15.'];
    }
    if (get_group_by_id($groupId) === null) {
        return ['success' => false, 'error' => 'Группа не найдена.'];
    }

    if ($programSemesters !== null) {
        $set = set_group_program_semesters($groupId, $programSemesters);
        if (!$set['success']) {
            return $set;
        }
    }

    $title = trim($title);
    $stmt = db()->prepare(
        'SELECT id FROM curriculum_modules WHERE group_id = ? AND number = ? LIMIT 1'
    );
    $stmt->execute([$groupId, $number]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'ПМ ' . str_pad((string) $number, 2, '0', STR_PAD_LEFT) . ' уже добавлен.'];
    }

    $planCol = db()->query("SHOW COLUMNS FROM curriculum_modules LIKE 'curriculum_plan_id'")->fetch();
    if ($planCol) {
        if ($planId === null || $planId < 1) {
            $plan = get_curriculum_plan($groupId, get_default_academic_year());
            $planId = $plan ? (int) $plan['id'] : null;
        }
        $stmt = db()->prepare(
            'INSERT INTO curriculum_modules (group_id, curriculum_plan_id, number, title)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$groupId, $planId, $number, $title]);
    } else {
        $stmt = db()->prepare(
            'INSERT INTO curriculum_modules (group_id, number, title) VALUES (?, ?, ?)'
        );
        $stmt->execute([$groupId, $number, $title]);
    }

    return ['success' => true, 'module_id' => (int) db()->lastInsertId()];
}

function update_curriculum_module(int $moduleId, string $title, ?int $number = null): array
{
    $module = get_curriculum_module_by_id($moduleId);
    if ($module === null) {
        return ['success' => false, 'error' => 'Модуль не найден.'];
    }

    $title = trim($title);
    $oldNumber = (int) $module['number'];
    $newNumber = $number !== null ? $number : $oldNumber;

    if ($newNumber < 1 || $newNumber > CURRICULUM_MODULE_MAX_NUMBER) {
        return ['success' => false, 'error' => 'Номер ПМ: от 01 до 15.'];
    }

    if ($newNumber !== $oldNumber) {
        $stmt = db()->prepare(
            'SELECT id FROM curriculum_modules
             WHERE group_id = ? AND number = ? AND id <> ?
             LIMIT 1'
        );
        $stmt->execute([(int) $module['group_id'], $newNumber, $moduleId]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'error' => 'ПМ ' . str_pad((string) $newNumber, 2, '0', STR_PAD_LEFT)
                    . ' уже занят другим модулем этой группы.',
            ];
        }
    }

    $stmt = db()->prepare('UPDATE curriculum_modules SET title = ?, number = ? WHERE id = ?');
    $stmt->execute([$title, $newNumber, $moduleId]);

    if ($newNumber !== $oldNumber) {
        sync_module_component_codes_after_renumber($moduleId, $oldNumber, $newNumber);
    }

    return ['success' => true];
}

function sync_module_component_codes_after_renumber(int $moduleId, int $oldNumber, int $newNumber): void
{
    $children = get_curriculum_module_children($moduleId, $newNumber);
    foreach ($children as $child) {
        $subjectId = (int) ($child['subject_id'] ?? 0);
        $oldName = (string) ($child['subject_name'] ?? '');
        if ($subjectId < 1 || $oldName === '') {
            continue;
        }

        $index = (int) ($child['component_index'] ?? 1);
        if (($child['item_type'] ?? '') === 'mdk') {
            $oldCode = format_mdk_code($oldNumber, $index);
            $newCode = format_mdk_code($newNumber, $index);
        } else {
            $kind = (string) ($child['practice_kind'] ?? 'up');
            $oldCode = format_practice_code($kind, $oldNumber, $index);
            $newCode = format_practice_code($kind, $newNumber, $index);
        }

        if (strpos($oldName, $oldCode) === 0) {
            $newName = $newCode . substr($oldName, strlen($oldCode));
        } else {
            $suffix = preg_replace('/^.*?\.\s*/u', '', $oldName, 1);
            $newName = $suffix !== '' && $suffix !== $oldName
                ? ($newCode . '. ' . $suffix)
                : $newCode;
        }

        if ($newName !== $oldName) {
            $exists = find_subject_by_name($newName);
            if ($exists && (int) $exists['id'] !== $subjectId) {
                continue;
            }
            $upd = db()->prepare('UPDATE subjects SET name = ? WHERE id = ?');
            $upd->execute([$newName, $subjectId]);
        }
    }
}

function count_curriculum_module_children(int $moduleId): int
{
    $typeCol = db()->query("SHOW COLUMNS FROM curriculum_items LIKE 'item_type'")->fetch();
    if (!$typeCol) {
        return 0;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM curriculum_items
         WHERE module_id = ? AND item_type IN (\'mdk\', \'practice\')'
    );
    $stmt->execute([$moduleId]);

    return (int) $stmt->fetchColumn();
}

function delete_curriculum_module(int $moduleId): array
{
    $module = get_curriculum_module_by_id($moduleId);
    if ($module === null) {
        return ['success' => false, 'error' => 'Модуль не найден.'];
    }

    $childrenCount = count_curriculum_module_children($moduleId);
    if ($childrenCount > 0) {
        return [
            'success' => false,
            'error' => 'Нельзя удалить модуль: сначала удалите все МДК и практики ('
                . $childrenCount . ').',
        ];
    }

    $stmt = db()->prepare('DELETE FROM curriculum_modules WHERE id = ?');
    $stmt->execute([$moduleId]);

    return ['success' => true];
}

function next_module_component_index(int $moduleId, string $itemType, ?string $practiceKind = null): int
{
    if ($itemType === 'practice' && $practiceKind === 'pdp') {
        return 1;
    }

    $sql = 'SELECT COALESCE(MAX(component_index), 0) + 1
            FROM curriculum_items
            WHERE module_id = ? AND item_type = ?';
    $params = [$moduleId, $itemType];
    if ($itemType === 'practice' && $practiceKind !== null) {
        $sql .= ' AND practice_kind = ?';
        $params[] = $practiceKind;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return max(1, (int) $stmt->fetchColumn());
}

function add_curriculum_module_component(
    int $planId,
    int $moduleId,
    string $itemType,
    string $title,
    int $startAbs,
    int $endAbs,
    ?int $teacherId = null,
    ?string $practiceKind = null,
    ?int $componentIndex = null
): array {
    if (!in_array($itemType, ['mdk', 'practice'], true)) {
        return ['success' => false, 'error' => 'Тип элемента: МДК или практика.'];
    }

    $module = get_curriculum_module_by_id($moduleId);
    if ($module === null) {
        return ['success' => false, 'error' => 'Модуль не найден.'];
    }

    $plan = get_curriculum_plan_by_id($planId);
    if ($plan === null || (int) $plan['group_id'] !== (int) $module['group_id']) {
        return ['success' => false, 'error' => 'Учебный план не соответствует группе модуля.'];
    }

    $programSemesters = get_group_program_semesters((int) $module['group_id']);
    $rangeError = validate_abs_semester_range($startAbs, $endAbs, $programSemesters);
    if ($rangeError !== null) {
        return ['success' => false, 'error' => $rangeError];
    }

    if ($itemType === 'practice') {
        $practiceKind = normalize_practice_kind((string) $practiceKind);
        if ($practiceKind === null) {
            return ['success' => false, 'error' => 'Укажите вид практики: УП, ПП или ПДП.'];
        }
    } else {
        $practiceKind = null;
    }

    $title = trim($title);
    if ($itemType === 'mdk' && $title === '') {
        return ['success' => false, 'error' => 'Укажите название МДК.'];
    }

    $index = $componentIndex ?? next_module_component_index($moduleId, $itemType, $practiceKind);
    if ($index < 1 || $index > 20) {
        return ['success' => false, 'error' => 'Некорректный порядковый номер компонента.'];
    }

    $dupSql = 'SELECT ci.id, sub.name AS subject_name
               FROM curriculum_items ci
               INNER JOIN subjects sub ON sub.id = ci.subject_id
               WHERE ci.module_id = ? AND ci.item_type = ? AND ci.component_index = ?';
    $dupParams = [$moduleId, $itemType, $index];
    if ($itemType === 'practice') {
        $dupSql .= ' AND ci.practice_kind = ?';
        $dupParams[] = $practiceKind;
    }
    $dupSql .= ' LIMIT 1';
    $dupStmt = db()->prepare($dupSql);
    $dupStmt->execute($dupParams);
    $dup = $dupStmt->fetch();
    if ($dup) {
        $label = $itemType === 'mdk' ? 'МДК' : 'Практика';
        return [
            'success' => false,
            'error' => $label . ' «' . $dup['subject_name'] . '» уже есть в этом модуле. '
                . 'Откройте вкладку «Профессиональные модули».',
        ];
    }

    $moduleNumber = (int) $module['number'];
    if ($itemType === 'mdk') {
        $fullName = format_mdk_code($moduleNumber, $index) . '. ' . $title;
    } else {
        $code = format_practice_code((string) $practiceKind, $moduleNumber, $index);
        $fullName = $title !== '' ? ($code . '. ' . $title) : $code;
    }

    $subjectResult = get_or_create_subject($fullName);
    if (!$subjectResult['success']) {
        return $subjectResult;
    }

    $teacherCheck = normalize_curriculum_teacher_id($teacherId);
    if (!$teacherCheck['success']) {
        return $teacherCheck;
    }
    $teacherId = $teacherCheck['teacher_id'];

    $stmt = db()->prepare(
        'SELECT id FROM curriculum_items
         WHERE curriculum_plan_id = ? AND subject_id = ?
         LIMIT 1'
    );
    $stmt->execute([$planId, $subjectResult['subject_id']]);
    if ($stmt->fetch()) {
        $label = $itemType === 'mdk' ? 'МДК' : 'Практика';
        return [
            'success' => false,
            'error' => $label . ' «' . $fullName . '» уже привязан к плану этого учебного года.',
        ];
    }

    $semester = semester_enum_from_abs_range($startAbs, $endAbs);
    $sortOrder = get_next_curriculum_sort_order($planId);

    $stmt = db()->prepare(
        'INSERT INTO curriculum_items
         (curriculum_plan_id, subject_id, item_type, module_id, practice_kind, component_index,
          start_abs_semester, end_abs_semester, teacher_id, semester, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $planId,
        $subjectResult['subject_id'],
        $itemType,
        $moduleId,
        $practiceKind,
        $index,
        $startAbs,
        $endAbs,
        $teacherId,
        $semester,
        $sortOrder,
    ]);

    return ['success' => true, 'item_id' => (int) db()->lastInsertId()];
}

function delete_curriculum_module_component(int $itemId): array
{
    $item = get_curriculum_item_by_id($itemId);
    if ($item === null) {
        return ['success' => false, 'error' => 'Элемент не найден.'];
    }
    $type = (string) ($item['item_type'] ?? 'subject');
    if (!in_array($type, ['mdk', 'practice'], true)) {
        return ['success' => false, 'error' => 'Удалять можно только МДК или практику модуля.'];
    }

    return delete_curriculum_item($itemId);
}

function mdk_title_from_subject_name(string $subjectName, int $moduleNumber, int $index): string
{
    $code = format_mdk_code($moduleNumber, $index);
    if (strpos($subjectName, $code . '. ') === 0) {
        return trim(substr($subjectName, strlen($code) + 2));
    }
    if (preg_match('/^МДК\s+\d+\.\d+\.\s*(.*)$/u', $subjectName, $m)) {
        return trim((string) $m[1]);
    }

    return trim($subjectName);
}

function update_curriculum_mdk(
    int $itemId,
    string $title,
    int $startAbs,
    int $endAbs,
    ?int $teacherId = null
): array {
    $item = get_curriculum_item_by_id($itemId);
    if ($item === null || (string) ($item['item_type'] ?? '') !== 'mdk') {
        return ['success' => false, 'error' => 'МДК не найден.'];
    }

    $moduleId = (int) ($item['module_id'] ?? 0);
    $module = get_curriculum_module_by_id($moduleId);
    if ($module === null) {
        return ['success' => false, 'error' => 'Модуль не найден.'];
    }

    $title = trim($title);
    if ($title === '') {
        return ['success' => false, 'error' => 'Укажите название МДК.'];
    }

    $programSemesters = get_group_program_semesters((int) $module['group_id']);
    $rangeError = validate_abs_semester_range($startAbs, $endAbs, $programSemesters);
    if ($rangeError !== null) {
        return ['success' => false, 'error' => $rangeError];
    }

    $teacherCheck = normalize_curriculum_teacher_id($teacherId);
    if (!$teacherCheck['success']) {
        return $teacherCheck;
    }
    $teacherId = $teacherCheck['teacher_id'];

    $index = (int) ($item['component_index'] ?? 1);
    $fullName = format_mdk_code((int) $module['number'], $index) . '. ' . $title;
    $subjectId = (int) $item['subject_id'];

    $existing = find_subject_by_name($fullName);
    if ($existing !== null && (int) $existing['id'] !== $subjectId) {
        $check = db()->prepare(
            'SELECT id FROM curriculum_items
             WHERE curriculum_plan_id = ? AND subject_id = ? AND id <> ?
             LIMIT 1'
        );
        $check->execute([(int) $item['curriculum_plan_id'], (int) $existing['id'], $itemId]);
        if ($check->fetch()) {
            return ['success' => false, 'error' => 'МДК с таким названием уже есть в учебном плане.'];
        }
        $subjectId = (int) $existing['id'];
    } else {
        $updSub = db()->prepare('UPDATE subjects SET name = ? WHERE id = ?');
        $updSub->execute([$fullName, $subjectId]);
    }

    $semester = semester_enum_from_abs_range($startAbs, $endAbs);
    $stmt = db()->prepare(
        'UPDATE curriculum_items
         SET subject_id = ?, teacher_id = ?, start_abs_semester = ?, end_abs_semester = ?, semester = ?
         WHERE id = ? AND item_type = \'mdk\''
    );
    $stmt->execute([$subjectId, $teacherId, $startAbs, $endAbs, $semester, $itemId]);

    return ['success' => true];
}

function practice_title_from_subject_name(string $subjectName, string $code): string
{
    $subjectName = trim($subjectName);
    $code = trim($code);
    if ($code !== '' && strpos($subjectName, $code . '. ') === 0) {
        return trim(substr($subjectName, strlen($code) + 2));
    }
    if ($code !== '' && $subjectName === $code) {
        return '';
    }
    if (preg_match('/^(?:УП|ПП)\s+\d+\.\d+\.\s*(.*)$/u', $subjectName, $m)) {
        return trim((string) $m[1]);
    }
    if (preg_match('/^ПДП\.\s*(.*)$/u', $subjectName, $m)) {
        return trim((string) $m[1]);
    }

    return $subjectName;
}

function update_curriculum_practice(
    int $itemId,
    string $title,
    int $startAbs,
    int $endAbs,
    ?int $teacherId = null
): array {
    $item = get_curriculum_item_by_id($itemId);
    if ($item === null || (string) ($item['item_type'] ?? '') !== 'practice') {
        return ['success' => false, 'error' => 'Практика не найдена.'];
    }

    $moduleId = (int) ($item['module_id'] ?? 0);
    $module = get_curriculum_module_by_id($moduleId);
    if ($module === null) {
        return ['success' => false, 'error' => 'Модуль не найден.'];
    }

    $practiceKind = normalize_practice_kind((string) ($item['practice_kind'] ?? ''));
    if ($practiceKind === null) {
        return ['success' => false, 'error' => 'Некорректный вид практики.'];
    }

    $programSemesters = get_group_program_semesters((int) $module['group_id']);
    $rangeError = validate_abs_semester_range($startAbs, $endAbs, $programSemesters);
    if ($rangeError !== null) {
        return ['success' => false, 'error' => $rangeError];
    }

    $teacherCheck = normalize_curriculum_teacher_id($teacherId);
    if (!$teacherCheck['success']) {
        return $teacherCheck;
    }
    $teacherId = $teacherCheck['teacher_id'];

    $title = trim($title);
    $index = (int) ($item['component_index'] ?? 1);
    $code = format_practice_code($practiceKind, (int) $module['number'], $index);
    $fullName = $title !== '' ? ($code . '. ' . $title) : $code;
    $subjectId = (int) $item['subject_id'];

    $existing = find_subject_by_name($fullName);
    if ($existing !== null && (int) $existing['id'] !== $subjectId) {
        $check = db()->prepare(
            'SELECT id FROM curriculum_items
             WHERE curriculum_plan_id = ? AND subject_id = ? AND id <> ?
             LIMIT 1'
        );
        $check->execute([(int) $item['curriculum_plan_id'], (int) $existing['id'], $itemId]);
        if ($check->fetch()) {
            return ['success' => false, 'error' => 'Практика с таким названием уже есть в учебном плане.'];
        }
        $subjectId = (int) $existing['id'];
    } else {
        $updSub = db()->prepare('UPDATE subjects SET name = ? WHERE id = ?');
        $updSub->execute([$fullName, $subjectId]);
    }

    $semester = semester_enum_from_abs_range($startAbs, $endAbs);
    $stmt = db()->prepare(
        'UPDATE curriculum_items
         SET subject_id = ?, teacher_id = ?, start_abs_semester = ?, end_abs_semester = ?, semester = ?
         WHERE id = ? AND item_type = \'practice\''
    );
    $stmt->execute([$subjectId, $teacherId, $startAbs, $endAbs, $semester, $itemId]);

    return ['success' => true];
}

function render_abs_semester_options(int $programSemesters, int $selected = 1): string
{
    $max = max(8, $programSemesters);
    $html = '';
    for ($abs = 1; $abs <= $max; $abs++) {
        $isSelected = $abs === $selected ? ' selected' : '';
        $html .= '<option value="' . $abs . '"' . $isSelected . '>'
            . e(curriculum_abs_semester_label($abs)) . '</option>';
    }

    return $html;
}

/**
 * МДК группы, активные в указанном курсе/семестре (для журнала).
 */
function get_group_mdk_for_period(int $groupId, int $course, ?string $semester = null): array
{
    $typeCol = db()->query("SHOW COLUMNS FROM curriculum_items LIKE 'item_type'")->fetch();
    if (!$typeCol) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT ci.id AS curriculum_item_id, ci.id, ci.curriculum_plan_id, ci.subject_id,
                ci.semester, ci.teacher_id,
                ci.item_type, ci.module_id, ci.start_abs_semester, ci.end_abs_semester,
                ci.component_index, sub.name AS subject_name,
                g.id AS group_id, g.number AS group_number,
                cp.academic_year, u.full_name AS teacher_name
         FROM curriculum_items ci
         INNER JOIN subjects sub ON sub.id = ci.subject_id
         INNER JOIN curriculum_plans cp ON cp.id = ci.curriculum_plan_id
         INNER JOIN study_groups g ON g.id = cp.group_id
         LEFT JOIN users u ON u.id = ci.teacher_id
         WHERE g.id = ? AND ci.item_type = \'mdk\''
    );
    $stmt->execute([$groupId]);
    $rows = $stmt->fetchAll();

    $filtered = [];
    foreach ($rows as $row) {
        if ($semester !== null && in_array($semester, ['1', '2'], true)) {
            if (!curriculum_item_covers_course_semester($row, $course, $semester)) {
                continue;
            }
        } else {
            $covers = curriculum_item_covers_course_semester($row, $course, '1')
                || curriculum_item_covers_course_semester($row, $course, '2');
            if (!$covers) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}
