<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/student_activities.php';

require_educator_panel();

$analytics = build_college_activities_analytics();
$groups = get_all_groups();
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

$group = null;
$groupReport = null;

if ($groupId > 0) {
    $group = get_group_by_id($groupId);
    if ($group === null) {
        $groupId = 0;
    } else {
        $students = get_students_by_group($groupId);
        $groupReport = $students !== [] ? build_group_activities_report($students) : [
            'with_activities' => 0,
            'without_activities' => 0,
            'club_count' => 0,
            'section_count' => 0,
            'total_students' => 0,
            'pct_with' => 0.0,
            'pct_without' => 0.0,
            'rows' => [],
        ];
    }
}

$pageTitle = 'Занятость студентов — Панель воспитателя';
$showHeader = true;
$basePath = '../';
$currentEducatorTab = 'activities';
require __DIR__ . '/../includes/header.php';

$fmtPct = static fn (float $value): string => rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.') . '%';
?>

<div class="dashboard dashboard--wide educator-activities-page">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель воспитателя</h1>
                <p class="text-muted">Занятость студентов во внеурочной деятельности</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/educator_nav.php'; ?>
    </section>

    <section class="panel">
        <h2>По колледжу</h2>
        <p class="text-muted">
            Всего студентов: <?= (int) $analytics['total_students'] ?>.
            Данные заполняют кураторы групп.
        </p>
        <div class="admin-stats-grid">
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e($fmtPct((float) $analytics['pct_with'])) ?></div>
                <div class="admin-stat-card__label">
                    Заняты
                    (<?= (int) $analytics['with_activities'] ?>)
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e($fmtPct((float) $analytics['pct_without'])) ?></div>
                <div class="admin-stat-card__label">
                    Не заняты
                    (<?= (int) $analytics['without_activities'] ?>)
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) $analytics['club_count'] ?></div>
                <div class="admin-stat-card__label">Разных кружков</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) $analytics['section_count'] ?></div>
                <div class="admin-stat-card__label">Разных секций</div>
            </div>
        </div>
    </section>

    <section class="panel">
        <h2>Занятость по группам</h2>
        <?php if ($analytics['groups'] === []): ?>
            <p class="text-muted">В системе пока нет групп.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table table--compact educator-activities-groups-table">
                    <thead>
                        <tr>
                            <th>Группа</th>
                            <th>Специальность</th>
                            <th>Студентов</th>
                            <th>Заняты</th>
                            <th>% занятости</th>
                            <th>Не заняты</th>
                            <th>% без занятости</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics['groups'] as $row): ?>
                        <tr class="<?= (int) $row['group_id'] === $groupId ? 'is-selected' : '' ?>">
                            <td>
                                <a href="activities.php?group_id=<?= (int) $row['group_id'] ?>">
                                    <?= e($row['group_number']) ?>
                                </a>
                            </td>
                            <td><?= e($row['specialty_name']) ?></td>
                            <td><?= (int) $row['total_students'] ?></td>
                            <td><?= (int) $row['with_activities'] ?></td>
                            <td><strong><?= e($fmtPct((float) $row['pct_with'])) ?></strong></td>
                            <td><?= (int) $row['without_activities'] ?></td>
                            <td><?= e($fmtPct((float) $row['pct_without'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel__header">
            <div>
                <h2>Таблица занятости по группе</h2>
                <p class="text-muted">Выберите группу, чтобы посмотреть занятость каждого студента.</p>
            </div>
        </div>

        <?php if ($groups === []): ?>
            <p class="text-muted">Групп нет.</p>
        <?php else: ?>
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

            <?php if ($group === null || $groupReport === null): ?>
                <p class="text-muted">Выберите группу в списке выше.</p>
            <?php else: ?>
                <p class="text-muted">
                    Группа <?= e($group['number']) ?>
                    · студентов: <?= (int) $groupReport['total_students'] ?>
                    · заняты: <?= e($fmtPct((float) $groupReport['pct_with'])) ?>
                    · не заняты: <?= e($fmtPct((float) $groupReport['pct_without'])) ?>
                </p>

                <div class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <div class="admin-stat-card__value"><?= e($fmtPct((float) $groupReport['pct_with'])) ?></div>
                        <div class="admin-stat-card__label">Заняты (<?= (int) $groupReport['with_activities'] ?>)</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-card__value"><?= e($fmtPct((float) $groupReport['pct_without'])) ?></div>
                        <div class="admin-stat-card__label">Не заняты (<?= (int) $groupReport['without_activities'] ?>)</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-card__value"><?= (int) $groupReport['club_count'] ?></div>
                        <div class="admin-stat-card__label">Разных кружков</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-card__value"><?= (int) $groupReport['section_count'] ?></div>
                        <div class="admin-stat-card__label">Разных секций</div>
                    </div>
                </div>

                <div class="table-wrap" style="margin-top:1rem">
                    <table class="table curator-activities-table">
                        <thead>
                            <tr>
                                <th style="width:3rem">№</th>
                                <th>Студент</th>
                                <th>Кружки и секции</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($groupReport['rows'] === []): ?>
                            <tr>
                                <td colspan="3" class="text-muted">В группе нет студентов.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($groupReport['rows'] as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= e(person_last_first_name((string) $row['full_name'])) ?></td>
                                    <td>
                                        <?php if ($row['summary'] === ''): ?>
                                            <span class="text-muted">Не указано</span>
                                        <?php else: ?>
                                            <ul class="curator-activities-list">
                                                <?php foreach ($row['activities'] as $activity): ?>
                                                    <?php $line = format_student_activity_line($activity); ?>
                                                    <?php if ($line !== ''): ?>
                                                    <li><?= e($line) ?></li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
