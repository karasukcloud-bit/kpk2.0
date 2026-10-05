<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mobile_auth_tokens.php';
require_once __DIR__ . '/../includes/mobile_notification_schedules.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    ensure_mobile_notification_schedules_schema();
    ensure_mobile_auth_tokens_schema();

    $roles = [];
    $isAdmin = false;

    $token = '';
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])
        && preg_match('/Bearer\s+(\S+)/i', (string) $_SERVER['HTTP_AUTHORIZATION'], $m)
    ) {
        $token = trim($m[1]);
    }
    if ($token === '') {
        $token = trim((string) ($_GET['token'] ?? ''));
    }

    if ($token !== '') {
        $hash = mobile_auth_hash_token($token);
        $stmt = db()->prepare(
            'SELECT t.user_id, t.expires_at, u.is_active, u.role
             FROM mobile_auth_tokens t
             INNER JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ?
             LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if ($row
            && (int) $row['is_active']
            && strtotime((string) $row['expires_at']) >= time()
        ) {
            $userId = (int) $row['user_id'];
            $baseRole = (string) $row['role'];
            $isAdmin = $baseRole === 'admin';
            $roles = get_user_staff_roles($userId);
            if ($baseRole === 'student') {
                $roles[] = 'student';
            }
        }
    }

    echo json_encode([
        'success' => true,
        'schedules' => mobile_notification_schedules_public_payload($roles, $isAdmin),
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
