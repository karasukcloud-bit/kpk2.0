<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/student_accounts.php';
require_once __DIR__ . '/../includes/record_book_pdf.php';

require_student();

$student = current_student();
if ($student === null) {
    http_response_code(403);
    exit('Карточка студента не найдена.');
}

stream_record_book_pdf((int) $student['id']);
