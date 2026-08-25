<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён. Требуются права завуча или администратора.');
}

$period = get_active_gradebook_period();
$year = $period['academic_year'];
$semester = $period['semester'];
$groups = get_all_groups();

$pageTitle = 'Электронные ведомости — Панель завуча';
$showHeader = true;
$basePath = '../';
$currentDeputyTab = 'grades';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель завуча</h1>
                <p class="text-muted">Электронные ведомости групп</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/deputy_nav.php'; ?>
    </section>

    <section class="panel">
        <h2>Выберите группу</h2>
        <p class="text-muted">
            Период ведомости: учебный год <?= e($year) ?> · <?= e(semester_label($semester)) ?>.
            Оценки подставляются из электронного журнала.
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
                            <th>Статус</th>
                            <th class="table__actions-col">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $index => $group): ?>
                        <?php
                        $groupId = (int) $group['id'];
                        $status = get_gradebook_completion_status($groupId, $year, $semester);
                        $studentCount = $status['students'];
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($group['number']) ?></td>
                            <td><?= e($group['specialty_name']) ?> (<?= e($group['specialty_code']) ?>)</td>
                            <td><?= e(($group['curator_name'] ?? '') !== '' ? $group['curator_name'] : '—') ?></td>
                            <td><?= $studentCount ?></td>
                            <td>
                                <?php if (!empty($status['empty'])): ?>
                                    <span class="badge badge--inactive">Нет данных</span>
                                <?php elseif (!empty($status['complete'])): ?>
                                    <span class="badge badge--gradebook-complete">Все оценки</span>
                                <?php else: ?>
                                    <span class="badge badge--gradebook-incomplete">Не все оценки</span>
                                    <span class="text-muted gradebook-status-count">
                                        <?= (int) $status['filled'] ?>/<?= (int) $status['expected'] ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="table__actions">
                                <a
                                    href="gradebook.php?group_id=<?= $groupId ?>"
                                    class="btn btn--primary btn--sm"
                                >Открыть ведомость</a>
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
