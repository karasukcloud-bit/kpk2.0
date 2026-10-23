<?php

declare(strict_types=1);

function manual_brs_get_or_create_sheet(
    string $academicYear,
    int $period,
    int $groupId,
    int $curriculumItemId
): array {
    $period = manual_brs_normalize_period($period);
    $stmt = db()->prepare(
        'SELECT * FROM manual_brs_sheets
         WHERE academic_year = ? AND period = ? AND curriculum_item_id = ?
         LIMIT 1'
    );
    $stmt->execute([$academicYear, $period, $curriculumItemId]);
    $row = $stmt->fetch();
    if ($row) {
        return $row;
    }

    $ins = db()->prepare(
        'INSERT INTO manual_brs_sheets
         (academic_year, period, group_id, curriculum_item_id, lessons_total, updated_by)
         VALUES (?, ?, ?, ?, 0, ?)'
    );
    $ins->execute([
        $academicYear,
        $period,
        $groupId,
        $curriculumItemId,
        (int) (current_user()['id'] ?? 0) ?: null,
    ]);

    $stmt->execute([$academicYear, $period, $curriculumItemId]);
    $row = $stmt->fetch();

    return $row ?: [
        'id' => (int) db()->lastInsertId(),
        'academic_year' => $academicYear,
        'period' => $period,
        'group_id' => $groupId,
        'curriculum_item_id' => $curriculumItemId,
        'lessons_total' => 0,
    ];
}

function manual_brs_update_lessons_total(int $sheetId, int $lessonsTotal): array
{
    $lessonsTotal = max(0, min(200, $lessonsTotal));
    $stmt = db()->prepare(
        'UPDATE manual_brs_sheets
         SET lessons_total = ?, updated_by = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $lessonsTotal,
        (int) (current_user()['id'] ?? 0) ?: null,
        $sheetId,
    ]);

    $entries = manual_brs_get_entries_map($sheetId);
    foreach ($entries as $entry) {
        $stages = manual_brs_entry_stages($entry);
        manual_brs_save_entry(
            $sheetId,
            (int) $entry['student_id'],
            [
                'stage1_current_marks' => $stages[1]['current_input'],
                'stage1_control_marks' => $stages[1]['control_input'],
                'stage1_absent_count' => $stages[1]['absent_count'],
                'stage1_late_count' => $stages[1]['late_count'],
                'stage1_activity_count' => $stages[1]['activity_count'],
                'stage2_current_marks' => $stages[2]['current_input'],
                'stage2_control_marks' => $stages[2]['control_input'],
                'stage2_absent_count' => $stages[2]['absent_count'],
                'stage2_late_count' => $stages[2]['late_count'],
                'stage2_activity_count' => $stages[2]['activity_count'],
            ],
            $lessonsTotal
        );
    }

    return ['success' => true, 'lessons_total' => $lessonsTotal];
}

/**
 * Данные двух этапов из записи (с миграцией старых полей в этап 1).
 *
 * @return array{1: array, 2: array}
 */
