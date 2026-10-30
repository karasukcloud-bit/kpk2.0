<?php

declare(strict_types=1);

/** Общая страница сводной ведомости. Ожидает переменные из deputy/summary*.php. */

$summarySource = ($summarySource ?? 'journal') === 'manual_brs' ? 'manual_brs' : 'journal';
$summaryGradebookHref = (string) ($summaryGradebookHref ?? 'gradebook.php');
$summaryPanelLead = (string) ($summaryPanelLead ?? 'Сводная ведомость по всем группам');
$summaryHeading = (string) ($summaryHeading ?? 'Сводная ведомость');
$summarySourceLabel = (string) ($summarySourceLabel ?? 'Оценки из электронного журнала.');
$pageTitle = (string) ($pageTitle ?? 'Сводная ведомость — Панель завуча');
$currentDeputyTab = (string) ($currentDeputyTab ?? 'summary');
$summarySelfUrl = (string) ($summarySelfUrl ?? 'summary.php');

require_once __DIR__ . '/attendance.php';

$activePeriod = get_active_gradebook_period();
$year = normalize_academic_year((string) ($_GET['year'] ?? '')) ?? $activePeriod['academic_year'];
$viewSem = (string) ($_GET['sem'] ?? '');
if (!in_array($viewSem, ['1', '2', 'year', 'compare'], true)) {
    $viewSem = normalize_gradebook_semester((string) $activePeriod['semester']);
}
$isCompare = $viewSem === 'compare';
$isYear = $viewSem === 'year';
$semester = ($isCompare || $isYear) ? '1' : normalize_gradebook_semester($viewSem);
$reportSemester = $isYear ? 'year' : $semester;
$yearsPeriodMode = $isYear ? 'year' : ($isCompare ? '' : $semester);

$manualBrsAvailable = is_manual_brs_gradebook_active();
$yearOptions = get_last_academic_years($activePeriod['academic_year'], 4);
$groups = get_all_groups();

$summaryQuery = static function (array $overrides = []) use ($year, $viewSem, $summarySource): string {
    $params = array_merge([
        'year' => $year,
        'sem' => $viewSem,
        'source' => $summarySource,
    ], $overrides);
    return http_build_query($params);
};

$report = null;
$report1 = null;
$report2 = null;

if ($groups !== []) {
    if ($isCompare) {
        $report1 = build_college_gradebook_report($year, '1', $summarySource, $groups);
        $report2 = build_college_gradebook_report($year, '2', $summarySource, $groups);
    } else {
        $report = build_college_gradebook_report($year, $reportSemester, $summarySource, $groups);
    }
}

$yearsComparison = $groups !== [] && !$isCompare
    ? build_gradebook_three_years_comparison($year, $summarySource, $yearsPeriodMode)
    : null;
$yearsCompareLabel = $yearsComparison
    ? implode(', ', $yearsComparison['year_labels'])
    : '';

