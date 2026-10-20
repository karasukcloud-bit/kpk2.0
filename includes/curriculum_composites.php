<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/curriculum.php';

/** Родитель составной дисциплины (БВС) или МДК с разделами. */

function ensure_curriculum_composites_schema(?PDO $pdo = null): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $pdo = $pdo ?? db();

    if (!$pdo->query("SHOW TABLES LIKE 'curriculum_items'")->fetch()) {
        return;
    }

    $typeCol = $pdo->query("SHOW COLUMNS FROM curriculum_items LIKE 'item_type'")->fetch(PDO::FETCH_ASSOC);
    if ($typeCol) {
        $type = (string) ($typeCol['Type'] ?? '');
        if (strpos($type, 'composite') === false) {
            $pdo->exec(
                "ALTER TABLE curriculum_items
                 MODIFY item_type ENUM('subject', 'mdk', 'practice', 'composite')
                 NOT NULL DEFAULT 'subject'"
            );
        }
    }

    $parentCol = $pdo->query("SHOW COLUMNS FROM curriculum_items LIKE 'parent_item_id'")->fetch();
    if (!$parentCol) {
        $pdo->exec(
            'ALTER TABLE curriculum_items
             ADD parent_item_id INT UNSIGNED NULL DEFAULT NULL AFTER module_id'
        );
    }

    $indexes = $pdo->query('SHOW INDEX FROM curriculum_items')->fetchAll();
    $indexNames = array_column($indexes, 'Key_name');
    if (!in_array('idx_curriculum_items_parent', $indexNames, true)) {
        $pdo->exec('ALTER TABLE curriculum_items ADD KEY idx_curriculum_items_parent (parent_item_id)');
    }

    $fkExists = false;
    foreach ($pdo->query(
        "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'curriculum_items'
           AND CONSTRAINT_TYPE = 'FOREIGN KEY'
           AND CONSTRAINT_NAME = 'fk_curriculum_items_parent'"
    ) as $row) {
        $fkExists = true;
        break;
    }
    if (!$fkExists) {
        try {
            $pdo->exec(
                'ALTER TABLE curriculum_items
                 ADD CONSTRAINT fk_curriculum_items_parent
                 FOREIGN KEY (parent_item_id) REFERENCES curriculum_items(id)
                 ON DELETE CASCADE'
            );
        } catch (Throwable $e) {
            // ignore if engine/rights block FK
        }
    }
}

function curriculum_items_has_parent_column(): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    $has = (bool) db()->query("SHOW COLUMNS FROM curriculum_items LIKE 'parent_item_id'")->fetch();

    return $has;
}

/** Раздел участвует в периоде: свой семестр или «оба». */
function curriculum_part_matches_semester(string $partSemester, string $periodSemester): bool
{
    if (!in_array($periodSemester, ['1', '2'], true)) {
        return true;
    }

    return $partSemester === $periodSemester || $partSemester === 'both';
}

/**
 * Дочерние разделы родителя.
 * @param ?string $semester null — все; '1'|'2' — только разделы этого семестра (+ both)
 */
function get_curriculum_item_children(int $parentItemId, ?string $semester = null): array
{
    if ($parentItemId <= 0 || !curriculum_items_has_parent_column()) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT ci.id, ci.curriculum_plan_id, ci.subject_id, ci.teacher_id, ci.semester, ci.sort_order,
                ci.item_type, ci.module_id, ci.parent_item_id, ci.practice_kind, ci.component_index,
                ci.start_abs_semester, ci.end_abs_semester,
                sub.name AS subject_name,
                u.full_name AS teacher_name
         FROM curriculum_items ci
         INNER JOIN subjects sub ON sub.id = ci.subject_id
         LEFT JOIN users u ON u.id = ci.teacher_id
         WHERE ci.parent_item_id = ?
         ORDER BY ci.sort_order ASC, sub.name ASC'
    );
    $stmt->execute([$parentItemId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['curriculum_item_id'] = (int) $row['id'];
        $row['parent_item_id'] = (int) $row['parent_item_id'];
    }
    unset($row);

    if ($semester !== null && in_array($semester, ['1', '2'], true)) {
        $rows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => curriculum_part_matches_semester(
                (string) ($row['semester'] ?? 'both'),
                $semester
            )
        ));
    }

    return $rows;
}

