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

$summarySource = 'journal';
$summaryGradebookHref = 'gradebook.php';
$summarySelfUrl = 'summary.php';
$summaryPanelLead = 'Сводная ведомость по всем группам';
$summaryHeading = 'Сводная ведомость';
$summarySourceLabel = 'Оценки из электронного журнала.';
$pageTitle = 'Сводная ведомость — Панель завуча';
$currentDeputyTab = 'summary';

require __DIR__ . '/../includes/deputy_summary_page.php';
