<?php

declare(strict_types=1);

/**
 * Расчёт БРС для ручного ввода (формула как в журнале, без привязки к урокам).
 * Веса/шкала читаются из настроек приложения (только чтение).
 */

function manual_brs_weights(): array
{
    if (function_exists('get_grading_config')) {
        $config = get_grading_config();
        if (isset($config['brs']) && is_array($config['brs'])) {
            return $config['brs'];
        }
    }

    return [
        'weight_current' => 30.0,
        'weight_control' => 45.0,
        'weight_attendance' => 10.0,
        'weight_punctuality' => 5.0,
        'weight_activity' => 5.0,
        'scale_3' => 50.0,
        'scale_4' => 65.0,
        'scale_5' => 75.0,
    ];
}

/** Разбор строки оценок: «5453» / «5 4 3» / «5,4,3» / JSON. Только 2–5. */
function manual_brs_parse_marks(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    if ($raw[0] === '[') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $marks = [];
            foreach ($decoded as $value) {
                $n = (int) $value;
                if ($n >= 2 && $n <= 5) {
                    $marks[] = $n;
                }
            }

            return $marks;
        }
    }

    $digitsOnly = preg_replace('/[^2-5]/u', '', $raw);
    if ($digitsOnly === null || $digitsOnly === '') {
        return [];
    }

    $marks = [];
    $len = strlen($digitsOnly);
    for ($i = 0; $i < $len; $i++) {
        $marks[] = (int) $digitsOnly[$i];
    }

    return $marks;
}

function manual_brs_marks_to_storage(array $marks): string
{
    $clean = [];
    foreach ($marks as $mark) {
        $n = (int) $mark;
        if ($n >= 2 && $n <= 5) {
            $clean[] = $n;
        }
    }

    return json_encode($clean, JSON_UNESCAPED_UNICODE);
}

function manual_brs_marks_to_input(array $marks): string
{
    $clean = [];
    foreach ($marks as $mark) {
        $n = (int) $mark;
        if ($n >= 2 && $n <= 5) {
            $clean[] = (string) $n;
        }
    }

    return implode('', $clean);
}

function manual_brs_average(array $marks): ?float
{
    if ($marks === []) {
        return null;
    }

    return round(array_sum($marks) / count($marks), 2);
}

