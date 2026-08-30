<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/profile.php';
require_once __DIR__ . '/../includes/ktp.php';
require_once __DIR__ . '/../includes/ktp/document_header.php';
require_once __DIR__ . '/../includes/curriculum.php';
require_once __DIR__ . '/../includes/gradebook.php';

require_teacher_panel();

$itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
if ($itemId <= 0) {
    header('Location: subjects.php');
    exit;
}

if (!can_manage_item_ktp($itemId)) {
    flash_set('error', 'Нет доступа к КТП этого предмета.');
    header('Location: subjects.php');
    exit;
}

$item = get_curriculum_item_by_id($itemId);
if ($item === null) {
    flash_set('error', 'Предмет не найден.');
    header('Location: subjects.php');
    exit;
}

$topics = get_ktp_topics($itemId);
$ktpSummary = $topics !== [] ? build_ktp_plan_summary($topics) : null;
$ktpHeader = build_ktp_document_header_context($item);
$ktpColumnWidths = get_ktp_column_widths($itemId);
$error = flash_get('error');

$pageTitle = 'КТП — ' . $item['subject_name'];
$showHeader = true;
$basePath = '../';
$currentTeacherTab = 'subjects';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide ktp-view-page">
    <section class="panel ktp-no-print">
        <div class="panel__header">
            <div>
                <h1>Панель преподавателя</h1>
                <p class="text-muted">Календарно-тематическое планирование</p>
            </div>
            <a href="subjects.php" class="btn btn--ghost btn--sm">← К предметам</a>
        </div>
        <?php require __DIR__ . '/../includes/teacher_nav.php'; ?>
    </section>

    <section class="panel">
        <div class="panel__header panel__header--compact ktp-no-print">
            <div>
                <h2><?= e($item['subject_name']) ?></h2>
                <p class="text-muted">
                    Группа <?= e($item['group_number']) ?>
                    · <?= e(semester_label($item['semester'])) ?>
                    · <?= e($item['academic_year']) ?>
                </p>
            </div>
            <div class="panel__actions">
                <?php if ($topics !== []): ?>
                <a href="ktp_pdf.php?item_id=<?= $itemId ?>" class="btn btn--secondary btn--sm">Скачать PDF</a>
                <button type="button" class="btn btn--ghost btn--sm" data-ktp-print>Печать</button>
                <?php endif; ?>
                <a href="ktp_constructor.php?item_id=<?= $itemId ?>&mode=rows" class="btn btn--primary btn--sm">Редактировать</a>
            </div>
        </div>

        <div class="ktp-view-print-area">
            <?php render_ktp_document_header($ktpHeader); ?>

            <?php if ($error): ?>
                <div class="alert alert--error ktp-no-print"><?= e($error) ?></div>
            <?php endif; ?>

            <?php require __DIR__ . '/../includes/ktp/view_table.php'; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
