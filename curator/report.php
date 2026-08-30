<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/group_report.php';

require_curator_panel();

$ctx = resolve_curator_group_context(isset($_GET['group_id']) ? (int) $_GET['group_id'] : null);
$groups = $ctx['groups'];
$groupId = $ctx['group_id'];
$group = $ctx['group'];
$students = $group ? get_students_by_group($groupId) : [];
$error = $ctx['error'];

$report = $group ? build_group_report($students, $groupId) : null;

$pageTitle = 'Справка по группе — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'report';
$curatorGroupId = $groupId;
$curatorGroups = $groups;
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель куратора</h1>
                <p class="text-muted">Аналитическая справка по группе</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/curator_nav.php'; ?>
    </section>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($groups === []): ?>
        <section class="panel">
            <p class="text-muted">
                Вам ещё не назначена группа. Обратитесь к администратору.
            </p>
        </section>
    <?php else: ?>
        <section class="panel">
            <?php if ($group === null): ?>
                <p class="text-muted">Выберите группу, чтобы открыть справку.</p>
            <?php else: ?>
                <?php
                $showGroupReportTitle = true;
                require __DIR__ . '/../includes/group_report_view.php';
                ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
