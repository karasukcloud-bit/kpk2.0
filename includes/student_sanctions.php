<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/curriculum.php';

function ensure_student_sanctions_schema(?PDO $pdo = null): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo = $pdo ?? db();
    if (!$pdo->query("SHOW TABLES LIKE 'student_sanctions'")->fetch()) {
        $pdo->exec(
            "CREATE TABLE student_sanctions (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                student_id INT UNSIGNED NOT NULL,
                issued_on DATE NOT NULL,
                academic_year VARCHAR(9) NOT NULL,
                sanction_type ENUM('remark', 'reprimand', 'strict_reprimand') NOT NULL DEFAULT 'remark',
                order_number VARCHAR(100) NOT NULL DEFAULT '',
                description VARCHAR(500) NOT NULL DEFAULT '',
                scan_stored_name VARCHAR(255) NULL,
                scan_original_name VARCHAR(255) NULL,
                created_by INT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_student_sanctions_student (student_id),
                KEY idx_student_sanctions_year (academic_year),
                KEY idx_student_sanctions_issued (issued_on),
                CONSTRAINT fk_student_sanctions_student
                    FOREIGN KEY (student_id) REFERENCES students(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_student_sanctions_user
                    FOREIGN KEY (created_by) REFERENCES users(id)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        return;
    }

    $typeCol = $pdo->query("SHOW COLUMNS FROM student_sanctions LIKE 'sanction_type'")->fetch();
    if (!$typeCol) {
        $pdo->exec(
            "ALTER TABLE student_sanctions
             ADD sanction_type ENUM('remark', 'reprimand', 'strict_reprimand') NOT NULL DEFAULT 'remark'
             AFTER academic_year"
        );
    }
}

/**
 * @return array<string, string>
 */
function student_sanction_type_options(): array
{
    return [
        'remark' => 'Замечание',
        'reprimand' => 'Выговор',
        'strict_reprimand' => 'Строгий выговор',
    ];
}

function normalize_student_sanction_type(string $type): string
{
    $type = trim($type);
    $map = student_sanction_type_options();

    return isset($map[$type]) ? $type : 'remark';
}

function student_sanction_type_label(string $type): string
{
    $map = student_sanction_type_options();
    $type = normalize_student_sanction_type($type);

    return $map[$type];
}

function student_sanctions_upload_dir(): string
{
    return dirname(__DIR__) . '/uploads/sanctions';
}

function academic_year_from_date(string $date): string
{
    $ts = strtotime($date);
    if ($ts === false) {
        return get_default_academic_year();
    }
    $month = (int) date('n', $ts);
    $year = (int) date('Y', $ts);
    if ($month >= 9) {
        return $year . '-' . ($year + 1);
    }

    return ($year - 1) . '-' . $year;
}

function format_sanction_date(?string $date): string
{
    if ($date === null || $date === '' || $date === '0000-00-00') {
        return '';
    }
    $ts = strtotime($date);

    return $ts === false ? $date : date('d.m.Y', $ts);
}

function format_sanction_label(array $row): string
{
    $parts = [];
    $typeLabel = student_sanction_type_label((string) ($row['sanction_type'] ?? 'remark'));
    $parts[] = mb_strtolower($typeLabel);
    $order = trim((string) ($row['order_number'] ?? ''));
    $date = format_sanction_date(isset($row['issued_on']) ? (string) $row['issued_on'] : null);
    if ($order !== '') {
        $parts[] = 'приказ №' . $order;
    }
    if ($date !== '') {
        $parts[] = 'от ' . $date;
    }
    $desc = trim((string) ($row['description'] ?? ''));
    if ($desc !== '') {
        $parts[] = $desc;
    }

    return implode(', ', $parts);
}

/**
 * @return list<array<string, mixed>>
 */
function get_student_sanctions(int $studentId): array
{
    ensure_student_sanctions_schema();
    if ($studentId < 1) {
        return [];
    }
    $stmt = db()->prepare(
        'SELECT * FROM student_sanctions
         WHERE student_id = ?
         ORDER BY issued_on DESC, id DESC'
    );
    $stmt->execute([$studentId]);

    return $stmt->fetchAll();
}

/**
 * @param list<int> $studentIds
 * @return array<int, list<array<string, mixed>>>
 */
function get_students_sanctions_map(array $studentIds): array
{
    ensure_student_sanctions_schema();
    $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
    if ($studentIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $stmt = db()->prepare(
        "SELECT * FROM student_sanctions
         WHERE student_id IN ($placeholders)
         ORDER BY issued_on DESC, id DESC"
    );
    $stmt->execute($studentIds);

    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $sid = (int) $row['student_id'];
        $map[$sid][] = $row;
    }

    return $map;
}

function get_student_sanction(int $id): ?array
{
    ensure_student_sanctions_schema();
    if ($id < 1) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM student_sanctions WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * @return array{success:bool,error?:string,id?:int}
 */
function save_student_sanction(
    int $studentId,
    string $issuedOn,
    string $orderNumber,
    string $description = '',
    ?array $file = null,
    ?int $createdBy = null,
    ?int $id = null,
    string $sanctionType = 'remark'
): array {
    ensure_student_sanctions_schema();
    if ($studentId < 1) {
        return ['success' => false, 'error' => 'Студент не указан.'];
    }

    $issuedOn = trim($issuedOn);
    if ($issuedOn === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $issuedOn)) {
        return ['success' => false, 'error' => 'Укажите дату вынесения.'];
    }
    $ts = strtotime($issuedOn);
    if ($ts === false) {
        return ['success' => false, 'error' => 'Некорректная дата вынесения.'];
    }

    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return ['success' => false, 'error' => 'Укажите номер приказа.'];
    }
    $sanctionType = normalize_student_sanction_type($sanctionType);
    if (function_exists('mb_substr')) {
        $orderNumber = mb_substr($orderNumber, 0, 100, 'UTF-8');
        $description = mb_substr(trim($description), 0, 500, 'UTF-8');
    } else {
        $orderNumber = substr($orderNumber, 0, 100);
        $description = substr(trim($description), 0, 500);
    }

    $academicYear = academic_year_from_date($issuedOn);
    $existing = $id !== null && $id > 0 ? get_student_sanction($id) : null;
    if ($id !== null && $id > 0 && $existing === null) {
        return ['success' => false, 'error' => 'Запись не найдена.'];
    }
    if ($existing !== null && (int) $existing['student_id'] !== $studentId) {
        return ['success' => false, 'error' => 'Нельзя изменить студента у существующей записи.'];
    }

    $scanStored = $existing['scan_stored_name'] ?? null;
    $scanOriginal = $existing['scan_original_name'] ?? null;

    $hasFile = is_array($file)
        && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($hasFile) {
        $upload = store_student_sanction_scan($studentId, $file);
        if (!$upload['success']) {
            return $upload;
        }
        if ($scanStored) {
            delete_student_sanction_scan_file((string) $scanStored);
        }
        $scanStored = $upload['stored_name'];
        $scanOriginal = $upload['original_name'];
    }

    if ($existing !== null) {
        $stmt = db()->prepare(
            'UPDATE student_sanctions
             SET issued_on = ?, academic_year = ?, sanction_type = ?, order_number = ?, description = ?,
                 scan_stored_name = ?, scan_original_name = ?
             WHERE id = ? AND student_id = ?'
        );
        $stmt->execute([
            $issuedOn,
            $academicYear,
            $sanctionType,
            $orderNumber,
            $description,
            $scanStored,
            $scanOriginal,
            (int) $existing['id'],
            $studentId,
        ]);

        return ['success' => true, 'id' => (int) $existing['id']];
    }

    $stmt = db()->prepare(
        'INSERT INTO student_sanctions
         (student_id, issued_on, academic_year, sanction_type, order_number, description,
          scan_stored_name, scan_original_name, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $studentId,
        $issuedOn,
        $academicYear,
        $sanctionType,
        $orderNumber,
        $description,
        $scanStored,
        $scanOriginal,
        $createdBy,
    ]);

    return ['success' => true, 'id' => (int) db()->lastInsertId()];
}

