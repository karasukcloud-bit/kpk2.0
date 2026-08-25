<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/gradebook_pdf.php';

require_login();

if (!can_view_archive_gradebook()) {
    http_response_code(403);
    exit('Доступ запрещён.');
}

$archiveId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

if ($archiveId <= 0 || $groupId <= 0) {
    http_response_code(400);
    exit('Укажите архив и группу.');
}

$archive = get_archive_period_by_id($archiveId);
if ($archive === null || $archive['archive_type'] !== 'gradebook') {
    http_response_code(404);
    exit('Архив ведомостей не найден.');
}

stream_archive_gradebook_pdf($archiveId, $groupId);