function curriculum_item_has_children(int $itemId, ?string $semester = null): bool
{
    if ($itemId <= 0 || !curriculum_items_has_parent_column()) {
        return false;
    }

    if ($semester === null || !in_array($semester, ['1', '2'], true)) {
        $stmt = db()->prepare('SELECT 1 FROM curriculum_items WHERE parent_item_id = ? LIMIT 1');
        $stmt->execute([$itemId]);

        return (bool) $stmt->fetchColumn();
    }

    $stmt = db()->prepare(
        'SELECT 1 FROM curriculum_items
         WHERE parent_item_id = ? AND (semester = ? OR semester = \'both\')
         LIMIT 1'
    );
    $stmt->execute([$itemId, $semester]);

    return (bool) $stmt->fetchColumn();
}

function curriculum_item_is_part(array $item): bool
{
    return (int) ($item['parent_item_id'] ?? 0) > 0;
}

function curriculum_item_is_composite_parent(array $item): bool
{
    $type = (string) ($item['item_type'] ?? 'subject');
    if ($type === 'composite') {
        return true;
    }
    if (in_array($type, ['mdk', 'subject'], true) && curriculum_item_has_children((int) ($item['id'] ?? $item['curriculum_item_id'] ?? 0))) {
        return true;
    }

    return false;
}

/**
 * Агрегация оценок разделов/видов: любая 2 → 2, иначе среднее с округлением.
 *
 * @param list<int|null> $grades
 */
function aggregate_composite_part_grades(array $grades): ?int
{
    $values = [];
    foreach ($grades as $grade) {
        if ($grade === null || $grade === '') {
            continue;
        }
        $g = (int) $grade;
        if ($g < 2 || $g > 5) {
            continue;
        }
        $values[] = $g;
    }
    if ($values === []) {
        return null;
    }
    foreach ($values as $g) {
        if ($g === 2) {
            return 2;
        }
    }

    return (int) round(array_sum($values) / count($values), 0, PHP_ROUND_HALF_UP);
}

/**
 * Итог составной дисциплины: база по разделам + ПА родителя.
 * Любая «2» по разделу → итог 2 (ПА не поднимает). ПА «2» → итог 2.
 */
function resolve_composite_final_grade(?int $baseGrade, ?int $paGrade): ?int
{
    $pa = ($paGrade !== null && $paGrade >= 2 && $paGrade <= 5) ? $paGrade : null;
    $base = ($baseGrade !== null && $baseGrade >= 2 && $baseGrade <= 5) ? $baseGrade : null;

    if ($base === 2) {
        return 2;
    }
    if ($pa === 2) {
        return 2;
    }
    if ($base !== null && $pa !== null) {
        return (int) round(($base + $pa) / 2, 0, PHP_ROUND_HALF_UP);
    }
    if ($base !== null) {
        return $base;
    }

    return $pa;
}

/**
 * Один столбец ПА у родителя (создаётся при первом открытии журнала).
 */
function ensure_composite_parent_pa_lesson(int $parentItemId): ?array
{
    require_once __DIR__ . '/journal.php';

    foreach (get_journal_lessons($parentItemId) as $lesson) {
        if ((string) ($lesson['grade_type'] ?? '') === 'attestation') {
            return $lesson;
        }
    }

    $result = add_journal_lesson(
        $parentItemId,
        date('Y-m-d'),
        null,
        'attestation',
        'Промежуточная аттестация'
    );
    if (!$result['success'] || empty($result['lesson_id'])) {
        return null;
    }

    return get_journal_lesson_by_id((int) $result['lesson_id']);
}

/**
 * Строки журнала родителя: оценки разделов, база, ПА, итог.
 *
 * @return list<array<string,mixed>>
 */
function build_composite_parent_journal_rows(
    int $parentItemId,
    array $students,
    ?array $paLesson,
    ?string $semester = null
): array {
    $children = get_curriculum_item_children($parentItemId, $semester);
    $paLessonId = $paLesson !== null ? (int) $paLesson['id'] : 0;
    $parentGrades = $paLessonId > 0 ? get_journal_grades($parentItemId) : [];
    $rows = [];

    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $breakdown = get_composite_grade_breakdown($parentItemId, $studentId, $semester);
        $paMark = '';
        if ($paLessonId > 0) {
            $paMark = (string) (($parentGrades[$studentId][$paLessonId]['mark'] ?? '') ?: '');
        }
        $rows[] = [
            'student' => $student,
            'student_id' => $studentId,
            'parts' => $breakdown['parts'],
            'base_grade' => $breakdown['base_grade'],
            'pa_mark' => $paMark,
            'final_grade' => $breakdown['final_grade'],
            'pa_lesson_id' => $paLessonId,
        ];
    }

    return [
        'children' => $children,
        'rows' => $rows,
        'pa_lesson' => $paLesson,
    ];
}

