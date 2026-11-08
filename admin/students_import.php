<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';

require_admin();

$importGroupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : (int) ($_POST['group_id'] ?? 0);
$importGroup = get_group_by_id($importGroupId);
if ($importGroup === null) {
    flash_set('error', 'Выберите группу для импорта студентов.');
    header('Location: students.php');
    exit;
}

$importBackUrl = 'students.php?group_id=' . $importGroupId;
$importPanel = 'admin';
$basePath = '../';
$currentAdminTab = 'students';

require __DIR__ . '/../includes/student_import_page.php';
