<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/attendance.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/organization.php';

require_educator_panel();

$period = get_active_gradebook_period();
$year = $period['academic_year'];
$monthOptions = get_academic_year_months($year);
$month = resolve_attendance_month($year, $_GET['month'] ?? null);

$monthReport = build_educator_attendance_report($year, $month);
$semester1Report = build_educator_attendance_report($year, null, '1');
$semester2Report = build_educator_attendance_report($year, null, '2');
$yearReport = build_educator_attendance_report($year);
$chartData = build_attendance_year_chart_data($year);
$compareCharts = build_attendance_three_years_comparison_set($year);
$compareYearsLabel = implode(', ', array_column($compareCharts['total']['series'], 'year'));
$reasonAnalysis = build_attendance_reason_analysis($year, $yearReport);

$pageTitle = 'Сводка по пропускам — Панель воспитателя';
$showHeader = true;
$basePath = '../';
$currentEducatorTab = 'summary';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide educator-attendance-page">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель воспитателя</h1>
                <p class="text-muted">Сводные таблицы пропусков по группам</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/educator_nav.php'; ?>
    </section>

    <section class="panel">
        <form method="get" class="form form--filter">
            <div class="form__row form__row--filter">
                <div class="form__group">
                    <label for="month">Месяц для первой таблицы</label>
                    <select id="month" name="month" onchange="this.form.submit()">
                        <?php foreach ($monthOptions as $option): ?>
                        <option value="<?= e($option['value']) ?>"<?= $option['value'] === $month ? ' selected' : '' ?>>
                            <?= e($option['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
        <p class="text-muted">Учебный год <?= e($year) ?>.</p>

        <?php
        $rows = $monthReport;
        $tableTitle = 'За месяц: ' . format_attendance_month($month);
        require __DIR__ . '/../includes/educator/attendance_table.php';

        $rows = $semester1Report;
        $tableTitle = 'За 1 семестр';
        require __DIR__ . '/../includes/educator/attendance_table.php';

        $rows = $semester2Report;
        $tableTitle = 'За 2 семестр';
        require __DIR__ . '/../includes/educator/attendance_table.php';

        $rows = $yearReport;
        $tableTitle = 'За учебный год';
        require __DIR__ . '/../includes/educator/attendance_table.php';
        ?>
    </section>

    <section class="panel">
        <h2>Динамика пропусков за учебный год</h2>
        <p class="text-muted">Сумма пропусков по всем группам по месяцам.</p>
        <div class="educator-attendance-chart-wrap">
            <canvas id="educator-attendance-chart" aria-label="График пропусков за учебный год"></canvas>
        </div>
    </section>

    <section class="panel">
        <h2>Сравнение пропусков на студента за 3 учебных года</h2>
        <p class="text-muted">Среднее по всем группам по месяцам: <?= e($compareYearsLabel) ?>.</p>
        <h3 class="subsection-title">Всего на студента</h3>
        <div class="educator-attendance-chart-wrap">
            <canvas id="educator-compare-total-chart" aria-label="Сравнение всего пропусков на студента"></canvas>
        </div>
        <h3 class="subsection-title">Уважительных на студента</h3>
        <div class="educator-attendance-chart-wrap">
            <canvas id="educator-compare-excused-chart" aria-label="Сравнение уважительных пропусков на студента"></canvas>
        </div>
        <h3 class="subsection-title">Неуважительных на студента</h3>
        <div class="educator-attendance-chart-wrap">
            <canvas id="educator-compare-unexcused-chart" aria-label="Сравнение неуважительных пропусков на студента"></canvas>
        </div>
    </section>

    <?php require __DIR__ . '/../includes/educator/reason_analysis.php'; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const chartNode = document.getElementById('educator-attendance-chart');
    if (!chartNode || typeof Chart === 'undefined') {
        return;
    }

    const chartData = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE) ?>;
    new Chart(chartNode, {
        type: 'line',
        data: {
            labels: chartData.map((item) => item.label),
            datasets: [
                {
                    label: 'Всего пропусков',
                    data: chartData.map((item) => item.total),
                    borderColor: '#1565c0',
                    backgroundColor: 'rgba(21, 101, 192, 0.12)',
                    tension: 0.25,
                    fill: true,
                },
                {
                    label: 'Уважительные',
                    data: chartData.map((item) => item.excused),
                    borderColor: '#2e7d32',
                    backgroundColor: 'transparent',
                    tension: 0.25,
                },
                {
                    label: 'Неуважительные',
                    data: chartData.map((item) => item.unexcused),
                    borderColor: '#c62828',
                    backgroundColor: 'transparent',
                    tension: 0.25,
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
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                    },
                },
            },
        },
    });
})();

(() => {
    if (typeof Chart === 'undefined') {
        return;
    }

    const palette = ['#1565c0', '#6a1b9a', '#ef6c00'];
    const compareCharts = <?= json_encode($compareCharts, JSON_UNESCAPED_UNICODE) ?>;

    const renderCompareChart = (canvasId, compareData) => {
        const chartNode = document.getElementById(canvasId);
        if (!chartNode || !compareData) {
            return;
        }

        new Chart(chartNode, {
            type: 'line',
            data: {
                labels: compareData.labels,
                datasets: compareData.series.map((item, index) => ({
                    label: item.year + ' (' + item.year_value + ')',
                    data: item.values,
                    borderColor: palette[index % palette.length],
                    backgroundColor: 'transparent',
                    tension: 0.25,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 1,
                        },
                    },
                },
            },
        });
    };

    renderCompareChart('educator-compare-total-chart', compareCharts.total);
    renderCompareChart('educator-compare-excused-chart', compareCharts.excused);
    renderCompareChart('educator-compare-unexcused-chart', compareCharts.unexcused);
})();

(() => {
    const chartNode = document.getElementById('educator-reasons-chart');
    if (!chartNode || typeof Chart === 'undefined') {
        return;
    }

    const reasonData = <?= json_encode($reasonAnalysis['reasons'], JSON_UNESCAPED_UNICODE) ?>;
    if (!reasonData || reasonData.length === 0) {
        return;
    }

    const palette = ['#1565c0', '#2e7d32', '#ef6c00', '#6a1b9a', '#00838f', '#c62828', '#5d4037'];
    new Chart(chartNode, {
        type: 'doughnut',
        data: {
            labels: reasonData.map((item) => item.reason_name),
            datasets: [{
                data: reasonData.map((item) => item.lessons),
                backgroundColor: reasonData.map((_, index) => palette[index % palette.length]),
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
            },
        },
    });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
