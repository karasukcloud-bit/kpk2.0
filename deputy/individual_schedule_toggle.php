<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/individual_schedule.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
if (!can_manage_individual_schedule()) {
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

$enabledRaw = $_POST['enabled'] ?? '';
$enabled = $enabledRaw === '1' || $enabledRaw === 1 || $enabledRaw === true || $enabledRaw === 'true';

$result = set_student_individual_schedule(
    (int) ($_POST['student_id'] ?? 0),
    (string) ($_POST['academic_year'] ?? ''),
    (string) ($_POST['semester'] ?? ''),
    $enabled
);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
