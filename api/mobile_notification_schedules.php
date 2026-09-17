<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mobile_notification_schedules.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    ensure_mobile_notification_schedules_schema();
    echo json_encode([
        'success' => true,
        'schedules' => mobile_notification_schedules_public_payload(),
        'updated_at' => date('c'),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Не удалось получить расписания.',
        'schedules' => [],
    ], JSON_UNESCAPED_UNICODE);
}
