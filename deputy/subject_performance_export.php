<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/subject_performance_export.php';
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
    exit('Некорректный период.');
}

$specialtyKey = (string) ($_POST['specialty'] ?? '');
$report = build_specialty_subjects_performance_report($year, $viewSem, $source);
$specialties = $report['specialties'];
if ($specialtyKey === '' || !isset($specialties[$specialtyKey])) {
    http_response_code(404);
    exit('Специальность не найдена.');
}

$subjects = $specialties[$specialtyKey]['subjects'] ?? [];
if ($subjects === []) {
    http_response_code(404);
    exit('Нет данных для экспорта.');
}

$orderRaw = (string) ($_POST['order'] ?? '');
$orderKeys = array_values(array_filter(
    array_map('trim', preg_split('/\|/', $orderRaw) ?: []),
    static fn (string $key): bool => $key !== ''
));
$subjects = subject_perf_export_order_rows($subjects, $orderKeys);

$sort = (string) ($_POST['sort'] ?? '');
$dir = ((string) ($_POST['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
if (!in_array($sort, ['absolute', 'quality', ''], true)) {
    $sort = '';
}

$periodCaption = $viewSem === 'year'
    ? 'весь учебный год'
    : semester_label($viewSem);
$sourceLabel = $source === 'manual_brs'
    ? 'Оценки из модуля «Ручное БРС»'
    : 'Оценки из электронного журнала';
$caption = $year . ' · ' . $periodCaption . ' · ' . (string) $specialties[$specialtyKey]['label']
    . '. ' . $sourceLabel;

stream_subject_perf_export(
    $subjects,
    $format,
    'Таблица по предметам',
    $caption,
    $sort,
    $dir
);
