<?php

declare(strict_types=1);

/** Страница «Показатели успеваемости». Ожидает переменные из deputy/performance_indicators.php. */

$perfSource = ($perfSource ?? 'journal') === 'manual_brs' ? 'manual_brs' : 'journal';
$perfGradebookHref = (string) ($perfGradebookHref ?? 'gradebook.php');
$perfSourceLabel = (string) ($perfSourceLabel ?? 'Оценки из электронного журнала.');
$perfSelfUrl = (string) ($perfSelfUrl ?? 'performance_indicators.php');
$pageTitle = (string) ($pageTitle ?? 'Показатели успеваемости — Панель завуча');
$currentDeputyTab = (string) ($currentDeputyTab ?? 'performance_indicators');

require_once __DIR__ . '/attendance.php';
require_once __DIR__ . '/students.php';

$activePeriod = get_active_gradebook_period();
$year = normalize_academic_year((string) ($_GET['year'] ?? '')) ?? $activePeriod['academic_year'];
$viewSem = (string) ($_GET['sem'] ?? '');
if (!in_array($viewSem, ['1', '2', 'year'], true)) {
    $viewSem = normalize_gradebook_semester((string) $activePeriod['semester']);
}
$isYear = $viewSem === 'year';
$semester = $isYear ? '1' : normalize_gradebook_semester($viewSem);
$reportSemester = $isYear ? 'year' : $semester;

$yearOptions = get_last_academic_years($activePeriod['academic_year'], 4);
$groups = get_all_groups();

$perfQuery = static function (array $overrides = []) use ($year, $viewSem, $perfSource): string {
    return http_build_query(array_merge([
        'year' => $year,
        'sem' => $viewSem,
        'source' => $perfSource,
    ], $overrides));
};

$report = $groups !== []
    ? build_college_gradebook_report($year, $reportSemester, $perfSource, $groups)
    : null;

if ($isYear) {
    $periodCaption = $year . ' · весь учебный год (1 и 2 семестр)';
} else {
    $periodCaption = $year . ' · ' . semester_label($semester);
}

$summaryPerfCell = static function (float $percent): string {
    $clamped = max(0.0, min(100.0, $percent));
    $label = rtrim(rtrim(number_format($clamped, 1, '.', ''), '0'), '.');
    if ($label === '') {
        $label = '0';
    }

    return '<span class="summary-perf-value">' . e($label) . '%</span>';
};

$formatStudentNames = static function (array $students): string {
    if ($students === []) {
        return '—';
    }
    $names = [];
    foreach ($students as $student) {
        $name = person_last_first_name((string) ($student['full_name'] ?? ''));
        if ($name !== '') {
            $names[] = $name;
        }
    }

    return $names === [] ? '—' : implode("\n", $names);
};

$qualityRowLevel = static function (float $percent): string {
    if ($percent >= 50) {
        return 'high';
    }
    if ($percent > 20) {
        return 'mid';
    }

    return 'low';
};

$qualityStudents = static function (array $lists): array {
    $merged = array_merge($lists['excellent'] ?? [], $lists['only_good'] ?? []);
    usort(
        $merged,
        static fn (array $a, array $b): int => strcasecmp(
            (string) ($a['full_name'] ?? ''),
            (string) ($b['full_name'] ?? '')
        )
    );

    return $merged;
};

