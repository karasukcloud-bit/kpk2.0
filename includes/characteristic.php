<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/students.php';
require_once __DIR__ . '/organization.php';
require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/curriculum.php';
require_once __DIR__ . '/glaz.php';
require_once __DIR__ . '/student_activities.php';
require_once __DIR__ . '/attendance.php';

function person_short_name(string $fullName): string
{
    $parts = split_person_full_name($fullName);
    $last = trim($parts['last_name']);
    $initials = '';
    if ($parts['first_name'] !== '') {
        $initials .= mb_substr($parts['first_name'], 0, 1) . '.';
    }
    if ($parts['middle_name'] !== '') {
        $initials .= mb_substr($parts['middle_name'], 0, 1) . '.';
    }

    return trim($last . ' ' . $initials);
}

function characteristic_is_female(?string $gender): bool
{
    return $gender === 'female';
}

/**
 * @return array<string, string>
 */
function characteristic_gender_words(?string $gender): array
{
    $f = characteristic_is_female($gender);

    return [
        'student_acc' => $f ? 'студентку' : 'студента',
        'student_nom' => $f ? 'студентка' : 'студент',
        'showed' => $f ? 'проявила' : 'проявил',
        'studied_as' => $f ? 'как' : 'как',
        'passed_practice' => $f ? 'проходила' : 'проходил',
        'received' => $f ? 'получила' : 'получил',
        'participated' => $f ? 'участвовала' : 'участвовал',
        'noticed' => $f ? 'не замечена' : 'не замечен',
        'allowed' => $f ? 'не допускала' : 'не допускал',
        'proved' => $f ? 'зарекомендовала' : 'зарекомендовал',
        'takes_part' => $f ? 'Принимает' : 'Принимает',
    ];
}

/**
 * @return list<string>
 */
function characteristic_option_list(string $key, ?string $gender): array
{
    $f = characteristic_is_female($gender);

    $map = [
        'general_trait' => $f
            ? ['ответственная', 'добросовестная', 'целеустремлённая']
            : ['ответственный', 'добросовестный', 'целеустремлённый'],
        'study_level' => [
            '«отлично»',
            '«хорошо» и «отлично»',
            '«хорошо»',
            '«удовлетворительно»',
        ],
        'attendance' => [
            'регулярно',
            'с единичными пропусками по уважительным причинам',
            'с пропусками',
        ],
        'assignments' => $f
            ? ['добросовестно', 'ответственно', 'формально']
            : ['добросовестно', 'ответственно', 'формально'],
        'deadlines' => ['соблюдает', 'иногда нарушает'],
        'personal_traits' => $f
            ? [
                'трудолюбивая, коммуникабельная, дисциплинированная, исполнительная, инициативная, стрессоустойчивая',
                'трудолюбивая, дисциплинированная, ответственная',
                'коммуникабельная, инициативная, доброжелательная',
            ]
            : [
                'трудолюбивый, коммуникабельный, дисциплинированный, исполнительный, инициативный, стрессоустойчивый',
                'трудолюбивый, дисциплинированный, ответственный',
                'коммуникабельный, инициативный, доброжелательный',
            ],
        'skills' => [
            'работать в команде, планировать время, находить решения в нестандартных ситуациях',
            'работать в команде и планировать время',
            'самостоятельно выполнять учебные задания',
        ],
        'relations' => [
            'поддерживает доброжелательные, уважительные отношения',
            'поддерживает корректные, уважительные отношения',
        ],
        'conflicts' => $f
            ? ['не замечена', 'ведёт себя корректно']
            : ['не замечен', 'ведёт себя корректно'],
        'discipline_rules' => ['соблюдает', 'в основном соблюдает'],
        'penalties' => ['не имеет', 'имеет'],
        'conclusion_side' => ['положительной', 'хорошей', 'отличной'],
        'purpose' => [
            'по месту требования',
            'в военный комиссариат',
            'для прохождения практики',
            'в органы опеки',
            'в организацию',
        ],
        'study_form' => ['очной', 'заочной'],
        'funding' => ['бюджетной', 'внебюджетной'],
    ];

    return $map[$key] ?? [];
}

function characteristic_estimate_study_start(array $group): string
{
    $course = get_group_course($group);
    $period = get_active_gradebook_period();
    $year = (string) ($period['academic_year'] ?? get_default_academic_year());
    $parts = explode('-', $year);
    $startYear = (int) ($parts[0] ?? date('Y'));
    $enrollYear = max(2000, $startYear - $course + 1);

    return 'сентября ' . $enrollYear;
}