function manual_brs_entry_stages(?array $entry): array
{
    $empty = static function (): array {
        return [
            'current_input' => '',
            'control_input' => '',
            'absent_count' => 0,
            'late_count' => 0,
            'activity_count' => 0,
        ];
    };

    if ($entry === null) {
        return [1 => $empty(), 2 => $empty()];
    }

    $hasStageCols = array_key_exists('stage1_current_marks', $entry)
        || array_key_exists('stage2_current_marks', $entry);
    $stage1Empty = trim((string) ($entry['stage1_current_marks'] ?? '')) === ''
        && trim((string) ($entry['stage1_control_marks'] ?? '')) === ''
        && (int) ($entry['stage1_absent_count'] ?? 0) === 0
        && (int) ($entry['stage1_late_count'] ?? 0) === 0
        && (int) ($entry['stage1_activity_count'] ?? 0) === 0;
    $stage2Empty = trim((string) ($entry['stage2_current_marks'] ?? '')) === ''
        && trim((string) ($entry['stage2_control_marks'] ?? '')) === ''
        && (int) ($entry['stage2_absent_count'] ?? 0) === 0
        && (int) ($entry['stage2_late_count'] ?? 0) === 0
        && (int) ($entry['stage2_activity_count'] ?? 0) === 0;

    if (!$hasStageCols || ($stage1Empty && $stage2Empty)) {
        return [
            1 => [
                'current_input' => manual_brs_marks_to_input(
                    manual_brs_parse_marks((string) ($entry['current_marks'] ?? ''))
                ),
                'control_input' => manual_brs_marks_to_input(
                    manual_brs_parse_marks((string) ($entry['control_marks'] ?? ''))
                ),
                'absent_count' => (int) ($entry['absent_count'] ?? 0),
                'late_count' => (int) ($entry['late_count'] ?? 0),
                'activity_count' => (int) ($entry['activity_count'] ?? 0),
            ],
            2 => $empty(),
        ];
    }

    return [
        1 => [
            'current_input' => manual_brs_marks_to_input(
                manual_brs_parse_marks((string) ($entry['stage1_current_marks'] ?? ''))
            ),
            'control_input' => manual_brs_marks_to_input(
                manual_brs_parse_marks((string) ($entry['stage1_control_marks'] ?? ''))
            ),
            'absent_count' => (int) ($entry['stage1_absent_count'] ?? 0),
            'late_count' => (int) ($entry['stage1_late_count'] ?? 0),
            'activity_count' => (int) ($entry['stage1_activity_count'] ?? 0),
        ],
        2 => [
            'current_input' => manual_brs_marks_to_input(
                manual_brs_parse_marks((string) ($entry['stage2_current_marks'] ?? ''))
            ),
            'control_input' => manual_brs_marks_to_input(
                manual_brs_parse_marks((string) ($entry['stage2_control_marks'] ?? ''))
            ),
            'absent_count' => (int) ($entry['stage2_absent_count'] ?? 0),
            'late_count' => (int) ($entry['stage2_late_count'] ?? 0),
            'activity_count' => (int) ($entry['stage2_activity_count'] ?? 0),
        ],
    ];
}

/**
 * @return array{current_marks: string, control_marks: string, absent_count: int, late_count: int, activity_count: int, stages: array}
 */