if ($isCompare) {
    $periodCaption = $year . ' · сравнение 1 и 2 семестра';
} elseif ($isYear) {
    $periodCaption = $year . ' · весь учебный год (1 и 2 семестр)';
} else {
    $periodCaption = $year . ' · ' . semester_label($semester);
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
                <p class="text-muted"><?= e($summaryPanelLead) ?></p>
            </div>
        </div>
        <?php require __DIR__ . '/deputy_nav.php'; ?>
    </section>

    <section class="panel">
        <h2><?= e($summaryHeading) ?></h2>
        <p class="text-muted">
            <?= e($periodCaption) ?>.
            <?= e($summarySourceLabel) ?>
        </p>

        <div class="summary-report-filters">
            <?php if ($manualBrsAvailable): ?>
            <?php
            $sourceFilterCurrent = $summarySource;
            $sourceFilterUrl = static function (array $overrides) use ($summaryQuery): string {
                return 'summary.php?' . $summaryQuery($overrides);
            };
            require __DIR__ . '/deputy_source_filter.php';
            ?>
            <?php endif; ?>

            <div class="summary-report-filters__group">
                <span class="summary-report-filters__label">Учебный год</span>
                <div class="summary-report-filters__tabs">
                    <?php foreach ($yearOptions as $optYear): ?>
                        <a href="<?= e($summarySelfUrl) ?>?<?= e($summaryQuery(['year' => $optYear])) ?>"
                           class="summary-report-filters__tab<?= $optYear === $year ? ' is-active' : '' ?>">
                            <?= e($optYear) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="summary-report-filters__group">
                <span class="summary-report-filters__label">Период</span>
                <div class="summary-report-filters__tabs">
                    <a href="<?= e($summarySelfUrl) ?>?<?= e($summaryQuery(['sem' => '1'])) ?>"
                       class="summary-report-filters__tab<?= $viewSem === '1' ? ' is-active' : '' ?>">
                        1 семестр
                    </a>
                    <a href="<?= e($summarySelfUrl) ?>?<?= e($summaryQuery(['sem' => '2'])) ?>"
                       class="summary-report-filters__tab<?= $viewSem === '2' ? ' is-active' : '' ?>">
                        2 семестр
                    </a>
                    <a href="<?= e($summarySelfUrl) ?>?<?= e($summaryQuery(['sem' => 'year'])) ?>"
                       class="summary-report-filters__tab<?= $isYear ? ' is-active' : '' ?>">
                        Весь год
                    </a>
                    <a href="<?= e($summarySelfUrl) ?>?<?= e($summaryQuery(['sem' => 'compare'])) ?>"
                       class="summary-report-filters__tab<?= $isCompare ? ' is-active' : '' ?>">
                        Сравнение 1 и 2
                    </a>
                </div>
            </div>
        </div>

        <?php if ($groups === []): ?>
            <p class="text-muted">Группы пока не добавлены.</p>
        <?php elseif ($isCompare && $report1 && $report2): ?>
            <?php
            $m1 = $report1['metrics'];
            $m2 = $report2['metrics'];
            $t1 = $report1['totals'];
            $t2 = $report2['totals'];
            ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Группа</th>
                            <th>Специальность</th>
                            <th>Студентов</th>
                            <th>Абс. 1 сем.</th>
                            <th>Абс. 2 сем.</th>
                            <th>Δ абс.</th>
                            <th>Кач. 1 сем.</th>
                            <th>Кач. 2 сем.</th>
                            <th>Δ кач.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report1['rows'] as $index => $row1): ?>
                        <?php
                        $group = $row1['group'];
                        $row2 = $report2['rows'][$index] ?? null;
                        $s1 = $row1['overview']['summary'];
                        $s2 = $row2['overview']['summary'] ?? ['absolute_percent' => 0, 'quality_percent' => 0];
                        $abs1 = (float) $s1['absolute_percent'];
                        $abs2 = (float) $s2['absolute_percent'];
                        $qual1 = (float) $s1['quality_percent'];
                        $qual2 = (float) $s2['quality_percent'];
                        $dAbs = round($abs2 - $abs1, 1);
                        $dQual = round($qual2 - $qual1, 1);
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <a href="<?= e($summaryGradebookHref) ?>?group_id=<?= (int) $group['id'] ?>">
                                    <?= e($group['number']) ?>
                                </a>
                            </td>
                            <td><?= e($group['specialty_name']) ?></td>
                            <td><?= (int) $row1['overview']['students'] ?></td>
                            <td><?= e((string) $abs1) ?>%</td>
                            <td><?= e((string) $abs2) ?>%</td>
                            <td class="<?= $dAbs > 0 ? 'text-success' : ($dAbs < 0 ? 'text-danger' : '') ?>">
                                <?= $dAbs > 0 ? '+' : '' ?><?= e((string) $dAbs) ?>
                            </td>
                            <td><?= e((string) $qual1) ?>%</td>
                            <td><?= e((string) $qual2) ?>%</td>
                            <td class="<?= $dQual > 0 ? 'text-success' : ($dQual < 0 ? 'text-danger' : '') ?>">
                                <?= $dQual > 0 ? '+' : '' ?><?= e((string) $dQual) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($report): ?>
            <?php
            $rows = $report['rows'];
            $totals = $report['totals'];
            $metrics = $report['metrics'];
            ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Группа</th>
                            <th>Специальность</th>
                            <th>Куратор</th>
                            <th>Студентов</th>
                            <th>Абс. усп.</th>
                            <th>Кач. усп.</th>
                            <th>С 1 «2», %</th>
                            <th>С 1 «3», %</th>
                            <th>С «2»</th>
                            <th>4–5</th>
                            <th>Отличники</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                        <?php
                        $group = $row['group'];
                        $overview = $row['overview'];
                        $summary = $overview['summary'];
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <a href="<?= e($summaryGradebookHref) ?>?group_id=<?= (int) $group['id'] ?>">
                                    <?= e($group['number']) ?>
                                </a>
                            </td>
                            <td><?= e($group['specialty_name']) ?></td>
                            <td><?= e(($group['curator_name'] ?? '') !== '' ? $group['curator_name'] : '—') ?></td>
                            <td><?= (int) $overview['students'] ?></td>
                            <td><?= e((string) $summary['absolute_percent']) ?>%</td>
                            <td><?= e((string) $summary['quality_percent']) ?>%</td>
                            <td><?= e((string) $summary['one_two_percent']) ?>%</td>
                            <td><?= e((string) $summary['one_three_percent']) ?>%</td>
                            <td><?= (int) $overview['with_twos'] ?></td>
                            <td><?= (int) $overview['only_good'] ?></td>
                            <td><?= (int) $overview['excellent'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($groups !== [] && $isCompare && $report1 && $report2): ?>
    <section class="panel">
        <h2>Сравнение семестров по колледжу</h2>
        <p class="text-muted">Ключевые показатели <?= e($year) ?>: 1 семестр vs 2 семестр.</p>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Показатель</th>
                        <th>1 семестр</th>
                        <th>2 семестр</th>
                        <th>Δ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $compareRows = [
                        ['Абс. успеваемость, %', $m1['absolute_percent'], $m2['absolute_percent'], true],
                        ['Кач. успеваемость, %', $m1['quality_percent'], $m2['quality_percent'], true],
                        ['Средний балл', $m1['average_grade'], $m2['average_grade'], true],
                        ['СОУ, %', $m1['sou_percent'], $m2['sou_percent'], true],
                        ['Без акад. задолженности, %', $m1['no_debt_percent'], $m2['no_debt_percent'], true],
                        ['Отличники, %', $m1['excellent_percent'], $m2['excellent_percent'], true],
                        ['С оценкой «2», %', $m1['twos_percent'], $m2['twos_percent'], false],
                        ['Оценённых', $t1['assessed'], $t2['assessed'], true],
                        ['Выставлено оценок', $t1['filled_grades'], $t2['filled_grades'], true],
                    ];
                    foreach ($compareRows as [$label, $v1, $v2, $higherBetter]):
                        $delta = round((float) $v2 - (float) $v1, 1);
                        $good = $higherBetter ? $delta > 0 : $delta < 0;
                        $bad = $higherBetter ? $delta < 0 : $delta > 0;
                        $cls = $good ? 'text-success' : ($bad ? 'text-danger' : '');
                    ?>
                    <tr>
                        <td><?= e($label) ?></td>
                        <td><?= e((string) $v1) ?></td>
                        <td><?= e((string) $v2) ?></td>
                        <td class="<?= $cls ?>">
                            <?= $delta > 0 ? '+' : '' ?><?= e((string) $delta) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="summary-college-kpis admin-stats-grid summary-compare-kpis">
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e((string) $m1['absolute_percent']) ?>%</div>
                <div class="admin-stat-card__label">Абс. · 1 сем.</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e((string) $m2['absolute_percent']) ?>%</div>
                <div class="admin-stat-card__label">Абс. · 2 сем.</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e((string) $m1['quality_percent']) ?>%</div>
                <div class="admin-stat-card__label">Кач. · 1 сем.</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e((string) $m2['quality_percent']) ?>%</div>
                <div class="admin-stat-card__label">Кач. · 2 сем.</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e(number_format((float) $m1['average_grade'], 2, '.', '')) ?></div>
                <div class="admin-stat-card__label">Ср. балл · 1 сем.</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e(number_format((float) $m2['average_grade'], 2, '.', '')) ?></div>
                <div class="admin-stat-card__label">Ср. балл · 2 сем.</div>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="summary-chart-head">
            <h2>Абсолютная успеваемость по группам: 1 vs 2</h2>
            <div class="summary-chart-sort" data-chart-sort="deputy-summary-compare-groups-abs"></div>
        </div>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-compare-groups-abs" aria-label="Сравнение абсолютной успеваемости по группам"></canvas>
        </div>
    </section>
    <section class="panel">
        <div class="summary-chart-head">
            <h2>Качественная успеваемость по группам: 1 vs 2</h2>
            <div class="summary-chart-sort" data-chart-sort="deputy-summary-compare-groups-qual"></div>
        </div>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-compare-groups-qual" aria-label="Сравнение качественной успеваемости по группам"></canvas>
        </div>
    </section>
    <section class="panel">
        <div class="summary-chart-head">
            <h2>Абсолютная успеваемость по специальностям: 1 vs 2</h2>
            <div class="summary-chart-sort" data-chart-sort="deputy-summary-compare-spec-abs"></div>
        </div>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-compare-spec-abs" aria-label="Сравнение абсолютной успеваемости по специальностям"></canvas>
        </div>
    </section>
    <section class="panel">
        <div class="summary-chart-head">
            <h2>Качественная успеваемость по специальностям: 1 vs 2</h2>
            <div class="summary-chart-sort" data-chart-sort="deputy-summary-compare-spec-qual"></div>
        </div>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-compare-spec-qual" aria-label="Сравнение качественной успеваемости по специальностям"></canvas>
        </div>
    </section>
    <section class="panel">
        <h2>Ключевые показатели колледжа: 1 vs 2</h2>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-compare-college" aria-label="Сравнение ключевых показателей колледжа"></canvas>
        </div>
    </section>

    <?php elseif ($groups !== [] && $report): ?>
    <?php
    $metrics = $report['metrics'];
    $totals = $report['totals'];
    $charts = $report['charts'];
    $absolutePercent = $metrics['absolute_percent'];
    $qualityPercent = $metrics['quality_percent'];
    ?>
    <section class="panel">
        <div class="summary-chart-head">
            <div>
                <h2>Абсолютная успеваемость по группам</h2>
                <p class="text-muted">
                    Сравнение групп за период <?= e($periodCaption) ?>.
                    Среднее по колледжу: <?= e((string) $absolutePercent) ?>%.
                </p>
            </div>
            <div class="summary-chart-sort" data-chart-sort="deputy-summary-absolute-chart"></div>
        </div>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-absolute-chart" aria-label="Диаграмма абсолютной успеваемости по группам"></canvas>
        </div>
    </section>

    <section class="panel">
        <div class="summary-chart-head">
            <div>
                <h2>Качественная успеваемость по группам</h2>
                <p class="text-muted">
                    Сравнение групп за период <?= e($periodCaption) ?>.
                    Среднее по колледжу: <?= e((string) $qualityPercent) ?>%.
                </p>
            </div>
            <div class="summary-chart-sort" data-chart-sort="deputy-summary-quality-chart"></div>
        </div>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-quality-chart" aria-label="Диаграмма качественной успеваемости по группам"></canvas>
        </div>
    </section>

    <section class="panel">
        <div class="summary-chart-head">
            <div>
                <h2>Абсолютная успеваемость по специальностям</h2>
                <p class="text-muted">
                    Сравнение специальностей за период <?= e($periodCaption) ?>.
                    Среднее по колледжу: <?= e((string) $absolutePercent) ?>%.
                </p>
            </div>
            <div class="summary-chart-sort" data-chart-sort="deputy-summary-specialty-absolute-chart"></div>
        </div>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-specialty-absolute-chart" aria-label="Диаграмма абсолютной успеваемости по специальностям"></canvas>
        </div>
    </section>

    <section class="panel">
        <div class="summary-chart-head">
            <div>
                <h2>Качественная успеваемость по специальностям</h2>
                <p class="text-muted">
                    Сравнение специальностей за период <?= e($periodCaption) ?>.
                    Среднее по колледжу: <?= e((string) $qualityPercent) ?>%.
                </p>
            </div>
            <div class="summary-chart-sort" data-chart-sort="deputy-summary-specialty-quality-chart"></div>
        </div>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-specialty-quality-chart" aria-label="Диаграмма качественной успеваемости по специальностям"></canvas>
        </div>
    </section>

    <section class="panel">
        <div class="summary-chart-head">
            <div>
                <h2>Абсолютная успеваемость по специальностям за 3 года</h2>
                <p class="text-muted">Сравнение по учебным годам: <?= e($yearsCompareLabel) ?>.</p>
            </div>
            <div class="summary-chart-sort" data-chart-sort="deputy-summary-specialty-absolute-years-chart"></div>
        </div>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-specialty-absolute-years-chart" aria-label="Сравнение абсолютной успеваемости по специальностям за три года"></canvas>
        </div>
    </section>

    <section class="panel">
        <div class="summary-chart-head">
            <div>
                <h2>Качественная успеваемость по специальностям за 3 года</h2>
                <p class="text-muted">Сравнение по учебным годам: <?= e($yearsCompareLabel) ?>.</p>
            </div>
            <div class="summary-chart-sort" data-chart-sort="deputy-summary-specialty-quality-years-chart"></div>
        </div>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-specialty-quality-years-chart" aria-label="Сравнение качественной успеваемости по специальностям за три года"></canvas>
        </div>
    </section>

    <section class="panel">
        <h2>Успеваемость колледжа за 3 года</h2>
        <p class="text-muted">Абсолютная и качественная успеваемость: <?= e($yearsCompareLabel) ?>.</p>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-college-years-chart" aria-label="Сравнение успеваемости колледжа за три года"></canvas>
        </div>
    </section>

    <section class="panel panel--info">
        <h2>Итого по колледжу</h2>
        <p class="text-muted">Сводка за <?= e($periodCaption) ?>.</p>

        <div class="admin-stats-grid summary-college-kpis">
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e((string) $absolutePercent) ?>%</div>
                <div class="admin-stat-card__label">Абс. успеваемость</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e((string) $qualityPercent) ?>%</div>
                <div class="admin-stat-card__label">Кач. успеваемость</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e(number_format((float) $metrics['average_grade'], 2, '.', '')) ?></div>
                <div class="admin-stat-card__label">Средний балл</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e((string) $metrics['sou_percent']) ?>%</div>
                <div class="admin-stat-card__label">СОУ (степень обученности)</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e((string) $metrics['no_debt_percent']) ?>%</div>
                <div class="admin-stat-card__label">Без акад. задолженности</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e((string) $metrics['excellent_percent']) ?>%</div>
                <div class="admin-stat-card__label">Отличники (<?= (int) $totals['excellent'] ?>)</div>
            </div>
        </div>

        <div class="summary-college-charts">
            <div class="summary-college-chart">
                <h3 class="subsection-title">Качество контингента</h3>
                <p class="text-muted summary-college-chart__hint">По минимальной оценке студента</p>
                <div class="summary-chart-wrap summary-chart-wrap--donut">
                    <canvas id="deputy-summary-college-students-chart" aria-label="Состав студентов по успеваемости"></canvas>
                </div>
            </div>
            <div class="summary-college-chart">
                <h3 class="subsection-title">Структура оценок</h3>
                <p class="text-muted summary-college-chart__hint">Все выставленные итоговые оценки</p>
                <div class="summary-chart-wrap summary-chart-wrap--donut">
                    <canvas id="deputy-summary-college-grades-chart" aria-label="Распределение итоговых оценок 2–5"></canvas>
                </div>
            </div>
            <div class="summary-college-chart">
                <h3 class="subsection-title">Студенты по специальностям</h3>
                <p class="text-muted summary-college-chart__hint">Контингент в разрезе специальностей</p>
                <div class="summary-chart-wrap summary-chart-wrap--donut">
                    <canvas id="deputy-summary-college-specialty-chart" aria-label="Распределение студентов по специальностям"></canvas>
                </div>
            </div>
        </div>

        <div class="admin-stats-columns">
            <dl class="profile-list">
                <dt>Студентов</dt>
                <dd><?= (int) $totals['students'] ?></dd>
                <dt>Оценённых</dt>
                <dd><?= (int) $totals['assessed'] ?> (<?= e((string) $metrics['assessed_percent']) ?>%)</dd>
                <dt>Выставлено оценок</dt>
                <dd><?= (int) $totals['filled_grades'] ?></dd>
                <dt>Средний балл</dt>
                <dd><?= e(number_format((float) $metrics['average_grade'], 2, '.', '')) ?></dd>
            </dl>
            <dl class="profile-list">
                <dt>Абс. успеваемость</dt>
                <dd><?= e((string) $absolutePercent) ?>%</dd>
                <dt>Кач. успеваемость</dt>
                <dd><?= e((string) $qualityPercent) ?>%</dd>
                <dt>СОУ</dt>
                <dd><?= e((string) $metrics['sou_percent']) ?>%</dd>
                <dt>Без акад. задолженности</dt>
                <dd><?= e((string) $metrics['no_debt_percent']) ?>%</dd>
            </dl>
            <dl class="profile-list">
                <dt>Отличники</dt>
                <dd><?= (int) $totals['excellent'] ?> (<?= e((string) $metrics['excellent_percent']) ?>%)</dd>
                <dt>Только 4–5</dt>
                <dd><?= (int) $totals['only_good'] ?> (<?= e((string) $metrics['only_good_percent']) ?>%)</dd>
                <dt>С 1 оценкой «2»</dt>
                <dd><?= (int) $totals['one_two'] ?> (<?= e((string) $metrics['one_two_percent']) ?>%)</dd>
                <dt>С 1 оценкой «3»</dt>
                <dd><?= (int) $totals['one_three'] ?> (<?= e((string) $metrics['one_three_percent']) ?>%)</dd>
            </dl>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php if ($groups !== []): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const summaryChartRegistry = {};

const fillChartSortControls = () => {
    document.querySelectorAll('[data-chart-sort]').forEach((node) => {
        if (node.dataset.ready === '1') {
            return;
        }
        node.dataset.ready = '1';
        node.innerHTML = ''
            + '<span class="summary-chart-sort__label">Сортировка</span>'
            + '<button type="button" class="summary-chart-sort__btn is-active" data-sort="default">По умолчанию</button>'
            + '<button type="button" class="summary-chart-sort__btn" data-sort="desc">По убыванию</button>'
            + '<button type="button" class="summary-chart-sort__btn" data-sort="asc">По возрастанию</button>';
        node.addEventListener('click', (event) => {
            const btn = event.target.closest('[data-sort]');
            if (!btn) {
                return;
            }
            const mode = btn.getAttribute('data-sort');
            const chartId = node.getAttribute('data-chart-sort');
            node.querySelectorAll('[data-sort]').forEach((el) => {
                el.classList.toggle('is-active', el === btn);
            });
            applySummaryChartSort(chartId, mode);
        });
    });
};

const buildSortedIndexes = (keys, labels, mode) => {
    const items = keys.map((key, index) => ({
        key: Number(key) || 0,
        label: String(labels[index] ?? ''),
        index,
    }));
    if (mode === 'asc') {
        items.sort((a, b) => a.key - b.key || a.label.localeCompare(b.label, 'ru'));
    } else if (mode === 'desc') {
        items.sort((a, b) => b.key - a.key || a.label.localeCompare(b.label, 'ru'));
    } else {
        items.sort((a, b) => a.index - b.index);
    }
    return items.map((item) => item.index);
};

const applySummaryChartSort = (chartId, mode) => {
    const entry = summaryChartRegistry[chartId];
    if (!entry || !entry.chart) {
        return;
    }
    const order = buildSortedIndexes(entry.sortKeys, entry.baseLabels, mode);
    entry.chart.data.labels = order.map((index) => entry.baseLabels[index]);
    entry.datasets.forEach((dataset, datasetIndex) => {
        entry.chart.data.datasets[datasetIndex].data = order.map((index) => dataset[index]);
    });
    if (entry.average != null) {
        const avgDataset = entry.chart.data.datasets.find((ds) => ds.type === 'line');
        if (avgDataset) {
            avgDataset.data = entry.chart.data.labels.map(() => entry.average);
        }
    }
    entry.chart.update();
};

const registerSummaryChart = (chartId, chart, baseLabels, datasets, sortKeys, average = null) => {
    summaryChartRegistry[chartId] = {
        chart,
        baseLabels: baseLabels.slice(),
        datasets: datasets.map((row) => row.slice()),
        sortKeys: sortKeys.slice(),
        average,
    };
};

const renderSummaryGroupChart = (canvasId, labels, values, collegeAverage, barLabel, barColor, barBorder, rotateXLabels = false) => {
    const chartNode = document.getElementById(canvasId);
    if (!chartNode || typeof Chart === 'undefined') {
        return;
    }

    const chart = new Chart(chartNode, {
        data: {
            labels,
            datasets: [
                {
                    type: 'bar',
                    label: barLabel,
                    data: values,
                    backgroundColor: barColor,
                    borderColor: barBorder,
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    type: 'line',
                    label: 'Среднее по колледжу',
                    data: labels.map(() => collegeAverage),
                    borderColor: '#ef6c00',
                    backgroundColor: 'transparent',
                    borderDash: [6, 4],
                    pointRadius: 0,
                    borderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 550, easing: 'easeOutQuart' },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label(context) {
                            return context.dataset.label + ': ' + context.parsed.y + '%';
                        },
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        font: { size: 13, weight: '700' },
                        color: '#243041',
                        ...(rotateXLabels ? { maxRotation: 45, minRotation: 15 } : {}),
                    },
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback(value) { return value + '%'; },
                    },
                },
            },
        },
    });

    registerSummaryChart(canvasId, chart, labels, [values], values, collegeAverage);
};

