<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mobile_notification_schedules.php';

require_admin();
ensure_mobile_notification_schedules_schema();

$error = null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editRow = $editId > 0 ? get_mobile_notification_schedule($editId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $result = save_mobile_notification_schedule($_POST, $id > 0 ? $id : null);
            if ($result['success']) {
                flash_set('success', $id > 0 ? 'Расписание обновлено.' : 'Расписание создано.');
                header('Location: mobile_notifications.php');
                exit;
            }
            $error = $result['error'] ?? 'Не удалось сохранить.';
            $editRow = [
                'id' => $id,
                'title' => (string) ($_POST['title'] ?? ''),
                'body' => (string) ($_POST['body'] ?? ''),
                'notify_time' => (string) ($_POST['notify_time'] ?? ''),
                'frequency' => (string) ($_POST['frequency'] ?? 'daily'),
                'weekday' => $_POST['weekday'] ?? null,
                'month_day' => $_POST['month_day'] ?? null,
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
            ];
            $editId = $id;
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            delete_mobile_notification_schedule($id);
            flash_set('success', 'Расписание удалено.');
            header('Location: mobile_notifications.php');
            exit;
        } elseif ($action === 'toggle') {
            $id = (int) ($_POST['id'] ?? 0);
            $row = get_mobile_notification_schedule($id);
            if ($row) {
                $row['is_active'] = empty($row['is_active']) ? 1 : 0;
                $row['notify_time'] = substr((string) $row['notify_time'], 0, 5);
                save_mobile_notification_schedule($row, $id);
                flash_set('success', $row['is_active'] ? 'Уведомление включено.' : 'Уведомление выключено.');
            }
            header('Location: mobile_notifications.php');
            exit;
        } else {
            $error = 'Неизвестное действие.';
        }
    }
}

$schedules = list_mobile_notification_schedules(false);
$success = flash_get('success');
$pageTitle = 'Уведомления на телефон — Администрирование';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'mobile_notifications';

require __DIR__ . '/../includes/header.php';

$formTitle = $editRow['title'] ?? 'СПО-ПРОГРЕСС';
$formBody = $editRow['body'] ?? '';
$formTime = isset($editRow['notify_time']) ? substr((string) $editRow['notify_time'], 0, 5) : '09:00';
$formFreq = (string) ($editRow['frequency'] ?? 'daily');
$formWeekday = (int) ($editRow['weekday'] ?? 1);
$formMonthDay = (int) ($editRow['month_day'] ?? 1);
$formActive = !isset($editRow['is_active']) || !empty($editRow['is_active']);
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель администратора</h1>
                <p class="text-muted">Расписание локальных уведомлений в мобильном приложении</p>
            </div>
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
        <h2><?= $editId > 0 ? 'Редактирование расписания' : 'Новое уведомление' ?></h2>
        <form method="post" class="form form--medium" id="mobile-notif-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= $editId > 0 ? $editId : 0 ?>">

            <div class="form__group">
                <label for="title">Заголовок</label>
                <input type="text" id="title" name="title" maxlength="120" value="<?= e($formTitle) ?>" required>
            </div>

            <div class="form__group">
                <label for="body">Текст уведомления</label>
                <textarea id="body" name="body" rows="3" required><?= e($formBody) ?></textarea>
            </div>

            <div class="form__row">
                <div class="form__group">
                    <label for="notify_time">Время</label>
                    <input type="time" id="notify_time" name="notify_time" value="<?= e($formTime) ?>" required>
                </div>
                <div class="form__group">
                    <label for="frequency">Периодичность</label>
                    <select id="frequency" name="frequency" required>
                        <option value="daily"<?= $formFreq === 'daily' ? ' selected' : '' ?>>Ежедневно</option>
                        <option value="weekly"<?= $formFreq === 'weekly' ? ' selected' : '' ?>>Еженедельно</option>
                        <option value="monthly"<?= $formFreq === 'monthly' ? ' selected' : '' ?>>Ежемесячно</option>
                    </select>
                </div>
            </div>

            <div class="form__group" data-freq-weekly<?= $formFreq === 'weekly' ? '' : ' hidden' ?>>
                <label for="weekday">День недели</label>
                <select id="weekday" name="weekday">
                    <?php foreach ([1 => 'Понедельник', 2 => 'Вторник', 3 => 'Среда', 4 => 'Четверг', 5 => 'Пятница', 6 => 'Суббота', 7 => 'Воскресенье'] as $num => $label): ?>
                    <option value="<?= $num ?>"<?= $formWeekday === $num ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form__group" data-freq-monthly<?= $formFreq === 'monthly' ? '' : ' hidden' ?>>
                <label for="month_day">Число месяца</label>
                <input type="number" id="month_day" name="month_day" min="1" max="31" value="<?= $formMonthDay ?>">
            </div>

            <div class="form__group">
                <label>
                    <input type="checkbox" name="is_active" value="1"<?= $formActive ? ' checked' : '' ?>>
                    Активно
                </label>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary"><?= $editId > 0 ? 'Сохранить' : 'Создать' ?></button>
                <?php if ($editId > 0): ?>
                <a href="mobile_notifications.php" class="btn btn--ghost">Отмена</a>
                <?php endif; ?>
            </div>
        </form>
        <p class="text-muted" style="margin-top:1rem;">
            Уведомления приходят на телефоны с установленным приложением после синхронизации
            (при открытии приложения). Проверка: создайте уведомление на ближайшие 1–2 минуты.
        </p>
    </section>

    <section class="panel">
        <h2>Текущие расписания</h2>
        <?php if ($schedules === []): ?>
            <p class="text-muted">Пока нет расписаний.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Заголовок / текст</th>
                            <th>Время</th>
                            <th>Периодичность</th>
                            <th>Статус</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $row): ?>
                            <?php
                            $freq = (string) $row['frequency'];
                            $detail = mobile_notification_frequency_label($freq);
                            if ($freq === 'weekly') {
                                $detail .= ', ' . mobile_notification_weekday_label(
                                    $row['weekday'] !== null ? (int) $row['weekday'] : null
                                );
                            } elseif ($freq === 'monthly') {
                                $detail .= ', ' . (int) $row['month_day'] . '-е число';
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e((string) $row['title']) ?></strong><br>
                                    <span class="text-muted"><?= e((string) $row['body']) ?></span>
                                </td>
                                <td><?= e(substr((string) $row['notify_time'], 0, 5)) ?></td>
                                <td><?= e($detail) ?></td>
                                <td><?= !empty($row['is_active']) ? 'Вкл' : 'Выкл' ?></td>
                                <td>
                                    <div class="form__actions" style="margin:0; flex-wrap:wrap; gap:0.35rem;">
                                        <a class="btn btn--ghost btn--sm" href="mobile_notifications.php?edit=<?= (int) $row['id'] ?>">Изменить</a>
                                        <form method="post" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                            <button type="submit" class="btn btn--ghost btn--sm">
                                                <?= !empty($row['is_active']) ? 'Выключить' : 'Включить' ?>
                                            </button>
                                        </form>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Удалить расписание?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                            <button type="submit" class="btn btn--ghost btn--sm">Удалить</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
(function () {
    const freq = document.getElementById('frequency');
    const weekly = document.querySelector('[data-freq-weekly]');
    const monthly = document.querySelector('[data-freq-monthly]');
    function sync() {
        const v = freq.value;
        weekly.hidden = v !== 'weekly';
        monthly.hidden = v !== 'monthly';
    }
    freq.addEventListener('change', sync);
    sync();
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
