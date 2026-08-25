<?php

declare(strict_types=1);

require_once __DIR__ . '/pdf.php';
require_once __DIR__ . '/record_book.php';
require_once __DIR__ . '/courseworks.php';
require_once __DIR__ . '/practices.php';
require_once __DIR__ . '/gia.php';
require_once __DIR__ . '/students.php';
require_once __DIR__ . '/organization.php';
require_once __DIR__ . '/curriculum.php';

function build_record_book_pdf_document(int $studentId): ?array
{
    $student = get_student_by_id($studentId);
    if ($student === null) {
        return null;
    }

    $group = get_group_by_id((int) $student['group_id']);
    $org = get_organization();
    $periods = get_student_record_book($studentId);
    usort($periods, static function (array $a, array $b): int {
        $yearCmp = strcmp((string) $a['academic_year'], (string) $b['academic_year']);
        if ($yearCmp !== 0) {
            return $yearCmp;
        }

        return strcmp((string) $a['semester'], (string) $b['semester']);
    });

    return [
        'student' => $student,
        'group' => $group,
        'organization' => $org,
        'periods' => $periods,
        'courseworks' => get_student_courseworks($studentId),
        'practices' => get_student_practices($studentId),
        'gia' => get_student_gia($studentId),
    ];
}

function record_book_pdf_filename(array $document): string
{
    $name = preg_replace('/[^\p{L}\p{N}_\-]+/u', '_', (string) ($document['student']['full_name'] ?? 'student')) ?: 'student';
    $group = preg_replace('/[^\p{L}\p{N}_\-]+/u', '_', (string) ($document['group']['number'] ?? '')) ?: 'group';

    return 'zachetka_' . $group . '_' . $name . '.pdf';
}

function stream_record_book_pdf(int $studentId): void
{
    $document = build_record_book_pdf_document($studentId);
    if ($document === null) {
        http_response_code(404);
        exit('Студент не найден.');
    }

    $html = record_book_pdf_render_html($document);
    $pdf = render_html_to_pdf($html, 'portrait');
    $filename = record_book_pdf_filename($document);

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . (string) strlen($pdf));
    echo $pdf;
    exit;
}

function record_book_pdf_dash(?string $value): string
{
    $value = trim((string) $value);

    return $value !== '' ? e($value) : '—';
}

function record_book_pdf_grade($grade): string
{
    if ($grade === null || $grade === '') {
        return '—';
    }

    return e((string) (int) $grade);
}

function record_book_pdf_styles(): string
{
    return <<<'CSS'
<style>
@page { margin: 12mm 10mm 12mm 10mm; }
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9pt;
    color: #111;
    line-height: 1.35;
}
.rb-header {
    text-align: center;
    margin-bottom: 4mm;
    border-bottom: 1.5pt solid #111;
    padding-bottom: 3mm;
}
.rb-header__ministry {
    font-size: 8pt;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
    margin: 0 0 1.5mm;
}
.rb-header__org {
    font-size: 8.5pt;
    margin: 0 0 2.5mm;
}
.rb-header__title {
    font-size: 14pt;
    font-weight: bold;
    text-transform: uppercase;
    margin: 0 0 2mm;
}
.rb-header__subtitle {
    font-size: 8pt;
    margin: 0;
    color: #333;
}
.rb-student {
    width: 100%;
    border-collapse: collapse;
    margin: 3mm 0 5mm;
}
.rb-student td {
    border: 0.6pt solid #222;
    padding: 1.6mm 2mm;
    vertical-align: top;
}
.rb-student__label {
    width: 32%;
    background: #f3f3f3;
    font-weight: bold;
    font-size: 8pt;
}
.rb-section {
    margin-top: 4.5mm;
    page-break-inside: avoid;
}
.rb-section__title {
    font-size: 10.5pt;
    font-weight: bold;
    margin: 0 0 1.5mm;
    text-align: center;
    text-transform: uppercase;
}
.rb-section__sub {
    font-size: 9pt;
    font-weight: bold;
    margin: 2.5mm 0 1mm;
}
.rb-meta {
    font-size: 8pt;
    color: #444;
    margin: 0 0 1.5mm;
    text-align: center;
}
.rb-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-bottom: 2mm;
}
.rb-table th,
.rb-table td {
    border: 0.6pt solid #222;
    padding: 1.3mm 1.5mm;
    vertical-align: top;
    word-wrap: break-word;
}
.rb-table th {
    background: #efefef;
    font-size: 7.5pt;
    font-weight: bold;
    text-align: center;
}
.rb-table td {
    font-size: 8pt;
}
.rb-table__num {
    width: 8mm;
    text-align: center;
}
.rb-table__grade {
    width: 14mm;
    text-align: center;
    font-weight: bold;
}
.rb-table__date {
    width: 22mm;
    text-align: center;
}
.rb-table__code {
    width: 18mm;
    text-align: center;
}
.rb-table__points {
    width: 20mm;
    text-align: center;
}
.rb-empty {
    font-size: 8pt;
    color: #555;
    margin: 0 0 2mm;
    font-style: italic;
}
.rb-footer {
    margin-top: 6mm;
    font-size: 7.5pt;
    color: #555;
    border-top: 0.4pt solid #999;
    padding-top: 2mm;
}
</style>
CSS;
}