function manual_brs_merge_stages_input(array $input, int $lessonsTotal = 0): array
{
    $stage1Current = manual_brs_parse_marks((string) ($input['stage1_current_marks'] ?? $input['current_marks'] ?? ''));
    $stage1Control = manual_brs_parse_marks((string) ($input['stage1_control_marks'] ?? $input['control_marks'] ?? ''));
    $stage2Current = manual_brs_parse_marks((string) ($input['stage2_current_marks'] ?? ''));
    $stage2Control = manual_brs_parse_marks((string) ($input['stage2_control_marks'] ?? ''));

    $hasStagePost = array_key_exists('stage1_current_marks', $input)
        || array_key_exists('stage2_current_marks', $input);

    $cap = static function (int $value) use ($lessonsTotal): int {
        $value = max(0, $value);
        if ($lessonsTotal > 0) {
            return min($value, $lessonsTotal);
        }

        return 0;
    };

    if ($hasStagePost) {
        $stage1Absent = $cap((int) ($input['stage1_absent_count'] ?? 0));
        $stage1Late = $cap((int) ($input['stage1_late_count'] ?? 0));
        $stage1Activity = $cap((int) ($input['stage1_activity_count'] ?? 0));
        $stage2Absent = $cap((int) ($input['stage2_absent_count'] ?? 0));
        $stage2Late = $cap((int) ($input['stage2_late_count'] ?? 0));
        $stage2Activity = $cap((int) ($input['stage2_activity_count'] ?? 0));
    } else {
        $stage1Absent = $cap((int) ($input['absent_count'] ?? 0));
        $stage1Late = $cap((int) ($input['late_count'] ?? 0));
        $stage1Activity = $cap((int) ($input['activity_count'] ?? 0));
        $stage2Absent = 0;
        $stage2Late = 0;
        $stage2Activity = 0;
    }

    $mergedCurrent = array_merge($stage1Current, $stage2Current);
    $mergedControl = array_merge($stage1Control, $stage2Control);

    return [
        'current_marks' => manual_brs_marks_to_input($mergedCurrent),
        'control_marks' => manual_brs_marks_to_input($mergedControl),
        'absent_count' => $stage1Absent + $stage2Absent,
        'late_count' => $stage1Late + $stage2Late,
        'activity_count' => $stage1Activity + $stage2Activity,
        'stages' => [
            1 => [
                'current_marks' => $stage1Current,
                'control_marks' => $stage1Control,
                'current_input' => manual_brs_marks_to_input($stage1Current),
                'control_input' => manual_brs_marks_to_input($stage1Control),
                'absent_count' => $stage1Absent,
                'late_count' => $stage1Late,
                'activity_count' => $stage1Activity,
            ],
            2 => [
                'current_marks' => $stage2Current,
                'control_marks' => $stage2Control,
                'current_input' => manual_brs_marks_to_input($stage2Current),
                'control_input' => manual_brs_marks_to_input($stage2Control),
                'absent_count' => $stage2Absent,
                'late_count' => $stage2Late,
                'activity_count' => $stage2Activity,
            ],
        ],
    ];
}

function manual_brs_get_entries_map(int $sheetId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM manual_brs_entries WHERE sheet_id = ?'
    );
    $stmt->execute([$sheetId]);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int) $row['student_id']] = $row;
    }

    return $map;
}

