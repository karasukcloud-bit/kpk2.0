<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/attendance.php';

require_curator_panel();

$groups = get_groups_for_curator();
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

if ($groupId === 0 && count($groups) === 1) {
    $groupId = (int) $groups[0]['id'];
}

$group = ($groupId > 0 && can_manage_group($groupId)) ? get_group_by_id($groupId) : null;
$students = $group ? get_students_by_group($groupId) : [];
$year = get_default_academic_year();
$monthOptions = get_academic_year_months($year);
$month = resolve_attendance_month($year, $_GET['month'] ?? null);
[$monthStart, $monthEnd] = attendance_month_date_bounds($month);
$reasons = get_attendance_reasons(true);
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
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
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
            <div class="alert alert--success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (empty($groups)): ?>
            <p class="text-muted">Вам ещё не назначена группа.</p>
        <?php elseif ($group === null): ?>
            <p class="text-muted">Сначала откройте вкладку «Список группы» и выберите группу.</p>
        <?php else: ?>
            <form method="get" class="form form--filter">
                <div class="form__row form__row--filter">
                    <?php if (count($groups) > 1): ?>
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
                    <?php else: ?>
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <?php endif; ?>
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
            <p class="attendance-journal-meta">
                Группа <strong><?= e($group['number']) ?></strong>
                · учебный год <?= e($year) ?>
                · <?= e(format_attendance_month($month)) ?>
                · студентов: <?= count($students) ?>
            </p>
            <p class="text-muted">
                Все пропуски сохраняются по датам и учебному году — их можно будет вывести в сводной таблице.
            </p>

            <div class="attendance-toolbar">
                <button type="button" class="btn btn--primary" data-attendance-add-toggle>
                    <?= $showForm && $editDayId === 0 ? 'Скрыть форму' : 'Добавить дату' ?>
                </button>
            </div>

            <div
                class="attendance-form<?= $showForm ? '' : ' attendance-form--hidden' ?>"
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

            <?php
            $attendanceReadOnly = false;
            $attendanceShowIntro = false;
            require __DIR__ . '/../includes/attendance/group_journal_display.php';
            ?>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