function record_book_pdf_render_html(array $document): string
{
    $student = $document['student'];
    $group = $document['group'] ?? null;
    $org = $document['organization'] ?? [];
    $orgName = trim((string) ($org['name'] ?? ''));
    $fullName = (string) ($student['full_name'] ?? '');
    $groupNumber = (string) ($group['number'] ?? ($student['group_number'] ?? '—'));
    $specialtyCode = (string) ($group['specialty_code'] ?? '');
    $specialtyName = (string) ($group['specialty_name'] ?? '');
    $specialty = trim($specialtyCode . ($specialtyCode !== '' && $specialtyName !== '' ? ' — ' : '') . $specialtyName);
    $birth = format_student_birth_date($student['birth_date'] ?? null);

    $html = '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8">'
        . record_book_pdf_styles()
        . '</head><body>';

    $html .= '<div class="rb-header">';
    $html .= '<p class="rb-header__ministry">Министерство просвещения Российской Федерации</p>';
    if ($orgName !== '') {
        $html .= '<p class="rb-header__org">' . e($orgName) . '</p>';
    }
    $html .= '<h1 class="rb-header__title">Зачётная книжка</h1>';
    $html .= '<p class="rb-header__subtitle">Электронная выписка для печати (формат А4)</p>';
    $html .= '</div>';

    $html .= '<table class="rb-student">';
    $html .= '<tr><td class="rb-student__label">Фамилия, имя, отчество</td><td>' . e($fullName) . '</td></tr>';
    $html .= '<tr><td class="rb-student__label">Специальность / профессия</td><td>' . record_book_pdf_dash($specialty) . '</td></tr>';
    $html .= '<tr><td class="rb-student__label">Учебная группа</td><td>' . e($groupNumber) . '</td></tr>';
    $html .= '<tr><td class="rb-student__label">Дата рождения</td><td>' . e($birth) . '</td></tr>';
    $html .= '</table>';

    $periods = $document['periods'] ?? [];
    if ($periods === []) {
        $html .= '<p class="rb-empty">Сведения о промежуточной аттестации отсутствуют.</p>';
    } else {
        foreach ($periods as $period) {
            $html .= record_book_pdf_render_period($period);
        }
    }

    $html .= record_book_pdf_render_courseworks($document['courseworks'] ?? []);
    $html .= record_book_pdf_render_practices($document['practices'] ?? []);
    $html .= record_book_pdf_render_gia($document['gia'] ?? []);

    $html .= '<div class="rb-footer">Документ сформирован автоматически. Дата формирования: '
        . e(date('d.m.Y H:i'))
        . '. Предназначен для печати на листе формата А4.</div>';
    $html .= '</body></html>';

    return $html;
}

