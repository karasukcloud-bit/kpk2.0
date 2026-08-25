<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/record_book_pdf.php';

require_login();

if (!can_use_deputy_panel() && !is_admin()) {
    http_response_code(403);
    exit('Доступ запрещён.');
}

$studentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($studentId <= 0) {
    http_response_code(400);
    exit('Укажите студента.');
}

stream_record_book_pdf($studentId);