$showHeader = true;
$basePath = '../';
require __DIR__ . '/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель завуча</h1>
                <p class="text-muted">Показатели качественной и абсолютной успеваемости по группам</p>
            </div>
        </div>
        <?php require __DIR__ . '/deputy_nav.php'; ?>
    </section>

    <section class="panel">
        <h2>Показатели успеваемости</h2>
        <p class="text-muted">
            <?= e($periodCaption) ?>.
            <?= e($perfSourceLabel) ?>
        </p>

        <div class="summary-report-filters">
            <?php
            $sourceFilterCurrent = $perfSource;
            $sourceFilterUrl = static function (array $overrides) use ($perfQuery): string {
                return 'performance_indicators.php?' . $perfQuery($overrides);
            };
            require __DIR__ . '/deputy_source_filter.php';
            ?>
            <div class="summary-report-filters__group">
                <span class="summary-report-filters__label">Учебный год</span>
                <div class="summary-report-filters__tabs">
                    <?php foreach ($yearOptions as $optYear): ?>
                        <a href="<?= e($perfSelfUrl) ?>?<?= e($perfQuery(['year' => $optYear])) ?>"
                           class="summary-report-filters__tab<?= $optYear === $year ? ' is-active' : '' ?>">
                            <?= e($optYear) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="summary-report-filters__group">
                <span class="summary-report-filters__label">Период</span>
                <div class="summary-report-filters__tabs">
                    <a href="<?= e($perfSelfUrl) ?>?<?= e($perfQuery(['sem' => '1'])) ?>"
                       class="summary-report-filters__tab<?= $viewSem === '1' ? ' is-active' : '' ?>">
                        1 семестр
                    </a>
                    <a href="<?= e($perfSelfUrl) ?>?<?= e($perfQuery(['sem' => '2'])) ?>"
                       class="summary-report-filters__tab<?= $viewSem === '2' ? ' is-active' : '' ?>">
                        2 семестр
                    </a>
                    <a href="<?= e($perfSelfUrl) ?>?<?= e($perfQuery(['sem' => 'year'])) ?>"
                       class="summary-report-filters__tab<?= $isYear ? ' is-active' : '' ?>">
                        Весь год
                    </a>
                </div>
            </div>
        </div>

        <?php if ($report === null || ($report['rows'] ?? []) === []): ?>
            <p class="text-muted">Нет данных для выбранного периода.</p>
        <?php else: ?>
            <?php
            $rows = $report['rows'];
            $qualityRows = $rows;
            usort(
                $qualityRows,
                static function (array $a, array $b): int {
                    $qa = (float) ($a['overview']['summary']['quality_percent'] ?? 0);
                    $qb = (float) ($b['overview']['summary']['quality_percent'] ?? 0);
                    if ($qa !== $qb) {
                        return $qb <=> $qa;
                    }
                    $ga = (string) ($a['group']['number'] ?? '');
                    $gb = (string) ($b['group']['number'] ?? '');

                    return strnatcasecmp($ga, $gb);
                }
            );
            ?>

            <div class="summary-table-toolbar">
                <p class="text-muted summary-sort-hint">Экспорт обеих таблиц с текущей сортировкой.</p>
                <div
                    class="summary-export-actions"
                    data-perf-export
                    data-export-url="performance_indicators_export.php"
                    data-year="<?= e($year) ?>"
                    data-sem="<?= e($viewSem) ?>"
                    data-source="<?= e($perfSource) ?>"
                    data-csrf="<?= e(csrf_token()) ?>"
                >
                    <button type="button" class="btn btn--secondary btn--sm" data-export-format="pdf">Скачать PDF</button>
                    <button type="button" class="btn btn--secondary btn--sm" data-export-format="excel">Скачать Excel</button>
                </div>
            </div>

            <h3 class="perf-indicators__title">Рейтинг групп по качественной успеваемости</h3>
            <p class="text-muted perf-indicators__hint">
                Места по убыванию «Кач. усп.». Список: студенты только на «4–5» и отличники (все «5»),
                у которых выставлены оценки по всем предметам (пустые ячейки ИГО не учитываются).
            </p>
            <div class="table-wrap">
                <table
                    class="table summary-groups-table perf-indicators-table"
                    data-summary-groups-table
                    data-perf-table="quality"
                    data-sort-key="quality"
                    data-sort-dir="desc"
                >
                    <thead>
                        <tr>
                            <th>Место</th>
                            <th>
                                <button
                                    type="button"
                                    class="summary-sort-btn"
                                    data-summary-sort="group"
                                    aria-pressed="false"
                                >Группа</button>
                            </th>
                            <th>Специальность</th>
                            <th>Куратор</th>
                            <th>Студентов</th>
                            <th>
                                <button
                                    type="button"
                                    class="summary-sort-btn is-active"
                                    data-summary-sort="quality"
                                    data-dir="desc"
                                    aria-pressed="true"
                                >Кач. усп.</button>
                            </th>
                            <th class="perf-indicators__count-col">Кол-во</th>
                            <th>Студенты на 4–5 и 5</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($qualityRows as $index => $row): ?>
                        <?php
                        $group = $row['group'];
                        $overview = $row['overview'];
                        $summary = $overview['summary'];
                        $qualPercent = (float) $summary['quality_percent'];
                        $goodList = $qualityStudents($overview['lists'] ?? []);
                        $goodCount = count($goodList);
                        ?>
                        <tr
                            data-group-id="<?= (int) $group['id'] ?>"
                            data-group="<?= e((string) $group['number']) ?>"
                            data-quality="<?= e((string) $qualPercent) ?>"
                            class="perf-indicators__row perf-indicators__row--<?= e($qualityRowLevel($qualPercent)) ?>"
                        >
                            <td data-summary-row-num class="summary-metric"><?= $index + 1 ?></td>
                            <td>
                                <a class="summary-group-link" href="<?= e($perfGradebookHref) ?>?group_id=<?= (int) $group['id'] ?>" target="_blank" rel="noopener">
                                    <?= e((string) $group['number']) ?>
                                </a>
                            </td>
                            <td><?= e((string) $group['specialty_name']) ?></td>
                            <td><?= e(($group['curator_name'] ?? '') !== '' ? (string) $group['curator_name'] : '—') ?></td>
                            <td><?= (int) $overview['students'] ?></td>
                            <td><?= $summaryPerfCell($qualPercent) ?></td>
                            <td class="perf-indicators__count-col summary-metric"><?= $goodCount ?></td>
                            <td class="perf-indicators__students"><?= nl2br(e($formatStudentNames($goodList)), false) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h3 class="perf-indicators__title">Показатели абсолютной успеваемости</h3>
            <p class="text-muted perf-indicators__hint">
                Список: студенты с «2», у которых выставлены оценки по всем предметам (пустые ячейки ИГО не учитываются).
                Сортировка по «Абс. усп.».
            </p>
            <div class="table-wrap">
                <table
                    class="table summary-groups-table perf-indicators-table"
                    data-summary-groups-table
                    data-perf-table="absolute"
                >
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>
                                <button
                                    type="button"
                                    class="summary-sort-btn"
                                    data-summary-sort="group"
                                    aria-pressed="false"
                                >Группа</button>
                            </th>
                            <th>Специальность</th>
                            <th>Куратор</th>
                            <th>Студентов</th>
                            <th>
                                <button
                                    type="button"
                                    class="summary-sort-btn"
                                    data-summary-sort="absolute"
                                    aria-pressed="false"
                                >Абс. усп.</button>
                            </th>
                            <th class="perf-indicators__count-col">Кол-во</th>
                            <th>Студенты с «2»</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                        <?php
                        $group = $row['group'];
                        $overview = $row['overview'];
                        $summary = $overview['summary'];
                        $absPercent = (float) $summary['absolute_percent'];
                        $withTwos = $overview['lists']['with_twos'] ?? [];
                        $twosCount = count($withTwos);
                        $hasTwos = $twosCount > 0;
                        ?>
                        <tr
                            data-group-id="<?= (int) $group['id'] ?>"
                            data-group="<?= e((string) $group['number']) ?>"
                            data-absolute="<?= e((string) $absPercent) ?>"
                            class="<?= $hasTwos ? 'summary-groups-table__row--debt' : '' ?>"
                        >
                            <td data-summary-row-num><?= $index + 1 ?></td>
                            <td>
                                <a class="summary-group-link" href="<?= e($perfGradebookHref) ?>?group_id=<?= (int) $group['id'] ?>" target="_blank" rel="noopener">
                                    <?= e((string) $group['number']) ?>
                                </a>
                            </td>
                            <td><?= e((string) $group['specialty_name']) ?></td>
                            <td><?= e(($group['curator_name'] ?? '') !== '' ? (string) $group['curator_name'] : '—') ?></td>
                            <td><?= (int) $overview['students'] ?></td>
                            <td><?= $summaryPerfCell($absPercent) ?></td>
                            <td class="perf-indicators__count-col summary-metric"><?= $twosCount ?></td>
                            <td class="perf-indicators__students">
                                <?= nl2br(e($formatStudentNames($withTwos)), false) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
