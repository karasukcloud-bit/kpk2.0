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
    return [manual_brs_period_for_semester($semester)];
}

/**
 * Итоговые оценки семестра из ручного БРС.
 * @return array<int, array<int, int>> student_id => curriculum_item_id => grade 2–5
 */
function manual_brs_get_gradebook_grades(
    int $groupId,
    string $academicYear,
    string $semester,
    ?array $batchContext = null
): array {
    require_once __DIR__ . '/data.php';

    $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
    if ($subjects === []) {
        return [];
    }

    $students = get_students_by_group($groupId);
    if ($students === []) {
        return [];
    }

    $ctx = $batchContext ?? manual_brs_build_grade_batch_context($academicYear, $semester, $subjects);
    $result = [];

    foreach ($subjects as $subject) {
        $itemId = (int) $subject['curriculum_item_id'];
        foreach ($students as $student) {
            $studentId = (int) $student['id'];
            $sem = manual_brs_resolve_from_batch($itemId, $studentId, $ctx);
            if ($sem['grade'] !== null) {
                $result[$studentId][$itemId] = (int) $sem['grade'];
            }
        }
    }

    return $result;
}

/**
 * Строки «неуспевающих»: дисциплина + внутренний раздел с оценкой 2.
 *
 * @return list<array{discipline:string,section:string,label:string}>
 */
function manual_brs_failing_entries_for_student(
    string $academicYear,
    string $semester,
    array $subjects,
    int $studentId,
    ?array $batchContext = null
): array {
    require_once __DIR__ . '/data.php';
    require_once __DIR__ . '/../../../includes/curriculum_composites.php';

    $ctx = $batchContext ?? manual_brs_build_grade_batch_context($academicYear, $semester, $subjects);
    $entries = [];

    foreach ($subjects as $subject) {
        $itemId = (int) $subject['curriculum_item_id'];
        $sem = manual_brs_resolve_from_batch($itemId, $studentId, $ctx);
        if (($sem['grade'] ?? null) !== 2) {
            continue;
        }

        $discipline = (string) ($subject['subject_name'] ?? 'Дисциплина');
        $children = $ctx['children'][$itemId] ?? [];

        if ($children === []) {
            $entries[] = [
                'discipline' => $discipline,
                'section' => '',
                'label' => $discipline,
            ];
            continue;
        }

        $partTwos = [];
        foreach ($children as $child) {
            $childId = (int) $child['id'];
            $points = $ctx['calc'][$childId][$studentId]['points'] ?? null;
            $childSem = manual_brs_semester_total(
                $points !== null ? (float) $points : null,
                null,
                null
            );
            if (($childSem['grade'] ?? null) === 2) {
                $section = (string) ($child['subject_name'] ?? 'Раздел');
                $partTwos[] = [
                    'discipline' => $discipline,
                    'section' => $section,
                    'label' => $discipline . ' — ' . $section,
                ];
            }
        }

        if ($partTwos !== []) {
            foreach ($partTwos as $row) {
                $entries[] = $row;
            }
            continue;
        }

        if (($sem['pa_grade'] ?? null) === 2) {
            $entries[] = [
                'discipline' => $discipline,
                'section' => 'ПА',
                'label' => $discipline . ' — ПА',
            ];
            continue;
        }

        $entries[] = [
            'discipline' => $discipline,
            'section' => '',
            'label' => $discipline,
        ];
    }

    return $entries;
}

/**
 * Списки студентов для ведомости ручного БРС (с расшифровкой двоек по разделам).
 *
 * @return array{with_twos: list<array<string,mixed>>, only_good: list<array<string,mixed>>, excellent: list<array<string,mixed>>}
 */
/**
 * Группировка строк двоек: дисциплина => список разделов.
 *
 * @param list<array{discipline?:string,section?:string,label?:string}> $failing
 * @return array<string, list<string>>
 */
function manual_brs_group_failing_by_discipline(array $failing): array
{
    $grouped = [];
    foreach ($failing as $row) {
        $discipline = trim((string) ($row['discipline'] ?? ''));
        if ($discipline === '') {
            $discipline = trim((string) ($row['label'] ?? 'Дисциплина'));
        }
        $section = trim((string) ($row['section'] ?? ''));
        if (!isset($grouped[$discipline])) {
            $grouped[$discipline] = [];
        }
        if ($section !== '' && !in_array($section, $grouped[$discipline], true)) {
            $grouped[$discipline][] = $section;
        }
    }

    return $grouped;
}

/** HTML списка неуспевающих: студент → дисциплина → разделы. */
function manual_brs_render_failing_students_html(array $withTwos): string
{
    if ($withTwos === []) {
        return '<p class="text-muted">Нет студентов</p>';
    }

    $html = '<ul class="gradebook-failing-tree">';
    foreach ($withTwos as $item) {
        $failing = $item['failing'] ?? [];
        if ($failing === [] && !empty($item['subjects'])) {
            foreach ($item['subjects'] as $label) {
                $failing[] = [
                    'discipline' => (string) $label,
                    'section' => '',
                ];
            }
        }
        $grouped = manual_brs_group_failing_by_discipline($failing);
        $html .= '<li class="gradebook-failing-tree__student">'
            . '<strong>' . e(person_last_first_name((string) ($item['full_name'] ?? ''))) . '</strong>';
        if ($grouped !== []) {
            $html .= '<ul class="gradebook-failing-tree__disciplines">';
            foreach ($grouped as $discipline => $sections) {
                $html .= '<li class="gradebook-failing-tree__discipline">'
                    . e((string) $discipline);
                if ($sections !== []) {
                    $html .= '<ul class="gradebook-failing-tree__sections">';
                    foreach ($sections as $section) {
                        $html .= '<li class="gradebook-failing-tree__section">'
                            . e((string) $section) . '</li>';
                    }
                    $html .= '</ul>';
                }
                $html .= '</li>';
            }
            $html .= '</ul>';
        }
        $html .= '</li>';
    }

    return $html . '</ul>';
}

function manual_brs_build_gradebook_student_lists(
    int $groupId,
    string $academicYear,
    string $semester,
    array $students,
    array $subjects,
    array $grades,
    ?array $batchContext = null
): array {
    require_once __DIR__ . '/data.php';

    $lists = build_gradebook_student_lists($students, $subjects, $grades);
    $withTwos = [];
    $ctx = $batchContext ?? manual_brs_build_grade_batch_context($academicYear, $semester, $subjects);

    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $studentGrades = $grades[$studentId] ?? [];
        $hasTwo = false;
        foreach ($studentGrades as $grade) {
            if ((int) $grade === 2) {
                $hasTwo = true;
                break;
            }
        }
        if (!$hasTwo) {
            continue;
        }

        $failing = manual_brs_failing_entries_for_student(
            $academicYear,
            $semester,
            $subjects,
            $studentId,
            $ctx
        );
        if ($failing === []) {
            continue;
        }
        $withTwos[] = [
            'student_id' => $studentId,
            'full_name' => (string) $student['full_name'],
            'subjects' => array_column($failing, 'label'),
            'failing' => $failing,
        ];
    }

    usort(
        $withTwos,
        static fn (array $a, array $b): int => strcasecmp($a['full_name'], $b['full_name'])
    );
    $lists['with_twos'] = $withTwos;

    return $lists;
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

function manual_brs_is_control_week(
    int $groupId,
    string $academicYear,
    string $semester,
    array $subjects
): bool {
    return false;
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
