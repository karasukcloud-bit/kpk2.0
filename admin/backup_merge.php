<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/backup_merge.php';

require_admin();

$bak = db_backup();
$error = null;
$success = flash_get('success');
$groupNumber = trim((string) ($_GET['group'] ?? $_POST['group'] ?? ''));
$liveStudentId = isset($_GET['live_id']) ? (int) $_GET['live_id'] : (int) ($_POST['live_id'] ?? 0);
$backupStudentId = isset($_GET['backup_id']) ? (int) $_GET['backup_id'] : (int) ($_POST['backup_id'] ?? 0);
$editMode = isset($_GET['edit']) || isset($_POST['edit']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } elseif ($bak === null) {
        $error = 'Снимок БД недоступен: ' . db_backup_error();
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $liveGroupIdPost = (int) ($_POST['live_group_id'] ?? 0);

        if ($action === 'apply_students' && $liveGroupIdPost > 0) {
            $result = backup_merge_apply_students_for_group($bak, $liveGroupIdPost);
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
        } elseif ($action === 'apply_attendance' && $liveGroupIdPost > 0) {
            $result = backup_merge_apply_attendance_for_group($bak, $liveGroupIdPost);
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
        } elseif ($action === 'save_student') {
            $id = (int) ($_POST['live_id'] ?? 0);
            $data = student_payload_from_post($_POST);
            $result = update_student($id, $data);
            if ($result['success']) {
                flash_set('success', 'Данные студента в live сохранены.');
                $q = 'group=' . rawurlencode($groupNumber) . '&live_id=' . $id;
                if ($backupStudentId > 0) {
                    $q .= '&backup_id=' . $backupStudentId;
                }
                header('Location: backup_merge.php?' . $q);
                exit;
            }
            $error = $result['error'] ?? 'Не удалось сохранить.';
            $editMode = true;
            $liveStudentId = $id;
        } elseif ($action === 'fill_from_backup') {
            $id = (int) ($_POST['live_id'] ?? 0);
            $bakId = (int) ($_POST['backup_id'] ?? 0);
            $live = get_student_by_id($id);
            $backup = $bakId > 0 ? backup_merge_get_backup_student($bak, $bakId) : null;
            if ($live === null || $backup === null) {
                $error = 'Не найдена пара live/снимок для заполнения.';
            } else {
                $copyName = !empty($_POST['copy_full_name']);
                $result = backup_merge_apply_one_student_from_backup($live, $backup, $copyName);
                if ($result['success']) {
                    $n = count($result['updated_fields']);
                    flash_set(
                        'success',
                        $n > 0
                            ? ('Из снимка дописано полей: ' . $n . ' (' . implode(', ', $result['updated_fields']) . ').')
                            : 'Пустых полей для дозаписи нет.'
                    );
                    header(
                        'Location: backup_merge.php?group=' . rawurlencode($groupNumber)
                        . '&live_id=' . $id . '&backup_id=' . $bakId
                    );
                    exit;
                }
                $error = $result['error'] ?? 'Не удалось дописать из снимка.';
            }
            $liveStudentId = $id;
            $backupStudentId = $bakId;
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
$fieldLabels = backup_merge_field_labels();

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

$viewLive = $liveStudentId > 0 ? get_student_by_id($liveStudentId) : null;
$viewBackup = null;
if ($bak && $backupStudentId > 0) {
    $viewBackup = backup_merge_get_backup_student($bak, $backupStudentId);
} elseif ($viewLive && $detail !== null) {
    foreach ($detail['matches'] as $pair) {
        if ((int) $pair['live']['id'] === $liveStudentId) {
            $viewBackup = $pair['backup'];
            $backupStudentId = (int) $viewBackup['id'];
            break;
        }
    }
}

$displayOrDash = static function (?string $value): string {
    $value = trim((string) $value);

    return $value !== '' ? $value : '—';
};

$formData = $viewLive ?? [];
if ($editMode && $_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'save_student') {
    $formData = array_merge($formData, student_payload_from_post($_POST));
}
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Временная дозапись из снимка БД</h1>
                <p class="text-muted">
                    Live: <code><?= e($liveDbName) ?></code> · Снимок:
                    <code><?= e($backupDbName) ?></code>.
                    Сопоставление по ФИО. Можно открыть карточку студента, сравнить со снимком и отредактировать live.
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
        <?php elseif (!($viewLive || $viewBackup)): ?>
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

    <?php if ($viewLive || $viewBackup): ?>
        <section class="panel" style="margin-top:1.5rem;">
            <div class="panel__header">
                <div>
                    <h2>
                        <?php if ($viewLive): ?>
                            <?= e((string) $viewLive['full_name']) ?>
                        <?php else: ?>
                            <?= e((string) ($viewBackup['full_name'] ?? 'Студент снимка')) ?>
                        <?php endif; ?>
                    </h2>
                    <p class="text-muted">Группа <?= e($groupNumber) ?></p>
                </div>
                <a class="btn btn--small"
                   href="backup_merge.php?group=<?= rawurlencode($groupNumber) ?>">← К группе</a>
            </div>

            <?php if ($viewLive && $viewBackup): ?>
                <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem;">
                    <?php if (!$editMode): ?>
                        <a class="btn btn--primary"
                           href="backup_merge.php?group=<?= rawurlencode($groupNumber) ?>&live_id=<?= (int) $viewLive['id'] ?>&backup_id=<?= (int) $viewBackup['id'] ?>&edit=1">
                            Редактировать live
                        </a>
                        <form method="post" style="display:inline;"
                              onsubmit="return confirm('Дописать пустые поля live из снимка для этого студента?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="fill_from_backup">
                            <input type="hidden" name="group" value="<?= e($groupNumber) ?>">
                            <input type="hidden" name="live_id" value="<?= (int) $viewLive['id'] ?>">
                            <input type="hidden" name="backup_id" value="<?= (int) $viewBackup['id'] ?>">
                            <label style="display:inline-flex;align-items:center;gap:.35rem;margin-right:.5rem;">
                                <input type="checkbox" name="copy_full_name" value="1">
                                заменить ФИО из снимка
                            </label>
                            <button type="submit" class="btn">Дописать пустые из снимка</button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if ($editMode): ?>
                    <form method="post" class="form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save_student">
                        <input type="hidden" name="edit" value="1">
                        <input type="hidden" name="group" value="<?= e($groupNumber) ?>">
                        <input type="hidden" name="live_id" value="<?= (int) $viewLive['id'] ?>">
                        <input type="hidden" name="backup_id" value="<?= (int) $viewBackup['id'] ?>">
                        <?php
                        $data = $formData;
                        require __DIR__ . '/../includes/student_fields.php';
                        ?>
                        <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem;">
                            <button type="submit" class="btn btn--primary">Сохранить в live</button>
                            <a class="btn"
                               href="backup_merge.php?group=<?= rawurlencode($groupNumber) ?>&live_id=<?= (int) $viewLive['id'] ?>&backup_id=<?= (int) $viewBackup['id'] ?>">
                                Отмена
                            </a>
                        </div>
                    </form>
                    <h3 style="margin-top:1.5rem;">Снимок (только просмотр)</h3>
                <?php endif; ?>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Поле</th>
                            <th>Live</th>
                            <th>Снимок</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $compareFields = array_merge(['full_name'], backup_merge_student_profile_fields());
                        foreach ($compareFields as $field):
                            $liveVal = (string) ($viewLive[$field] ?? '');
                            $bakVal = (string) ($viewBackup[$field] ?? '');
                            $diff = trim($liveVal) !== trim($bakVal);
                            ?>
                            <tr<?= $diff ? ' style="background:rgba(255,193,7,.12)"' : '' ?>>
                                <td><?= e($fieldLabels[$field] ?? $field) ?></td>
                                <td><?= e($displayOrDash($liveVal)) ?></td>
                                <td><?= e($displayOrDash($bakVal)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($viewLive): ?>
                <p class="text-muted">В снимке пары по ФИО нет — можно править только live.</p>
                <?php if (!$editMode): ?>
                    <a class="btn btn--primary"
                       href="backup_merge.php?group=<?= rawurlencode($groupNumber) ?>&live_id=<?= (int) $viewLive['id'] ?>&edit=1">
                        Редактировать live
                    </a>
                    <dl class="profile-list" style="margin-top:1rem;">
                        <?php foreach (array_merge(['full_name'], backup_merge_student_profile_fields()) as $field): ?>
                            <dt><?= e($fieldLabels[$field] ?? $field) ?></dt>
                            <dd><?= e($displayOrDash((string) ($viewLive[$field] ?? ''))) ?></dd>
                        <?php endforeach; ?>
                    </dl>
                <?php else: ?>
                    <form method="post" class="form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save_student">
                        <input type="hidden" name="edit" value="1">
                        <input type="hidden" name="group" value="<?= e($groupNumber) ?>">
                        <input type="hidden" name="live_id" value="<?= (int) $viewLive['id'] ?>">
                        <?php
                        $data = $formData;
                        require __DIR__ . '/../includes/student_fields.php';
                        ?>
                        <div style="display:flex;gap:.75rem;margin-top:1rem;">
                            <button type="submit" class="btn btn--primary">Сохранить в live</button>
                            <a class="btn" href="backup_merge.php?group=<?= rawurlencode($groupNumber) ?>&live_id=<?= (int) $viewLive['id'] ?>">Отмена</a>
                        </div>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted">Только снимок (в live нет пары) — правка недоступна, сначала заведите/выровняйте студента в live.</p>
                <dl class="profile-list">
                    <?php foreach (array_merge(['full_name'], backup_merge_student_profile_fields()) as $field): ?>
                        <dt><?= e($fieldLabels[$field] ?? $field) ?></dt>
                        <dd><?= e($displayOrDash((string) ($viewBackup[$field] ?? ''))) ?></dd>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </section>

    <?php elseif ($detail !== null && $liveGroupId): ?>
        <section class="panel" style="margin-top: 1.5rem;">
            <div class="panel__header">
                <div>
                    <h2>Группа <?= e($groupNumber) ?></h2>
                    <p class="text-muted">
                        Совпало: <?= count($detail['matches']) ?>,
                        без пары live: <?= count($detail['live_unmatched']) ?>,
                        без пары снимок: <?= count($detail['backup_unmatched']) ?>.
                        Нажмите «Карточка», чтобы посмотреть и править данные.
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
                        <th></th>
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
                            <td>
                                <a class="btn btn--small"
                                   href="backup_merge.php?group=<?= rawurlencode($groupNumber) ?>&live_id=<?= (int) $pair['live']['id'] ?>&backup_id=<?= (int) $pair['backup']['id'] ?>">
                                    Карточка
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($detail['matches'] === []): ?>
                        <tr><td colspan="5" class="text-muted">Пока нет совпадений по ФИО.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($detail['live_unmatched'] !== []): ?>
                <h3 style="margin-top:1rem;">Live без пары</h3>
                <ul>
                    <?php foreach ($detail['live_unmatched'] as $s): ?>
                        <li>
                            <?= e((string) $s['full_name']) ?>
                            <a class="btn btn--small"
                               href="backup_merge.php?group=<?= rawurlencode($groupNumber) ?>&live_id=<?= (int) $s['id'] ?>">
                                Карточка
                            </a>
                        </li>
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
                            <a class="btn btn--small"
                               href="backup_merge.php?group=<?= rawurlencode($groupNumber) ?>&backup_id=<?= (int) $s['id'] ?>">
                                Карточка
                            </a>
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
