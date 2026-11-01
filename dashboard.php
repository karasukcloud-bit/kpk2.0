<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

require_login();
redirect_to(resolve_post_login_path());
