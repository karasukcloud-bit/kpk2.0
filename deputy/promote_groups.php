<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_login();

if (is_admin()) {
    header('Location: ../admin/promote_groups.php');
    exit;
}

http_response_code(403);
exit('Доступ запрещён. Перевод курсов доступен только администратору.');
