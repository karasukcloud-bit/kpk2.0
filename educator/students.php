<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/group_report.php';

require_educator_panel();

$groups = get_all_groups();
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

if ($groupId === 0 && count($groups) === 1) {
    $groupId = (int) $groups[0]['id'];
}

$group = null;
$students = [];
$report = null;

if ($groupId > 0) {
    $group = get_group_by_id($groupId);
    if ($group === null) {
        $groupId = 0;
    } else {
        $students = get_students_by_group($groupId);
        $report = build_group_report($students, $groupId);
    }
}

$pageTitle = 'Информация по студентам — Панель воспитателя';
$showHeader = true;
$basePath = '../';
$currentEducatorTab = 'students';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide educator-students-page">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель воспитателя</h1>
                <p class="text-muted">Сведения о студентах по группам</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/educator_nav.php'; ?>
    </section>

    <?php if ($groups === []): ?>
        <section class="panel">
            <p class="text-muted">В системе пока нет групп.</p>
        </section>
    <?php else: ?>
        <section class="panel">
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
                </div>
            </form>

            <?php if ($group === null): ?>
                <p class="text-muted">Выберите группу, чтобы увидеть информацию о студентах.</p>
            <?php else: ?>
                <?php require __DIR__ . '/../includes/educator/students_info_table.php'; ?>

                <hr class="divider">
                <?php
                $showGroupReportTitle = true;
                require __DIR__ . '/../includes/group_report_view.php';
                ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php if ($group !== null && $students !== []): ?>
<script>
(() => {
    const table = document.getElementById('educator-students-table');
    if (!table) {
        return;
    }

    const controls = document.getElementById('educator-students-col-controls');
    const storageKey = 'educatorStudentInfoColumns';
    const checks = controls ? controls.querySelectorAll('.student-info-col__check') : [];

    const applyColumn = (col, visible) => {
        table.querySelectorAll('[data-col="' + col + '"]').forEach((node) => {
            node.classList.toggle('is-col-hidden', !visible);
        });
    };

    const saveState = () => {
        const state = {};
        checks.forEach((check) => {
            state[check.dataset.col] = check.checked;
        });
        localStorage.setItem(storageKey, JSON.stringify(state));
    };

    checks.forEach((check) => {
        const syncColumn = () => {
            applyColumn(check.dataset.col, check.checked);
            saveState();
        };
        check.addEventListener('change', syncColumn);
        check.addEventListener('input', syncColumn);
    });

    try {
        const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
        checks.forEach((check) => {
            if (Object.prototype.hasOwnProperty.call(saved, check.dataset.col)) {
                check.checked = !!saved[check.dataset.col];
            }
            applyColumn(check.dataset.col, check.checked);
        });
    } catch (error) {
        checks.forEach((check) => applyColumn(check.dataset.col, check.checked));
    }

    const printBtn = document.getElementById('educator-students-print-btn');
    if (printBtn) {
        printBtn.addEventListener('click', () => window.print());
    }
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
