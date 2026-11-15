<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/summary_table_export.php';
require_once __DIR__ . '/../includes/gradebook.php';

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

$orderRaw = (string) ($_POST['order'] ?? '');
$orderIds = array_values(array_filter(
    array_map('intval', preg_split('/[,\s]+/', $orderRaw) ?: []),
    static fn (int $id): bool => $id > 0
));

$sort = (string) ($_POST['sort'] ?? '');
$dir = ((string) ($_POST['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
if (!in_array($sort, ['absolute', 'quality', ''], true)) {
    $sort = '';
}

$rows = summary_export_order_rows($report['rows'], $orderIds);

if ($viewSem === 'year') {
    $caption = $year . ' · весь учебный год (1 и 2 семестр)';
} else {
    $caption = $year . ' · ' . semester_label($reportSemester);
}
$caption .= '. ' . ($source === 'manual_brs'
    ? 'Оценки из временного модуля «Ручное БРС»'
    : 'Оценки из электронного журнала');

stream_summary_table_export(
    $rows,
    $format,
    'Сводная ведомость',
    $caption,
    $sort,
    $dir
);
