<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/curriculum.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/student_rating.php';

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён. Требуются права завуча или администратора.');
}

$source = (string) ($_GET['source'] ?? 'journal');
if ($source === 'manual_brs' && !is_manual_brs_gradebook_active()) {
    $source = 'journal';
}
$source = $source === 'manual_brs' ? 'manual_brs' : 'journal';

$ratingSource = $source;
$ratingSelfUrl = 'rating.php';
$ratingPanelLead = 'Рейтинг студентов по успеваемости';
$ratingSourceLabel = $source === 'manual_brs'
    ? 'Средний балл считается по итоговым оценкам из модуля «Ручное БРС».'
    : 'Средний балл считается по итоговым оценкам из электронного журнала.';
$ratingEmptyText = $source === 'manual_brs'
    ? 'Нет студентов с выставленными итоговыми оценками ручного БРС за выбранный период.'
    : 'Нет студентов с выставленными итоговыми оценками за выбранный период.';
$pageTitle = 'Рейтинг студентов — Панель завуча';
$currentDeputyTab = 'rating';

require __DIR__ . '/../includes/deputy_rating_page.php';
