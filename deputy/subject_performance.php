<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/attendance.php';

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён. Требуются права завуча или администратора.');
}

$activePeriod = get_active_gradebook_period();
$year = normalize_academic_year((string) ($_GET['year'] ?? '')) ?? $activePeriod['academic_year'];
$viewSem = (string) ($_GET['sem'] ?? '');
if (!in_array($viewSem, ['1', '2', 'year'], true)) {
    $viewSem = normalize_gradebook_semester((string) $activePeriod['semester']);
}

$source = (string) ($_GET['source'] ?? 'journal');
$manualBrsAvailable = is_manual_brs_gradebook_active();
if ($source === 'manual_brs' && !$manualBrsAvailable) {
    $source = 'journal';
}
$source = $source === 'manual_brs' ? 'manual_brs' : 'journal';

$yearOptions = get_last_academic_years($activePeriod['academic_year'], 4);
$report = build_specialty_subjects_performance_report($year, $viewSem, $source);
$specialties = $report['specialties'];

$specialtyKeys = array_keys($specialties);
$selectedSpecialty = (string) ($_GET['specialty'] ?? '');
if ($selectedSpecialty === '' || !isset($specialties[$selectedSpecialty])) {
    $selectedSpecialty = $specialtyKeys[0] ?? '';
}

$selected = $selectedSpecialty !== '' ? $specialties[$selectedSpecialty] : null;
$subjects = $selected['subjects'] ?? [];

$avgAbsolute = 0.0;
$avgQuality = 0.0;
$totalAssessed = 0;
if ($subjects !== []) {
    $sumAbs = 0.0;
    $sumQual = 0.0;
    foreach ($subjects as $subject) {
        $sumAbs += (float) $subject['absolute_percent'];
        $sumQual += (float) $subject['quality_percent'];
        $totalAssessed += (int) $subject['assessed'];
    }
    $avgAbsolute = round($sumAbs / count($subjects), 1);
    $avgQuality = round($sumQual / count($subjects), 1);
}

$periodCaption = $viewSem === 'year'
    ? 'весь учебный год'
    : semester_label($viewSem);
$sourceLabel = $source === 'manual_brs'
    ? 'Оценки из модуля «Ручное БРС».'
    : 'Оценки из электронного журнала.';

$query = static function (array $overrides = []) use ($year, $viewSem, $source, $selectedSpecialty): string {
    return http_build_query(array_merge([
        'year' => $year,
        'sem' => $viewSem,
        'source' => $source,
        'specialty' => $selectedSpecialty,
    ], $overrides));
};

$chartLabels = array_map(static fn (array $s): string => (string) $s['name'], $subjects);
$chartAbsolute = array_map(static fn (array $s): float => (float) $s['absolute_percent'], $subjects);
$chartQuality = array_map(static fn (array $s): float => (float) $s['quality_percent'], $subjects);
$chartHeight = max(280, count($subjects) * 34 + 80);

