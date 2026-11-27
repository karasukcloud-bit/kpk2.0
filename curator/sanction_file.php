<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/student_sanctions.php';

require_curator_panel();
ensure_student_sanctions_schema();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = $id > 0 ? get_student_sanction($id) : null;
if ($row === null) {
    http_response_code(404);
    echo 'Файл не найден.';
    exit;
}

$student = get_student_by_id((int) $row['student_id']);
if ($student === null) {
    http_response_code(404);
    echo 'Студент не найден.';
    exit;
}

$groupId = (int) ($student['group_id'] ?? 0);
$ctx = resolve_curator_group_context($groupId > 0 ? $groupId : null);
if ($ctx['group'] === null || (int) $ctx['group_id'] !== $groupId) {
    http_response_code(403);
    echo 'Нет доступа.';
    exit;
}

serve_student_sanction_file($id);
