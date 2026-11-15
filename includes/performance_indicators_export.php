<?php

declare(strict_types=1);

require_once __DIR__ . '/summary_table_export.php';
require_once __DIR__ . '/students.php';

/**
 * @param array<string,mixed> $lists
 * @return list<array<string,mixed>>
 */
function perf_export_quality_students(array $lists): array
{
    $merged = array_merge($lists['excellent'] ?? [], $lists['only_good'] ?? []);
    usort(
        $merged,
        static fn (array $a, array $b): int => strcasecmp(
            (string) ($a['full_name'] ?? ''),
            (string) ($b['full_name'] ?? '')
        )
    );

    return $merged;
}

/**
 * @param list<array<string,mixed>> $students
 */
function perf_export_student_names(array $students): string
{
    if ($students === []) {
        return '—';
    }
    $names = [];
    foreach ($students as $student) {
        $name = person_last_first_name((string) ($student['full_name'] ?? ''));
        if ($name !== '') {
            $names[] = $name;
        }
    }

    return $names === [] ? '—' : implode("\n", $names);
}

function perf_export_quality_row_level(float $percent): string
{
    if ($percent >= 50) {
        return 'high';
    }
    if ($percent > 20) {
        return 'mid';
    }

    return 'low';
}

/**
 * @param list<array<string,mixed>> $qualityRows
 * @param list<array<string,mixed>> $absoluteRows
 */
