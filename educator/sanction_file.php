<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/student_sanctions.php';

require_educator_panel();
serve_student_sanction_file(isset($_GET['id']) ? (int) $_GET['id'] : 0);
