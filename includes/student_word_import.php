<?php

declare(strict_types=1);

require_once __DIR__ . '/students.php';

const STUDENT_WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

/**
 * @return list<string>
 */
function student_word_paragraph_texts_from_xml(string $xml): array
{
    $dom = new DOMDocument();
    if (@$dom->loadXML($xml) === false) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', STUDENT_WORD_NS);

    $lines = [];
    foreach ($xpath->query('//w:p') as $paragraph) {
        $line = '';
        foreach ($xpath->query('.//w:t|.//w:tab', $paragraph) as $node) {
            if ($node->localName === 'tab') {
                $line .= ' ';
            } else {
                $line .= $node->textContent;
            }
        }
        $line = trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $line)) ?? '');
        if ($line !== '') {
            $lines[] = $line;
        }
    }

    return $lines;
}

/**
 * @return array{success:bool,error?:string,lines?:list<string>}
 */
function student_word_extract_lines_from_docx(string $filePath): array
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

    $lines = student_word_paragraph_texts_from_xml($xml);
    if ($lines === []) {
        return ['success' => false, 'error' => 'В документе не найдено текстовых строк.'];
    }

    return ['success' => true, 'lines' => $lines];
}

/**
 * Разбирает строку списка вида «1. Иванов Иван Иванович».
 *
 * @return array{last_name:string,first_name:string,middle_name:string,full_name:string}|null
 */
function parse_student_list_line(string $line): ?array
{
    $line = trim(str_replace("\xc2\xa0", ' ', $line));
    $line = preg_replace('/\s+/u', ' ', $line) ?? '';
    if ($line === '') {
        return null;
    }

    $line = preg_replace('/^\d+[\.\)\:\-]?\s+/u', '', $line) ?? $line;
    $line = preg_replace('/^[•·\-–—]\s+/u', '', $line) ?? $line;
    $line = trim($line, " \t\n\r\0\x0B.,;");

    if ($line === '' || mb_strlen($line) < 3) {
        return null;
    }

    if (preg_match('/^(список|фио|группа|студенты|курс|специальность|№)\b/ui', $line)) {
        return null;
    }

    if (!preg_match('/\p{L}/u', $line)) {
        return null;
    }

    $parts = split_person_full_name($line);
    if ($parts['last_name'] === '' || $parts['first_name'] === '') {
        return null;
    }

    $fullName = compose_person_full_name(
        $parts['last_name'],
        $parts['first_name'],
        $parts['middle_name']
    );

    return [
        'last_name' => $parts['last_name'],
        'first_name' => $parts['first_name'],
        'middle_name' => $parts['middle_name'],
        'full_name' => $fullName,
    ];
}

/**
 * @param list<string> $lines
 * @return list<array{last_name:string,first_name:string,middle_name:string,full_name:string}>
 */
function parse_student_list_lines(array $lines): array
{
    $students = [];
    $seen = [];

    foreach ($lines as $line) {
        $parsed = parse_student_list_line((string) $line);
        if ($parsed === null) {
            continue;
        }
        $key = mb_strtolower($parsed['full_name']);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $students[] = $parsed;
    }

    return $students;
}

/**
 * @return array{success:bool,error?:string,students?:list<array{last_name:string,first_name:string,middle_name:string,full_name:string}>}
 */
function parse_students_from_uploaded_word(array $file): array
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

    $extracted = student_word_extract_lines_from_docx($tmp);
    if (!$extracted['success']) {
        return $extracted;
    }

    $students = parse_student_list_lines($extracted['lines'] ?? []);
    if ($students === []) {
        return [
            'success' => false,
            'error' => 'Не удалось распознать ФИО. Ожидается список вида «1. Иванов Иван Иванович».',
        ];
    }

    return ['success' => true, 'students' => $students];
}

/**
 * @param list<string> $textLines
 * @return array{success:bool,error?:string,students?:list<array{last_name:string,first_name:string,middle_name:string,full_name:string}>}
 */
function parse_students_from_text_list(string $text): array
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = preg_split("/\n+/u", $text) ?: [];
    $students = parse_student_list_lines($lines);
    if ($students === []) {
        return [
            'success' => false,
            'error' => 'Не удалось распознать ФИО в тексте. Укажите список вида «1. Иванов Иван Иванович».',
        ];
    }

    return ['success' => true, 'students' => $students];
}

/**
 * @param list<array{last_name?:string,first_name?:string,middle_name?:string}> $rows
 * @return array{success:bool,error?:string,created?:int,skipped?:int,errors?:list<string>}
 */
function import_students_name_rows(int $groupId, array $rows): array
{
    if (get_group_by_id($groupId) === null) {
        return ['success' => false, 'error' => 'Группа не найдена.'];
    }

    $created = 0;
    $skipped = 0;
    $errors = [];

    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            $skipped++;
            continue;
        }
        $last = trim((string) ($row['last_name'] ?? ''));
        $first = trim((string) ($row['first_name'] ?? ''));
        $middle = trim((string) ($row['middle_name'] ?? ''));
        if ($last === '' && $first === '' && $middle === '') {
            $skipped++;
            continue;
        }

        $data = student_payload_from_post([
            'last_name' => $last,
            'first_name' => $first,
            'middle_name' => $middle,
        ]);
        $result = create_student($groupId, $data);
        if (!$result['success']) {
            $errors[] = ($index + 1) . '. ' . ($result['error'] ?? 'Ошибка') . ' (' . compose_person_full_name($last, $first, $middle) . ')';
            continue;
        }
        $created++;
    }

    if ($created === 0 && $errors !== []) {
        return [
            'success' => false,
            'error' => 'Не удалось добавить студентов: ' . $errors[0],
            'created' => 0,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    return [
        'success' => true,
        'created' => $created,
        'skipped' => $skipped,
        'errors' => $errors,
    ];
}
