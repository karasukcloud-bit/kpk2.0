<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/journal_pdf.php';

require_archive_manager();

$archiveId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

if ($archiveId <= 0 || $groupId <= 0) {
    http_response_code(400);
    exit('Укажите архив и группу.');
}

$archive = get_archive_period_by_id($archiveId);
if ($archive === null || $archive['archive_type'] !== 'journal') {
    http_response_code(404);
    exit('Архив журналов не найден.');
}

stream_archive_journal_pdf($archiveId, $groupId);
