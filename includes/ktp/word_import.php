<?php

declare(strict_types=1);

require_once __DIR__ . '/../ktp.php';

const KTP_WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

function ktp_word_normalize_text(string $text): string
{
    $text = str_replace("\xc2\xa0", ' ', $text);
    $text = mb_strtolower($text);
    $text = preg_replace('/\s+/u', ' ', $text) ?? '';

    return trim($text);
}

function ktp_word_cell_text(DOMElement $cell, DOMXPath $xpath): string
{
    $parts = [];
    foreach ($xpath->query('./w:p', $cell) as $paragraph) {
        $line = '';
        foreach ($xpath->query('.//w:t|.//w:tab', $paragraph) as $node) {
            if ($node->localName === 'tab') {
                $line .= "\t";
            } else {
                $line .= $node->textContent;
            }
        }
        if (str_contains($line, "\n") === false) {
            foreach ($xpath->query('.//w:br', $paragraph) as $br) {
                $line .= "\n";
            }
        }
        $parts[] = $line;
    }

    return trim(implode("\n", $parts));
}

function ktp_word_extract_tables_from_xml(string $xml): array
{
    $dom = new DOMDocument();
    if (@$dom->loadXML($xml) === false) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', KTP_WORD_NS);

    $tables = [];
    foreach ($xpath->query('//w:tbl') as $table) {
        $rows = [];
        foreach ($xpath->query('./w:tr', $table) as $row) {
            $cells = [];
            foreach ($xpath->query('./w:tc', $row) as $cell) {
                $cells[] = ktp_word_cell_text($cell, $xpath);
            }
            if ($cells !== []) {
                $rows[] = $cells;
            }
        }
        if ($rows !== []) {
            $tables[] = $rows;
        }
    }

    return $tables;
}

function ktp_word_detect_column_map(array $normalizedCells): ?array
{
    $map = [];
    foreach ($normalizedCells as $index => $cell) {
        if ($cell === '') {
            continue;
        }
        if (str_contains($cell, 'наименование') && str_contains($cell, 'тем')) {
            $map['name'] = $index;
        }
        if (str_contains($cell, 'содержание') && str_contains($cell, 'материал')) {
            $map['content'] = $index;
        }
        if (
            str_contains($cell, 'объем')
            || str_contains($cell, 'ак. ч')
            || str_contains($cell, 'академ')
            || str_contains($cell, 'в том числе')
        ) {
            $map['hours'] = $index;
        }
        if (str_contains($cell, 'компетенц')) {
            $map['competencies'] = $index;
        }
    }

    if (isset($map['name'], $map['content'], $map['hours'], $map['competencies'])) {
        return $map;
    }

    if (count($normalizedCells) >= 4) {
        $joined = implode(' ', $normalizedCells);
        if (
            str_contains($joined, 'наименование')
            && str_contains($joined, 'тем')
            && str_contains($joined, 'содержание')
            && (str_contains($joined, 'объем') || str_contains($joined, 'ак. ч'))
            && str_contains($joined, 'компетенц')
        ) {
            return [
                'name' => 0,
                'content' => 1,
                'hours' => 2,
                'competencies' => 3,
            ];
        }
    }

    return null;
}

function ktp_word_find_target_table(array $tables): ?array
{
    foreach ($tables as $rows) {
        $limit = min(4, count($rows));
        for ($rowIndex = 0; $rowIndex < $limit; $rowIndex++) {
            $normalized = array_map('ktp_word_normalize_text', $rows[$rowIndex]);
            $map = ktp_word_detect_column_map($normalized);
            if ($map !== null) {
                return [
                    'rows' => $rows,
                    'header_row' => $rowIndex,
                    'map' => $map,
                ];
            }

            if (isset($rows[$rowIndex + 1])) {
                $combined = [];
                $maxCols = max(count($rows[$rowIndex]), count($rows[$rowIndex + 1]));
                for ($col = 0; $col < $maxCols; $col++) {
                    $combined[] = ktp_word_normalize_text(
                        trim(($rows[$rowIndex][$col] ?? '') . ' ' . ($rows[$rowIndex + 1][$col] ?? ''))
                    );
                }
                $map = ktp_word_detect_column_map($combined);
                if ($map !== null) {
                    return [
                        'rows' => $rows,
                        'header_row' => $rowIndex + 1,
                        'map' => $map,
                    ];
                }
            }
        }
    }

    return null;
}

