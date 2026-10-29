<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../archive.php';
require_once __DIR__ . '/../archive_import.php';
require_once __DIR__ . '/../activity_log.php';

require_archive_manager();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Ошибка безопасности. Обновите страницу.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$dryRun = isset($_POST['dry_run']) && (string) $_POST['dry_run'] !== '0';
$allowCreate = isset($_POST['allow_create']) && (string) $_POST['allow_create'] === '1';

$result = archive_import_uploaded_json_files(
    $_FILES['files'] ?? [],
    $dryRun,
    $allowCreate
);

$okCount = 0;
foreach ($result['results'] as $row) {
    if (!empty($row['ok'])) {
        $okCount++;
    }
}

log_activity('archive.import_json', 'archive', null, null, [
    'dry_run' => $dryRun,
    'allow_create' => $allowCreate,
    'files' => count($_FILES['files']['name'] ?? []),
    'ok' => $okCount,
    'failed' => (int) ($result['failed'] ?? 0),
]);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
