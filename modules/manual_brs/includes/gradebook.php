<?php

declare(strict_types=1);

/** Ведомость ручного БРС: только итоговые оценки за семестр. */

function manual_brs_require_gradebook_viewer(): void
{
    require_login();
    if (is_admin() || can_use_deputy_panel() || can_use_curator_panel()) {
        return;
    }

    http_response_code(403);
    exit('Доступ запрещён.');
}

function manual_brs_periods_for_semester(string $semester): array
{
    return $semester === '2' ? [3, 4] : [1, 2];
}

/**
 * Итоговые оценки семестра из ручного БРС.
 * @return array<int, array<int, int>> student_id => curriculum_item_id => grade 2–5
 */
function manual_brs_get_gradebook_grades(
    int $groupId,
    string $academicYear,
    string $semester
): array {
    $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
    if ($subjects === []) {
        return [];
    }

    $students = get_students_by_group($groupId);
    if ($students === []) {
        return [];
    }

    $periods = manual_brs_periods_for_semester($semester);
    $result = [];

    foreach ($subjects as $subject) {
        $itemId = (int) $subject['curriculum_item_id'];
        $paMap = manual_brs_get_attestations_map($academicYear, $semester, $itemId);

        foreach ($students as $student) {
            $studentId = (int) $student['id'];
            $periodData = manual_brs_points_for_periods(
                $academicYear,
                $itemId,
                $studentId,
                $periods
            );
            $sem = manual_brs_semester_total(
                $periodData[$periods[0]]['points'] ?? null,
                $periodData[$periods[1]]['points'] ?? null,
                $paMap[$studentId] ?? null
            );
            if ($sem['grade'] !== null) {
                $result[$studentId][$itemId] = (int) $sem['grade'];
            }
        }
    }

    return $result;
}

/**
 * Есть ли в периоде хотя бы одна запись БРС по предметам группы.
 */
function manual_brs_period_has_grades(
    int $groupId,
    string $academicYear,
    int $period,
    array $curriculumItemIds
): bool {
    if ($curriculumItemIds === []) {
        return false;
    }

    $placeholders = implode(',', array_fill(0, count($curriculumItemIds), '?'));
    $params = array_merge([$academicYear, $period, $groupId], array_map('intval', $curriculumItemIds));
    $stmt = db()->prepare(
        "SELECT 1
         FROM manual_brs_sheets s
         INNER JOIN manual_brs_entries e ON e.sheet_id = s.id
         WHERE s.academic_year = ?
           AND s.period = ?
           AND s.group_id = ?
           AND s.curriculum_item_id IN ($placeholders)
           AND e.points IS NOT NULL
         LIMIT 1"
    );
    $stmt->execute($params);

    return (bool) $stmt->fetchColumn();
}

/**
 * Контрольная неделя: есть оценки только за 1-й период семестра, за 2-й ещё нет.
 */
function manual_brs_is_control_week(
    int $groupId,
    string $academicYear,
    string $semester,
    array $subjects
): bool {
    $itemIds = [];
    foreach ($subjects as $subject) {
        $itemIds[] = (int) ($subject['curriculum_item_id'] ?? $subject['id'] ?? 0);
    }
    $itemIds = array_values(array_filter($itemIds));
    if ($itemIds === []) {
        return false;
    }

    $periods = manual_brs_periods_for_semester($semester);
    $hasFirst = manual_brs_period_has_grades($groupId, $academicYear, $periods[0], $itemIds);
    $hasSecond = manual_brs_period_has_grades($groupId, $academicYear, $periods[1], $itemIds);

    return $hasFirst && !$hasSecond;
}

function manual_brs_gradebook_title(string $groupNumber, string $semester, string $academicYear): string
{
    return 'Электронная ведомость группы ' . $groupNumber
        . ' за ' . semester_label($semester)
        . ' ' . $academicYear . ' учебного года';
}

function manual_brs_gradebook_url(string $panel, array $params = []): string
{
    $file = 'manual_brs_gradebook.php';
    $query = array_filter($params, static function ($value) {
        return $value !== null && $value !== '' && $value !== 0;
    });

    return $file . ($query !== [] ? '?' . http_build_query($query) : '');
}

function manual_brs_gradebook_pdf_url(string $panel, int $groupId): string
{
    return 'manual_brs_gradebook_pdf.php?group_id=' . $groupId;
}
