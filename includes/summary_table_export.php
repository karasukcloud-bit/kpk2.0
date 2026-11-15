<?php

declare(strict_types=1);

require_once __DIR__ . '/pdf.php';
require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/organization.php';

/**
 * @param list<array<string,mixed>> $rows
 * @param list<int> $orderIds
 * @return list<array<string,mixed>>
 */
function summary_export_order_rows(array $rows, array $orderIds): array
{
    if ($orderIds === []) {
        return $rows;
    }

    $byId = [];
    foreach ($rows as $row) {
        $id = (int) ($row['group']['id'] ?? 0);
        if ($id > 0) {
            $byId[$id] = $row;
        }
    }

    $ordered = [];
    foreach ($orderIds as $id) {
        if (isset($byId[$id])) {
            $ordered[] = $byId[$id];
            unset($byId[$id]);
        }
    }

    foreach ($byId as $row) {
        $ordered[] = $row;
    }

    return $ordered;
}

function summary_export_sort_label(string $sort, string $dir): string
{
    $labels = [
        'absolute' => 'Абс. усп.',
        'quality' => 'Кач. усп.',
        'group' => 'Группа',
    ];
    if (!isset($labels[$sort])) {
        return 'без сортировки';
    }
    $arrow = $dir === 'asc' ? '↑' : '↓';

    return $labels[$sort] . ' ' . $arrow;
}

function summary_export_format_percent(float $percent): string
{
    $clamped = max(0.0, min(100.0, $percent));
    $label = rtrim(rtrim(number_format($clamped, 1, '.', ''), '0'), '.');

    return ($label === '' ? '0' : $label) . '%';
}

function summary_export_percent_level(float $percent): string
{
    $clamped = max(0.0, min(100.0, $percent));
    if ($clamped >= 70) {
        return 'high';
    }
    if ($clamped >= 40) {
        return 'mid';
    }

    return 'low';
}

/**
 * @param list<array<string,mixed>> $rows
 */
