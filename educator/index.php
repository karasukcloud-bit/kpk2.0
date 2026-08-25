<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_educator_panel();
header('Location: attendance.php');
exit;
