<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/grading.php';
require_once __DIR__ . '/../includes/gradebook_composites.php';
require_once __DIR__ . '/../includes/gradebook_composite_ui.php';
require_once __DIR__ . '/../includes/individual_schedule.php';

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён. Требуются права завуча или администратора.');
}

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$group = $groupId > 0 ? get_group_by_id($groupId) : null;

if ($group === null) {
    flash_set('error', 'Группа не найдена.');
    header('Location: grades.php');
    exit;
}

$students = get_students_by_group($groupId);
$period = get_active_gradebook_period();
$year = $period['academic_year'];
$semester = $period['semester'];
$subjects = get_group_curriculum_subjects($groupId, $year, $semester);
$grades = get_gradebook_grades_from_journal($groupId, $year, $semester);
$igoIds = get_group_individual_schedule_ids($groupId, $year, $semester);
$canToggleIgo = can_manage_individual_schedule();
$summary = build_gradebook_summary($students, $subjects, $grades, $igoIds);
$studentLists = build_gradebook_student_lists($students, $subjects, $grades, $igoIds);
$compositeMap = build_gradebook_composite_fill_map(
    $students,
    $subjects,
    $year,
    $semester,
    'journal'
);
$gradingConfig = get_grading_config();
$periodHint = gradebook_period_mismatch_hint($period);

$pageTitle = 'Ведомость группы ' . $group['number'] . ' — Панель завуча';
$showHeader = true;
$basePath = '../';
$currentDeputyTab = 'grades';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Ведомость группы <?= e($group['number']) ?></h1>
                <p class="text-muted">
                    <?= e($group['specialty_name']) ?> (<?= e($group['specialty_code']) ?>)
                    · учебный год <?= e($year) ?>
                    · <?= e(semester_label($semester)) ?>
                </p>
            </div>
            <a href="grades.php" class="btn btn--ghost">← Назад</a>
        </div>
        <?php require __DIR__ . '/../includes/deputy_nav.php'; ?>
    </section>

    <section class="panel">
        <?php if ($periodHint): ?>
            <div class="alert alert--error"><?= e($periodHint) ?></div>
        <?php endif; ?>
        <?php if ($subjects === []): ?>
            <p class="text-muted">
                Для этого периода нет предметов в учебном плане группы.
                Заполните учебный план и журнал, затем обновите страницу.
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

            <div
                class="table-wrap"
                data-igo-root
                data-igo-url="<?= e(app_base_path()) ?>deputy/individual_schedule_toggle.php"
                data-csrf="<?= e(csrf_token()) ?>"
            >
                <table class="table gradebook-table gradebook-table--readonly">
                    <thead>
                        <tr>
                            <th class="gradebook-table__student-col">Студенты</th>
                            <?php foreach ($subjects as $subject): ?>
                            <?php render_gradebook_subject_header($subject, $compositeMap); ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <?php
                        $studentId = (int) $student['id'];
                        $isIgo = isset($igoIds[$studentId]);
                        ?>
                        <tr<?= $isIgo ? ' class="gradebook-row--igo"' : '' ?>>
                            <?php render_gradebook_igo_student_cell($student, $isIgo, $canToggleIgo, $year, $semester); ?>
                            <?php foreach ($subjects as $subject): ?>
                            <?php
                            $itemId = (int) $subject['curriculum_item_id'];
                            $value = $grades[$studentId][$itemId] ?? null;
                            ?>
                            <td class="gradebook-table__cell gradebook-table__grade-cell<?= $value === null && $isIgo ? ' gradebook-grade--igo' : '' ?>">
                                <?= $value !== null ? e((string) $value) : e(gradebook_empty_cell_label($isIgo)) ?>
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
                и не редактируются.
                У составных дисциплин (БВС, МДК) в шапке — заполненность разделов и ПА; нажмите на столбец, чтобы открыть расшифровку.
            </p>
            <?php render_gradebook_composite_modals($compositeMap); ?>

            <?php render_gradebook_student_lists($studentLists); ?>
        <?php endif; ?>

        <div class="form__actions">
            <a href="grades.php" class="btn btn--ghost">← Назад к списку групп</a>
        </div>
    </section>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
