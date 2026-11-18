<?php

declare(strict_types=1);

/**
 * Временное подключение к БД-снимку (октябрь 2026) для дозаписи данных.
 * Live-конфиг — config/database.php (не менять).
 *
 * На этой машине OSPanel MySQL слушает 127.0.1.30 (не 127.0.0.1).
 */
$live = require __DIR__ . '/database.php';

return [
    'host'     => '127.0.1.30',
    'dbname'   => 'spo_progress_backup_oct2026',
    'charset'  => (string) ($live['charset'] ?? 'utf8mb4'),
    'username' => (string) ($live['username'] ?? 'root'),
    'password' => (string) ($live['password'] ?? ''),
];
