<?php

declare(strict_types=1);

require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/organization.php';
require_once __DIR__ . '/students.php';

/**
 * Рейтинг студентов по успеваемости за период (итоговые оценки из журнала).
 *
 * @return array{
 *   items: list<array>,
 *   specialties: list<array>,
 *   groups: list<array>
 * }
 */
function build_student_performance_rating(string $academicYear, string $semester): array
{
    $groups = get_all_groups();
    $items = [];
    $specialtyMap = [];
    $groupMap = [];

    foreach ($groups as $group) {
        $groupId = (int) $group['id'];
        $specialtyId = (int) ($group['specialty_id'] ?? 0);
        $groupNumber = (string) $group['number'];
        $specialtyName = (string) ($group['specialty_name'] ?? '');
        $specialtyCode = (string) ($group['specialty_code'] ?? '');

        $groupMap[$groupId] = [
            'id' => $groupId,
            'number' => $groupNumber,
            'specialty_id' => $specialtyId,
            'specialty_name' => $specialtyName,
        ];

        if ($specialtyId > 0 && !isset($specialtyMap[$specialtyId])) {
            $specialtyMap[$specialtyId] = [
                'id' => $specialtyId,
                'name' => $specialtyName,
                'code' => $specialtyCode,
            ];
        }

        $students = get_students_by_group($groupId);
        $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
        if ($students === [] || $subjects === []) {
            continue;
        }

        $grades = get_gradebook_grades_from_journal($groupId, $academicYear, $semester);
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

            $items[] = [
                'student_id' => $studentId,
                'full_name' => (string) $student['full_name'],
                'group_id' => $groupId,
                'group_number' => $groupNumber,
                'specialty_id' => $specialtyId,
                'specialty_name' => $specialtyName,
                'specialty_code' => $specialtyCode,
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

    $groupList = array_values($groupMap);
    usort($groupList, static fn (array $a, array $b): int => strnatcasecmp($a['number'], $b['number']));

    return [
        'items' => $items,
        'specialties' => $specialties,
        'groups' => $groupList,
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
    $index = 0;
    $prevKey = null;

    foreach ($items as &$item) {
        $index++;
        $key = $item['average'] . '|' . $item['grades_count'];
        if ($key !== $prevKey) {
            $place = $index;
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
    if ($scope === 'specialty' && $scopeId > 0) {
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
