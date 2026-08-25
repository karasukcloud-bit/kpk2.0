<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/curriculum.php';
require_once __DIR__ . '/students.php';

const GRADE_VALUES = [2, 3, 4, 5];

function get_app_setting(string $key, ?string $default = null): ?string
{
    $stmt = db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();

    return $value === false ? $default : (string) $value;
}

function set_app_setting(string $key, string $value): void
{
    $stmt = db()->prepare(
        'INSERT INTO app_settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

function ensure_app_settings_value_column(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $col = db()->query("SHOW COLUMNS FROM app_settings LIKE 'setting_value'")->fetch();
    if ($col && stripos((string) ($col['Type'] ?? ''), 'text') === false) {
        db()->exec('ALTER TABLE app_settings MODIFY setting_value TEXT NOT NULL');
    }
}

function get_active_gradebook_period(): array
{
    return [
        'academic_year' => normalize_academic_year(
            get_app_setting('active_academic_year', get_default_academic_year()) ?? get_default_academic_year()
        ) ?? get_default_academic_year(),
        'semester' => normalize_gradebook_semester(get_app_setting('active_semester', '1') ?? '1'),
    ];
}

function normalize_gradebook_semester(string $semester): string
{
    return in_array($semester, ['1', '2'], true) ? $semester : '1';
}

function save_active_gradebook_period(string $academicYear, string $semester): array
{
    $academicYear = normalize_academic_year($academicYear);
    $semester = normalize_gradebook_semester($semester);

    if ($academicYear === null) {
        return ['success' => false, 'error' => 'Некорректный учебный год. Формат: 2025-2026.'];
    }

    set_app_setting('active_academic_year', $academicYear);
    set_app_setting('active_semester', $semester);

    return ['success' => true];
}

function get_academic_year_horizon_extra(): int
{
    $extra = (int) (get_app_setting('academic_year_extra_years', '0') ?? '0');

    return max(0, $extra);
}

/** Максимальный стартовый год в списках (например 2045 для «2045-2046»). */
function get_academic_year_to_start_year(): int
{
    $currentStart = (int) explode('-', get_default_academic_year())[0];

    return $currentStart + 20 + get_academic_year_horizon_extra();
}

function extend_academic_year_horizon(int $years = 10): array
{
    $years = max(1, min(50, $years));
    $extra = get_academic_year_horizon_extra() + $years;
    set_app_setting('academic_year_extra_years', (string) $extra);

    $toStart = get_academic_year_to_start_year();

    return [
        'success' => true,
        'extra' => $extra,
        'to_start_year' => $toStart,
        'max_year_label' => $toStart . '-' . ($toStart + 1),
    ];
}

function validate_grade_value(string $grade): bool
{
    return in_array((int) $grade, GRADE_VALUES, true) && (string) (int) $grade === trim($grade);
}

function get_grade_entries_for_group_period(int $groupId, string $academicYear, string $semester): array
{
    $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
    if ($subjects === []) {
        return [];
    }

    $itemIds = array_map(static fn (array $item): int => (int) $item['curriculum_item_id'], $subjects);
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));

    $stmt = db()->prepare(
        "SELECT student_id, curriculum_item_id, grade
         FROM grade_entries
         WHERE curriculum_item_id IN ($placeholders)"
    );
    $stmt->execute($itemIds);

    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int) $row['student_id']][(int) $row['curriculum_item_id']] = (int) $row['grade'];
    }

    return $result;
}

/**
 * Итоговые оценки ведомости из электронного журнала (только чтение).
 * @return array<int, array<int, int>> student_id => curriculum_item_id => grade 2–5
 */
function get_gradebook_grades_from_journal(int $groupId, string $academicYear, string $semester): array
{
    require_once __DIR__ . '/journal.php';
    require_once __DIR__ . '/grading.php';

    $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
    if ($subjects === []) {
        return [];
    }

    $students = get_students_by_group($groupId);
    if ($students === []) {
        return [];
    }

    $config = get_grading_config();
    $result = [];

    foreach ($subjects as $subject) {
        $itemId = (int) $subject['curriculum_item_id'];
        $lessons = get_journal_lessons($itemId);
        $journalGrades = get_journal_grades($itemId);
        $totals = build_journal_totals($students, $lessons, $journalGrades, $config);

        foreach ($totals as $studentId => $total) {
            $grade = resolve_journal_final_grade_for_gradebook($total);
            if ($grade !== null) {
                $result[(int) $studentId][$itemId] = $grade;
            }
        }
    }

    return $result;
}

