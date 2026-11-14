<?php

declare(strict_types=1);

require_once __DIR__ . '/curriculum_composites.php';
require_once __DIR__ . '/gradebook.php';

/**
 * Карта заполненности составных дисциплин для ведомости.
 *
 * @param list<array<string,mixed>> $students
 * @param list<array<string,mixed>> $subjects
 * @return array<int, array<string,mixed>> curriculum_item_id => details
 */
function build_gradebook_composite_fill_map(
    array $students,
    array $subjects,
    string $academicYear,
    string $semester,
    string $source = 'journal'
): array {
    require_once __DIR__ . '/individual_schedule.php';

    $source = $source === 'manual_brs' ? 'manual_brs' : 'journal';
    $semester = $semester === '2' ? '2' : '1';
    if ($students === [] || $subjects === []) {
        return [];
    }

    $groupId = (int) ($students[0]['group_id'] ?? 0);
    $igoIds = $groupId > 0
        ? get_group_individual_schedule_ids($groupId, $academicYear, $semester)
        : [];

    return $source === 'manual_brs'
        ? build_gradebook_composite_fill_map_manual_brs($students, $subjects, $academicYear, $semester, $igoIds)
        : build_gradebook_composite_fill_map_journal($students, $subjects, $semester, $igoIds);
}

/**
 * @param list<array<string,mixed>> $students
 * @param list<array<string,mixed>> $subjects
 * @return array<int, array<string,mixed>>
 */
/**
 * @param array<int, true> $igoIds
 */
function build_gradebook_composite_fill_map_journal(
    array $students,
    array $subjects,
    string $semester,
    array $igoIds = []
): array {
    require_once __DIR__ . '/journal.php';
    require_once __DIR__ . '/grading.php';

    $config = get_grading_config();
    $studentCount = count($students);
    $result = [];

    foreach ($subjects as $subject) {
        $itemId = (int) ($subject['curriculum_item_id'] ?? 0);
        if ($itemId <= 0) {
            continue;
        }
        $children = get_curriculum_item_children($itemId, $semester);
        if ($children === []) {
            continue;
        }

        $partGradesByChild = [];
        $childMeta = [];
        foreach ($children as $child) {
            $childId = (int) $child['id'];
            $childMeta[] = [
                'curriculum_item_id' => $childId,
                'subject_name' => (string) ($child['subject_name'] ?? 'Раздел'),
            ];
            $lessons = get_journal_lessons($childId);
            $journalGrades = get_journal_grades($childId);
            $totals = build_journal_totals($students, $lessons, $journalGrades, $config);
            foreach ($students as $student) {
                $studentId = (int) $student['id'];
                $total = $totals[$studentId] ?? null;
                $partGradesByChild[$childId][$studentId] = $total !== null
                    ? resolve_journal_final_grade_for_gradebook($total)
                    : null;
            }
        }

        $parentLessons = get_journal_lessons($itemId);
        $parentGrades = get_journal_grades($itemId);
        $paByStudent = [];
        foreach ($students as $student) {
            $studentId = (int) $student['id'];
            $paByStudent[$studentId] = gradebook_composite_pa_from_marks(
                collect_attestation_marks($parentLessons, $parentGrades[$studentId] ?? [])
            );
        }

        $result[$itemId] = gradebook_composite_fill_pack(
            $itemId,
            (string) ($subject['subject_name'] ?? 'Дисциплина'),
            $childMeta,
            $students,
            $partGradesByChild,
            $paByStudent,
            $studentCount,
            $igoIds
        );
    }

    return $result;
}

/**
 * @param list<array<string,mixed>> $students
 * @param list<array<string,mixed>> $subjects
 * @return array<int, array<string,mixed>>
 */
/**
 * @param array<int, true> $igoIds
 */
function build_gradebook_composite_fill_map_manual_brs(
    array $students,
    array $subjects,
    string $academicYear,
    string $semester,
    array $igoIds = []
): array {
    if (!is_manual_brs_gradebook_active()) {
        return [];
    }

    require_once __DIR__ . '/../modules/manual_brs/bootstrap.php';
    require_once __DIR__ . '/../modules/manual_brs/includes/data.php';
    manual_brs_ensure_schema();

    $batch = manual_brs_build_grade_batch_context($academicYear, $semester, $subjects);
    $studentCount = count($students);
    $result = [];

    foreach ($subjects as $subject) {
        $itemId = (int) ($subject['curriculum_item_id'] ?? 0);
        if ($itemId <= 0) {
            continue;
        }
        $children = $batch['children'][$itemId] ?? [];
        if ($children === []) {
            continue;
        }

        $childMeta = [];
        $partGradesByChild = [];
        foreach ($children as $child) {
            $childId = (int) $child['id'];
            $childMeta[] = [
                'curriculum_item_id' => $childId,
                'subject_name' => (string) ($child['subject_name'] ?? 'Раздел'),
            ];
            foreach ($students as $student) {
                $studentId = (int) $student['id'];
                $points = $batch['calc'][$childId][$studentId]['points'] ?? null;
                $childSem = manual_brs_semester_total(
                    $points !== null ? (float) $points : null,
                    null,
                    null
                );
                $partGradesByChild[$childId][$studentId] = $childSem['grade'] !== null
                    ? (int) $childSem['grade']
                    : null;
            }
        }

        $paByStudent = [];
        foreach ($students as $student) {
            $studentId = (int) $student['id'];
            $pa = $batch['pa'][$itemId][$studentId] ?? null;
            $paByStudent[$studentId] = $pa !== null ? (int) $pa : null;
        }

        $result[$itemId] = gradebook_composite_fill_pack(
            $itemId,
            (string) ($subject['subject_name'] ?? 'Дисциплина'),
            $childMeta,
            $students,
            $partGradesByChild,
            $paByStudent,
            $studentCount,
            $igoIds
        );
    }

    return $result;
}

