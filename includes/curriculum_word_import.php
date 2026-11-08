<?php

declare(strict_types=1);

require_once __DIR__ . '/curriculum.php';
require_once __DIR__ . '/student_word_import.php';
require_once __DIR__ . '/teachers.php';

/**
 * Очищает строку до названия предмета.
 */
function curriculum_word_normalize_subject_line(string $line): string
{
    $line = trim(str_replace("\xc2\xa0", ' ', $line));
    $line = preg_replace('/\s+/u', ' ', $line) ?? '';
    $line = preg_replace('/^\d+[\.\)\:\-]?\s+/u', '', $line) ?? $line;
    $line = preg_replace('/^[•·\-–—]\s+/u', '', $line) ?? $line;
    $line = preg_replace('/^(предмет|дисциплина|наименование)\s*[:\-–—]\s*/ui', '', $line) ?? $line;
    $line = trim($line, " \t\n\r\0\x0B.;");

    return $line;
}

function curriculum_word_is_noise_line(string $line): bool
{
    if ($line === '' || mb_strlen($line) < 2) {
        return true;
    }

    if (!preg_match('/\p{L}/u', $line)) {
        return true;
    }

    return (bool) preg_match(
        '/^(список|предметы|дисциплины|наименование|семестр|часы|всего|итог|№|n\/n|код|индекс|группа|учебный\s+план|содержание|примечание|практика)\b/ui',
        $line
    );
}

/**
 * @param list<string> $lines
 * @return list<string>
 */
function parse_curriculum_subject_lines(array $lines): array
{
    $subjects = [];
    $seen = [];

    foreach ($lines as $line) {
        $name = curriculum_word_normalize_subject_line((string) $line);
        if (curriculum_word_is_noise_line($name)) {
            continue;
        }
        $key = mb_strtolower($name);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $subjects[] = $name;
    }

    return $subjects;
}

/**
 * @return list<string>
 */
function curriculum_word_cell_paragraphs(DOMXPath $xpath, DOMNode $cell): array
{
    $parts = [];
    foreach ($xpath->query('./w:p', $cell) as $paragraph) {
        $text = '';
        foreach ($xpath->query('.//w:t', $paragraph) as $node) {
            $text .= $node->textContent;
        }
        $text = trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $text)) ?? '');
        if ($text !== '') {
            $parts[] = $text;
        }
    }

    if ($parts === []) {
        $text = '';
        foreach ($xpath->query('.//w:t', $cell) as $node) {
            $text .= $node->textContent;
        }
        $text = trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $text)) ?? '');
        if ($text !== '') {
            $parts[] = $text;
        }
    }

    return $parts;
}

/**
 * @return list<list<string>>
 */
function curriculum_word_extract_table_rows_from_xml(string $xml): array
{
    $dom = new DOMDocument();
    if (@$dom->loadXML($xml) === false) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', STUDENT_WORD_NS);

    $rows = [];
    foreach ($xpath->query('//w:tr') as $row) {
        $cells = [];
        foreach ($xpath->query('./w:tc', $row) as $cell) {
            $parts = curriculum_word_cell_paragraphs($xpath, $cell);
            $cells[] = implode(' ', $parts);
        }
        if ($cells === []) {
            continue;
        }
        $rows[] = $cells;
    }

    return $rows;
}

/**
 * @return list<string>
 */
function curriculum_word_extract_table_lines_from_xml(string $xml): array
{
    $lines = [];
    foreach (curriculum_word_extract_table_rows_from_xml($xml) as $cells) {
        if (count($cells) === 1) {
            $lines[] = $cells[0];
            continue;
        }
        $candidate = $cells[0];
        if (preg_match('/^\d+[\.\)]?$/', $candidate) && isset($cells[1])) {
            $candidate = $cells[1];
        }
        $lines[] = $candidate;
    }

    return $lines;
}

function curriculum_word_cell_has_load(string $cell): bool
{
    $cell = trim(str_replace("\xc2\xa0", ' ', $cell));
    if ($cell === '' || preg_match('/^[–—\-−\.]+$/u', $cell)) {
        return false;
    }

    return (bool) preg_match('/\d/u', $cell);
}

