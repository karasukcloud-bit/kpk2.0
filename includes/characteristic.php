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
function characteristic_gender_words(?string $gender, bool $isGraduate = false): array
{
    $f = characteristic_is_female($gender);

    if ($isGraduate) {
        return [
            'status_label' => $f ? 'выпускницы' : 'выпускника',
            'student_nom' => $f ? 'студентки' : 'студента',
            'raised' => $f ? 'воспитывалась' : 'воспитывался',
            'studies' => $f ? 'обучалась' : 'обучался',
            'studies_well' => $f ? 'занималась' : 'занимался',
            'showed' => $f ? 'зарекомендовала' : 'зарекомендовал',
            'participated' => $f ? 'участвовала' : 'участвовал',
            'relates' => $f ? 'относилась' : 'относился',
            'has' => $f ? 'имела' : 'имел',
            'shows_interest' => $f ? 'проявляла' : 'проявлял',
            'attends' => $f ? 'Посещала' : 'Посещал',
            'acquainted' => $f ? 'Ознакомлена' : 'Ознакомлен',
        ];
    }

    return [
        'status_label' => $f ? 'студентки' : 'студента',
        'student_nom' => $f ? 'студентки' : 'студента',
        'raised' => $f ? 'воспитывается' : 'воспитывается',
        'studies' => $f ? 'обучается' : 'обучается',
        'studies_well' => $f ? 'занимается' : 'занимается',
        'showed' => $f ? 'зарекомендовала' : 'зарекомендовал',
        'participated' => $f ? 'участвовала' : 'участвовал',
        'relates' => $f ? 'относится' : 'относится',
        'has' => $f ? 'имеет' : 'имеет',
        'shows_interest' => $f ? 'проявляет' : 'проявляет',
        'attends' => 'Посещает',
        'acquainted' => $f ? 'Ознакомлена' : 'Ознакомлен',
    ];
}

/**
 * @return list<string>
 */
function characteristic_option_list(string $key, ?string $gender = null): array
{
    $f = characteristic_is_female($gender);

    $map = [
        'status' => ['student', 'graduate'],
        'dominant_grade' => ['«5»', '«4-5»', '«4»', '«4-3»', '«3-4»', '«3»'],
        'family_kind' => [
            'полной семье',
            'неполной семье',
            'приёмной семье',
        ],
        'discipline' => [
            'Соблюдает правила внутреннего распорядка колледжа',
            'Старается соблюдать правила внутреннего распорядка колледжа',
        ],
        'penalties' => [
            'дисциплинарных взысканий не имеет',
            'взысканий не имеет',
            'имеет взыскание за пропуск занятия без уважительной причины',
        ],
        'bad_habits' => [
            'Вредных привычек не имеет.',
            'Имеет вредную привычку курение.',
            '',
        ],
        'merits' => [
            'желание и умение учиться, настойчивость в достижении поставленных целей, ответственность',
            'трудолюбие, ответственность, дисциплинированность',
            'коммуникабельность, инициативность, ответственность',
        ],
        'self_esteem' => [
            'имеет адекватную самооценку, что положительно сказывается на процессе обучения',
            'имеет адекватную самооценку',
        ],
        'motivation' => [
            'получение знаний в области будущей профессии',
            'освоение профессии и успешная учёба',
        ],
    ];

    if ($key === 'discipline' && $f) {
        // same text for female in samples
    }

    return $map[$key] ?? [];
}

function characteristic_first_name(string $fullName): string
{
    $parts = split_person_full_name($fullName);

    return trim($parts['first_name']) !== ''
        ? trim($parts['first_name'])
        : trim($fullName);
}

function characteristic_acquainted_name(string $fullName): string
{
    $parts = split_person_full_name($fullName);
    $first = trim($parts['first_name']);
    $last = trim($parts['last_name']);

    return trim($first . ' ' . $last);
}

function characteristic_org_short_default(string $orgName): string
{
    if (preg_match('/«([^»]+)»/u', $orgName, $m)) {
        return trim($m[1]);
    }

    return $orgName !== '' ? $orgName : 'педагогическом колледже';
}