function characteristic_study_level_from_average(?float $avg): string
{
    if ($avg === null) {
        return '«хорошо»';
    }
    if ($avg >= 4.75) {
        return '«отлично»';
    }
    if ($avg >= 4.0) {
        return '«хорошо» и «отлично»';
    }
    if ($avg >= 3.5) {
        return '«хорошо»';
    }

    return '«удовлетворительно»';
}

/**
 * @return array{average: ?float, top_subjects: string, study_level: string}
 */
function characteristic_student_grades_info(int $studentId, int $groupId): array
{
    $empty = ['average' => null, 'top_subjects' => '', 'study_level' => '«хорошо»'];
    try {
        $period = get_active_gradebook_period();
        $year = (string) $period['academic_year'];
        $semester = (string) $period['semester'];
        $subjects = get_group_curriculum_subjects($groupId, $year, $semester);
        if ($subjects === []) {
            $subjects = get_group_curriculum_subjects($groupId, $year, null);
        }
        if ($subjects === []) {
            return $empty;
        }
        $grades = get_gradebook_grades_from_journal($groupId, $year, $semester);
        $studentGrades = $grades[$studentId] ?? [];
        $values = [];
        $top = [];
        foreach ($subjects as $subject) {
            $itemId = (int) ($subject['curriculum_item_id'] ?? $subject['id'] ?? 0);
            if ($itemId < 1 || !isset($studentGrades[$itemId])) {
                continue;
            }
            $grade = (int) $studentGrades[$itemId];
            if ($grade < 2 || $grade > 5) {
                continue;
            }
            $values[] = $grade;
            if ($grade >= 5) {
                $top[] = (string) ($subject['subject_name'] ?? '');
            }
        }
        if ($values === []) {
            return $empty;
        }
        $avg = round(array_sum($values) / count($values), 2);
        $top = array_values(array_filter(array_unique($top)));
        $top = array_slice($top, 0, 5);

        return [
            'average' => $avg,
            'top_subjects' => implode(', ', $top),
            'study_level' => characteristic_study_level_from_average($avg),
        ];
    } catch (Throwable $e) {
        return $empty;
    }
}

function characteristic_student_attendance_text(int $studentId, int $groupId): string
{
    try {
        $period = get_active_gradebook_period();
        $year = (string) $period['academic_year'];
        $totals = fetch_group_attendance_student_totals($groupId, $year);
        $row = $totals[$studentId] ?? null;
        if ($row === null) {
            return 'регулярно';
        }
        $unexcused = (int) ($row['unexcused_lessons'] ?? 0);
        $excused = (int) ($row['excused_lessons'] ?? 0);
        if ($unexcused === 0 && $excused === 0) {
            return 'регулярно';
        }
        if ($unexcused === 0) {
            return 'с единичными пропусками по уважительным причинам';
        }

        return 'с пропусками';
    } catch (Throwable $e) {
        return 'регулярно';
    }
}

function characteristic_student_debts_text(int $studentId): string
{
    try {
        $subjects = [];
        foreach (get_all_academic_debts() as $debt) {
            if ((int) ($debt['student_id'] ?? 0) !== $studentId) {
                continue;
            }
            $name = trim((string) ($debt['subject_name'] ?? ''));
            if ($name !== '') {
                $subjects[] = $name;
            }
        }
        $subjects = array_values(array_unique($subjects));
        if ($subjects === []) {
            return 'нет';
        }

        return 'имеются по ' . implode(', ', array_slice($subjects, 0, 5));
    } catch (Throwable $e) {
        return 'нет';
    }
}

function characteristic_student_activities_text(int $studentId): string
{
    $list = get_student_activities($studentId);
    if ($list === []) {
        return 'мероприятиях колледжа';
    }
    $titles = [];
    foreach ($list as $activity) {
        $title = trim((string) ($activity['title'] ?? ''));
        if ($title === '') {
            continue;
        }
        $type = student_activity_type_label((string) ($activity['activity_type'] ?? 'club'));
        $titles[] = mb_strtolower($type) . ' «' . $title . '»';
    }
    if ($titles === []) {
        return 'мероприятиях колледжа';
    }

    return implode(', ', $titles);
}

/**
 * @return array<string, string>
 */