function build_summary_export_pdf_html(
    array $rows,
    string $title,
    string $caption,
    string $sortLabel
): string {
    $org = get_organization();
    $orgName = trim((string) ($org['name'] ?? ''));

    $bodyRows = '';
    foreach ($rows as $index => $row) {
        $group = $row['group'];
        $overview = $row['overview'];
        $summary = $overview['summary'];
        $abs = (float) $summary['absolute_percent'];
        $qual = (float) $summary['quality_percent'];
        $withTwos = (int) $overview['with_twos'];
        $absLevel = summary_export_percent_level($abs);
        $qualLevel = summary_export_percent_level($qual);

        $bodyRows .= '<tr' . ($withTwos > 0 ? ' class="row-debt"' : '') . '>'
            . '<td class="num">' . ($index + 1) . '</td>'
            . '<td class="group">' . e((string) $group['number']) . '</td>'
            . '<td>' . e((string) $group['specialty_name']) . '</td>'
            . '<td>' . e(($group['curator_name'] ?? '') !== '' ? (string) $group['curator_name'] : '—') . '</td>'
            . '<td class="num">' . (int) $overview['students'] . '</td>'
            . '<td class="perf perf-' . e($absLevel) . '">' . e(summary_export_format_percent($abs)) . '</td>'
            . '<td class="perf perf-' . e($qualLevel) . '">' . e(summary_export_format_percent($qual)) . '</td>'
            . '<td class="num">' . e((string) $summary['one_two_percent']) . '%</td>'
            . '<td class="num">' . e((string) $summary['one_three_percent']) . '%</td>'
            . '<td class="num' . ($withTwos > 0 ? ' warn' : '') . '">' . $withTwos . '</td>'
            . '<td class="num good">' . (int) $overview['only_good'] . '</td>'
            . '<td class="num best">' . (int) $overview['excellent'] . '</td>'
            . '</tr>';
    }

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#1f2937;margin:0}
        h1{font-size:16px;margin:0 0 4px}
        .meta{color:#64748b;margin:0 0 10px;font-size:10px}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #cbd5e1;padding:4px 5px;vertical-align:middle}
        th{background:#e2e8f0;font-size:9px;text-transform:uppercase;letter-spacing:.02em}
        td.num,.perf{text-align:center;white-space:nowrap}
        td.group{font-weight:700}
        .perf{font-weight:800;font-size:11px}
        .perf-high{color:#15803d}
        .perf-mid{color:#b45309}
        .perf-low{color:#b91c1c}
        .warn{color:#b45309;font-weight:700}
        .good{color:#15803d;font-weight:700}
        .best{color:#1d4ed8;font-weight:700}
        tr.row-debt td:first-child{border-left:3px solid #f59e0b}
        tr:nth-child(even) td{background:#f8fafc}
    </style></head><body>'
        . ($orgName !== '' ? '<div class="meta">' . e($orgName) . '</div>' : '')
        . '<h1>' . e($title) . '</h1>'
        . '<p class="meta">' . e($caption) . '. Сортировка: ' . e($sortLabel) . '.</p>'
        . '<table><thead><tr>'
        . '<th>№</th><th>Группа</th><th>Специальность</th><th>Куратор</th><th>Студентов</th>'
        . '<th>Абс. усп.</th><th>Кач. усп.</th><th>С 1 «2», %</th><th>С 1 «3», %</th>'
        . '<th>С «2»</th><th>4–5</th><th>Отличники</th>'
        . '</tr></thead><tbody>' . $bodyRows . '</tbody></table></body></html>';
}

/**
 * @param list<array<string,mixed>> $rows
 */
function build_summary_export_excel_xml(
    array $rows,
    string $title,
    string $caption,
    string $sortLabel
): string {
    $org = get_organization();
    $orgName = trim((string) ($org['name'] ?? ''));

    $esc = static function (string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    };

    $rowsXml = '';
    foreach ($rows as $index => $row) {
        $group = $row['group'];
        $overview = $row['overview'];
        $summary = $overview['summary'];
        $abs = (float) $summary['absolute_percent'];
        $qual = (float) $summary['quality_percent'];
        $withTwos = (int) $overview['with_twos'];
        $absStyle = 'Perf' . ucfirst(summary_export_percent_level($abs));
        $qualStyle = 'Perf' . ucfirst(summary_export_percent_level($qual));

        $rowsXml .= '<Row>'
            . '<Cell ss:StyleID="Cell"><Data ss:Type="Number">' . ($index + 1) . '</Data></Cell>'
            . '<Cell ss:StyleID="Group"><Data ss:Type="String">' . $esc((string) $group['number']) . '</Data></Cell>'
            . '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $esc((string) $group['specialty_name']) . '</Data></Cell>'
            . '<Cell ss:StyleID="Cell"><Data ss:Type="String">'
            . $esc(($group['curator_name'] ?? '') !== '' ? (string) $group['curator_name'] : '—')
            . '</Data></Cell>'
            . '<Cell ss:StyleID="Cell"><Data ss:Type="Number">' . (int) $overview['students'] . '</Data></Cell>'
            . '<Cell ss:StyleID="' . $absStyle . '"><Data ss:Type="String">'
            . $esc(summary_export_format_percent($abs)) . '</Data></Cell>'
            . '<Cell ss:StyleID="' . $qualStyle . '"><Data ss:Type="String">'
            . $esc(summary_export_format_percent($qual)) . '</Data></Cell>'
            . '<Cell ss:StyleID="Cell"><Data ss:Type="String">'
            . $esc((string) $summary['one_two_percent']) . '%</Data></Cell>'
            . '<Cell ss:StyleID="Cell"><Data ss:Type="String">'
            . $esc((string) $summary['one_three_percent']) . '%</Data></Cell>'
            . '<Cell ss:StyleID="' . ($withTwos > 0 ? 'Warn' : 'Cell') . '"><Data ss:Type="Number">'
            . $withTwos . '</Data></Cell>'
            . '<Cell ss:StyleID="Good"><Data ss:Type="Number">' . (int) $overview['only_good'] . '</Data></Cell>'
            . '<Cell ss:StyleID="Best"><Data ss:Type="Number">' . (int) $overview['excellent'] . '</Data></Cell>'
            . '</Row>';
    }

    $headerMeta = trim($caption . '. Сортировка: ' . $sortLabel . '.');
    if ($orgName !== '') {
        $headerMeta = $orgName . ' — ' . $headerMeta;
    }

    return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<?mso-application progid="Excel.Sheet"?>'
        . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
        . ' xmlns:o="urn:schemas-microsoft-com:office:office"'
        . ' xmlns:x="urn:schemas-microsoft-com:office:excel"'
        . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"'
        . ' xmlns:html="http://www.w3.org/TR/REC-html40">'
        . '<Styles>'
        . '<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="14"/></Style>'
        . '<Style ss:ID="Meta"><Font ss:Color="#64748B" ss:Size="10"/></Style>'
        . '<Style ss:ID="Header"><Font ss:Bold="1" ss:Size="10"/><Interior ss:Color="#E2E8F0" ss:Pattern="Solid"/>'
        . '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/></Style>'
        . '<Style ss:ID="Cell"><Alignment ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="Group"><Font ss:Bold="1"/><Alignment ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="PerfHigh"><Font ss:Bold="1" ss:Size="12" ss:Color="#15803D"/>'
        . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="PerfMid"><Font ss:Bold="1" ss:Size="12" ss:Color="#B45309"/>'
        . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="PerfLow"><Font ss:Bold="1" ss:Size="12" ss:Color="#B91C1C"/>'
        . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="Warn"><Font ss:Bold="1" ss:Color="#B45309"/>'
        . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="Good"><Font ss:Bold="1" ss:Color="#15803D"/>'
        . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
        . '<Style ss:ID="Best"><Font ss:Bold="1" ss:Color="#1D4ED8"/>'
        . '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
        . '</Styles>'
        . '<Worksheet ss:Name="Сводная">'
        . '<Table>'
        . '<Column ss:Width="30"/><Column ss:Width="50"/><Column ss:Width="180"/><Column ss:Width="150"/>'
        . '<Column ss:Width="55"/><Column ss:Width="55"/><Column ss:Width="55"/><Column ss:Width="55"/>'
        . '<Column ss:Width="55"/><Column ss:Width="45"/><Column ss:Width="40"/><Column ss:Width="55"/>'
        . '<Row><Cell ss:StyleID="Title" ss:MergeAcross="11"><Data ss:Type="String">'
        . $esc($title) . '</Data></Cell></Row>'
        . '<Row><Cell ss:StyleID="Meta" ss:MergeAcross="11"><Data ss:Type="String">'
        . $esc($headerMeta) . '</Data></Cell></Row>'
        . '<Row/>'
        . '<Row>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">№</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Группа</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Специальность</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Куратор</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Студентов</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Абс. усп.</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Кач. усп.</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">С 1 «2», %</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">С 1 «3», %</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">С «2»</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">4–5</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Отличники</Data></Cell>'
        . '</Row>'
        . $rowsXml
        . '</Table></Worksheet></Workbook>';
}

/**
 * @param list<array<string,mixed>> $rows
 */
function stream_summary_table_export(
    array $rows,
    string $format,
    string $title,
    string $caption,
    string $sort,
    string $dir
): void {
    $sortLabel = summary_export_sort_label($sort, $dir);
    $safeBase = 'svodnaya_' . date('Y-m-d_H-i');

    if ($format === 'excel') {
        $xml = build_summary_export_excel_xml($rows, $title, $caption, $sortLabel);
        $filename = $safeBase . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header(
            'Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\''
            . rawurlencode($filename)
        );
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $xml;
        exit;
    }

    $html = build_summary_export_pdf_html($rows, $title, $caption, $sortLabel);
    $pdf = render_html_to_pdf($html, 'landscape');
    $filename = $safeBase . '.pdf';
    send_gradebook_pdf_download($filename, $pdf);
}
