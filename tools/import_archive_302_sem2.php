<?php

declare(strict_types=1);

$_SERVER['argv'] = array_merge(
    [$argv[0] ?? 'import'],
    ['--file=' . __DIR__ . '/archive_import_data/2025-2026_sem2_302.json'],
    array_slice($argv ?? [], 1)
);
$argv = $_SERVER['argv'];
require __DIR__ . '/import_archive_gradebook.php';