function characteristic_college_in_default(string $orgName): string
{
    $short = characteristic_org_short_default($orgName);
    // Типовая формулировка для Карасукского педколледжа; поле редактируется.
    if (mb_stripos($short, 'Карасукск') !== false) {
        return 'В Карасукском педагогическом колледже';
    }

    return 'В ' . $short;
}

function characteristic_dominant_grade_from_average(?float $avg): string
{
    if ($avg === null) {
        return '«4-5»';
    }
    if ($avg >= 4.75) {
        return '«5»';
    }
    if ($avg >= 4.35) {
        return '«4-5»';
    }
    if ($avg >= 3.85) {
        return '«4»';
    }
    if ($avg >= 3.35) {
        return '«4-3»';
    }

    return '«3-4»';
}

function characteristic_family_kind_from_student(array $student): string
{
    if (!empty($student['without_parental_care'])) {
        return 'приёмной семье';
    }
    $type = (string) ($student['family_type'] ?? '');
    if ($type === 'complete') {
        return 'полной семье';
    }
    if ($type === 'no_father' || $type === 'no_mother') {
        return 'неполной семье';
    }

    return 'полной семье';
}

function characteristic_parent_clause(string $role, string $name, string $workplace): string
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }
    $workplace = trim($workplace);
    $label = $role === 'mother' ? 'мать' : 'отец';
    if ($workplace !== '') {
        return $label . ': ' . $name . ', работает ' . $workplace;
    }

    return $label . ': ' . $name;
}

function characteristic_family_sentence(array $student, string $firstName, string $familyKind, string $raisedVerb): string
{
    $mother = characteristic_parent_clause(
        'mother',
        (string) ($student['mother_name'] ?? ''),
        (string) ($student['mother_workplace'] ?? '')
    );
    $father = characteristic_parent_clause(
        'father',
        (string) ($student['father_name'] ?? ''),
        (string) ($student['father_workplace'] ?? '')
    );

    $parts = [];
    if ($mother !== '') {
        $parts[] = $mother;
    }
    if ($father !== '') {
        $parts[] = $father;
    }

    $sentence = $firstName . ' ' . $raisedVerb . ' в ' . $familyKind;
    if ($parts !== []) {
        $sentence .= ', ' . implode(', ', $parts);
    }

    return $sentence . '.';
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
            'top_subjects' => mb_strtolower(implode(', ', $top)),
            'study_level' => characteristic_dominant_grade_from_average($avg),
        ];
    } catch (Throwable $e) {
        return $empty;
    }
}

/**
 * Названия кружков/секций студента для характеристики.
 *
 * @return array{section: string, club: string}
 */
function characteristic_student_activity_titles(int $studentId): array
{
    $sections = [];
    $clubs = [];
    foreach (get_student_activities($studentId) as $activity) {
        $title = trim((string) ($activity['title'] ?? ''));
        if ($title === '') {
            continue;
        }
        $quoted = '«' . $title . '»';
        if (($activity['activity_type'] ?? '') === 'section') {
            $sections[] = $quoted;
        } else {
            $clubs[] = $quoted;
        }
    }

    return [
        'section' => implode(', ', array_values(array_unique($sections))),
        'club' => implode(', ', array_values(array_unique($clubs))),
    ];
}

/**
 * Уникальные названия предметов учебного плана группы.
 *
 * @return list<string>
 */