function manual_brs_get_entry(int $sheetId, int $studentId): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM manual_brs_entries WHERE sheet_id = ? AND student_id = ? LIMIT 1'
    );
    $stmt->execute([$sheetId, $studentId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function manual_brs_save_entry(
    int $sheetId,
    int $studentId,
    array $input,
    int $lessonsTotal
): array {
    $student = get_student_by_id($studentId);
    if ($student === null) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    $sheetStmt = db()->prepare('SELECT * FROM manual_brs_sheets WHERE id = ? LIMIT 1');
    $sheetStmt->execute([$sheetId]);
    $sheet = $sheetStmt->fetch();
    if (!$sheet) {
        return ['success' => false, 'error' => 'Ведомость периода не найдена.'];
    }
    if ((int) $student['group_id'] !== (int) $sheet['group_id']) {
        return ['success' => false, 'error' => 'Студент не из этой группы.'];
    }

    require_once __DIR__ . '/../../../includes/curriculum_composites.php';
    require_once __DIR__ . '/periods.php';
    $sheetSemester = manual_brs_semester_from_period((int) ($sheet['period'] ?? 1));
    if (curriculum_item_has_children((int) $sheet['curriculum_item_id'], $sheetSemester)) {
        return [
            'success' => false,
            'error' => 'У составной дисциплины баллы БРС вносятся в разделы; здесь только ПА.',
        ];
    }

    $merged = manual_brs_merge_stages_input($input, $lessonsTotal);
    $stages = $merged['stages'];

    $calc = manual_brs_calculate([
        'current_marks' => $merged['current_marks'],
        'control_marks' => $merged['control_marks'],
        'absent_count' => $merged['absent_count'],
        'late_count' => $merged['late_count'],
        'activity_count' => $merged['activity_count'],
        'lessons_total' => $lessonsTotal,
    ]);

    $currentStorage = manual_brs_marks_to_storage($calc['current_marks']);
    $controlStorage = manual_brs_marks_to_storage($calc['control_marks']);
    $s1Current = manual_brs_marks_to_storage($stages[1]['current_marks']);
    $s1Control = manual_brs_marks_to_storage($stages[1]['control_marks']);
    $s2Current = manual_brs_marks_to_storage($stages[2]['current_marks']);
    $s2Control = manual_brs_marks_to_storage($stages[2]['control_marks']);

    $existing = manual_brs_get_entry($sheetId, $studentId);
    if ($existing) {
        $stmt = db()->prepare(
            'UPDATE manual_brs_entries
             SET current_marks = ?, control_marks = ?,
                 stage1_current_marks = ?, stage1_control_marks = ?,
                 stage1_absent_count = ?, stage1_late_count = ?, stage1_activity_count = ?,
                 stage2_current_marks = ?, stage2_control_marks = ?,
                 stage2_absent_count = ?, stage2_late_count = ?, stage2_activity_count = ?,
                 absent_count = ?, late_count = ?, activity_count = ?,
                 current_avg = ?, control_avg = ?, points = ?, grade = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $currentStorage,
            $controlStorage,
            $s1Current,
            $s1Control,
            $stages[1]['absent_count'],
            $stages[1]['late_count'],
            $stages[1]['activity_count'],
            $s2Current,
            $s2Control,
            $stages[2]['absent_count'],
            $stages[2]['late_count'],
            $stages[2]['activity_count'],
            $calc['absent_count'],
            $calc['late_count'],
            $calc['activity_count'],
            $calc['current_avg'],
            $calc['control_avg'],
            $calc['points'],
            $calc['grade'],
            (int) $existing['id'],
        ]);
    } else {
        $stmt = db()->prepare(
            'INSERT INTO manual_brs_entries
             (sheet_id, student_id, current_marks, control_marks,
              stage1_current_marks, stage1_control_marks,
              stage1_absent_count, stage1_late_count, stage1_activity_count,
              stage2_current_marks, stage2_control_marks,
              stage2_absent_count, stage2_late_count, stage2_activity_count,
              absent_count, late_count, activity_count,
              current_avg, control_avg, points, grade)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $sheetId,
            $studentId,
            $currentStorage,
            $controlStorage,
            $s1Current,
            $s1Control,
            $stages[1]['absent_count'],
            $stages[1]['late_count'],
            $stages[1]['activity_count'],
            $s2Current,
            $s2Control,
            $stages[2]['absent_count'],
            $stages[2]['late_count'],
            $stages[2]['activity_count'],
            $calc['absent_count'],
            $calc['late_count'],
            $calc['activity_count'],
            $calc['current_avg'],
            $calc['control_avg'],
            $calc['points'],
            $calc['grade'],
        ]);
    }

    return [
        'success' => true,
        'calc' => $calc,
        'stages' => $stages,
        'entry' => manual_brs_get_entry($sheetId, $studentId),
    ];
}

/** Пересчёт баллов записи по актуальной формуле БРС из настроек. */
function manual_brs_calc_from_entry(array $entry, int $lessonsTotal, ?array $brs = null): array
{
    $stages = manual_brs_entry_stages($entry);
    $merged = manual_brs_merge_stages_input([
        'stage1_current_marks' => $stages[1]['current_input'],
        'stage1_control_marks' => $stages[1]['control_input'],
        'stage1_absent_count' => $stages[1]['absent_count'],
        'stage1_late_count' => $stages[1]['late_count'],
        'stage1_activity_count' => $stages[1]['activity_count'],
        'stage2_current_marks' => $stages[2]['current_input'],
        'stage2_control_marks' => $stages[2]['control_input'],
        'stage2_absent_count' => $stages[2]['absent_count'],
        'stage2_late_count' => $stages[2]['late_count'],
        'stage2_activity_count' => $stages[2]['activity_count'],
    ], $lessonsTotal);

    return manual_brs_calculate([
        'current_marks' => $merged['current_marks'],
        'control_marks' => $merged['control_marks'],
        'absent_count' => $merged['absent_count'],
        'late_count' => $merged['late_count'],
        'activity_count' => $merged['activity_count'],
        'lessons_total' => max(0, $lessonsTotal),
    ], $brs);
}