function characteristic_default_data(array $student, array $group, ?array $user = null): array
{
    $org = get_organization();
    $orgName = trim((string) ($org['name'] ?? ''));
    $gender = (string) ($student['gender'] ?? '');
    $words = characteristic_gender_words($gender);
    $studentId = (int) ($student['id'] ?? 0);
    $groupId = (int) ($group['id'] ?? 0);
    $grades = characteristic_student_grades_info($studentId, $groupId);
    $generalOptions = characteristic_option_list('general_trait', $gender);
    $personalOptions = characteristic_option_list('personal_traits', $gender);
    $curatorName = trim((string) ($group['curator_name'] ?? ''));
    if ($curatorName === '' && $user !== null) {
        $curatorName = (string) ($user['full_name'] ?? '');
    }

    $specialty = trim(
        trim((string) ($group['specialty_code'] ?? '')) . ' '
        . trim((string) ($group['specialty_name'] ?? ''))
    );

    return [
        'student_id' => (string) $studentId,
        'full_name' => trim((string) ($student['full_name'] ?? '')),
        'birth_date' => format_student_birth_date($student['birth_date'] ?? null),
        'gender' => $gender,
        'course' => (string) get_group_course($group),
        'group_number' => (string) ($group['number'] ?? ''),
        'org_name' => $orgName !== '' ? $orgName : 'образовательной организации',
        'specialty' => $specialty,
        'study_start' => characteristic_estimate_study_start($group),
        'study_form' => 'очной',
        'funding' => 'бюджетной',
        'general_trait' => $generalOptions[0] ?? ($words['student_nom'] === 'студентка' ? 'ответственная' : 'ответственный'),
        'average_grade' => $grades['average'] !== null ? (string) $grades['average'] : '',
        'study_level' => $grades['study_level'],
        'favorite_subjects' => $grades['top_subjects'] !== '' ? $grades['top_subjects'] : 'профильным дисциплинам',
        'attendance' => characteristic_student_attendance_text($studentId, $groupId),
        'assignments' => 'добросовестно',
        'deadlines' => 'соблюдает',
        'debts' => characteristic_student_debts_text($studentId),
        'practice_place' => '',
        'practice_grade' => '',
        'practice_review' => '',
        'practice_skills' => '',
        'competitions' => '',
        'competition_result' => '',
        'personal_traits' => $personalOptions[0] ?? '',
        'skills' => characteristic_option_list('skills', $gender)[0] ?? '',
        'relations' => characteristic_option_list('relations', $gender)[0] ?? '',
        'conflicts' => characteristic_option_list('conflicts', $gender)[0] ?? '',
        'extracurricular' => characteristic_student_activities_text($studentId),
        'achievements' => '',
        'discipline_rules' => 'соблюдает',
        'penalties' => 'не имеет',
        'violations' => $words['allowed'],
        'conclusion_side' => 'положительной',
        'purpose' => 'по месту требования',
        'purpose_extra' => '',
        'director_name' => '',
        'curator_name' => person_short_name($curatorName),
    ];
}

/**
 * @param array<string, string> $defaults
 * @return array<string, string>
 */
function characteristic_merge_post(array $defaults, array $post): array
{
    $data = $defaults;
    foreach ($defaults as $key => $value) {
        if ($key === 'student_id' || $key === 'gender') {
            continue;
        }
        $custom = trim((string) ($post[$key . '_custom'] ?? ''));
        if ($custom !== '') {
            $data[$key] = $custom;
            continue;
        }
        if (!array_key_exists($key, $post)) {
            continue;
        }
        $data[$key] = trim((string) $post[$key]);
    }

    return $data;
}

/**
 * @param array<string, string> $data
 */
