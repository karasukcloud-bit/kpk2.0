<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';

require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$group = get_group_by_id($id);
$specialties = get_all_specialties();

if ($group === null) {
    flash_set('error', 'Группа не найдена.');
    header('Location: info.php#groups');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $result = delete_group($id);

        if ($result['success']) {
            flash_set('success', 'Группа удалена.');
            header('Location: info.php#groups');
            exit;
        }

        $error = $result['error'];
    } else {
        $result = update_group(
            $id,
            $_POST['number'] ?? '',
            (int) ($_POST['specialty_id'] ?? 0),
            (int) ($_POST['curator_id'] ?? 0) ?: null
        );

        if ($result['success']) {
            flash_set('success', 'Группа обновлена.');
            header('Location: info.php#groups');
            exit;
        }

        $error = $result['error'];
    }
}

$selectedSpecialtyId = (int) (
    $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])
        ? ($_POST['specialty_id'] ?? $group['specialty_id'])
        : $group['specialty_id']
);

$pageTitle = 'Редактирование группы';
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
                <h1>Редактирование группы</h1>
                <p class="text-muted">Группа <?= e($group['number']) ?></p>
            </div>
            <a href="info.php#groups" class="btn btn--ghost">← К информации</a>
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
                <label for="number">Номер группы</label>
                <input type="text" id="number" name="number" required
                       value="<?= e($_POST['number'] ?? $group['number']) ?>">
            </div>

            <div class="form__group">
                <label for="specialty_id">Специальность</label>
                <select id="specialty_id" name="specialty_id" required>
                    <?= render_specialty_options($specialties, $selectedSpecialtyId) ?>
                </select>
            </div>

            <div class="form__group">
                <label for="curator_id">Куратор группы</label>
                <select id="curator_id" name="curator_id">
                    <?= render_curator_options(
                        (int) ($_POST['curator_id'] ?? ($group['curator_id'] ?? 0)) ?: null
                    ) ?>
                </select>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сохранить</button>
                <a href="info.php#groups" class="btn btn--ghost">Отмена</a>
            </div>
        </form>

        <hr class="divider">

        <form method="post" class="form form--narrow"
              onsubmit="return confirm('Удалить группу из системы?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn--danger">Удалить группу</button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