/**
 * Пакетный пересчёт баллов периода: item_id => student_id => {points, grade, display}.
 *
 * @param list<int> $itemIds
 * @return array<int, array<int, array{points: ?float, grade: ?int, display: string}>>
 */
function manual_brs_load_period_grades_map(string $academicYear, int $period, array $itemIds): array
{
    $period = manual_brs_normalize_period($period);
    $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
    if ($itemIds === []) {
        return [];
    }

    $brs = manual_brs_weights();
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $params = array_merge([$academicYear, $period], $itemIds);
    $stmt = db()->prepare(
        "SELECT e.*, s.lessons_total, s.curriculum_item_id
         FROM manual_brs_sheets s
         INNER JOIN manual_brs_entries e ON e.sheet_id = s.id
         WHERE s.academic_year = ? AND s.period = ?
           AND s.curriculum_item_id IN ($placeholders)"
    );
    $stmt->execute($params);

    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $itemId = (int) $row['curriculum_item_id'];
        $studentId = (int) $row['student_id'];
        $calc = manual_brs_calc_from_entry($row, (int) ($row['lessons_total'] ?? 0), $brs);
        $points = $calc['points'] !== null ? (float) $calc['points'] : null;
        $grade = $calc['grade'] !== null ? (int) $calc['grade'] : null;
        $map[$itemId][$studentId] = [
            'points' => $points,
            'grade' => $grade,
            'display' => manual_brs_period_display($points, $grade),
        ];
    }

    return $map;
}

/**
 * Пакет ПА: item_id => student_id => grade.
 *
 * @param list<int> $itemIds
 * @return array<int, array<int, int>>
 */
function manual_brs_get_attestations_map_multi(
    string $academicYear,
    string $semester,
    array $itemIds
): array {
    $semester = $semester === '2' ? '2' : '1';
    $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
    if ($itemIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $params = array_merge([$academicYear, $semester], $itemIds);
    $stmt = db()->prepare(
        "SELECT curriculum_item_id, student_id, grade
         FROM manual_brs_attestations
         WHERE academic_year = ? AND semester = ?
           AND curriculum_item_id IN ($placeholders)"
    );
    $stmt->execute($params);

    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $grade = $row['grade'] !== null ? (int) $row['grade'] : null;
        if ($grade === null || $grade < 2 || $grade > 5) {
            continue;
        }
        $map[(int) $row['curriculum_item_id']][(int) $row['student_id']] = $grade;
    }

    return $map;
}

/**
 * Контекст пакетного расчёта итогов группы за семестр.
 *
 * @param list<array{curriculum_item_id?: int}> $subjects
 * @return array{
 *   academic_year: string,
 *   semester: string,
 *   period_id: int,
 *   calc: array<int, array<int, array{points: ?float, grade: ?int, display: string}>>,
 *   pa: array<int, array<int, int>>,
 *   children: array<int, list<array>>
 * }
 */
function manual_brs_build_grade_batch_context(
    string $academicYear,
    string $semester,
    array $subjects
): array {
    require_once __DIR__ . '/../../../includes/curriculum_composites.php';
    $semester = $semester === '2' ? '2' : '1';
    $periodId = manual_brs_period_for_semester($semester);
    $itemIds = [];
    $childrenByParent = [];

    foreach ($subjects as $subject) {
        $itemId = (int) ($subject['curriculum_item_id'] ?? $subject['id'] ?? 0);
        if ($itemId <= 0) {
            continue;
        }
        $itemIds[] = $itemId;
        $children = get_curriculum_item_children($itemId, $semester);
        $childrenByParent[$itemId] = $children;
        foreach ($children as $child) {
            $itemIds[] = (int) $child['id'];
        }
    }

    $itemIds = array_values(array_unique($itemIds));

    return [
        'academic_year' => $academicYear,
        'semester' => $semester,
        'period_id' => $periodId,
        'calc' => manual_brs_load_period_grades_map($academicYear, $periodId, $itemIds),
        'pa' => manual_brs_get_attestations_map_multi($academicYear, $semester, $itemIds),
        'children' => $childrenByParent,
    ];
}

