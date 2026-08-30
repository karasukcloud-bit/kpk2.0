<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';

require_curator_panel();

$ctx = resolve_curator_group_context(isset($_GET['group_id']) ? (int) $_GET['group_id'] : null);
$groups = $ctx['groups'];
$groupId = $ctx['group_id'];
$group = $ctx['group'];
$students = $group ? get_students_by_group($groupId) : [];
$error = flash_get('error') ?: $ctx['error'];
$success = flash_get('success');

$pageTitle = 'Список группы — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'group';
$curatorGroupId = $groupId;
$curatorGroups = $groups;
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель куратора</h1>
                <p class="text-muted">Список студентов закреплённой группы</p>
            </div>
            <?php if ($group): ?>
            <a href="student_create.php?group_id=<?= $groupId ?>" class="btn btn--primary">+ Добавить студента</a>
            <?php endif; ?>
        </div>

        <?php require __DIR__ . '/../includes/curator_nav.php'; ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
        <section class="panel">
            <p class="text-muted">
                Вам ещё не назначена группа. Обратитесь к администратору:
                в разделе «Информация» у группы нужно указать куратора.
            </p>
        </section>
    <?php else: ?>
        <section class="panel">
            <?php if ($group === null): ?>
                <p class="text-muted">Выберите группу, чтобы увидеть список студентов.</p>
            <?php else: ?>
                <p class="text-muted">
                    Группа <strong><?= e($group['number']) ?></strong>
                    · <?= e($group['specialty_name']) ?> (<?= e($group['specialty_code']) ?>)
                    · студентов: <?= count($students) ?>
                </p>

                <?php if (empty($students)): ?>
                    <p class="text-muted">В группе пока нет студентов.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>№</th>
                                    <th>ФИО</th>
                                    <th>Телефон</th>
                                    <th>Дата рождения</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $index => $student): ?>
                                <tr class="table__row--clickable"
                                    onclick="location.href='student_edit.php?id=<?= (int) $student['id'] ?>'">
                                    <td><?= $index + 1 ?></td>
                                    <td><?= e($student['full_name']) ?></td>
                                    <td><?= e($student['phone'] ?: '—') ?></td>
                                    <td><?= e(format_student_birth_date($student['birth_date'] ?? null)) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted table-hint">Нажмите на строку, чтобы открыть карточку студента.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
