<?php

declare(strict_types=1);

/**
 * Настройки подключения к MySQL.
 * При переносе на сервер измените эти значения.
 */
return [
    // OSPanel на этой машине слушает MySQL на 127.0.1.30 (не 127.0.0.1).
    'host'     => '127.0.1.30',
    'dbname'   => 'spo_progress',
    'charset'  => 'utf8mb4',
    'username' => 'root',
    'password' => '',
];

