<?php

declare(strict_types=1);

$curriculumPanel = $curriculumPanel ?? 'admin';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../curriculum.php';

require_curriculum_manager();

$academicYear = normalize_academic_year($_GET['year'] ?? get_default_academic_year())
    ?? get_default_academic_year();
$yearOptions = get_academic_year_options($academicYear);
$groups = get_groups_with_curriculum_stats($academicYear);
$error = flash_get('error');
$success = flash_get('success');

$pageTitle = 'Учебный план — ' . ($curriculumPanel === 'admin' ? 'Администрирование' : 'Панель завуча');
$showHeader = true;
$basePath = '../';

if ($curriculumPanel === 'admin') {
    $currentAdminTab = 'curriculum';
} else {
    $currentDeputyTab = 'curriculum';
}

require __DIR__ . '/../header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1><?= $curriculumPanel === 'admin' ? 'Панель администратора' : 'Панель завуча' ?></h1>
                <p class="text-muted">Учебный план групп на учебный год</p>
            </div>
        </div>

        <?php
        if ($curriculumPanel === 'admin') {
            require __DIR__ . '/../admin_nav.php';
        } else {
            require __DIR__ . '/../deputy_nav.php';
        }
        ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <form method="get" class="form form--inline form--filter">
            <div class="form__row form__row--filter">
                <div class="form__group">
                    <label for="year">Учебный год</label>
                    <select id="year" name="year" onchange="this.form.submit()">
                        <?php foreach ($yearOptions as $year): ?>
                        <option value="<?= e($year) ?>"<?= $year === $academicYear ? ' selected' : '' ?>>
                            <?= e($year) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form__group form__group--align-end">
                    <label class="form__label">&nbsp;</label>
                    <a href="?year=<?= e(get_default_academic_year()) ?>" class="btn btn--ghost">Текущий год</a>
                </div>
            </div>
        </form>

        <?php if (empty($groups)): ?>
            <p class="text-muted">Сначала добавьте группы во вкладке «Информация».</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Группа</th>
                            <th>Специальность</th>
                            <th>Предметов в плане</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $group): ?>
                        <tr class="table__row--clickable"
                            onclick="location.href='curriculum_edit.php?group_id=<?= (int) $group['id'] ?>&year=<?= e(urlencode($academicYear)) ?>'">
                            <td><strong><?= e($group['number']) ?></strong></td>
                            <td><?= e($group['specialty_name']) ?> <code><?= e($group['specialty_code']) ?></code></td>
                            <td><?= (int) $group['subjects_count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted table-hint">Нажмите на группу, чтобы открыть список предметов на учебный год.</p>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
