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

/** Подсказка, если период ведомости отличается от текущего учебного года. */
function gradebook_period_mismatch_hint(?array $period = null): ?string
{
    $period = $period ?? get_active_gradebook_period();
    $defaultYear = get_default_academic_year();
    if ($period['academic_year'] === $defaultYear) {
        return null;
    }

    return 'Период ведомости: ' . $period['academic_year'] . ' · '
        . semester_label($period['semester'])
        . '. Текущий учебный год: ' . $defaultYear
        . '. Предметы берутся из учебного плана периода ведомости (не из текущего года). '
        . 'Сменить период можно в разделе «Информация» администратора.';
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

/** Включён ли временный модуль «Ручное БРС» (отдельный контур ведомостей). */
function is_manual_brs_gradebook_active(): bool
{
    return is_file(__DIR__ . '/../modules/manual_brs/bootstrap.php');
}

/**
 * Оценки по источнику: journal (электронный журнал) или manual_brs (отдельный контур).
 *
 * @return array<int, array<int, int>> student_id => curriculum_item_id => grade 2–5
 */
function get_gradebook_grades_by_source(
    int $groupId,
    string $academicYear,
    string $semester,
    string $source = 'journal'
): array {
    if ($source === 'manual_brs' && is_manual_brs_gradebook_active()) {
        require_once __DIR__ . '/../modules/manual_brs/bootstrap.php';
        require_once __DIR__ . '/../modules/manual_brs/includes/gradebook.php';
        manual_brs_ensure_schema();

        return manual_brs_get_gradebook_grades($groupId, $academicYear, $semester);
    }

    return get_gradebook_grades_from_journal($groupId, $academicYear, $semester);
}

/**
 * Оценки стандартного контура (электронный журнал).
 *
 * @return array<int, array<int, int>> student_id => curriculum_item_id => grade 2–5
 */
function get_gradebook_grades_for_period(int $groupId, string $academicYear, string $semester): array
{
    return get_gradebook_grades_from_journal($groupId, $academicYear, $semester);
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

    require_once __DIR__ . '/curriculum_composites.php';

    foreach ($subjects as $subject) {
        $itemId = (int) $subject['curriculum_item_id'];
        $hasChildren = curriculum_item_has_children($itemId, $semester);

        if ($hasChildren) {
            foreach ($students as $student) {
                $studentId = (int) $student['id'];
                $grade = resolve_composite_parent_grade_from_journal($itemId, $studentId, $semester);
                if ($grade !== null) {
                    $result[$studentId][$itemId] = $grade;
                }
            }
            continue;
        }

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

        $grade = (int) round((float) $total['grade']);

        return in_array($grade, GRADE_VALUES, true) ? $grade : null;
    }

    if ($total['average'] === null && ($total['grade'] === null || $total['grade'] === '')) {
        return null;
    }

    $value = $total['average'] ?? $total['grade'];

    return max(2, min(5, (int) round((float) $value)));
}

/**
 * Статус заполнения ведомости группы за период.
 * @return array{complete: bool, empty: bool, filled: int, expected: int, students: int, subjects: int, source?: string}
 */
function get_gradebook_completion_status(
    int $groupId,
    string $academicYear,
    string $semester,
    string $source = 'journal'
): array {
    $students = get_students_by_group($groupId);
    $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
    $studentCount = count($students);
    $subjectCount = count($subjects);
    $expected = $studentCount * $subjectCount;
    $source = $source === 'manual_brs' ? 'manual_brs' : 'journal';

    if ($expected === 0) {
        return [
            'complete' => false,
            'empty' => true,
            'filled' => 0,
            'expected' => 0,
            'students' => $studentCount,
            'subjects' => $subjectCount,
            'source' => $source,
        ];
    }

    $grades = get_gradebook_grades_by_source($groupId, $academicYear, $semester, $source);
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
        'source' => $source,
    ];
}

