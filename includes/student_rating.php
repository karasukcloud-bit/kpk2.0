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
        'satisfactory' => 'Удовлетворительно',
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
        $key = $hasTwo . '|' . (float) $item['average'];
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
