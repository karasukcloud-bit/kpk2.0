<?php

declare(strict_types=1);

$archivePanel = $archivePanel ?? 'admin';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../archive.php';
require_once __DIR__ . '/../teachers.php';
require_once __DIR__ . '/../students.php';

if ($archivePanel === 'curator') {
    require_curator_panel();
} else {
    require_archive_manager();
}

$archiveId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$archive = $archiveId > 0 ? get_archive_period_by_id($archiveId) : null;
$group = ($archive && $archive['archive_type'] === 'gradebook')
    ? get_archive_gradebook_group($archiveId, $groupId)
    : null;

if ($archive === null || $group === null || !can_view_archive_gradebook()) {
    flash_set('error', 'Архивная ведомость не найдена.');
    header('Location: archive.php');
    exit;
}

$error = null;
$canEdit = can_manage_archives();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'edit_grade')) {
    if (!$canEdit) {
        $error = 'Редактировать архив могут только завуч и администратор.';
    } elseif (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $result = update_archived_gradebook_grade(
            $archiveId,
            $groupId,
            (int) ($_POST['student_id'] ?? 0),
            (int) ($_POST['item_id'] ?? 0),
            (int) ($_POST['new_grade'] ?? 0),
            (string) ($_POST['reason_code'] ?? ''),
            (string) ($_POST['reason_text'] ?? '')
        );
        if ($result['success']) {
            flash_set('success', 'Оценка в архиве обновлена.');
            header('Location: archive_gradebook.php?id=' . $archiveId . '&group_id=' . $groupId);
            exit;
        }
        $error = $result['error'];
    }
}

$sheet = get_archive_gradebook_sheet($archiveId, $groupId);
$students = $sheet['students'];
$subjects = $sheet['subjects'];
$grades = $sheet['grades'];
$showGradeChanges = $archivePanel !== 'curator';
$changes = $showGradeChanges ? $sheet['changes'] : [];
$success = flash_get('success');
$backPeriod = urlencode((string) $archive['academic_year'] . '|' . (string) $archive['semester']);

