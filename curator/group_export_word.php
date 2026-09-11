<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/group_list_word.php';

require_curator_panel();

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$group = require_group_access($groupId);
$students = get_students_by_group($groupId);

if ($students === []) {
    flash_set('error', 'В группе нет студентов для экспорта.');
    header('Location: group.php?group_id=' . $groupId);
    exit;
}

try {
    download_group_list_docx($group, $students);
} catch (Throwable $e) {
    flash_set('error', 'Не удалось сформировать файл Word.');
    header('Location: group.php?group_id=' . $groupId);
    exit;
}
