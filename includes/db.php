<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Возвращает PDO-подключение к базе данных (singleton).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $config = require __DIR__ . '/../config/database.php';
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset']
        );

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        require_once __DIR__ . '/migrations.php';
        run_migrations($pdo);
    }

    return $pdo;
}
