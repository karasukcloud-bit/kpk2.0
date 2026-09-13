<?php

declare(strict_types=1);

/**
 * Экспорт ведомости ручного БРС в PDF.
 * Ожидает: $manualBrsPanel = curator|deputy|admin
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/organization.php';
require_once __DIR__ . '/../../../includes/students.php';
require_once __DIR__ . '/../../../includes/gradebook.php';
require_once __DIR__ . '/../../../includes/curriculum.php';
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/gradebook_pdf.php';

manual_brs_require_gradebook_viewer();
manual_brs_ensure_schema();

$manualBrsPanel = $manualBrsPanel ?? 'deputy';
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$period = get_active_gradebook_period();
$year = $period['academic_year'];
$semester = $period['semester'];

if ($groupId <= 0) {
    http_response_code(400);
    exit('Не указана группа.');
}

if ($manualBrsPanel === 'curator') {
    require_curator_panel();
    $ctx = resolve_curator_group_context($groupId);
    if ($ctx['group'] === null || (int) $ctx['group_id'] !== $groupId) {
        http_response_code(403);
        exit('Группа недоступна.');
    }
} elseif ($manualBrsPanel === 'admin') {
    require_admin();
} elseif (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён.');
}

stream_manual_brs_gradebook_pdf($groupId, $year, $semester);