function record_book_pdf_render_period(array $period): string
{
    $year = (string) ($period['academic_year'] ?? '');
    $semester = (string) ($period['semester'] ?? '');
    $sections = split_record_book_entries($period['entries'] ?? []);
    $html = '<div class="rb-section">';
    $html .= '<h2 class="rb-section__title">Результаты промежуточной аттестации</h2>';
    $html .= '<p class="rb-meta">' . e($year) . ' учебный год · ' . e(semester_label($semester)) . '</p>';

    $html .= '<div class="rb-section__sub">Зачёты</div>';
    $html .= record_book_pdf_render_attestation_table($sections['credits'], false);

    $html .= '<div class="rb-section__sub">Экзамены</div>';
    $html .= record_book_pdf_render_attestation_table($sections['exams'], true);

    $html .= '</div>';

    return $html;
}

function record_book_pdf_render_attestation_table(array $entries, bool $isExam): string
{
    if ($entries === []) {
        return '<p class="rb-empty">' . ($isExam ? 'Экзаменов нет.' : 'Зачётов нет.') . '</p>';
    }

    $html = '<table class="rb-table"><thead><tr>';
    $html .= '<th class="rb-table__num">№ п/п</th>';
    $html .= '<th>Наименование дисциплины (модуля), МДК</th>';
    $html .= '<th>Форма аттестации</th>';
    $html .= '<th>Фамилия преподавателя</th>';
    $html .= '<th class="rb-table__grade">Оценка</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($entries as $index => $entry) {
        $formLabel = record_book_attestation_label((string) ($entry['attestation_form'] ?? ''));
        if ($formLabel === '') {
            $formLabel = $isExam ? 'Экзамен' : 'Зачёт';
        }
        $html .= '<tr>';
        $html .= '<td class="rb-table__num">' . ($index + 1) . '</td>';
        $html .= '<td>' . e((string) ($entry['subject_name'] ?? '')) . '</td>';
        $html .= '<td>' . e($formLabel) . '</td>';
        $html .= '<td>' . record_book_pdf_dash($entry['teacher_name'] ?? null) . '</td>';
        $html .= '<td class="rb-table__grade">' . record_book_pdf_grade($entry['grade'] ?? null) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    return $html;
}

function record_book_pdf_render_courseworks(array $rows): string
{
    $html = '<div class="rb-section">';
    $html .= '<h2 class="rb-section__title">Курсовые работы (проекты)</h2>';
    if ($rows === []) {
        $html .= '<p class="rb-empty">Сведения о курсовых работах отсутствуют.</p></div>';

        return $html;
    }

    $html .= '<table class="rb-table"><thead><tr>';
    $html .= '<th class="rb-table__num">№ п/п</th>';
    $html .= '<th>Наименование учебных дисциплин (модулей), МДК</th>';
    $html .= '<th>Тема курсового проекта (курсовой работы)</th>';
    $html .= '<th class="rb-table__date">Дата защиты</th>';
    $html .= '<th>Фамилия преподавателя</th>';
    $html .= '<th class="rb-table__grade">Оценка</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($rows as $index => $row) {
        $html .= '<tr>';
        $html .= '<td class="rb-table__num">' . ($index + 1) . '</td>';
        $html .= '<td>' . e((string) ($row['subject_name'] ?? '')) . '</td>';
        $html .= '<td>' . e((string) ($row['topic'] ?? '')) . '</td>';
        $html .= '<td class="rb-table__date">' . e(format_coursework_defense_date($row['defense_date'] ?? null)) . '</td>';
        $html .= '<td>' . record_book_pdf_dash($row['teacher_name'] ?? null) . '</td>';
        $html .= '<td class="rb-table__grade">' . record_book_pdf_grade($row['grade'] ?? null) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table></div>';

    return $html;
}

