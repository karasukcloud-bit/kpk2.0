<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/group_report.php';

require_curator_panel();

$groups = get_groups_for_curator();
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

if ($groupId === 0 && count($groups) === 1) {
    $groupId = (int) $groups[0]['id'];
}

$group = null;
$students = [];
$error = null;

if ($groupId > 0) {
    if (!can_manage_group($groupId)) {
        $error = 'Нет доступа к выбранной группе.';
        $groupId = 0;
    } else {
        $group = get_group_by_id($groupId);
        $students = get_students_by_group($groupId);
    }
}

$report = $group ? build_group_report($students, $groupId) : null;

$pageTitle = 'Справка по группе — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'report';
$curatorGroupId = $groupId;
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
            <?php if (count($groups) > 1): ?>
            <form method="get" class="form form--filter">
                <div class="form__row form__row--filter">
                    <div class="form__group">
                        <label for="group_id">Группа</label>
                        <select id="group_id" name="group_id" onchange="this.form.submit()">
                            <option value="">— Выберите группу —</option>
                            <?php foreach ($groups as $item): ?>
                            <option value="<?= (int) $item['id'] ?>"<?= (int) $item['id'] === $groupId ? ' selected' : '' ?>>
                                <?= e($item['number']) ?> · <?= e($item['specialty_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
            <?php endif; ?>

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
