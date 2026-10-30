<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/attendance.php';
require_once __DIR__ . '/../includes/curator_monthly_report.php';

require_educator_panel();

$period = get_active_gradebook_period();
$academicYear = (string) ($period['academic_year'] ?? '');
$monthOptions = $academicYear !== '' ? get_academic_year_months($academicYear) : [];
$month = resolve_attendance_month($academicYear, isset($_GET['month']) ? (string) $_GET['month'] : null);
$rows = build_curator_monthly_reports_overview($month);

$submittedCount = 0;
foreach ($rows as $row) {
    if (!empty($row['has_report'])) {
        $submittedCount++;
    }
}

$matrix = build_curator_monthly_reports_matrix($academicYear, $monthOptions);

$pageTitle = 'Отчёты кураторов — Панель воспитателя';
$currentEducatorTab = 'curator_reports';
$showHeader = true;
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель воспитателя</h1>
                <p class="text-muted">Ежемесячные отчёты кураторов по группам</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/educator_nav.php'; ?>
    </section>

    <section class="panel">
        <div class="panel__header panel__header--compact">
            <div>
                <h2>Отчёты кураторов</h2>
                <p class="text-muted">
                    Учебный год <?= e($academicYear) ?> · <?= e(format_attendance_month($month)) ?>.
                    Сдано: <?= (int) $submittedCount ?> из <?= count($rows) ?>.
                </p>
            </div>
        </div>

        <form method="get" class="form form--filter">
            <div class="form__group">
                <label for="educator_report_month">Месяц</label>
                <select id="educator_report_month" name="month" onchange="this.form.submit()">
                    <?php foreach ($monthOptions as $opt): ?>
                        <option value="<?= e($opt['value']) ?>"<?= $opt['value'] === $month ? ' selected' : '' ?>>
                            <?= e($opt['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($rows === []): ?>
            <p class="text-muted">Группы не найдены.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Место</th>
                            <th>Группа</th>
                            <th>Куратор</th>
                            <th>Специальность</th>
                            <th>Баллы</th>
                            <th>Статус</th>
                            <th class="table__actions-col">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $place = 0;
                        $prevPoints = null;
                        $index = 0;
                        foreach ($rows as $row):
                            $index++;
                            if (!empty($row['has_report'])) {
                                $points = (int) $row['total_points'];
                                if ($prevPoints === null || $points !== $prevPoints) {
                                    $place = $index;
                                    $prevPoints = $points;
                                }
                            }
                        ?>
                        <tr class="<?= !empty($row['has_report']) ? 'curator-report-row--done' : 'curator-report-row--missing' ?>">
                            <td><?= !empty($row['has_report']) ? (int) $place : '—' ?></td>
                            <td><?= e((string) $row['group_number']) ?></td>
                            <td><?= e((string) ($row['curator_name'] !== '' ? $row['curator_name'] : '—')) ?></td>
                            <td><?= e((string) ($row['specialty_name'] !== '' ? $row['specialty_name'] : '—')) ?></td>
                            <td>
                                <?php if (!empty($row['has_report'])): ?>
                                    <strong><?= (int) $row['total_points'] ?></strong>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['has_report'])): ?>
                                    <span class="badge badge--success">Сдан</span>
                                <?php else: ?>
                                    <span class="badge badge--gradebook-incomplete">Не сдан</span>
                                <?php endif; ?>
                            </td>
                            <td class="table__actions-col">
                                <?php if (!empty($row['has_report'])): ?>
                                    <a
                                        class="btn btn--ghost btn--sm"
                                        href="curator_report_view.php?group_id=<?= (int) $row['group_id'] ?>&month=<?= e(urlencode($month)) ?>"
                                    >Открыть</a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel__header panel__header--compact">
            <div>
                <h2>Сводная таблица за учебный год</h2>
                <p class="text-muted">
                    Группы и баллы по месяцам · <?= e($academicYear) ?>.
                    Нажмите на балл, чтобы открыть отчёт.
                </p>
            </div>
        </div>

        <?php if ($matrix['rows'] === [] || $matrix['months'] === []): ?>
            <p class="text-muted">Нет данных для сводной таблицы.</p>
        <?php else: ?>
            <div class="table-wrap curator-reports-matrix-wrap">
                <table class="table curator-reports-matrix">
                    <thead>
                        <tr>
                            <th class="curator-reports-matrix__group">Группа</th>
                            <?php foreach ($matrix['months'] as $m): ?>
                                <th
                                    class="curator-reports-matrix__month<?= $m['value'] === $month ? ' is-current' : '' ?>"
                                    title="<?= e($m['label']) ?>"
                                ><?= e($m['short']) ?></th>
                            <?php endforeach; ?>
                            <th class="curator-reports-matrix__total">Итого</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matrix['rows'] as $mRow): ?>
                        <tr>
                            <th class="curator-reports-matrix__group" scope="row">
                                <?= e((string) $mRow['group_number']) ?>
                                <?php if (($mRow['curator_name'] ?? '') !== ''): ?>
                                    <span class="curator-reports-matrix__curator"><?= e((string) $mRow['curator_name']) ?></span>
                                <?php endif; ?>
                            </th>
                            <?php foreach ($matrix['months'] as $m): ?>
                                <?php
                                $pts = $mRow['points_by_month'][$m['value']] ?? null;
                                $isCurrent = $m['value'] === $month;
                                ?>
                                <td class="curator-reports-matrix__cell<?= $pts !== null ? ' is-done' : ' is-missing' ?><?= $isCurrent ? ' is-current' : '' ?>">
                                    <?php if ($pts !== null): ?>
                                        <a
                                            href="curator_report_view.php?group_id=<?= (int) $mRow['group_id'] ?>&month=<?= e(urlencode($m['value'])) ?>"
                                            title="Открыть отчёт"
                                        ><?= (int) $pts ?></a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="curator-reports-matrix__total">
                                <strong><?= (int) $mRow['total_points'] ?></strong>
                                <span class="text-muted">/ <?= (int) $mRow['submitted_months'] ?></span>
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
