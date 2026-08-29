<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/profile.php';
require_once __DIR__ . '/../includes/gradebook.php';

require_teacher_panel();

$period = get_active_gradebook_period();
$assignedSubjects = get_teacher_assigned_subjects((int) current_user()['id'], $period['academic_year']);

$pageTitle = 'Мои предметы — Панель преподавателя';
$showHeader = true;
$basePath = '../';
$currentTeacherTab = 'subjects';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель преподавателя</h1>
                <p class="text-muted">Предметы, закреплённые за вами в учебном плане</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/teacher_nav.php'; ?>
    </section>

    <section class="panel">
        <p class="text-muted">
            Учебный год <?= e($period['academic_year']) ?> · <?= e(semester_label($period['semester'])) ?>.
            КТП по этим предметам подгружается в электронный журнал для всех преподавателей.
        </p>

        <?php if ($assignedSubjects === []): ?>
            <p class="text-muted">Нет закреплённых предметов в текущем учебном году.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Группа</th>
                            <th>Предмет</th>
                            <th>Семестр</th>
                            <th>Тем в КТП</th>
                            <th class="table__actions-col">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignedSubjects as $subject): ?>
                        <tr>
                            <td><?= e($subject['group_number']) ?></td>
                            <td><?= e($subject['subject_name']) ?></td>
                            <td><?= e(semester_label($subject['semester'])) ?></td>
                            <td><?= (int) $subject['ktp_count'] ?></td>
                            <td class="table__actions">
                                <a
                                    href="ktp_constructor.php?item_id=<?= (int) $subject['curriculum_item_id'] ?>&mode=manual"
                                    class="btn btn--primary btn--sm"
                                >КТП</a>
                                <a
                                    href="journal.php?group_id=<?= (int) $subject['group_id'] ?>&item_id=<?= (int) $subject['curriculum_item_id'] ?>"
                                    class="btn btn--ghost btn--sm"
                                >Журнал</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