$pageTitle = 'Архив ведомости — ' . $group['group_number'];
$showHeader = true;
$basePath = '../';
if ($archivePanel === 'admin') {
    $currentAdminTab = 'archive';
} elseif ($archivePanel === 'deputy') {
    $currentDeputyTab = 'archive';
} else {
    $currentCuratorTab = 'archive';
    $curatorGroupId = $groupId;
}
require __DIR__ . '/../header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Архив: ведомость группы <?= e($group['group_number']) ?></h1>
                <p class="text-muted">
                    <?= e($archive['academic_year']) ?> · <?= e(semester_label((string) $archive['semester'])) ?>
                    <?php if (!empty($group['specialty_name'])): ?>
                        · <?= e($group['specialty_name']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <a href="archive.php?period=<?= e($backPeriod) ?>" class="btn btn--ghost">← Назад</a>
            <?php if ($subjects !== [] && $students !== []): ?>
            <a
                href="archive_gradebook_pdf.php?id=<?= $archiveId ?>&group_id=<?= $groupId ?>"
                class="btn btn--secondary"
            >Скачать PDF</a>
            <?php endif; ?>
        </div>
        <?php
        if ($archivePanel === 'admin') {
            require __DIR__ . '/../admin_nav.php';
        } elseif ($archivePanel === 'deputy') {
            require __DIR__ . '/../deputy_nav.php';
        } else {
            require __DIR__ . '/../curator_nav.php';
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
        <?php if ($subjects === [] || $students === []): ?>
            <p class="text-muted">В архиве нет данных по этой группе.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table gradebook-table gradebook-table--readonly">
                    <thead>
                        <tr>
                            <th class="gradebook-table__student-col">Студенты</th>
                            <?php foreach ($subjects as $subject): ?>
                            <th class="gradebook-table__subject-col">
                                <span class="gradebook-subject-title"><?= e($subject['subject_name']) ?></span>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td class="gradebook-table__student-col">
                                <?= e(person_last_first_name((string) $student['full_name'])) ?>
                            </td>
                            <?php foreach ($subjects as $subject): ?>
                            <?php
                            $studentId = (int) $student['student_id'];
                            $itemId = (int) $subject['curriculum_item_id'];
                            $value = $grades[$studentId][$itemId] ?? null;
                            $historyPayload = [];
                            if ($showGradeChanges) {
                                $changeKey = $studentId . ':' . $itemId;
                                $cellChanges = $changes[$changeKey] ?? [];
                                foreach ($cellChanges as $change) {
                                    $reason = archive_reason_label((string) $change['reason_code']);
                                    if (trim((string) ($change['reason_text'] ?? '')) !== '') {
                                        $reason .= ' — ' . trim((string) $change['reason_text']);
                                    }
                                    $historyPayload[] = [
                                        'user' => (string) (($change['changed_by_name'] ?? '') !== '' ? $change['changed_by_name'] : '—'),
                                        'at' => format_date($change['changed_at'] ?? null),
                                        'reason' => $reason,
                                        'from' => $change['old_grade'] !== null && $change['old_grade'] !== '' ? (string) $change['old_grade'] : '—',
                                        'to' => (string) $change['new_grade'],
                                    ];
                                }
                            }
                            ?>
                            <td class="gradebook-table__cell gradebook-table__grade-cell archive-grade-cell">
                                <?php if ($canEdit): ?>
                                <button
                                    type="button"
                                    class="archive-grade-btn"
                                    data-archive-edit-open
                                    data-student-id="<?= $studentId ?>"
                                    data-item-id="<?= $itemId ?>"
                                    data-student-name="<?= e(person_last_first_name((string) $student['full_name'])) ?>"
                                    data-subject-name="<?= e($subject['subject_name']) ?>"
                                    data-current-grade="<?= e((string) ($value ?? '')) ?>"
                                ><?= $value !== null ? e((string) $value) : '—' ?></button>
                                <?php else: ?>
                                    <?= $value !== null ? e((string) $value) : '—' ?>
                                <?php endif; ?>
                                <?php if ($showGradeChanges && $historyPayload !== []): ?>
                                <button
                                    type="button"
                                    class="archive-history-btn"
                                    title="История изменений"
                                    data-archive-history-open
                                    data-history="<?= e(json_encode($historyPayload, JSON_UNESCAPED_UNICODE)) ?>"
                                >i</button>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($canEdit): ?>
            <p class="text-muted table-hint">Нажмите на оценку, чтобы изменить её в архиве. Текущие отметки журнала не меняются — обновляется только итог.</p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php if ($canEdit): ?>
<div class="modal" data-archive-edit-modal hidden>
    <div class="modal__backdrop" data-archive-edit-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="archive-edit-title">
        <div class="modal__header">
            <h2 id="archive-edit-title">Изменить оценку</h2>
            <button type="button" class="modal__close" data-archive-edit-close aria-label="Закрыть">&times;</button>
        </div>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit_grade">
            <input type="hidden" name="student_id" value="" data-archive-edit-student>
            <input type="hidden" name="item_id" value="" data-archive-edit-item>
            <p class="text-muted" data-archive-edit-meta></p>
            <div class="form__group">
                <label for="new_grade">Новая оценка</label>
                <select id="new_grade" name="new_grade" required data-archive-edit-grade>
                    <option value="5">5</option>
                    <option value="4">4</option>
                    <option value="3">3</option>
                    <option value="2">2</option>
                </select>
            </div>
            <div class="form__group">
                <label for="reason_code">Причина изменения</label>
                <select id="reason_code" name="reason_code" required data-archive-reason-code>
                    <?php foreach (archive_grade_change_reasons() as $code => $label): ?>
                    <option value="<?= e($code) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form__group" data-archive-reason-text-wrap hidden>
                <label for="reason_text">Уточнение</label>
                <input type="text" id="reason_text" name="reason_text" data-archive-reason-text placeholder="Опишите причину">
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сохранить</button>
                <button type="button" class="btn btn--ghost" data-archive-edit-close>Отмена</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($showGradeChanges): ?>
<div class="modal" data-archive-history-modal hidden>
    <div class="modal__backdrop" data-archive-history-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="archive-history-title">
        <div class="modal__header">
            <h2 id="archive-history-title">История исправлений</h2>
            <button type="button" class="modal__close" data-archive-history-close aria-label="Закрыть">&times;</button>
        </div>
        <div class="archive-history-list" data-archive-history-body></div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../footer.php'; ?>