/**
 * Сводка ведомости группы: заполненность, успеваемость, списки.
 * $source: journal | manual_brs
 */
function get_group_gradebook_overview(
    int $groupId,
    string $academicYear,
    string $semester,
    string $source = 'journal'
): array {
    $source = $source === 'manual_brs' ? 'manual_brs' : 'journal';
    $students = get_students_by_group($groupId);
    $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
    $studentCount = count($students);
    $subjectCount = count($subjects);
    $expected = $studentCount * $subjectCount;
    $manualBrsBatch = null;
    $grades = [];

    if ($expected > 0) {
        if ($source === 'manual_brs' && is_manual_brs_gradebook_active()) {
            require_once __DIR__ . '/../modules/manual_brs/bootstrap.php';
            require_once __DIR__ . '/../modules/manual_brs/includes/data.php';
            require_once __DIR__ . '/../modules/manual_brs/includes/gradebook.php';
            manual_brs_ensure_schema();
            $manualBrsBatch = manual_brs_build_grade_batch_context($academicYear, $semester, $subjects);
            $grades = manual_brs_get_gradebook_grades(
                $groupId,
                $academicYear,
                $semester,
                $manualBrsBatch
            );
        } else {
            $grades = get_gradebook_grades_by_source($groupId, $academicYear, $semester, $source);
        }
    }

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
    if ($source === 'manual_brs' && is_manual_brs_gradebook_active()) {
        require_once __DIR__ . '/../modules/manual_brs/includes/gradebook.php';
        $lists = manual_brs_build_gradebook_student_lists(
            $groupId,
            $academicYear,
            $semester,
            $students,
            $subjects,
            $grades,
            $manualBrsBatch
        );
    } else {
        $lists = build_gradebook_student_lists($students, $subjects, $grades);
    }

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
        'lists' => $lists,
        'source' => $source,
    ];
}

/** Объединение предметов ведомости без дублей (важно для semester=both). */
function merge_gradebook_subjects(array ...$subjectLists): array
{
    $merged = [];
    foreach ($subjectLists as $subjects) {
        foreach ($subjects as $subject) {
            $itemId = (int) ($subject['curriculum_item_id'] ?? 0);
            if ($itemId <= 0 || isset($merged[$itemId])) {
                continue;
            }
            $merged[$itemId] = $subject;
        }
    }

    return array_values($merged);
}

/** Объединение карт оценок student_id → item_id → grade. */
function merge_gradebook_grades(array ...$gradeMaps): array
{
    $merged = [];
    foreach ($gradeMaps as $grades) {
        foreach ($grades as $studentId => $items) {
            $studentId = (int) $studentId;
            foreach ($items as $itemId => $grade) {
                if ($grade === null || $grade === '') {
                    continue;
                }
                $merged[$studentId][(int) $itemId] = (int) $grade;
            }
        }
    }

    return $merged;
}

/**
 * Сводка ведомости группы за весь учебный год (1 + 2 семестр).
 * Предметы semester=both учитываются один раз.
 */
function get_group_gradebook_year_overview(
    int $groupId,
    string $academicYear,
    string $source = 'journal'
): array {
    $source = $source === 'manual_brs' ? 'manual_brs' : 'journal';
    $students = get_students_by_group($groupId);
    $subjects1 = get_group_curriculum_subjects($groupId, $academicYear, '1');
    $subjects2 = get_group_curriculum_subjects($groupId, $academicYear, '2');
    $subjects = merge_gradebook_subjects($subjects1, $subjects2);
    $studentCount = count($students);
    $subjectCount = count($subjects);
    $expected = $studentCount * $subjectCount;
    $grades = [];

    if ($expected > 0) {
        $grades1 = get_gradebook_grades_by_source($groupId, $academicYear, '1', $source);
        $grades2 = get_gradebook_grades_by_source($groupId, $academicYear, '2', $source);
        $grades = merge_gradebook_grades($grades1, $grades2);
    }

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
        'lists' => $lists,
        'source' => $source,
        'period' => 'year',
    ];
}