/**
 * @param list<string> $cells
 * @return array{index:int,subject:int,sem1:int,sem2:int,teachers:int}|null
 */
function curriculum_word_detect_rup_header(array $cells): ?array
{
    $joined = mb_strtolower(implode(' | ', $cells));
    if (
        !preg_match('/дисциплин/ui', $joined)
        && !preg_match('/наименован/ui', $joined)
    ) {
        return null;
    }
    if (
        !preg_match('/полуг|семест|преподават/ui', $joined)
    ) {
        return null;
    }

    $map = [
        'index' => -1,
        'subject' => -1,
        'sem1' => -1,
        'sem2' => -1,
        'teachers' => -1,
    ];

    foreach ($cells as $i => $cell) {
        $c = mb_strtolower($cell);
        if ($map['subject'] < 0 && preg_match('/дисциплин|наименован/ui', $c)) {
            $map['subject'] = $i;
            continue;
        }
        if ($map['teachers'] < 0 && preg_match('/преподават/ui', $c)) {
            $map['teachers'] = $i;
            continue;
        }
        if ($map['sem1'] < 0 && preg_match('/1\s*полуг|1\s*сем/ui', $c) && !preg_match('/зач[её]т/ui', $c)) {
            $map['sem1'] = $i;
            continue;
        }
        if ($map['sem2'] < 0 && preg_match('/2\s*полуг|2\s*сем/ui', $c) && !preg_match('/зач[её]т/ui', $c)) {
            $map['sem2'] = $i;
            continue;
        }
        if ($map['index'] < 0 && preg_match('/^№|^n\/?n$/ui', trim($c))) {
            $map['index'] = $i;
        }
    }

    if ($map['subject'] < 0) {
        return null;
    }
    if ($map['index'] < 0) {
        $map['index'] = 0;
    }
    if ($map['sem1'] < 0 && $map['subject'] + 2 < count($cells)) {
        $map['sem1'] = $map['subject'] + 2;
    }
    if ($map['sem2'] < 0 && $map['sem1'] >= 0) {
        $map['sem2'] = $map['sem1'] + 1;
    }
    if ($map['teachers'] < 0) {
        $map['teachers'] = count($cells) - 1;
    }

    return $map;
}

/**
 * @param list<string> $cells
 * @param array{index:int,subject:int,sem1:int,sem2:int,teachers:int} $map
 * @return array{name:string,semester:string,teacher_raw:string}|null
 */
function curriculum_word_parse_rup_data_row(array $cells, array $map): ?array
{
    $indexCell = trim((string) ($cells[$map['index']] ?? ''));
    $subjectCell = trim((string) ($cells[$map['subject']] ?? ''));
    $sem1Cell = (string) ($cells[$map['sem1']] ?? '');
    $sem2Cell = (string) ($cells[$map['sem2']] ?? '');
    $teachersCell = trim((string) ($cells[$map['teachers']] ?? ''));

    if ($subjectCell === '' && $indexCell === '') {
        return null;
    }

    $code = '';
    if (preg_match(
        '/\b((?:МДК|УП|ПП|ОУД|ОГСЭ|ЕН|ОП|ПМ)[\s\.]*\d[\w\.\(\)\-]*)/ui',
        $indexCell,
        $m
    )) {
        $code = trim($m[1]);
    } elseif (
        $indexCell !== ''
        && !preg_match('/^\d+[\.\)]?$/u', $indexCell)
        && preg_match('/\p{L}/u', $indexCell)
    ) {
        $code = preg_replace('/^(практика|факультатив)\s+/ui', '', $indexCell) ?? $indexCell;
        $code = trim($code);
    }

    $name = $subjectCell;
    if ($name === '' && $code !== '') {
        $name = $code;
    } elseif (
        $code !== ''
        && !preg_match('/^\d+[\.\)]?$/u', $indexCell)
        && mb_stripos($name, $code) === false
    ) {
        $name = $code . '. ' . $name;
    }

    $name = curriculum_word_normalize_subject_line($name);
    if (curriculum_word_is_noise_line($name)) {
        return null;
    }
    if (preg_match('/^всего\b/ui', $name) || preg_match('/примечание/ui', $name)) {
        return null;
    }

    $has1 = $map['sem1'] >= 0 && curriculum_word_cell_has_load($sem1Cell);
    $has2 = $map['sem2'] >= 0 && curriculum_word_cell_has_load($sem2Cell);
    if ($has1 && $has2) {
        $semester = 'both';
    } elseif ($has1) {
        $semester = '1';
    } elseif ($has2) {
        $semester = '2';
    } else {
        $semester = 'both';
    }

    return [
        'name' => $name,
        'semester' => $semester,
        'teacher_raw' => $teachersCell,
    ];
}

