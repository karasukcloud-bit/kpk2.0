<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/student_accounts.php';
require_once __DIR__ . '/../includes/journal.php';
require_once __DIR__ . '/../includes/curriculum.php';
require_once __DIR__ . '/../includes/grading.php';

require_student();

$student = current_student();
if ($student === null) {
    flash_set('error', 'Карточка студента не найдена. Обратитесь к куратору.');
    header('Location: cabinet.php');
    exit;
}

$period = get_active_gradebook_period();
$year = $period['academic_year'];
$semester = $period['semester'];
$studentId = (int) $student['id'];
$groupId = (int) $student['group_id'];
$subjects = get_student_journal_subjects($groupId, $year, $semester);
$itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;

$assignment = null;
foreach ($subjects as $row) {
    if ($itemId > 0 && (int) $row['curriculum_item_id'] === $itemId) {
        $assignment = $row;
        break;
    }
}

if ($itemId > 0 && $assignment === null) {
    $itemId = 0;
}

$lessons = [];
$grades = [];
$totals = [];
$gradingConfig = get_grading_config();
$isBrs = $gradingConfig['system'] === 'brs';

if ($assignment !== null) {
    $lessons = get_journal_lessons($itemId);
    $ownGrades = get_journal_grades_for_student($itemId, $studentId);
    $grades = [$studentId => $ownGrades];
    $totals = build_journal_totals([['id' => $studentId]], $lessons, $grades, $gradingConfig);
}

$pageTitle = 'Электронный журнал';
$showHeader = true;
$basePath = '../';
$currentStudentTab = 'journal';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Электронный журнал</h1>
                <p class="text-muted">
                    <?= e($student['full_name']) ?>
                    · группа <?= e((string) $student['group_number']) ?>
                    · <?= e($year) ?> · <?= e(semester_label($semester)) ?>
                </p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/student_nav.php'; ?>
    </section>

    <section class="panel">
        <?php if ($subjects === []): ?>
            <p class="text-muted">В текущем семестре для вашей группы нет предметов в журнале.</p>
        <?php elseif ($assignment === null): ?>
            <h2>Предметы</h2>
            <p class="text-muted">Выберите предмет, чтобы посмотреть свои отметки.</p>
            <div class="journal-choice-grid">
                <?php foreach ($subjects as $item): ?>
                <a
                    href="journal.php?item_id=<?= (int) $item['curriculum_item_id'] ?>"
                    class="journal-choice"
                >
                    <?= render_journal_choice_icon('journal') ?>
                    <strong><?= e($item['subject_name']) ?></strong>
                    <?php if (!empty($item['teacher_name'])): ?>
                    <span class="journal-choice__meta">Преп.: <?= e($item['teacher_name']) ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="panel__header panel__header--compact">
                <div>
                    <h2><?= e($assignment['subject_name']) ?></h2>
                    <?php if (!empty($assignment['teacher_name'])): ?>
                    <p class="text-muted">Преподаватель: <?= e($assignment['teacher_name']) ?></p>
                    <?php endif; ?>
                </div>
                <a href="journal.php" class="btn btn--ghost btn--sm">← К предметам</a>
            </div>

            <?php if ($lessons === []): ?>
                <p class="text-muted">По этому предмету пока нет занятий в журнале.</p>
            <?php else: ?>
                <div class="table-wrap journal-table-wrap">
                    <table class="table journal-table journal-table--readonly">
                        <thead>
                            <tr>
                                <th class="journal-table__student-col">Студент</th>
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
                                        <?php if (!empty($lesson['topic_title'])): ?>
                                        <span class="journal-lesson-topic" title="<?= e($lesson['topic_title']) ?>">
                                            <?= e($lesson['topic_title']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </th>
                                <?php endforeach; ?>
                                <th class="journal-table__total-col"><?= $isBrs ? 'Баллы' : 'Итого' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="journal-table__student-col">
                                    <?= e(person_last_first_name((string) $student['full_name'])) ?>
                                </td>
                                <?php foreach ($lessons as $index => $lesson): ?>
                                <?php
                                $lessonId = (int) $lesson['id'];
                                $gradeType = (string) ($lesson['grade_type'] ?? 'current');
                                $entry = $ownGrades[$lessonId] ?? empty_journal_entry();
                                $mark = (string) ($entry['mark'] ?? '');
                                ?>
                                <td class="journal-table__cell<?= $index % 2 === 1 ? ' journal-table__cell--alt' : '' ?><?= $gradeType === 'control' ? ' journal-table__cell--control' : '' ?>">
                                    <?= e(render_journal_mark_label($mark)) ?>
                                </td>
                                <?php endforeach; ?>
                                <td class="journal-table__total-col">
                                    <?php
                                    $total = $totals[$studentId] ?? null;
                                    echo is_array($total) ? ($total['html'] ?? '—') : '—';
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted table-hint">
                    Показаны только ваши отметки. Редактирование недоступно.
                    <?php if ($isBrs): ?>
                        Итог — баллы БРС и оценка по шкале.
                    <?php else: ?>
                        Итог — средний балл. Пропуски (Н) в среднем не учитываются.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
