<?php

declare(strict_types=1);

/**
 * Задолженности ГЛАЗ по итогам ручного БРС (оценка «2»).
 *
 * @return list<array<string, mixed>>
 */
function get_all_manual_brs_academic_debts(): array
{
    require_once __DIR__ . '/../../../includes/glaz.php';
    require_once __DIR__ . '/../../../includes/organization.php';
    require_once __DIR__ . '/../../../includes/students.php';
    require_once __DIR__ . '/../../../includes/gradebook.php';
    require_once __DIR__ . '/../../../includes/curriculum_composites.php';
    require_once __DIR__ . '/../bootstrap.php';
    require_once __DIR__ . '/gradebook.php';
    require_once __DIR__ . '/data.php';

    manual_brs_ensure_schema();

    $excluded = get_student_ids_excluded_from_glaz();
    $periods = [];
    $stmt = db()->query(
        'SELECT DISTINCT academic_year, period FROM manual_brs_sheets ORDER BY academic_year DESC, period ASC'
    );
    foreach ($stmt->fetchAll() as $row) {
        $year = (string) $row['academic_year'];
        $semester = manual_brs_semester_from_period((int) $row['period']);
        $periods[$year . '|' . $semester] = [
            'academic_year' => $year,
            'semester' => $semester,
        ];
    }

    // Активный период ведомости — чтобы сразу видеть текущие двойки.
    $active = get_active_gradebook_period();
    $activeKey = $active['academic_year'] . '|' . $active['semester'];
    if (!isset($periods[$activeKey])) {
        $periods[$activeKey] = [
            'academic_year' => (string) $active['academic_year'],
            'semester' => (string) $active['semester'],
        ];
    }

    $debts = [];
    foreach ($periods as $period) {
        $year = $period['academic_year'];
        $semester = $period['semester'];

        foreach (get_all_groups() as $group) {
            $groupId = (int) $group['id'];
            $subjects = get_group_curriculum_subjects($groupId, $year, $semester);
            if ($subjects === []) {
                continue;
            }
            $students = get_students_by_group($groupId);
            if ($students === []) {
                continue;
            }

            $batchCtx = manual_brs_build_grade_batch_context($year, $semester, $subjects);
            $grades = manual_brs_get_gradebook_grades($groupId, $year, $semester, $batchCtx);
            $failingByStudent = [];
            foreach ($students as $student) {
                $studentId = (int) $student['id'];
                if (isset($excluded[$studentId])) {
                    continue;
                }

                foreach ($subjects as $subject) {
                    $itemId = (int) $subject['curriculum_item_id'];
                    if (($grades[$studentId][$itemId] ?? null) !== 2) {
                        continue;
                    }

                    $causeDetail = '';
                    $suggestedCommissionIds = [];
                    $itemTeacherId = (int) ($subject['teacher_id'] ?? 0);
                    if ($itemTeacherId > 0) {
                        $suggestedCommissionIds[] = $itemTeacherId;
                    }

                    $children = $batchCtx['children'][$itemId] ?? [];
                    if ($children !== []) {
                        if (!isset($failingByStudent[$studentId])) {
                            $failingByStudent[$studentId] = manual_brs_failing_entries_for_student(
                                $year,
                                $semester,
                                $subjects,
                                $studentId,
                                $batchCtx
                            );
                        }
                        $lines = [];
                        foreach ($failingByStudent[$studentId] as $entry) {
                            if ((string) ($entry['discipline'] ?? '') !== (string) ($subject['subject_name'] ?? '')) {
                                continue;
                            }
                            $section = trim((string) ($entry['section'] ?? ''));
                            $lines[] = $section !== ''
                                ? ($section . ' (2)')
                                : ((string) ($entry['label'] ?? 'Раздел') . ' (2)');
                        }
                        if ($lines !== []) {
                            $causeDetail = implode('; ', $lines);
                        }
                        foreach ($children as $child) {
                            $childTeacher = (int) ($child['teacher_id'] ?? 0);
                            if ($childTeacher > 0) {
                                $suggestedCommissionIds[] = $childTeacher;
                            }
                        }
                    }

                    $suggestedCommissionIds = array_values(array_unique($suggestedCommissionIds));
                    $key = glaz_debt_key($studentId, $itemId, $year, $semester);

                    $debts[] = [
                        'student_id' => $studentId,
                        'curriculum_item_id' => $itemId,
                        'group_id' => $groupId,
                        'group_number' => (string) ($group['number'] ?? ''),
                        'student_name' => (string) ($student['full_name'] ?? ''),
                        'subject_name' => (string) ($subject['subject_name'] ?? ''),
                        'academic_year' => $year,
                        'semester' => $semester,
                        'period_label' => glaz_period_label($year, $semester),
                        'debt_key' => $key,
                        'archived_at' => null,
                        'cause_detail' => $causeDetail,
                        'suggested_commission_ids' => $suggestedCommissionIds,
                    ];
                }
            }
        }
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