/** Пустые агрегаты колледжа для сводной ведомости. */
function empty_college_gradebook_totals(): array
{
    return [
        'students' => 0,
        'assessed' => 0,
        'absolute' => 0,
        'quality' => 0,
        'with_twos' => 0,
        'only_good' => 0,
        'excellent' => 0,
        'complete' => 0,
        'incomplete' => 0,
        'empty' => 0,
        'one_two' => 0,
        'one_three' => 0,
        'grade_counts' => [2 => 0, 3 => 0, 4 => 0, 5 => 0],
        'grade_sum' => 0,
        'filled_grades' => 0,
    ];
}

/** Производные KPI колледжа из агрегатов. */
function derive_college_gradebook_metrics(array $totals): array
{
    $assessed = (int) ($totals['assessed'] ?? 0);
    $students = (int) ($totals['students'] ?? 0);
    $filled = (int) ($totals['filled_grades'] ?? 0);
    $gradeCounts = $totals['grade_counts'] ?? [2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $satisfactoryOnly = max(
        0,
        $assessed
        - (int) ($totals['with_twos'] ?? 0)
        - (int) ($totals['only_good'] ?? 0)
        - (int) ($totals['excellent'] ?? 0)
    );

    $absolutePercent = $assessed > 0
        ? round((int) $totals['absolute'] / $assessed * 100, 1)
        : 0.0;
    $qualityPercent = $assessed > 0
        ? round((int) $totals['quality'] / $assessed * 100, 1)
        : 0.0;
    $averageGrade = $filled > 0
        ? round((int) $totals['grade_sum'] / $filled, 2)
        : 0.0;
    $souPercent = $filled > 0
        ? round(
            (
                (int) ($gradeCounts[5] ?? 0) * 100
                + (int) ($gradeCounts[4] ?? 0) * 64
                + (int) ($gradeCounts[3] ?? 0) * 36
                + (int) ($gradeCounts[2] ?? 0) * 16
            ) / $filled,
            1
        )
        : 0.0;

    return [
        'absolute_percent' => $absolutePercent,
        'quality_percent' => $qualityPercent,
        'assessed_percent' => $students > 0 ? round($assessed / $students * 100, 1) : 0.0,
        'not_assessed' => max(0, $students - $assessed),
        'satisfactory_only' => $satisfactoryOnly,
        'twos_percent' => $assessed > 0
            ? round((int) $totals['with_twos'] / $assessed * 100, 1)
            : 0.0,
        'excellent_percent' => $assessed > 0
            ? round((int) $totals['excellent'] / $assessed * 100, 1)
            : 0.0,
        'only_good_percent' => $assessed > 0
            ? round((int) $totals['only_good'] / $assessed * 100, 1)
            : 0.0,
        'average_grade' => $averageGrade,
        'sou_percent' => $souPercent,
        'one_two_percent' => $students > 0
            ? round((int) $totals['one_two'] / $students * 100, 1)
            : 0.0,
        'one_three_percent' => $students > 0
            ? round((int) $totals['one_three'] / $students * 100, 1)
            : 0.0,
        'no_debt_percent' => $assessed > 0
            ? round(($assessed - (int) $totals['with_twos']) / $assessed * 100, 1)
            : 0.0,
        'student_composition_labels' => ['Отличники', 'Только 4–5', 'Удовл. (есть 3)', 'Неуспевающие («2»)'],
        'student_composition_values' => [
            (int) ($totals['excellent'] ?? 0),
            (int) ($totals['only_good'] ?? 0),
            $satisfactoryOnly,
            (int) ($totals['with_twos'] ?? 0),
        ],
        'grade_distribution_labels' => ['«5»', '«4»', '«3»', '«2»'],
        'grade_distribution_values' => [
            (int) ($gradeCounts[5] ?? 0),
            (int) ($gradeCounts[4] ?? 0),
            (int) ($gradeCounts[3] ?? 0),
            (int) ($gradeCounts[2] ?? 0),
        ],
    ];
}

/**
 * Полный отчёт сводной ведомости колледжа за семестр или весь год.
 * $semester: 1 | 2 | year
 * @return array{rows:array,totals:array,metrics:array,specialty_stats:array,charts:array}
 */
function build_college_gradebook_report(
    string $academicYear,
    string $semester,
    string $source = 'journal',
    ?array $groups = null
): array {
    $source = $source === 'manual_brs' ? 'manual_brs' : 'journal';
    $isYear = $semester === 'year' || $semester === 'both';
    $semester = $isYear ? 'year' : normalize_gradebook_semester($semester);
    $groups = $groups ?? get_all_groups();
    $rows = [];
    $totals = empty_college_gradebook_totals();

    foreach ($groups as $group) {
        $overview = $isYear
            ? get_group_gradebook_year_overview((int) $group['id'], $academicYear, $source)
            : get_group_gradebook_overview((int) $group['id'], $academicYear, $semester, $source);
        $summary = $overview['summary'];
        $assessed = (int) $summary['assessed_students'];

        $rows[] = [
            'group' => $group,
            'overview' => $overview,
        ];

        $totals['students'] += (int) $overview['students'];
        $totals['assessed'] += $assessed;
        $totals['absolute'] += (int) ($summary['absolute_count'] ?? 0);
        $totals['quality'] += (int) ($summary['quality_count'] ?? 0);
        $totals['with_twos'] += (int) $overview['with_twos'];
        $totals['only_good'] += (int) $overview['only_good'];
        $totals['excellent'] += (int) $overview['excellent'];
        $totals['one_two'] += (int) ($summary['one_two_count'] ?? 0);
        $totals['one_three'] += (int) ($summary['one_three_count'] ?? 0);
        $totals['filled_grades'] += (int) ($summary['filled_grades'] ?? 0);
        $gradeCounts = $summary['grade_counts'] ?? [2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ([2, 3, 4, 5] as $g) {
            $cnt = (int) ($gradeCounts[$g] ?? 0);
            $totals['grade_counts'][$g] += $cnt;
            $totals['grade_sum'] += $g * $cnt;
        }

        if (!empty($overview['empty'])) {
            $totals['empty']++;
        } elseif (!empty($overview['complete'])) {
            $totals['complete']++;
        } else {
            $totals['incomplete']++;
        }
    }

    $specialtyStats = [];
    foreach ($rows as $row) {
        $group = $row['group'];
        $key = gradebook_specialty_key_from_group($group);
        $summary = $row['overview']['summary'];
        if (!isset($specialtyStats[$key])) {
            $specialtyStats[$key] = [
                'label' => gradebook_specialty_label_from_group($group),
                'assessed' => 0,
                'absolute' => 0,
                'quality' => 0,
                'students' => 0,
                'groups' => 0,
            ];
        }
        $specialtyStats[$key]['assessed'] += (int) ($summary['assessed_students'] ?? 0);
        $specialtyStats[$key]['absolute'] += (int) ($summary['absolute_count'] ?? 0);
        $specialtyStats[$key]['quality'] += (int) ($summary['quality_count'] ?? 0);
        $specialtyStats[$key]['students'] += (int) ($row['overview']['students'] ?? 0);
        $specialtyStats[$key]['groups']++;
    }

    uasort($specialtyStats, static function (array $a, array $b): int {
        $gen = gradebook_general_education_label();
        if ($a['label'] === $gen && $b['label'] !== $gen) {
            return 1;
        }
        if ($b['label'] === $gen && $a['label'] !== $gen) {
            return -1;
        }

        return strcmp($a['label'], $b['label']);
    });

    $groupLabels = [];
    $groupAbsolute = [];
    $groupQuality = [];
    foreach ($rows as $row) {
        $groupLabels[] = (string) $row['group']['number'];
        $groupAbsolute[] = (float) ($row['overview']['summary']['absolute_percent'] ?? 0);
        $groupQuality[] = (float) ($row['overview']['summary']['quality_percent'] ?? 0);
    }

    $specialtyLabels = [];
    $specialtyAbsolute = [];
    $specialtyQuality = [];
    $specialtyStudentsLabels = [];
    $specialtyStudentsValues = [];
    foreach ($specialtyStats as $stat) {
        $specialtyLabels[] = (string) $stat['label'];
        $assessed = (int) $stat['assessed'];
        $specialtyAbsolute[] = $assessed > 0
            ? round($stat['absolute'] / $assessed * 100, 1)
            : 0.0;
        $specialtyQuality[] = $assessed > 0
            ? round($stat['quality'] / $assessed * 100, 1)
            : 0.0;
        $specialtyStudentsLabels[] = (string) $stat['label'];
        $specialtyStudentsValues[] = (int) $stat['students'];
    }

    $metrics = derive_college_gradebook_metrics($totals);

    return [
        'academic_year' => $academicYear,
        'semester' => $semester,
        'source' => $source,
        'rows' => $rows,
        'totals' => $totals,
        'metrics' => $metrics,
        'specialty_stats' => $specialtyStats,
        'charts' => [
            'group_labels' => $groupLabels,
            'group_absolute' => $groupAbsolute,
            'group_quality' => $groupQuality,
            'specialty_labels' => $specialtyLabels,
            'specialty_absolute' => $specialtyAbsolute,
            'specialty_quality' => $specialtyQuality,
            'specialty_students_labels' => $specialtyStudentsLabels,
            'specialty_students_values' => $specialtyStudentsValues,
        ],
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
    $subjectCount = count($subjects);
    $expectedGrades = $totalStudents * $subjectCount;

    $emptyMetrics = [
        'grade_counts' => [2 => 0, 3 => 0, 4 => 0, 5 => 0],
        'average_grade' => 0.0,
        'sou_percent' => 0.0,
    ];

    if ($totalStudents === 0 || $subjects === []) {
        return array_merge([
            'total_students' => $totalStudents,
            'assessed_students' => 0,
            'filled_grades' => 0,
            'expected_grades' => $expectedGrades,
            'absolute_count' => 0,
            'quality_count' => 0,
            'absolute_percent' => 0.0,
            'quality_percent' => 0.0,
            'one_two_count' => 0,
            'one_three_count' => 0,
            'one_two_percent' => 0.0,
            'one_three_percent' => 0.0,
        ], $emptyMetrics);
    }

    $assessedStudents = 0;
    $filledGrades = 0;
    $absoluteCount = 0;
    $qualityCount = 0;
    $oneTwoCount = 0;
    $oneThreeCount = 0;
    $gradeCounts = [2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $gradeSum = 0;

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
                $filledGrades++;
                $gradeSum += $grade;
                if (isset($gradeCounts[$grade])) {
                    $gradeCounts[$grade]++;
                }
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

    $averageGrade = $filledGrades > 0 ? round($gradeSum / $filledGrades, 2) : 0.0;
    // Степень обученности (СОУ) по всем выставленным оценкам.
    $souPercent = $filledGrades > 0
        ? round(
            (
                $gradeCounts[5] * 100
                + $gradeCounts[4] * 64
                + $gradeCounts[3] * 36
                + $gradeCounts[2] * 16
            ) / $filledGrades,
            1
        )
        : 0.0;

    if ($assessedStudents === 0) {
        return [
            'total_students' => $totalStudents,
            'assessed_students' => 0,
            'filled_grades' => $filledGrades,
            'expected_grades' => $expectedGrades,
            'absolute_count' => 0,
            'quality_count' => 0,
            'absolute_percent' => 0.0,
            'quality_percent' => 0.0,
            'one_two_count' => $oneTwoCount,
            'one_three_count' => $oneThreeCount,
            'one_two_percent' => round($oneTwoCount / $totalStudents * 100, 1),
            'one_three_percent' => round($oneThreeCount / $totalStudents * 100, 1),
            'grade_counts' => $gradeCounts,
            'average_grade' => $averageGrade,
            'sou_percent' => $souPercent,
        ];
    }

    return [
        'total_students' => $totalStudents,
        'assessed_students' => $assessedStudents,
        'filled_grades' => $filledGrades,
        'expected_grades' => $expectedGrades,
        'absolute_count' => $absoluteCount,
        'quality_count' => $qualityCount,
        'absolute_percent' => round($absoluteCount / $assessedStudents * 100, 1),
        'quality_percent' => round($qualityCount / $assessedStudents * 100, 1),
        'one_two_count' => $oneTwoCount,
        'one_three_count' => $oneThreeCount,
        'one_two_percent' => round($oneTwoCount / $totalStudents * 100, 1),
        'one_three_percent' => round($oneThreeCount / $totalStudents * 100, 1),
        'grade_counts' => $gradeCounts,
        'average_grade' => $averageGrade,
        'sou_percent' => $souPercent,
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

function gradebook_general_education_key(): string
{
    return 'general_education';
}

function gradebook_general_education_label(): string
{
    return 'Общеобразовательный цикл';
}

function gradebook_group_is_general_education(array $group): bool
{
    return !empty($group['is_general_education']);
}

function gradebook_archive_group_is_general_education(array $group): bool
{
    if (array_key_exists('is_general_education', $group)) {
        return !empty($group['is_general_education']);
    }

    $groupId = (int) ($group['group_id'] ?? 0);
    if ($groupId <= 0) {
        return false;
    }

    require_once __DIR__ . '/organization.php';
    $live = get_group_by_id($groupId);

    return $live !== null && !empty($live['is_general_education']);
}

function gradebook_specialty_key_from_group(array $group): string
{
    if (gradebook_group_is_general_education($group)) {
        return gradebook_general_education_key();
    }

    return 'id:' . (int) ($group['specialty_id'] ?? 0);
}

function gradebook_specialty_label_from_group(array $group): string
{
    if (gradebook_group_is_general_education($group)) {
        return gradebook_general_education_label();
    }

    $name = trim((string) ($group['specialty_name'] ?? ''));
    $code = trim((string) ($group['specialty_code'] ?? ''));
    if ($name === '') {
        return $code !== '' ? $code : '—';
    }

    return $code !== '' ? $name . ' (' . $code . ')' : $name;
}

function gradebook_specialty_key_from_archive_group(array $group): string
{
    if (gradebook_archive_group_is_general_education($group)) {
        return gradebook_general_education_key();
    }

    $code = trim((string) ($group['specialty_code'] ?? ''));
    if ($code !== '') {
        return 'code:' . $code;
    }

    $name = trim((string) ($group['specialty_name'] ?? ''));

    return $name !== '' ? 'name:' . $name : 'group:' . (int) ($group['group_id'] ?? 0);
}

function gradebook_specialty_label_from_archive_group(array $group): string
{
    if (gradebook_archive_group_is_general_education($group)) {
        return gradebook_general_education_label();
    }

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

/**
 * Какой период брать для сравнения лет.
 * $preferSemester: 1 | 2 | year | '' (авто: сначала 2, потом 1).
 */
function resolve_gradebook_year_period(
    string $academicYear,
    string $source = 'journal',
    string $preferSemester = ''
): ?array {
    require_once __DIR__ . '/archive.php';

    $academicYear = normalize_academic_year($academicYear) ?? '';
    if ($academicYear === '') {
        return null;
    }

    $source = $source === 'manual_brs' ? 'manual_brs' : 'journal';
    if ($preferSemester === 'year' || $preferSemester === 'both') {
        return [
            'academic_year' => $academicYear,
            'semester' => 'year',
            'archive_id' => null,
        ];
    }

    $semesterOrder = in_array($preferSemester, ['1', '2'], true)
        ? [$preferSemester]
        : ['2', '1'];

    // Архив — только стандартный контур; ручное БРС всегда из живых данных.
    if ($source === 'journal') {
        foreach ($semesterOrder as $semester) {
            $archive = get_archive_period('gradebook', $academicYear, $semester);
            if ($archive) {
                return [
                    'academic_year' => $academicYear,
                    'semester' => $semester,
                    'archive_id' => (int) $archive['id'],
                ];
            }
        }
    }

    $groups = get_all_groups();
    if ($groups === []) {
        return null;
    }

    foreach ($semesterOrder as $semester) {
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

function aggregate_gradebook_college_and_specialties(
    string $academicYear,
    string $semester,
    ?int $archiveId = null,
    string $source = 'journal'
): array {
    $college = ['assessed' => 0, 'absolute' => 0, 'quality' => 0];
    $specialties = [];
    $source = $source === 'manual_brs' ? 'manual_brs' : 'journal';

    if ($archiveId) {
        // Архив только у стандартного контура; для ручного БРС прошлые годы без снапшота.
        if ($source === 'manual_brs') {
            return finalize_gradebook_aggregates($college, $specialties);
        }

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
            $overview = get_group_gradebook_overview(
                (int) $group['id'],
                $academicYear,
                $semester,
                $source
            );
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

/**
 * Сравнение успеваемости за 3 учебных года.
 * $periodMode: 1 | 2 | year | '' (автовыбор семестра).
 */
function build_gradebook_three_years_comparison(
    string $baseYear,
    string $source = 'journal',
    string $periodMode = ''
): array {
    require_once __DIR__ . '/attendance.php';

    $source = $source === 'manual_brs' ? 'manual_brs' : 'journal';
    if ($periodMode === 'both') {
        $periodMode = 'year';
    }
    if (!in_array($periodMode, ['1', '2', 'year', ''], true)) {
        $periodMode = '';
    }

    $years = get_last_academic_years($baseYear, 3);
    $yearLabels = [];
    $yearAggregates = [];

    foreach ($years as $year) {
        if ($periodMode === 'year') {
            $report = build_college_gradebook_report($year, 'year', $source);
            $yearLabels[] = $year . ' · оба семестра';
            $specialtyAgg = [];
            foreach ($report['specialty_stats'] as $key => $stat) {
                $assessed = (int) $stat['assessed'];
                $specialtyAgg[$key] = [
                    'label' => (string) $stat['label'],
                    'assessed' => $assessed,
                    'absolute_percent' => gradebook_percent_from_counts((int) $stat['absolute'], $assessed),
                    'quality_percent' => gradebook_percent_from_counts((int) $stat['quality'], $assessed),
                ];
            }
            $yearAggregates[] = [
                'college' => [
                    'assessed' => (int) $report['totals']['assessed'],
                    'absolute_percent' => (float) $report['metrics']['absolute_percent'],
                    'quality_percent' => (float) $report['metrics']['quality_percent'],
                ],
                'specialties' => $specialtyAgg,
            ];
            continue;
        }

        $period = resolve_gradebook_year_period($year, $source, $periodMode);
        if ($period === null) {
            $yearLabels[] = $year;
            $yearAggregates[] = null;
            continue;
        }

        $yearLabels[] = $year . ' · ' . semester_label($period['semester']);
        $yearAggregates[] = aggregate_gradebook_college_and_specialties(
            $period['academic_year'],
            $period['semester'],
            $period['archive_id'],
            $source
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
    $genLabel = gradebook_general_education_label();
    uasort($specialtyLabels, static function (string $a, string $b) use ($genLabel): int {
        if ($a === $genLabel && $b !== $genLabel) {
            return 1;
        }
        if ($b === $genLabel && $a !== $genLabel) {
            return -1;
        }

        return strcasecmp($a, $b);
    });

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