/**
 * @return array{success:bool,error?:string,stored_name?:string,original_name?:string}
 */
function store_student_sanction_scan(int $studentId, array $file): array
{
    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'Выберите файл скана приказа.'];
    }
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        return ['success' => false, 'error' => 'Файл слишком большой для загрузки.'];
    }
    if ($err !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Не удалось загрузить файл (код ' . $err . ').'];
    }

    $maxBytes = 20 * 1024 * 1024;
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        return ['success' => false, 'error' => 'Размер файла не должен превышать 20 МБ.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['success' => false, 'error' => 'Временный файл загрузки недоступен.'];
    }

    $originalName = basename((string) ($file['name'] ?? 'scan'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = (string) finfo_file($finfo, $tmp);
            finfo_close($finfo);
        }
    }

    $mimeToExt = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/bmp' => 'bmp',
        'image/x-ms-bmp' => 'bmp',
        'image/tiff' => 'tiff',
    ];
    $allowedExt = ['pdf', 'jpg', 'jpeg', 'jpe', 'jfif', 'png', 'webp', 'gif', 'bmp', 'tif', 'tiff'];

    if ($extension === 'jpeg' || $extension === 'jpe' || $extension === 'jfif') {
        $extension = 'jpg';
    }
    if ($extension === 'tif') {
        $extension = 'tiff';
    }

    if ($extension === '' || !in_array($extension, $allowedExt, true)) {
        if ($mime !== '' && isset($mimeToExt[$mime])) {
            $extension = $mimeToExt[$mime];
        }
    }

    $okByExt = in_array($extension, ['pdf', 'jpg', 'png', 'webp', 'gif', 'bmp', 'tiff'], true);
    $okByMime = $mime !== '' && isset($mimeToExt[$mime]);
    if (!$okByExt && !$okByMime) {
        return [
            'success' => false,
            'error' => 'Допустимы скан/фото приказа: PDF, JPG, PNG, WEBP, GIF, BMP, TIFF.',
        ];
    }
    if (!$okByExt && $okByMime) {
        $extension = $mimeToExt[$mime];
    }

    if ($extension !== 'pdf') {
        $info = @getimagesize($tmp);
        // BMP/TIFF на части сборок PHP без сигнатуры в getimagesize — тогда опираемся на MIME.
        if ($info === false && !in_array($extension, ['bmp', 'tiff'], true)) {
            return ['success' => false, 'error' => 'Файл изображения повреждён или не поддерживается.'];
        }
    } elseif ($mime !== '' && $mime !== 'application/pdf') {
        // Расширение .pdf, но содержимое не PDF
        if (isset($mimeToExt[$mime]) && $mimeToExt[$mime] !== 'pdf') {
            return ['success' => false, 'error' => 'Файл с расширением PDF не является PDF-документом.'];
        }
    }

    $dir = student_sanctions_upload_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['success' => false, 'error' => 'Не удалось создать папку для загрузок.'];
    }
    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php\\d)$\">\nDeny from all\n</FilesMatch>\n");
    }

    $storedName = 's' . $studentId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $dest = $dir . '/' . $storedName;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['success' => false, 'error' => 'Не удалось сохранить файл на сервере.'];
    }

    return [
        'success' => true,
        'stored_name' => $storedName,
        'original_name' => $originalName !== '' ? $originalName : ('scan.' . $extension),
    ];
}