const renderCompareBars = (canvasId, labels, values1, values2, label1, label2, maxY = 100, asPercent = true) => {
    const chartNode = document.getElementById(canvasId);
    if (!chartNode || typeof Chart === 'undefined') {
        return;
    }

    const chart = new Chart(chartNode, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: label1,
                    data: values1,
                    backgroundColor: 'rgba(21, 101, 192, 0.75)',
                    borderColor: '#1565c0',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: label2,
                    data: values2,
                    backgroundColor: 'rgba(46, 125, 50, 0.75)',
                    borderColor: '#2e7d32',
                    borderWidth: 1,
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 550, easing: 'easeOutQuart' },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const suffix = asPercent ? '%' : '';
                            return context.dataset.label + ': ' + context.parsed.y + suffix;
                        },
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        font: { size: 13, weight: '700' },
                        color: '#243041',
                        maxRotation: 45,
                        minRotation: 0,
                    },
                },
                y: {
                    beginAtZero: true,
                    max: maxY,
                    ticks: asPercent ? {
                        callback(value) { return value + '%'; },
                    } : {},
                },
            },
        },
    });

    const sortKeys = labels.map((_, index) => (
        (Number(values1[index]) || 0) + (Number(values2[index]) || 0)
    ) / 2);
    registerSummaryChart(canvasId, chart, labels, [values1, values2], sortKeys);
};