function characteristic_group_subject_names(int $groupId): array
{
    if ($groupId < 1) {
        return [];
    }

    require_once __DIR__ . '/curriculum.php';

    $names = [];
    $years = [];
    try {
        $period = get_active_gradebook_period();
        $years[] = (string) ($period['academic_year'] ?? '');
    } catch (Throwable $e) {
        // ignore
    }
    $years[] = get_default_academic_year();

    $stmt = db()->prepare(
        'SELECT DISTINCT academic_year FROM curriculum_plans WHERE group_id = ? ORDER BY academic_year DESC'
    );
    $stmt->execute([$groupId]);
    foreach ($stmt->fetchAll() as $row) {
        $years[] = (string) ($row['academic_year'] ?? '');
    }

    $years = array_values(array_unique(array_filter($years)));
    foreach ($years as $year) {
        try {
            foreach (get_group_curriculum_subjects($groupId, $year, null) as $subject) {
                $name = trim((string) ($subject['subject_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $names[mb_strtolower($name)] = $name;
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    $list = array_values($names);
    usort($list, static fn (string $a, string $b): int => strnatcasecmp($a, $b));

    return $list;
}

function characteristic_specialty_quoted(array $group): string
{
    $code = trim((string) ($group['specialty_code'] ?? ''));
    $name = trim((string) ($group['specialty_name'] ?? ''));
    if ($name === '') {
        return '«»';
    }
    if ($code !== '') {
        return $code . ' «' . $name . '»';
    }

    return '«' . $name . '»';
}

/**
 * @return array<string, string>
 */
function characteristic_default_data(array $student, array $group, ?array $user = null): array
{
    $org = get_organization();
    $orgName = trim((string) ($org['name'] ?? ''));
    if ($orgName === '') {
        $orgName = 'ГАПОУ НСО «Карасукский педагогический колледж»';
    }

    $gender = (string) ($student['gender'] ?? '');
    $fullName = trim((string) ($student['full_name'] ?? ''));
    $firstName = characteristic_first_name($fullName);
    $studentId = (int) ($student['id'] ?? 0);
    $groupId = (int) ($group['id'] ?? 0);
    $grades = characteristic_student_grades_info($studentId, $groupId);

    $address = format_student_registered_address($student);
    if ($address === '—') {
        $address = trim((string) ($student['address_actual'] ?? ''));
    }

    $curatorName = trim((string) ($group['curator_name'] ?? ''));
    if ($curatorName === '' && $user !== null) {
        $curatorName = (string) ($user['full_name'] ?? '');
    }

    $familyKind = characteristic_family_kind_from_student($student);
    $words = characteristic_gender_words($gender, false);
    $familySentence = characteristic_family_sentence($student, $firstName, $familyKind, $words['raised']);

    $favorite = $grades['top_subjects'] !== ''
        ? $grades['top_subjects']
        : 'профильным дисциплинам';
    $activities = characteristic_student_activity_titles($studentId);

    return [
        'student_id' => (string) $studentId,
        'gender' => $gender,
        'status' => 'student',
        'full_name' => $fullName,
        'full_name_genitive' => $fullName,
        'first_name' => $firstName,
        'first_name_genitive' => $firstName,
        'birth_date' => format_student_birth_date($student['birth_date'] ?? null),
        'address' => $address,
        'course' => (string) get_group_course($group),
        'group_number' => (string) ($group['number'] ?? ''),
        'org_name' => $orgName,
        'college_in' => characteristic_college_in_default($orgName),
        'specialty' => characteristic_specialty_quoted($group),
        'family_kind' => $familyKind,
        'family_sentence' => $familySentence,
        'dominant_grade' => $grades['study_level'],
        'favorite_subjects' => $favorite,
        'self_esteem' => characteristic_option_list('self_esteem')[0],
        'motivation' => characteristic_option_list('motivation')[0],
        'sports_section' => $activities['section'],
        'club' => $activities['club'],
        'achievements' => '',
        'discipline' => characteristic_option_list('discipline')[0],
        'penalties' => characteristic_option_list('penalties')[0],
        'bad_habits' => characteristic_option_list('bad_habits')[0],
        'merits' => characteristic_option_list('merits')[0],
        'acquainted_line' => characteristic_gender_words($gender)['acquainted']
            . ' ' . characteristic_acquainted_name($fullName),
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
    $isGraduate = ($data['status'] ?? 'student') === 'graduate';
    $words = characteristic_gender_words($data['gender'] ?? '', $isGraduate);

    $fullName = trim((string) ($data['full_name'] ?? ''));
    $fullGen = trim((string) ($data['full_name_genitive'] ?? $fullName));
    $first = trim((string) ($data['first_name'] ?? characteristic_first_name($fullName)));
    $firstGen = trim((string) ($data['first_name_genitive'] ?? $first));
    $org = trim((string) ($data['org_name'] ?? ''));
    $course = trim((string) ($data['course'] ?? ''));
    $birth = trim((string) ($data['birth_date'] ?? ''));
    $address = trim((string) ($data['address'] ?? ''));
    $collegeIn = trim((string) ($data['college_in'] ?? 'В колледже'));
    $specialty = trim((string) ($data['specialty'] ?? ''));
    $grade = trim((string) ($data['dominant_grade'] ?? '«4-5»'));
    $favorite = trim((string) ($data['favorite_subjects'] ?? ''));
    $selfEsteem = trim((string) ($data['self_esteem'] ?? ''));
    $motivation = trim((string) ($data['motivation'] ?? ''));
    $achievements = trim((string) ($data['achievements'] ?? ''));
    $sportsSection = trim((string) ($data['sports_section'] ?? ''));
    $club = trim((string) ($data['club'] ?? ''));
    $discipline = trim((string) ($data['discipline'] ?? ''));
    $penalties = trim((string) ($data['penalties'] ?? ''));
    $badHabits = trim((string) ($data['bad_habits'] ?? ''));
    $merits = trim((string) ($data['merits'] ?? ''));
    $familySentence = trim((string) ($data['family_sentence'] ?? ''));
    $acquainted = trim((string) ($data['acquainted_line'] ?? ''));
    $director = trim((string) ($data['director_name'] ?? ''));
    $curator = trim((string) ($data['curator_name'] ?? ''));

    if ($isGraduate) {
        $headerRole = $words['status_label'] . ' ' . $org;
    } else {
        $headerRole = $words['status_label'] . ' ' . $course . ' курса ' . $org;
    }

    $birthLine = $fullName;
    if ($birth !== '') {
        $birthLine .= ' ' . $birth . ' года рождения';
    }
    if ($address !== '' && $address !== '—') {
        $birthLine .= ' проживает по адресу: ' . $address . '.';
    } else {
        $birthLine .= '.';
    }

    if ($isGraduate) {
        $female = characteristic_is_female($data['gender'] ?? '');
        $studyLine = $collegeIn . ' ' . $first . ' завершил' . ($female ? 'а' : '')
            . ' обучение по специальности ' . $specialty . '.';
    } else {
        $studyLine = $collegeIn . ' ' . $first . ' ' . $words['studies']
            . ' на специальности ' . $specialty . '.';
    }

    $studyBlock = 'По всем предметам ' . $words['studies_well'] . ' хорошо, преобладающая отметка '
        . $grade . '. За период обучения ' . $words['showed']
        . ' себя с положительной стороны. Активно ' . $words['participated']
        . ' во всех общеколледжных мероприятиях и мероприятиях группы. Всегда '
        . $words['relates'] . ' ответственно к поручениям и своим обязанностям. ';
    if ($selfEsteem !== '') {
        $studyBlock .= $first . ' ' . $selfEsteem . '. ';
    }
    if ($motivation !== '') {
        $studyBlock .= 'Основным мотивом учения у ' . $words['student_nom']
            . ' выступает ' . $motivation . '.';
    }

    $interestLine = $first . ' ' . $words['shows_interest']
        . ' особый интерес к таким предметам, как ' . $favorite . '.';
    if ($badHabits !== '') {
        $interestLine .= ' ' . $badHabits;
    }

    $disciplineLine = trim($discipline);
    if ($penalties !== '') {
        $disciplineLine .= ($disciplineLine !== '' ? ', ' : '') . $penalties;
    }
    if ($disciplineLine !== '') {
        $disciplineLine .= '.';
    }

    $meritsLine = 'К основным достоинствам ' . $firstGen
        . ' можно отнести ' . $merits . '.';

    $lines = [
        'Характеристика',
        $fullGen,
        $headerRole,
        '',
        $birthLine,
    ];

    if ($familySentence !== '') {
        $lines[] = $familySentence;
    }

    $lines[] = $studyLine;
    $lines[] = '';
    $lines[] = trim($studyBlock);
    $lines[] = '';
    $lines[] = trim($interestLine);

    if ($achievements !== '') {
        $lines[] = $first . ' ' . $words['participated'] . ' в ' . $achievements . '.';
    }

    if ($sportsSection !== '') {
        $sectionValue = $sportsSection;
        if (!str_contains($sectionValue, '«')) {
            $sectionValue = '«' . $sectionValue . '»';
        }
        $lines[] = $words['attends'] . ' спортивную секцию ' . $sectionValue . '.';
    }

    if ($club !== '') {
        $clubValue = $club;
        if (!str_contains($clubValue, '«')) {
            $clubValue = '«' . $clubValue . '»';
        }
        $lines[] = $words['attends'] . ' кружок ' . $clubValue . '.';
    }

    if ($disciplineLine !== '') {
        $lines[] = $disciplineLine;
    }

    $lines[] = $meritsLine;
    $lines[] = '';

    if ($acquainted !== '') {
        $lines[] = $acquainted;
        $lines[] = '';
    }
    if ($director !== '') {
        $lines[] = (str_contains(mb_strtolower($director), 'директор')
            ? $director
            : ('Директор колледжа ' . $director));
        $lines[] = '';
    }
    $lines[] = 'Куратор группы ' . ($curator !== '' ? $curator : '_______________');

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
    bool $firstLineIndent = true
): string {
    $align = in_array($align, ['left', 'center', 'right', 'both'], true) ? $align : 'both';
    $boldXml = $bold ? '<w:b/>' : '';
    $indent = ($align === 'both' && $firstLineIndent)
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
 * @return list<array{text: string, bold?: bool, align?: string, size?: int, indent?: bool}>
 */
function build_characteristic_blocks(array $data): array
{
    $text = build_characteristic_text($data);
    $parts = preg_split("/\r\n|\n|\r/", $text) ?: [];
    $blocks = [];
    $lineIndex = 0;
    $contentStarted = false;

    foreach ($parts as $raw) {
        $line = rtrim($raw);
        if ($line === '') {
            $blocks[] = ['text' => '', 'align' => 'left', 'indent' => false];
            continue;
        }

        $lineIndex++;

        if ($lineIndex === 1 && mb_strtolower($line) === 'характеристика') {
            $blocks[] = ['text' => 'Характеристика', 'bold' => true, 'align' => 'center', 'size' => 32, 'indent' => false];
            continue;
        }

        // Заголовок: ФИО род.п. и строка статуса — по центру
        if (!$contentStarted && (
            $lineIndex <= 3
            || str_starts_with($line, 'студента ')
            || str_starts_with($line, 'студентки ')
            || str_starts_with($line, 'выпускника ')
            || str_starts_with($line, 'выпускницы ')
        )) {
            $blocks[] = ['text' => $line, 'align' => 'center', 'indent' => false];
            if ($lineIndex >= 3
                || str_starts_with($line, 'студента ')
                || str_starts_with($line, 'студентки ')
                || str_starts_with($line, 'выпускника ')
                || str_starts_with($line, 'выпускницы ')
            ) {
                $contentStarted = true;
            }
            continue;
        }

        if (
            str_starts_with($line, 'Ознакомлен')
            || str_starts_with($line, 'Ознакомлена')
            || str_starts_with($line, 'Директор')
            || str_starts_with($line, 'И.О. директора')
            || str_starts_with($line, 'Куратор группы')
        ) {
            $blocks[] = ['text' => $line, 'align' => 'left', 'indent' => false];
            continue;
        }

        $blocks[] = ['text' => $line, 'align' => 'both', 'indent' => true];
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
            ($block['indent'] ?? true) !== false && ($block['align'] ?? '') === 'both'
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
