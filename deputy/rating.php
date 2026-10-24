<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/curriculum.php';
require_once __DIR__ . '/../includes/student_rating.php';

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён. Требуются права завуча или администратора.');
}

$ratingSource = 'journal';
$ratingSelfUrl = 'rating.php';
$ratingPanelLead = 'Рейтинг студентов по успеваемости';
$ratingSourceLabel = 'Средний балл считается по итоговым оценкам из электронного журнала.';
$pageTitle = 'Рейтинг студентов — Панель завуча';
$currentDeputyTab = 'rating';

require __DIR__ . '/../includes/deputy_rating_page.php';