const yearComparePalette = ['#1565c0', '#6a1b9a', '#ef6c00'];

const renderSpecialtyYearsChart = (canvasId, compareData, metricKey) => {
    const chartNode = document.getElementById(canvasId);
    if (!chartNode || typeof Chart === 'undefined' || !compareData) {
        return;
    }

    const metricRows = compareData[metricKey] || [];
    const labels = (compareData.specialties || []).map((item) => item.label);
    const yearCount = (compareData.years || []).length;
    const datasetsData = (compareData.years || []).map((year, index) => (
        metricRows.map((row) => row[index] ?? 0)
    ));

    const chart = new Chart(chartNode, {
        type: 'bar',
        data: {
            labels,
            datasets: (compareData.years || []).map((year, index) => ({
                label: (compareData.year_labels || [])[index] || year,
                data: datasetsData[index],
                backgroundColor: yearComparePalette[index % yearComparePalette.length],
                borderColor: yearComparePalette[index % yearComparePalette.length],
                borderWidth: 1,
                borderRadius: 4,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 550, easing: 'easeOutQuart' },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label(context) {
                            return context.dataset.label + ': ' + context.parsed.y + '%';
                        },
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        font: { size: 13, weight: '700' },
                        color: '#243041',
                        maxRotation: 45,
                        minRotation: 15,
                    },
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback(value) { return value + '%'; } },
                },
            },
        },
    });

    const lastYearIndex = Math.max(0, yearCount - 1);
    const sortKeys = metricRows.map((row) => Number(row[lastYearIndex] ?? 0) || 0);
    registerSummaryChart(canvasId, chart, labels, datasetsData, sortKeys);
};

