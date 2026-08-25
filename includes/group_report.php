<?php

declare(strict_types=1);

require_once __DIR__ . '/students.php';
require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/glaz.php';

/**
 * Аналитическая справка по группе на основе карточек студентов.
 * Пересчитывается при каждом открытии (после сохранения карточек).
 */
function build_group_report(array $students, ?int $groupId = null): array
{
    $total = count($students);
    $male = 0;
    $female = 0;
    $genderUnknown = 0;
    $ages = [];
    $minor = 0;
    $adult = 0;
    $ageUnknown = 0;
    $districts = [];
    $withDistrict = 0;
    $lowIncome = 0;
    $familyComplete = 0;
    $familyNoFather = 0;
    $familyNoMother = 0;
    $familyUnknown = 0;
    $largeFamily = 0;
    $dormitory = 0;
    $nonresidentApartment = 0;
    $withoutParentalCare = 0;

    foreach ($students as $student) {
        $gender = (string) ($student['gender'] ?? '');
        if ($gender === 'male') {
            $male++;
        } elseif ($gender === 'female') {
            $female++;
        } else {
            $genderUnknown++;
        }

        $age = student_age_years(isset($student['birth_date']) ? (string) $student['birth_date'] : null);
        if ($age === null) {
            $ageUnknown++;
        } else {
            $ages[] = $age;
            if ($age < 18) {
                $minor++;
            } else {
                $adult++;
            }
        }

        $district = student_geography_label($student);
        if ($district !== '') {
            $withDistrict++;
            $key = mb_strtolower($district);
            if (!isset($districts[$key])) {
                $districts[$key] = ['name' => $district, 'count' => 0];
            }
            $districts[$key]['count']++;
        }

        if (!empty($student['is_low_income'])) {
            $lowIncome++;
        }

        $familyType = (string) ($student['family_type'] ?? '');
        if ($familyType === 'complete') {
            $familyComplete++;
        } elseif ($familyType === 'no_father') {
            $familyNoFather++;
        } elseif ($familyType === 'no_mother') {
            $familyNoMother++;
        } else {
            $familyUnknown++;
        }

        if ((int) ($student['siblings_under_18'] ?? 0) >= 2) {
            $largeFamily++;
        }

        if ((string) ($student['residence_type'] ?? '') === 'dormitory') {
            $dormitory++;
        }

        if (
            !empty($student['is_nonresident'])
            && (string) ($student['residence_type'] ?? '') === 'apartment'
        ) {
            $nonresidentApartment++;
        }

        if (!empty($student['without_parental_care'])) {
            $withoutParentalCare++;
        }
    }

    uasort($districts, static function (array $a, array $b): int {
        if ($a['count'] === $b['count']) {
            return strcmp($a['name'], $b['name']);
        }

        return $b['count'] <=> $a['count'];
    });

    $avgAge = $ages === [] ? null : round(array_sum($ages) / count($ages), 1);
    $minAge = $ages === [] ? null : min($ages);
    $maxAge = $ages === [] ? null : max($ages);

    $report = [
        'total' => $total,
        'male' => $male,
        'female' => $female,
        'gender_unknown' => $genderUnknown,
        'avg_age' => $avgAge,
        'min_age' => $minAge,
        'max_age' => $maxAge,
        'minor' => $minor,
        'adult' => $adult,
        'age_unknown' => $ageUnknown,
        'districts' => array_values($districts),
        'district_unknown' => $total - $withDistrict,
        'low_income' => $lowIncome,
        'family_complete' => $familyComplete,
        'family_no_father' => $familyNoFather,
        'family_no_mother' => $familyNoMother,
        'family_incomplete' => $familyNoFather + $familyNoMother,
        'family_unknown' => $familyUnknown,
        'large_family' => $largeFamily,
        'dormitory' => $dormitory,
        'nonresident_apartment' => $nonresidentApartment,
        'without_parental_care' => $withoutParentalCare,
        'sanctions' => [],
    ];

    return array_merge($report, build_group_report_academic($students, $groupId));
}

/**
 * Успеваемость и академические задолженности по группе.
 */