function resolve_journal_final_grade_for_gradebook(array $total): ?int
{
    if (($total['system'] ?? '') === 'brs') {
        if ($total['grade'] === null || $total['grade'] === '') {
            return null;
        }

        $grade = (int) $total['grade'];

        return in_array($grade, GRADE_VALUES, true) ? $grade : null;
    }

    if ($total['average'] === null) {
        return null;
    }

    return max(2, min(5, (int) round((float) $total['average'])));
}

/**
 * Статус заполнения ведомости группы за период.
 * @return array{complete: bool, empty: bool, filled: int, expected: int, students: int, subjects: int}
 */
function get_gradebook_completion_status(int $groupId, string $academicYear, string $semester): array
{
    $students = get_students_by_group($groupId);
    $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
    $studentCount = count($students);
    $subjectCount = count($subjects);
    $expected = $studentCount * $subjectCount;

    if ($expected === 0) {
        return [
            'complete' => false,
            'empty' => true,
            'filled' => 0,
            'expected' => 0,
            'students' => $studentCount,
            'subjects' => $subjectCount,
        ];
    }

    $grades = get_gradebook_grades_from_journal($groupId, $academicYear, $semester);
    $filled = 0;

    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        foreach ($subjects as $subject) {
            $itemId = (int) $subject['curriculum_item_id'];
            if (isset($grades[$studentId][$itemId])) {
                $filled++;
            }
        }
    }

    return [
        'complete' => $filled === $expected,
        'empty' => false,
        'filled' => $filled,
        'expected' => $expected,
        'students' => $studentCount,
        'subjects' => $subjectCount,
    ];
}

/**
 * Сводка ведомости группы: заполненность, успеваемость, списки.
 */
function get_group_gradebook_overview(int $groupId, string $academicYear, string $semester): array
{
    $students = get_students_by_group($groupId);
    $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
    $studentCount = count($students);
    $subjectCount = count($subjects);
    $expected = $studentCount * $subjectCount;
    $grades = ($expected > 0) ? get_gradebook_grades_from_journal($groupId, $academicYear, $semester) : [];
    $filled = 0;

    if ($expected > 0) {
        foreach ($students as $student) {
            $studentId = (int) $student['id'];
            foreach ($subjects as $subject) {
                $itemId = (int) $subject['curriculum_item_id'];
                if (isset($grades[$studentId][$itemId])) {
                    $filled++;
                }
            }
        }
    }

    $summary = build_gradebook_summary($students, $subjects, $grades);
    $lists = build_gradebook_student_lists($students, $subjects, $grades);

    return [
        'students' => $studentCount,
        'subjects' => $subjectCount,
        'filled' => $filled,
        'expected' => $expected,
        'complete' => $expected > 0 && $filled === $expected,
        'empty' => $expected === 0,
        'summary' => $summary,
        'with_twos' => count($lists['with_twos']),
        'only_good' => count($lists['only_good']),
        'excellent' => count($lists['excellent']),
    ];
}

function save_grade_entry(int $studentId, int $curriculumItemId, string $grade): array
{
    $student = get_student_by_id($studentId);
    $item = get_curriculum_item_by_id($curriculumItemId);

    if ($student === null || $item === null) {
        return ['success' => false, 'error' => 'Студент или предмет не найден.'];
    }

    $grade = trim($grade);

    if ($grade === '') {
        $stmt = db()->prepare(
            'DELETE FROM grade_entries WHERE student_id = ? AND curriculum_item_id = ?'
        );
        $stmt->execute([$studentId, $curriculumItemId]);

        return ['success' => true];
    }

    if (!validate_grade_value($grade)) {
        return ['success' => false, 'error' => 'Оценка должна быть от 2 до 5.'];
    }

    if ((int) $student['group_id'] !== (int) $item['group_id']) {
        return ['success' => false, 'error' => 'Студент и предмет относятся к разным группам.'];
    }

    $stmt = db()->prepare(
        'INSERT INTO grade_entries (student_id, curriculum_item_id, grade)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE grade = VALUES(grade)'
    );
    $stmt->execute([$studentId, $curriculumItemId, (int) $grade]);

    return ['success' => true];
}

