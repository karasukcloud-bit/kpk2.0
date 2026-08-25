<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity_log.php';
require_once __DIR__ . '/../includes/teachers.php';

require_admin();

$filters = [
    'action' => trim((string) ($_GET['action'] ?? '')),
    'user_id' => (int) ($_GET['user_id'] ?? 0),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
    'q' => trim((string) ($_GET['q'] ?? '')),
    'page' => (int) ($_GET['page'] ?? 1),
];

$result = search_activity_logs($filters);
$logs = $result['items'];
$users = list_activity_log_users();
$actionOptions = activity_log_action_options();

$queryBase = static function (array $override = []) use ($filters): string {
    $params = array_merge($filters, $override);
    unset($params['page']);
    $params = array_filter(
        $params,
        static fn ($value): bool => $value !== '' && $value !== 0
    );

    return 'logs.php?' . http_build_query($params);
};

$pageTitle = 'Журнал действий — Администрирование';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'logs';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель администратора</h1>
                <p class="text-muted">Журнал действий пользователей</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/admin_nav.php'; ?>
    </section>

    <section class="panel">
        <form method="get" class="form form--filter activity-log-filter">
            <div class="form__group">
                <label for="log_action">Действие</label>
                <select id="log_action" name="action">
                    <?php foreach ($actionOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= $filters['action'] === $value ? ' selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form__group">
                <label for="log_user_id">Пользователь</label>
                <select id="log_user_id" name="user_id">
                    <option value="">Все пользователи</option>
                    <?php foreach ($users as $user): ?>
                    <option
                        value="<?= (int) $user['id'] ?>"
                        <?= $filters['user_id'] === (int) $user['id'] ? ' selected' : '' ?>
                    ><?= e($user['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form__group">
                <label for="log_date_from">С</label>
                <input type="date" id="log_date_from" name="date_from" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="form__group">
                <label for="log_date_to">По</label>
                <input type="date" id="log_date_to" name="date_to" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="form__group">
                <label for="log_q">Поиск</label>
                <input
                    type="search"
                    id="log_q"
                    name="q"
                    value="<?= e($filters['q']) ?>"
                    placeholder="ФИО, группа, предмет..."
                >
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Применить</button>
                <a href="logs.php" class="btn btn--ghost">Сбросить</a>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel__header panel__header--compact">
            <h2>Записи</h2>
            <p class="text-muted">Всего: <?= (int) $result['total'] ?></p>
        </div>

        <?php if ($logs === []): ?>
            <p class="text-muted">Записей не найдено.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table activity-log-table">
                    <thead>
                        <tr>
                            <th class="activity-log-table__time">Дата и время</th>
                            <th class="activity-log-table__user">Пользователь</th>
                            <th class="activity-log-table__action">Действие</th>
                            <th>Детали</th>
                            <th class="activity-log-table__ip">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="activity-log-table__time"><?= e(format_date($log['created_at'] ?? null)) ?></td>
                            <td class="activity-log-table__user">
                                <?= e(($log['user_name'] ?? '') !== '' ? (string) $log['user_name'] : '—') ?>
                                <?php if (!empty($log['user_role'])): ?>
                                <span class="activity-log-table__role"><?= e(role_label((string) $log['user_role'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="activity-log-table__action">
                                <?= e(activity_log_action_label((string) $log['action'])) ?>
                            </td>
                            <td class="activity-log-table__details">
                                <?= e(activity_log_format_details($log)) ?>
                            </td>
                            <td class="activity-log-table__ip"><?= e((string) ($log['ip_address'] ?? '')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($result['pages'] > 1): ?>
            <nav class="pagination" aria-label="Страницы журнала">
                <?php if ($result['page'] > 1): ?>
                <a href="<?= e($queryBase(['page' => $result['page'] - 1])) ?>" class="btn btn--ghost btn--sm">← Назад</a>
                <?php endif; ?>
                <span class="pagination__info">
                    Страница <?= (int) $result['page'] ?> из <?= (int) $result['pages'] ?>
                </span>
                <?php if ($result['page'] < $result['pages']): ?>
                <a href="<?= e($queryBase(['page' => $result['page'] + 1])) ?>" class="btn btn--ghost btn--sm">Вперёд →</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
