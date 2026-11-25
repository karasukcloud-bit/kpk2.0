<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/attendance.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/organization.php';

require_educator_panel();

$activePeriod = get_active_gradebook_period();
$year = normalize_academic_year((string) ($_GET['year'] ?? '')) ?? $activePeriod['academic_year'];
$viewPeriod = (string) ($_GET['period'] ?? 'year');
if (!in_array($viewPeriod, ['1', '2', 'year'], true)) {
    $viewPeriod = 'year';
}

$yearOptions = get_last_academic_years($activePeriod['academic_year'], 4);
$rating = build_group_unexcused_absence_rating($year, $viewPeriod);

$maxTotal = 0;
$maxPerStudentTotal = 0.0;
foreach ($rating as $row) {
    $maxTotal = max($maxTotal, (int) $row['total']);
    $maxPerStudentTotal = max($maxPerStudentTotal, (float) $row['per_student_total']);
}

$periodCaption = $viewPeriod === 'year'
    ? 'весь учебный год'
    : semester_label($viewPeriod);

$query = static function (array $overrides = []) use ($year, $viewPeriod): string {
    return http_build_query(array_merge(['year' => $year, 'period' => $viewPeriod], $overrides));
};

$pageTitle = 'Рейтинг групп по пропускам — Панель воспитателя';
$currentEducatorTab = 'absence_rating';
$showHeader = true;
$basePath = '../';
require __DIR__ . '/../includes/header.php';

/**
 * @param list<array<string,mixed>> $rows
 * @param callable(array):float|int $totalFn
 * @param callable(array):float|int $unexcusedFn
 */
$renderRankChart = static function (
    array $rows,
    callable $totalFn,
    callable $unexcusedFn,
    float $maxValue,
    string $format,
    string $emptyText
): void {
    if ($rows === []) {
        echo '<p class="text-muted">' . e($emptyText) . '</p>';
        return;
    }
    ?>
    <div class="absence-rank-chart" role="img" aria-label="Всего пропусков и неуважительные по группам">
        <?php foreach ($rows as $row): ?>
            <?php
            $total = $totalFn($row);
            $unexcused = $unexcusedFn($row);
            if ((float) $unexcused > (float) $total) {
                $unexcused = $total;
            }
            $totalWidth = $maxValue > 0 && (float) $total > 0
                ? max(8, round((float) $total / $maxValue * 100, 1))
                : 0;
            $unexcusedShare = (float) $total > 0
                ? round((float) $unexcused / (float) $total * 100, 1)
                : 0;
            $fmt = static function ($value) use ($format): string {
                return $format === 'int'
                    ? (string) (int) $value
                    : number_format((float) $value, 1, ',', '');
            };
            $totalLabel = $fmt($total);
            $unexcusedLabel = $fmt($unexcused);
            ?>
            <div class="absence-rank-chart__row">
                <div class="absence-rank-chart__label"><?= e((string) $row['group_number']) ?></div>
                <div class="absence-rank-chart__track">
                    <?php if ($totalWidth > 0): ?>
                        <div
                            class="absence-rank-chart__bar absence-rank-chart__bar--total"
                            style="width: <?= e((string) $totalWidth) ?>%"
                            title="Всего: <?= e($totalLabel) ?>; неуважительных: <?= e($unexcusedLabel) ?>"
                        >
                            <?php if ($unexcusedShare > 0): ?>
                                <div
                                    class="absence-rank-chart__bar absence-rank-chart__bar--unexcused"
                                    style="width: <?= e((string) $unexcusedShare) ?>%"
                                ></div>
                            <?php endif; ?>
                            <span class="absence-rank-chart__value">
                                <span class="absence-rank-chart__value-unexcused"><?= e($unexcusedLabel) ?></span>
                                <span class="absence-rank-chart__value-sep">/</span>
                                <span class="absence-rank-chart__value-total"><?= e($totalLabel) ?></span>
                            </span>
                        </div>
                    <?php else: ?>
                        <span class="absence-rank-chart__zero">0</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
};
?>