function build_gradebook_summary(array $students, array $subjects, array $grades): array
{
    $totalStudents = count($students);
    if ($totalStudents === 0 || $subjects === []) {
        return [
            'total_students' => $totalStudents,
            'assessed_students' => 0,
            'absolute_count' => 0,
            'quality_count' => 0,
            'absolute_percent' => 0.0,
            'quality_percent' => 0.0,
            'one_two_count' => 0,
            'one_three_count' => 0,
            'one_two_percent' => 0.0,
            'one_three_percent' => 0.0,
        ];
    }

    $assessedStudents = 0;
    $absoluteCount = 0;
    $qualityCount = 0;
    $oneTwoCount = 0;
    $oneThreeCount = 0;

    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $studentGrades = $grades[$studentId] ?? [];
        $values = [];
        $twos = 0;
        $threes = 0;

        foreach ($subjects as $subject) {
            $itemId = (int) $subject['curriculum_item_id'];
            if (isset($studentGrades[$itemId])) {
                $grade = (int) $studentGrades[$itemId];
                $values[] = $grade;
                if ($grade === 2) {
                    $twos++;
                } elseif ($grade === 3) {
                    $threes++;
                }
            }
        }

        if ($twos === 1) {
            $oneTwoCount++;
        }
        if ($threes === 1) {
            $oneThreeCount++;
        }

        if ($values === []) {
            continue;
        }

        $assessedStudents++;

        if (min($values) >= 3) {
            $absoluteCount++;
        }

        if (min($values) >= 4) {
            $qualityCount++;
        }
    }

    if ($assessedStudents === 0) {
        return [
            'total_students' => $totalStudents,
            'assessed_students' => 0,
            'absolute_count' => 0,
            'quality_count' => 0,
            'absolute_percent' => 0.0,
            'quality_percent' => 0.0,
            'one_two_count' => $oneTwoCount,
            'one_three_count' => $oneThreeCount,
            'one_two_percent' => round($oneTwoCount / $totalStudents * 100, 1),
            'one_three_percent' => round($oneThreeCount / $totalStudents * 100, 1),
        ];
    }

    return [
        'total_students' => $totalStudents,
        'assessed_students' => $assessedStudents,
        'absolute_count' => $absoluteCount,
        'quality_count' => $qualityCount,
        'absolute_percent' => round($absoluteCount / $assessedStudents * 100, 1),
        'quality_percent' => round($qualityCount / $assessedStudents * 100, 1),
        'one_two_count' => $oneTwoCount,
        'one_three_count' => $oneThreeCount,
        'one_two_percent' => round($oneTwoCount / $totalStudents * 100, 1),
        'one_three_percent' => round($oneThreeCount / $totalStudents * 100, 1),
    ];
}

function build_gradebook_student_lists(array $students, array $subjects, array $grades): array
{
    $withTwos = [];
    $onlyGood = [];
    $excellent = [];

    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $studentGrades = $grades[$studentId] ?? [];
        $values = [];
        $twoSubjects = [];

        foreach ($subjects as $subject) {
            $itemId = (int) $subject['curriculum_item_id'];
            if (!isset($studentGrades[$itemId])) {
                continue;
            }

            $grade = (int) $studentGrades[$itemId];
            $values[] = $grade;

            if ($grade === 2) {
                $twoSubjects[] = (string) $subject['subject_name'];
            }
        }

        if ($twoSubjects !== []) {
            $withTwos[] = [
                'student_id' => $studentId,
                'full_name' => (string) $student['full_name'],
                'subjects' => $twoSubjects,
            ];
        }

        if ($values === []) {
            continue;
        }

        $min = min($values);

        if ($min === 5) {
            $excellent[] = [
                'student_id' => $studentId,
                'full_name' => (string) $student['full_name'],
            ];
        } elseif ($min >= 4) {
            $onlyGood[] = [
                'student_id' => $studentId,
                'full_name' => (string) $student['full_name'],
            ];
        }
    }

    $sortByName = static fn (array $a, array $b): int => strcasecmp($a['full_name'], $b['full_name']);
    usort($withTwos, $sortByName);
    usort($onlyGood, $sortByName);
    usort($excellent, $sortByName);

    return [
        'with_twos' => $withTwos,
        'only_good' => $onlyGood,
        'excellent' => $excellent,
    ];
}

function gradebook_percent_from_counts(int $numerator, int $denominator): float
{
    return $denominator > 0 ? round($numerator / $denominator * 100, 1) : 0.0;
}