/**
 * @return list<string>
 */
function curriculum_word_split_teacher_names(string $raw): array
{
    $raw = trim(str_replace("\xc2\xa0", ' ', $raw));
    if ($raw === '' || preg_match('/^[–—\-−\.]+$/u', $raw)) {
        return [];
    }

    $parts = preg_split('/\s*[,;\/]\s*/u', $raw) ?: [];
    $names = [];
    foreach ($parts as $part) {
        $part = trim(preg_replace('/\s+/u', ' ', $part) ?? '');
        $part = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $part) ?? $part;
        $part = trim($part, " \t\n\r\0\x0B.;");
        if ($part === '' || !preg_match('/\p{L}/u', $part)) {
            continue;
        }
        $names[] = $part;
    }

    return $names;
}

function curriculum_word_teacher_key(string $name): string
{
    $name = mb_strtolower(trim(str_replace('ё', 'е', $name)));
    $name = preg_replace('/\s+/u', ' ', $name) ?? '';
    $name = preg_replace('/[^a-zа-я0-9 ]/ui', '', $name) ?? $name;

    return trim($name);
}

/**
 * @param list<array{id:int|string,full_name:string,is_active?:int|string}> $teachers
 * @return array{0:array<string,int>,1:list<array{id:int,key:string,parts:list<string>}>}
 */
function curriculum_word_teacher_indexes(array $teachers): array
{
    $exact = [];
    $list = [];
    foreach ($teachers as $teacher) {
        if (!(int) ($teacher['is_active'] ?? 1)) {
            continue;
        }
        $id = (int) $teacher['id'];
        $key = curriculum_word_teacher_key((string) $teacher['full_name']);
        if ($key === '') {
            continue;
        }
        $exact[$key] = $id;
        $list[] = [
            'id' => $id,
            'key' => $key,
            'parts' => preg_split('/\s+/u', $key) ?: [],
        ];
    }

    return [$exact, $list];
}

function curriculum_word_mb_distance(string $a, string $b): int
{
    if ($a === $b) {
        return 0;
    }
    $aa = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $bb = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $n = count($aa);
    $m = count($bb);
    if ($n === 0) {
        return $m;
    }
    if ($m === 0) {
        return $n;
    }
    if (abs($n - $m) > 2) {
        return 99;
    }

    $prev = range(0, $m);
    for ($i = 1; $i <= $n; $i++) {
        $cur = [$i];
        for ($j = 1; $j <= $m; $j++) {
            $cost = $aa[$i - 1] === $bb[$j - 1] ? 0 : 1;
            $cur[$j] = min($prev[$j] + 1, $cur[$j - 1] + 1, $prev[$j - 1] + $cost);
        }
        $prev = $cur;
    }

    return (int) $prev[$m];
}

/**
 * @param array<string,int> $exact
 * @param list<array{id:int,key:string,parts:list<string>}> $list
 */
function curriculum_word_match_teacher_id(string $name, array $exact, array $list): ?int
{
    $key = curriculum_word_teacher_key($name);
    if ($key === '') {
        return null;
    }
    if (isset($exact[$key])) {
        return $exact[$key];
    }

    $parts = preg_split('/\s+/u', $key) ?: [];
    if ($parts === []) {
        return null;
    }

    $bestId = null;
    $bestScore = 0;
    foreach ($list as $teacher) {
        if ($teacher['key'] === $key) {
            return $teacher['id'];
        }
        $tParts = $teacher['parts'];
        if ($tParts === []) {
            continue;
        }
        if (isset($parts[0], $parts[1], $tParts[0], $tParts[1])) {
            $surnameOk = $parts[0] === $tParts[0]
                || (
                    mb_strlen($parts[0]) >= 4
                    && mb_strlen($tParts[0]) >= 4
                    && curriculum_word_mb_distance($parts[0], $tParts[0]) <= 1
                );
            if (!$surnameOk || $parts[1] !== $tParts[1]) {
                continue;
            }
            if (isset($parts[2], $tParts[2]) && $parts[2] !== $tParts[2]) {
                continue;
            }
            $score = 3 + (isset($parts[2]) ? 1 : 0);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = $teacher['id'];
            }
        }
    }

    return $bestId;
}

