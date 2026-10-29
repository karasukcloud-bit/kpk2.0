<?php

declare(strict_types=1);

require_once __DIR__ . '/archive_gradebook_import_lib.php';

$pdo = archive_import_pdo();
$pdo->beginTransaction();
try {
    $report = archive_import_merge_all_fio_duplicates($pdo);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
}

if ($report === []) {
    echo "No FIO duplicates found.\n";
    exit(0);
}

echo "Merged " . count($report) . " name(s):\n";
foreach ($report as $row) {
    echo "  {$row['full_name']}: keep={$row['keep']} removed=" . implode(',', $row['removed']) . "\n";
}
