<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/notifications.php';
require_once __DIR__ . '/includes/glaz.php';
require_once __DIR__ . '/includes/teachers.php';

require_login();

$user = current_user();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'mark_read') {
            $result = mark_notification_read((int) ($_POST['notification_id'] ?? 0));
            if ($result['success']) {
                header('Location: notifications.php?id=' . (int) ($_POST['notification_id'] ?? 0));
                exit;
            }
            $error = $result['error'];
        } elseif ($action === 'mark_all_read') {
            mark_all_notifications_read();
            flash_set('success', 'Все уведомления отмечены прочитанными.');
            header('Location: notifications.php');
            exit;
        }
    }
}

$notifications = get_user_notifications((int) $user['id']);
$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$selected = null;
foreach ($notifications as $row) {
    if ((int) $row['id'] === $selectedId) {
        $selected = $row;
        break;
    }
}
if ($selected && empty($selected['is_read'])) {
    mark_notification_read((int) $selected['id'], (int) $user['id']);
    $selected['is_read'] = 1;
}

$success = flash_get('success');
$pageTitle = 'Уведомления';
$showHeader = true;
require __DIR__ . '/includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Уведомления</h1>
                <p class="text-muted">Личные сообщения и общие оповещения</p>
            </div>
            <?php if ($notifications !== [] && get_unread_notifications_count((int) $user['id']) > 0): ?>
            <form method="post" class="form-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn--ghost btn--sm">Отметить все прочитанными</button>
            </form>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <?php if ($notifications === []): ?>
            <p class="text-muted">Уведомлений пока нет.</p>
        <?php else: ?>
            <div class="notifications-layout">
                <div class="notifications-list">
                    <?php foreach ($notifications as $row): ?>
                    <?php $isUnread = empty($row['is_read']); ?>
                    <a
                        href="notifications.php?id=<?= (int) $row['id'] ?>"
                        class="notifications-item<?= $isUnread ? ' notifications-item--unread' : '' ?><?= (int) $row['id'] === $selectedId ? ' notifications-item--active' : '' ?>"
                    >
                        <span class="notifications-item__type">
                            <?= e(notification_type_label((string) $row['notification_type'])) ?>
                        </span>
                        <strong class="notifications-item__title"><?= e($row['title']) ?></strong>
                        <span class="notifications-item__meta">
                            <?= e(format_date($row['created_at'])) ?>
                            <?php if (!empty($row['sender_name'])): ?>
                                · <?= e($row['sender_name']) ?>
                            <?php endif; ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="notifications-detail">
                    <?php if ($selected === null): ?>
                        <p class="text-muted">Выберите уведомление из списка.</p>
                    <?php else: ?>
                        <div class="notifications-detail__head">
                            <span class="badge<?= $selected['notification_type'] === 'announcement' ? ' badge--deputy' : '' ?>">
                                <?= e(notification_type_label((string) $selected['notification_type'])) ?>
                            </span>
                            <h2><?= e($selected['title']) ?></h2>
                            <p class="text-muted">
                                <?= e(format_date($selected['created_at'])) ?>
                                <?php if (!empty($selected['sender_name'])): ?>
                                    · <?= e($selected['sender_name']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="notifications-detail__body"><?= nl2br(e($selected['body'])) ?></div>
                        <?php if (notification_is_glaz($selected)): ?>
                        <p class="notifications-detail__actions">
                            <a href="<?= e(app_base_path() . notification_glaz_view_path()) ?>" class="btn btn--primary">
                                Открыть график ГЛАЗ
                            </a>
                        </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
