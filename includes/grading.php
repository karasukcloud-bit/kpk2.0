<?php

declare(strict_types=1);

require_once __DIR__ . '/gradebook.php';

function default_grading_config(): array
{
    return [
        'system' => 'traditional',
        'brs' => [
            'weight_current' => 30.0,
            'weight_control' => 45.0,
            'weight_attendance' => 10.0,
            'weight_punctuality' => 5.0,
            'weight_activity' => 5.0,
            'scale_3' => 50.0,
            'scale_4' => 65.0,
            'scale_5' => 75.0,
        ],
    ];
}

function get_grading_config(): array
{
    ensure_app_settings_value_column();

    $defaults = default_grading_config();
    $raw = get_app_setting('grading_config');
    if ($raw === null || $raw === '') {
        return $defaults;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return $defaults;
    }

    $system = (($decoded['system'] ?? '') === 'brs') ? 'brs' : 'traditional';
    $brs = is_array($decoded['brs'] ?? null) ? $decoded['brs'] : [];
    $defaultsBrs = $defaults['brs'];

    return [
        'system' => $system,
        'brs' => [
            'weight_current' => normalize_grading_weight($brs['weight_current'] ?? $defaultsBrs['weight_current']),
            'weight_control' => normalize_grading_weight($brs['weight_control'] ?? $defaultsBrs['weight_control']),
            'weight_attendance' => normalize_grading_weight($brs['weight_attendance'] ?? $defaultsBrs['weight_attendance']),
            'weight_punctuality' => normalize_grading_weight($brs['weight_punctuality'] ?? $defaultsBrs['weight_punctuality']),
            'weight_activity' => normalize_grading_weight($brs['weight_activity'] ?? $defaultsBrs['weight_activity']),
            'scale_3' => normalize_grading_scale($brs['scale_3'] ?? $defaultsBrs['scale_3']),
            'scale_4' => normalize_grading_scale($brs['scale_4'] ?? $defaultsBrs['scale_4']),
            'scale_5' => normalize_grading_scale($brs['scale_5'] ?? $defaultsBrs['scale_5']),
        ],
    ];
}

function is_brs_grading(): bool
{
    return get_grading_config()['system'] === 'brs';
}

function normalize_grading_weight($value): float
{
    $value = round((float) $value, 2);
    if ($value < 0) {
        return 0.0;
    }

    return min(100.0, $value);
}

function normalize_grading_scale($value): float
{
    $value = round((float) $value, 2);
    if ($value < 0) {
        return 0.0;
    }

    return min(100.0, $value);
}

function save_grading_config(array $input): array
{
    ensure_app_settings_value_column();

    $system = (($input['system'] ?? '') === 'brs') ? 'brs' : 'traditional';

    $brs = [
        'weight_current' => normalize_grading_weight($input['weight_current'] ?? 30),
        'weight_control' => normalize_grading_weight($input['weight_control'] ?? 45),
        'weight_attendance' => normalize_grading_weight($input['weight_attendance'] ?? 10),
        'weight_punctuality' => normalize_grading_weight($input['weight_punctuality'] ?? 5),
        'weight_activity' => normalize_grading_weight($input['weight_activity'] ?? 5),
        'scale_3' => normalize_grading_scale($input['scale_3'] ?? 50),
        'scale_4' => normalize_grading_scale($input['scale_4'] ?? 65),
        'scale_5' => normalize_grading_scale($input['scale_5'] ?? 75),
    ];

    if (!($brs['scale_3'] < $brs['scale_4'] && $brs['scale_4'] < $brs['scale_5'])) {
        return [
            'success' => false,
            'error' => 'Пороги шкалы должны возрастать: «3» < «4» < «5».',
        ];
    }

    $config = [
        'system' => $system,
        'brs' => $brs,
    ];

    set_app_setting('grading_config', json_encode($config, JSON_UNESCAPED_UNICODE));

    return ['success' => true, 'config' => $config];
}

function brs_points_to_grade(float $points, array $brs): int
{
    $points = max(0.0, min(100.0, $points));

    if ($points >= (float) $brs['scale_5']) {
        return 5;
    }
    if ($points >= (float) $brs['scale_4']) {
        return 4;
    }
    if ($points >= (float) $brs['scale_3']) {
        return 3;
    }

    return 2;
}

function format_grading_number(float $value, int $decimals = 1): string
{
    $formatted = number_format($value, $decimals, '.', '');
    if (strpos($formatted, '.') !== false) {
        $formatted = rtrim(rtrim($formatted, '0'), '.');
    }

    return $formatted === '' ? '0' : $formatted;
}

