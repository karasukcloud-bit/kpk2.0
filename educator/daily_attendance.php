<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/attendance.php';
require_once __DIR__ . '/../includes/organization.php';

require_educator_panel();

$dateOptions = get_educator_daily_attendance_date_options();
$selectedDate = resolve_educator_daily_attendance_date($_GET['date'] ?? null);
$dailyReport = build_educator_daily_attendance_report($selectedDate);
$organizationName = trim((string) (get_organization()['name'] ?? ''));

$pageTitle = 'Пропуски по дням — Панель воспитателя';
$showHeader = true;
$basePath = '../';
$currentEducatorTab = 'daily_attendance';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide educator-daily-attendance-page">
    <section class="panel educator-no-print">
        <div class="panel__header">
            <div>
                <h1>Панель воспитателя</h1>
                <p class="text-muted">Пропуски по дням за последние 7 дней</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/educator_nav.php'; ?>
    </section>

    <section class="panel">
        <div class="eda-toolbar educator-no-print">
            <nav class="journal-subtabs eda-date-tabs">
                <?php foreach ($dateOptions as $option): ?>
                <a
                    href="daily_attendance.php?date=<?= e($option['value']) ?>"
                    class="journal-subtabs__item<?= $option['value'] === $selectedDate ? ' journal-subtabs__item--active' : '' ?>"
                ><?= e($option['label']) ?><?= !empty($option['is_today']) ? ' · сегодня' : '' ?></a>
                <?php endforeach; ?>
            </nav>
            <button type="button" class="btn btn--secondary" id="educator-daily-attendance-print-btn">
                Печать
            </button>
        </div>

        <div class="educator-daily-attendance-print-area">
            <div class="educator-attendance-print-header educator-print-only">
                <?php if ($organizationName !== ''): ?>
                <div class="educator-daily-attendance-print-org"><?= e($organizationName) ?></div>
                <?php endif; ?>
                <h2>Информация о пропусках занятий за <?= e(format_attendance_date($selectedDate)) ?></h2>
            </div>

            <p class="text-muted educator-no-print eda-hint">
                Данные из журналов пропусков кураторов. Зелёная подсветка группы — за выбранный день дата уже внесена куратором (даже если отсутствующих не было).
            </p>

            <?php require __DIR__ . '/../includes/educator/daily_attendance_table.php'; ?>
        </div>
    </section>
</div>

<script>
(() => {
    const printBtn = document.getElementById('educator-daily-attendance-print-btn');
    if (printBtn) {
        printBtn.addEventListener('click', () => window.print());
    }
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
