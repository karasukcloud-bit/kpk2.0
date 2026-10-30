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

$source = (string) ($_GET['source'] ?? 'journal');
if ($source === 'manual_brs' && !is_manual_brs_gradebook_active()) {
    $source = 'journal';
}
$source = $source === 'manual_brs' ? 'manual_brs' : 'journal';

if ($source === 'manual_brs') {
    $summarySource = 'manual_brs';
    $summaryGradebookHref = 'manual_brs_gradebook.php';
    $summaryPanelLead = 'Сводная ведомость по всем группам';
    $summaryHeading = 'Сводная ведомость';
    $summarySourceLabel = 'Оценки из временного модуля «Ручное БРС».';
} else {
    $summarySource = 'journal';
    $summaryGradebookHref = 'gradebook.php';
    $summaryPanelLead = 'Сводная ведомость по всем группам';
    $summaryHeading = 'Сводная ведомость';
    $summarySourceLabel = 'Оценки из электронного журнала.';
}

$summarySelfUrl = 'summary.php';
$pageTitle = 'Сводная ведомость — Панель завуча';
$currentDeputyTab = 'summary';

require __DIR__ . '/../includes/deputy_summary_page.php';
