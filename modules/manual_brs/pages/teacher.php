<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/students.php';
require_once __DIR__ . '/../../../includes/journal.php';
require_once __DIR__ . '/../../../includes/grading.php';
require_once __DIR__ . '/../bootstrap.php';

require_teacher_panel();
manual_brs_ensure_schema();

$user = current_user();
$activePeriod = get_active_gradebook_period();
$academicYear = $activePeriod['academic_year'];

$period = isset($_GET['period'])
    ? manual_brs_normalize_period((int) $_GET['period'])
    : manual_brs_default_period_for_semester($activePeriod['semester']);
$periodMeta = manual_brs_period_meta($period);
$semester = manual_brs_semester_from_period($period);

$groups = get_journal_groups($academicYear, $semester);
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
$error = null;
$success = null;

$selectedGroup = null;
foreach ($groups as $group) {
    if ((int) $group['group_id'] === $groupId) {
        $selectedGroup = $group;
        break;
    }
}
if ($groupId > 0 && $selectedGroup === null) {
    $error = 'Группа не найдена или недоступна.';
    $groupId = 0;
    $itemId = 0;
}

$assignment = null;
if ($selectedGroup !== null && $itemId > 0) {
    foreach ($selectedGroup['subjects'] as $row) {
        if ((int) $row['curriculum_item_id'] === $itemId) {
            $assignment = $row;
            break;
        }
    }
    if ($assignment === null) {
        $error = 'Предмет не найден в учебном плане выбранного периода.';
        $itemId = 0;
    }
}

$url = static function (array $params = []) use ($period): string {
    $base = [
        'period' => $period,
        'group_id' => $params['group_id'] ?? null,
        'item_id' => $params['item_id'] ?? null,
    ];
    foreach ($params as $key => $value) {
        $base[$key] = $value;
    }
    $query = array_filter($base, static function ($value) {
        return $value !== null && $value !== '' && $value !== 0;
    });

    return 'manual_brs.php' . ($query !== [] ? '?' . http_build_query($query) : '');
};

$sheet = null;
$students = [];
$rows = [];
$brsWeights = manual_brs_weights();