/**
 * Итог родителя из журнала: база по детям + ПА родителя.
 */
function resolve_composite_parent_grade_from_journal(
    int $parentItemId,
    int $studentId,
    ?string $semester = null
): ?int {
    require_once __DIR__ . '/journal.php';
    require_once __DIR__ . '/grading.php';

    $children = get_curriculum_item_children($parentItemId, $semester);
    if ($children === []) {
        return null;
    }

    $config = get_grading_config();
    $partGrades = [];
    foreach ($children as $child) {
        $itemId = (int) $child['id'];
        $lessons = get_journal_lessons($itemId);
        $journalGrades = get_journal_grades($itemId);
        $studentMap = [
            $studentId => [
                'id' => $studentId,
                'full_name' => '',
            ],
        ];
        $totals = build_journal_totals($studentMap, $lessons, $journalGrades, $config);
        $total = $totals[$studentId] ?? null;
        if ($total === null) {
            continue;
        }
        $grade = resolve_journal_final_grade_for_gradebook($total);
        if ($grade !== null) {
            $partGrades[] = $grade;
        }
    }

    $base = aggregate_composite_part_grades($partGrades);
    $parentLessons = get_journal_lessons($parentItemId);
    $parentGrades = get_journal_grades($parentItemId);
    $studentGrades = $parentGrades[$studentId] ?? [];
    $attestationMarks = collect_attestation_marks($parentLessons, $studentGrades);
    $paGrade = null;
    if ($attestationMarks !== []) {
        foreach ($attestationMarks as $mark) {
            if ((int) $mark === 2) {
                $paGrade = 2;
                break;
            }
        }
        if ($paGrade === null) {
            $paGrade = (int) round(
                array_sum($attestationMarks) / count($attestationMarks),
                0,
                PHP_ROUND_HALF_UP
            );
        }
    }

    return resolve_composite_final_grade($base, $paGrade);
}

/**
 * Расшифровка составной оценки для ГЛАЗ / подсказок.
 *
 * @return array{parts: list<array<string,mixed>>, cause_parts: list<array<string,mixed>>, final_grade: ?int}
 */
function get_composite_grade_breakdown(
    int $parentItemId,
    int $studentId,
    ?string $semester = null
): array {
    require_once __DIR__ . '/journal.php';
    require_once __DIR__ . '/grading.php';

    $children = get_curriculum_item_children($parentItemId, $semester);
    $parts = [];
    $partGrades = [];
    $config = get_grading_config();

    foreach ($children as $child) {
        $itemId = (int) $child['id'];
        $lessons = get_journal_lessons($itemId);
        $journalGrades = get_journal_grades($itemId);
        $studentMap = [$studentId => ['id' => $studentId, 'full_name' => '']];
        $totals = build_journal_totals($studentMap, $lessons, $journalGrades, $config);
        $grade = null;
        if (isset($totals[$studentId])) {
            $grade = resolve_journal_final_grade_for_gradebook($totals[$studentId]);
        }
        if ($grade !== null) {
            $partGrades[] = $grade;
        }
        $parts[] = [
            'curriculum_item_id' => $itemId,
            'subject_name' => (string) ($child['subject_name'] ?? ''),
            'teacher_id' => isset($child['teacher_id']) ? (int) $child['teacher_id'] : null,
            'teacher_name' => (string) ($child['teacher_name'] ?? ''),
            'grade' => $grade,
            'semester' => (string) ($child['semester'] ?? 'both'),
        ];
    }

    $base = aggregate_composite_part_grades($partGrades);
    $final = resolve_composite_parent_grade_from_journal($parentItemId, $studentId, $semester);
    $cause = array_values(array_filter(
        $parts,
        static fn (array $p): bool => (int) ($p['grade'] ?? 0) === 2
    ));

    return [
        'parts' => $parts,
        'cause_parts' => $cause,
        'base_grade' => $base,
        'final_grade' => $final,
    ];
}