function ktp_word_parse_hours(string $text): array
{
    $text = str_replace(',', '.', $text);
    preg_match_all('/\d+(?:\.\d+)?/u', $text, $matches);
    $numbers = array_map('floatval', $matches[0] ?? []);
    $hours = $numbers[0] ?? 0.0;
    $orientation = $numbers[1] ?? 0.0;

    return [
        'hours' => $hours,
        'orientation_hours' => $orientation,
    ];
}

function ktp_word_parse_competency_codes(string $text): array
{
    $ok = [];
    $pk = [];

    if (preg_match_all('/\b(?:OK|ОК)[.\s-]*(\d+)\b/ui', $text, $okMatches)) {
        foreach ($okMatches[1] as $num) {
            $ok[] = 'OK' . (int) $num;
        }
    }

    if (preg_match_all('/\b(?:PK|ПК)[.\s-]*(\d+)\b/ui', $text, $pkMatches)) {
        foreach ($pkMatches[1] as $num) {
            $pk[] = 'PK' . (int) $num;
        }
    }

    return [
        'ok_codes' => normalize_ktp_competency_codes($ok, 'OK'),
        'pk_codes' => normalize_ktp_competency_codes($pk, 'PK'),
    ];
}

function ktp_word_guess_lesson_type(string $title, string $content): string
{
    $text = mb_strtolower($title . ' ' . $content);
    if (preg_match('/\bэкзамен/ui', $text)) {
        return 'exam';
    }
    if (preg_match('/дифференцированн/ui', $text) && preg_match('/зач/ui', $text)) {
        return 'diff_credit';
    }
    if (preg_match('/\bзач/ui', $text)) {
        return 'credit';
    }
    if (preg_match('/контрольн/ui', $text)) {
        return 'control';
    }
    if (preg_match('/самостоятельн/ui', $text)) {
        return 'independent';
    }
    if (preg_match('/практич|лаборатор/ui', $text)) {
        return 'practice';
    }
    if (preg_match('/лекци/ui', $text)) {
        return 'lecture';
    }

    return 'lecture';
}

function ktp_word_build_title(string $name, string $content): string
{
    $name = trim($name);
    $content = trim($content);

    if ($name === '') {
        return $content;
    }
    if ($content === '' || $content === $name) {
        return $name;
    }
    if (preg_match('/^(?:раздел\s*)?[\d.\s]+$/ui', $name)) {
        return $content;
    }

    return $name;
}

function ktp_word_is_skip_row(string $title, float $hours, string $nameCell): bool
{
    $check = ktp_word_normalize_text($title . ' ' . $nameCell);
    if ($check === '') {
        return true;
    }
    if (preg_match('/^(?:итого|всего|общий объем|общий объём)\b/ui', $check)) {
        return true;
    }
    if ($hours <= 0 && preg_match('/^(?:раздел|модуль|курс)\b/ui', $check)) {
        return true;
    }

    return false;
}

function ktp_word_is_semester_marker(string $text): bool
{
    return (bool) preg_match('/\b2\s*[-]?\s*семестр/ui', $text);
}

function ktp_word_row_value(array $row, int $index): string
{
    return trim((string) ($row[$index] ?? ''));
}

function ktp_word_parse_table_rows(array $rows, int $headerRow, array $map): array
{
    $parsed = [];
    $lastTitle = '';

    for ($i = $headerRow + 1, $count = count($rows); $i < $count; $i++) {
        $row = $rows[$i];
        $nameCell = ktp_word_row_value($row, $map['name']);
        $contentCell = ktp_word_row_value($row, $map['content']);
        $hoursCell = ktp_word_row_value($row, $map['hours']);
        $competenciesCell = ktp_word_row_value($row, $map['competencies']);
        $hoursData = ktp_word_parse_hours($hoursCell);
        $hours = (float) $hoursData['hours'];
        $title = ktp_word_build_title($nameCell, $contentCell);

        if ($title === '' && $lastTitle !== '' && $hours > 0) {
            $title = $lastTitle;
        }

        if (ktp_word_is_semester_marker($nameCell . ' ' . $contentCell . ' ' . $title)) {
            $parsed[] = [
                'title' => ktp_semester_marker_title(),
                'lesson_type' => 'semester_2',
                'hours' => 0.0,
                'orientation_hours' => 0.0,
                'ok_codes' => '',
                'pk_codes' => '',
            ];
            $lastTitle = '';
            continue;
        }

        if (ktp_word_is_skip_row($title, $hours, $nameCell)) {
            continue;
        }

        if ($title !== '') {
            $lastTitle = $title;
        }

        $competencies = ktp_word_parse_competency_codes($competenciesCell);
        $lessonType = ktp_word_guess_lesson_type($title, $contentCell);
        if (ktp_is_attestation_type($lessonType)) {
            $title = ktp_attestation_title($lessonType);
        }

        $parsed[] = [
            'title' => $title,
            'lesson_type' => $lessonType,
            'hours' => normalize_ktp_hours($hours > 0 ? $hours : 2),
            'orientation_hours' => normalize_ktp_orientation_hours($hoursData['orientation_hours']),
            'ok_codes' => $competencies['ok_codes'],
            'pk_codes' => $competencies['pk_codes'],
        ];
    }

    return $parsed;
}

