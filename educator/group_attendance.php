<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/attendance.php';

require_educator_panel();

$groups = get_all_groups();
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

if ($groupId === 0 && count($groups) === 1) {
    $groupId = (int) $groups[0]['id'];
}

$group = null;
$students = [];
$journal = ['days' => [], 'entries' => []];
$monthTotals = [];
$monthSummary = null;

$period = get_active_gradebook_period();
$year = $period['academic_year'];
$monthOptions = get_academic_year_months($year);
$month = resolve_attendance_month($year, $_GET['month'] ?? null);

if ($groupId > 0) {
    $group = get_group_by_id($groupId);
    if ($group === null) {
        $groupId = 0;
    } else {
        $students = get_students_by_group($groupId);
        $journal = get_attendance_journal($groupId, $year, $month);
        $monthTotals = build_attendance_month_totals($students, $journal);
        $monthSummary = build_attendance_month_summary($students, $monthTotals);
    }
}

$pageTitle = 'Пропуски — Панель воспитателя';
$showHeader = true;
$basePath = '../';
$currentEducatorTab = 'group_attendance';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide educator-group-attendance-page">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель воспитателя</h1>
                <p class="text-muted">Пропуски студентов по группам</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/educator_nav.php'; ?>
    </section>

    <section class="panel">
        <?php if ($groups === []): ?>
            <p class="text-muted">В системе пока нет групп.</p>
        <?php else: ?>
            <form method="get" class="form form--filter educator-no-print">
                <div class="form__row form__row--filter">
                    <div class="form__group">
                        <label for="group_id">Группа</label>
                        <select id="group_id" name="group_id" onchange="this.form.submit()">
                            <option value="">— Выберите группу —</option>
                            <?php foreach ($groups as $item): ?>
                            <option value="<?= (int) $item['id'] ?>"<?= (int) $item['id'] === $groupId ? ' selected' : '' ?>>
                                <?= e($item['number']) ?> · <?= e($item['specialty_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form__group">
                        <label for="month">Месяц</label>
                        <select id="month" name="month" onchange="this.form.submit()"<?= $groupId === 0 ? ' disabled' : '' ?>>
                            <?php foreach ($monthOptions as $option): ?>
                            <option value="<?= e($option['value']) ?>"<?= $option['value'] === $month ? ' selected' : '' ?>>
                                <?= e($option['label']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>

            <?php if ($group === null): ?>
                <p class="text-muted">Выберите группу, чтобы просмотреть пропуски.</p>
            <?php elseif ($students === []): ?>
                <p class="text-muted">В группе пока нет студентов.</p>
            <?php else: ?>
                <div class="educator-attendance-print-toolbar educator-no-print">
                    <button type="button" class="btn btn--secondary" id="educator-group-attendance-print-btn">
                        Печать
                    </button>
                </div>
                <div class="educator-attendance-print-area">
                    <div class="educator-attendance-print-header educator-print-only">
                        <h2>Пропуски группы</h2>
                        <p>
                            Группа <?= e($group['number']) ?>
                            · <?= e($group['specialty_name']) ?> (<?= e($group['specialty_code']) ?>)
                            · учебный год <?= e($year) ?>
                            · <?= e(format_attendance_month($month)) ?>
                            · студентов: <?= count($students) ?>
                        </p>
                    </div>
                    <?php
                    $attendanceReadOnly = true;
                    require __DIR__ . '/../includes/attendance/group_journal_display.php';
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php if ($group !== null && $students !== []): ?>
<script>
(() => {
    const printBtn = document.getElementById('educator-group-attendance-print-btn');
    if (printBtn) {
        printBtn.addEventListener('click', () => window.print());
    }
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
