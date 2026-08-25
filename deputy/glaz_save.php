<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/glaz.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Доступ запрещён.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Ошибка безопасности.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = save_glaz_schedule(
    (int) ($_POST['student_id'] ?? 0),
    (int) ($_POST['curriculum_item_id'] ?? 0),
    (string) ($_POST['academic_year'] ?? ''),
    (string) ($_POST['semester'] ?? ''),
    (string) ($_POST['liquidation_date'] ?? ''),
    (string) ($_POST['liquidation_time'] ?? ''),
    isset($_POST['commission']) && is_array($_POST['commission']) ? $_POST['commission'] : []
);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
