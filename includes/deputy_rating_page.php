<?php

declare(strict_types=1);

/** Общая страница рейтинга завуча. Ожидает $ratingSource: journal | manual_brs */

$ratingSource = ($ratingSource ?? 'journal') === 'manual_brs' ? 'manual_brs' : 'journal';
$ratingSelfUrl = $ratingSelfUrl ?? ($ratingSource === 'manual_brs' ? 'manual_brs_rating.php' : 'rating.php');
$ratingPanelLead = $ratingPanelLead ?? 'Рейтинг студентов по успеваемости';
$ratingSourceLabel = $ratingSourceLabel
    ?? ($ratingSource === 'manual_brs'
        ? 'Средний балл считается по итоговым оценкам из модуля «Ручное БРС».'
        : 'Средний балл считается по итоговым оценкам из электронного журнала.');
$ratingEmptyText = $ratingEmptyText
    ?? 'Нет студентов с выставленными итоговыми оценками за выбранный период.';

$period = get_active_gradebook_period();
$year = $period['academic_year'];
$semester = $period['semester'];

$scope = (string) ($_GET['scope'] ?? 'college');
if (!in_array($scope, ['college', 'specialty', 'group'], true)) {
    $scope = 'college';
}

$scopeId = (int) ($_GET['id'] ?? 0);
$rating = build_student_performance_rating($year, $semester, $ratingSource);

if ($scope === 'specialty') {
    $validIds = array_map(static fn (array $row): int => (int) $row['id'], $rating['specialties']);
    if ($scopeId <= 0 || !in_array($scopeId, $validIds, true)) {
        $scopeId = $rating['specialties'] !== [] ? (int) $rating['specialties'][0]['id'] : 0;
    }
} elseif ($scope === 'group') {
    $validIds = array_map(static fn (array $row): int => (int) $row['id'], $rating['groups']);
    if ($scopeId <= 0 || !in_array($scopeId, $validIds, true)) {
        $scopeId = $rating['groups'] !== [] ? (int) $rating['groups'][0]['id'] : 0;
    }
} else {
    $scopeId = 0;
}

$items = filter_student_rating_items($rating['items'], $scope, $scopeId);

$scopeTitle = 'Общий рейтинг по колледжу';
if ($scope === 'specialty') {
    $selectedSpecialty = null;
    foreach ($rating['specialties'] as $specialty) {
        if ((int) $specialty['id'] === $scopeId) {
            $selectedSpecialty = $specialty;
            break;
        }
    }
    $scopeTitle = $selectedSpecialty
        ? 'Рейтинг по специальности «' . $selectedSpecialty['name'] . '»'
        : 'Рейтинг по специальностям';
} elseif ($scope === 'group') {
    $selectedGroup = null;
    foreach ($rating['groups'] as $group) {
        if ((int) $group['id'] === $scopeId) {
            $selectedGroup = $group;
            break;
        }
    }
    $scopeTitle = $selectedGroup
        ? 'Рейтинг группы ' . $selectedGroup['number']
        : 'Рейтинг по группам';
}

