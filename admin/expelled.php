<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$viewBase = 'expelled_view.php';
require __DIR__ . '/../includes/expelled/list_page.php';

$pageTitle = 'Отчисленные студенты — Администрирование';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'expelled';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель администратора</h1>
                <p class="text-muted">Отчисленные студенты</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/admin_nav.php'; ?>
    </section>

    <?php require __DIR__ . '/../includes/expelled/list.php'; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
