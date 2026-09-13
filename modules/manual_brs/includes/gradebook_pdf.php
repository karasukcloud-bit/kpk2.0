<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/pdf.php';

const MANUAL_BRS_GRADEBOOK_PDF_SUBJECTS_PER_PAGE = 9;

function manual_brs_gradebook_pdf_build_document(
    int $groupId,
    string $academicYear,
    string $semester
): ?array {
    $group = get_group_by_id($groupId);
    if ($group === null) {
        return null;
    }

    $students = get_students_by_group($groupId);
    $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
    if ($students === [] || $subjects === []) {
        return null;
    }

    $grades = manual_brs_get_gradebook_grades($groupId, $academicYear, $semester);
    $summary = build_gradebook_summary($students, $subjects, $grades);
    $isControlWeek = manual_brs_is_control_week($groupId, $academicYear, $semester, $subjects);

    return [
        'org' => get_organization(),
        'group' => $group,
        'academic_year' => $academicYear,
        'semester' => $semester,
        'students' => $students,
        'subjects' => $subjects,
        'grades' => $grades,
        'summary' => $summary,
        'is_control_week' => $isControlWeek,
        'title' => manual_brs_gradebook_title(
            (string) $group['number'],
            $semester,
            $academicYear
        ),
    ];
}

function manual_brs_gradebook_pdf_filename(array $document): string
{
    $group = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', (string) $document['group']['number']) ?: 'group';
    $year = str_replace('/', '-', (string) $document['academic_year']);
    $semester = (string) $document['semester'];
    $suffix = !empty($document['is_control_week']) ? '_control' : '';

    return 'manual_brs_gradebook_' . $group . '_' . $year . '_sem' . $semester . $suffix . '.pdf';
}

function stream_manual_brs_gradebook_pdf(int $groupId, string $academicYear, string $semester): void
{
    $document = manual_brs_gradebook_pdf_build_document($groupId, $academicYear, $semester);
    if ($document === null) {
        http_response_code(404);
        exit('Ведомость для экспорта не найдена.');
    }

    $html = manual_brs_gradebook_pdf_render_html($document);
    $pdf = render_html_to_pdf($html, 'landscape');
    $filename = manual_brs_gradebook_pdf_filename($document);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) strlen($pdf));
    header('Cache-Control: private, max-age=0, must-revalidate');

    echo $pdf;
    exit;
}

function manual_brs_gradebook_pdf_styles(): string
{
    return <<<'CSS'
@page landscape { size: A4 landscape; margin: 10mm 8mm 12mm; }

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10px;
    color: #111;
}

.pdf-page {
    page-break-after: always;
}

.pdf-page:last-child {
    page-break-after: auto;
}

.pdf-header-title {
    margin: 0 0 4px;
    font-size: 14px;
    text-align: center;
}

.pdf-header-meta {
    margin: 0 0 3px;
    text-align: center;
    color: #333;
}

.pdf-header-subtitle {
    margin: 2px 0 8px;
    text-align: center;
    font-weight: 700;
    font-size: 12px;
}

.pdf-meta {
    margin: 0 0 6px;
    color: #555;
}

.pdf-stats {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 10px;
}

.pdf-stats td {
    width: 33.33%;
    vertical-align: top;
    padding: 4px 6px;
    border: 1px solid #ccc;
}

.pdf-stats__label {
    display: block;
    color: #555;
    font-size: 9px;
}

.pdf-stats__value {
    display: block;
    font-weight: 700;
    font-size: 12px;
    margin-top: 2px;
}

.pdf-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.pdf-table th,
.pdf-table td {
    border: 1px solid #333;
    padding: 3px 4px;
    vertical-align: middle;
}

.pdf-table th {
    font-size: 8px;
    font-weight: 700;
    text-align: center;
    word-wrap: break-word;
}

.pdf-table__student {
    width: 140px;
    text-align: left;
    font-size: 9px;
}

.pdf-table__grade {
    text-align: center;
    font-weight: 700;
}
CSS;
}

function manual_brs_gradebook_pdf_render_stats(array $summary): string
{
    $filled = (int) ($summary['filled_grades'] ?? 0);
    $expected = (int) ($summary['expected_grades'] ?? 0);
    $assessed = (int) ($summary['assessed_students'] ?? 0);
    $absolutePercent = (float) ($summary['absolute_percent'] ?? 0);
    $qualityPercent = (float) ($summary['quality_percent'] ?? 0);
    $absoluteCount = (int) ($summary['absolute_count'] ?? 0);
    $qualityCount = (int) ($summary['quality_count'] ?? 0);

    $html = '<table class="pdf-stats"><tr>';
    $html .= '<td><span class="pdf-stats__label">Выставлено оценок</span>'
        . '<span class="pdf-stats__value">' . $filled . ' из ' . $expected . '</span></td>';
    $html .= '<td><span class="pdf-stats__label">Абсолютная успеваемость</span>'
        . '<span class="pdf-stats__value">' . e((string) $absolutePercent) . '%</span>'
        . '<span class="pdf-stats__label">(' . $absoluteCount . ' из ' . $assessed . ')</span></td>';
    $html .= '<td><span class="pdf-stats__label">Качественная успеваемость</span>'
        . '<span class="pdf-stats__value">' . e((string) $qualityPercent) . '%</span>'
        . '<span class="pdf-stats__label">(' . $qualityCount . ' из ' . $assessed . ')</span></td>';
    $html .= '</tr></table>';

    return $html;
}

function manual_brs_gradebook_pdf_render_html(array $document): string
{
    $group = $document['group'];
    $students = $document['students'];
    $subjects = $document['subjects'];
    $grades = $document['grades'];
    $summary = $document['summary'];
    $orgName = trim((string) ($document['org']['name'] ?? ''));
    $groupNumber = e((string) $group['number']);
    $title = e((string) $document['title']);
    $specialty = trim((string) ($group['specialty_name'] ?? ''));
    $curator = trim((string) ($group['curator_name'] ?? ''));
    $isControlWeek = !empty($document['is_control_week']);

    $subjectChunks = array_chunk($subjects, MANUAL_BRS_GRADEBOOK_PDF_SUBJECTS_PER_PAGE);
    $html = '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><style>';
    $html .= manual_brs_gradebook_pdf_styles();
    $html .= '</style></head><body>';

    foreach ($subjectChunks as $chunkIndex => $subjectChunk) {
        $pageNum = $chunkIndex + 1;
        $pageTotal = count($subjectChunks);
        $html .= '<div class="pdf-page">';

        if ($chunkIndex === 0) {
            if ($orgName !== '') {
                $html .= '<p class="pdf-header-meta">' . e($orgName) . '</p>';
            }
            $html .= '<h1 class="pdf-header-title">' . $title . '</h1>';
            if ($isControlWeek) {
                $html .= '<p class="pdf-header-subtitle">Контрольная неделя</p>';
            }
            if ($specialty !== '') {
                $html .= '<p class="pdf-header-meta">' . e($specialty) . '</p>';
            }
            if ($curator !== '') {
                $html .= '<p class="pdf-header-meta">Куратор: ' . e($curator) . '</p>';
            }
            $html .= manual_brs_gradebook_pdf_render_stats($summary);
        } else {
            $html .= '<p class="pdf-meta">' . $title
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
            $studentId = (int) $student['id'];
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
