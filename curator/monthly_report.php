<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/attendance.php';
require_once __DIR__ . '/../includes/curator_monthly_report.php';

require_curator_panel();

$ctx = resolve_curator_group_context(isset($_GET['group_id']) ? (int) $_GET['group_id'] : null);
$groups = $ctx['groups'];
$groupId = $ctx['group_id'];
$group = $ctx['group'];
$students = $group ? get_students_by_group($groupId) : [];
$error = flash_get('error') ?: $ctx['error'];
$success = flash_get('success');

$period = get_active_gradebook_period();
$academicYear = (string) ($period['academic_year'] ?? '');
$monthOptions = $academicYear !== '' ? get_academic_year_months($academicYear) : [];
$month = resolve_attendance_month($academicYear, isset($_GET['month']) ? (string) $_GET['month'] : null);

$studentCount = count($students);
$allowedIds = array_map(static fn (array $s): int => (int) $s['id'], $students);
$autoMetrics = $group
    ? curator_monthly_report_auto_metrics($groupId, $students, $academicYear, $month)
    : ['clubs' => 0, 'debts' => 0, 'unexcused' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $group !== null) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Ошибка безопасности. Обновите страницу и попробуйте снова.');
    } else {
        $postMonth = resolve_attendance_month($academicYear, (string) ($_POST['month'] ?? $month));
        $auto = curator_monthly_report_auto_metrics($groupId, $students, $academicYear, $postMonth);

        $rawEvents = $_POST['group_events'] ?? [];
        if (!is_array($rawEvents)) {
            $rawEvents = [];
        }

        $pick = static function (string $key) use ($allowedIds): array {
            $raw = $_POST[$key] ?? [];
            if (!is_array($raw)) {
                $raw = [];
            }
            return curator_monthly_report_normalize_student_ids(
                array_map('intval', $raw),
                $allowedIds
            );
        };

        $payload = curator_monthly_report_normalize_payload([
            'key_events_count' => (int) ($_POST['key_events_count'] ?? 0),
            'group_events' => $rawEvents,
            'sanctions' => $pick('sanctions'),
            'bad_habits' => $pick('bad_habits'),
            'appearance' => $pick('appearance'),
            'volunteers' => $pick('volunteers'),
            'cafeteria' => $pick('cafeteria'),
            'characteristics' => (string) ($_POST['characteristics'] ?? 'not_submitted'),
        ], $studentCount, $allowedIds, $auto);

        $user = current_user();
        $result = save_curator_monthly_report(
            $groupId,
            $postMonth,
            $payload,
            $user ? (int) $user['id'] : null
        );

        if (!empty($result['success'])) {
            flash_set('success', 'Отчёт сохранён. Баллов: ' . (int) ($result['total_points'] ?? 0) . '.');
        } else {
            flash_set('error', $result['error'] ?? 'Не удалось сохранить отчёт.');
        }

        header(
            'Location: monthly_report.php?group_id=' . $groupId
            . '&month=' . urlencode($postMonth)
            . '&mode=view&focus=1'
        );
        exit;
    }

    header(
        'Location: monthly_report.php?group_id=' . $groupId
        . '&month=' . urlencode($month)
        . '&mode=edit&focus=1'
    );
    exit;
}

$saved = $group ? get_curator_monthly_report($groupId, $month) : null;
$hasSaved = $saved !== null;
$mode = (string) ($_GET['mode'] ?? '');
if (!in_array($mode, ['view', 'edit'], true)) {
    $mode = $hasSaved ? 'view' : 'edit';
}
if (!$hasSaved) {
    $mode = 'edit';
}

$payload = curator_monthly_report_normalize_payload(
    is_array($saved['payload'] ?? null) ? $saved['payload'] : [],
    $studentCount,
    $allowedIds,
    $autoMetrics
);
$scored = curator_monthly_report_score_payload($payload, $studentCount);
$itemMeta = curator_monthly_report_item_meta();
$groupReportMonths = $group
    ? list_curator_monthly_reports_for_group($groupId, $monthOptions)
    : [];
