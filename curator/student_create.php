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

<div class="dashboard dashboard--wide curator-page">
    <?php
    $curatorPageTitle = 'Новый студент';
    $curatorPageSubtitle = 'Группа ' . (string) $group['number'];
    $curatorPageIcon = 'student';
    $curatorPageShowGroup = false;
    ob_start();
    ?>
    <a href="group.php?group_id=<?= $groupId ?>" class="btn btn--ghost">← К списку группы</a>
    <?php
    $curatorPageActionsHtml = ob_get_clean();
    require __DIR__ . '/../includes/curator_page_hero.php';
    ?>

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