<div class="dashboard dashboard--wide educator-attendance-page">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель воспитателя</h1>
                <p class="text-muted">Рейтинг групп по пропускам</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/educator_nav.php'; ?>
    </section>

    <section class="panel">
        <h2>Рейтинг групп по пропускам</h2>
        <p class="text-muted">
            Учебный год <?= e($year) ?> · <?= e($periodCaption) ?>.
            Сортировка: больше неуважительных — выше в списке.
        </p>

        <div class="summary-report-filters">
            <div class="summary-report-filters__group">
                <span class="summary-report-filters__label">Учебный год</span>
                <div class="summary-report-filters__tabs">
                    <?php foreach ($yearOptions as $optYear): ?>
                        <a href="absence_rating.php?<?= e($query(['year' => $optYear])) ?>"
                           class="summary-report-filters__tab<?= $optYear === $year ? ' is-active' : '' ?>">
                            <?= e($optYear) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="summary-report-filters__group">
                <span class="summary-report-filters__label">Период</span>
                <div class="summary-report-filters__tabs">
                    <a href="absence_rating.php?<?= e($query(['period' => '1'])) ?>"
                       class="summary-report-filters__tab<?= $viewPeriod === '1' ? ' is-active' : '' ?>">
                        1 семестр
                    </a>
                    <a href="absence_rating.php?<?= e($query(['period' => '2'])) ?>"
                       class="summary-report-filters__tab<?= $viewPeriod === '2' ? ' is-active' : '' ?>">
                        2 семестр
                    </a>
                    <a href="absence_rating.php?<?= e($query(['period' => 'year'])) ?>"
                       class="summary-report-filters__tab<?= $viewPeriod === 'year' ? ' is-active' : '' ?>">
                        Весь год
                    </a>
                </div>
            </div>
        </div>

        <div class="absence-rank-legend" aria-hidden="true">
            <span class="absence-rank-legend__item">
                <span class="absence-rank-legend__swatch absence-rank-legend__swatch--total"></span>
                Всего пропусков
            </span>
            <span class="absence-rank-legend__item">
                <span class="absence-rank-legend__swatch absence-rank-legend__swatch--unexcused"></span>
                Неуважительные
            </span>
            <span class="absence-rank-legend__hint">в подписи: неуважительные / всего</span>
        </div>
    </section>

    <section class="panel">
        <h2>Всего пропусков</h2>
        <p class="text-muted">
            Сумма пропусков (часов) по группе: бирюзовый — всего, красный — неуважительные.
        </p>
        <?php
        $byTotal = $rating;
        usort(
            $byTotal,
            static fn (array $a, array $b): int => $b['unexcused'] <=> $a['unexcused']
                ?: $b['total'] <=> $a['total']
                ?: strcmp($a['group_number'], $b['group_number'])
        );
        $renderRankChart(
            $byTotal,
            static fn (array $row) => (int) $row['total'],
            static fn (array $row) => (int) $row['unexcused'],
            (float) $maxTotal,
            'int',
            'Нет данных о пропусках за выбранный период.'
        );
        ?>
    </section>

    <section class="panel">
        <h2>Пропусков на студента</h2>
        <p class="text-muted">
            Среднее число пропусков на одного студента группы (по текущему составу):
            бирюзовый — всего, красный — неуважительные.
        </p>
        <?php
        $byPerStudent = $rating;
        usort(
            $byPerStudent,
            static fn (array $a, array $b): int => $b['per_student_unexcused'] <=> $a['per_student_unexcused']
                ?: $b['per_student_total'] <=> $a['per_student_total']
                ?: strcmp($a['group_number'], $b['group_number'])
        );
        $renderRankChart(
            $byPerStudent,
            static fn (array $row) => (float) $row['per_student_total'],
            static fn (array $row) => (float) $row['per_student_unexcused'],
            (float) $maxPerStudentTotal,
            'float',
            'Нет данных о пропусках за выбранный период.'
        );
        ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
