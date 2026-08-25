<?php

declare(strict_types=1);

require_once __DIR__ . '/../archive.php';
require_once __DIR__ . '/../organization.php';
require_once __DIR__ . '/../journal.php';
require_once __DIR__ . '/../ktp.php';
require_once __DIR__ . '/../students.php';
require_once __DIR__ . '/../curriculum.php';
require_once __DIR__ . '/../pdf.php';

const ARCHIVE_JOURNAL_PDF_LESSONS_PER_PAGE = 12;
const ARCHIVE_JOURNAL_PDF_SUBJECTS_PER_PAGE = 9;

function archive_journal_pdf_build_document(int $archiveId, int $groupId): ?array
{
    $archive = get_archive_period_by_id($archiveId);
    if ($archive === null || $archive['archive_type'] !== 'journal') {
        return null;
    }

    $items = get_archive_journal_items($archiveId, $groupId);
    if ($items === []) {
        return null;
    }

    $subjects = [];
    foreach ($items as $item) {
        $subjects[] = [
            'item' => $item,
            'sheet' => get_archive_journal_sheet((int) $item['id']),
        ];
    }

    $gradebookArchive = get_archive_period(
        'gradebook',
        (string) $archive['academic_year'],
        (string) $archive['semester']
    );
    $gradebookGroup = $gradebookArchive
        ? get_archive_gradebook_group((int) $gradebookArchive['id'], $groupId)
        : null;
    $gradebookSheet = $gradebookGroup
        ? get_archive_gradebook_sheet((int) $gradebookArchive['id'], $groupId)
        : null;

    return [
        'archive' => $archive,
        'org' => get_organization(),
        'group_number' => (string) $items[0]['group_number'],
        'group_meta' => $gradebookGroup,
        'subjects' => $subjects,
        'gradebook' => $gradebookSheet,
    ];
}

function archive_journal_pdf_filename(array $document): string
{
    $group = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', $document['group_number']) ?: 'group';
    $year = str_replace('/', '-', (string) $document['archive']['academic_year']);
    $semester = (string) $document['archive']['semester'];

    return 'journal_' . $group . '_' . $year . '_sem' . $semester . '.pdf';
}

function stream_archive_journal_pdf(int $archiveId, int $groupId): void
{
    $document = archive_journal_pdf_build_document($archiveId, $groupId);
    if ($document === null) {
        http_response_code(404);
        exit('Журнал для экспорта не найден.');
    }

    $html = archive_journal_pdf_render_html($document);
    $pdf = render_html_to_pdf($html);
    $filename = archive_journal_pdf_filename($document);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string) strlen($pdf));
    header('Cache-Control: private, max-age=0, must-revalidate');

    echo $pdf;
    exit;
}

function archive_journal_pdf_styles(): string
{
    return <<<'CSS'
@page { margin: 12mm 10mm 14mm; }
@page landscape { size: A4 landscape; margin: 10mm 8mm 12mm; }

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9pt;
    color: #111;
    line-height: 1.35;
}

.pdf-page { page-break-after: always; }
.pdf-page:last-child { page-break-after: auto; }
.pdf-landscape { page: landscape; }

.pdf-title {
    display: table;
    width: 100%;
    height: 250mm;
    text-align: center;
}
.pdf-title__inner {
    display: table-cell;
    vertical-align: middle;
}
.pdf-title__org {
    font-size: 11pt;
    margin-bottom: 18mm;
}
.pdf-title__main {
    font-size: 22pt;
    font-weight: bold;
    letter-spacing: 0.5pt;
    margin: 0 0 8mm;
    text-transform: uppercase;
}
.pdf-title__group {
    font-size: 16pt;
    margin: 0 0 6mm;
}
.pdf-title__year {
    font-size: 13pt;
    margin: 0 0 4mm;
}
.pdf-title__meta {
    font-size: 10pt;
    color: #333;
    margin-top: 3mm;
}

.pdf-section-title {
    font-size: 13pt;
    font-weight: bold;
    margin: 0 0 4mm;
    padding-bottom: 2mm;
    border-bottom: 1px solid #333;
}
.pdf-subsection-title {
    font-size: 11pt;
    font-weight: bold;
    margin: 0 0 3mm;
}
.pdf-meta {
    font-size: 8.5pt;
    color: #444;
    margin: 0 0 4mm;
}

