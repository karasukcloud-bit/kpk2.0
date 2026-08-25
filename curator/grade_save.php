<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_curator_panel();

header('Content-Type: application/json; charset=UTF-8');
http_response_code(403);
echo json_encode([
    'success' => false,
    'error' => 'Ведомость заполняется автоматически из журнала и не редактируется.',
]);
