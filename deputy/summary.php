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

$rows = [];
$totals = [
    'students' => 0,
    'assessed' => 0,
    'absolute' => 0,
    'quality' => 0,
    'with_twos' => 0,
    'only_good' => 0,
    'excellent' => 0,
    'complete' => 0,
    'incomplete' => 0,
    'empty' => 0,
];

foreach ($groups as $group) {
    $overview = get_group_gradebook_overview((int) $group['id'], $year, $semester);
    $summary = $overview['summary'];
    $assessed = (int) $summary['assessed_students'];

    $rows[] = [
        'group' => $group,
        'overview' => $overview,
    ];

    $totals['students'] += (int) $overview['students'];
    $totals['assessed'] += $assessed;
    $totals['absolute'] += (int) ($summary['absolute_count'] ?? 0);
    $totals['quality'] += (int) ($summary['quality_count'] ?? 0);
    $totals['with_twos'] += (int) $overview['with_twos'];
    $totals['only_good'] += (int) $overview['only_good'];
    $totals['excellent'] += (int) $overview['excellent'];

    if (!empty($overview['empty'])) {
        $totals['empty']++;
    } elseif (!empty($overview['complete'])) {
        $totals['complete']++;
    } else {
        $totals['incomplete']++;
    }
}

$absolutePercent = $totals['assessed'] > 0
    ? round($totals['absolute'] / $totals['assessed'] * 100, 1)
    : 0.0;
$qualityPercent = $totals['assessed'] > 0
    ? round($totals['quality'] / $totals['assessed'] * 100, 1)
    : 0.0;

$absoluteChartLabels = [];
$absoluteChartValues = [];
$qualityChartValues = [];
foreach ($rows as $row) {
    $absoluteChartLabels[] = (string) $row['group']['number'];
    $absoluteChartValues[] = (float) ($row['overview']['summary']['absolute_percent'] ?? 0);
    $qualityChartValues[] = (float) ($row['overview']['summary']['quality_percent'] ?? 0);
}

$specialtyStats = [];
foreach ($rows as $row) {
    $specialtyId = (int) $row['group']['specialty_id'];
    $summary = $row['overview']['summary'];
    if (!isset($specialtyStats[$specialtyId])) {
        $specialtyStats[$specialtyId] = [
            'name' => (string) ($row['group']['specialty_name'] ?? ''),
            'code' => (string) ($row['group']['specialty_code'] ?? ''),
            'assessed' => 0,
            'absolute' => 0,
            'quality' => 0,
        ];
    }
    $specialtyStats[$specialtyId]['assessed'] += (int) ($summary['assessed_students'] ?? 0);
    $specialtyStats[$specialtyId]['absolute'] += (int) ($summary['absolute_count'] ?? 0);
    $specialtyStats[$specialtyId]['quality'] += (int) ($summary['quality_count'] ?? 0);
}

uasort($specialtyStats, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));

$specialtyChartLabels = [];
$specialtyAbsoluteValues = [];
$specialtyQualityValues = [];
foreach ($specialtyStats as $stat) {
    $label = $stat['name'];
    if ($stat['code'] !== '') {
        $label .= ' (' . $stat['code'] . ')';
    }
    $specialtyChartLabels[] = $label;
    $assessed = (int) $stat['assessed'];
    $specialtyAbsoluteValues[] = $assessed > 0
        ? round($stat['absolute'] / $assessed * 100, 1)
        : 0.0;
    $specialtyQualityValues[] = $assessed > 0
        ? round($stat['quality'] / $assessed * 100, 1)
        : 0.0;
}

$yearsComparison = build_gradebook_three_years_comparison($year);
$yearsCompareLabel = implode(', ', $yearsComparison['year_labels']);

