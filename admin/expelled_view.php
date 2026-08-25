<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/expelled.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/curriculum.php';

require_expelled_manager();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$expelled = get_expelled_student($id);
if ($expelled === null) {
    flash_set('error', 'Запись не найдена.');
    header('Location: expelled.php');
    exit;
}

$error = null;
$success = flash_get('success');
$tab = (string) ($_GET['tab'] ?? 'info');
if (!in_array($tab, ['info', 'record_book', 'debts'], true)) {
    $tab = 'info';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } elseif (($_POST['action'] ?? '') === 'restore') {
        $result = restore_expelled_student(
            $id,
            (string) ($_POST['restore_date'] ?? ''),
            (int) ($_POST['group_id'] ?? 0),
            (string) ($_POST['additional_info'] ?? '')
        );
        if ($result['success']) {
            flash_set('success', 'Студент восстановлен в группу.');
            header('Location: expelled_view.php?id=' . $id . '&tab=info');
            exit;
        }
        $error = $result['error'];
        $tab = 'info';
    }
}

$periods = get_expelled_record_book($id);
$courseworks = get_expelled_courseworks($id);
$practices = get_expelled_practices($id);
$giaEntries = get_expelled_gia($id);
$debts = get_expelled_debts($id);
$restoration = get_expelled_restoration($id);
$groups = get_all_groups();
$listUrl = 'expelled.php';
$viewUrl = 'expelled_view.php?id=' . $id;

$pageTitle = 'Отчисленный — ' . $expelled['full_name'];
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'expelled';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1><?= e($expelled['full_name']) ?></h1>
                <p class="text-muted">Карточка отчисленного студента</p>
            </div>
            <a href="<?= e($listUrl) ?>" class="btn btn--ghost">← К списку</a>
        </div>
        <?php require __DIR__ . '/../includes/admin_nav.php'; ?>
    </section>

    <?php require __DIR__ . '/../includes/expelled/view.php'; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
