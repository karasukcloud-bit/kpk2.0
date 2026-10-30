<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/attendance.php';
require_once __DIR__ . '/../includes/curator_monthly_report.php';

require_educator_panel();

$period = get_active_gradebook_period();
$academicYear = (string) ($period['academic_year'] ?? '');
$month = resolve_attendance_month($academicYear, isset($_GET['month']) ? (string) $_GET['month'] : null);
$groupId = (int) ($_GET['group_id'] ?? 0);
$group = $groupId > 0 ? get_group_by_id($groupId) : null;
$report = $group ? get_curator_monthly_report($groupId, $month) : null;

if ($group === null || $report === null) {
    flash_set('error', 'Отчёт куратора за выбранный месяц не найден.');
    header('Location: curator_reports.php?month=' . urlencode($month));
    exit;
}

$students = get_students_by_group($groupId);
$studentCount = count($students);
$studentNameMap = [];
foreach ($students as $student) {
    $studentNameMap[(int) $student['id']] = (string) ($student['full_name'] ?? '');
}

$payload = is_array($report['payload'] ?? null)
    ? $report['payload']
    : curator_monthly_report_default_payload();
$scored = curator_monthly_report_score_payload($payload, $studentCount);
$itemMeta = curator_monthly_report_item_meta();

$pageTitle = 'Отчёт куратора — Панель воспитателя';
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
                <p class="text-muted">Просмотр отчёта куратора</p>
            </div>
            <div class="panel__actions">
                <a href="curator_reports.php?month=<?= e(urlencode($month)) ?>" class="btn btn--ghost">← К списку</a>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/educator_nav.php'; ?>
    </section>

    <section class="panel">
        <div class="panel__header panel__header--compact">
            <div>
                <h2>Группа <?= e((string) ($group['number'] ?? '')) ?></h2>
                <p class="text-muted">
                    <?= e(format_attendance_month($month)) ?> ·
                    Куратор: <?= e((string) (($group['curator_name'] ?? '') !== '' ? $group['curator_name'] : '—')) ?> ·
                    Студентов: <?= (int) $studentCount ?> ·
                    Баллов: <?= (int) $scored['total_points'] ?>
                </p>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/curator_monthly_report_view.php'; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
