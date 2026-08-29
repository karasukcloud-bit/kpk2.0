<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
if ($itemId > 0) {
    header('Location: ktp_constructor.php?item_id=' . $itemId . '&mode=manual');
    exit;
}

header('Location: ktp_constructor.php');
exit;
