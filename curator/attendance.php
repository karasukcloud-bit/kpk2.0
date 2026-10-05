<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/attendance.php';

require_curator_panel();

$ctx = resolve_curator_group_context(isset($_GET['group_id']) ? (int) $_GET['group_id'] : null);
$groups = $ctx['groups'];
$groupId = $ctx['group_id'];
$group = $ctx['group'];
$students = $group ? get_students_by_group($groupId) : [];
$year = get_default_academic_year();
$monthOptions = get_academic_year_months($year);
$month = resolve_attendance_month($year, $_GET['month'] ?? null);
[$monthStart, $monthEnd] = attendance_month_date_bounds($month);
$reasons = get_attendance_reasons(true);
$organizationName = trim((string) (get_organization()['name'] ?? ''));
$curatorName = '';
if ($group !== null) {
    $curatorName = trim((string) ($group['curator_name'] ?? ''));
    if ($curatorName === '') {
        $curatorName = trim((string) (current_user()['full_name'] ?? ''));
    }
}
$error = null;
$editDayId = isset($_GET['edit_day']) ? (int) $_GET['edit_day'] : 0;
$editDay = null;
$editEntries = [];
$showForm = $editDayId > 0;

$attendanceUrl = static function (int $groupId, string $month, array $extra = []): string {
    $params = array_merge(['group_id' => $groupId, 'month' => $month], $extra);

    return 'attendance.php?' . http_build_query($params);
};

if ($group && $editDayId > 0) {
    $editDay = get_attendance_day($editDayId, $groupId);
    if ($editDay === null) {
        $error = 'Запись не найдена.';
        $editDayId = 0;
        $showForm = false;
    } else {
        $month = resolve_attendance_month($year, substr((string) $editDay['attendance_date'], 0, 7));
        [$monthStart, $monthEnd] = attendance_month_date_bounds($month);
        $journal = get_attendance_journal($groupId, $year, $month);
        $editEntries = $journal['entries'][$editDayId] ?? [];
        $showForm = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $group !== null) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = $_POST['action'] ?? '';
        $postedMonth = resolve_attendance_month($year, $_POST['month'] ?? $month);

        if ($action === 'save_day') {
            $result = save_attendance_day(
                $groupId,
                (string) ($_POST['attendance_date'] ?? ''),
                $year,
                (array) ($_POST['entries'] ?? []),
                isset($_POST['day_id']) && $_POST['day_id'] !== '' ? (int) $_POST['day_id'] : null
            );
        } elseif ($action === 'delete_day') {
            $result = delete_attendance_day((int) ($_POST['day_id'] ?? 0), $groupId);
            $result['month'] = $postedMonth;
        } else {
            $result = ['success' => false, 'error' => 'Неизвестное действие.'];
        }

        if ($result['success']) {
            if ($action === 'save_day') {
                $hasAbsences = false;
                foreach ((array) ($_POST['entries'] ?? []) as $entry) {
                    if ((int) ($entry['excused_lessons'] ?? 0) > 0 || (int) ($entry['unexcused_lessons'] ?? 0) > 0) {
                        $hasAbsences = true;
                        break;
                    }
                }
                $successMessage = $hasAbsences
                    ? 'Запись о пропусках сохранена.'
                    : 'Дата сохранена. Пропусков нет — все студенты присутствовали.';
            } elseif ($action === 'delete_day') {
                $successMessage = 'Запись удалена.';
            } else {
                $successMessage = 'Изменения сохранены.';
            }
            flash_set('success', $successMessage);
            $redirectMonth = $result['month'] ?? $postedMonth;
            header('Location: ' . $attendanceUrl($groupId, $redirectMonth));
            exit;
        }

        $error = $result['error'];
        $showForm = $action === 'save_day';
        $month = $postedMonth;
        [$monthStart, $monthEnd] = attendance_month_date_bounds($month);

        if ($showForm) {
            $editDayId = (int) ($_POST['day_id'] ?? 0);
            $editDay = $editDayId > 0 ? get_attendance_day($editDayId, $groupId) : null;
            $editEntries = [];

            foreach ((array) ($_POST['entries'] ?? []) as $studentId => $entry) {
                $studentId = (int) $studentId;
                $editEntries[$studentId] = [
                    'excused_lessons' => max(0, (int) ($entry['excused_lessons'] ?? 0)),
                    'unexcused_lessons' => max(0, (int) ($entry['unexcused_lessons'] ?? 0)),
                    'reason_id' => isset($entry['reason_id']) && $entry['reason_id'] !== ''
                        ? (int) $entry['reason_id']
                        : null,
                    'reason_name' => '',
                ];
            }
        }
    }
}