function build_group_report_academic(array $students, ?int $groupId): array
{
    $result = [
        'absolute_percent' => null,
        'quality_percent' => null,
        'assessed_students' => 0,
        'with_twos_count' => 0,
        'only_good_count' => 0,
        'excellent_count' => 0,
        'with_twos' => [],
        'only_good' => [],
        'excellent' => [],
        'academic_period_label' => '',
        'academic_available' => false,
        'debts' => [],
        'debtors' => [],
        'debtors_count' => 0,
        'debts_count' => 0,
    ];

    if ($students === []) {
        return $result;
    }

    $studentIds = [];
    $studentNames = [];
    foreach ($students as $student) {
        $id = (int) $student['id'];
        $studentIds[$id] = true;
        $studentNames[$id] = (string) $student['full_name'];
    }

    $period = get_active_gradebook_period();
    $year = (string) ($period['academic_year'] ?? '');
    $semester = (string) ($period['semester'] ?? '1');
    $result['academic_period_label'] = $year !== ''
        ? ($year . ' · ' . semester_label($semester))
        : '';

    if ($groupId !== null && $groupId > 0 && $year !== '') {
        $subjects = get_group_curriculum_subjects($groupId, $year, $semester);
        $grades = $subjects !== []
            ? get_gradebook_grades_from_journal($groupId, $year, $semester)
            : [];
        $summary = build_gradebook_summary($students, $subjects, $grades);
        $lists = build_gradebook_student_lists($students, $subjects, $grades);

        $result['absolute_percent'] = (int) ($summary['assessed_students'] ?? 0) > 0
            ? (float) $summary['absolute_percent']
            : null;
        $result['quality_percent'] = (int) ($summary['assessed_students'] ?? 0) > 0
            ? (float) $summary['quality_percent']
            : null;
        $result['assessed_students'] = (int) ($summary['assessed_students'] ?? 0);
        $result['with_twos'] = $lists['with_twos'];
        $result['only_good'] = $lists['only_good'];
        $result['excellent'] = $lists['excellent'];
        $result['with_twos_count'] = count($lists['with_twos']);
        $result['only_good_count'] = count($lists['only_good']);
        $result['excellent_count'] = count($lists['excellent']);
        $result['academic_available'] = (int) ($summary['assessed_students'] ?? 0) > 0
            || $subjects !== [];
    }

    $debtors = [];
    $debtsFlat = [];
    foreach (get_all_academic_debts() as $debt) {
        $studentId = (int) $debt['student_id'];
        if (!isset($studentIds[$studentId])) {
            continue;
        }

        $name = $studentNames[$studentId] !== ''
            ? $studentNames[$studentId]
            : (string) $debt['student_name'];
        $subject = (string) $debt['subject_name'];
        $periodLabel = (string) $debt['period_label'];

        $debtsFlat[] = [
            'student_id' => $studentId,
            'student_name' => $name,
            'subject_name' => $subject,
            'period_label' => $periodLabel,
        ];

        if (!isset($debtors[$studentId])) {
            $debtors[$studentId] = [
                'student_id' => $studentId,
                'student_name' => $name,
                'subjects' => [],
            ];
        }
        $debtors[$studentId]['subjects'][] = [
            'subject_name' => $subject,
            'period_label' => $periodLabel,
        ];
    }

    usort($debtsFlat, static function (array $a, array $b): int {
        $nameCmp = strcasecmp($a['student_name'], $b['student_name']);
        if ($nameCmp !== 0) {
            return $nameCmp;
        }

        return strcmp($a['period_label'], $b['period_label']);
    });

    $debtorList = array_values($debtors);
    usort($debtorList, static fn (array $a, array $b): int => strcasecmp($a['student_name'], $b['student_name']));

    $result['debts'] = $debtsFlat;
    $result['debtors'] = $debtorList;
    $result['debtors_count'] = count($debtorList);
    $result['debts_count'] = count($debtsFlat);

    return $result;
}

function format_group_report_number($value, string $empty = '—'): string
{
    if ($value === null || $value === '') {
        return $empty;
    }

    if (is_float($value)) {
        $formatted = rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');

        return $formatted !== '' ? $formatted : '0';
    }

    return (string) $value;
}