function build_characteristic_text(array $data): string
{
    $words = characteristic_gender_words($data['gender'] ?? '');
    $fio = (string) ($data['full_name'] ?? '');
    $fioShort = $fio;
    $org = (string) ($data['org_name'] ?? '');
    $avg = trim((string) ($data['average_grade'] ?? ''));
    $avgText = $avg !== '' ? $avg : 'не рассчитан';

    $purpose = (string) ($data['purpose'] ?? 'по месту требования');
    $purposeExtra = trim((string) ($data['purpose_extra'] ?? ''));
    if ($purpose === 'в организацию' && $purposeExtra !== '') {
        $purpose .= ' (' . $purposeExtra . ')';
    } elseif ($purposeExtra !== '' && $purpose === 'по месту требования') {
        $purpose = $purposeExtra;
    }

    $practicePlace = trim((string) ($data['practice_place'] ?? ''));
    $practiceGrade = trim((string) ($data['practice_grade'] ?? ''));
    $practiceReview = trim((string) ($data['practice_review'] ?? ''));
    $practiceSkills = trim((string) ($data['practice_skills'] ?? ''));
    $competitions = trim((string) ($data['competitions'] ?? ''));
    $competitionResult = trim((string) ($data['competition_result'] ?? ''));
    $achievements = trim((string) ($data['achievements'] ?? ''));

    $practiceBlock = '';
    if ($practicePlace !== '') {
        $practiceBlock .= $words['passed_practice'] . ' учебную и производственную практику в '
            . $practicePlace . '.';
        if ($practiceGrade !== '') {
            $practiceBlock .= ' По итогам практики ' . $words['received'] . ' оценку '
                . $practiceGrade;
            if ($practiceReview !== '') {
                $practiceBlock .= ' и отзыв руководителя: ' . $practiceReview;
            }
            $practiceBlock .= '.';
        } elseif ($practiceReview !== '') {
            $practiceBlock .= ' Отзыв руководителя: ' . $practiceReview . '.';
        }
    } else {
        $practiceBlock .= 'Сведения о месте прохождения практики уточняются.';
    }
    if ($practiceSkills !== '') {
        $practiceBlock .= ' Профессиональные навыки: ' . $practiceSkills . '.';
    }
    if ($competitions !== '') {
        $practiceBlock .= ' ' . $words['participated'] . ' в ' . $competitions;
        if ($competitionResult !== '') {
            $practiceBlock .= ' — ' . $competitionResult;
        }
        $practiceBlock .= '.';
    }

    $extraBlock = 'Принимает участие в ' . ($data['extracurricular'] ?: 'мероприятиях колледжа') . '.';
    if ($achievements !== '') {
        $extraBlock .= ' Достижения: ' . $achievements . '.';
    }

    $penalties = (string) ($data['penalties'] ?? 'не имеет');

    $lines = [
        'ХАРАКТЕРИСТИКА',
        '',
        'на ' . $words['student_acc'] . ' ' . ($data['course'] ?? '') . ' курса группы '
            . ($data['group_number'] ?? ''),
        $org,
        'специальность / профессия: ' . ($data['specialty'] ?? ''),
        '',
        $fio . ', ' . ($data['birth_date'] ?? '') . ',',
        '',
        '1. Общие сведения',
        $fioShort . ' обучается в ' . $org . ' с ' . ($data['study_start'] ?? '')
            . ' по ' . ($data['study_form'] ?? 'очной') . ' форме обучения на '
            . ($data['funding'] ?? 'бюджетной') . ' основе. За время обучения '
            . $words['showed'] . ' себя как ' . ($data['general_trait'] ?? '')
            . ' ' . $words['student_nom'] . '.',
        '',
        '2. Учебная деятельность',
        'Средний балл успеваемости: ' . $avgText . '. Учится на '
            . ($data['study_level'] ?? '«хорошо»') . '. Наибольший интерес проявляет к дисциплинам '
            . ($data['favorite_subjects'] ?? '') . '. Занятия посещает '
            . ($data['attendance'] ?? 'регулярно') . '. К выполнению заданий относится '
            . ($data['assignments'] ?? 'добросовестно') . ', сроки сдачи работ '
            . ($data['deadlines'] ?? 'соблюдает') . '. Академических задолженностей '
            . ($data['debts'] ?? 'нет') . '.',
        '',
        '3. Практическая подготовка',
        $practiceBlock,
        '',
        '4. Личностные и деловые качества',
        $fioShort . ' характеризуется как ' . ($data['personal_traits'] ?? '')
            . '. Умеет ' . ($data['skills'] ?? '') . '. С одногруппниками и преподавателями '
            . ($data['relations'] ?? '') . '. В конфликтных ситуациях '
            . ($data['conflicts'] ?? '') . '.',
        '',
        '5. Внеучебная и общественная деятельность',
        $extraBlock,
        '',
        '6. Дисциплина и соблюдение норм',
        'Правила внутреннего распорядка ' . ($data['discipline_rules'] ?? 'соблюдает')
            . '. Дисциплинарных взысканий ' . $penalties
            . '. Нарушений ' . ($data['violations'] ?? $words['allowed']) . '.',
        '',
        '7. Заключение',
        $fioShort . ' ' . $words['proved'] . ' себя с '
            . ($data['conclusion_side'] ?? 'положительной')
            . ' стороны. Характеристика выдана для предоставления ' . $purpose . '.',
        '',
        'Директор ' . $org . ' ___________ / '
            . (($data['director_name'] ?? '') !== '' ? $data['director_name'] : '_______________'),
        'Куратор группы ___________ / '
            . (($data['curator_name'] ?? '') !== '' ? $data['curator_name'] : '_______________'),
    ];

    return implode("\n", $lines);
}

