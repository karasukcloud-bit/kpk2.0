<?php

declare(strict_types=1);

/**
 * Интеграция с Тесторией (соседнее приложение).
 * SSO: одноразовый HMAC-билет → Testoria /sso/spo
 */

function testoria_default_base_url(): string
{
    return 'http://127.0.0.1:8090';
}

function testoria_base_url(): string
{
    require_once __DIR__ . '/gradebook.php';
    $url = trim((string) (get_app_setting('testoria_base_url', testoria_default_base_url()) ?? ''));
    if ($url === '') {
        $url = testoria_default_base_url();
    }

    return rtrim($url, '/');
}

function testoria_shared_secret_path(): string
{
    // domains/localhost/.spo_testoria_sso_secret — общий файл для kpk2.0 и testoria/tester
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.spo_testoria_sso_secret';
}

function testoria_sso_secret(): string
{
    require_once __DIR__ . '/gradebook.php';

    $secret = trim((string) (get_app_setting('testoria_sso_secret', '') ?? ''));
    if ($secret !== '') {
        return $secret;
    }

    $file = testoria_shared_secret_path();
    if (is_file($file)) {
        $fromFile = trim((string) file_get_contents($file));
        if ($fromFile !== '') {
            set_app_setting('testoria_sso_secret', $fromFile);

            return $fromFile;
        }
    }

    $secret = bin2hex(random_bytes(32));
    set_app_setting('testoria_sso_secret', $secret);
    $dir = dirname($file);
    if (is_dir($dir) && is_writable($dir)) {
        file_put_contents($file, $secret . PHP_EOL);
    }

    return $secret;
}

/**
 * @param array<string, mixed> $user
 */
function testoria_issue_sso_ticket(array $user): string
{
    $payload = [
        'uid' => (int) ($user['id'] ?? 0),
        'name' => (string) ($user['full_name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'phone' => (string) ($user['phone'] ?? ''),
        'role' => 'teacher',
        'exp' => time() + 120,
        'nonce' => bin2hex(random_bytes(8)),
    ];

    $body = rtrim(strtr(base64_encode(
        (string) json_encode($payload, JSON_UNESCAPED_UNICODE)
    ), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $body, testoria_sso_secret());

    return $body . '.' . $sig;
}

function testoria_sso_launch_url(array $user): string
{
    $ticket = testoria_issue_sso_ticket($user);

    return testoria_base_url() . '/sso/spo?ticket=' . rawurlencode($ticket);
}
