<?php

declare(strict_types=1);

/**
 * Универсальный импорт архивных ведомостей из JSON.
 *
 * Примеры:
 *   php tools/import_archive_gradebook.php --file=tools/archive_import_data/2025-2026_sem2_301.json --dry-run
 *   php tools/import_archive_gradebook.php --dir=tools/archive_import_data --dry-run
 *   php tools/import_archive_gradebook.php --dir=tools/archive_import_data
 *
 * Флаги:
 *   --dry-run        только сверка ФИО, без записи
 *   --allow-create   создать карточку, если ФИО нет (для прошлых лет — выпускник removed_at)
 *
 * JSON-поле "alumni": true — принудительно считать состав выпускниками
 * (если год импорта = текущий, но группа уже с новым набором).
 *
 * Автоматически: academic_year < active_academic_year → прошлый период:
 * не линкует к текущему составу этой группы; новых создаёт с removed_at.
 */

require_once __DIR__ . '/archive_gradebook_import_lib.php';

$argvList = $argv ?? [];
$flags = archive_import_cli_flags($argvList);

try {
    $paths = archive_import_collect_json_paths($argvList);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

if ($paths === []) {
    fwrite(STDERR, "Укажите --file=path.json и/или --dir=folder\n");
    exit(1);
}

$pdo = archive_import_pdo();
$failed = 0;
$totalMissing = 0;

foreach ($paths as $path) {
    try {
        $items = archive_import_load_json_file($path);
    } catch (Throwable $e) {
        fwrite(STDERR, 'FAIL load ' . $path . ': ' . $e->getMessage() . "\n");
        $failed++;
        continue;
    }

    foreach ($items as $item) {
        $sheet = $item['sheet'];
        $label = $sheet['group'] . ' / ' . $sheet['academic_year'] . ' сем.' . $sheet['semester'];
        try {
            $result = archive_import_gradebook_sheet(
                $pdo,
                $sheet['group'],
                $sheet['academic_year'],
                $sheet['semester'],
                $sheet['subjects'],
                $sheet['rows'],
                $flags['dry_run'],
                $flags['allow_create'],
                !empty($sheet['alumni']),
                $sheet['specialty_name'] ?? null,
                $sheet['specialty_code'] ?? null,
                array_key_exists('is_general_education', $sheet)
                    ? $sheet['is_general_education']
                    : null
            );
            archive_import_print_result($result, $label);
            $totalMissing += (int) $result['missing_count'];
        } catch (Throwable $e) {
            fwrite(STDERR, "FAIL [{$label}]: " . $e->getMessage() . "\n");
            $failed++;
        }
    }
}

echo "Done. files=" . count($paths) . " failed={$failed} missing_total={$totalMissing}\n";
exit($failed > 0 || (!$flags['dry_run'] && $totalMissing > 0) ? 1 : 0);
