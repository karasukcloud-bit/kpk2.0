<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/backup_merge.php';
require_once __DIR__ . '/../includes/journal.php';
require_once __DIR__ . '/../includes/students.php';

require_admin();

$bak = db_backup();
$error = null;
$groupNumber = trim((string) ($_GET['group'] ?? ''));
$itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;

$items = [];
$selected = null;
$sheet = null;
$backupGroupId = null;

if ($bak === null) {
    $error = 'Снимок БД недоступен: ' . db_backup_error();
} elseif ($groupNumber === '') {
    $error = 'Не указана группа.';
} else {
    $stmt = $bak->prepare('SELECT id, number FROM study_groups WHERE number = ? LIMIT 1');
    $stmt->execute([$groupNumber]);
    $bakGroup = $stmt->fetch();
    if (!$bakGroup) {
        $error = 'Группа ' . $groupNumber . ' не найдена в снимке.';
    } else {
        $backupGroupId = (int) $bakGroup['id'];
        $items = backup_merge_journal_items_for_group($bak, $backupGroupId);
        if ($itemId > 0) {
            foreach ($items as $row) {
                if ((int) $row['curriculum_item_id'] === $itemId) {
                    $selected = $row;
                    break;
                }
            }
            if ($selected === null) {
                $selected = backup_merge_get_backup_journal_item($bak, $itemId);
                if ($selected === null || (string) ($selected['group_number'] ?? '') !== $groupNumber) {
                    $error = 'Дисциплина не найдена в журнале снимка этой группы.';
                    $selected = null;
                    $itemId = 0;
                }
            }
            if ($selected !== null) {
                $sheet = backup_merge_backup_journal_sheet($bak, (int) ($selected['curriculum_item_id'] ?? $itemId));
                if ($sheet === null) {
                    $error = 'Не удалось загрузить журнал из снимка.';
                    $selected = null;
                }
            }
        }
    }
}

$pageTitle = 'Журнал из снимка БД';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'backup_merge';
require __DIR__ . '/../includes/header.php';

