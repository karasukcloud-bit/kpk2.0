<?php

declare(strict_types=1);

/** Период = семестр: 1 — 1-й семестр, 2 — 2-й. */

function manual_brs_period_list(): array
{
    return [
        1 => [
            'id' => 1,
            'label' => '1 семестр',
            'semester' => '1',
            'semester_label' => '1 семестр',
        ],
        2 => [
            'id' => 2,
            'label' => '2 семестр',
            'semester' => '2',
            'semester_label' => '2 семестр',
        ],
    ];
}

function manual_brs_normalize_period(int $period): int
{
    if ($period === 3 || $period === 4) {
        return 2;
    }
    if ($period === 2) {
        return 2;
    }

    return 1;
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

function manual_brs_period_for_semester(string $semester): int
{
    return $semester === '2' ? 2 : 1;
}

function manual_brs_default_period_for_semester(string $semester): int
{
    return manual_brs_period_for_semester($semester);
}

function manual_brs_render_period_options(int $selected): string
{
    $selected = manual_brs_normalize_period($selected);
    $html = '';
    foreach (manual_brs_period_list() as $row) {
        $sel = (int) $row['id'] === $selected ? ' selected' : '';
        $html .= '<option value="' . (int) $row['id'] . '"' . $sel . '>'
            . htmlspecialchars($row['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</option>';
    }

    return $html;
}