function record_book_pdf_render_practices(array $rows): string
{
    $html = '<div class="rb-section">';
    $html .= '<h2 class="rb-section__title">Практики</h2>';
    if ($rows === []) {
        $html .= '<p class="rb-empty">Сведения о практиках отсутствуют.</p></div>';

        return $html;
    }

    $html .= '<table class="rb-table"><thead><tr>';
    $html .= '<th class="rb-table__num">№ п/п</th>';
    $html .= '<th>Наименование профессионального модуля (ПМ)</th>';
    $html .= '<th>Руководитель практики от организации</th>';
    $html .= '<th>Руководитель практики</th>';
    $html .= '<th class="rb-table__grade">Оценка</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($rows as $index => $row) {
        $html .= '<tr>';
        $html .= '<td class="rb-table__num">' . ($index + 1) . '</td>';
        $html .= '<td>' . e((string) ($row['module_name'] ?? '')) . '</td>';
        $html .= '<td>' . record_book_pdf_dash($row['org_supervisor_name'] ?? null) . '</td>';
        $html .= '<td>' . record_book_pdf_dash($row['college_supervisor_name'] ?? null) . '</td>';
        $html .= '<td class="rb-table__grade">' . record_book_pdf_grade($row['grade'] ?? null) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table></div>';

    return $html;
}

function record_book_pdf_render_gia(array $rows): string
{
    $sections = split_gia_entries($rows);
    $html = '<div class="rb-section">';
    $html .= '<h2 class="rb-section__title">Государственная итоговая аттестация</h2>';

    $html .= '<div class="rb-section__sub">Демонстрационный экзамен</div>';
    $demo = $sections['demo_exam'];
    if ($demo === []) {
        $html .= '<p class="rb-empty">Сведения отсутствуют.</p>';
    } else {
        $html .= '<table class="rb-table"><thead><tr>';
        $html .= '<th class="rb-table__num">№ п/п</th>';
        $html .= '<th>Наименование ПМ</th>';
        $html .= '<th class="rb-table__code">КОД</th>';
        $html .= '<th class="rb-table__points">Количество баллов</th>';
        $html .= '<th class="rb-table__grade">Оценка</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($demo as $index => $row) {
            $points = $row['points'] !== null && $row['points'] !== ''
                ? rtrim(rtrim(number_format((float) $row['points'], 2, '.', ''), '0'), '.')
                : '—';
            $html .= '<tr>';
            $html .= '<td class="rb-table__num">' . ($index + 1) . '</td>';
            $html .= '<td>' . e((string) ($row['module_name'] ?? '')) . '</td>';
            $html .= '<td class="rb-table__code">' . record_book_pdf_dash($row['code'] ?? null) . '</td>';
            $html .= '<td class="rb-table__points">' . e($points) . '</td>';
            $html .= '<td class="rb-table__grade">' . record_book_pdf_grade($row['grade'] ?? null) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    }

    $html .= '<div class="rb-section__sub">Выпускная квалификационная работа</div>';
    $vkr = $sections['vkr'];
    if ($vkr === []) {
        $html .= '<p class="rb-empty">Сведения отсутствуют.</p>';
    } else {
        $html .= '<table class="rb-table"><thead><tr>';
        $html .= '<th class="rb-table__num">№ п/п</th>';
        $html .= '<th>Тема</th>';
        $html .= '<th class="rb-table__date">Дата защиты</th>';
        $html .= '<th class="rb-table__grade">Оценка</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($vkr as $index => $row) {
            $html .= '<tr>';
            $html .= '<td class="rb-table__num">' . ($index + 1) . '</td>';
            $html .= '<td>' . e((string) ($row['topic'] ?? '')) . '</td>';
            $html .= '<td class="rb-table__date">' . e(format_gia_defense_date($row['defense_date'] ?? null)) . '</td>';
            $html .= '<td class="rb-table__grade">' . record_book_pdf_grade($row['grade'] ?? null) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    }

    $html .= '</div>';

    return $html;
}
