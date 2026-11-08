<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';

require_curator_panel();

$importGroupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : (int) ($_POST['group_id'] ?? 0);
$importGroup = require_group_access($importGroupId);
$importBackUrl = 'group.php?group_id=' . $importGroupId;
$importPanel = 'curator';
$basePath = '../';
$currentCuratorTab = 'group';
$curatorGroupId = $importGroupId;
$curatorGroups = get_groups_for_curator();

require __DIR__ . '/../includes/student_import_page.php';
