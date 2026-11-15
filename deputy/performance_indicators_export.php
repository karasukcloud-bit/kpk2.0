<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/performance_indicators_export.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/organization.php';

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Метод не поддерживается.');
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Ошибка безопасности.');
}

$format = (string) ($_POST['format'] ?? 'pdf');
if (!in_array($format, ['pdf', 'excel'], true)) {
    $format = 'pdf';
}

$source = ((string) ($_POST['source'] ?? 'journal')) === 'manual_brs' ? 'manual_brs' : 'journal';
if ($source === 'manual_brs' && !is_manual_brs_gradebook_active()) {
    http_response_code(400);
    exit('Модуль «Ручное БРС» недоступен.');
}

$activePeriod = get_active_gradebook_period();
$year = normalize_academic_year((string) ($_POST['year'] ?? '')) ?? $activePeriod['academic_year'];
$viewSem = (string) ($_POST['sem'] ?? '');
if (!in_array($viewSem, ['1', '2', 'year'], true)) {
    http_response_code(400);
    exit('Экспорт доступен для семестра или всего года.');
}

$reportSemester = $viewSem === 'year' ? 'year' : normalize_gradebook_semester($viewSem);
$groups = get_all_groups();
$report = $groups !== []
    ? build_college_gradebook_report($year, $reportSemester, $source, $groups)
    : null;

if ($report === null || ($report['rows'] ?? []) === []) {
    http_response_code(404);
    exit('Нет данных для экспорта.');
}

$parseOrder = static function (string $raw): array {
    return array_values(array_filter(
        array_map('intval', preg_split('/[,\s]+/', $raw) ?: []),
        static fn (int $id): bool => $id > 0
    ));
};

$qualityOrder = $parseOrder((string) ($_POST['quality_order'] ?? ''));
$absoluteOrder = $parseOrder((string) ($_POST['absolute_order'] ?? ''));

$qualitySort = (string) ($_POST['quality_sort'] ?? 'quality');
$qualityDir = ((string) ($_POST['quality_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
$absoluteSort = (string) ($_POST['absolute_sort'] ?? '');
$absoluteDir = ((string) ($_POST['absolute_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

if (!in_array($qualitySort, ['absolute', 'quality', 'group', ''], true)) {
    $qualitySort = 'quality';
}
if (!in_array($absoluteSort, ['absolute', 'quality', 'group', ''], true)) {
    $absoluteSort = '';
}

$qualityRows = summary_export_order_rows($report['rows'], $qualityOrder);
$absoluteRows = summary_export_order_rows($report['rows'], $absoluteOrder);

if ($qualityOrder === []) {
    usort(
        $qualityRows,
        static function (array $a, array $b): int {
            $qa = (float) ($a['overview']['summary']['quality_percent'] ?? 0);
            $qb = (float) ($b['overview']['summary']['quality_percent'] ?? 0);
            if ($qa !== $qb) {
                return $qb <=> $qa;
            }

            return strnatcasecmp(
                (string) ($a['group']['number'] ?? ''),
                (string) ($b['group']['number'] ?? '')
            );
        }
    );
    $qualitySort = 'quality';
    $qualityDir = 'desc';
}

if ($viewSem === 'year') {
    $caption = $year . ' · весь учебный год (1 и 2 семестр)';
} else {
    $caption = $year . ' · ' . semester_label($reportSemester);
}
$caption .= '. ' . ($source === 'manual_brs'
    ? 'Оценки из временного модуля «Ручное БРС»'
    : 'Оценки из электронного журнала');

stream_perf_indicators_export(
    $qualityRows,
    $absoluteRows,
    $format,
    $caption,
    $qualitySort,
    $qualityDir,
    $absoluteSort,
    $absoluteDir
);
