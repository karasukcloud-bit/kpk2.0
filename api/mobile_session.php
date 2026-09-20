<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mobile_auth_tokens.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Cookie, X-Mobile-Session');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    ensure_mobile_auth_tokens_schema();

    $raw = file_get_contents('php://input') ?: '';
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $action = (string) ($input['action'] ?? '');

    if ($action === 'issue') {
        // Сессия из cookie или явный session_id от мобильного клиента
        $forcedSessionId = trim((string) ($input['session_id'] ?? ''));
        if ($forcedSessionId !== '' && preg_match('/^[a-zA-Z0-9,-]{16,128}$/', $forcedSessionId)) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            session_id($forcedSessionId);
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => false,
            ]);
            clear_current_user_cache();
        }

        $user = current_user();
        if ($user === null) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Требуется авторизация.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $result = mobile_auth_issue_token(
            (int) $user['id'],
            isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : null
        );
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'exchange') {
        $token = trim((string) ($input['token'] ?? ''));
        if ($token === '' && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(\S+)/i', (string) $_SERVER['HTTP_AUTHORIZATION'], $m)) {
                $token = trim($m[1]);
            }
        }

        $result = mobile_auth_exchange_token($token);
        if (!$result['success']) {
            http_response_code(401);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'revoke') {
        $token = trim((string) ($input['token'] ?? ''));
        mobile_auth_revoke_token($token);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Неизвестное действие.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка сервера.',
    ], JSON_UNESCAPED_UNICODE);
}
