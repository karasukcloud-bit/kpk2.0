<?php

declare(strict_types=1);

require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/organization.php';
require_once __DIR__ . '/students.php';

/** Виртуальный id специальности для групп «Общеобразовательный цикл». */
function student_rating_general_education_specialty_id(): int
{
    return -1;
}

/**
 * Рейтинг студентов по успеваемости за период.
 * $source: journal | manual_brs
 *
 * @return array{
 *   items: list<array>,
 *   specialties: list<array>,
 *   groups: list<array>,
 *   source: string
 * }
 */
function build_student_performance_rating(
    string $academicYear,
    string $semester,
    string $source = 'journal'
): array {
    $source = $source === 'manual_brs' ? 'manual_brs' : 'journal';
    $useManualBrs = $source === 'manual_brs' && is_manual_brs_gradebook_active();
    if ($useManualBrs) {
        require_once __DIR__ . '/../modules/manual_brs/bootstrap.php';
        require_once __DIR__ . '/../modules/manual_brs/includes/data.php';
        require_once __DIR__ . '/../modules/manual_brs/includes/gradebook.php';
        manual_brs_ensure_schema();
    } else {
        $source = 'journal';
    }

    $groups = get_all_groups();
    $items = [];
    $specialtyMap = [];
    $groupMap = [];
    $geSpecialtyId = student_rating_general_education_specialty_id();
    $geLabel = gradebook_general_education_label();
    $hasGeneralEducationItems = false;

    foreach ($groups as $group) {
        $groupId = (int) $group['id'];
        $isGeneralEducation = gradebook_group_is_general_education($group);
        $specialtyId = $isGeneralEducation
            ? $geSpecialtyId
            : (int) ($group['specialty_id'] ?? 0);
        $groupNumber = (string) $group['number'];
        $specialtyName = $isGeneralEducation
            ? $geLabel
            : (string) ($group['specialty_name'] ?? '');
        $specialtyCode = $isGeneralEducation
            ? ''
            : (string) ($group['specialty_code'] ?? '');

        $groupMap[$groupId] = [
            'id' => $groupId,
            'number' => $groupNumber,
            'specialty_id' => $specialtyId,
            'specialty_name' => $specialtyName,
            'is_general_education' => $isGeneralEducation,
        ];

        if (!$isGeneralEducation && $specialtyId > 0 && !isset($specialtyMap[$specialtyId])) {
            $specialtyMap[$specialtyId] = [
                'id' => $specialtyId,
                'name' => $specialtyName,
                'code' => $specialtyCode,
                'is_general_education' => false,
            ];
        }

        $students = get_students_by_group($groupId);
        $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
        if ($students === [] || $subjects === []) {
            continue;
        }

        if ($useManualBrs) {
            $batch = manual_brs_build_grade_batch_context($academicYear, $semester, $subjects);
            $grades = manual_brs_get_gradebook_grades($groupId, $academicYear, $semester, $batch);
        } else {
            $grades = get_gradebook_grades_from_journal($groupId, $academicYear, $semester);
        }
        $subjectCount = count($subjects);

        foreach ($students as $student) {
            $studentId = (int) $student['id'];
            $studentGrades = $grades[$studentId] ?? [];
            $values = [];

            foreach ($subjects as $subject) {
                $itemId = (int) $subject['curriculum_item_id'];
                if (isset($studentGrades[$itemId])) {
                    $values[] = (int) $studentGrades[$itemId];
                }
            }

            if ($values === []) {
                continue;
            }

            $graded = count($values);
            $sum = array_sum($values);
            $average = round($sum / $graded, 2);
            $min = min($values);
            $max = max($values);

            if ($isGeneralEducation) {
                $hasGeneralEducationItems = true;
            }

            $items[] = [
                'student_id' => $studentId,
                'full_name' => (string) $student['full_name'],
                'group_id' => $groupId,
                'group_number' => $groupNumber,
                'specialty_id' => $specialtyId,
                'specialty_name' => $specialtyName,
                'specialty_code' => $specialtyCode,
                'is_general_education' => $isGeneralEducation,
                'average' => $average,
                'grades_count' => $graded,
                'subjects_count' => $subjectCount,
                'min_grade' => $min,
                'max_grade' => $max,
                'category' => student_rating_category($min),
            ];
        }
    }

    usort($items, static function (array $a, array $b): int {
        $aFail = (int) $a['min_grade'] <= 2 ? 1 : 0;
        $bFail = (int) $b['min_grade'] <= 2 ? 1 : 0;
        if ($aFail !== $bFail) {
            return $aFail <=> $bFail;
        }
        if ($a['average'] !== $b['average']) {
            return $b['average'] <=> $a['average'];
        }
        $aHasThree = (int) $a['min_grade'] === 3 ? 1 : 0;
        $bHasThree = (int) $b['min_grade'] === 3 ? 1 : 0;
        if ($aHasThree !== $bHasThree) {
            return $aHasThree <=> $bHasThree;
        }
        if ($a['grades_count'] !== $b['grades_count']) {
            return $b['grades_count'] <=> $a['grades_count'];
        }

        return strcasecmp($a['full_name'], $b['full_name']);
    });

    $specialties = array_values($specialtyMap);
    usort($specialties, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));
    if ($hasGeneralEducationItems) {
        $specialties[] = [
            'id' => $geSpecialtyId,
            'name' => $geLabel,
            'code' => '',
            'is_general_education' => true,
        ];
    }

    $groupList = array_values($groupMap);
    usort($groupList, static fn (array $a, array $b): int => strnatcasecmp($a['number'], $b['number']));

    return [
        'items' => $items,
        'specialties' => $specialties,
        'groups' => $groupList,
        'source' => $source,
    ];
}