/** Итог семестра из пакетного контекста (без SQL). */
function manual_brs_resolve_from_batch(int $curriculumItemId, int $studentId, array $ctx): array
{
    require_once __DIR__ . '/../../../includes/curriculum_composites.php';
    $pa = $ctx['pa'][$curriculumItemId][$studentId] ?? null;
    $children = $ctx['children'][$curriculumItemId] ?? [];

    if ($children !== []) {
        $partGrades = [];
        foreach ($children as $child) {
            $childId = (int) $child['id'];
            $points = $ctx['calc'][$childId][$studentId]['points'] ?? null;
            $childSem = manual_brs_semester_total(
                $points !== null ? (float) $points : null,
                null,
                null
            );
            if ($childSem['grade'] !== null) {
                $partGrades[] = (int) $childSem['grade'];
            }
        }
        $base = aggregate_composite_part_grades($partGrades);
        $grade = resolve_composite_final_grade($base, $pa);

        return [
            'points' => null,
            'grade' => $grade,
            'brs_grade' => $base,
            'pa_grade' => $pa,
            'display' => $grade !== null ? (string) $grade : '',
            'html' => manual_brs_render_semester_html($grade, null),
        ];
    }

    $points = $ctx['calc'][$curriculumItemId][$studentId]['points'] ?? null;

    return manual_brs_semester_total(
        $points !== null ? (float) $points : null,
        null,
        $pa
    );
}

function manual_brs_points_for_periods(
    string $academicYear,
    int $curriculumItemId,
    int $studentId,
    array $periods
): array {
    $result = [];
    foreach ($periods as $period) {
        $period = (int) $period;
        $map = manual_brs_load_period_grades_map($academicYear, $period, [$curriculumItemId]);
        $result[$period] = $map[$curriculumItemId][$studentId] ?? [
            'points' => null,
            'grade' => null,
            'display' => '',
        ];
    }

    return $result;
}

function manual_brs_get_attestation(
    string $academicYear,
    string $semester,
    int $curriculumItemId,
    int $studentId
): ?int {
    $semester = $semester === '2' ? '2' : '1';
    $stmt = db()->prepare(
        'SELECT grade FROM manual_brs_attestations
         WHERE academic_year = ? AND semester = ?
           AND curriculum_item_id = ? AND student_id = ?
         LIMIT 1'
    );
    $stmt->execute([$academicYear, $semester, $curriculumItemId, $studentId]);
    $grade = $stmt->fetchColumn();
    if ($grade === false || $grade === null || $grade === '') {
        return null;
    }
    $grade = (int) $grade;

    return ($grade >= 2 && $grade <= 5) ? $grade : null;
}

function manual_brs_get_attestations_map(
    string $academicYear,
    string $semester,
    int $curriculumItemId
): array {
    $semester = $semester === '2' ? '2' : '1';
    $stmt = db()->prepare(
        'SELECT student_id, grade FROM manual_brs_attestations
         WHERE academic_year = ? AND semester = ? AND curriculum_item_id = ?'
    );
    $stmt->execute([$academicYear, $semester, $curriculumItemId]);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $grade = $row['grade'] !== null ? (int) $row['grade'] : null;
        if ($grade !== null && $grade >= 2 && $grade <= 5) {
            $map[(int) $row['student_id']] = $grade;
        }
    }

    return $map;
}

