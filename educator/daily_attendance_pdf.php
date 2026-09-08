<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/educator/daily_attendance_pdf.php';

require_educator_panel();

$date = resolve_educator_daily_attendance_date($_GET['date'] ?? null);
stream_educator_daily_attendance_pdf($date);
