<?php

declare(strict_types=1);

require_once __DIR__ . '/pdf.php';
require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/organization.php';
require_once __DIR__ . '/summary_table_export.php';

/**
 * @param list<array<string,mixed>> $subjects
 * @param list<string> $orderKeys
 * @return list<array<string,mixed>>
 */
function subject_perf_export_order_rows(array $subjects, array $orderKeys): array
{
    if ($orderKeys === []) {
        return $subjects;
    }

    $byKey = [];
    foreach ($subjects as $row) {
        $key = (string) ($row['key'] ?? '');
        if ($key !== '') {
            $byKey[$key] = $row;
        }
    }

    $ordered = [];
    foreach ($orderKeys as $key) {
        if (isset($byKey[$key])) {
            $ordered[] = $byKey[$key];
            unset($byKey[$key]);
        }
    }
    foreach ($byKey as $row) {
        $ordered[] = $row;
    }

    return $ordered;
}

/**
 * @param list<array<string,mixed>> $subjects
 */
function build_subject_perf_export_pdf_html(
    array $subjects,
    string $title,
    string $caption,
    string $sortLabel
): string {
    $org = get_organization();
    $orgName = trim((string) ($org['name'] ?? ''));

    $body = '';
    foreach ($subjects as $index => $subject) {
        $body .= '<tr>'
            . '<td class="num">' . ($index + 1) . '</td>'
            . '<td>' . e((string) $subject['name']) . '</td>'
            . '<td class="num">' . (int) $subject['assessed'] . '</td>'
            . '<td class="num">' . e(summary_export_format_percent((float) $subject['absolute_percent'])) . '</td>'
            . '<td class="num">' . e(summary_export_format_percent((float) $subject['quality_percent'])) . '</td>'
            . '<td class="num">' . e(number_format((float) $subject['average_grade'], 2, '.', '')) . '</td>'
            . '</tr>';
    }

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#1f2937;margin:0}
        h1{font-size:15px;margin:0 0 4px}
        .meta{color:#64748b;margin:0 0 10px;font-size:9px}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #cbd5e1;padding:4px 5px;vertical-align:middle}
        th{background:#e2e8f0;font-size:9px;text-transform:uppercase}
        td.num{text-align:center;white-space:nowrap}
        tr:nth-child(even) td{background:#f8fafc}
    </style></head><body>'
        . ($orgName !== '' ? '<div class="meta">' . e($orgName) . '</div>' : '')
        . '<h1>' . e($title) . '</h1>'
        . '<p class="meta">' . e($caption) . '. Сортировка: ' . e($sortLabel) . '.</p>'
        . '<table><thead><tr>'
        . '<th>№</th><th>Предмет</th><th>Оценок</th><th>Абс. усп.</th><th>Кач. усп.</th><th>Средний балл</th>'
        . '</tr></thead><tbody>' . $body . '</tbody></table></body></html>';
}

/**
 * @param list<array<string,mixed>> $subjects
 */
function build_subject_perf_export_excel_xml(
    array $subjects,
    string $title,
    string $caption,
    string $sortLabel
): string {
    $org = get_organization();
    $orgName = trim((string) ($org['name'] ?? ''));
    $esc = static function (string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    };

    $headerMeta = $caption . '. Сортировка: ' . $sortLabel . '.';
    if ($orgName !== '') {
        $headerMeta = $orgName . ' — ' . $headerMeta;
    }

    $rowsXml = '';
    foreach ($subjects as $index => $subject) {
        $rowsXml .= '<Row>'
            . '<Cell ss:StyleID="Cell"><Data ss:Type="Number">' . ($index + 1) . '</Data></Cell>'
            . '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $esc((string) $subject['name']) . '</Data></Cell>'
            . '<Cell ss:StyleID="Cell"><Data ss:Type="Number">' . (int) $subject['assessed'] . '</Data></Cell>'
            . '<Cell ss:StyleID="Cell"><Data ss:Type="String">'
            . $esc(summary_export_format_percent((float) $subject['absolute_percent'])) . '</Data></Cell>'
            . '<Cell ss:StyleID="Cell"><Data ss:Type="String">'
            . $esc(summary_export_format_percent((float) $subject['quality_percent'])) . '</Data></Cell>'
            . '<Cell ss:StyleID="Cell"><Data ss:Type="Number">'
            . number_format((float) $subject['average_grade'], 2, '.', '') . '</Data></Cell>'
            . '</Row>';
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
        . '</Styles>'
        . '<Worksheet ss:Name="По предметам">'
        . '<Table>'
        . '<Column ss:Width="30"/><Column ss:Width="280"/><Column ss:Width="55"/>'
        . '<Column ss:Width="60"/><Column ss:Width="60"/><Column ss:Width="70"/>'
        . '<Row><Cell ss:StyleID="Title" ss:MergeAcross="5"><Data ss:Type="String">'
        . $esc($title) . '</Data></Cell></Row>'
        . '<Row><Cell ss:StyleID="Meta" ss:MergeAcross="5"><Data ss:Type="String">'
        . $esc($headerMeta) . '</Data></Cell></Row>'
        . '<Row/>'
        . '<Row>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">№</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Предмет</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Оценок</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Абс. усп.</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Кач. усп.</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Средний балл</Data></Cell>'
        . '</Row>'
        . $rowsXml
        . '</Table></Worksheet></Workbook>';
}

/**
 * @param list<array<string,mixed>> $subjects
 */
function stream_subject_perf_export(
    array $subjects,
    string $format,
    string $title,
    string $caption,
    string $sort,
    string $dir
): void {
    $sortLabel = summary_export_sort_label($sort, $dir);
    $safeBase = 'uspevaemost_po_predmetam_' . date('Y-m-d_H-i');

    if ($format === 'excel') {
        $xml = build_subject_perf_export_excel_xml($subjects, $title, $caption, $sortLabel);
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

    $html = build_subject_perf_export_pdf_html($subjects, $title, $caption, $sortLabel);
    $pdf = render_html_to_pdf($html, 'landscape');
    send_gradebook_pdf_download($safeBase . '.pdf', $pdf);
}