function manual_brs_save_attestation(
    string $academicYear,
    string $semester,
    int $curriculumItemId,
    int $studentId,
    $grade
): array {
    $semester = $semester === '2' ? '2' : '1';
    $student = get_student_by_id($studentId);
    if ($student === null) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    require_once __DIR__ . '/../../../includes/curriculum_composites.php';
    $item = get_curriculum_item_by_id($curriculumItemId);
    $isChildPart = $item !== null && (int) ($item['parent_item_id'] ?? 0) > 0;

    if ($grade === null || $grade === '') {
        // Для раздела ПА не хранится — очищение не ошибка.
        if ($isChildPart) {
            return ['success' => true, 'grade' => null];
        }
        $stmt = db()->prepare(
            'DELETE FROM manual_brs_attestations
             WHERE academic_year = ? AND semester = ?
               AND curriculum_item_id = ? AND student_id = ?'
        );
        $stmt->execute([$academicYear, $semester, $curriculumItemId, $studentId]);

        return ['success' => true, 'grade' => null];
    }

    if ($isChildPart) {
        return [
            'success' => false,
            'error' => 'ПА выставляется по родительской дисциплине (МДК / БВС), а не по разделу.',
        ];
    }

    $grade = (int) $grade;
    if ($grade < 2 || $grade > 5) {
        return ['success' => false, 'error' => 'ПА: оценка 2–5 или пусто.'];
    }

    $stmt = db()->prepare(
        'INSERT INTO manual_brs_attestations
         (academic_year, semester, curriculum_item_id, student_id, grade)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE grade = VALUES(grade)'
    );
    $stmt->execute([$academicYear, $semester, $curriculumItemId, $studentId, $grade]);

    return ['success' => true, 'grade' => $grade];
}

/**
 * Итог семестра: для родителя с разделами — агрегация разделов + ПА родителя.
 */
function manual_brs_resolve_semester_grade(
    string $academicYear,
    string $semester,
    int $curriculumItemId,
    int $studentId
): array {
    require_once __DIR__ . '/../../../includes/curriculum_composites.php';
    $periods = [manual_brs_period_for_semester($semester)];
    $periodId = $periods[0];
    $paMap = manual_brs_get_attestations_map($academicYear, $semester, $curriculumItemId);
    $pa = $paMap[$studentId] ?? null;

    if (curriculum_item_has_children($curriculumItemId, $semester)) {
        $partGrades = [];
        foreach (get_curriculum_item_children($curriculumItemId, $semester) as $child) {
            $childId = (int) $child['id'];
            $periodData = manual_brs_points_for_periods($academicYear, $childId, $studentId, $periods);
            $childSem = manual_brs_semester_total($periodData[$periodId]['points'] ?? null, null, null);
            if ($childSem['grade'] !== null) {
                $partGrades[] = (int) $childSem['grade'];
            }
        }
        $base = aggregate_composite_part_grades($partGrades);
        $grade = resolve_composite_final_grade($base, $pa);

        return [
            'points' => null,
            'grade' => $grade,
            'brs_grade' => $base,
            'pa_grade' => $pa,
            'display' => $grade !== null ? (string) $grade : '',
            'html' => manual_brs_render_semester_html($grade, null),
        ];
    }

    $periodData = manual_brs_points_for_periods($academicYear, $curriculumItemId, $studentId, $periods);

    return manual_brs_semester_total($periodData[$periodId]['points'] ?? null, null, $pa);
}

