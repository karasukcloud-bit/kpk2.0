<?php

declare(strict_types=1);

/**
 * Подключение к БД-снимку для дозаписи.
 * Логин/пароль/host берутся из live config/database.php — не дублировать.
 * Меняется только имя базы снимка.
 */
$live = require __DIR__ . '/database.php';

$liveDb = (string) ($live['dbname'] ?? '');
// Beget: live = u3716463_spo_progress → снимок u3716463_spo_progress_backup_oct
// Локально: spo_progress → spo_progress_backup_oct2026
if (strpos($liveDb, 'u3716463_') === 0) {
    $backupDb = 'u3716463_spo_progress_backup_oct';
} else {
    $backupDb = 'spo_progress_backup_oct2026';
}

return [
    'host'     => (string) ($live['host'] ?? 'localhost'),
    'dbname'   => $backupDb,
    'charset'  => (string) ($live['charset'] ?? 'utf8mb4'),
    'username' => (string) ($live['username'] ?? 'root'),
    'password' => (string) ($live['password'] ?? ''),
];