function delete_student_sanction_scan_file(string $storedName): void
{
    $storedName = basename($storedName);
    if ($storedName === '' || strpos($storedName, '..') !== false) {
        return;
    }
    $path = student_sanctions_upload_dir() . '/' . $storedName;
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * @return array{success:bool,error?:string}
 */
function delete_student_sanction(int $id, int $studentId): array
{
    ensure_student_sanctions_schema();
    $row = get_student_sanction($id);
    if ($row === null || (int) $row['student_id'] !== $studentId) {
        return ['success' => false, 'error' => 'Запись не найдена.'];
    }

    db()->prepare('DELETE FROM student_sanctions WHERE id = ?')->execute([$id]);
    if (!empty($row['scan_stored_name'])) {
        delete_student_sanction_scan_file((string) $row['scan_stored_name']);
    }

    return ['success' => true];
}

/**
 * Текст для характеристики по всем взысканиям студента.
 */
function characteristic_penalties_from_sanctions(int $studentId): string
{
    $rows = get_student_sanctions($studentId);
    if ($rows === []) {
        return 'дисциплинарных взысканий не имеет';
    }

    $labels = [];
    foreach ($rows as $row) {
        $labels[] = format_sanction_label($row);
    }

    if (count($labels) === 1) {
        return 'имеет дисциплинарное взыскание: ' . $labels[0];
    }

    return 'имеет дисциплинарные взыскания: ' . implode('; ', $labels);
}

/**
 * Список взысканий группы для справки «О группе».
 *
 * @param list<array<string, mixed>> $students
 * @return list<array<string, mixed>>
 */
function build_group_sanctions_report(array $students): array
{
    $ids = [];
    $names = [];
    foreach ($students as $student) {
        $id = (int) ($student['id'] ?? 0);
        if ($id < 1) {
            continue;
        }
        $ids[] = $id;
        $names[$id] = (string) ($student['full_name'] ?? '');
    }
    if ($ids === []) {
        return [];
    }

    $map = get_students_sanctions_map($ids);
    $out = [];
    foreach ($map as $studentId => $rows) {
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row['id'],
                'student_id' => (int) $studentId,
                'student_name' => $names[$studentId] ?? '',
                'issued_on' => (string) $row['issued_on'],
                'issued_on_label' => format_sanction_date((string) $row['issued_on']),
                'academic_year' => (string) $row['academic_year'],
                'sanction_type' => (string) ($row['sanction_type'] ?? 'remark'),
                'sanction_type_label' => student_sanction_type_label((string) ($row['sanction_type'] ?? 'remark')),
                'order_number' => (string) $row['order_number'],
                'description' => (string) ($row['description'] ?? ''),
                'label' => format_sanction_label($row),
                'has_scan' => !empty($row['scan_stored_name']),
            ];
        }
    }

    usort($out, static function (array $a, array $b): int {
        $byYear = strcmp((string) $b['academic_year'], (string) $a['academic_year']);
        if ($byYear !== 0) {
            return $byYear;
        }
        $byDate = strcmp((string) $b['issued_on'], (string) $a['issued_on']);
        if ($byDate !== 0) {
            return $byDate;
        }

        return strcasecmp((string) $a['student_name'], (string) $b['student_name']);
    });

    return $out;
}

