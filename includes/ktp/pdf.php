<?php

declare(strict_types=1);

require_once __DIR__ . '/../organization.php';
require_once __DIR__ . '/../curriculum.php';
require_once __DIR__ . '/../pdf.php';
require_once __DIR__ . '/../ktp.php';
require_once __DIR__ . '/document_header.php';
require_once __DIR__ . '/summary_table.php';

function ktp_pdf_build_document(int $itemId): ?array
{
    $item = get_curriculum_item_by_id($itemId);
    if ($item === null) {
        return null;
    }

    $topics = get_ktp_topics($itemId);
    if ($topics === []) {
        return null;
    }

    return [
        'org' => get_organization(),
        'item' => $item,
        'topics' => $topics,
        'summary' => build_ktp_plan_summary($topics),
        'is_professionality' => curriculum_item_is_professionality($item),
        'column_widths' => get_ktp_column_widths($itemId),
    ];
}

function ktp_pdf_filename(array $document): string
{
    $item = $document['item'];
    $sanitize = static function (string $value): string {
        $value = trim(preg_replace('/[\\\\\/:*?"<>|]+/u', ' ', $value) ?? '');
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return $value;
    };

    $subject = $sanitize((string) ($item['subject_name'] ?? '')) ?: 'предмет';
    $group = $sanitize((string) ($item['group_number'] ?? '')) ?: 'группа';
    $year = str_replace('/', '-', $sanitize((string) ($item['academic_year'] ?? ''))) ?: 'год';

    return 'КТП ' . $subject . ' ' . $group . ' ' . $year . '.pdf';
}

function ktp_pdf_resolve_times_fonts(): ?array
{
    $fileMap = [
        'normal' => 'times.ttf',
        'bold' => 'timesbd.ttf',
        'italic' => 'timesi.ttf',
        'bold_italic' => 'timesbi.ttf',
    ];
    $dirs = [
        dirname(__DIR__, 2) . '/assets/fonts/times-new-roman',
        'C:/Windows/Fonts',
    ];

    foreach ($dirs as $dir) {
        $resolvedDir = realpath($dir);
        if ($resolvedDir === false) {
            continue;
        }

        $paths = [];
        foreach ($fileMap as $key => $filename) {
            $path = $resolvedDir . DIRECTORY_SEPARATOR . $filename;
            if (!is_readable($path)) {
                continue 2;
            }
            $paths[$key] = realpath($path) ?: $path;
        }

        return $paths;
    }

    return null;
}

function ktp_pdf_times_font_faces_css(?array $fonts): string
{
    if ($fonts === null) {
        return '';
    }

    $variants = [
        ['normal', 'normal', $fonts['normal']],
        ['bold', 'normal', $fonts['bold']],
        ['normal', 'italic', $fonts['italic']],
        ['bold', 'italic', $fonts['bold_italic']],
    ];

    $css = '';
    foreach ($variants as [$weight, $style, $path]) {
        $url = str_replace('\\', '/', $path);
        $css .= sprintf(
            "@font-face{font-family:'Times New Roman';font-weight:%s;font-style:%s;src:url('%s') format('truetype');}\n",
            $weight,
            $style,
            $url
        );
    }

    return $css;
}

function ktp_pdf_styles(?array $timesFonts = null): string
{
    $fontFamily = $timesFonts !== null
        ? '"Times New Roman", Times, serif'
        : 'DejaVu Sans, sans-serif';

    return ktp_pdf_times_font_faces_css($timesFonts) . <<<CSS
@page { size: A4 landscape; margin: 10mm 8mm 12mm; }

body {
    font-family: {$fontFamily};
    font-size: 8.5pt;
    color: #111;
    line-height: 1.3;
}

.pdf-header-title {
    font-size: 13pt;
    font-weight: bold;
    margin: 0 0 2mm;
    text-align: center;
}

.pdf-header-meta {
    font-size: 8.5pt;
    color: #333;
    margin: 0 0 1mm;
    text-align: center;
}

.pdf-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4mm;
}

