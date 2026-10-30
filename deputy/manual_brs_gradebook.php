<?php

declare(strict_types=1);

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
if ($groupId <= 0) {
    header('Location: grades.php?source=manual_brs');
    exit;
}

$manualBrsPanel = 'deputy';
require __DIR__ . '/../modules/manual_brs/pages/gradebook_view.php';
