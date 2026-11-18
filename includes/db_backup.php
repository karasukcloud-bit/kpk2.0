<?php

declare(strict_types=1);

/**
 * PDO к БД-снимку spo_progress_backup_oct2026 (или null, если недоступна).
 */
function db_backup(): ?PDO
{
    static $pdo = false; // false = ещё не пробовали; null = ошибка; PDO = ок

    if ($pdo !== false) {
        return $pdo instanceof PDO ? $pdo : null;
    }

    $configPath = __DIR__ . '/../config/database_backup.php';
    if (!is_file($configPath)) {
        $pdo = null;

        return null;
    }

    /** @var array{host:string,dbname:string,charset:string,username:string,password:string} $config */
    $config = require $configPath;

    $hosts = array_values(array_unique(array_filter([
        (string) ($config['host'] ?? ''),
        '127.0.1.30',
        '127.0.0.1',
        'localhost',
    ])));

    foreach ($hosts as $host) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $host,
                $config['dbname'],
                $config['charset']
            );
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            break;
        } catch (Throwable $e) {
            $pdo = null;
        }
    }

    return $pdo instanceof PDO ? $pdo : null;
}

function db_backup_error(): string
{
    $configPath = __DIR__ . '/../config/database_backup.php';
    if (!is_file($configPath)) {
        return 'Нет файла config/database_backup.php';
    }

    /** @var array{host:string,dbname:string,charset:string,username:string,password:string} $config */
    $config = require $configPath;

    try {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset']
        );
        new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        return '';
    } catch (Throwable $e) {
        return $e->getMessage();
    }
}
