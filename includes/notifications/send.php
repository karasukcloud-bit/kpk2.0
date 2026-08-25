<?php

declare(strict_types=1);

$notifyPanel = $notifyPanel ?? 'admin';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../notifications.php';
require_once __DIR__ . '/../teachers.php';

require_notification_sender();

$error = null;
$allowAnnouncements = can_send_announcements();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'send_personal') {
            $result = send_personal_notification(
                (int) ($_POST['recipient_id'] ?? 0),
                (string) ($_POST['title'] ?? ''),
                (string) ($_POST['body'] ?? '')
            );
            if ($result['success']) {
                flash_set('success', 'Личное сообщение отправлено.');
                header('Location: notifications.php');
                exit;
            }
            $error = $result['error'];
        } elseif ($action === 'send_announcement' && $allowAnnouncements) {
            $result = send_announcement(
                (string) ($_POST['title'] ?? ''),
                (string) ($_POST['body'] ?? '')
            );
            if ($result['success']) {
                flash_set('success', 'Общее оповещение отправлено.');
                header('Location: notifications.php');
                exit;
            }
            $error = $result['error'];
        } else {
            $error = 'Неизвестное действие.';
        }
    }
}

$recipients = get_notification_recipient_options();
$recipientGroups = get_notification_recipient_groups();
$success = flash_get('success');
$pageTitle = 'Уведомления — ' . ($notifyPanel === 'admin' ? 'Администрирование' : 'Панель завуча');
$showHeader = true;
$basePath = '../';

if ($notifyPanel === 'admin') {
    $currentAdminTab = 'notifications';
} else {
    $currentDeputyTab = 'notifications';
}

require __DIR__ . '/../header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1><?= $notifyPanel === 'admin' ? 'Панель администратора' : 'Панель завуча' ?></h1>
                <p class="text-muted">Отправка уведомлений пользователям</p>
            </div>
            <a href="<?= e($basePath) ?>notifications.php" class="btn btn--ghost">Мои уведомления</a>
        </div>
        <?php
        if ($notifyPanel === 'admin') {
            require __DIR__ . '/../admin_nav.php';
        } else {
            require __DIR__ . '/../deputy_nav.php';
        }
        ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <h2>Личное сообщение</h2>
        <form method="post" class="form form--medium">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="send_personal">
            <div class="form__group">
                <label for="recipient_id">Получатель</label>
                <select id="recipient_id" name="recipient_id" required>
                    <option value="">— Выберите получателя —</option>
                    <?php if ($notifyPanel === 'deputy'): ?>
                    <optgroup label="Преподаватели">
                        <?php foreach ($recipientGroups['teachers'] as $recipient): ?>
                        <option value="<?= (int) $recipient['id'] ?>">
                            <?= e(notification_recipient_label($recipient)) ?>
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Студенты">
                        <?php foreach ($recipientGroups['students'] as $recipient): ?>
                        <option value="<?= (int) $recipient['id'] ?>">
                            <?= e(notification_recipient_label($recipient)) ?>
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php else: ?>
                    <?php foreach ($recipients as $recipient): ?>
                    <option value="<?= (int) $recipient['id'] ?>">
                        <?= e(notification_recipient_label($recipient)) ?>
                    </option>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form__group">
                <label for="personal_title">Тема</label>
                <input type="text" id="personal_title" name="title" required maxlength="255">
            </div>
            <div class="form__group">
                <label for="personal_body">Сообщение</label>
                <textarea id="personal_body" name="body" rows="5" required></textarea>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Отправить</button>
            </div>
        </form>
    </section>

    <?php if ($allowAnnouncements): ?>
    <section class="panel">
        <h2>Общее оповещение</h2>
        <p class="text-muted">Сообщение увидят все пользователи системы (преподаватели, кураторы, студенты и т.д.).</p>
        <form method="post" class="form form--medium">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="send_announcement">
            <div class="form__group">
                <label for="announce_title">Тема</label>
                <input type="text" id="announce_title" name="title" required maxlength="255">
            </div>
            <div class="form__group">
                <label for="announce_body">Текст оповещения</label>
                <textarea id="announce_body" name="body" rows="5" required></textarea>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Опубликовать</button>
            </div>
        </form>
    </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
