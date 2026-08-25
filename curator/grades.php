<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/grading.php';

require_curator_panel();

$groups = get_groups_for_curator();
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

if ($groupId === 0 && count($groups) === 1) {
    $groupId = (int) $groups[0]['id'];
}

$group = ($groupId > 0 && can_manage_group($groupId)) ? get_group_by_id($groupId) : null;
$students = $group ? get_students_by_group($groupId) : [];
$period = get_active_gradebook_period();
$year = $period['academic_year'];
$semester = $period['semester'];
$subjects = $group ? get_group_curriculum_subjects($groupId, $year, $semester) : [];
$grades = $group ? get_gradebook_grades_from_journal($groupId, $year, $semester) : [];
$summary = build_gradebook_summary($students, $subjects, $grades);
$studentLists = build_gradebook_student_lists($students, $subjects, $grades);
$gradingConfig = get_grading_config();

$pageTitle = 'Электронная ведомость — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'grades';
$curatorGroupId = $groupId;
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель куратора</h1>
                <p class="text-muted">Электронная ведомость группы</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/curator_nav.php'; ?>
    </section>

    <section class="panel">
        <?php if (empty($groups)): ?>
            <p class="text-muted">Вам ещё не назначена группа.</p>
        <?php else: ?>
            <?php if (count($groups) > 1): ?>
            <form method="get" class="form form--filter">
                <div class="form__row form__row--filter">
                    <div class="form__group">
                        <label for="group_id">Группа</label>
                        <select id="group_id" name="group_id" onchange="this.form.submit()">
                            <option value="">— Выберите группу —</option>
                            <?php foreach ($groups as $item): ?>
                            <option value="<?= (int) $item['id'] ?>"<?= (int) $item['id'] === $groupId ? ' selected' : '' ?>>
                                <?= e($item['number']) ?> · <?= e($item['specialty_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (empty($groups)): ?>
        <?php elseif ($group === null): ?>
            <p class="text-muted">Сначала откройте вкладку «Список группы» и выберите группу.</p>
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
            <p>
                Группа <strong><?= e($group['number']) ?></strong>
                · учебный год <?= e($year) ?>
                · <?= e(semester_label($semester)) ?>
                · студентов: <?= count($students) ?>
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
                и не редактируются в ведомости.
            </p>

            <div class="gradebook-lists">
                <h2>Списки студентов</h2>
                <div class="gradebook-lists__grid">
                    <div class="gradebook-lists__col">
                        <h3>Оценка «2»</h3>
                        <div>
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
                    </div>
                    <div class="gradebook-lists__col">
                        <h3>Только оценки 4–5</h3>
                        <div>
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
                    </div>
                    <div class="gradebook-lists__col">
                        <h3>Отличники (все «5»)</h3>
                        <div>
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
            </div>
        <?php endif; ?>
    </section>

    <?php if ($group !== null && !empty($subjects)): ?>
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
