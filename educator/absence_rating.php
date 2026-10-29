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

$maxUnexcused = 0;
$maxPerStudent = 0.0;
foreach ($rating as $row) {
    $maxUnexcused = max($maxUnexcused, (int) $row['unexcused']);
    $maxPerStudent = max($maxPerStudent, (float) $row['per_student_unexcused']);
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
 * @param callable(array):float|int $valueFn
 */
$renderRankChart = static function (
    array $rows,
    callable $valueFn,
    float $maxValue,
    string $format,
    string $emptyText
): void {
    if ($rows === []) {
        echo '<p class="text-muted">' . e($emptyText) . '</p>';
        return;
    }
    ?>
    <div class="absence-rank-chart" role="img">
        <?php foreach ($rows as $row): ?>
            <?php
            $value = $valueFn($row);
            $width = $maxValue > 0 ? max(8, round((float) $value / $maxValue * 100, 1)) : 0;
            if ((float) $value <= 0) {
                $width = 0;
            }
            $label = $format === 'int'
                ? (string) (int) $value
                : number_format((float) $value, 1, ',', '');
            ?>
            <div class="absence-rank-chart__row">
                <div class="absence-rank-chart__label"><?= e((string) $row['group_number']) ?></div>
                <div class="absence-rank-chart__track">
                    <?php if ($width > 0): ?>
                        <div class="absence-rank-chart__bar" style="width: <?= e((string) $width) ?>%">
                            <span class="absence-rank-chart__value"><?= e($label) ?></span>
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
                <p class="text-muted">Рейтинг групп по неуважительным пропускам</p>
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
    </section>

    <section class="panel">
        <h2>Всего неуважительных</h2>
        <p class="text-muted">Сумма неуважительных пропусков (часов) по группе.</p>
        <?php
        $byTotal = $rating;
        usort(
            $byTotal,
            static fn (array $a, array $b): int => $b['unexcused'] <=> $a['unexcused']
                ?: strcmp($a['group_number'], $b['group_number'])
        );
        $renderRankChart(
            $byTotal,
            static fn (array $row) => (int) $row['unexcused'],
            (float) $maxUnexcused,
            'int',
            'Нет данных о пропусках за выбранный период.'
        );
        ?>
    </section>

    <section class="panel">
        <h2>Неуважительных на студента</h2>
        <p class="text-muted">
            Среднее число неуважительных пропусков на одного студента группы
            (по текущему составу).
        </p>
        <?php
        $byPerStudent = $rating;
        usort(
            $byPerStudent,
            static fn (array $a, array $b): int => $b['per_student_unexcused'] <=> $a['per_student_unexcused']
                ?: strcmp($a['group_number'], $b['group_number'])
        );
        $renderRankChart(
            $byPerStudent,
            static fn (array $row) => (float) $row['per_student_unexcused'],
            (float) $maxPerStudent,
            'float',
            'Нет данных о пропусках за выбранный период.'
        );
        ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
