<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/glaz.php';
require_once __DIR__ . '/../includes/teachers.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/notifications.php';

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён. Требуются права завуча или администратора.');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } elseif (($_POST['action'] ?? '') === 'send_glaz') {
        $result = send_glaz_to_teachers();
        if (!empty($result['success'])) {
            flash_set('success', 'График отправлен преподавателям: ' . (int) $result['sent'] . ' оповещений.');
            header('Location: glaz.php');
            exit;
        }
        $error = $result['error'] ?? 'Не удалось отправить график.';
    }
}

$debts = get_all_academic_debts();
$scheduleIndex = get_glaz_schedules_index();
$tableGroups = build_glaz_table_groups($debts, $scheduleIndex['schedules']);
$teachers = get_all_teachers();
$organization = get_organization();
$glazEditable = true;
$glazHighlightGroupNumbers = [];
$glazHighlightTeacherId = null;
$glazShowLegend = false;
$success = flash_get('success');

$pageTitle = 'ГЛАЗ — Панель завуча';
$showHeader = true;
$basePath = '../';
$currentDeputyTab = 'glaz';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide glaz-page">
    <section class="panel glaz-no-print">
        <div class="panel__header">
            <div>
                <h1>Панель завуча</h1>
                <p class="text-muted">График ликвидации академической задолженности (ГЛАЗ)</p>
            </div>
            <div class="panel__actions">
                <?php if ($tableGroups !== []): ?>
                <form method="post" class="form-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="send_glaz">
                    <button
                        type="submit"
                        class="btn btn--primary btn--sm"
                        onclick="return confirm('Отправить график ГЛАЗ всем преподавателям?');"
                    >Отправить преподавателям</button>
                </form>
                <button type="button" class="btn btn--ghost" data-glaz-print>Печать</button>
                <?php endif; ?>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/deputy_nav.php'; ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success glaz-no-print"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error glaz-no-print"><?= e($error) ?></div>
    <?php endif; ?>

    <?php require __DIR__ . '/../includes/glaz/table.php'; ?>
</div>

<template id="glaz-commission-row-template">
    <?php
    $selectedId = 0;
    $withRemove = true;
    require __DIR__ . '/../includes/glaz_commission_row.php';
    ?>
</template>

<?php require __DIR__ . '/../includes/footer.php'; ?>