const renderCollegeYearsChart = (canvasId, compareData) => {
    const chartNode = document.getElementById(canvasId);
    if (!chartNode || typeof Chart === 'undefined' || !compareData) {
        return;
    }

    new Chart(chartNode, {
        type: 'bar',
        data: {
            labels: compareData.year_labels || compareData.years || [],
            datasets: [
                {
                    label: 'Абсолютная успеваемость',
                    data: compareData.college_absolute || [],
                    backgroundColor: 'rgba(21, 101, 192, 0.75)',
                    borderColor: '#1565c0',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Качественная успеваемость',
                    data: compareData.college_quality || [],
                    backgroundColor: 'rgba(46, 125, 50, 0.75)',
                    borderColor: '#2e7d32',
                    borderWidth: 1,
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label(context) {
                            return context.dataset.label + ': ' + context.parsed.y + '%';
                        },
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback(value) { return value + '%'; } },
                },
            },
        },
    });
};

const donutPalette = ['#2e7d32', '#1565c0', '#f9a825', '#c62828', '#78909c', '#6a1b9a', '#00838f', '#ef6c00'];

const renderCollegeDonutChart = (canvasId, labels, values, colors) => {
    const chartNode = document.getElementById(canvasId);
    if (!chartNode || typeof Chart === 'undefined') {
        return;
    }

    const total = values.reduce((sum, value) => sum + Number(value || 0), 0);
    const palette = Array.isArray(colors) && colors.length ? colors : donutPalette;
    new Chart(chartNode, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: labels.map((_, index) => palette[index % palette.length]),
                borderWidth: 1,
                borderColor: '#ffffff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '58%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, padding: 10 },
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const value = Number(context.parsed || 0);
                            const pct = total > 0 ? Math.round((value / total) * 1000) / 10 : 0;
                            return context.label + ': ' + value + ' (' + pct + '%)';
                        },
                    },
                },
            },
        },
    });
};

