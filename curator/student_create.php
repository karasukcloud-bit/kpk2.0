<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';

require_curator_panel();

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$group = require_group_access($groupId);
$error = null;
$data = student_payload_from_post($_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $result = create_student($groupId, $data);

        if ($result['success']) {
            if (curator_show_student_auth_data() && !empty($result['login']) && !empty($result['password'])) {
                $loginShow = (string) $result['login'];
                if (substr($loginShow, -13) === '@student.local') {
                    $loginShow = substr($loginShow, 0, -13);
                }
                flash_set(
                    'success',
                    'Студент добавлен. Логин: ' . $loginShow . ' · Пароль: ' . $result['password']
                );
            } else {
                flash_set('success', 'Студент добавлен в группу.');
            }
            header('Location: group.php?group_id=' . $groupId);
            exit;
        }

        $error = $result['error'];
    }
}

$pageTitle = 'Добавить студента';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'group';
$curatorGroupId = $groupId;
$curatorGroups = get_groups_for_curator();
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Новый студент</h1>
                <p class="text-muted">Группа <?= e($group['number']) ?></p>
            </div>
            <a href="group.php?group_id=<?= $groupId ?>" class="btn btn--ghost">← К списку группы</a>
        </div>

        <?php require __DIR__ . '/../includes/curator_nav.php'; ?>
    </section>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <form method="post" class="form form--medium">
            <?= csrf_field() ?>
            <?php require __DIR__ . '/../includes/student_fields.php'; ?>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сохранить</button>
                <a href="group.php?group_id=<?= $groupId ?>" class="btn btn--ghost">Отмена</a>
            </div>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