function characteristic_word_escape(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function characteristic_word_paragraph(
    string $text,
    bool $bold = false,
    string $align = 'both',
    int $size = 28,
    bool $center = false
): string {
    if ($center) {
        $align = 'center';
    }
    $align = in_array($align, ['left', 'center', 'right', 'both'], true) ? $align : 'both';
    $boldXml = $bold ? '<w:b/>' : '';
    $indent = $align === 'both'
        ? '<w:ind w:firstLine="709"/>'
        : '';

    return '<w:p><w:pPr><w:jc w:val="' . $align . '"/>' . $indent
        . '<w:spacing w:after="120" w:line="360" w:lineRule="auto"/></w:pPr>'
        . '<w:r><w:rPr>' . $boldXml . '<w:sz w:val="' . $size . '"/><w:szCs w:val="' . $size . '"/>'
        . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
        . '</w:rPr><w:t xml:space="preserve">' . characteristic_word_escape($text) . '</w:t></w:r></w:p>';
}

/**
 * @param array<string, string> $data
 * @return list<array{text: string, bold?: bool, align?: string, size?: int}>
 */
function build_characteristic_blocks(array $data): array
{
    $text = build_characteristic_text($data);
    $parts = preg_split("/\r\n|\n|\r/", $text) ?: [];
    $org = (string) ($data['org_name'] ?? '');
    $specialtyLine = 'специальность / профессия: ' . ($data['specialty'] ?? '');
    $blocks = [];

    foreach ($parts as $line) {
        $line = rtrim($line);
        if ($line === '') {
            $blocks[] = ['text' => '', 'align' => 'left'];
            continue;
        }
        if ($line === 'ХАРАКТЕРИСТИКА') {
            $blocks[] = ['text' => $line, 'bold' => true, 'align' => 'center', 'size' => 32];
            continue;
        }
        if (preg_match('/^\d+\.\s/u', $line)
            || str_starts_with($line, 'Директор')
            || str_starts_with($line, 'Куратор')) {
            $blocks[] = [
                'text' => $line,
                'bold' => (bool) preg_match('/^\d+\.\s/u', $line),
                'align' => 'left',
            ];
            continue;
        }
        if (
            str_starts_with($line, 'на ')
            || $line === $org
            || $line === $specialtyLine
            || str_starts_with($line, 'специальность / профессия')
            || preg_match('/,\s*\d{2}\.\d{2}\.\d{4},?\s*$/u', $line)
        ) {
            $blocks[] = ['text' => $line, 'align' => 'center'];
            continue;
        }
        $blocks[] = ['text' => $line, 'align' => 'both'];
    }

    return $blocks;
}

/**
 * @param array<string, string> $data
 */
function build_characteristic_docx(array $data): string
{
    $body = '';
    foreach (build_characteristic_blocks($data) as $block) {
        $text = (string) ($block['text'] ?? '');
        if ($text === '') {
            $body .= '<w:p><w:pPr><w:spacing w:after="60"/></w:pPr></w:p>';
            continue;
        }
        $body .= characteristic_word_paragraph(
            $text,
            !empty($block['bold']),
            (string) ($block['align'] ?? 'both'),
            (int) ($block['size'] ?? 28),
            ($block['align'] ?? '') === 'center'
        );
    }

    $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
        . '<w:body>' . $body
        . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>'
        . '<w:pgMar w:top="1134" w:right="850" w:bottom="1134" w:left="1701"/>'
        . '</w:sectPr></w:body></w:document>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
        . '</Types>';

    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
        . '</Relationships>';

    $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';

    $tmp = tempnam(sys_get_temp_dir(), 'char_docx_');
    if ($tmp === false) {
        throw new RuntimeException('Не удалось создать временный файл.');
    }
    $zipPath = $tmp . '.docx';
    @unlink($tmp);

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Не удалось создать DOCX-файл.');
    }
    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $rels);
    $zip->addFromString('word/document.xml', $documentXml);
    $zip->addFromString('word/_rels/document.xml.rels', $docRels);
    $zip->close();

    $binary = file_get_contents($zipPath);
    @unlink($zipPath);
    if ($binary === false || $binary === '') {
        throw new RuntimeException('Не удалось прочитать DOCX-файл.');
    }

    return $binary;
}

/**
 * @param array<string, string> $data
 */
function download_characteristic_docx(array $data): void
{
    $fio = preg_replace('/[^\w\-а-яА-ЯёЁ]+/u', '_', (string) ($data['full_name'] ?? 'student')) ?: 'student';
    $filename = 'harakteristika_' . $fio . '.docx';
    $binary = build_characteristic_docx($data);

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($binary));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo $binary;
    exit;
}
