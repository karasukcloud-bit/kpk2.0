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

if (!is_manual_brs_gradebook_active()) {
    header('Location: rating.php');
    exit;
}

$ratingSource = 'manual_brs';
$ratingSelfUrl = 'manual_brs_rating.php';
$ratingPanelLead = 'Рейтинг студентов (Ручное БРС)';
$ratingSourceLabel = 'Средний балл считается по итоговым оценкам из модуля «Ручное БРС».';
$ratingEmptyText = 'Нет студентов с выставленными итоговыми оценками ручного БРС за выбранный период.';
$pageTitle = 'Рейтинг студентов (Ручное БРС) — Панель завуча';
$currentDeputyTab = 'manual_brs_rating';

require __DIR__ . '/../includes/deputy_rating_page.php';