/**
 * ID студентов группы с взысканиями в указанном календарном месяце (YYYY-MM).
 *
 * @param list<int> $studentIds
 * @return list<int>
 */
function student_ids_with_sanctions_in_month(array $studentIds, string $month): array
{
    ensure_student_sanctions_schema();
    $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
    if ($studentIds === [] || !preg_match('/^\d{4}-\d{2}$/', $month)) {
        return [];
    }

    $from = $month . '-01';
    $to = date('Y-m-t', strtotime($from) ?: time());
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $params = array_merge($studentIds, [$from, $to]);
    $stmt = db()->prepare(
        "SELECT DISTINCT student_id FROM student_sanctions
         WHERE student_id IN ($placeholders)
           AND issued_on BETWEEN ? AND ?"
    );
    $stmt->execute($params);

    return array_map('intval', array_column($stmt->fetchAll(), 'student_id'));
}

/**
 * Группировка списка взысканий по учебным годам.
 *
 * @param list<array<string, mixed>> $rows
 * @return list<array{academic_year:string,items:list<array<string,mixed>>}>
 */
function group_sanctions_by_academic_year(array $rows): array
{
    $groups = [];
    foreach ($rows as $row) {
        $year = (string) ($row['academic_year'] ?? '');
        if ($year === '') {
            $year = academic_year_from_date((string) ($row['issued_on'] ?? ''));
        }
        if (!isset($groups[$year])) {
            $groups[$year] = [];
        }
        $groups[$year][] = $row;
    }

    krsort($groups, SORT_STRING);
    $out = [];
    foreach ($groups as $year => $items) {
        $out[] = [
            'academic_year' => $year,
            'items' => $items,
        ];
    }

    return $out;
}

