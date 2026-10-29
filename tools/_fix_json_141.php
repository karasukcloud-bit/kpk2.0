<?php

declare(strict_types=1);

$src = 'd:/ЗАГРУЗКИ/2025 - 2026_semI_141.json';
$dst = __DIR__ . '/archive_import_data/2025-2026_sem1_141.json';

$data = json_decode((string) file_get_contents($src), true);
if (!is_array($data)) {
    fwrite(STDERR, "Bad JSON\n");
    exit(1);
}

$data['academic_year'] = '2025-2026';
$data['semester'] = '1';
$data['group'] = trim((string) ($data['group'] ?? ''));

foreach ($data['students'] as &$student) {
    $student['name'] = preg_replace('/\s+/u', ' ', trim((string) ($student['name'] ?? ''))) ?? '';
}
unset($student);

$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
file_put_contents($src, $json);
file_put_contents($dst, $json);

echo "ok year={$data['academic_year']} sem={$data['semester']} n=" . count($data['students']) . "\n";
echo "updated: {$src}\n";
echo "copied:  {$dst}\n";