.pdf-table--custom-widths {
    table-layout: fixed;
}

.pdf-table th,
.pdf-table td {
    border: 1px solid #000;
    padding: 1.5mm 1.8mm;
    vertical-align: top;
    text-align: left;
}

.pdf-table th {
    background: #f2f2f2;
    font-weight: bold;
}

.pdf-table .col-num { width: 6mm; text-align: center; }
.pdf-table .col-hours { width: 12mm; text-align: center; white-space: nowrap; }
.pdf-table .col-type { width: 22mm; }
.pdf-table .col-deadline { width: 18mm; white-space: nowrap; }
.pdf-table .col-control { width: 24mm; }

.pdf-workload-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 7pt;
    table-layout: fixed;
}

.pdf-workload-table th,
.pdf-workload-table td {
    border: 1px solid #000;
    padding: 0.8mm 1mm;
    vertical-align: middle;
    text-align: center;
    word-wrap: break-word;
}

.pdf-workload-table th {
    background: #f2f2f2;
    font-weight: bold;
}

.pdf-workload-table__label {
    text-align: left;
    font-weight: normal;
    font-size: 6.5pt;
    line-height: 1.2;
}

.pdf-workload-table__label-sub {
    font-style: italic;
    font-size: 5.5pt;
}

.pdf-workload-table__corner {
    padding: 0;
    vertical-align: middle;
}

.pdf-workload-table__corner-grid {
    width: 100%;
    border-collapse: collapse;
    border: none;
}

.pdf-workload-table__corner-grid td {
    border: none;
    padding: 0.6mm 1mm;
    background: transparent;
}

.pdf-workload-table__corner-top {
    text-align: right;
    font-size: 5.5pt;
    line-height: 1.15;
}

.pdf-workload-table__corner-line {
    border-top: 0.5pt solid #000;
    height: 0;
    padding: 0;
    line-height: 0;
    font-size: 0;
}

.pdf-workload-table__corner-bottom {
    text-align: left;
    font-size: 5.5pt;
    line-height: 1.15;
}

.pdf-workload-table__course-col {
    font-size: 6pt;
    line-height: 1.15;
}

.pdf-workload-table__year-note {
    font-size: 5.5pt;
    font-weight: normal;
}

.pdf-workload-table__value {
    white-space: nowrap;
    font-size: 6.5pt;
}

.pdf-summary-wrap {
    margin: 4mm 0;
    font-size: 8pt;
}

.pdf-summary dt {
    display: inline;
    font-weight: bold;
}

.pdf-summary dd {
    display: inline;
    margin: 0 4mm 0 1mm;
}

.ktp-doc-header {
    margin-bottom: 5mm;
}

.ktp-doc-header__center {
    width: 100%;
    text-align: center;
    margin: 0 auto 3mm;
}

.ktp-doc-header__org {
    text-align: center;
    font-size: 9pt;
    line-height: 1.35;
    margin: 0 0 4mm;
}

.ktp-doc-header__approve {
    width: 45%;
    margin: 0 0 4mm auto;
    text-align: right;
    font-size: 8.5pt;
}

.ktp-doc-header__approve-title {
    font-weight: bold;
    margin-bottom: 1mm;
}

.ktp-doc-header__approve-role {
    line-height: 1.3;
}

.ktp-doc-header__approve-sign {
    margin-top: 2mm;
    white-space: nowrap;
}

.ktp-doc-header__signature {
    margin-right: 3mm;
}

.ktp-doc-header__title {
    text-align: center;
    font-size: 11pt;
    font-weight: bold;
    margin: 0;
    text-transform: uppercase;
}

.ktp-doc-header__meta {
    margin: 0;
    font-size: 8.5pt;
}

.ktp-doc-header__meta-line {
    margin: 0 0 1.5mm;
    line-height: 1.35;
}
CSS;
}

