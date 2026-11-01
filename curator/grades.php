<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/grading.php';

require_curator_panel();

$ctx = resolve_curator_group_context(isset($_GET['group_id']) ? (int) $_GET['group_id'] : null);
$groups = $ctx['groups'];
$groupId = $ctx['group_id'];
$group = $ctx['group'];
$students = $group ? get_students_by_group($groupId) : [];
$period = get_active_gradebook_period();
$year = $period['academic_year'];
$semester = $period['semester'];
$subjects = $group ? get_group_curriculum_subjects($groupId, $year, $semester) : [];
$grades = $group ? get_gradebook_grades_from_journal($groupId, $year, $semester) : [];
$summary = build_gradebook_summary($students, $subjects, $grades);
$studentLists = build_gradebook_student_lists($students, $subjects, $grades);
$gradingConfig = get_grading_config();
$periodHint = gradebook_period_mismatch_hint($period);

$pageTitle = 'Электронная ведомость — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'grades';
$curatorGroupId = $groupId;
$curatorGroups = $groups;
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide curator-page">
    <?php
    $curatorPageTitle = 'Электронная ведомость';
    $curatorPageSubtitle = $group
        ? ('Группа ' . (string) $group['number'] . ' · ' . $year . ' · ' . semester_label($semester))
        : 'Электронная ведомость группы';
    $curatorPageIcon = 'grades';
    require __DIR__ . '/../includes/curator_page_hero.php';
    ?>

    <section class="panel">
        <?php if ($periodHint): ?>
            <div class="alert alert--error"><?= e($periodHint) ?></div>
        <?php endif; ?>
        <?php if (empty($groups)): ?>
            <p class="text-muted">Вам ещё не назначена группа.</p>
        <?php elseif ($group === null): ?>
            <p class="text-muted">Выберите группу, чтобы открыть электронную ведомость.</p>
        <?php elseif (empty($subjects)): ?>
            <p>
                Группа <strong><?= e($group['number']) ?></strong>
                · учебный год <?= e($year) ?>
                · <?= e(semester_label($semester)) ?>
            </p>
            <p class="text-muted">
                Для этого периода нет предметов в учебном плане. Администратор должен указать
                период ведомости и заполнить учебный план группы на нужный семестр.
            </p>
        <?php else: ?>
            <?php
            render_gradebook_sheet_header(
                (string) $group['number'],
                $year,
                $semester,
                (string) ($group['specialty_name'] ?? ''),
                (string) ($group['curator_name'] ?? ''),
                $summary
            );
            ?>

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
                            $studentId = (int) $student['id'];
                            $itemId = (int) $subject['curriculum_item_id'];
                            $value = $grades[$studentId][$itemId] ?? null;
                            ?>
                            <td class="gradebook-table__cell gradebook-table__grade-cell">
                                <?= $value !== null ? e((string) $value) : '—' ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="text-muted table-hint">
                Оценки подставляются автоматически из итогов электронного журнала
                (<?= $gradingConfig['system'] === 'brs' ? 'БРС' : 'средний балл' ?>)
                и не редактируются в ведомости.
            </p>

            <?php render_gradebook_student_lists($studentLists); ?>
        <?php endif; ?>
    </section>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
