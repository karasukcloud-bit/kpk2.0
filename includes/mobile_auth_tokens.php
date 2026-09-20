<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

const MOBILE_AUTH_TOKEN_TTL_DAYS = 180;

function ensure_mobile_auth_tokens_schema(?PDO $pdo = null): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo = $pdo ?? db();
    $table = $pdo->query("SHOW TABLES LIKE 'mobile_auth_tokens'")->fetch();
    if ($table) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE mobile_auth_tokens (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            last_used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            user_agent VARCHAR(255) NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_mobile_auth_token_hash (token_hash),
            KEY idx_mobile_auth_user (user_id),
            KEY idx_mobile_auth_expires (expires_at),
            CONSTRAINT fk_mobile_auth_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function mobile_auth_hash_token(string $token): string
{
    return hash('sha256', $token);
}

/**
 * @return array{success: bool, error?: string, token?: string, expires_at?: string}
 */
function mobile_auth_issue_token(int $userId, ?string $userAgent = null): array
{
    ensure_mobile_auth_tokens_schema();

    if ($userId <= 0) {
        return ['success' => false, 'error' => 'Не авторизован.'];
    }

    $stmt = db()->prepare('SELECT id, is_active FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || !(int) $user['is_active']) {
        return ['success' => false, 'error' => 'Учётная запись недоступна.'];
    }

    $token = bin2hex(random_bytes(32));
    $hash = mobile_auth_hash_token($token);
    $expiresAt = (new DateTimeImmutable('+' . MOBILE_AUTH_TOKEN_TTL_DAYS . ' days'))->format('Y-m-d H:i:s');
    $ua = $userAgent !== null ? mb_substr($userAgent, 0, 255) : null;

    // Один активный токен на пользователя достаточно для мобильного клиента
    $del = db()->prepare('DELETE FROM mobile_auth_tokens WHERE user_id = ?');
    $del->execute([$userId]);

    $ins = db()->prepare(
        'INSERT INTO mobile_auth_tokens (user_id, token_hash, expires_at, user_agent)
         VALUES (?, ?, ?, ?)'
    );
    $ins->execute([$userId, $hash, $expiresAt, $ua]);

    return [
        'success' => true,
        'token' => $token,
        'expires_at' => $expiresAt,
    ];
}

/**
 * @return array{success: bool, error?: string, session_name?: string, session_id?: string, user_id?: int}
 */
function mobile_auth_exchange_token(string $token): array
{
    ensure_mobile_auth_tokens_schema();

    $token = trim($token);
    if ($token === '' || strlen($token) < 32) {
        return ['success' => false, 'error' => 'Некорректный токен.'];
    }

    $hash = mobile_auth_hash_token($token);
    $stmt = db()->prepare(
        'SELECT t.id, t.user_id, t.expires_at, u.is_active, u.role, u.full_name, u.email
         FROM mobile_auth_tokens t
         INNER JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = ?
         LIMIT 1'
    );
    $stmt->execute([$hash]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['success' => false, 'error' => 'Сессия не найдена. Войдите снова.'];
    }

    if (!(int) $row['is_active']) {
        return ['success' => false, 'error' => 'Учётная запись заблокирована.'];
    }

    if (strtotime((string) $row['expires_at']) < time()) {
        $del = db()->prepare('DELETE FROM mobile_auth_tokens WHERE id = ?');
        $del->execute([(int) $row['id']]);

        return ['success' => false, 'error' => 'Срок входа истёк. Войдите снова.'];
    }

    $touch = db()->prepare('UPDATE mobile_auth_tokens SET last_used_at = NOW() WHERE id = ?');
    $touch->execute([(int) $row['id']]);

    login_user([
        'id' => (int) $row['user_id'],
        'role' => (string) $row['role'],
    ]);

    return [
        'success' => true,
        'session_name' => session_name(),
        'session_id' => session_id(),
        'user_id' => (int) $row['user_id'],
    ];
}

function mobile_auth_revoke_token(string $token): void
{
    ensure_mobile_auth_tokens_schema();
    $token = trim($token);
    if ($token === '') {
        return;
    }
    $hash = mobile_auth_hash_token($token);
    $stmt = db()->prepare('DELETE FROM mobile_auth_tokens WHERE token_hash = ?');
    $stmt->execute([$hash]);
}

function mobile_auth_revoke_user_tokens(int $userId): void
{
    ensure_mobile_auth_tokens_schema();
    if ($userId <= 0) {
        return;
    }
    $stmt = db()->prepare('DELETE FROM mobile_auth_tokens WHERE user_id = ?');
    $stmt->execute([$userId]);
}