function build_perf_indicators_export_pdf_html(
    array $qualityRows,
    array $absoluteRows,
    string $caption,
    string $qualitySortLabel,
    string $absoluteSortLabel
): string {
    $org = get_organization();
    $orgName = trim((string) ($org['name'] ?? ''));

    $qualityBody = '';
    foreach ($qualityRows as $index => $row) {
        $group = $row['group'];
        $overview = $row['overview'];
        $summary = $overview['summary'];
        $qual = (float) $summary['quality_percent'];
        $list = perf_export_quality_students($overview['lists'] ?? []);
        $level = perf_export_quality_row_level($qual);
        $namesHtml = nl2br(e(perf_export_student_names($list)), false);

        $qualityBody .= '<tr class="row-' . e($level) . '">'
            . '<td class="num">' . ($index + 1) . '</td>'
            . '<td class="group">' . e((string) $group['number']) . '</td>'
            . '<td>' . e((string) $group['specialty_name']) . '</td>'
            . '<td>' . e(($group['curator_name'] ?? '') !== '' ? (string) $group['curator_name'] : '—') . '</td>'
            . '<td class="num">' . (int) $overview['students'] . '</td>'
            . '<td class="num">' . e(summary_export_format_percent($qual)) . '</td>'
            . '<td class="num">' . count($list) . '</td>'
            . '<td class="students">' . $namesHtml . '</td>'
            . '</tr>';
    }

    $absoluteBody = '';
    foreach ($absoluteRows as $index => $row) {
        $group = $row['group'];
        $overview = $row['overview'];
        $summary = $overview['summary'];
        $abs = (float) $summary['absolute_percent'];
        $list = $overview['lists']['with_twos'] ?? [];
        $namesHtml = nl2br(e(perf_export_student_names($list)), false);

        $absoluteBody .= '<tr>'
            . '<td class="num">' . ($index + 1) . '</td>'
            . '<td class="group">' . e((string) $group['number']) . '</td>'
            . '<td>' . e((string) $group['specialty_name']) . '</td>'
            . '<td>' . e(($group['curator_name'] ?? '') !== '' ? (string) $group['curator_name'] : '—') . '</td>'
            . '<td class="num">' . (int) $overview['students'] . '</td>'
            . '<td class="num">' . e(summary_export_format_percent($abs)) . '</td>'
            . '<td class="num">' . count($list) . '</td>'
            . '<td class="students">' . $namesHtml . '</td>'
            . '</tr>';
    }

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#1f2937;margin:0}
        h1{font-size:15px;margin:0 0 4px}
        h2{font-size:12px;margin:14px 0 4px}
        .meta{color:#64748b;margin:0 0 8px;font-size:9px}
        table{width:100%;border-collapse:collapse;margin-bottom:8px}
        th,td{border:1px solid #cbd5e1;padding:3px 4px;vertical-align:top}
        th{background:#e2e8f0;font-size:8px;text-transform:uppercase}
        td.num,.group{text-align:center;white-space:nowrap}
        td.group{font-weight:700}
        td.students{line-height:1.35}
        .row-high td{background:#ecfdf5}
        .row-mid td{background:#fffbeb}
        .row-low td{background:#fef2f2}
    </style></head><body>'
        . ($orgName !== '' ? '<div class="meta">' . e($orgName) . '</div>' : '')
        . '<h1>Показатели успеваемости</h1>'
        . '<p class="meta">' . e($caption) . '</p>'
        . '<h2>Рейтинг групп по качественной успеваемости</h2>'
        . '<p class="meta">Сортировка: ' . e($qualitySortLabel) . '</p>'
        . '<table><thead><tr>'
        . '<th>Место</th><th>Группа</th><th>Специальность</th><th>Куратор</th><th>Студентов</th>'
        . '<th>Кач. усп.</th><th>Кол-во</th><th>Студенты на 4–5 и 5</th>'
        . '</tr></thead><tbody>' . $qualityBody . '</tbody></table>'
        . '<h2>Показатели абсолютной успеваемости</h2>'
        . '<p class="meta">Сортировка: ' . e($absoluteSortLabel) . '</p>'
        . '<table><thead><tr>'
        . '<th>№</th><th>Группа</th><th>Специальность</th><th>Куратор</th><th>Студентов</th>'
        . '<th>Абс. усп.</th><th>Кол-во</th><th>Студенты с «2»</th>'
        . '</tr></thead><tbody>' . $absoluteBody . '</tbody></table>'
        . '</body></html>';
}

/**
 * @param list<array<string,mixed>> $qualityRows
 * @param list<array<string,mixed>> $absoluteRows
 */
function build_perf_indicators_export_excel_xml(
    array $qualityRows,
    array $absoluteRows,
    string $caption,
    string $qualitySortLabel,
    string $absoluteSortLabel
): string {
    $org = get_organization();
    $orgName = trim((string) ($org['name'] ?? ''));
    $esc = static function (string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    };

    $headerMeta = $caption;
    if ($orgName !== '') {
        $headerMeta = $orgName . ' — ' . $headerMeta;
    }

    $buildSheetRows = static function (
        array $rows,
        string $percentKey,
        string $listMode
    ) use ($esc): string {
        $xml = '';
        foreach ($rows as $index => $row) {
            $group = $row['group'];
            $overview = $row['overview'];
            $summary = $overview['summary'];
            $percent = (float) ($summary[$percentKey] ?? 0);
            $list = $listMode === 'quality'
                ? perf_export_quality_students($overview['lists'] ?? [])
                : ($overview['lists']['with_twos'] ?? []);
            $level = $listMode === 'quality' ? perf_export_quality_row_level($percent) : 'plain';
            $rowStyle = match ($level) {
                'high' => 'RowHigh',
                'mid' => 'RowMid',
                'low' => 'RowLow',
                default => 'Cell',
            };
            $names = perf_export_student_names($list);

            $xml .= '<Row>'
                . '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="Number">' . ($index + 1) . '</Data></Cell>'
                . '<Cell ss:StyleID="Group"><Data ss:Type="String">' . $esc((string) $group['number']) . '</Data></Cell>'
                . '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">'
                . $esc((string) $group['specialty_name']) . '</Data></Cell>'
                . '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">'
                . $esc(($group['curator_name'] ?? '') !== '' ? (string) $group['curator_name'] : '—')
                . '</Data></Cell>'
                . '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="Number">'
                . (int) $overview['students'] . '</Data></Cell>'
                . '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="String">'
                . $esc(summary_export_format_percent($percent)) . '</Data></Cell>'
                . '<Cell ss:StyleID="' . $rowStyle . '"><Data ss:Type="Number">' . count($list) . '</Data></Cell>'
                . '<Cell ss:StyleID="Students"><Data ss:Type="String">' . $esc($names) . '</Data></Cell>'
                . '</Row>';
        }

        return $xml;
    };

    $qualityXml = $buildSheetRows($qualityRows, 'quality_percent', 'quality');
    $absoluteXml = $buildSheetRows($absoluteRows, 'absolute_percent', 'absolute');

    $cols = '<Column ss:Width="35"/><Column ss:Width="45"/><Column ss:Width="170"/><Column ss:Width="140"/>'
        . '<Column ss:Width="50"/><Column ss:Width="55"/><Column ss:Width="45"/><Column ss:Width="220"/>';

    $headerRow = '<Row>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">№</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Группа</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Специальность</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Куратор</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Студентов</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">%</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Кол-во</Data></Cell>'
        . '<Cell ss:StyleID="Header"><Data ss:Type="String">Список студентов</Data></Cell>'
        . '</Row>';

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
        . '<Style ss:ID="Cell"><Alignment ss:Vertical="Top"/></Style>'
        . '<Style ss:ID="Group"><Font ss:Bold="1"/><Alignment ss:Horizontal="Center" ss:Vertical="Top"/></Style>'
        . '<Style ss:ID="Students"><Alignment ss:Vertical="Top" ss:WrapText="1"/></Style>'
        . '<Style ss:ID="RowHigh"><Interior ss:Color="#ECFDF5" ss:Pattern="Solid"/><Alignment ss:Vertical="Top"/></Style>'
        . '<Style ss:ID="RowMid"><Interior ss:Color="#FFFBEB" ss:Pattern="Solid"/><Alignment ss:Vertical="Top"/></Style>'
        . '<Style ss:ID="RowLow"><Interior ss:Color="#FEF2F2" ss:Pattern="Solid"/><Alignment ss:Vertical="Top"/></Style>'
        . '</Styles>'
        . '<Worksheet ss:Name="Кач. успеваемость">'
        . '<Table>' . $cols
        . '<Row><Cell ss:StyleID="Title" ss:MergeAcross="7"><Data ss:Type="String">'
        . $esc('Рейтинг по качественной успеваемости') . '</Data></Cell></Row>'
        . '<Row><Cell ss:StyleID="Meta" ss:MergeAcross="7"><Data ss:Type="String">'
        . $esc($headerMeta . '. Сортировка: ' . $qualitySortLabel) . '</Data></Cell></Row>'
        . '<Row/>' . $headerRow . $qualityXml
        . '</Table></Worksheet>'
        . '<Worksheet ss:Name="Абс. успеваемость">'
        . '<Table>' . $cols
        . '<Row><Cell ss:StyleID="Title" ss:MergeAcross="7"><Data ss:Type="String">'
        . $esc('Показатели абсолютной успеваемости') . '</Data></Cell></Row>'
        . '<Row><Cell ss:StyleID="Meta" ss:MergeAcross="7"><Data ss:Type="String">'
        . $esc($headerMeta . '. Сортировка: ' . $absoluteSortLabel) . '</Data></Cell></Row>'
        . '<Row/>' . $headerRow . $absoluteXml
        . '</Table></Worksheet>'
        . '</Workbook>';
}

/**
 * @param list<array<string,mixed>> $qualityRows
 * @param list<array<string,mixed>> $absoluteRows
 */
function stream_perf_indicators_export(
    array $qualityRows,
    array $absoluteRows,
    string $format,
    string $caption,
    string $qualitySort,
    string $qualityDir,
    string $absoluteSort,
    string $absoluteDir
): void {
    $qualitySortLabel = summary_export_sort_label($qualitySort, $qualityDir);
    $absoluteSortLabel = summary_export_sort_label($absoluteSort, $absoluteDir);
    $safeBase = 'pokazateli_uspevaemosti_' . date('Y-m-d_H-i');

    if ($format === 'excel') {
        $xml = build_perf_indicators_export_excel_xml(
            $qualityRows,
            $absoluteRows,
            $caption,
            $qualitySortLabel,
            $absoluteSortLabel
        );
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

    $html = build_perf_indicators_export_pdf_html(
        $qualityRows,
        $absoluteRows,
        $caption,
        $qualitySortLabel,
        $absoluteSortLabel
    );
    $pdf = render_html_to_pdf($html, 'landscape');
    send_gradebook_pdf_download($safeBase . '.pdf', $pdf);
}