$backGroupUrl = 'backup_merge.php' . ($groupNumber !== '' ? ('?group=' . rawurlencode($groupNumber)) : '');
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Журнал из снимка</h1>
                <p class="text-muted">
                    Только просмотр данных новой БД
                    <?php if ($groupNumber !== ''): ?>
                        · группа <?= e($groupNumber) ?>
                    <?php endif; ?>
                </p>
            </div>
            <a href="<?= e($backGroupUrl) ?>" class="btn btn--ghost">← К снимку группы</a>
        </div>
        <?php require __DIR__ . '/../includes/admin_nav.php'; ?>
    </section>

    <?php if ($error): ?>
        <section class="panel">
            <div class="alert alert--error"><?= e($error) ?></div>
        </section>
    <?php elseif ($selected === null): ?>
        <section class="panel">
            <div class="panel__header panel__header--compact">
                <h2>Дисциплины с журналом</h2>
            </div>
            <?php if ($items === []): ?>
                <p class="text-muted">В снимке нет уроков электронного журнала по этой группе.</p>
            <?php else: ?>
                <div class="journal-choice-grid">
                    <?php foreach ($items as $item): ?>
                        <a
                            href="backup_journal.php?group=<?= rawurlencode($groupNumber) ?>&item_id=<?= (int) $item['curriculum_item_id'] ?>"
                            class="journal-choice"
                        >
                            <?= render_journal_choice_icon('journal') ?>
                            <strong><?= e((string) $item['subject_name']) ?></strong>
                            <span class="journal-choice__meta">
                                <?= e((string) $item['academic_year']) ?>
                                · сем. <?= e((string) $item['semester']) ?>
                                · уроков <?= (int) $item['lessons_count'] ?>
                                · оценок <?= (int) $item['grades_count'] ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <?php
        $item = $sheet['item'];
        $students = $sheet['students'];
        $lessons = $sheet['lessons'];
        $grades = $sheet['grades'];
        $totals = $sheet['totals'];
        ?>
        <section class="panel">
            <div class="panel__header panel__header--compact">
                <div>
                    <h2><?= e((string) $item['subject_name']) ?></h2>
                    <p class="text-muted">
                        <?= e((string) $item['academic_year']) ?>
                        · сем. <?= e((string) $item['semester']) ?>
                        <?php if (!empty($item['teacher_name'])): ?>
                            · <?= e((string) $item['teacher_name']) ?>
                        <?php endif; ?>
                        · только чтение
                    </p>
                </div>
                <a
                    href="backup_journal.php?group=<?= rawurlencode($groupNumber) ?>"
                    class="btn btn--ghost btn--sm"
                >← К дисциплинам</a>
            </div>

            <?php if ($students === []): ?>
                <p class="text-muted">В снимке нет студентов этой группы.</p>
            <?php elseif ($lessons === []): ?>
                <p class="text-muted">По дисциплине нет уроков.</p>
            <?php else: ?>
                <div class="table-wrap journal-table-wrap">
                    <table class="table journal-table">
                        <thead>
                            <tr>
                                <th class="journal-table__student-col">Студенты</th>
                                <?php foreach ($lessons as $index => $lesson): ?>
                                    <?php $gradeType = (string) ($lesson['grade_type'] ?? 'current'); ?>
                                    <th class="journal-table__lesson-col journal-table__lesson-col--<?= e($gradeType) ?><?= $index % 2 === 1 ? ' journal-table__lesson-col--alt' : '' ?>">
                                        <div class="journal-lesson-head">
                                            <div class="journal-lesson-meta">
                                                <span class="journal-lesson-date"><?= e(format_journal_date((string) $lesson['lesson_date'])) ?></span>
                                                <span
                                                    class="journal-lesson-type journal-lesson-type--<?= e($gradeType) ?>"
                                                    title="<?= e(journal_grade_type_label($gradeType)) ?>"
                                                ><?= e(journal_grade_type_short($gradeType)) ?></span>
                                            </div>
                                            <?php
                                            $lessonNote = trim((string) ($lesson['note'] ?? ''));
                                            if ($lessonNote !== ''):
                                            ?>
                                            <span class="journal-lesson-note" title="<?= e($lessonNote) ?>">
                                                <?= e($lessonNote) ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if (!empty($lesson['topic_title'])): ?>
                                            <span class="journal-lesson-topic" title="<?= e((string) $lesson['topic_title']) ?>">
                                                <?= e((string) $lesson['topic_title']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                                <th class="journal-table__total-col">Итого</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $rowIndex => $student): ?>
                                <?php $studentId = (int) $student['id']; ?>
                                <tr class="<?= $rowIndex % 2 === 1 ? 'journal-row--alt' : '' ?>">
                                    <td class="journal-table__student-col">
                                        <span class="journal-student-name"><?= e(person_last_first_name((string) $student['full_name'])) ?></span>
                                    </td>
                                    <?php foreach ($lessons as $index => $lesson): ?>
                                        <?php
                                        $lessonId = (int) $lesson['id'];
                                        $gradeType = (string) ($lesson['grade_type'] ?? 'current');
                                        $entry = $grades[$studentId][$lessonId] ?? empty_journal_entry();
                                        $mark = (string) ($entry['mark'] ?? '');
                                        ?>
                                        <td class="journal-table__cell journal-table__cell--readonly<?= $index % 2 === 1 ? ' journal-table__cell--alt' : '' ?><?= is_journal_weighted_control_type($gradeType) ? ' journal-table__cell--' . e($gradeType) : '' ?>">
                                            <?= e(render_journal_mark_label($mark)) ?>
                                            <?php if (!empty($entry['activity'])): ?>
                                                <span class="text-muted"> А</span>
                                            <?php endif; ?>
                                            <?php if (!empty($entry['late'])): ?>
                                                <span class="text-muted"> О</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="journal-table__total-col">
                                        <?php
                                        $total = $totals[$studentId] ?? null;
                                        echo is_array($total) ? ($total['html'] ?? '—') : '—';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted table-hint">Просмотр снимка: изменения здесь недоступны. Импорт — на странице группы снимка БД.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
