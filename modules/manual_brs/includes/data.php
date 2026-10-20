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

/** @return array{current_marks: string, control_marks: string, absent_count: int, late_count: int, activity_count: int, stages: array} */
function manual_brs_merge_stages_input(array $input): array
{
    $stage1Current = manual_brs_parse_marks((string) ($input['stage1_current_marks'] ?? $input['current_marks'] ?? ''));
    $stage1Control = manual_brs_parse_marks((string) ($input['stage1_control_marks'] ?? $input['control_marks'] ?? ''));
    $stage2Current = manual_brs_parse_marks((string) ($input['stage2_current_marks'] ?? ''));
    $stage2Control = manual_brs_parse_marks((string) ($input['stage2_control_marks'] ?? ''));

    $hasStagePost = array_key_exists('stage1_current_marks', $input)
        || array_key_exists('stage2_current_marks', $input);

    if ($hasStagePost) {
        $stage1Absent = max(0, (int) ($input['stage1_absent_count'] ?? 0));
        $stage1Late = max(0, (int) ($input['stage1_late_count'] ?? 0));
        $stage1Activity = max(0, (int) ($input['stage1_activity_count'] ?? 0));
        $stage2Absent = max(0, (int) ($input['stage2_absent_count'] ?? 0));
        $stage2Late = max(0, (int) ($input['stage2_late_count'] ?? 0));
        $stage2Activity = max(0, (int) ($input['stage2_activity_count'] ?? 0));
    } else {
        $stage1Absent = max(0, (int) ($input['absent_count'] ?? 0));
        $stage1Late = max(0, (int) ($input['late_count'] ?? 0));
        $stage1Activity = max(0, (int) ($input['activity_count'] ?? 0));
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

    $merged = manual_brs_merge_stages_input($input);
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

function manual_brs_points_for_periods(
    string $academicYear,
    int $curriculumItemId,
    int $studentId,
    array $periods
): array {
    $result = [];
    foreach ($periods as $period) {
        $period = (int) $period;
        $stmt = db()->prepare(
            'SELECT e.points, e.grade
             FROM manual_brs_sheets s
             INNER JOIN manual_brs_entries e ON e.sheet_id = s.id
             WHERE s.academic_year = ? AND s.period = ?
               AND s.curriculum_item_id = ? AND e.student_id = ?
             LIMIT 1'
        );
        $stmt->execute([$academicYear, $period, $curriculumItemId, $studentId]);
        $row = $stmt->fetch();
        $result[$period] = [
            'points' => $row && $row['points'] !== null ? (float) $row['points'] : null,
            'grade' => $row && $row['grade'] !== null ? (int) $row['grade'] : null,
            'display' => manual_brs_period_display(
                $row && $row['points'] !== null ? (float) $row['points'] : null,
                $row && $row['grade'] !== null ? (int) $row['grade'] : null
            ),
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
    int $period
): array {
    $period = manual_brs_normalize_period($period);
    $semester = manual_brs_semester_from_period($period);
    $paMap = manual_brs_get_attestations_map($academicYear, $semester, $curriculumItemId);
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

        $periodPoints = null;
        if ($entry && $entry['points'] !== null) {
            $periodPoints = (float) $entry['points'];
        }
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

        $sem = manual_brs_semester_total($periodPoints, null, $paGrade);

        $rows[] = [
            'student' => $student,
            'entry' => $entry,
            'points' => $entry && $entry['points'] !== null ? (float) $entry['points'] : null,
            'grade' => $entry && $entry['grade'] !== null ? (int) $entry['grade'] : null,
            'display' => $entry
                ? manual_brs_period_display(
                    $entry['points'] !== null ? (float) $entry['points'] : null,
                    $entry['grade'] !== null ? (int) $entry['grade'] : null
                )
                : '',
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
