<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/glaz.php';
require_once __DIR__ . '/../includes/organization.php';

require_login();
if (!can_use_teacher_panel() && !can_use_curator_panel()) {
    http_response_code(403);
    exit('Доступ запрещён.');
}

$debts = get_all_academic_debts();
$scheduleIndex = get_glaz_schedules_index();
$tableGroups = build_glaz_table_groups($debts, $scheduleIndex['schedules']);
$organization = get_organization();
$highlights = get_glaz_view_highlights();
$glazEditable = false;
$glazHighlightGroupNumbers = $highlights['group_numbers'];
$glazHighlightTeacherId = $highlights['teacher_id'];
$glazShowLegend = $glazHighlightGroupNumbers !== [] || $glazHighlightTeacherId !== null;
$teachers = [];

$pageTitle = 'ГЛАЗ — Просмотр';
$showHeader = true;
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide glaz-page">
    <section class="panel glaz-no-print">
        <div class="panel__header">
            <div>
                <h1>График ликвидации задолженности</h1>
                <p class="text-muted">Режим просмотра</p>
            </div>
            <div class="panel__actions">
                <a href="<?= e($basePath) ?>notifications.php" class="btn btn--ghost btn--sm">← Уведомления</a>
                <?php if ($tableGroups !== []): ?>
                <button type="button" class="btn btn--ghost" data-glaz-print>Печать</button>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/../includes/glaz/table.php'; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
