<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/teachers.php';

require_admin();

header('Content-Type: application/json; charset=UTF-8');

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

$id = (int) ($_POST['teacher_id'] ?? 0);
$sent = ($_POST['sent'] ?? '') === '1';

$result = set_teacher_auth_credentials_sent($id, $sent);
http_response_code($result['success'] ? 200 : 400);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