$pageTitle = 'Успеваемость по предметам — Панель завуча';
$currentDeputyTab = 'subject_performance';
$showHeader = true;
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
<script>
(() => {
    const KEY = 'kpk.deputy.subject_performance';
    const keys = ['year', 'sem', 'source', 'specialty'];
    const params = new URLSearchParams(window.location.search);
    const hasExplicit = keys.some((k) => params.has(k));
    try {
        if (!hasExplicit) {
            const saved = JSON.parse(window.localStorage.getItem(KEY) || 'null');
            if (saved && typeof saved === 'object') {
                const next = new URLSearchParams();
                keys.forEach((k) => {
                    if (saved[k] != null && String(saved[k]) !== '') {
                        next.set(k, String(saved[k]));
                    }
                });
                if ([...next.keys()].length > 0) {
                    window.location.replace(window.location.pathname + '?' + next.toString());
                    return;
                }
            }
        }
    } catch (e) {}

    try {
        window.localStorage.setItem(KEY, JSON.stringify({
            year: <?= json_encode($year, JSON_UNESCAPED_UNICODE) ?>,
            sem: <?= json_encode($viewSem, JSON_UNESCAPED_UNICODE) ?>,
            source: <?= json_encode($source, JSON_UNESCAPED_UNICODE) ?>,
            specialty: <?= json_encode($selectedSpecialty, JSON_UNESCAPED_UNICODE) ?>,
        }));
    } catch (e) {}
})();
</script>
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель завуча</h1>
                <p class="text-muted">Абсолютная и качественная успеваемость по предметам специальностей</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/deputy_nav.php'; ?>
    </section>

    <section class="panel">
        <h2>Успеваемость по предметам</h2>
        <p class="text-muted">
            <?= e($year) ?> · <?= e($periodCaption) ?>.
            <?= e($sourceLabel) ?>
        </p>

        <div class="summary-report-filters">
            <?php if ($manualBrsAvailable): ?>
            <div class="summary-report-filters__group">
                <span class="summary-report-filters__label">Источник оценок</span>
                <div class="summary-report-filters__tabs">
                    <a href="subject_performance.php?<?= e($query(['source' => 'journal'])) ?>"
                       class="summary-report-filters__tab<?= $source === 'journal' ? ' is-active' : '' ?>">
                        Электронный журнал
                    </a>
                    <a href="subject_performance.php?<?= e($query(['source' => 'manual_brs'])) ?>"
                       class="summary-report-filters__tab<?= $source === 'manual_brs' ? ' is-active' : '' ?>">
                        Ручное БРС
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="summary-report-filters__group">
                <span class="summary-report-filters__label">Учебный год</span>
                <div class="summary-report-filters__tabs">
                    <?php foreach ($yearOptions as $optYear): ?>
                        <a href="subject_performance.php?<?= e($query(['year' => $optYear])) ?>"
                           class="summary-report-filters__tab<?= $optYear === $year ? ' is-active' : '' ?>">
                            <?= e($optYear) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="summary-report-filters__group">
                <span class="summary-report-filters__label">Период</span>
                <div class="summary-report-filters__tabs">
                    <a href="subject_performance.php?<?= e($query(['sem' => '1'])) ?>"
                       class="summary-report-filters__tab<?= $viewSem === '1' ? ' is-active' : '' ?>">
                        1 семестр
                    </a>
                    <a href="subject_performance.php?<?= e($query(['sem' => '2'])) ?>"
                       class="summary-report-filters__tab<?= $viewSem === '2' ? ' is-active' : '' ?>">
                        2 семестр
                    </a>
                    <a href="subject_performance.php?<?= e($query(['sem' => 'year'])) ?>"
                       class="summary-report-filters__tab<?= $viewSem === 'year' ? ' is-active' : '' ?>">
                        Весь год
                    </a>
                </div>
            </div>
        </div>

        <?php if ($specialties === []): ?>
            <p class="text-muted">Нет оценок за выбранный период и источник.</p>
        <?php else: ?>
            <div class="summary-report-filters__group subject-perf-specialty">
                <span class="summary-report-filters__label">Специальность</span>
                <div class="summary-report-filters__tabs">
                    <?php foreach ($specialties as $key => $specialty): ?>
                        <a href="subject_performance.php?<?= e($query(['specialty' => $key])) ?>"
                           class="summary-report-filters__tab<?= $key === $selectedSpecialty ? ' is-active' : '' ?>">
                            <?= e((string) $specialty['label']) ?>
                            <span class="subject-perf-specialty__count"><?= count($specialty['subjects']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($selected !== null): ?>
    <section class="panel panel--info">
        <h2><?= e((string) $selected['label']) ?></h2>
        <p class="text-muted">
            Групп: <?= (int) $selected['groups'] ?> ·
            Предметов с оценками: <?= count($subjects) ?> ·
            Выставлено оценок: <?= (int) $totalAssessed ?>
        </p>

        <div class="admin-stats-grid summary-college-kpis">
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e((string) $avgAbsolute) ?>%</div>
                <div class="admin-stat-card__label">Средняя абс. по предметам</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e((string) $avgQuality) ?>%</div>
                <div class="admin-stat-card__label">Средняя кач. по предметам</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= count($subjects) ?></div>
                <div class="admin-stat-card__label">Предметов</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) $totalAssessed ?></div>
                <div class="admin-stat-card__label">Оценок</div>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="summary-chart-head">
            <div>
                <h2>Абсолютная успеваемость по предметам</h2>
                <p class="text-muted">Доля оценок «3» и выше. Среднее: <?= e((string) $avgAbsolute) ?>%.</p>
            </div>
            <div class="summary-chart-sort" data-chart-sort="subject-perf-abs-chart"></div>
        </div>
        <div class="summary-chart-wrap subject-perf-chart-wrap" style="height: <?= (int) $chartHeight ?>px">
            <canvas id="subject-perf-abs-chart" aria-label="Абсолютная успеваемость по предметам"></canvas>
        </div>
    </section>

    <section class="panel">
        <div class="summary-chart-head">
            <div>
                <h2>Качественная успеваемость по предметам</h2>
                <p class="text-muted">Доля оценок «4» и «5». Среднее: <?= e((string) $avgQuality) ?>%.</p>
            </div>
            <div class="summary-chart-sort" data-chart-sort="subject-perf-qual-chart"></div>
        </div>
        <div class="summary-chart-wrap subject-perf-chart-wrap" style="height: <?= (int) $chartHeight ?>px">
            <canvas id="subject-perf-qual-chart" aria-label="Качественная успеваемость по предметам"></canvas>
        </div>
    </section>

    <section class="panel">
        <h2>Таблица по предметам</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Предмет</th>
                        <th>Оценок</th>
                        <th>Абс. усп.</th>
                        <th>Кач. усп.</th>
                        <th>Средний балл</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $index => $subject): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= e((string) $subject['name']) ?></td>
                        <td><?= (int) $subject['assessed'] ?></td>
                        <td><?= e((string) $subject['absolute_percent']) ?>%</td>
                        <td><?= e((string) $subject['quality_percent']) ?>%</td>
                        <td><?= e(number_format((float) $subject['average_grade'], 2, '.', '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php if ($selected !== null && $subjects !== []): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
    const absoluteValues = <?= json_encode($chartAbsolute, JSON_UNESCAPED_UNICODE) ?>;
    const qualityValues = <?= json_encode($chartQuality, JSON_UNESCAPED_UNICODE) ?>;
    const avgAbsolute = <?= json_encode($avgAbsolute, JSON_UNESCAPED_UNICODE) ?>;
    const avgQuality = <?= json_encode($avgQuality, JSON_UNESCAPED_UNICODE) ?>;
    const registry = {};

    const fillSortControls = () => {
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
                applySort(chartId, mode);
            });
        });
    };

    const buildOrder = (keys, names, mode) => {
        const items = keys.map((key, index) => ({
            key: Number(key) || 0,
            name: String(names[index] ?? ''),
            index,
        }));
        if (mode === 'asc') {
            items.sort((a, b) => a.key - b.key || a.name.localeCompare(b.name, 'ru'));
        } else if (mode === 'desc') {
            items.sort((a, b) => b.key - a.key || a.name.localeCompare(b.name, 'ru'));
        } else {
            items.sort((a, b) => a.index - b.index);
        }
        return items.map((item) => item.index);
    };

    const applySort = (chartId, mode) => {
        const entry = registry[chartId];
        if (!entry) {
            return;
        }
        const order = buildOrder(entry.keys, entry.labels, mode);
        entry.chart.data.labels = order.map((i) => entry.labels[i]);
        entry.chart.data.datasets[0].data = order.map((i) => entry.values[i]);
        entry.chart.data.datasets[1].data = entry.chart.data.labels.map(() => entry.average);
        entry.chart.update();
    };

    const renderHorizontal = (canvasId, chartLabels, values, average, barLabel, color) => {
        const node = document.getElementById(canvasId);
        if (!node || typeof Chart === 'undefined') {
            return;
        }
        const chart = new Chart(node, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: barLabel,
                        data: values,
                        backgroundColor: color,
                        borderRadius: 4,
                        borderSkipped: false,
                    },
                    {
                        type: 'line',
                        label: 'Среднее по предметам',
                        data: chartLabels.map(() => average),
                        borderColor: '#ef6c00',
                        borderDash: [6, 4],
                        pointRadius: 0,
                        borderWidth: 2,
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 550, easing: 'easeOutQuart' },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                return ctx.dataset.label + ': ' + ctx.parsed.x + '%';
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { callback(v) { return v + '%'; } },
                    },
                    y: {
                        ticks: {
                            font: { size: 12, weight: '600' },
                            color: '#243041',
                            autoSkip: false,
                        },
                    },
                },
            },
        });
        registry[canvasId] = {
            chart,
            labels: chartLabels.slice(),
            values: values.slice(),
            keys: values.slice(),
            average,
        };
    };

    fillSortControls();
    renderHorizontal(
        'subject-perf-abs-chart',
        labels,
        absoluteValues,
        avgAbsolute,
        'Абс. успеваемость, %',
        'rgba(21, 101, 192, 0.8)'
    );
    renderHorizontal(
        'subject-perf-qual-chart',
        labels,
        qualityValues,
        avgQuality,
        'Кач. успеваемость, %',
        'rgba(46, 125, 50, 0.8)'
    );
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