/**
 * @param list<float|int|string> $marks
 */
function gradebook_composite_pa_from_marks(array $marks): ?int
{
    if ($marks === []) {
        return null;
    }
    foreach ($marks as $mark) {
        if ((int) $mark === 2) {
            return 2;
        }
    }

    return (int) round(
        array_sum(array_map('floatval', $marks)) / count($marks),
        0,
        PHP_ROUND_HALF_UP
    );
}

/**
 * @param list<array{curriculum_item_id:int,subject_name:string}> $childMeta
 * @param list<array<string,mixed>> $students
 * @param array<int, array<int, ?int>> $partGradesByChild
 * @param array<int, ?int> $paByStudent
 * @return array<string,mixed>
 */
/**
 * @param array<int, true> $igoIds
 */
function gradebook_composite_fill_pack(
    int $itemId,
    string $subjectName,
    array $childMeta,
    array $students,
    array $partGradesByChild,
    array $paByStudent,
    int $studentCount,
    array $igoIds = []
): array {
    $sectionsTotal = count($childMeta);
    $sectionsComplete = 0;
    $sectionFilledCounts = [];

    foreach ($childMeta as $child) {
        $childId = (int) $child['curriculum_item_id'];
        $filled = 0;
        $required = 0;
        foreach ($students as $student) {
            $studentId = (int) $student['id'];
            $grade = $partGradesByChild[$childId][$studentId] ?? null;
            $isIgo = isset($igoIds[$studentId]);
            if ($isIgo && $grade === null) {
                continue;
            }
            $required++;
            if ($grade !== null) {
                $filled++;
            }
        }
        $sectionFilledCounts[$childId] = $filled;
        if ($required === 0 || $filled === $required) {
            $sectionsComplete++;
        }
    }

    $paFilled = 0;
    $paRequired = 0;
    $rows = [];
    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $parts = [];
        $partGrades = [];
        foreach ($childMeta as $child) {
            $childId = (int) $child['curriculum_item_id'];
            $grade = $partGradesByChild[$childId][$studentId] ?? null;
            $parts[] = [
                'curriculum_item_id' => $childId,
                'subject_name' => (string) $child['subject_name'],
                'grade' => $grade,
            ];
            if ($grade !== null) {
                $partGrades[] = $grade;
            }
        }
        $paGrade = $paByStudent[$studentId] ?? null;
        $isIgo = isset($igoIds[$studentId]);
        if (!($isIgo && $paGrade === null)) {
            $paRequired++;
            if ($paGrade !== null) {
                $paFilled++;
            }
        }
        $base = aggregate_composite_part_grades($partGrades);
        $rows[] = [
            'student_id' => $studentId,
            'full_name' => (string) ($student['full_name'] ?? ''),
            'display_name' => person_last_first_name((string) ($student['full_name'] ?? '')),
            'parts' => $parts,
            'base_grade' => $base,
            'pa_grade' => $paGrade,
            'final_grade' => resolve_composite_final_grade($base, $paGrade),
        ];
    }

    $hasSectionGaps = $sectionsComplete < $sectionsTotal;
    $hasPaGaps = $paRequired > 0 && $paFilled < $paRequired;
    $hasGaps = $hasSectionGaps || $hasPaGaps;

    return [
        'curriculum_item_id' => $itemId,
        'subject_name' => $subjectName,
        'children' => $childMeta,
        'sections_complete' => $sectionsComplete,
        'sections_total' => $sectionsTotal,
        'section_filled_counts' => $sectionFilledCounts,
        'pa_filled' => $paFilled,
        'pa_total' => $paRequired,
        'has_section_gaps' => $hasSectionGaps,
        'has_pa_gaps' => $hasPaGaps,
        'has_gaps' => $hasGaps,
        'rows' => $rows,
        'status_label' => sprintf(
            'разделы %d/%d · ПА %d/%d',
            $sectionsComplete,
            $sectionsTotal,
            $paFilled,
            $paRequired
        ),
    ];
}

/**
 * @return array{sections: bool, pa: bool}
 */
function group_composite_gap_flags(
    int $groupId,
    string $academicYear,
    string $semester,
    string $source = 'journal'
): array {
    $flags = ['sections' => false, 'pa' => false];
    $students = get_students_by_group($groupId);
    $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
    if ($students === [] || $subjects === []) {
        return $flags;
    }

    $map = build_gradebook_composite_fill_map(
        $students,
        $subjects,
        $academicYear,
        $semester,
        $source
    );
    foreach ($map as $item) {
        if (!empty($item['has_section_gaps'])) {
            $flags['sections'] = true;
        }
        if (!empty($item['has_pa_gaps'])) {
            $flags['pa'] = true;
        }
        if ($flags['sections'] && $flags['pa']) {
            break;
        }
    }

    return $flags;
}

function group_has_incomplete_composite_sections(
    int $groupId,
    string $academicYear,
    string $semester,
    string $source = 'journal'
): bool {
    return group_composite_gap_flags($groupId, $academicYear, $semester, $source)['sections'];
}

function group_has_incomplete_composite_pa(
    int $groupId,
    string $academicYear,
    string $semester,
    string $source = 'journal'
): bool {
    return group_composite_gap_flags($groupId, $academicYear, $semester, $source)['pa'];
}
