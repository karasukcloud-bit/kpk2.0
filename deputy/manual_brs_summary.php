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

if (!is_manual_brs_gradebook_active()) {
    header('Location: summary.php');
    exit;
}

$summarySource = 'manual_brs';
$summaryGradebookHref = 'manual_brs_gradebook.php';
$summaryPanelLead = 'Сводные ведомости Ручное БРС';
$summaryHeading = 'Сводные ведомости Ручное БРС';
$summarySourceLabel = 'Оценки из временного модуля «Ручное БРС».';
$pageTitle = 'Сводные ведомости Ручное БРС — Панель завуча';
$currentDeputyTab = 'manual_brs_summary';

require __DIR__ . '/../includes/deputy_summary_page.php';