/**
 * @param list<array{name:string,semester:string,teacher_raw:string}> $rows
 * @return list<array{name:string,semester:string,teacher_id:?int,teacher_raw:string}>
 */
function curriculum_word_attach_teachers(array $rows): array
{
    [$exact, $list] = curriculum_word_teacher_indexes(get_all_teachers());
    $out = [];
    foreach ($rows as $row) {
        $teacherId = null;
        foreach (curriculum_word_split_teacher_names((string) ($row['teacher_raw'] ?? '')) as $name) {
            $teacherId = curriculum_word_match_teacher_id($name, $exact, $list);
            if ($teacherId !== null) {
                break;
            }
        }
        $out[] = [
            'name' => (string) $row['name'],
            'semester' => (string) $row['semester'],
            'teacher_id' => $teacherId,
            'teacher_raw' => (string) ($row['teacher_raw'] ?? ''),
        ];
    }

    return $out;
}

/**
 * @param list<list<string>> $tableRows
 * @return list<array{name:string,semester:string,teacher_raw:string}>
 */
function curriculum_word_parse_rup_rows(array $tableRows): array
{
    $map = null;
    $items = [];
    $seen = [];

    foreach ($tableRows as $cells) {
        $header = curriculum_word_detect_rup_header($cells);
        if ($header !== null) {
            $map = $header;
            continue;
        }
        if ($map === null) {
            continue;
        }

        $parsed = curriculum_word_parse_rup_data_row($cells, $map);
        if ($parsed === null) {
            continue;
        }
        $key = mb_strtolower($parsed['name']);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $items[] = $parsed;
    }

    return $items;
}

/**
 * @return list<string>
 */
function curriculum_word_subjects_from_rows(array $rows): array
{
    $names = [];
    foreach ($rows as $row) {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name !== '') {
            $names[] = $name;
        }
    }

    return $names;
}

/**
 * @return array{
 *   success:bool,
 *   error?:string,
 *   mode?:string,
 *   rows?:list<array{name:string,semester:string,teacher_id:?int,teacher_raw:string}>,
 *   subjects?:list<string>
 * }
 */
function parse_curriculum_subjects_from_docx(string $filePath): array
{
    if (!class_exists(ZipArchive::class)) {
        return ['success' => false, 'error' => 'На сервере недоступно расширение ZipArchive для чтения DOCX.'];
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        return ['success' => false, 'error' => 'Не удалось открыть файл Word.'];
    }
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    if (!is_string($xml) || $xml === '') {
        return ['success' => false, 'error' => 'В файле не найден текст документа.'];
    }

    $tableRows = curriculum_word_extract_table_rows_from_xml($xml);
    $rupRows = curriculum_word_parse_rup_rows($tableRows);
    if ($rupRows !== []) {
        $rows = curriculum_word_attach_teachers($rupRows);

        return [
            'success' => true,
            'mode' => 'rup',
            'rows' => $rows,
            'subjects' => curriculum_word_subjects_from_rows($rows),
        ];
    }

    $extracted = student_word_extract_lines_from_docx($filePath);
    if (!$extracted['success']) {
        return $extracted;
    }

    $tableLines = curriculum_word_extract_table_lines_from_xml($xml);
    $lines = array_merge($extracted['lines'] ?? [], $tableLines);
    $subjects = parse_curriculum_subject_lines($lines);
    if ($subjects === []) {
        return [
            'success' => false,
            'error' => 'Не удалось распознать учебный план. Ожидается таблица РУП или список предметов.',
        ];
    }

    $rows = [];
    foreach ($subjects as $name) {
        $rows[] = [
            'name' => $name,
            'semester' => '1',
            'teacher_id' => null,
            'teacher_raw' => '',
        ];
    }

    return [
        'success' => true,
        'mode' => 'list',
        'rows' => $rows,
        'subjects' => $subjects,
    ];
}