function parse_ktp_word_file(string $path, ?string $extension = null): array
{
    if (!is_readable($path)) {
        return ['success' => false, 'error' => 'Файл недоступен для чтения.'];
    }

    $extension = strtolower((string) ($extension ?: pathinfo($path, PATHINFO_EXTENSION)));
    if ($extension !== '' && $extension !== 'docx') {
        return ['success' => false, 'error' => 'Поддерживается только формат DOCX. Сохраните документ Word как .docx.'];
    }

    if (!class_exists(ZipArchive::class)) {
        return ['success' => false, 'error' => 'На сервере не доступно расширение ZipArchive для чтения DOCX.'];
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return ['success' => false, 'error' => 'Не удалось открыть DOCX-файл. Убедитесь, что документ сохранён в формате .docx.'];
    }

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    if ($xml === false || $xml === '') {
        return ['success' => false, 'error' => 'В документе не найдено содержимое.'];
    }

    $tables = ktp_word_extract_tables_from_xml($xml);
    if ($tables === []) {
        return ['success' => false, 'error' => 'В документе не найдено таблиц.'];
    }

    $target = ktp_word_find_target_table($tables);
    if ($target === null) {
        return [
            'success' => false,
            'error' => 'Не найдена таблица с заголовками: «Наименование разделов и тем», «Содержание учебного материала…», «Объем, ак. ч.» и «Коды компетенций…».',
        ];
    }

    $rows = ktp_word_parse_table_rows($target['rows'], $target['header_row'], $target['map']);
    if ($rows === []) {
        return ['success' => false, 'error' => 'Таблица найдена, но строки для импорта не обнаружены.'];
    }

    return [
        'success' => true,
        'rows' => $rows,
        'row_count' => count($rows),
    ];
}

function ktp_word_import_store_preview(int $itemId, array $rows, string $filename): void
{
    if (!isset($_SESSION['ktp_word_import']) || !is_array($_SESSION['ktp_word_import'])) {
        $_SESSION['ktp_word_import'] = [];
    }

    $_SESSION['ktp_word_import'][$itemId] = [
        'rows' => $rows,
        'filename' => $filename,
        'created_at' => time(),
    ];
}

function ktp_word_import_get_preview(int $itemId): ?array
{
    $preview = $_SESSION['ktp_word_import'][$itemId] ?? null;
    if (!is_array($preview)) {
        return null;
    }

    if ((time() - (int) ($preview['created_at'] ?? 0)) > 3600) {
        unset($_SESSION['ktp_word_import'][$itemId]);

        return null;
    }

    return $preview;
}

function ktp_word_import_clear_preview(int $itemId): void
{
    unset($_SESSION['ktp_word_import'][$itemId]);
}

function upload_and_parse_ktp_word(int $curriculumItemId, array $file): array
{
    $item = get_curriculum_item_by_id($curriculumItemId);
    if ($item === null) {
        return ['success' => false, 'error' => 'Предмет учебного плана не найден.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'Выберите файл Word.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Не удалось загрузить файл.'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 10 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Размер файла не должен превышать 10 МБ.'];
    }

    $originalName = basename((string) ($file['name'] ?? 'program.docx'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension !== 'docx') {
        return ['success' => false, 'error' => 'Поддерживается только формат DOCX.'];
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    $parsed = parse_ktp_word_file($tmpPath, $extension);
    if (!$parsed['success']) {
        return $parsed;
    }

    ktp_word_import_store_preview($curriculumItemId, $parsed['rows'], $originalName);

    return [
        'success' => true,
        'rows' => $parsed['rows'],
        'row_count' => (int) $parsed['row_count'],
        'filename' => $originalName,
    ];
}
