<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/backup_merge.php';
require_once __DIR__ . '/../includes/student_activities.php';

require_admin();
ensure_student_activities_schema();

$bak = db_backup();
$error = null;
$success = flash_get('success');
$groupNumber = trim((string) ($_GET['group'] ?? $_POST['group'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } elseif ($bak === null) {
        $error = 'Снимок БД недоступен: ' . db_backup_error();
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $liveGroupIdPost = (int) ($_POST['live_group_id'] ?? 0);
        if ($action === 'apply_activities' && $liveGroupIdPost > 0) {
            $result = backup_merge_apply_activities_for_group($bak, $liveGroupIdPost);
            if ($result['success']) {
                flash_set(
                    'success',
                    'Занятость: записано студентам ' . $result['imported']
                    . ', записей кружков/секций ' . $result['activities']
                    . ', пропущено ' . $result['skipped'] . '.'
                );
                header('Location: activities.php?group=' . rawurlencode($groupNumber));
                exit;
            }
            $error = $result['error'] ?? 'Не удалось записать занятость.';
        } else {
            $error = 'Неизвестное действие или не выбрана группа.';
        }
    }
}

$pageTitle = 'Занятость — импорт из снимка';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'activities';
require __DIR__ . '/../includes/header.php';

$liveDbConfig = require __DIR__ . '/../config/database.php';
$backupDbConfig = require __DIR__ . '/../config/database_backup.php';
$liveDbName = (string) ($liveDbConfig['dbname'] ?? 'spo_progress');
$backupDbName = (string) ($backupDbConfig['dbname'] ?? '');

$summaries = $bak ? backup_merge_group_summaries($bak) : [];
$liveGroupId = null;
$backupGroupId = null;
$activitiesPreview = null;

if ($bak && $groupNumber !== '') {
    foreach ($summaries as $row) {
        if ((string) $row['number'] === $groupNumber) {
            $liveGroupId = $row['live_group_id'];
            $backupGroupId = $row['backup_group_id'];
            break;
        }
    }
    if ($liveGroupId && $backupGroupId) {
        $activitiesPreview = backup_merge_activities_preview_for_group($bak, (int) $liveGroupId);
    }
}

$typeLabel = static function (string $type): string {
    return $type === 'section' ? 'Секция' : 'Кружок';
};
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Занятость во внеурочное время</h1>
                <p class="text-muted">
                    Данные из снимка <code><?= e($backupDbName) ?></code>
                    → запись в <code><?= e($liveDbName) ?></code> по ФИО.
                    Уже заполненные в текущей БД не перезаписываются.
                </p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert--success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($bak === null): ?>
            <div class="alert alert--error">
                Не удалось подключиться к снимку:
                <?= e(db_backup_error()) ?>
            </div>
        <?php elseif ($activitiesPreview === null): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Группа</th>
                        <th>Live</th>
                        <th>Снимок</th>
                        <th>Совпало ФИО</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($summaries as $row): ?>
                        <tr>
                            <td><strong><?= e((string) $row['number']) ?></strong></td>
                            <td><?= (int) $row['live_count'] ?></td>
                            <td><?= (int) $row['backup_count'] ?></td>
                            <td><?= (int) $row['matched'] ?></td>
                            <td>
                                <?php if ($row['live_group_id'] && $row['backup_group_id']): ?>
                                    <a class="btn btn--small btn--primary"
                                       href="activities.php?group=<?= rawurlencode((string) $row['number']) ?>">
                                        Открыть
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">нет пары групп</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($activitiesPreview !== null && $liveGroupId): ?>
        <section class="panel" style="margin-top:1.5rem;">
            <div class="panel__header">
                <div>
                    <h2>Группа <?= e($groupNumber) ?></h2>
                    <p class="text-muted">
                        К импорту: <?= (int) $activitiesPreview['to_import'] ?>,
                        уже в live: <?= (int) $activitiesPreview['already_filled'] ?>,
                        нет в снимке: <?= (int) $activitiesPreview['no_backup_data'] ?>.
                    </p>
                </div>
                <a href="activities.php" class="btn btn--small">← Все группы</a>
            </div>

            <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem;">
                <form method="post"
                      onsubmit="return confirm('Записать занятость из снимка в текущую БД для сопоставленных студентов группы <?= e($groupNumber) ?>?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="group" value="<?= e($groupNumber) ?>">
                    <input type="hidden" name="live_group_id" value="<?= (int) $liveGroupId ?>">
                    <input type="hidden" name="action" value="apply_activities">
                    <button type="submit" class="btn btn--primary"
                        <?= (int) $activitiesPreview['to_import'] === 0 ? ' disabled' : '' ?>>
                        Записать занятость в текущую БД
                    </button>
                </form>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>ФИО (live)</th>
                        <th>ФИО (снимок)</th>
                        <th>Занятость из снимка</th>
                        <th>Статус</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($activitiesPreview['rows'] as $row): ?>
                        <tr>
                            <td><?= e((string) $row['live_name']) ?></td>
                            <td><?= e((string) $row['backup_name']) ?></td>
                            <td>
                                <?php if ($row['backup_activities'] === []): ?>
                                    <span class="text-muted">—</span>
                                <?php else: ?>
                                    <ul style="margin:0;padding-left:1.1rem;">
                                        <?php foreach ($row['backup_activities'] as $activity): ?>
                                            <li>
                                                <?= e($typeLabel((string) ($activity['activity_type'] ?? 'club'))) ?>:
                                                <?= e((string) ($activity['title'] ?? '')) ?>
                                                <?php if (trim((string) ($activity['place'] ?? '')) !== ''): ?>
                                                    <span class="text-muted">(<?= e((string) $activity['place']) ?>)</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'import'): ?>
                                    <span>к записи</span>
                                <?php elseif ($row['status'] === 'already'): ?>
                                    <span class="text-muted">уже в live</span>
                                <?php else: ?>
                                    <span class="text-muted">нет в снимке</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($activitiesPreview['rows'] === []): ?>
                        <tr><td colspan="4" class="text-muted">Нет сопоставлений по ФИО.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php elseif ($groupNumber !== '' && $bak): ?>
        <section class="panel" style="margin-top:1.5rem;">
            <div class="alert alert--error">Группа <?= e($groupNumber) ?> не найдена сразу в live и снимке.</div>
            <a href="activities.php" class="btn btn--small">← Все группы</a>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