$showHeader = true;
$basePath = '../';
require __DIR__ . '/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель завуча</h1>
                <p class="text-muted"><?= e($ratingPanelLead) ?></p>
            </div>
        </div>
        <?php require __DIR__ . '/deputy_nav.php'; ?>
    </section>

    <section class="panel">
        <nav class="admin-tabs rating-scope-tabs">
            <a
                href="<?= e($ratingSelfUrl) ?>?scope=college"
                class="admin-tabs__item<?= $scope === 'college' ? ' admin-tabs__item--active' : '' ?>"
            >По колледжу</a>
            <a
                href="<?= e($ratingSelfUrl) ?>?scope=specialty"
                class="admin-tabs__item<?= $scope === 'specialty' ? ' admin-tabs__item--active' : '' ?>"
            >По специальностям</a>
            <a
                href="<?= e($ratingSelfUrl) ?>?scope=group"
                class="admin-tabs__item<?= $scope === 'group' ? ' admin-tabs__item--active' : '' ?>"
            >По группам</a>
        </nav>

        <p class="text-muted">
            Период: учебный год <?= e($year) ?> · <?= e(semester_label($semester)) ?>.
            <?= e($ratingSourceLabel) ?>
        </p>

        <?php if ($scope === 'specialty'): ?>
            <?php if ($rating['specialties'] === []): ?>
                <p class="text-muted">Специальности не найдены.</p>
            <?php else: ?>
                <form method="get" class="form form--filter" action="<?= e($ratingSelfUrl) ?>">
                    <input type="hidden" name="scope" value="specialty">
                    <div class="form__group">
                        <label for="rating_specialty">Специальность</label>
                        <select id="rating_specialty" name="id" onchange="this.form.submit()">
                            <?php foreach ($rating['specialties'] as $specialty): ?>
                            <option value="<?= (int) $specialty['id'] ?>"<?= $scopeId === (int) $specialty['id'] ? ' selected' : '' ?>>
                                <?= e($specialty['name']) ?>
                                <?php if ($specialty['code'] !== ''): ?>
                                    (<?= e($specialty['code']) ?>)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            <?php endif; ?>
        <?php elseif ($scope === 'group'): ?>
            <?php if ($rating['groups'] === []): ?>
                <p class="text-muted">Группы не найдены.</p>
            <?php else: ?>
                <form method="get" class="form form--filter" action="<?= e($ratingSelfUrl) ?>">
                    <input type="hidden" name="scope" value="group">
                    <div class="form__group">
                        <label for="rating_group">Группа</label>
                        <select id="rating_group" name="id" onchange="this.form.submit()">
                            <?php foreach ($rating['groups'] as $group): ?>
                            <option value="<?= (int) $group['id'] ?>"<?= $scopeId === (int) $group['id'] ? ' selected' : '' ?>>
                                <?= e($group['number']) ?>
                                <?php if ($group['specialty_name'] !== ''): ?>
                                    — <?= e($group['specialty_name']) ?>
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <div class="panel__header panel__header--compact">
            <h2><?= e($scopeTitle) ?></h2>
            <p class="text-muted">В рейтинге: <?= count($items) ?></p>
        </div>

        <?php if ($items === []): ?>
            <p class="text-muted"><?= e($ratingEmptyText) ?></p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table rating-table">
                    <thead>
                        <tr>
                            <th class="rating-table__place">Место</th>
                            <th>Студент</th>
                            <?php if ($scope !== 'group'): ?>
                            <th>Группа</th>
                            <?php endif; ?>
                            <?php if ($scope === 'college'): ?>
                            <th>Специальность</th>
                            <?php endif; ?>
                            <th class="rating-table__num">Средний балл</th>
                            <th class="rating-table__num">Оценок</th>
                            <th>Категория</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $row): ?>
                        <?php
                        $place = (int) $row['place'];
                        $placeClass = '';
                        if ($place === 1) {
                            $placeClass = ' rating-table__place--1';
                        } elseif ($place === 2) {
                            $placeClass = ' rating-table__place--2';
                        } elseif ($place === 3) {
                            $placeClass = ' rating-table__place--3';
                        }
                        $category = (string) $row['category'];
                        ?>
                        <tr>
                            <td class="rating-table__place<?= $placeClass ?>"><?= $place ?></td>
                            <td><?= e(person_last_first_name((string) $row['full_name'])) ?></td>
                            <?php if ($scope !== 'group'): ?>
                            <td><?= e((string) $row['group_number']) ?></td>
                            <?php endif; ?>
                            <?php if ($scope === 'college'): ?>
                            <td><?= e((string) $row['specialty_name']) ?></td>
                            <?php endif; ?>
                            <td class="rating-table__num rating-table__average">
                                <?= e(rtrim(rtrim(number_format((float) $row['average'], 2, '.', ''), '0'), '.') ?: '0') ?>
                            </td>
                            <td class="rating-table__num">
                                <?= (int) $row['grades_count'] ?> / <?= (int) $row['subjects_count'] ?>
                            </td>
                            <td>
                                <span class="badge badge--rating-<?= e($category) ?>">
                                    <?= e(student_rating_category_label($category)) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted table-hint">
                Сначала идут студенты без оценки «2» (по среднему баллу), затем студенты с «2» — даже при более высоком среднем они ниже.
                При одинаковом среднем балле в своей группе места совпадают; следующее место идёт подряд (1, 2, 3…).
                Категория: отличник — все «5»; хорошист — не ниже «4»; удовлетворительно — не ниже «3»; есть «2» — есть оценка «2».
            </p>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
