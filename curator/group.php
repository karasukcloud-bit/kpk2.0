<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';

require_curator_panel();

$groups = get_groups_for_curator();
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

if ($groupId === 0 && count($groups) === 1) {
    $groupId = (int) $groups[0]['id'];
}

$group = null;
$students = [];
$error = flash_get('error');
$success = flash_get('success');

if ($groupId > 0) {
    if (!can_manage_group($groupId)) {
        $error = 'Нет доступа к выбранной группе.';
        $groupId = 0;
    } else {
        $group = get_group_by_id($groupId);
        $students = get_students_by_group($groupId);
    }
}

$pageTitle = 'Список группы — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'group';
$curatorGroupId = $groupId;
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
