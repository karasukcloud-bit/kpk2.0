<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/testoria.php';
require_once __DIR__ . '/../includes/activity_log.php';

require_teacher_panel();

$user = current_user();
if ($user === null) {
    redirect_to('login.php');
}

activity_log_testoria_open($user);

$launchUrl = testoria_sso_launch_url($user);
header('Location: ' . $launchUrl, true, 302);
exit;