$journal = $group ? get_attendance_journal($groupId, $year, $month) : ['days' => [], 'entries' => []];
$monthTotals = $group ? build_attendance_month_totals($students, $journal) : [];
$monthSummary = $group ? build_attendance_month_summary($students, $monthTotals) : null;
$success = flash_get('success');
$defaultFormDate = max($monthStart, min(date('Y-m-d'), $monthEnd));

$pageTitle = 'Посещаемость — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'attendance';
$curatorGroupId = $groupId;
$curatorGroups = $groups;
$curatorGroupPreserveParams = ['month' => $month];
if ($editDayId > 0) {
    $curatorGroupPreserveParams['edit_day'] = $editDayId;
}
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide curator-attendance-page">
    <section class="panel curator-no-print">
        <div class="panel__header">
            <div>
                <h1>Панель куратора</h1>
                <p class="text-muted">Учёт посещаемости группы</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/curator_nav.php'; ?>
    </section>

    <section class="panel">
        <?php if ($success): ?>
            <div class="alert alert--success curator-no-print"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert--error curator-no-print"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (empty($groups)): ?>
            <p class="text-muted">Вам ещё не назначена группа.</p>
        <?php elseif ($group === null): ?>
            <p class="text-muted">Выберите группу, чтобы вести учёт посещаемости.</p>
        <?php else: ?>
            <form method="get" class="form form--filter curator-no-print">
                <div class="form__row form__row--filter">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <div class="form__group">
                        <label for="month">Месяц</label>
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
        <?php endif; ?>

        <?php if (empty($groups) || $group === null): ?>
        <?php elseif (empty($students)): ?>
            <p class="text-muted">В группе пока нет студентов.</p>
        <?php else: ?>
            <p class="attendance-journal-meta curator-no-print">
                Группа <strong><?= e($group['number']) ?></strong>
                · учебный год <?= e($year) ?>
                · <?= e(format_attendance_month($month)) ?>
                · студентов: <?= count($students) ?>
            </p>
            <p class="text-muted curator-no-print">
                Все пропуски сохраняются по датам и учебному году — их можно будет вывести в сводной таблице.
            </p>

            <div class="attendance-toolbar curator-no-print">
                <button type="button" class="btn btn--primary" data-attendance-add-toggle>
                    <?= $showForm && $editDayId === 0 ? 'Скрыть форму' : 'Добавить дату' ?>
                </button>
            </div>

            <div
                class="attendance-form<?= $showForm ? '' : ' attendance-form--hidden' ?> curator-no-print"
                data-attendance-form
            >
                <h2><?= $editDayId > 0 ? 'Изменить дату' : 'Добавить дату' ?></h2>
                <form method="post" class="form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_day">
                    <input type="hidden" name="month" value="<?= e($month) ?>">
                    <?php if ($editDayId > 0): ?>
                    <input type="hidden" name="day_id" value="<?= $editDayId ?>">
                    <?php endif; ?>

                    <div class="form__group">
                        <label for="attendance_date">Дата</label>
                        <input
                            type="date"
                            id="attendance_date"
                            name="attendance_date"
                            required
                            min="<?= e($monthStart) ?>"
                            max="<?= e($monthEnd) ?>"
                            value="<?= e($editDay['attendance_date'] ?? $defaultFormDate) ?>"
                        >
                    </div>

                    <div class="table-wrap">
                        <table class="table attendance-entry-table">
                            <thead>
                                <tr>
                                    <th>Студент</th>
                                    <th>Причина</th>
                                    <th>Уважит.</th>
                                    <th>Неуважит.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <?php
                                $studentId = (int) $student['id'];
                                $entry = $editEntries[$studentId] ?? null;
                                ?>
                                <tr>
                                    <td><?= e(person_last_first_name((string) $student['full_name'])) ?></td>
                                    <td>
                                        <select name="entries[<?= $studentId ?>][reason_id]">
                                            <?= render_attendance_reason_options(
                                                $reasons,
                                                $entry['reason_id'] ?? null
                                            ) ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            name="entries[<?= $studentId ?>][excused_lessons]"
                                            min="0"
                                            max="20"
                                            value="<?= (int) ($entry['excused_lessons'] ?? 0) ?>"
                                            class="attendance-input-num"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            name="entries[<?= $studentId ?>][unexcused_lessons]"
                                            min="0"
                                            max="20"
                                            value="<?= (int) ($entry['unexcused_lessons'] ?? 0) ?>"
                                            class="attendance-input-num"
                                        >
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-muted attendance-form__hint">
                        Укажите количество пропущенных уроков. Для уважительных пропусков выберите причину.
                        Можно сохранить дату без пропусков — если отсутствующих не было.
                        Дата должна относиться к выбранному месяцу.
                    </p>

                    <div class="form__actions">
                        <button type="submit" class="btn btn--primary">
                            <?= $editDayId > 0 ? 'Сохранить' : 'Добавить' ?>
                        </button>
                        <?php if ($editDayId > 0): ?>
                        <a href="<?= e($attendanceUrl($groupId, $month)) ?>" class="btn btn--ghost">Отмена</a>
                        <?php else: ?>
                        <button type="button" class="btn btn--ghost" data-attendance-cancel>Отмена</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if (!empty($journal['days'])): ?>
            <div class="curator-attendance-print-toolbar curator-no-print">
                <label class="checkbox-label curator-print-mode">
                    <input
                        type="checkbox"
                        id="curator-attendance-multisheet"
                        value="1"
                    >
                    Крупный шрифт — печать на нескольких листах по ширине
                </label>
                <button type="button" class="btn btn--secondary" id="curator-attendance-print-btn">
                    Печать
                </button>
            </div>
            <?php endif; ?>

            <div class="curator-attendance-print-area" id="curator-attendance-print-area">
                <div class="curator-attendance-print-header curator-print-only">
                    <?php if ($organizationName !== ''): ?>
                    <div class="curator-attendance-print-org"><?= e($organizationName) ?></div>
                    <?php endif; ?>
                    <h2>Информация о пропусках занятий</h2>
                    <p>
                        Группа <?= e($group['number']) ?>
                        <?php if (!empty($group['specialty_name'])): ?>
                        · <?= e($group['specialty_name']) ?><?= !empty($group['specialty_code']) ? ' (' . e($group['specialty_code']) . ')' : '' ?>
                        <?php endif; ?>
                        · учебный год <?= e($year) ?>
                        · <?= e(format_attendance_month($month)) ?>
                        <?php if ($curatorName !== ''): ?>
                        · куратор: <?= e(person_last_first_name($curatorName)) ?>
                        <?php endif; ?>
                        · студентов: <?= count($students) ?>
                    </p>
                </div>
                <?php
                $attendanceReadOnly = false;
                $attendanceShowIntro = false;
                require __DIR__ . '/../includes/attendance/group_journal_display.php';
                ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php if ($group !== null && $students !== [] && !empty($journal['days'])): ?>