$pageTitle = 'Сводная ведомость — Панель завуча';
$showHeader = true;
$basePath = '../';
$currentDeputyTab = 'summary';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель завуча</h1>
                <p class="text-muted">Сводная ведомость по всем группам</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/deputy_nav.php'; ?>
    </section>

    <section class="panel">
        <h2>Сводная ведомость</h2>
        <p class="text-muted">
            Учебный год <?= e($year) ?> · <?= e(semester_label($semester)) ?>.
            Оценки из электронного журнала.
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
                                <a href="gradebook.php?group_id=<?= (int) $group['id'] ?>">
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

    <?php if ($groups !== []): ?>
    <section class="panel">
        <h2>Абсолютная успеваемость по группам</h2>
        <p class="text-muted">
            Сравнение групп за период <?= e($year) ?> · <?= e(semester_label($semester)) ?>.
            Среднее по колледжу: <?= e((string) $absolutePercent) ?>%.
        </p>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-absolute-chart" aria-label="Диаграмма абсолютной успеваемости по группам"></canvas>
        </div>
    </section>

    <section class="panel">
        <h2>Качественная успеваемость по группам</h2>
        <p class="text-muted">
            Сравнение групп за период <?= e($year) ?> · <?= e(semester_label($semester)) ?>.
            Среднее по колледжу: <?= e((string) $qualityPercent) ?>%.
        </p>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-quality-chart" aria-label="Диаграмма качественной успеваемости по группам"></canvas>
        </div>
    </section>

    <section class="panel">
        <h2>Абсолютная успеваемость по специальностям</h2>
        <p class="text-muted">
            Сравнение специальностей за период <?= e($year) ?> · <?= e(semester_label($semester)) ?>.
            Среднее по колледжу: <?= e((string) $absolutePercent) ?>%.
        </p>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-specialty-absolute-chart" aria-label="Диаграмма абсолютной успеваемости по специальностям"></canvas>
        </div>
    </section>

    <section class="panel">
        <h2>Качественная успеваемость по специальностям</h2>
        <p class="text-muted">
            Сравнение специальностей за период <?= e($year) ?> · <?= e(semester_label($semester)) ?>.
            Среднее по колледжу: <?= e((string) $qualityPercent) ?>%.
        </p>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-specialty-quality-chart" aria-label="Диаграмма качественной успеваемости по специальностям"></canvas>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($groups !== []): ?>
    <section class="panel">
        <h2>Абсолютная успеваемость по специальностям за 3 года</h2>
        <p class="text-muted">Сравнение по учебным годам: <?= e($yearsCompareLabel) ?>.</p>
        <div class="summary-chart-wrap">
            <canvas id="deputy-summary-specialty-absolute-years-chart" aria-label="Сравнение абсолютной успеваемости по специальностям за три года"></canvas>
        </div>
    </section>

    <section class="panel">
        <h2>Качественная успеваемость по специальностям за 3 года</h2>
        <p class="text-muted">Сравнение по учебным годам: <?= e($yearsCompareLabel) ?>.</p>
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
    <?php endif; ?>

    <?php if ($groups !== []): ?>
    <section class="panel panel--info">
        <h2>Итого по колледжу</h2>
        <dl class="profile-list">
            <dt>Групп</dt>
            <dd><?= count($groups) ?> (заполнены: <?= (int) $totals['complete'] ?>, не полностью: <?= (int) $totals['incomplete'] ?>)</dd>
            <dt>Студентов</dt>
            <dd><?= (int) $totals['students'] ?></dd>
            <dt>Оценённых студентов</dt>
            <dd><?= (int) $totals['assessed'] ?> из <?= (int) $totals['students'] ?></dd>
            <dt>Успеваемость абсолютная</dt>
            <dd><?= e((string) $absolutePercent) ?>%</dd>
            <dt>Успеваемость качественная</dt>
            <dd><?= e((string) $qualityPercent) ?>%</dd>
            <dt>С оценкой «2»</dt>
            <dd><?= (int) $totals['with_twos'] ?></dd>
            <dt>Только оценки 4–5</dt>
            <dd><?= (int) $totals['only_good'] ?></dd>
            <dt>Отличники</dt>
            <dd><?= (int) $totals['excellent'] ?></dd>
        </dl>
    </section>
    <?php endif; ?>
</div>

<?php if ($groups !== []): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const renderSummaryGroupChart = (canvasId, labels, values, collegeAverage, barLabel, barColor, barBorder, rotateXLabels = false) => {
    const chartNode = document.getElementById(canvasId);
    if (!chartNode || typeof Chart === 'undefined') {
        return;
    }

    new Chart(chartNode, {
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
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            return context.dataset.label + ': ' + context.parsed.y + '%';
                        },
                    },
                },
            },
            scales: {
                x: rotateXLabels ? {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 15,
                    },
                } : {},
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback(value) {
                            return value + '%';
                        },
                    },
                },
            },
        },
    });
};

const yearComparePalette = ['#1565c0', '#6a1b9a', '#ef6c00'];

const renderSpecialtyYearsChart = (canvasId, compareData, metricKey) => {
    const chartNode = document.getElementById(canvasId);
    if (!chartNode || typeof Chart === 'undefined' || !compareData) {
        return;
    }

    const metricRows = compareData[metricKey] || [];
    const labels = (compareData.specialties || []).map((item) => item.label);

    new Chart(chartNode, {
        type: 'bar',
        data: {
            labels,
            datasets: (compareData.years || []).map((year, index) => ({
                label: (compareData.year_labels || [])[index] || year,
                data: metricRows.map((row) => row[index] ?? 0),
                backgroundColor: yearComparePalette[index % yearComparePalette.length],
                borderColor: yearComparePalette[index % yearComparePalette.length],
                borderWidth: 1,
                borderRadius: 4,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
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
                        maxRotation: 45,
                        minRotation: 15,
                    },
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback(value) {
                            return value + '%';
                        },
                    },
                },
            },
        },
    });
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
                legend: {
                    position: 'bottom',
                },
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
                    ticks: {
                        callback(value) {
                            return value + '%';
                        },
                    },
                },
            },
        },
    });
};

(() => {
    const labels = <?= json_encode($absoluteChartLabels, JSON_UNESCAPED_UNICODE) ?>;
    renderSummaryGroupChart(
        'deputy-summary-absolute-chart',
        labels,
        <?= json_encode($absoluteChartValues, JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($absolutePercent, JSON_UNESCAPED_UNICODE) ?>,
        'Абс. успеваемость, %',
        'rgba(21, 101, 192, 0.75)',
        '#1565c0'
    );
    renderSummaryGroupChart(
        'deputy-summary-quality-chart',
        labels,
        <?= json_encode($qualityChartValues, JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($qualityPercent, JSON_UNESCAPED_UNICODE) ?>,
        'Кач. успеваемость, %',
        'rgba(46, 125, 50, 0.75)',
        '#2e7d32'
    );

    const specialtyLabels = <?= json_encode($specialtyChartLabels, JSON_UNESCAPED_UNICODE) ?>;
    renderSummaryGroupChart(
        'deputy-summary-specialty-absolute-chart',
        specialtyLabels,
        <?= json_encode($specialtyAbsoluteValues, JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($absolutePercent, JSON_UNESCAPED_UNICODE) ?>,
        'Абс. успеваемость, %',
        'rgba(21, 101, 192, 0.75)',
        '#1565c0',
        true
    );
    renderSummaryGroupChart(
        'deputy-summary-specialty-quality-chart',
        specialtyLabels,
        <?= json_encode($specialtyQualityValues, JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($qualityPercent, JSON_UNESCAPED_UNICODE) ?>,
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
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
