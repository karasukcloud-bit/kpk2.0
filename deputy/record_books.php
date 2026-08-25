<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/record_book.php';

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён. Требуются права завуча или администратора.');
}

$groups = get_all_groups();
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$group = $groupId > 0 ? get_group_by_id($groupId) : null;
$students = [];

if ($group === null) {
    $groupId = 0;
} else {
    $students = get_students_by_group($groupId);
}

$pageTitle = 'Зачётные книжки — Панель завуча';
$showHeader = true;
$basePath = '../';
$currentDeputyTab = 'record_books';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель завуча</h1>
                <p class="text-muted">Зачётные книжки студентов</p>
            </div>
            <?php if ($group !== null): ?>
            <a href="record_books.php" class="btn btn--ghost">← К группам</a>
            <?php endif; ?>
        </div>
        <?php require __DIR__ . '/../includes/deputy_nav.php'; ?>
    </section>

    <section class="panel">
        <?php if ($group === null): ?>
            <h2>Выберите группу</h2>
            <p class="text-muted">
                Откройте группу, затем карточку студента, чтобы просмотреть зачётную книжку.
                Оценки появляются после архивации ведомостей.
            </p>
            <?php if ($groups === []): ?>
                <p class="text-muted">Группы пока не добавлены.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Группа</th>
                                <th>Специальность</th>
                                <th>Куратор</th>
                                <th>Студентов</th>
                                <th class="table__actions-col">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups as $index => $row): ?>
                            <?php
                            $gid = (int) $row['id'];
                            $count = count(get_students_by_group($gid));
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($row['number']) ?></td>
                                <td><?= e($row['specialty_name']) ?> (<?= e($row['specialty_code']) ?>)</td>
                                <td><?= e(($row['curator_name'] ?? '') !== '' ? $row['curator_name'] : '—') ?></td>
                                <td><?= $count ?></td>
                                <td class="table__actions">
                                    <a href="record_books.php?group_id=<?= $gid ?>" class="btn btn--primary btn--sm">Открыть</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <h2>Группа <?= e($group['number']) ?></h2>
            <p class="text-muted">
                <?= e($group['specialty_name']) ?> (<?= e($group['specialty_code']) ?>)
                · студентов: <?= count($students) ?>
            </p>
            <?php if ($students === []): ?>
                <p class="text-muted">В группе пока нет студентов.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>ФИО</th>
                                <th class="table__actions-col">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e(person_last_first_name((string) $student['full_name'])) ?></td>
                                <td class="table__actions">
                                    <a
                                        href="record_book.php?id=<?= (int) $student['id'] ?>&group_id=<?= $groupId ?>"
                                        class="btn btn--primary btn--sm"
                                    >Зачётная книжка</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