(() => {
    fillChartSortControls();
<?php if ($isCompare && $report1 && $report2): ?>
    const groupLabels = <?= json_encode($report1['charts']['group_labels'], JSON_UNESCAPED_UNICODE) ?>;
    renderCompareBars(
        'deputy-summary-compare-groups-abs',
        groupLabels,
        <?= json_encode($report1['charts']['group_absolute'], JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($report2['charts']['group_absolute'], JSON_UNESCAPED_UNICODE) ?>,
        '1 семестр',
        '2 семестр'
    );
    renderCompareBars(
        'deputy-summary-compare-groups-qual',
        groupLabels,
        <?= json_encode($report1['charts']['group_quality'], JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($report2['charts']['group_quality'], JSON_UNESCAPED_UNICODE) ?>,
        '1 семестр',
        '2 семестр'
    );

    const specLabels = <?= json_encode(
        array_values(array_unique(array_merge(
            $report1['charts']['specialty_labels'],
            $report2['charts']['specialty_labels']
        ))),
        JSON_UNESCAPED_UNICODE
    ) ?>;
    const mapSpec = (labels, values, allLabels) => {
        const map = {};
        labels.forEach((label, i) => { map[label] = values[i] ?? 0; });
        return allLabels.map((label) => map[label] ?? 0);
    };
    const specAbs1 = <?= json_encode($report1['charts']['specialty_absolute'], JSON_UNESCAPED_UNICODE) ?>;
    const specAbs2 = <?= json_encode($report2['charts']['specialty_absolute'], JSON_UNESCAPED_UNICODE) ?>;
    const specQual1 = <?= json_encode($report1['charts']['specialty_quality'], JSON_UNESCAPED_UNICODE) ?>;
    const specQual2 = <?= json_encode($report2['charts']['specialty_quality'], JSON_UNESCAPED_UNICODE) ?>;
    const labels1 = <?= json_encode($report1['charts']['specialty_labels'], JSON_UNESCAPED_UNICODE) ?>;
    const labels2 = <?= json_encode($report2['charts']['specialty_labels'], JSON_UNESCAPED_UNICODE) ?>;
    renderCompareBars(
        'deputy-summary-compare-spec-abs',
        specLabels,
        mapSpec(labels1, specAbs1, specLabels),
        mapSpec(labels2, specAbs2, specLabels),
        '1 семестр',
        '2 семестр'
    );
    renderCompareBars(
        'deputy-summary-compare-spec-qual',
        specLabels,
        mapSpec(labels1, specQual1, specLabels),
        mapSpec(labels2, specQual2, specLabels),
        '1 семестр',
        '2 семестр'
    );

    renderCompareBars(
        'deputy-summary-compare-college',
        ['Абс. усп.', 'Кач. усп.', 'СОУ', 'Без задолженности', 'Отличники'],
        [
            <?= (float) $report1['metrics']['absolute_percent'] ?>,
            <?= (float) $report1['metrics']['quality_percent'] ?>,
            <?= (float) $report1['metrics']['sou_percent'] ?>,
            <?= (float) $report1['metrics']['no_debt_percent'] ?>,
            <?= (float) $report1['metrics']['excellent_percent'] ?>
        ],
        [
            <?= (float) $report2['metrics']['absolute_percent'] ?>,
            <?= (float) $report2['metrics']['quality_percent'] ?>,
            <?= (float) $report2['metrics']['sou_percent'] ?>,
            <?= (float) $report2['metrics']['no_debt_percent'] ?>,
            <?= (float) $report2['metrics']['excellent_percent'] ?>
        ],
        '1 семестр',
        '2 семестр'
    );
<?php elseif ($report): ?>
    const labels = <?= json_encode($report['charts']['group_labels'], JSON_UNESCAPED_UNICODE) ?>;
    renderSummaryGroupChart(
        'deputy-summary-absolute-chart',
        labels,
        <?= json_encode($report['charts']['group_absolute'], JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($report['metrics']['absolute_percent'], JSON_UNESCAPED_UNICODE) ?>,
        'Абс. успеваемость, %',
        'rgba(21, 101, 192, 0.75)',
        '#1565c0'
    );
    renderSummaryGroupChart(
        'deputy-summary-quality-chart',
        labels,
        <?= json_encode($report['charts']['group_quality'], JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($report['metrics']['quality_percent'], JSON_UNESCAPED_UNICODE) ?>,
        'Кач. успеваемость, %',
        'rgba(46, 125, 50, 0.75)',
        '#2e7d32'
    );

    const specialtyLabels = <?= json_encode($report['charts']['specialty_labels'], JSON_UNESCAPED_UNICODE) ?>;
    renderSummaryGroupChart(
        'deputy-summary-specialty-absolute-chart',
        specialtyLabels,
        <?= json_encode($report['charts']['specialty_absolute'], JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($report['metrics']['absolute_percent'], JSON_UNESCAPED_UNICODE) ?>,
        'Абс. успеваемость, %',
        'rgba(21, 101, 192, 0.75)',
        '#1565c0',
        true
    );
    renderSummaryGroupChart(
        'deputy-summary-specialty-quality-chart',
        specialtyLabels,
        <?= json_encode($report['charts']['specialty_quality'], JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($report['metrics']['quality_percent'], JSON_UNESCAPED_UNICODE) ?>,
        'Кач. успеваемость, %',
        'rgba(46, 125, 50, 0.75)',
        '#2e7d32',
        true
    );

    const yearsComparison = <?= json_encode($yearsComparison, JSON_UNESCAPED_UNICODE) ?>;
    renderSpecialtyYearsChart(
        'deputy-summary-specialty-absolute-years-chart',
        yearsComparison,
        'specialty_absolute'
    );
    renderSpecialtyYearsChart(
        'deputy-summary-specialty-quality-years-chart',
        yearsComparison,
        'specialty_quality'
    );
    renderCollegeYearsChart('deputy-summary-college-years-chart', yearsComparison);

    renderCollegeDonutChart(
        'deputy-summary-college-students-chart',
        <?= json_encode($report['metrics']['student_composition_labels'], JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($report['metrics']['student_composition_values'], JSON_UNESCAPED_UNICODE) ?>,
        ['#2e7d32', '#1565c0', '#f9a825', '#c62828']
    );
    renderCollegeDonutChart(
        'deputy-summary-college-grades-chart',
        <?= json_encode($report['metrics']['grade_distribution_labels'], JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($report['metrics']['grade_distribution_values'], JSON_UNESCAPED_UNICODE) ?>,
        ['#2e7d32', '#1565c0', '#f9a825', '#c62828']
    );
    renderCollegeDonutChart(
        'deputy-summary-college-specialty-chart',
        <?= json_encode($report['charts']['specialty_students_labels'], JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($report['charts']['specialty_students_values'], JSON_UNESCAPED_UNICODE) ?>
    );
<?php endif; ?>
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