function gradebook_specialty_key_from_group(array $group): string
{
    return 'id:' . (int) ($group['specialty_id'] ?? 0);
}

function gradebook_specialty_label_from_group(array $group): string
{
    $name = trim((string) ($group['specialty_name'] ?? ''));
    $code = trim((string) ($group['specialty_code'] ?? ''));
    if ($name === '') {
        return $code !== '' ? $code : '—';
    }

    return $code !== '' ? $name . ' (' . $code . ')' : $name;
}

function gradebook_specialty_key_from_archive_group(array $group): string
{
    $code = trim((string) ($group['specialty_code'] ?? ''));
    if ($code !== '') {
        return 'code:' . $code;
    }

    $name = trim((string) ($group['specialty_name'] ?? ''));

    return $name !== '' ? 'name:' . $name : 'group:' . (int) ($group['group_id'] ?? 0);
}

function gradebook_specialty_label_from_archive_group(array $group): string
{
    $name = trim((string) ($group['specialty_name'] ?? ''));
    $code = trim((string) ($group['specialty_code'] ?? ''));
    if ($name === '') {
        return $code !== '' ? $code : '—';
    }

    return $code !== '' ? $name . ' (' . $code . ')' : $name;
}

function merge_gradebook_summary_into_aggregates(
    array &$college,
    array &$specialties,
    string $specialtyKey,
    string $specialtyLabel,
    array $summary
): void {
    $assessed = (int) ($summary['assessed_students'] ?? 0);
    $absolute = (int) ($summary['absolute_count'] ?? 0);
    $quality = (int) ($summary['quality_count'] ?? 0);

    $college['assessed'] += $assessed;
    $college['absolute'] += $absolute;
    $college['quality'] += $quality;

    if (!isset($specialties[$specialtyKey])) {
        $specialties[$specialtyKey] = [
            'label' => $specialtyLabel,
            'assessed' => 0,
            'absolute' => 0,
            'quality' => 0,
        ];
    }

    $specialties[$specialtyKey]['assessed'] += $assessed;
    $specialties[$specialtyKey]['absolute'] += $absolute;
    $specialties[$specialtyKey]['quality'] += $quality;
}

function finalize_gradebook_aggregates(array $college, array $specialties): array
{
    $specialtyResult = [];
    foreach ($specialties as $key => $stat) {
        $assessed = (int) $stat['assessed'];
        $specialtyResult[$key] = [
            'label' => (string) $stat['label'],
            'assessed' => $assessed,
            'absolute_percent' => gradebook_percent_from_counts((int) $stat['absolute'], $assessed),
            'quality_percent' => gradebook_percent_from_counts((int) $stat['quality'], $assessed),
        ];
    }

    $collegeAssessed = (int) $college['assessed'];

    return [
        'college' => [
            'assessed' => $collegeAssessed,
            'absolute_percent' => gradebook_percent_from_counts((int) $college['absolute'], $collegeAssessed),
            'quality_percent' => gradebook_percent_from_counts((int) $college['quality'], $collegeAssessed),
        ],
        'specialties' => $specialtyResult,
    ];
}

function resolve_gradebook_year_period(string $academicYear): ?array
{
    require_once __DIR__ . '/archive.php';

    $academicYear = normalize_academic_year($academicYear) ?? '';
    if ($academicYear === '') {
        return null;
    }

    foreach (['2', '1'] as $semester) {
        $archive = get_archive_period('gradebook', $academicYear, $semester);
        if ($archive) {
            return [
                'academic_year' => $academicYear,
                'semester' => $semester,
                'archive_id' => (int) $archive['id'],
            ];
        }
    }

    $groups = get_all_groups();
    if ($groups === []) {
        return null;
    }

    foreach (['2', '1'] as $semester) {
        foreach ($groups as $group) {
            $subjects = get_group_curriculum_subjects((int) $group['id'], $academicYear, $semester);
            if ($subjects !== []) {
                return [
                    'academic_year' => $academicYear,
                    'semester' => $semester,
                    'archive_id' => null,
                ];
            }
        }
    }

    return null;
}

