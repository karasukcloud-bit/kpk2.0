<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/grading.php';

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
$summary = build_gradebook_summary($students, $subjects, $grades);
$studentLists = build_gradebook_student_lists($students, $subjects, $grades);
$gradingConfig = get_grading_config();

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
        <?php if ($subjects === []): ?>
            <p class="text-muted">
                Для этого периода нет предметов в учебном плане группы.
                Заполните учебный план и журнал, затем обновите страницу.
            </p>
        <?php else: ?>
            <p>
                Студентов: <?= count($students) ?>
                · предметов в плане: <?= count($subjects) ?>
            </p>

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
                и не редактируются.
            </p>

            <div class="gradebook-lists">
                <h2>Списки студентов</h2>
                <div class="gradebook-lists__grid">
                    <div class="gradebook-lists__col">
                        <h3>Оценка «2»</h3>
                        <?php if ($studentLists['with_twos'] === []): ?>
                            <p class="text-muted">Нет студентов</p>
                        <?php else: ?>
                            <ul class="gradebook-lists__items">
                                <?php foreach ($studentLists['with_twos'] as $item): ?>
                                <li class="gradebook-lists__item">
                                    <strong><?= e(person_last_first_name((string) $item['full_name'])) ?></strong>
                                    <span class="gradebook-lists__subjects text-muted"><?= e(implode(', ', $item['subjects'])) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="gradebook-lists__col">
                        <h3>Только оценки 4–5</h3>
                        <?php if ($studentLists['only_good'] === []): ?>
                            <p class="text-muted">Нет студентов</p>
                        <?php else: ?>
                            <ul class="gradebook-lists__items">
                                <?php foreach ($studentLists['only_good'] as $item): ?>
                                <li class="gradebook-lists__item">
                                    <strong><?= e(person_last_first_name((string) $item['full_name'])) ?></strong>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="gradebook-lists__col">
                        <h3>Отличники (все «5»)</h3>
                        <?php if ($studentLists['excellent'] === []): ?>
                            <p class="text-muted">Нет студентов</p>
                        <?php else: ?>
                            <ul class="gradebook-lists__items">
                                <?php foreach ($studentLists['excellent'] as $item): ?>
                                <li class="gradebook-lists__item">
                                    <strong><?= e(person_last_first_name((string) $item['full_name'])) ?></strong>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="form__actions">
            <a href="grades.php" class="btn btn--ghost">← Назад к списку групп</a>
        </div>
    </section>

    <?php if ($subjects !== []): ?>
    <section class="panel panel--info">
        <h2>Сводная информация</h2>
        <dl class="profile-list">
            <dt>Оценённых студентов</dt>
            <dd><?= (int) $summary['assessed_students'] ?> из <?= (int) $summary['total_students'] ?></dd>
            <dt>Успеваемость абсолютная</dt>
            <dd><?= e((string) $summary['absolute_percent']) ?>%</dd>
            <dt>Успеваемость качественная</dt>
            <dd><?= e((string) $summary['quality_percent']) ?>%</dd>
        </dl>
        <p class="text-muted">
            Абсолютная успеваемость: доля студентов без оценок «2».
            Качественная успеваемость: доля студентов, у которых все выставленные оценки не ниже «4».
        </p>
    </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