function student_rating_category(int $minGrade): string
{
    if ($minGrade >= 5) {
        return 'excellent';
    }
    if ($minGrade >= 4) {
        return 'good';
    }
    if ($minGrade >= 3) {
        return 'satisfactory';
    }

    return 'failing';
}

function student_rating_category_label(string $category): string
{
    return match ($category) {
        'excellent' => 'Отличник',
        'good' => 'Хорошист',
        'satisfactory' => 'Есть 3',
        'failing' => 'Есть «2»',
        default => '—',
    };
}

/**
 * @param list<array> $items
 * @return list<array>
 */
function assign_student_rating_places(array $items): array
{
    $place = 0;
    $prevKey = null;

    foreach ($items as &$item) {
        $hasTwo = (int) $item['min_grade'] <= 2 ? 1 : 0;
        $hasThree = (int) $item['min_grade'] === 3 ? 1 : 0;
        $key = $hasTwo . '|' . $hasThree . '|' . (float) $item['average'];
        if ($key !== $prevKey) {
            $place++;
            $prevKey = $key;
        }
        $item['place'] = $place;
    }
    unset($item);

    return $items;
}

/**
 * @param list<array> $items
 * @return list<array>
 */
function filter_student_rating_items(array $items, string $scope, int $scopeId): array
{
    if ($scope === 'specialty' && $scopeId !== 0) {
        $items = array_values(array_filter(
            $items,
            static fn (array $row): bool => (int) $row['specialty_id'] === $scopeId
        ));
    } elseif ($scope === 'group' && $scopeId > 0) {
        $items = array_values(array_filter(
            $items,
            static fn (array $row): bool => (int) $row['group_id'] === $scopeId
        ));
    }

    return assign_student_rating_places($items);
}

function student_rating_medal_svg(int $place): string
{
    $colors = [
        1 => ['#f59e0b', '#fbbf24', '#b45309'],
        2 => ['#94a3b8', '#cbd5e1', '#475569'],
        3 => ['#d97706', '#f59e0b', '#9a3412'],
    ];
    if (!isset($colors[$place])) {
        return '';
    }
    [$fill, $light, $stroke] = $colors[$place];

    return '<svg class="rating-place__icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">'
        . '<path fill="' . $fill . '" d="M8 2h2.2l1.8 4.2L13.8 2H16l-2.4 5.2A5.5 5.5 0 0 1 17.5 12.5 5.5 5.5 0 0 1 12 18a5.5 5.5 0 0 1-5.5-5.5 5.5 5.5 0 0 1 3.9-5.3L8 2z"/>'
        . '<circle cx="12" cy="12.5" r="3.2" fill="' . $light . '" stroke="' . $stroke . '" stroke-width="1.2"/>'
        . '<text x="12" y="13.7" text-anchor="middle" font-size="7" font-weight="700" fill="' . $stroke . '">' . $place . '</text>'
        . '</svg>';
}

function student_rating_alert_svg(): string
{
    return '<svg class="rating-place__icon rating-place__icon--alert" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">'
        . '<path fill="#f59e0b" d="M12 3.2 2.8 20.2h18.4L12 3.2z"/>'
        . '<path fill="#fff" d="M11.1 9.2h1.8v5.2h-1.8V9.2zm0 6.4h1.8V18h-1.8v-2.4z"/>'
        . '</svg>';
}

/**
 * @param list<array> $items
 * @return array{0:int,1:array<int,true>}
 */
function student_rating_bottom_row_flags(array $items, int $count = 5): array
{
    $total = count($items);
    $flags = [];
    if ($total === 0 || $count <= 0) {
        return [$total, $flags];
    }
    $from = max(0, $total - $count);
    for ($i = $from; $i < $total; $i++) {
        $flags[$i] = true;
    }

    return [$total, $flags];
}

function render_student_rating_place_cell(int $place, bool $isBottom): string
{
    $placeClass = '';
    $icon = '';
    if ($place === 1) {
        $placeClass = ' rating-table__place--1';
        $icon = student_rating_medal_svg(1);
    } elseif ($place === 2) {
        $placeClass = ' rating-table__place--2';
        $icon = student_rating_medal_svg(2);
    } elseif ($place === 3) {
        $placeClass = ' rating-table__place--3';
        $icon = student_rating_medal_svg(3);
    } elseif ($isBottom) {
        $placeClass = ' rating-table__place--bottom';
        $icon = student_rating_alert_svg();
    }

    return '<td class="rating-table__place' . $placeClass . '">'
        . '<span class="rating-place">'
        . $icon
        . '<span class="rating-place__num">' . $place . '</span>'
        . '</span>'
        . '</td>';
}
