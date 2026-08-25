<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/expelled.php';

require_expelled_manager();

$showRestored = !isset($_GET['active_only']);
$list = list_expelled_students($showRestored);
$success = flash_get('success');
$error = flash_get('error');

$pageTitle = 'Отчисленные студенты — Панель завуча';
$showHeader = true;
$basePath = '../';
$currentDeputyTab = 'expelled';
$viewBase = 'expelled_view.php';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель завуча</h1>
                <p class="text-muted">Отчисленные студенты</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/deputy_nav.php'; ?>
    </section>

    <?php require __DIR__ . '/../includes/expelled/list.php'; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
