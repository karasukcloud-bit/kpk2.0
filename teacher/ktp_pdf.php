<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/profile.php';
require_once __DIR__ . '/../includes/ktp/pdf.php';

require_teacher_panel();

$itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
if ($itemId <= 0 || !can_manage_item_ktp($itemId)) {
    http_response_code(403);
    exit('Нет доступа к КТП этого предмета.');
}

stream_ktp_pdf($itemId);
