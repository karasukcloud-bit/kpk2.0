<?php

declare(strict_types=1);

require_once __DIR__ . '/../archive.php';
require_once __DIR__ . '/../organization.php';
require_once __DIR__ . '/../students.php';
require_once __DIR__ . '/../curriculum.php';
require_once __DIR__ . '/../gradebook.php';
require_once __DIR__ . '/../pdf.php';

const ARCHIVE_GRADEBOOK_PDF_SUBJECTS_PER_PAGE = 9;

function archive_gradebook_pdf_build_document(int $archiveId, int $groupId): ?array
{
    $archive = get_archive_period_by_id($archiveId);
    if ($archive === null || $archive['archive_type'] !== 'gradebook') {
        return null;
    }

    $group = get_archive_gradebook_group($archiveId, $groupId);
    if ($group === null) {
        return null;
    }

    $sheet = get_archive_gradebook_sheet($archiveId, $groupId);
    if ($sheet['students'] === [] || $sheet['subjects'] === []) {
        return null;
    }

    $studentsForSummary = array_map(
        static fn (array $student): array => ['id' => (int) $student['student_id']] + $student,
        $sheet['students']
    );
    $summary = build_gradebook_summary($studentsForSummary, $sheet['subjects'], $sheet['grades']);

    return [
        'archive' => $archive,
        'org' => get_organization(),
        'group' => $group,
        'sheet' => $sheet,
        'summary' => $summary,
    ];
}

function archive_gradebook_pdf_filename(array $document): string
{
    $group = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', (string) $document['group']['group_number']) ?: 'group';
    $year = str_replace('/', '-', (string) $document['archive']['academic_year']);
    $semester = (string) $document['archive']['semester'];

    return 'gradebook_' . $group . '_' . $year . '_sem' . $semester . '.pdf';
}

function stream_archive_gradebook_pdf(int $archiveId, int $groupId): void
{
    $document = archive_gradebook_pdf_build_document($archiveId, $groupId);
    if ($document === null) {
        http_response_code(404);
        exit('Ведомость для экспорта не найдена.');
    }

    $html = archive_gradebook_pdf_render_html($document);
    $pdf = render_html_to_pdf($html, 'landscape');
    $filename = archive_gradebook_pdf_filename($document);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) strlen($pdf));
    header('Cache-Control: private, max-age=0, must-revalidate');

    echo $pdf;
    exit;
}

function archive_gradebook_pdf_styles(): string
{
    return <<<'CSS'
@page landscape { size: A4 landscape; margin: 10mm 8mm 12mm; }

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9pt;
    color: #111;
    line-height: 1.35;
}

.pdf-page { page-break-after: always; page: landscape; }
.pdf-page:last-child { page-break-after: auto; }

.pdf-header-title {
    font-size: 14pt;
    font-weight: bold;
    margin: 0 0 3mm;
    text-align: center;
}
.pdf-header-meta {
    font-size: 9pt;
    color: #333;
    margin: 0 0 1.5mm;
    text-align: center;
}
.pdf-header-meta--curator {
    margin-bottom: 5mm;
}
.pdf-meta {
    font-size: 8.5pt;
    color: #444;
    margin: 0 0 4mm;
    text-align: center;
}
.pdf-stats {
    width: 100%;
    margin: 0 0 5mm;
    font-size: 8.5pt;
    border-collapse: collapse;
}
.pdf-stats td {
    padding: 2mm 3mm;
    text-align: center;
    border: 0.5pt solid #333;
    vertical-align: middle;
}
.pdf-stats__label {
    display: block;
    font-size: 7.5pt;
    color: #555;
    margin-bottom: 0.5mm;
}
.pdf-stats__value {
    display: block;
    font-size: 10pt;
    font-weight: bold;
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
.pdf-table__student {
    width: 28mm;
    text-align: left;
    font-size: 7pt;
}
.pdf-table__grade {
    text-align: center;
}
CSS;
}