if ($assignment !== null) {
    $sheet = manual_brs_get_or_create_sheet($academicYear, $period, $groupId, $itemId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $assignment !== null && $sheet !== null) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'save_lessons_total') {
            $result = manual_brs_update_lessons_total(
                (int) $sheet['id'],
                (int) ($_POST['lessons_total'] ?? 0)
            );
            if ($result['success']) {
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isAjax || (($_POST['ajax'] ?? '') === '1')) {
                    $entries = manual_brs_get_entries_map((int) $sheet['id']);
                    $students = get_students_by_group($groupId);
                    $builtRows = manual_brs_build_student_rows(
                        $students,
                        $entries,
                        $academicYear,
                        $itemId,
                        $period
                    );
                    $rowsPayload = [];
                    foreach ($builtRows as $row) {
                        $sid = (int) $row['student']['id'];
                        $rowsPayload[$sid] = [
                            'period_display' => $row['display'],
                            'points' => $row['points'],
                            'grade' => $row['grade'],
                            'pa_grade' => $row['pa_grade'],
                            'semester_html' => $row['semester_html'],
                            'semester_grade' => $row['semester_grade'],
                            'semester_points' => $row['semester_points'],
                        ];
                    }
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => true,
                        'lessons_total' => (int) ($result['lessons_total'] ?? 0),
                        'rows' => $rowsPayload,
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                flash_set('success', 'Число занятий периода сохранено.');
                header('Location: ' . $url(['group_id' => $groupId, 'item_id' => $itemId, 'period' => $period]));
                exit;
            }
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax || (($_POST['ajax'] ?? '') === '1')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => $result['error'] ?? 'Не удалось сохранить.',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $error = $result['error'] ?? 'Не удалось сохранить.';
        } elseif ($action === 'save_entry') {
            $studentId = (int) ($_POST['student_id'] ?? 0);
            $result = manual_brs_save_entry(
                (int) $sheet['id'],
                $studentId,
                $_POST,
                (int) ($sheet['lessons_total'] ?? 0)
            );
            if ($result['success']) {
                $paRaw = $_POST['pa_grade'] ?? '';
                $paResult = manual_brs_save_attestation(
                    $academicYear,
                    $semester,
                    $itemId,
                    $studentId,
                    $paRaw === '' ? null : $paRaw
                );
                if (!$paResult['success']) {
                    $error = $paResult['error'] ?? 'Не удалось сохранить ПА.';
                } else {
                    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                        && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                    if ($isAjax || (($_POST['ajax'] ?? '') === '1')) {
                        header('Content-Type: application/json; charset=utf-8');
                        $paGrade = $paResult['grade'];
                        $periodPoints = isset($result['calc']['points'])
                            ? (float) $result['calc']['points']
                            : null;
                        $sem = manual_brs_semester_total($periodPoints, null, $paGrade);
                        $periodDisplay = $result['calc']['display'] ?? '';
                        echo json_encode([
                            'success' => true,
                            'calc' => $result['calc'],
                            'current_period' => $period,
                            'period_display' => $periodDisplay,
                            'pa_grade' => $paGrade,
                            'semester_display' => $sem['display'],
                            'semester_grade' => $sem['grade'],
                            'semester_points' => $sem['points'],
                            'semester_html' => $sem['html'],
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    flash_set('success', 'Данные БРС сохранены.');
                    header('Location: ' . $url(['group_id' => $groupId, 'item_id' => $itemId, 'period' => $period]));
                    exit;
                }
            } else {
                $error = $result['error'] ?? 'Не удалось сохранить.';
            }
        } elseif ($action === 'save_pa') {
            $studentId = (int) ($_POST['student_id'] ?? 0);
            $paRaw = $_POST['pa_grade'] ?? '';
            $result = manual_brs_save_attestation(
                $academicYear,
                $semester,
                $itemId,
                $studentId,
                $paRaw === '' ? null : $paRaw
            );
            if ($result['success']) {
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isAjax || (($_POST['ajax'] ?? '') === '1')) {
                    header('Content-Type: application/json; charset=utf-8');
                    $periodData = manual_brs_points_for_periods(
                        $academicYear,
                        $itemId,
                        $studentId,
                        [$period]
                    );
                    $periodPoints = $periodData[$period]['points'] ?? null;
                    $sem = manual_brs_semester_total($periodPoints, null, $result['grade']);
                    $periodDisplay = $periodData[$period]['display'] ?? '';
                    echo json_encode([
                        'success' => true,
                        'pa_grade' => $result['grade'],
                        'period_display' => $periodDisplay,
                        'semester_display' => $sem['display'],
                        'semester_grade' => $sem['grade'],
                        'semester_points' => $sem['points'],
                        'semester_html' => $sem['html'],
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                flash_set('success', 'ПА сохранена.');
                header('Location: ' . $url(['group_id' => $groupId, 'item_id' => $itemId, 'period' => $period]));
                exit;
            }
            $error = $result['error'] ?? 'Не удалось сохранить ПА.';
        }
    }
    $sheet = manual_brs_get_or_create_sheet($academicYear, $period, $groupId, $itemId);
}

if ($assignment !== null && $sheet !== null) {
    $students = get_students_by_group($groupId);
    $entries = manual_brs_get_entries_map((int) $sheet['id']);
    $rows = manual_brs_build_student_rows($students, $entries, $academicYear, $itemId, $period);
}

$flashSuccess = flash_get('success');
if ($flashSuccess) {
    $success = $flashSuccess;
}

$pageTitle = 'Ручное БРС — Панель преподавателя';
$showHeader = true;
$basePath = '../';
$currentTeacherTab = 'manual_brs';
require __DIR__ . '/../../../includes/header.php';
?>

<link rel="stylesheet" href="<?= e(manual_brs_asset_url('manual_brs.css', $basePath)) ?>">

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Ручное выставление БРС</h1>
                <p class="text-muted">
                    Временный модуль · <?= e($academicYear) ?> · <?= e($periodMeta['label']) ?>
                </p>
            </div>
        </div>
        <?php require __DIR__ . '/../../../includes/teacher_nav.php'; ?>
    </section>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <section class="panel">
        <form method="get" class="form form--inline manual-brs-period-bar">
            <?php if ($groupId > 0): ?>
                <input type="hidden" name="group_id" value="<?= $groupId ?>">
            <?php endif; ?>
            <?php if ($itemId > 0): ?>
                <input type="hidden" name="item_id" value="<?= $itemId ?>">
            <?php endif; ?>
            <div class="form__group">
                <label for="manual_brs_period">Семестр</label>
                <select id="manual_brs_period" name="period" onchange="this.form.submit()">
                    <?= manual_brs_render_period_options($period) ?>
                </select>
            </div>
            <p class="text-muted manual-brs-period-hint">
                Два периода в учебном году: 1-й и 2-й семестр. Итог семестра — по баллам выбранного семестра и ПА.
            </p>
        </form>
    </section>

    <?php if ($assignment !== null && $sheet !== null): ?>
        <section class="panel">
            <div class="panel__header panel__header--compact">
                <div>
                    <h2><?= e($assignment['subject_name'] ?? $assignment['name'] ?? 'Предмет') ?></h2>
                    <p class="text-muted">
                        Группа <?= e($selectedGroup['group_number']) ?>
                        · <?= e($periodMeta['label']) ?>
                    </p>
                </div>
                <div class="panel__header-actions">
                    <a href="<?= e($url(['group_id' => $groupId, 'period' => $period])) ?>" class="btn btn--ghost btn--sm">← К предметам</a>
                </div>
            </div>

            <form method="post" class="form form--inline manual-brs-lessons-form" data-manual-brs-lessons-form>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_lessons_total">
                <input type="hidden" name="ajax" value="1">
                <div class="form__group">
                    <label for="lessons_total">Занятий в периоде (для посещаемости)</label>
                    <input
                        type="number"
                        id="lessons_total"
                        name="lessons_total"
                        min="0"
                        max="200"
                        value="<?= (int) $sheet['lessons_total'] ?>"
                        required
                        data-manual-brs-lessons-input
                    >
                    <p class="form__hint" data-manual-brs-lessons-status></p>
                </div>
            </form>

            <?php if ($students === []): ?>
                <p class="text-muted">В группе нет студентов.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table manual-brs-table">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Студент</th>
                                <th>Баллы (<?= e($periodMeta['label']) ?>)</th>
                                <th>ПА</th>
                                <th>Итог семестра</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $index => $row): ?>
                                <?php $st = $row['student']; ?>
                                <tr
                                    class="manual-brs-row"
                                    data-manual-brs-open
                                    data-student-id="<?= (int) $st['id'] ?>"
                                    data-student-name="<?= e($st['full_name']) ?>"
                                    data-current-marks="<?= e($row['current_marks_input']) ?>"
                                    data-control-marks="<?= e($row['control_marks_input']) ?>"
                                    data-absent="<?= (int) $row['absent_count'] ?>"
                                    data-late="<?= (int) $row['late_count'] ?>"
                                    data-activity="<?= (int) $row['activity_count'] ?>"
                                    data-pa-grade="<?= $row['pa_grade'] !== null ? (int) $row['pa_grade'] : '' ?>"
                                    data-points="<?= $row['points'] !== null ? e(manual_brs_format_number($row['points'], 1)) : '' ?>"
                                    data-grade="<?= $row['grade'] !== null ? (int) $row['grade'] : '' ?>"
                                    data-semester-display="<?= e($row['semester_display']) ?>"
                                >
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= e($st['full_name']) ?></strong></td>
                                    <td data-manual-brs-period-cell><?= $row['display'] !== '' ? e($row['display']) : '—' ?></td>
                                    <td class="manual-brs-pa-cell" onclick="event.stopPropagation();">
                                        <select
                                            class="manual-brs-pa-select"
                                            data-manual-brs-pa
                                            data-student-id="<?= (int) $st['id'] ?>"
                                            aria-label="Промежуточная аттестация"
                                        >
                                            <option value=""<?= $row['pa_grade'] === null ? ' selected' : '' ?>>—</option>
                                            <?php foreach ([2, 3, 4, 5] as $paOpt): ?>
                                            <option value="<?= $paOpt ?>"<?= (int) $row['pa_grade'] === $paOpt ? ' selected' : '' ?>><?= $paOpt ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="manual-brs-semester-cell" data-manual-brs-semester-cell><?= $row['semester_html'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted">
                    Нажмите на строку студента, чтобы заполнить данные выбранного семестра.
                    ПА: пусто — итог по баллам БРС; «2» — итог 2; «3–5» — среднее оценки БРС и ПА.
                </p>
            <?php endif; ?>
        </section>

        <div class="modal" data-manual-brs-modal hidden>
            <div class="modal__backdrop" data-manual-brs-close></div>
            <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="manual-brs-modal-title">
                <div class="modal__header">
                    <h2 id="manual-brs-modal-title">БРС · <span data-manual-brs-student-label></span></h2>
                    <button type="button" class="modal__close" data-manual-brs-close aria-label="Закрыть">&times;</button>
                </div>
                <form method="post" class="form" data-manual-brs-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_entry">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="student_id" value="" data-manual-brs-student-id>

                    <div class="form__group">
                        <label for="mbrs_current">Текущие отметки (2–5)</label>
                        <input type="text" id="mbrs_current" name="current_marks" data-manual-brs-current
                               placeholder="5453" inputmode="numeric" pattern="[2-5]*" autocomplete="off">
                        <p class="form__hint">Только цифры 2–5, без пробелов. Среднее: <strong data-manual-brs-current-avg>—</strong></p>
                    </div>

                    <div class="form__group">
                        <label for="mbrs_control">Контрольные работы (2–5)</label>
                        <input type="text" id="mbrs_control" name="control_marks" data-manual-brs-control
                               placeholder="45" inputmode="numeric" pattern="[2-5]*" autocomplete="off">
                        <p class="form__hint">Только цифры 2–5, без пробелов. Среднее: <strong data-manual-brs-control-avg>—</strong></p>
                    </div>

                    <div class="form__row">
                        <div class="form__group">
                            <label for="mbrs_absent">Пропуски (Н)</label>
                            <input type="number" id="mbrs_absent" name="absent_count" min="0" max="200"
                                   value="0" data-manual-brs-absent>
                        </div>
                        <div class="form__group">
                            <label for="mbrs_late">Опоздания</label>
                            <input type="number" id="mbrs_late" name="late_count" min="0" max="200"
                                   value="0" data-manual-brs-late>
                        </div>
                        <div class="form__group">
                            <label for="mbrs_activity">Активность (уроков)</label>
                            <input type="number" id="mbrs_activity" name="activity_count" min="0" max="200"
                                   value="0" data-manual-brs-activity>
                        </div>
                    </div>

                    <div class="form__group">
                        <label for="mbrs_pa">Промежуточная аттестация (ПА)</label>
                        <select id="mbrs_pa" name="pa_grade" data-manual-brs-pa-modal>
                            <option value="">— Не выставлена —</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>

                    <div class="manual-brs-result" data-manual-brs-preview>
                        <div>Итог периода: <strong data-manual-brs-total>—</strong></div>
                        <div class="text-muted">Занятий в периоде: <span data-manual-brs-lessons-label><?= (int) $sheet['lessons_total'] ?></span></div>
                    </div>

                    <div class="form__actions">
                        <button type="submit" class="btn btn--primary">Сохранить</button>
                        <button type="button" class="btn btn--ghost" data-manual-brs-close>Отмена</button>
                    </div>
                    <p class="text-muted" data-manual-brs-form-status></p>
                </form>
            </div>
        </div>

        <form method="post" hidden data-manual-brs-pa-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_pa">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="student_id" value="" data-manual-brs-pa-student>
            <input type="hidden" name="pa_grade" value="" data-manual-brs-pa-value>
        </form>

        <script>
        window.MANUAL_BRS_CONFIG = <?= json_encode([
            'lessonsTotal' => (int) $sheet['lessons_total'],
            'brs' => $brsWeights,
            'currentPeriod' => $period,
        ], JSON_UNESCAPED_UNICODE) ?>;
        </script>
        <script src="<?= e(manual_brs_asset_url('manual_brs.js', $basePath)) ?>?v=20260916c"></script>

    <?php elseif ($selectedGroup !== null): ?>
        <section class="panel">
            <div class="panel__header panel__header--compact">
                <div>
                    <h2>Группа <?= e($selectedGroup['group_number']) ?></h2>
                    <p class="text-muted">Выберите предмет · <?= e($periodMeta['label']) ?></p>
                </div>
                <a href="<?= e($url(['period' => $period])) ?>" class="btn btn--ghost btn--sm">← К группам</a>
            </div>
            <?php if (empty($selectedGroup['subjects'])): ?>
                <p class="text-muted">Нет предметов учебного плана на <?= e($periodMeta['semester_label']) ?>.</p>
            <?php else: ?>
                <div class="journal-choice-grid">
                    <?php foreach ($selectedGroup['subjects'] as $subject): ?>
                        <a
                            class="journal-choice"
                            href="<?= e($url([
                                'group_id' => $groupId,
                                'item_id' => (int) $subject['curriculum_item_id'],
                                'period' => $period,
                            ])) ?>"
                        >
                            <strong><?= e($subject['subject_name']) ?></strong>
                            <span class="text-muted"><?= e(semester_label((string) ($subject['semester'] ?? $semester))) ?></span>
                            <?php if (!empty($subject['teacher_name'])): ?>
                            <span class="journal-choice__meta">Преп.: <?= e($subject['teacher_name']) ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="panel">
            <h2>Выберите группу</h2>
            <?php if ($groups === []): ?>
                <p class="text-muted">Нет групп с учебным планом.</p>
            <?php else: ?>
                <div class="journal-choice-grid">
                    <?php foreach ($groups as $group): ?>
                        <a
                            class="journal-choice"
                            href="<?= e($url(['group_id' => (int) $group['group_id'], 'period' => $period])) ?>"
                        >
                            <strong><?= e($group['group_number']) ?></strong>
                            <span class="text-muted"><?= e($group['specialty_name'] ?? '') ?></span>
                            <span class="journal-choice__meta"><?= count($group['subjects'] ?? []) ?> предм.</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../../includes/footer.php'; ?>
