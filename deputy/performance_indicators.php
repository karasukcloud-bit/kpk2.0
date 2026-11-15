<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён. Требуются права завуча или администратора.');
}

$source = resolve_grades_source(isset($_GET['source']) ? (string) $_GET['source'] : null);

if ($source === 'manual_brs') {
    $perfSource = 'manual_brs';
    $perfGradebookHref = 'manual_brs_gradebook.php';
    $perfSourceLabel = 'Оценки из временного модуля «Ручное БРС».';
} else {
    $perfSource = 'journal';
    $perfGradebookHref = 'gradebook.php';
    $perfSourceLabel = 'Оценки из электронного журнала.';
}

$perfSelfUrl = 'performance_indicators.php';
$pageTitle = 'Показатели успеваемости — Панель завуча';
$currentDeputyTab = 'performance_indicators';

require __DIR__ . '/../includes/deputy_performance_indicators_page.php';
