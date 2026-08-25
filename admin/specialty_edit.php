<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';

require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$specialty = get_specialty_by_id($id);

if ($specialty === null) {
    flash_set('error', 'Специальность не найдена.');
    header('Location: info.php#specialties');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $result = delete_specialty($id);

        if ($result['success']) {
            flash_set('success', 'Специальность удалена.');
            header('Location: info.php#specialties');
            exit;
        }

        $error = $result['error'];
    } else {
        $result = update_specialty($id, $_POST['name'] ?? '', $_POST['code'] ?? '');

        if ($result['success']) {
            flash_set('success', 'Специальность обновлена.');
            header('Location: info.php#specialties');
            exit;
        }

        $error = $result['error'];
    }
}

$pageTitle = 'Редактирование специальности';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'info';
require __DIR__ . '/../includes/header.php';

$success = flash_get('success');
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Редактирование специальности</h1>
                <p class="text-muted"><?= e($specialty['name']) ?></p>
            </div>
            <a href="info.php#specialties" class="btn btn--ghost">← К информации</a>
        </div>

        <?php require __DIR__ . '/../includes/admin_nav.php'; ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <form method="post" class="form form--narrow">
            <?= csrf_field() ?>

            <div class="form__group">
                <label for="name">Название</label>
                <input type="text" id="name" name="name" required
                       value="<?= e($_POST['name'] ?? $specialty['name']) ?>">
            </div>

            <div class="form__group">
                <label for="code">Код</label>
                <input type="text" id="code" name="code" required
                       value="<?= e($_POST['code'] ?? $specialty['code']) ?>">
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сохранить</button>
                <a href="info.php#specialties" class="btn btn--ghost">Отмена</a>
            </div>
        </form>

        <hr class="divider">

        <form method="post" class="form form--narrow"
              onsubmit="return confirm('Удалить специальность? Это невозможно, если к ней привязаны группы.');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn--danger">Удалить специальность</button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