function archive_gradebook_pdf_render_stats(array $summary): string
{
    $assessed = (int) ($summary['assessed_students'] ?? 0);
    $total = (int) ($summary['total_students'] ?? 0);
    $absolutePercent = (float) ($summary['absolute_percent'] ?? 0);
    $qualityPercent = (float) ($summary['quality_percent'] ?? 0);
    $absoluteCount = (int) ($summary['absolute_count'] ?? 0);
    $qualityCount = (int) ($summary['quality_count'] ?? 0);

    $html = '<table class="pdf-stats"><tr>';
    $html .= '<td><span class="pdf-stats__label">Оценено студентов</span>'
        . '<span class="pdf-stats__value">' . $assessed . ' из ' . $total . '</span></td>';
    $html .= '<td><span class="pdf-stats__label">Абсолютная успеваемость</span>'
        . '<span class="pdf-stats__value">' . e((string) $absolutePercent) . '%</span>'
        . '<span class="pdf-stats__label">(' . $absoluteCount . ' из ' . $assessed . ')</span></td>';
    $html .= '<td><span class="pdf-stats__label">Качественная успеваемость</span>'
        . '<span class="pdf-stats__value">' . e((string) $qualityPercent) . '%</span>'
        . '<span class="pdf-stats__label">(' . $qualityCount . ' из ' . $assessed . ')</span></td>';
    $html .= '</tr></table>';

    return $html;
}

function archive_gradebook_pdf_render_html(array $document): string
{
    $archive = $document['archive'];
    $group = $document['group'];
    $sheet = $document['sheet'];
    $summary = $document['summary'];
    $orgName = trim((string) ($document['org']['name'] ?? ''));
    $groupNumber = e((string) $group['group_number']);
    $year = e((string) $archive['academic_year']);
    $semester = e(semester_label((string) $archive['semester']));
    $specialty = trim((string) ($group['specialty_name'] ?? ''));
    $curator = trim((string) ($group['curator_name'] ?? ''));

    $students = $sheet['students'];
    $subjects = $sheet['subjects'];
    $grades = $sheet['grades'];
    $subjectChunks = array_chunk($subjects, ARCHIVE_GRADEBOOK_PDF_SUBJECTS_PER_PAGE);
    $html = '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><style>';
    $html .= archive_gradebook_pdf_styles();
    $html .= '</style></head><body>';

    foreach ($subjectChunks as $chunkIndex => $subjectChunk) {
        $pageNum = $chunkIndex + 1;
        $pageTotal = count($subjectChunks);
        $html .= '<div class="pdf-page">';

        if ($chunkIndex === 0) {
            if ($orgName !== '') {
                $html .= '<p class="pdf-header-meta">' . e($orgName) . '</p>';
            }
            $html .= '<h1 class="pdf-header-title">Ведомость успеваемости учебной группы № '
                . $groupNumber . ' за ' . $year . ' учебный год</h1>';
            $html .= '<p class="pdf-header-meta">' . $semester;
            if ($specialty !== '') {
                $html .= ' · ' . e($specialty);
            }
            $html .= '</p>';
            if ($curator !== '') {
                $html .= '<p class="pdf-header-meta pdf-header-meta--curator">Куратор: ' . e($curator) . '</p>';
            }
            $html .= archive_gradebook_pdf_render_stats($summary);
        } else {
            $html .= '<p class="pdf-meta">Ведомость успеваемости · группа № ' . $groupNumber
                . ' · стр. ' . $pageNum . ' из ' . $pageTotal . '</p>';
        }

        if ($pageTotal > 1 && $chunkIndex === 0) {
            $html .= '<p class="pdf-meta">Страница ' . $pageNum . ' из ' . $pageTotal . '</p>';
        }

        $html .= '<table class="pdf-table"><thead><tr>';
        $html .= '<th class="pdf-table__student">Студент</th>';
        foreach ($subjectChunk as $subject) {
            $html .= '<th>' . e((string) $subject['subject_name']) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($students as $student) {
            $studentId = (int) $student['student_id'];
            $html .= '<tr><td class="pdf-table__student">'
                . e(person_last_first_name((string) $student['full_name'])) . '</td>';
            foreach ($subjectChunk as $subject) {
                $itemId = (int) $subject['curriculum_item_id'];
                $value = $grades[$studentId][$itemId] ?? null;
                $html .= '<td class="pdf-table__grade">'
                    . e($value !== null ? (string) $value : '—') . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
    }

    $html .= '</body></html>';

    return $html;
}