function calculate_traditional_total(array $lessons, array $studentGrades): array
{
    $values = [];

    foreach ($lessons as $lesson) {
        $lessonId = (int) $lesson['id'];
        $entry = $studentGrades[$lessonId] ?? null;
        if ($entry === null) {
            continue;
        }

        $mark = (string) ($entry['mark'] ?? '');
        if ($mark === '' || $mark === 'Н') {
            continue;
        }

        $values[] = (float) $mark;
    }

    if ($values === []) {
        return [
            'display' => '',
            'average' => null,
            'points' => null,
            'grade' => null,
        ];
    }

    $average = round(array_sum($values) / count($values), 1);

    return [
        'display' => format_grading_number($average, 1),
        'average' => $average,
        'points' => null,
        'grade' => null,
    ];
}

function calculate_brs_total(array $lessons, array $studentGrades, array $brs): array
{
    $totalLessons = count($lessons);
    if ($totalLessons === 0) {
        return [
            'display' => '',
            'average' => null,
            'points' => null,
            'grade' => null,
        ];
    }

    $currentSum = 0.0;
    $currentCount = 0;
    $controlSum = 0.0;
    $controlCount = 0;
    $controlLessonCount = 0;
    $withoutAbsent = 0;
    $withoutLate = 0;
    $withActivity = 0;

    foreach ($lessons as $lesson) {
        $lessonId = (int) $lesson['id'];
        $gradeType = (string) ($lesson['grade_type'] ?? 'current');
        if ($gradeType === 'control') {
            $controlLessonCount++;
        }

        $entry = $studentGrades[$lessonId] ?? ['mark' => '', 'activity' => false, 'late' => false];
        $mark = (string) ($entry['mark'] ?? '');
        $isAbsent = ($mark === 'Н');

        if (!$isAbsent) {
            $withoutAbsent++;
            if (empty($entry['late'])) {
                $withoutLate++;
            }
            if (!empty($entry['activity'])) {
                $withActivity++;
            }
        }

        if ($mark !== '' && $mark !== 'Н') {
            $value = (float) $mark;
            if ($gradeType === 'control') {
                $controlSum += $value;
                $controlCount++;
            } else {
                $currentSum += $value;
                $currentCount++;
            }
        }
    }

    $points = 0.0;

    if ($currentCount > 0) {
        $points += (($currentSum / $currentCount) / 5.0) * (float) $brs['weight_current'];
    }

    if ($controlCount > 0) {
        $points += (($controlSum / $controlCount) / 5.0) * (float) $brs['weight_control'];
    } elseif ($controlLessonCount === 0 && $currentCount > 0) {
        // Нет контрольных в журнале — блок контрольных считаем по средней текущих.
        $points += (($currentSum / $currentCount) / 5.0) * (float) $brs['weight_control'];
    }

    // Формула: (вес / число уроков) * уроки без Н
    $points += ((float) $brs['weight_attendance'] / $totalLessons) * $withoutAbsent;

    if ($withoutAbsent > 0) {
        $points += ((float) $brs['weight_punctuality'] / $withoutAbsent) * $withoutLate;
        $points += ((float) $brs['weight_activity'] / $withoutAbsent) * $withActivity;
    }

    $points = round(max(0.0, min(100.0, $points)), 1);
    $grade = brs_points_to_grade($points, $brs);

    return [
        'display' => format_grading_number($points, 1) . ' → ' . $grade,
        'average' => null,
        'points' => $points,
        'grade' => $grade,
    ];
}

function build_journal_totals(array $students, array $lessons, array $grades, ?array $config = null): array
{
    $config = $config ?? get_grading_config();
    $totals = [];

    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $studentGrades = $grades[$studentId] ?? [];

        if ($config['system'] === 'brs') {
            $result = calculate_brs_total($lessons, $studentGrades, $config['brs']);
        } else {
            $result = calculate_traditional_total($lessons, $studentGrades);
        }

        $totals[$studentId] = [
            'system' => $config['system'],
            'grade' => $result['grade'],
            'points' => $result['points'],
            'average' => $result['average'],
            'display' => $result['display'],
            'html' => render_journal_total_html($config['system'], $result),
        ];
    }

    return $totals;
}

function render_journal_total_html(string $system, array $result): string
{
    if ($system === 'brs') {
        if ($result['grade'] === null || $result['points'] === null) {
            return '';
        }

        $grade = (int) $result['grade'];
        $points = htmlspecialchars(format_grading_number((float) $result['points'], 1), ENT_QUOTES, 'UTF-8');

        return '<span class="journal-total">'
            . '<span class="journal-total__grade journal-total__grade--' . $grade . '">' . $grade . '</span>'
            . '<span class="journal-total__points">' . $points . '</span>'
            . '</span>';
    }

    if ($result['average'] === null) {
        return '';
    }

    $average = (float) $result['average'];
    $gradeClass = max(2, min(5, (int) round($average)));
    $label = htmlspecialchars(format_grading_number($average, 1), ENT_QUOTES, 'UTF-8');

    return '<span class="journal-total">'
        . '<span class="journal-total__grade journal-total__grade--' . $gradeClass . '">' . $label . '</span>'
        . '</span>';
}
