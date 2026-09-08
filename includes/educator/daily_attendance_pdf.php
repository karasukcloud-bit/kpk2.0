<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../attendance.php';
require_once __DIR__ . '/../organization.php';
require_once __DIR__ . '/../pdf.php';

function educator_daily_attendance_pdf_filename(string $date): string
{
    return 'propuski_po_dnyam_' . $date . '.pdf';
}

function educator_daily_attendance_pdf_styles(): string
{
    return <<<'CSS'
@page { size: A4 landscape; margin: 10mm 8mm 12mm; }

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 8.5pt;
    color: #111;
    line-height: 1.3;
}

.pdf-header-org {
    font-size: 9pt;
    text-align: center;
    margin: 0 0 2mm;
    color: #333;
}

.pdf-header-title {
    font-size: 13pt;
    font-weight: bold;
    text-align: center;
    margin: 0 0 5mm;
}

.pdf-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 7.5pt;
}

.pdf-table th,
.pdf-table td {
    border: 0.5pt solid #333;
    padding: 1.2mm 1mm;
    vertical-align: middle;
    word-wrap: break-word;
}

.pdf-table th {
    background: #f0f0f0;
    font-weight: bold;
    text-align: center;
}

.pdf-table__group {
    width: 14mm;
    text-align: center;
    font-weight: bold;
}

.pdf-table__curator {
    width: 28mm;
    text-align: left;
    font-size: 7pt;
}

.pdf-table__reason,
.pdf-table__unexcused {
    text-align: center;
    width: 12mm;
}

.pdf-table__students {
    text-align: left;
    font-size: 7pt;
}

.pdf-table__row--marked td {
    background: #eef8ef;
}

.pdf-empty {
    color: #888;
}

.pdf-note {
    margin-top: 4mm;
    font-size: 7.5pt;
    color: #555;
}
CSS;
}

function educator_daily_attendance_pdf_render_html(string $date, array $dailyReport): string
{
    $orgName = trim((string) (get_organization()['name'] ?? ''));
    $reasons = $dailyReport['reasons'] ?? [];
    $rows = $dailyReport['rows'] ?? [];
    $dateLabel = e(format_attendance_date($date));

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'
        . educator_daily_attendance_pdf_styles()
        . '</style></head><body>';

    if ($orgName !== '') {
        $html .= '<div class="pdf-header-org">' . e($orgName) . '</div>';
    }

    $html .= '<div class="pdf-header-title">Информация о пропусках занятий за '
        . $dateLabel . '</div>';

    if ($rows === []) {
        $html .= '<p>Группы пока не добавлены.</p></body></html>';

        return $html;
    }

    $html .= '<table class="pdf-table"><thead><tr>';
    $html .= '<th class="pdf-table__group">Группа</th>';
    $html .= '<th class="pdf-table__curator">Куратор</th>';

    foreach ($reasons as $reason) {
        $html .= '<th class="pdf-table__reason">' . e((string) $reason['name']) . '</th>';
    }

    $html .= '<th class="pdf-table__unexcused">Неуважительные</th>';
    $html .= '<th class="pdf-table__students">Студенты с неуважительными пропусками</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $marked = !empty($row['has_absences']);
        $unexcused = (int) ($row['unexcused'] ?? 0);
        $students = $row['unexcused_students'] ?? [];
        $curatorName = trim((string) ($row['curator_name'] ?? ''));
        $rowClass = $marked ? ' class="pdf-table__row--marked"' : '';

        $html .= '<tr' . $rowClass . '>';
        $html .= '<td class="pdf-table__group">' . e((string) $row['group_number']) . '</td>';
        $html .= '<td class="pdf-table__curator">'
            . ($curatorName !== '' ? e($curatorName) : '<span class="pdf-empty">—</span>')
            . '</td>';

        foreach ($reasons as $reason) {
            $reasonId = (int) $reason['id'];
            $count = (int) ($row['reason_totals'][$reasonId] ?? 0);
            $html .= '<td class="pdf-table__reason">'
                . ($count > 0 ? (string) $count : '<span class="pdf-empty">—</span>')
                . '</td>';
        }

        $html .= '<td class="pdf-table__unexcused">'
            . ($unexcused > 0 ? (string) $unexcused : '<span class="pdf-empty">—</span>')
            . '</td>';
        $html .= '<td class="pdf-table__students">'
            . e(format_educator_unexcused_students_list($students))
            . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    $html .= '<div class="pdf-note">Зелёная подсветка строки — за выбранный день дата уже внесена куратором.</div>';
    $html .= '</body></html>';

    return $html;
}

function stream_educator_daily_attendance_pdf(string $date): void
{
    $date = resolve_educator_daily_attendance_date($date);
    $dailyReport = build_educator_daily_attendance_report($date);
    $html = educator_daily_attendance_pdf_render_html($date, $dailyReport);
    $pdf = render_html_to_pdf($html, 'landscape');
    $filename = educator_daily_attendance_pdf_filename($date);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) strlen($pdf));
    header('Cache-Control: private, max-age=0, must-revalidate');

    echo $pdf;
    exit;
}
