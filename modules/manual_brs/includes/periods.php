<?php

declare(strict_types=1);

/** Периоды 1–4 (четверти): по 2 в каждом семестре. */

function manual_brs_period_list(): array
{
    return [
        1 => [
            'id' => 1,
            'label' => '1 период',
            'semester' => '1',
            'semester_label' => '1 семестр',
            'half' => 1,
        ],
        2 => [
            'id' => 2,
            'label' => '2 период',
            'semester' => '1',
            'semester_label' => '1 семестр',
            'half' => 2,
        ],
        3 => [
            'id' => 3,
            'label' => '3 период',
            'semester' => '2',
            'semester_label' => '2 семестр',
            'half' => 1,
        ],
        4 => [
            'id' => 4,
            'label' => '4 период',
            'semester' => '2',
            'semester_label' => '2 семестр',
            'half' => 2,
        ],
    ];
}

function manual_brs_normalize_period(int $period): int
{
    return in_array($period, [1, 2, 3, 4], true) ? $period : 1;
}

function manual_brs_period_meta(int $period): array
{
    $period = manual_brs_normalize_period($period);
    $list = manual_brs_period_list();

    return $list[$period];
}

function manual_brs_semester_from_period(int $period): string
{
    return manual_brs_period_meta($period)['semester'];
}

/** Пара периодов одного семестра: [1,2] или [3,4]. */
function manual_brs_sibling_periods(int $period): array
{
    $period = manual_brs_normalize_period($period);

    return $period <= 2 ? [1, 2] : [3, 4];
}

function manual_brs_default_period_for_semester(string $semester): int
{
    return $semester === '2' ? 3 : 1;
}

function manual_brs_render_period_options(int $selected): string
{
    $html = '';
    foreach (manual_brs_period_list() as $row) {
        $sel = (int) $row['id'] === $selected ? ' selected' : '';
        $html .= '<option value="' . (int) $row['id'] . '"' . $sel . '>'
            . htmlspecialchars(
                $row['label'] . ' (' . $row['semester_label'] . ')',
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            )
            . '</option>';
    }

    return $html;
}