function aggregate_gradebook_college_and_specialties(string $academicYear, string $semester, ?int $archiveId = null): array
{
    $college = ['assessed' => 0, 'absolute' => 0, 'quality' => 0];
    $specialties = [];

    if ($archiveId) {
        require_once __DIR__ . '/archive.php';

        foreach (get_archive_gradebook_groups($archiveId) as $archiveGroup) {
            $groupId = (int) $archiveGroup['group_id'];
            $sheet = get_archive_gradebook_sheet($archiveId, $groupId);
            $students = [];
            foreach ($sheet['students'] as $student) {
                $students[] = [
                    'id' => (int) $student['student_id'],
                    'full_name' => (string) $student['full_name'],
                ];
            }
            $subjects = [];
            foreach ($sheet['subjects'] as $subject) {
                $subjects[] = [
                    'curriculum_item_id' => (int) $subject['curriculum_item_id'],
                    'subject_name' => (string) $subject['subject_name'],
                ];
            }
            $grades = [];
            foreach ($sheet['grades'] as $studentId => $items) {
                foreach ($items as $itemId => $grade) {
                    if ($grade !== null) {
                        $grades[(int) $studentId][(int) $itemId] = (int) $grade;
                    }
                }
            }

            $summary = build_gradebook_summary($students, $subjects, $grades);
            merge_gradebook_summary_into_aggregates(
                $college,
                $specialties,
                gradebook_specialty_key_from_archive_group($archiveGroup),
                gradebook_specialty_label_from_archive_group($archiveGroup),
                $summary
            );
        }
    } else {
        foreach (get_all_groups() as $group) {
            $overview = get_group_gradebook_overview((int) $group['id'], $academicYear, $semester);
            merge_gradebook_summary_into_aggregates(
                $college,
                $specialties,
                gradebook_specialty_key_from_group($group),
                gradebook_specialty_label_from_group($group),
                $overview['summary']
            );
        }
    }

    return finalize_gradebook_aggregates($college, $specialties);
}

function build_gradebook_three_years_comparison(string $baseYear): array
{
    require_once __DIR__ . '/attendance.php';

    $years = get_last_academic_years($baseYear, 3);
    $yearLabels = [];
    $yearAggregates = [];

    foreach ($years as $year) {
        $period = resolve_gradebook_year_period($year);
        if ($period === null) {
            $yearLabels[] = $year;
            $yearAggregates[] = null;
            continue;
        }

        $yearLabels[] = $year . ' · ' . semester_label($period['semester']);
        $yearAggregates[] = aggregate_gradebook_college_and_specialties(
            $period['academic_year'],
            $period['semester'],
            $period['archive_id']
        );
    }

    $specialtyLabels = [];
    foreach ($yearAggregates as $aggregate) {
        if ($aggregate === null) {
            continue;
        }
        foreach ($aggregate['specialties'] as $key => $stat) {
            if (!isset($specialtyLabels[$key])) {
                $specialtyLabels[$key] = (string) $stat['label'];
            }
        }
    }
    asort($specialtyLabels, SORT_STRING | SORT_FLAG_CASE);

    $specialtyKeys = array_keys($specialtyLabels);
    $specialtyAbsolute = [];
    $specialtyQuality = [];
    foreach ($specialtyKeys as $key) {
        $absoluteRow = [];
        $qualityRow = [];
        foreach ($yearAggregates as $aggregate) {
            if ($aggregate === null || !isset($aggregate['specialties'][$key])) {
                $absoluteRow[] = 0.0;
                $qualityRow[] = 0.0;
                continue;
            }
            $absoluteRow[] = (float) $aggregate['specialties'][$key]['absolute_percent'];
            $qualityRow[] = (float) $aggregate['specialties'][$key]['quality_percent'];
        }
        $specialtyAbsolute[] = $absoluteRow;
        $specialtyQuality[] = $qualityRow;
    }

    $collegeAbsolute = [];
    $collegeQuality = [];
    foreach ($yearAggregates as $aggregate) {
        if ($aggregate === null) {
            $collegeAbsolute[] = 0.0;
            $collegeQuality[] = 0.0;
            continue;
        }
        $collegeAbsolute[] = (float) $aggregate['college']['absolute_percent'];
        $collegeQuality[] = (float) $aggregate['college']['quality_percent'];
    }

    return [
        'years' => $years,
        'year_labels' => $yearLabels,
        'specialties' => array_map(
            static fn (string $key): array => ['key' => $key, 'label' => $specialtyLabels[$key]],
            $specialtyKeys
        ),
        'specialty_absolute' => $specialtyAbsolute,
        'specialty_quality' => $specialtyQuality,
        'college_absolute' => $collegeAbsolute,
        'college_quality' => $collegeQuality,
    ];
}