function ktp_pdf_render_html(array $document, ?array $timesFonts = null): string
{
    $item = $document['item'];
    $topics = $document['topics'];
    $summary = $document['summary'];
    $isProfessionality = (bool) $document['is_professionality'];
    $header = build_ktp_document_header_context($item);
    $columnWidths = $document['column_widths'] ?? null;

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <style><?= ktp_pdf_styles($timesFonts ?? null) ?></style>
</head>
<body>
    <?php render_ktp_document_header($header, true); ?>

    <div class="pdf-summary-wrap">
        <h3>Сводка КТП</h3>
        <?php render_ktp_plan_summary_table($item, $topics, false, true); ?>
    </div>

    <table class="pdf-table<?= $columnWidths !== null ? ' pdf-table--custom-widths' : '' ?>">
        <thead>
            <tr>
                <th class="col-num"<?= ktp_column_width_attr($columnWidths, 0) ?>>№</th>
                <th<?= ktp_column_width_attr($columnWidths, 1) ?>>Тема</th>
                <th class="col-type"<?= ktp_column_width_attr($columnWidths, 2) ?>>Тип</th>
                <th class="col-hours"<?= ktp_column_width_attr($columnWidths, 3) ?>>Часы</th>
                <th class="col-deadline"<?= ktp_column_width_attr($columnWidths, 4) ?>>Сроки</th>
                <th<?= ktp_column_width_attr($columnWidths, 5) ?>>ОК / ПК</th>
                <th class="col-control"<?= ktp_column_width_attr($columnWidths, 6) ?>>Форма контроля</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topics as $index => $topic): ?>
            <?php $isSemesterMarker = ktp_is_semester_marker_type((string) ($topic['lesson_type'] ?? '')); ?>
            <tr>
                <td class="col-num"><?php
                    $topicNum = ktp_topic_display_number($topics, $index);
                    echo $topicNum !== null ? (int) $topicNum : '';
                ?></td>
                <?php if ($isSemesterMarker): ?>
                <td colspan="6"><strong><?= htmlspecialchars(ktp_semester_marker_title(), ENT_QUOTES, 'UTF-8') ?></strong></td>
                <?php else: ?>
                <td><?= htmlspecialchars((string) $topic['title'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="col-type"><?= htmlspecialchars(ktp_lesson_type_label((string) $topic['lesson_type']), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="col-hours"><?= htmlspecialchars(format_ktp_topic_hours($topic, $isProfessionality), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="col-deadline"><?= htmlspecialchars(format_ktp_deadline_date($topic['deadline_date'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?php
                    $ok = format_ktp_competency_codes_list($topic['ok_codes'] ?? null);
                    $pk = format_ktp_competency_codes_list($topic['pk_codes'] ?? null);
                    $parts = [];
                    if ($ok !== '') {
                        $parts[] = 'ОК: ' . $ok;
                    }
                    if ($pk !== '') {
                        $parts[] = 'ПК: ' . $pk;
                    }
                    echo htmlspecialchars($parts !== [] ? implode('; ', $parts) : '—', ENT_QUOTES, 'UTF-8');
                ?></td>
                <td class="col-control"><?= htmlspecialchars(ktp_control_form_label($topic['control_form'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
    <?php
    return (string) ob_get_clean();
}

function stream_ktp_pdf(int $itemId): void
{
    $document = ktp_pdf_build_document($itemId);
    if ($document === null) {
        http_response_code(404);
        exit('КТП для экспорта не найдено.');
    }

    $timesFonts = ktp_pdf_resolve_times_fonts();
    $html = ktp_pdf_render_html($document, $timesFonts);
    $pdfOptions = ['defaultFont' => 'Times New Roman'];
    if ($timesFonts !== null) {
        $pdfOptions['chroot'] = [dirname($timesFonts['normal'])];
    }
    $pdf = render_html_to_pdf($html, 'landscape', $pdfOptions);
    $filename = ktp_pdf_filename($document);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) strlen($pdf));
    header('Cache-Control: private, max-age=0, must-revalidate');

    echo $pdf;
    exit;
}