<script>
(() => {
    const printBtn = document.getElementById('curator-attendance-print-btn');
    const multiSheet = document.getElementById('curator-attendance-multisheet');
    const printArea = document.getElementById('curator-attendance-print-area');
    if (!printBtn || !printArea) {
        return;
    }

    const studentsPerSheet = 15;
    let splitHost = null;

    const restoreOriginalTable = () => {
        document.body.classList.remove('curator-print-multisheet');
        if (splitHost && splitHost.parentNode) {
            splitHost.parentNode.removeChild(splitHost);
        }
        splitHost = null;
        printArea.classList.remove('is-print-split-source');
    };

    const buildSplitTables = (sourceTable) => {
        const headRow = sourceTable.tHead ? sourceTable.tHead.rows[0] : null;
        if (!headRow) {
            return null;
        }

        const studentIndexes = [];
        Array.from(headRow.cells).forEach((cell, index) => {
            if (cell.classList.contains('attendance-table__student-col')) {
                studentIndexes.push(index);
            }
        });

        if (studentIndexes.length === 0) {
            return null;
        }

        const host = document.createElement('div');
        host.className = 'curator-attendance-print-split curator-print-only';
        host.setAttribute('aria-hidden', 'true');

        const headerClone = printArea.querySelector('.curator-attendance-print-header');
        const totalSheets = Math.ceil(studentIndexes.length / studentsPerSheet);

        for (let sheet = 0; sheet < totalSheets; sheet += 1) {
            const start = sheet * studentsPerSheet;
            const chunk = studentIndexes.slice(start, start + studentsPerSheet);
            const keep = new Set([0, ...chunk]);

            const section = document.createElement('section');
            section.className = 'curator-attendance-print-sheet';
            if (sheet < totalSheets - 1) {
                section.classList.add('curator-attendance-print-sheet--break');
            }

            if (headerClone) {
                const sheetHeader = headerClone.cloneNode(true);
                sheetHeader.classList.remove('curator-print-only');
                const meta = sheetHeader.querySelector('p');
                if (meta) {
                    meta.textContent = (meta.textContent || '').trim()
                        + ' · лист ' + (sheet + 1) + ' из ' + totalSheets
                        + ' (студенты ' + (start + 1) + '–' + (start + chunk.length) + ')';
                }
                section.appendChild(sheetHeader);
            }

            const table = sourceTable.cloneNode(true);
            table.classList.add('attendance-table--print-sheet');
            Array.from(table.rows).forEach((row) => {
                Array.from(row.cells).forEach((cell, index) => {
                    if (!keep.has(index)) {
                        cell.parentNode.removeChild(cell);
                    }
                });
            });

            const wrap = document.createElement('div');
            wrap.className = 'table-wrap';
            wrap.appendChild(table);
            section.appendChild(wrap);
            host.appendChild(section);
        }

        return host;
    };

    const prepareMultisheetPrint = () => {
        restoreOriginalTable();
        const sourceTable = printArea.querySelector('table.attendance-table');
        if (!sourceTable) {
            return false;
        }

        splitHost = buildSplitTables(sourceTable);
        if (!splitHost) {
            return false;
        }

        document.body.classList.add('curator-print-multisheet');
        printArea.classList.add('is-print-split-source');
        printArea.parentNode.insertBefore(splitHost, printArea.nextSibling);
        return true;
    };

    printBtn.addEventListener('click', () => {
        const useMulti = multiSheet && multiSheet.checked;
        if (useMulti) {
            prepareMultisheetPrint();
        } else {
            restoreOriginalTable();
        }

        let styleEl = document.getElementById('curator-force-landscape');
        if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = 'curator-force-landscape';
            document.head.appendChild(styleEl);
        }
        styleEl.textContent = [
            '@page { size: A4 landscape; margin: 10mm; }',
            '@page curator-attendance { size: A4 landscape; margin: 10mm; }',
        ].join('\n');

        const cleanup = () => {
            if (styleEl && styleEl.parentNode) {
                styleEl.parentNode.removeChild(styleEl);
            }
            restoreOriginalTable();
            window.removeEventListener('afterprint', cleanup);
        };
        window.addEventListener('afterprint', cleanup);
        window.setTimeout(() => window.print(), 50);
    });
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