.pdf-toc { margin-top: 6mm; }
.pdf-toc ol { margin: 0; padding-left: 6mm; }
.pdf-toc li { margin: 2mm 0; }

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
.pdf-table__total {
    width: 8mm;
    text-align: center;
    font-weight: bold;
}
.pdf-table__lesson {
    text-align: center;
    font-size: 6.5pt;
}
.pdf-lesson-date { display: block; font-weight: bold; }
.pdf-lesson-type { display: block; font-size: 6pt; color: #555; }
.pdf-mark-muted { font-size: 6pt; color: #666; }

.pdf-summary {
    margin-top: 4mm;
    font-size: 8pt;
}
.pdf-summary dt,
.pdf-summary dd {
    display: inline;
    margin: 0;
}
.pdf-summary dt::after { content: ': '; }
.pdf-summary dd::after { content: ' · '; }
.pdf-summary dd:last-child::after { content: ''; }

.pdf-footer-note {
    margin-top: 3mm;
    font-size: 7pt;
    color: #666;
}
CSS;
}

function archive_journal_pdf_render_html(array $document): string
{
    $parts = [
        '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><style>',
        archive_journal_pdf_styles(),
        '</style></head><body>',
        archive_journal_pdf_render_title($document),
        archive_journal_pdf_render_toc($document),
    ];

    foreach ($document['subjects'] as $index => $subjectBlock) {
        $parts[] = archive_journal_pdf_render_subject_journal(
            $subjectBlock['item'],
            $subjectBlock['sheet'],
            $index + 1
        );
        $parts[] = archive_journal_pdf_render_subject_material(
            $subjectBlock['item'],
            $subjectBlock['sheet'],
            $index + 1
        );
    }

    $parts[] = archive_journal_pdf_render_gradebook($document);
    $parts[] = '</body></html>';

    return implode('', $parts);
}

function archive_journal_pdf_render_title(array $document): string
{
    $orgName = trim((string) ($document['org']['name'] ?? ''));
    $groupNumber = e($document['group_number']);
    $year = e((string) $document['archive']['academic_year']);
    $semester = e(semester_label((string) $document['archive']['semester']));
    $specialty = trim((string) ($document['group_meta']['specialty_name'] ?? ''));
    $curator = trim((string) ($document['group_meta']['curator_name'] ?? ''));

    $html = '<div class="pdf-page pdf-title" id="title">';
    $html .= '<div class="pdf-title__inner">';
    if ($orgName !== '') {
        $html .= '<p class="pdf-title__org">' . e($orgName) . '</p>';
    }
    $html .= '<h1 class="pdf-title__main">Журнал</h1>';
    $html .= '<p class="pdf-title__group">учебной группы № ' . $groupNumber . '</p>';
    $html .= '<p class="pdf-title__year">за ' . $year . ' учебный год</p>';
    $html .= '<p class="pdf-title__meta">' . $semester . '</p>';
    if ($specialty !== '') {
        $html .= '<p class="pdf-title__meta">Специальность: ' . e($specialty) . '</p>';
    }
    if ($curator !== '') {
        $html .= '<p class="pdf-title__meta">Куратор: ' . e($curator) . '</p>';
    }
    $html .= '</div></div>';

    return $html;
}

function archive_journal_pdf_render_toc(array $document): string
{
    $html = '<div class="pdf-page" id="toc"><h2 class="pdf-section-title">Содержание</h2><div class="pdf-toc"><ol>';
    $html .= '<li>Титульный лист</li>';

    foreach ($document['subjects'] as $index => $subjectBlock) {
        $num = $index + 1;
        $name = e((string) $subjectBlock['item']['subject_name']);
        $html .= '<li>' . $num . '. ' . $name;
        $html .= '<ol><li>Журнал успеваемости</li><li>Пройденный материал</li></ol></li>';
    }

    $html .= '<li>Ведомость успеваемости за семестр</li>';
    $html .= '</ol></div></div>';

    return $html;
}

function archive_journal_pdf_render_subject_journal(array $item, array $sheet, int $subjectIndex): string
{
    $students = $sheet['students'];
    $lessons = $sheet['lessons'];
    $grades = $sheet['grades'];
    $totals = $sheet['totals'];
    $subjectName = e((string) $item['subject_name']);
    $teacher = trim((string) ($item['teacher_name'] ?? ''));
    $anchor = 'subject-' . $subjectIndex . '-journal';

    if ($students === []) {
        return '<div class="pdf-page" id="' . $anchor . '"><h2 class="pdf-section-title">'
            . $subjectIndex . '. ' . $subjectName . ' — журнал</h2>'
            . '<p class="pdf-meta">Нет данных о студентах.</p></div>';
    }

    if ($lessons === []) {
        return '<div class="pdf-page" id="' . $anchor . '"><h2 class="pdf-section-title">'
            . $subjectIndex . '. ' . $subjectName . ' — журнал</h2>'
            . '<p class="pdf-meta">Нет занятий в архиве.</p></div>';
    }

    $chunks = array_chunk($lessons, ARCHIVE_JOURNAL_PDF_LESSONS_PER_PAGE);
    $html = '';

    foreach ($chunks as $chunkIndex => $lessonChunk) {
        $pageNum = $chunkIndex + 1;
        $pageTotal = count($chunks);
        $html .= '<div class="pdf-page pdf-landscape" id="' . ($chunkIndex === 0 ? $anchor : '') . '">';
        $html .= '<h2 class="pdf-section-title">' . $subjectIndex . '. ' . $subjectName . ' — журнал</h2>';
        if ($teacher !== '') {
            $html .= '<p class="pdf-meta">Преподаватель: ' . e($teacher) . '</p>';
        }
        if ($pageTotal > 1) {
            $html .= '<p class="pdf-meta">Страница журнала ' . $pageNum . ' из ' . $pageTotal . '</p>';
        }
        $html .= '<table class="pdf-table"><thead><tr>';
        $html .= '<th class="pdf-table__student">Студент</th>';
        foreach ($lessonChunk as $lesson) {
            $gradeType = (string) ($lesson['grade_type'] ?? 'current');
            $html .= '<th class="pdf-table__lesson">';
            $html .= '<span class="pdf-lesson-date">' . e(format_journal_date((string) $lesson['lesson_date'])) . '</span>';
            $html .= '<span class="pdf-lesson-type">' . e(journal_grade_type_short($gradeType)) . '</span>';
            $html .= '</th>';
        }
        $html .= '<th class="pdf-table__total">Итог</th></tr></thead><tbody>';

        foreach ($students as $student) {
            $studentId = (int) $student['student_id'];
            $html .= '<tr><td class="pdf-table__student">' . e(person_last_first_name((string) $student['full_name'])) . '</td>';
            foreach ($lessonChunk as $lesson) {
                $lessonId = (int) $lesson['id'];
                $entry = $grades[$studentId][$lessonId] ?? empty_journal_entry();
                $mark = render_journal_mark_label((string) ($entry['mark'] ?? ''));
                $html .= '<td class="pdf-table__lesson">' . e($mark);
                if (!empty($entry['activity'])) {
                    $html .= ' <span class="pdf-mark-muted">акт</span>';
                }
                if (!empty($entry['late'])) {
                    $html .= ' <span class="pdf-mark-muted">оп</span>';
                }
                $html .= '</td>';
            }
            $final = $totals[$studentId]['final_grade'] ?? null;
            $html .= '<td class="pdf-table__total">' . e($final !== null && $final !== '' ? (string) $final : '—') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
    }

    return $html;
}

function archive_journal_pdf_render_subject_material(array $item, array $sheet, int $subjectIndex): string
{
    $lessons = $sheet['lessons'];
    $subjectName = e((string) $item['subject_name']);
    $anchor = 'subject-' . $subjectIndex . '-material';
    $html = '<div class="pdf-page" id="' . $anchor . '">';
    $html .= '<h2 class="pdf-section-title">' . $subjectIndex . '. ' . $subjectName . ' — пройденный материал</h2>';

    if ($lessons === []) {
        $html .= '<p class="pdf-meta">Нет пройденных занятий.</p></div>';
        return $html;
    }

    $html .= '<table class="pdf-table"><thead><tr>';
    $html .= '<th style="width:18mm">Дата</th><th>Тема</th><th style="width:22mm">Тип оценки</th><th style="width:22mm">Тип урока</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($lessons as $lesson) {
        $topic = trim((string) ($lesson['topic_title'] ?? ''));
        $lessonType = trim((string) ($lesson['topic_lesson_type'] ?? ''));
        $html .= '<tr>';
        $html .= '<td>' . e(date('d.m.Y', (int) strtotime((string) $lesson['lesson_date']))) . '</td>';
        $html .= '<td>' . e($topic !== '' ? $topic : '—') . '</td>';
        $html .= '<td>' . e(journal_grade_type_label((string) ($lesson['grade_type'] ?? 'current'))) . '</td>';
        $html .= '<td>' . e($lessonType !== '' ? ktp_lesson_type_label($lessonType) : '—') . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    $summary = build_covered_material_summary($lessons);
    $html .= '<div class="pdf-summary"><dl>';
    $html .= '<dt>Уроков</dt><dd>' . (int) $summary['total_lessons'] . '</dd>';
    $html .= '<dt>Часов лекций</dt><dd>' . e((string) $summary['lecture_hours']) . '</dd>';
    $html .= '<dt>Часов практики</dt><dd>' . e((string) $summary['practice_hours']) . '</dd>';
    $html .= '<dt>Часов всего</dt><dd>' . e((string) $summary['total_hours']) . '</dd>';
    $html .= '</dl></div></div>';

    return $html;
}

function archive_journal_pdf_render_gradebook(array $document): string
{
    $sheet = $document['gradebook'];
    $groupNumber = e($document['group_number']);
    $year = e((string) $document['archive']['academic_year']);
    $semester = e(semester_label((string) $document['archive']['semester']));
    $specialty = trim((string) ($document['group_meta']['specialty_name'] ?? ''));

    if ($sheet === null || $sheet['students'] === [] || $sheet['subjects'] === []) {
        return '<div class="pdf-page" id="gradebook"><h2 class="pdf-section-title">Ведомость успеваемости</h2>'
            . '<p class="pdf-meta">Ведомость за этот семестр не архивирована.</p></div>';
    }

    $students = $sheet['students'];
    $subjects = $sheet['subjects'];
    $grades = $sheet['grades'];
    $subjectChunks = array_chunk($subjects, ARCHIVE_JOURNAL_PDF_SUBJECTS_PER_PAGE);
    $html = '';

    foreach ($subjectChunks as $chunkIndex => $subjectChunk) {
        $pageNum = $chunkIndex + 1;
        $pageTotal = count($subjectChunks);
        $html .= '<div class="pdf-page pdf-landscape" id="' . ($chunkIndex === 0 ? 'gradebook' : '') . '">';
        $html .= '<h2 class="pdf-section-title">Ведомость успеваемости</h2>';
        $html .= '<p class="pdf-meta">Группа № ' . $groupNumber . ' · ' . $year . ' · ' . $semester;
        if ($specialty !== '') {
            $html .= ' · ' . e($specialty);
        }
        $html .= '</p>';
        if ($pageTotal > 1) {
            $html .= '<p class="pdf-meta">Страница ' . $pageNum . ' из ' . $pageTotal . '</p>';
        }

        $html .= '<table class="pdf-table"><thead><tr>';
        $html .= '<th class="pdf-table__student">Студент</th>';
        foreach ($subjectChunk as $subject) {
            $html .= '<th class="pdf-table__lesson">' . e((string) $subject['subject_name']) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($students as $student) {
            $studentId = (int) $student['student_id'];
            $html .= '<tr><td class="pdf-table__student">' . e(person_last_first_name((string) $student['full_name'])) . '</td>';
            foreach ($subjectChunk as $subject) {
                $itemId = (int) $subject['curriculum_item_id'];
                $value = $grades[$studentId][$itemId] ?? null;
                $html .= '<td class="pdf-table__lesson">' . e($value !== null && $value !== '' ? (string) $value : '—') . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<p class="pdf-footer-note">Итоговые оценки за семестр по архивной ведомости.</p>';
        $html .= '</div>';
    }

    return $html;
}