/**
 * @return array{
 *   success:bool,
 *   error?:string,
 *   mode?:string,
 *   rows?:list<array{name:string,semester:string,teacher_id:?int,teacher_raw:string}>,
 *   subjects?:list<string>
 * }
 */
function parse_curriculum_subjects_from_uploaded_word(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Не удалось загрузить файл.'];
    }

    $name = (string) ($file['name'] ?? '');
    $tmp = (string) ($file['tmp_name'] ?? '');
    $ext = mb_strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext !== 'docx') {
        return ['success' => false, 'error' => 'Загрузите файл в формате .docx (Word).'];
    }
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['success' => false, 'error' => 'Файл загрузки недоступен.'];
    }

    return parse_curriculum_subjects_from_docx($tmp);
}

/**
 * @return array{
 *   success:bool,
 *   error?:string,
 *   mode?:string,
 *   rows?:list<array{name:string,semester:string,teacher_id:?int,teacher_raw:string}>,
 *   subjects?:list<string>
 * }
 */
function parse_curriculum_subjects_from_text(string $text): array
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = preg_split("/\n+/u", $text) ?: [];
    $subjects = parse_curriculum_subject_lines($lines);
    if ($subjects === []) {
        return [
            'success' => false,
            'error' => 'Не удалось распознать названия предметов в тексте.',
        ];
    }

    $rows = [];
    foreach ($subjects as $name) {
        $rows[] = [
            'name' => $name,
            'semester' => '1',
            'teacher_id' => null,
            'teacher_raw' => '',
        ];
    }

    return [
        'success' => true,
        'mode' => 'list',
        'rows' => $rows,
        'subjects' => $subjects,
    ];
}

/**
 * @return array<string, true> lower(name) => true
 */
function curriculum_plan_subject_name_set(int $planId): array
{
    $items = get_curriculum_items($planId, null);
    $set = [];
    foreach ($items as $item) {
        $name = trim((string) ($item['subject_name'] ?? ''));
        if ($name !== '') {
            $set[mb_strtolower($name)] = true;
        }
    }

    return $set;
}

/**
 * @param list<array{name:string,semester:string,teacher_id?:int|null}> $rows
 * @return array{success:bool,error?:string,added?:int,skipped?:int,errors?:list<string>}
 */
function import_curriculum_subjects_rows(int $planId, array $rows): array
{
    if (get_curriculum_plan_by_id($planId) === null) {
        return ['success' => false, 'error' => 'Учебный план не найден.'];
    }

    $added = 0;
    $skipped = 0;
    $errors = [];

    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            $skipped++;
            continue;
        }
        $name = trim((string) ($row['name'] ?? ''));
        $semester = (string) ($row['semester'] ?? '1');
        $teacherId = isset($row['teacher_id']) ? (int) $row['teacher_id'] : null;
        if ($teacherId !== null && $teacherId <= 0) {
            $teacherId = null;
        }
        if ($name === '') {
            $skipped++;
            continue;
        }
        if (!validate_semester($semester)) {
            $errors[] = ($index + 1) . '. Некорректный семестр для «' . $name . '».';
            continue;
        }

        $result = add_curriculum_item($planId, $name, $semester, $teacherId);
        if (!$result['success']) {
            $msg = (string) ($result['error'] ?? 'Ошибка');
            if (str_contains($msg, 'уже есть')) {
                $skipped++;
                continue;
            }
            $errors[] = ($index + 1) . '. ' . $msg . ' («' . $name . '»)';
            continue;
        }
        $added++;
    }

    if ($added === 0 && $errors !== []) {
        return [
            'success' => false,
            'error' => 'Не удалось добавить предметы: ' . $errors[0],
            'added' => 0,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    return [
        'success' => true,
        'added' => $added,
        'skipped' => $skipped,
        'errors' => $errors,
    ];
}