/**
 * Поиск студентов с взысканиями для панели воспитателя.
 *
 * @return list<array{
 *   student_id:int,
 *   full_name:string,
 *   group_id:int,
 *   group_number:string,
 *   sanctions_count:int,
 *   last_issued_on:?string,
 *   sanctions:list<array<string,mixed>>
 * }>
 */
function search_students_with_sanctions(?int $groupId = null, string $query = ''): array
{
    ensure_student_sanctions_schema();
    $query = trim($query);

    $sql = 'SELECT s.id AS student_id, s.full_name, s.group_id, g.number AS group_number,
                   COUNT(ss.id) AS sanctions_count,
                   MAX(ss.issued_on) AS last_issued_on
            FROM student_sanctions ss
            INNER JOIN students s ON s.id = ss.student_id
            INNER JOIN study_groups g ON g.id = s.group_id
            WHERE 1=1';
    $params = [];
    if ($groupId !== null && $groupId > 0) {
        $sql .= ' AND s.group_id = ?';
        $params[] = $groupId;
    }
    if ($query !== '') {
        $sql .= ' AND s.full_name LIKE ?';
        $params[] = '%' . $query . '%';
    }
    $sql .= ' GROUP BY s.id, s.full_name, s.group_id, g.number
              ORDER BY last_issued_on DESC, s.full_name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    if ($rows === []) {
        return [];
    }

    $ids = array_map(static fn (array $r): int => (int) $r['student_id'], $rows);
    $map = get_students_sanctions_map($ids);
    $out = [];
    foreach ($rows as $row) {
        $sid = (int) $row['student_id'];
        $sanctions = [];
        foreach ($map[$sid] ?? [] as $item) {
            $sanctions[] = [
                'id' => (int) $item['id'],
                'issued_on' => (string) $item['issued_on'],
                'issued_on_label' => format_sanction_date((string) $item['issued_on']),
                'academic_year' => (string) $item['academic_year'],
                'sanction_type' => (string) ($item['sanction_type'] ?? 'remark'),
                'sanction_type_label' => student_sanction_type_label((string) ($item['sanction_type'] ?? 'remark')),
                'order_number' => (string) $item['order_number'],
                'description' => (string) ($item['description'] ?? ''),
                'label' => format_sanction_label($item),
                'has_scan' => !empty($item['scan_stored_name']),
            ];
        }
        $out[] = [
            'student_id' => $sid,
            'full_name' => (string) $row['full_name'],
            'group_id' => (int) $row['group_id'],
            'group_number' => (string) $row['group_number'],
            'sanctions_count' => (int) $row['sanctions_count'],
            'last_issued_on' => $row['last_issued_on'] !== null ? (string) $row['last_issued_on'] : null,
            'sanctions' => $sanctions,
        ];
    }

    return $out;
}

/**
 * Отдать скан приказа (для куратора/воспитателя).
 */
function serve_student_sanction_file(int $id): void
{
    ensure_student_sanctions_schema();
    $row = $id > 0 ? get_student_sanction($id) : null;
    if ($row === null || empty($row['scan_stored_name'])) {
        http_response_code(404);
        echo 'Файл не найден.';
        exit;
    }

    $stored = basename((string) $row['scan_stored_name']);
    $path = student_sanctions_upload_dir() . '/' . $stored;
    if (!is_file($path)) {
        http_response_code(404);
        echo 'Файл отсутствует на диске.';
        exit;
    }

    $ext = strtolower(pathinfo($stored, PATHINFO_EXTENSION));
    $mimeMap = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
    ];
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    $downloadName = (string) ($row['scan_original_name'] ?: $stored);

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($path));
    header('Content-Disposition: inline; filename="' . str_replace('"', '', $downloadName) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}