function manual_brs_points_to_grade(float $points, array $brs): int
{
    if (function_exists('brs_points_to_grade')) {
        return brs_points_to_grade($points, $brs);
    }

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

function manual_brs_format_number(?float $value, int $decimals = 1): string
{
    if ($value === null) {
        return '';
    }
    if (function_exists('format_grading_number')) {
        return format_grading_number($value, $decimals);
    }

    $formatted = number_format($value, $decimals, '.', '');
    if (strpos($formatted, '.') !== false) {
        $formatted = rtrim(rtrim($formatted, '0'), '.');
    }

    return $formatted === '' ? '0' : $formatted;
}

/**
 * @param array{
 *   current_marks?: string|array,
 *   control_marks?: string|array,
 *   absent_count?: int,
 *   late_count?: int,
 *   activity_count?: int,
 *   lessons_total?: int
 * } $input
 */
function manual_brs_calculate(array $input, ?array $brs = null): array
{
    $brs = $brs ?? manual_brs_weights();

    $currentMarks = is_array($input['current_marks'] ?? null)
        ? array_values(array_filter(array_map('intval', $input['current_marks'])))
        : manual_brs_parse_marks((string) ($input['current_marks'] ?? ''));
    $controlMarks = is_array($input['control_marks'] ?? null)
        ? array_values(array_filter(array_map('intval', $input['control_marks'])))
        : manual_brs_parse_marks((string) ($input['control_marks'] ?? ''));

    $currentMarks = array_values(array_filter(
        $currentMarks,
        static function (int $n): bool {
            return $n >= 2 && $n <= 5;
        }
    ));
    $controlMarks = array_values(array_filter(
        $controlMarks,
        static function (int $n): bool {
            return $n >= 2 && $n <= 5;
        }
    ));

    $lessonsTotal = max(0, (int) ($input['lessons_total'] ?? 0));
    $absent = max(0, (int) ($input['absent_count'] ?? 0));
    $late = max(0, (int) ($input['late_count'] ?? 0));
    $activity = max(0, (int) ($input['activity_count'] ?? 0));

    if ($lessonsTotal > 0 && $absent > $lessonsTotal) {
        $absent = $lessonsTotal;
    }
    $attended = max(0, $lessonsTotal - $absent);
    if ($late > $attended) {
        $late = $attended;
    }
    if ($activity > $attended) {
        $activity = $attended;
    }
    $notLate = max(0, $attended - $late);

    $currentAvg = manual_brs_average($currentMarks);
    $controlAvg = manual_brs_average($controlMarks);

    $points = 0.0;
    $hasData = $currentMarks !== [] || $controlMarks !== [] || $lessonsTotal > 0;

    if ($currentAvg !== null) {
        $points += ($currentAvg / 5.0) * (float) $brs['weight_current'];
    }

    if ($controlAvg !== null) {
        $points += ($controlAvg / 5.0) * (float) $brs['weight_control'];
    } elseif ($currentAvg !== null) {
        $points += ($currentAvg / 5.0) * (float) $brs['weight_control'];
    }

    if ($lessonsTotal > 0) {
        $points += ((float) $brs['weight_attendance'] / $lessonsTotal) * $attended;
        if ($attended > 0) {
            $points += ((float) $brs['weight_punctuality'] / $attended) * $notLate;
            $points += ((float) $brs['weight_activity'] / $attended) * $activity;
        }
    }

    if (!$hasData) {
        return [
            'current_marks' => $currentMarks,
            'control_marks' => $controlMarks,
            'current_avg' => null,
            'control_avg' => null,
            'absent_count' => $absent,
            'late_count' => $late,
            'activity_count' => $activity,
            'lessons_total' => $lessonsTotal,
            'attended' => $attended,
            'points' => null,
            'grade' => null,
            'display' => '',
        ];
    }

    $points = round(max(0.0, min(100.0, $points)), 1);
    $grade = manual_brs_points_to_grade($points, $brs);

    return [
        'current_marks' => $currentMarks,
        'control_marks' => $controlMarks,
        'current_avg' => $currentAvg,
        'control_avg' => $controlAvg,
        'absent_count' => $absent,
        'late_count' => $late,
        'activity_count' => $activity,
        'lessons_total' => $lessonsTotal,
        'attended' => $attended,
        'points' => $points,
        'grade' => $grade,
        'display' => manual_brs_format_number($points, 1) . ' → ' . $grade,
    ];
}

/** Итог семестра по двум периодам + ПА. */
function manual_brs_semester_total(
    ?float $pointsA,
    ?float $pointsB,
    ?int $paGrade = null,
    ?array $brs = null
): array {
    $brs = $brs ?? manual_brs_weights();
    $values = [];
    if ($pointsA !== null) {
        $values[] = $pointsA;
    }
    if ($pointsB !== null) {
        $values[] = $pointsB;
    }

    $points = null;
    $brsGrade = null;
    if ($values !== []) {
        $points = round(array_sum($values) / count($values), 1);
        $brsGrade = manual_brs_points_to_grade($points, $brs);
    }

    $paGrade = ($paGrade !== null && $paGrade >= 2 && $paGrade <= 5) ? $paGrade : null;

    if ($paGrade === null) {
        if ($brsGrade === null) {
            $result = [
                'points' => null,
                'grade' => null,
                'brs_grade' => null,
                'pa_grade' => null,
                'display' => '',
            ];
        } else {
            $result = [
                'points' => $points,
                'grade' => $brsGrade,
                'brs_grade' => $brsGrade,
                'pa_grade' => null,
                'display' => manual_brs_format_number($points, 1) . ' → ' . $brsGrade,
            ];
        }
    } elseif ($paGrade === 2) {
        $result = [
            'points' => $points,
            'grade' => 2,
            'brs_grade' => $brsGrade,
            'pa_grade' => 2,
            'display' => $points !== null
                ? manual_brs_format_number($points, 1) . ' → 2'
                : '2',
        ];
    } elseif ($brsGrade === null) {
        $result = [
            'points' => null,
            'grade' => $paGrade,
            'brs_grade' => null,
            'pa_grade' => $paGrade,
            'display' => (string) $paGrade,
        ];
    } else {
        $final = (int) round(($brsGrade + $paGrade) / 2, 0, PHP_ROUND_HALF_UP);
        $final = max(2, min(5, $final));
        $result = [
            'points' => $points,
            'grade' => $final,
            'brs_grade' => $brsGrade,
            'pa_grade' => $paGrade,
            'display' => manual_brs_format_number($points, 1) . ' → ' . $final,
        ];
    }

    $result['html'] = manual_brs_render_semester_html(
        $result['grade'] !== null ? (int) $result['grade'] : null,
        $result['points'] !== null ? (float) $result['points'] : null
    );

    return $result;
}

function manual_brs_period_display(?float $points, ?int $grade): string
{
    if ($points === null || $grade === null) {
        return '';
    }

    return manual_brs_format_number($points, 1) . ' → ' . $grade;
}

/** HTML итога семестра в стиле электронного журнала. */
function manual_brs_render_semester_html(?int $grade, ?float $points): string
{
    if ($grade === null) {
        return '<span class="text-muted">—</span>';
    }

    $gradeClass = max(2, min(5, $grade));
    $gradeLabel = htmlspecialchars((string) $grade, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html = '<div class="journal-total">'
        . '<span class="journal-total__grade journal-total__grade--' . $gradeClass . '">'
        . $gradeLabel
        . '</span>';

    if ($points !== null) {
        $pointsLabel = htmlspecialchars(manual_brs_format_number($points, 1), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html .= '<span class="journal-total__points">' . $pointsLabel . '</span>';
    }

    return $html . '</div>';
}