$submittedMonths = array_values(array_filter(
    $groupReportMonths,
    static fn (array $row): bool => !empty($row['has_report'])
));
$studentNameMap = [];
foreach ($students as $student) {
    $studentNameMap[(int) $student['id']] = (string) ($student['full_name'] ?? '');
}

$monthQuery = static function (string $targetMonth, string $targetMode) use ($groupId): string {
    return 'monthly_report.php?' . http_build_query([
        'group_id' => $groupId,
        'month' => $targetMonth,
        'mode' => $targetMode,
        'focus' => 1,
    ]) . '#report-editor';
};
$shouldFocusEditor = (string) ($_GET['focus'] ?? '') === '1';
$isAjax = (string) ($_GET['ajax'] ?? '') === '1';

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    if ($group === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Группа не выбрана.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    ob_start();
    require __DIR__ . '/../includes/curator_monthly_report_editor.php';
    $html = ob_get_clean();
    echo json_encode([
        'success' => true,
        'html' => $html,
        'month' => $month,
        'mode' => $mode,
        'has_saved' => $hasSaved,
        'total_points' => (int) ($hasSaved
            ? ($saved['total_points'] ?? $scored['total_points'])
            : $scored['total_points']),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$pageTitle = 'Отчёт по группе — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'monthly_report';
$curatorGroupId = $groupId;
$curatorGroups = $groups;
require __DIR__ . '/../includes/header.php';

$studentOptionsHtml = '';
foreach ($students as $student) {
    $studentOptionsHtml .= '<option value="' . (int) $student['id'] . '">'
        . e((string) $student['full_name']) . '</option>';
}
?>

<div class="dashboard dashboard--wide curator-monthly-report-page" data-monthly-report-page>
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель куратора</h1>
                <p class="text-muted">Ежемесячный отчёт по воспитательной работе группы</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/curator_nav.php'; ?>
    </section>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($groups === []): ?>
        <section class="panel">
            <p class="text-muted">Вам ещё не назначена группа. Обратитесь к администратору.</p>
        </section>
    <?php elseif ($group === null): ?>
        <section class="panel">
            <p class="text-muted">Выберите группу, чтобы заполнить отчёт.</p>
        </section>
    <?php else: ?>
        <section class="panel">
            <div class="panel__header panel__header--compact">
                <div>
                    <h2>Отчёты группы <?= e((string) ($group['number'] ?? '')) ?></h2>
                    <p class="text-muted">
                        Студентов: <?= (int) $studentCount ?> ·
                        Учебный год <?= e($academicYear) ?> ·
                        Сдано: <?= count($submittedMonths) ?> из <?= count($groupReportMonths) ?>.
                    </p>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Месяц</th>
                            <th>Статус</th>
                            <th>Баллы</th>
                            <th class="table__actions-col">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupReportMonths as $monthRow): ?>
                        <tr
                            class="<?= !empty($monthRow['has_report']) ? 'curator-report-row--done' : 'curator-report-row--missing' ?><?= $monthRow['month'] === $month ? ' is-selected-month' : '' ?>"
                            data-month-row="<?= e($monthRow['month']) ?>"
                        >
                            <td><?= e($monthRow['label']) ?></td>
                            <td>
                                <?php if (!empty($monthRow['has_report'])): ?>
                                    <span class="badge badge--success">Сдан</span>
                                <?php else: ?>
                                    <span class="badge badge--gradebook-incomplete">Не сдан</span>
                                <?php endif; ?>
                            </td>
                            <td data-month-points>
                                <?= !empty($monthRow['has_report'])
                                    ? '<strong>' . (int) $monthRow['total_points'] . '</strong>'
                                    : '—' ?>
                            </td>
                            <td class="table__actions-col">
                                <?php if (!empty($monthRow['has_report'])): ?>
                                    <a
                                        class="btn btn--ghost btn--sm"
                                        href="<?= e($monthQuery($monthRow['month'], 'view')) ?>"
                                        data-report-nav
                                        data-month="<?= e($monthRow['month']) ?>"
                                        data-mode="view"
                                    >Открыть</a>
                                    <a
                                        class="btn btn--ghost btn--sm"
                                        href="<?= e($monthQuery($monthRow['month'], 'edit')) ?>"
                                        data-report-nav
                                        data-month="<?= e($monthRow['month']) ?>"
                                        data-mode="edit"
                                    >Изменить</a>
                                <?php else: ?>
                                    <a
                                        class="btn btn--primary btn--sm"
                                        href="<?= e($monthQuery($monthRow['month'], 'edit')) ?>"
                                        data-report-nav
                                        data-month="<?= e($monthRow['month']) ?>"
                                        data-mode="edit"
                                    >Заполнить</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section
            class="panel"
            id="report-editor"
            data-report-editor
            data-group-id="<?= (int) $groupId ?>"
        >
            <?php require __DIR__ . '/../includes/curator_monthly_report_editor.php'; ?>
        </section>
    <?php endif; ?>
</div>

<?php if ($group): ?>
<template id="monthly-report-event-row">
    <div class="curator-monthly-report-events__row" data-event-row>
        <input type="text" name="group_events[]" maxlength="255" placeholder="Название мероприятия" data-event-title>
        <button type="button" class="btn btn--ghost btn--sm" data-event-remove title="Удалить">×</button>
    </div>
</template>
<template id="monthly-report-student-row">
    <div class="curator-monthly-report-students__row" data-student-row>
        <select name="__KEY__[]" data-student-select>
            <option value="">— Выберите студента —</option>
            <?= $studentOptionsHtml ?>
        </select>
        <button type="button" class="btn btn--ghost btn--sm" data-student-remove title="Удалить">×</button>
    </div>
</template>
<script>
(() => {
    const page = document.querySelector('[data-monthly-report-page]');
    const editor = document.querySelector('[data-report-editor]');
    if (!page || !editor) {
        return;
    }

    const groupId = editor.dataset.groupId || '';
    const eventTpl = document.getElementById('monthly-report-event-row');
    const studentTpl = document.getElementById('monthly-report-student-row');
    let loading = false;
    let formCleanup = null;

    const scrollToEditor = () => {
        editor.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const markSelectedMonth = (month) => {
        page.querySelectorAll('[data-month-row]').forEach((row) => {
            row.classList.toggle('is-selected-month', row.dataset.monthRow === month);
        });
    };

    const buildUrl = (month, mode, withAjax) => {
        const params = new URLSearchParams({
            group_id: groupId,
            month,
            mode,
            focus: '1',
        });
        if (withAjax) {
            params.set('ajax', '1');
        }
        const base = 'monthly_report.php?' + params.toString();
        return withAjax ? base : (base + '#report-editor');
    };

    const percentOf = (part, total) => {
        if (!total || total <= 0) {
            return 0;
        }
        return Math.round((part / total) * 1000) / 10;
    };

    const pointsByPercent = (percent, inverted) => {
        if (!inverted && percent <= 0) {
            return 0;
        }
        let band = 1;
        if (percent > 75) band = 4;
        else if (percent > 50) band = 3;
        else if (percent > 25) band = 2;
        return inverted ? (5 - band) : band;
    };

    const initForm = () => {
        if (typeof formCleanup === 'function') {
            formCleanup();
            formCleanup = null;
        }

        const form = editor.querySelector('[data-monthly-report]');
        if (!form) {
            return;
        }

        const studentCount = Number(form.dataset.studentCount || 0);
        const autoCounts = {
            clubs: Number(form.dataset.autoClubs || 0),
            debts: Number(form.dataset.autoDebts || 0),
            unexcused: Number(form.dataset.autoUnexcused || 0),
        };

        const countFilledEvents = () => {
            let n = 0;
            form.querySelectorAll('[data-event-title]').forEach((input) => {
                if (String(input.value || '').trim() !== '') {
                    n += 1;
                }
            });
            return n;
        };

        const countSelectedStudents = (root) => {
            const seen = {};
            let n = 0;
            root.querySelectorAll('[data-student-select]').forEach((select) => {
                const id = Number(select.value || 0);
                if (id > 0 && !seen[id]) {
                    seen[id] = true;
                    n += 1;
                }
            });
            return n;
        };

        const syncStudentSelects = (root) => {
            if (!root) {
                return;
            }
            const selects = [...root.querySelectorAll('[data-student-select]')];
            const taken = {};
            selects.forEach((select) => {
                const id = String(select.value || '');
                if (id !== '' && taken[id]) {
                    select.value = '';
                } else if (id !== '') {
                    taken[id] = true;
                }
            });
            selects.forEach((select) => {
                const current = String(select.value || '');
                select.querySelectorAll('option').forEach((option) => {
                    const value = String(option.value || '');
                    if (value === '') {
                        option.disabled = false;
                        return;
                    }
                    option.disabled = value !== current && !!taken[value];
                });
            });
            const item = root.closest('[data-report-item]');
            const addBtn = item && item.querySelector('[data-student-add]');
            if (addBtn) {
                addBtn.disabled = countSelectedStudents(root) >= studentCount;
            }
        };

        const syncAllStudentSelects = () => {
            form.querySelectorAll('[data-students-root]').forEach((root) => syncStudentSelects(root));
        };

        const recalc = () => {
            syncAllStudentSelects();
            let total = 0;
            form.querySelectorAll('[data-report-item]').forEach((item) => {
                const key = item.dataset.reportItem;
                const type = item.dataset.itemType;
                const inverted = item.dataset.inverted === '1';
                let count = 0;
                let percent = 0;
                let points = 1;

                if (type === 'count') {
                    const input = item.querySelector('[data-count-input]');
                    count = Math.max(0, Math.min(studentCount, Number(input && input.value ? input.value : 0)));
                    if (input && Number(input.value) !== count) {
                        input.value = String(count);
                    }
                    percent = percentOf(count, studentCount);
                    points = pointsByPercent(percent, inverted);
                } else if (type === 'events') {
                    count = countFilledEvents();
                    percent = Math.min(100, percentOf(count, 4));
                    points = pointsByPercent(percent, inverted);
                    const counter = item.querySelector('[data-events-count]');
                    if (counter) {
                        counter.textContent = String(count);
                    }
                } else if (type === 'auto') {
                    count = Number(autoCounts[key] || 0);
                    percent = percentOf(count, studentCount);
                    points = pointsByPercent(percent, inverted);
                } else if (type === 'students') {
                    const root = item.querySelector('[data-students-root]');
                    count = root ? countSelectedStudents(root) : 0;
                    percent = percentOf(count, studentCount);
                    points = pointsByPercent(percent, inverted);
                    const counter = item.querySelector('[data-students-count]');
                    if (counter) {
                        counter.textContent = String(count);
                    }
                } else if (type === 'characteristics') {
                    const checked = item.querySelector('[data-char-input]:checked');
                    const submitted = checked && checked.value === 'submitted';
                    percent = submitted ? 100 : 0;
                    points = submitted ? 4 : 0;
                }

                const percentNode = item.querySelector('[data-item-percent]');
                const pointsNode = item.querySelector('[data-item-points]');
                if (percentNode) {
                    percentNode.textContent = String(percent);
                }
                if (pointsNode) {
                    pointsNode.textContent = String(points);
                }
                total += points;
            });

            const totalNode = form.querySelector('[data-total-points]');
            if (totalNode) {
                totalNode.textContent = String(total);
            }
        };

        const onInput = () => recalc();
        const onClick = (event) => {
            const addEventBtn = event.target.closest('[data-event-add]');
            if (addEventBtn && eventTpl) {
                const root = form.querySelector('[data-events-root]');
                if (root) {
                    root.insertAdjacentHTML('beforeend', eventTpl.innerHTML.trim());
                    recalc();
                }
                return;
            }

            const removeEventBtn = event.target.closest('[data-event-remove]');
            if (removeEventBtn) {
                const root = form.querySelector('[data-events-root]');
                const row = removeEventBtn.closest('[data-event-row]');
                if (root && row) {
                    if (root.querySelectorAll('[data-event-row]').length <= 1) {
                        const input = row.querySelector('[data-event-title]');
                        if (input) {
                            input.value = '';
                        }
                    } else {
                        row.remove();
                    }
                    recalc();
                }
                return;
            }

            const addStudentBtn = event.target.closest('[data-student-add]');
            if (addStudentBtn && studentTpl) {
                if (addStudentBtn.disabled) {
                    return;
                }
                const item = addStudentBtn.closest('[data-report-item]');
                const root = item && item.querySelector('[data-students-root]');
                const key = root && root.dataset.studentsKey;
                if (root && key) {
                    if (countSelectedStudents(root) >= studentCount) {
                        return;
                    }
                    root.insertAdjacentHTML('beforeend', studentTpl.innerHTML.replaceAll('__KEY__', key).trim());
                    recalc();
                }
                return;
            }

            const removeStudentBtn = event.target.closest('[data-student-remove]');
            if (removeStudentBtn) {
                const root = removeStudentBtn.closest('[data-students-root]');
                const row = removeStudentBtn.closest('[data-student-row]');
                if (root && row) {
                    if (root.querySelectorAll('[data-student-row]').length <= 1) {
                        const select = row.querySelector('[data-student-select]');
                        if (select) {
                            select.value = '';
                        }
                    } else {
                        row.remove();
                    }
                    recalc();
                }
            }
        };

        const onCharChange = (event) => {
            const wrap = event.target.closest('.curator-monthly-report-chars');
            if (!wrap) {
                return;
            }
            wrap.querySelectorAll('.summary-report-filters__tab').forEach((tab) => {
                const input = tab.querySelector('input');
                tab.classList.toggle('is-active', !!(input && input.checked));
            });
        };

        form.addEventListener('input', onInput);
        form.addEventListener('change', onInput);
        form.addEventListener('click', onClick);
        form.addEventListener('change', onCharChange);
        recalc();

        formCleanup = () => {
            form.removeEventListener('input', onInput);
            form.removeEventListener('change', onInput);
            form.removeEventListener('click', onClick);
            form.removeEventListener('change', onCharChange);
        };
    };

    const loadReport = async (month, mode, pushState) => {
        if (loading) {
            return;
        }
        loading = true;
        editor.classList.add('is-loading');
        try {
            const response = await fetch(buildUrl(month, mode, true), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();
            if (!data || !data.success) {
                throw new Error((data && data.error) || 'Не удалось загрузить отчёт.');
            }
            editor.innerHTML = data.html;
            markSelectedMonth(data.month);
            initForm();
            if (pushState) {
                history.pushState(
                    { month: data.month, mode: data.mode },
                    '',
                    buildUrl(data.month, data.mode, false)
                );
            }
            scrollToEditor();
        } catch (error) {
            window.location.href = buildUrl(month, mode, false);
        } finally {
            editor.classList.remove('is-loading');
            loading = false;
        }
    };

    page.addEventListener('click', (event) => {
        const link = event.target.closest('[data-report-nav]');
        if (!link || !page.contains(link)) {
            return;
        }
        event.preventDefault();
        const month = link.dataset.month || '';
        const mode = link.dataset.mode || 'view';
        if (!month) {
            return;
        }
        loadReport(month, mode, true);
    });

    window.addEventListener('popstate', (event) => {
        const state = event.state;
        if (state && state.month && state.mode) {
            loadReport(state.month, state.mode, false);
            return;
        }
        const params = new URLSearchParams(window.location.search);
        const month = params.get('month');
        const mode = params.get('mode') || 'view';
        if (month) {
            loadReport(month, mode, false);
        }
    });

    initForm();
    <?php if ($shouldFocusEditor): ?>
    setTimeout(scrollToEditor, 50);
    <?php endif; ?>
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