function add_curriculum_composite(
    int $planId,
    string $subjectName,
    string $semester,
    ?int $teacherId = null
): array {
    ensure_curriculum_composites_schema();
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

    $sortOrder = next_curriculum_sort_order($planId);
    $stmt = db()->prepare(
        'INSERT INTO curriculum_items
         (curriculum_plan_id, subject_id, item_type, teacher_id, semester, sort_order, parent_item_id)
         VALUES (?, ?, \'composite\', ?, ?, ?, NULL)'
    );
    $stmt->execute([$planId, (int) $subjectResult['subject_id'], $teacherId, $semester, $sortOrder]);

    return [
        'success' => true,
        'curriculum_item_id' => (int) db()->lastInsertId(),
    ];
}

function add_curriculum_item_part(
    int $parentItemId,
    string $subjectName,
    ?int $teacherId = null,
    ?string $semester = null
): array {
    ensure_curriculum_composites_schema();
    $parent = get_curriculum_item_by_id($parentItemId);
    if ($parent === null) {
        return ['success' => false, 'error' => 'Родительская дисциплина не найдена.'];
    }

    $parentType = (string) ($parent['item_type'] ?? 'subject');
    if (!in_array($parentType, ['composite', 'mdk', 'subject'], true)) {
        return ['success' => false, 'error' => 'К этому элементу нельзя добавить разделы.'];
    }
    if ((int) ($parent['parent_item_id'] ?? 0) > 0) {
        return ['success' => false, 'error' => 'Нельзя добавить раздел к разделу.'];
    }

    $planId = (int) $parent['curriculum_plan_id'];
    $semester = $semester !== null && $semester !== ''
        ? $semester
        : (string) ($parent['semester'] ?? 'both');
    if (!validate_semester($semester)) {
        return ['success' => false, 'error' => 'Некорректный семестр раздела.'];
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

    $maxSort = db()->prepare(
        'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM curriculum_items WHERE parent_item_id = ?'
    );
    $maxSort->execute([$parentItemId]);
    $sortOrder = (int) $maxSort->fetchColumn();

    $stmt = db()->prepare(
        'INSERT INTO curriculum_items
         (curriculum_plan_id, subject_id, item_type, module_id, parent_item_id, teacher_id, semester, sort_order,
          start_abs_semester, end_abs_semester)
         VALUES (?, ?, \'subject\', ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $planId,
        (int) $subjectResult['subject_id'],
        $parent['module_id'] !== null ? (int) $parent['module_id'] : null,
        $parentItemId,
        $teacherId,
        $semester,
        $sortOrder,
        $parent['start_abs_semester'] !== null ? (int) $parent['start_abs_semester'] : null,
        $parent['end_abs_semester'] !== null ? (int) $parent['end_abs_semester'] : null,
    ]);

    return [
        'success' => true,
        'curriculum_item_id' => (int) db()->lastInsertId(),
    ];
}

function update_curriculum_item_part(
    int $partItemId,
    string $subjectName,
    ?int $teacherId = null,
    ?string $semester = null
): array {
    ensure_curriculum_composites_schema();
    $item = get_curriculum_item_by_id($partItemId);
    if ($item === null || (int) ($item['parent_item_id'] ?? 0) <= 0) {
        return ['success' => false, 'error' => 'Раздел не найден.'];
    }

    $subjectResult = get_or_create_subject($subjectName);
    if (!$subjectResult['success']) {
        return $subjectResult;
    }
    $teacherCheck = normalize_curriculum_teacher_id($teacherId);
    if (!$teacherCheck['success']) {
        return $teacherCheck;
    }

    $semester = $semester !== null && $semester !== ''
        ? $semester
        : (string) ($item['semester'] ?? 'both');
    if (!validate_semester($semester)) {
        return ['success' => false, 'error' => 'Некорректный семестр раздела.'];
    }

    $stmt = db()->prepare(
        'UPDATE curriculum_items
         SET subject_id = ?, teacher_id = ?, semester = ?
         WHERE id = ? AND parent_item_id IS NOT NULL'
    );
    $stmt->execute([
        (int) $subjectResult['subject_id'],
        $teacherCheck['teacher_id'],
        $semester,
        $partItemId,
    ]);

    return ['success' => true];
}

/**
 * Удаление раздела из плана.
 * Если раздел «оба семестра», а удаляют из вкладки 1 или 2 — оставляем в другом семестре.
 *
 * @return array{success:bool,error?:string,kept_semester?:string}
 */
function delete_curriculum_item_part(int $partItemId, ?string $fromSemester = null): array
{
    $item = get_curriculum_item_by_id($partItemId);
    if ($item === null || (int) ($item['parent_item_id'] ?? 0) <= 0) {
        return ['success' => false, 'error' => 'Раздел не найден.'];
    }

    $partSemester = (string) ($item['semester'] ?? 'both');
    $fromSemester = $fromSemester !== null && $fromSemester !== ''
        ? $fromSemester
        : null;

    if (
        $partSemester === 'both'
        && $fromSemester !== null
        && in_array($fromSemester, ['1', '2'], true)
    ) {
        $keep = $fromSemester === '1' ? '2' : '1';
        $stmt = db()->prepare(
            'UPDATE curriculum_items SET semester = ? WHERE id = ? AND parent_item_id IS NOT NULL'
        );
        $stmt->execute([$keep, $partItemId]);

        return [
            'success' => true,
            'kept_semester' => $keep,
        ];
    }

    return delete_curriculum_item($partItemId);
}

/**
 * Предметы ведомости: без дочерних разделов; родители (composite/mdk с детьми) остаются.
 *
 * @param list<array<string,mixed>> $items
 * @return list<array<string,mixed>>
 */
function filter_curriculum_items_for_gradebook(array $items): array
{
    if (!curriculum_items_has_parent_column()) {
        return $items;
    }

    $result = [];
    foreach ($items as $item) {
        if ((int) ($item['parent_item_id'] ?? 0) > 0) {
            continue;
        }
        $item['is_composite_parent'] = curriculum_item_is_composite_parent($item);
        $result[] = $item;
    }

    return $result;
}

/**
 * Предметы для журнала преподавателя: разделы + обычные; родители с детьми — только для ПА.
 *
 * @param list<array<string,mixed>> $items
 * @return list<array<string,mixed>>
 */
function annotate_curriculum_items_for_teacher(array $items, ?string $semester = null): array
{
    foreach ($items as &$item) {
        $id = (int) ($item['curriculum_item_id'] ?? $item['id'] ?? 0);
        $item['parent_item_id'] = (int) ($item['parent_item_id'] ?? 0);
        $item['has_children'] = curriculum_item_has_children($id, $semester);
        $item['journal_mode'] = $item['has_children'] ? 'attestation_only' : 'full';
    }
    unset($item);

    return $items;
}

/**
 * Дерево предметов для выбора в журнале: разделы вложены в родителя.
 *
 * @param list<array<string,mixed>> $subjects
 * @return list<array<string,mixed>>
 */
function group_journal_subjects_tree(array $subjects): array
{
    $childrenByParent = [];
    $roots = [];
    $rootIds = [];

    foreach ($subjects as $subject) {
        $parentId = (int) ($subject['parent_item_id'] ?? 0);
        if ($parentId > 0) {
            $childrenByParent[$parentId][] = $subject;
            continue;
        }
        $id = (int) ($subject['curriculum_item_id'] ?? $subject['id'] ?? 0);
        $rootIds[$id] = true;
        $roots[] = $subject;
    }

    foreach ($roots as &$root) {
        $id = (int) ($root['curriculum_item_id'] ?? $root['id'] ?? 0);
        $root['children'] = $childrenByParent[$id] ?? [];
        unset($childrenByParent[$id]);
    }
    unset($root);

    foreach ($childrenByParent as $parentId => $children) {
        $roots[] = [
            'curriculum_item_id' => (int) $parentId,
            'subject_name' => (string) ($children[0]['parent_subject_name'] ?? 'Дисциплина'),
            'semester' => (string) ($children[0]['semester'] ?? 'both'),
            'teacher_name' => '',
            'journal_mode' => 'attestation_only',
            'parent_item_id' => 0,
            'virtual_parent' => true,
            'children' => $children,
        ];
    }

    usort($roots, static function (array $a, array $b): int {
        return strcmp((string) ($a['subject_name'] ?? ''), (string) ($b['subject_name'] ?? ''));
    });

    return $roots;
}
