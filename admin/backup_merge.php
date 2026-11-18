<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/backup_merge.php';

require_admin();

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
        $liveGroupId = (int) ($_POST['live_group_id'] ?? 0);

        if ($action === 'apply_students' && $liveGroupId > 0) {
            $result = backup_merge_apply_students_for_group($bak, $liveGroupId);
            if ($result['success']) {
                flash_set(
                    'success',
                    'Студенты группы: обновлено ' . $result['updated']
                    . ', без изменений ' . $result['skipped'] . '.'
                );
                header('Location: backup_merge.php?group=' . rawurlencode($groupNumber));
                exit;
            }
            $error = $result['error'] ?? 'Не удалось дописать студентов.';
        } elseif ($action === 'apply_attendance' && $liveGroupId > 0) {
            $result = backup_merge_apply_attendance_for_group($bak, $liveGroupId);
            if ($result['success']) {
                flash_set(
                    'success',
                    'Пропуски сен–окт: дней добавлено ' . $result['days']
                    . ', записей ' . $result['entries']
                    . ', пропущено ' . $result['skipped_entries'] . '.'
                );
                header('Location: backup_merge.php?group=' . rawurlencode($groupNumber));
                exit;
            }
            $error = $result['error'] ?? 'Не удалось импортировать пропуски.';
        } else {
            $error = 'Неизвестное действие или не выбрана группа.';
        }
    }
}

$pageTitle = 'Снимок БД — дозапись';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'backup_merge';
require __DIR__ . '/../includes/header.php';

$liveDbConfig = require __DIR__ . '/../config/database.php';
$backupDbConfig = require __DIR__ . '/../config/database_backup.php';
$liveDbName = (string) ($liveDbConfig['dbname'] ?? 'spo_progress');
$backupDbName = (string) ($backupDbConfig['dbname'] ?? '');

$summaries = $bak ? backup_merge_group_summaries($bak) : [];
$detail = null;
$liveGroupId = null;
$backupGroupId = null;

if ($bak && $groupNumber !== '') {
    foreach ($summaries as $row) {
        if ((string) $row['number'] === $groupNumber) {
            $liveGroupId = $row['live_group_id'];
            $backupGroupId = $row['backup_group_id'];
            break;
        }
    }
    if ($liveGroupId && $backupGroupId) {
        $liveStudents = backup_merge_live_students_in_group((int) $liveGroupId);
        $bakStudents = backup_merge_backup_students_in_group($bak, (int) $backupGroupId);
        $detail = backup_merge_match_students($liveStudents, $bakStudents);
    }
}
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Временная дозапись из снимка БД</h1>
                <p class="text-muted">
                    Live: <code><?= e($liveDbName) ?></code> · Снимок:
                    <code><?= e($backupDbName) ?></code>
                    (user: <code><?= e((string) ($backupDbConfig['username'] ?? '')) ?></code>).
                    Сопоставление по ФИО внутри номера группы. Пустые поля дописываются, заполненные не затираются.
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
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Группа</th>
                        <th>Live</th>
                        <th>Снимок</th>
                        <th>Совпало ФИО</th>
                        <th>Есть что дописать</th>
                        <th>Без пары (live)</th>
                        <th>Без пары (снимок)</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($summaries as $row): ?>
                        <?php
                        $ok = (int) $row['matched'] > 0 && (int) $row['live_unmatched'] === 0 && (int) $row['backup_unmatched'] === 0;
                        ?>
                        <tr>
                            <td><strong><?= e((string) $row['number']) ?></strong></td>
                            <td><?= (int) $row['live_count'] ?></td>
                            <td><?= (int) $row['backup_count'] ?></td>
                            <td><?= (int) $row['matched'] ?></td>
                            <td><?= (int) $row['with_fields'] ?></td>
                            <td><?= (int) $row['live_unmatched'] ?></td>
                            <td><?= (int) $row['backup_unmatched'] ?></td>
                            <td>
                                <?php if ($row['live_group_id'] && $row['backup_group_id']): ?>
                                    <a class="btn btn--small<?= $ok ? ' btn--primary' : '' ?>"
                                       href="backup_merge.php?group=<?= rawurlencode((string) $row['number']) ?>">
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

    <?php if ($detail !== null && $liveGroupId): ?>
        <section class="panel" style="margin-top: 1.5rem;">
            <div class="panel__header">
                <div>
                    <h2>Группа <?= e($groupNumber) ?></h2>
                    <p class="text-muted">
                        Совпало: <?= count($detail['matches']) ?>,
                        без пары live: <?= count($detail['live_unmatched']) ?>,
                        без пары снимок: <?= count($detail['backup_unmatched']) ?>.
                    </p>
                </div>
                <a href="backup_merge.php" class="btn btn--small">← Все группы</a>
            </div>

            <div style="display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1rem;">
                <form method="post" onsubmit="return confirm('Дописать пустые поля профиля студентам этой группы из снимка?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="group" value="<?= e($groupNumber) ?>">
                    <input type="hidden" name="live_group_id" value="<?= (int) $liveGroupId ?>">
                    <input type="hidden" name="action" value="apply_students">
                    <button type="submit" class="btn btn--primary">Дописать данные студентов</button>
                </form>
                <form method="post" onsubmit="return confirm('Импортировать пропуски сен–окт 2026 для сопоставленных студентов этой группы?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="group" value="<?= e($groupNumber) ?>">
                    <input type="hidden" name="live_group_id" value="<?= (int) $liveGroupId ?>">
                    <input type="hidden" name="action" value="apply_attendance">
                    <button type="submit" class="btn">Импорт пропусков сен–окт</button>
                </form>
            </div>

            <h3>Сопоставления</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Live ФИО</th>
                        <th>Снимок ФИО</th>
                        <th>Телефон (снимок)</th>
                        <th>Поля к дозаписи</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($detail['matches'] as $pair): ?>
                        <tr>
                            <td><?= e((string) $pair['live']['full_name']) ?></td>
                            <td><?= e((string) $pair['backup']['full_name']) ?></td>
                            <td><?= e((string) ($pair['backup']['phone'] ?? '')) ?></td>
                            <td>
                                <?php if ($pair['fields_to_fill'] === []): ?>
                                    <span class="text-muted">уже заполнено</span>
                                <?php else: ?>
                                    <code><?= e(implode(', ', $pair['fields_to_fill'])) ?></code>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($detail['matches'] === []): ?>
                        <tr><td colspan="4" class="text-muted">Пока нет совпадений по ФИО — добавьте/выровняйте списки на проде.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($detail['live_unmatched'] !== []): ?>
                <h3 style="margin-top:1rem;">Live без пары</h3>
                <ul>
                    <?php foreach ($detail['live_unmatched'] as $s): ?>
                        <li><?= e((string) $s['full_name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($detail['backup_unmatched'] !== []): ?>
                <h3 style="margin-top:1rem;">Снимок без пары</h3>
                <ul>
                    <?php foreach ($detail['backup_unmatched'] as $s): ?>
                        <li>
                            <?= e((string) $s['full_name']) ?>
                            <?php if (!empty($s['phone'])): ?>
                                <span class="text-muted">· <?= e((string) $s['phone']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php elseif ($groupNumber !== '' && $bak): ?>
        <section class="panel" style="margin-top: 1.5rem;">
            <div class="alert alert--error">Группа <?= e($groupNumber) ?> не найдена сразу в live и снимке.</div>
            <a href="backup_merge.php" class="btn btn--small">← Все группы</a>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