function manual_brs_build_student_rows(
    array $students,
    array $entries,
    string $academicYear,
    int $curriculumItemId,
    int $period,
    int $lessonsTotal = 0
): array {
    $period = manual_brs_normalize_period($period);
    $semester = manual_brs_semester_from_period($period);
    $paMap = manual_brs_get_attestations_map($academicYear, $semester, $curriculumItemId);
    $brs = manual_brs_weights();
    require_once __DIR__ . '/../../../includes/curriculum_composites.php';
    $isCompositeParent = curriculum_item_has_children($curriculumItemId, $semester);
    $isPart = false;
    $itemMeta = get_curriculum_item_by_id($curriculumItemId);
    if ($itemMeta !== null) {
        $isPart = (int) ($itemMeta['parent_item_id'] ?? 0) > 0;
    }
    $rows = [];

    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $entry = $entries[$studentId] ?? null;
        $paGrade = $isPart ? null : ($paMap[$studentId] ?? null);

        if ($isCompositeParent) {
            $sem = manual_brs_resolve_semester_grade($academicYear, $semester, $curriculumItemId, $studentId);
            $display = $sem['brs_grade'] !== null ? ('разделы → ' . (int) $sem['brs_grade']) : '—';
            $rows[] = [
                'student' => $student,
                'entry' => $entry,
                'points' => null,
                'grade' => $sem['brs_grade'],
                'display' => $display,
                'pa_grade' => $paGrade,
                'semester_display' => $sem['display'],
                'semester_grade' => $sem['grade'],
                'semester_points' => null,
                'semester_html' => $sem['html'],
                'current_marks_input' => '',
                'control_marks_input' => '',
                'absent_count' => 0,
                'late_count' => 0,
                'activity_count' => 0,
                'stages' => manual_brs_entry_stages(null),
                'attestation_only' => true,
                'hide_pa' => false,
            ];
            continue;
        }

        $calc = null;
        if ($entry !== null) {
            $calc = manual_brs_calc_from_entry($entry, $lessonsTotal, $brs);
            // Синхронизируем кэш в БД, если формула в админке изменилась.
            $newPoints = $calc['points'] !== null ? round((float) $calc['points'], 1) : null;
            $newGrade = $calc['grade'] !== null ? (int) $calc['grade'] : null;
            $oldPoints = $entry['points'] !== null ? round((float) $entry['points'], 1) : null;
            $oldGrade = $entry['grade'] !== null ? (int) $entry['grade'] : null;
            if ($newPoints !== $oldPoints || $newGrade !== $oldGrade) {
                $upd = db()->prepare(
                    'UPDATE manual_brs_entries
                     SET current_avg = ?, control_avg = ?, points = ?, grade = ?
                     WHERE id = ?'
                );
                $upd->execute([
                    $calc['current_avg'],
                    $calc['control_avg'],
                    $newPoints,
                    $newGrade,
                    (int) $entry['id'],
                ]);
            }
        }

        $periodPoints = $calc['points'] ?? null;
        $periodGrade = $calc['grade'] ?? null;
        $sem = manual_brs_semester_total(
            $periodPoints !== null ? (float) $periodPoints : null,
            null,
            $paGrade,
            $brs
        );

        $rows[] = [
            'student' => $student,
            'entry' => $entry,
            'points' => $periodPoints !== null ? (float) $periodPoints : null,
            'grade' => $periodGrade !== null ? (int) $periodGrade : null,
            'display' => $calc['display'] ?? '',
            'pa_grade' => $paGrade,
            'semester_display' => $sem['display'],
            'semester_grade' => $sem['grade'],
            'semester_points' => $sem['points'],
            'semester_html' => $sem['html'],
            'current_marks_input' => $entry
                ? manual_brs_marks_to_input(manual_brs_parse_marks((string) $entry['current_marks']))
                : '',
            'control_marks_input' => $entry
                ? manual_brs_marks_to_input(manual_brs_parse_marks((string) $entry['control_marks']))
                : '',
            'absent_count' => $entry ? (int) $entry['absent_count'] : 0,
            'late_count' => $entry ? (int) $entry['late_count'] : 0,
            'activity_count' => $entry ? (int) $entry['activity_count'] : 0,
            'stages' => manual_brs_entry_stages($entry),
            'attestation_only' => false,
            'hide_pa' => $isPart,
        ];
    }

    return $rows;
}
