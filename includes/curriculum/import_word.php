<?php

declare(strict_types=1);

$curriculumPanel = $curriculumPanel ?? 'admin';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../curriculum.php';
require_once __DIR__ . '/../curriculum_word_import.php';
require_once __DIR__ . '/../teachers.php';

require_curriculum_manager();

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : (int) ($_POST['group_id'] ?? 0);
$academicYear = normalize_academic_year($_GET['year'] ?? $_POST['year'] ?? '') ?? get_default_academic_year();
$group = get_group_by_id($groupId);

if ($group === null) {
    flash_set('error', 'Группа не найдена.');
    header('Location: curriculum.php?year=' . urlencode($academicYear));
    exit;
}

$planResult = get_or_create_curriculum_plan($groupId, $academicYear);
if (!$planResult['success']) {
    flash_set('error', $planResult['error']);
    header('Location: curriculum.php?year=' . urlencode($academicYear));
    exit;
}

$planId = (int) $planResult['plan_id'];
$backUrl = 'curriculum_edit.php?group_id=' . $groupId . '&year=' . urlencode($academicYear) . '&tab=subjects';
$sessionKey = 'curriculum_word_import_' . $planId;
$error = null;
$preview = [];
$importMode = 'list';
$defaultSemester = '1';
$teachers = get_all_teachers();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $defaultSemester = validate_semester((string) ($_POST['default_semester'] ?? '1'))
            ? (string) $_POST['default_semester']
            : '1';

        if ($action === 'parse') {
            $source = (string) ($_POST['source'] ?? 'file');
            if ($source === 'text') {
                $parsed = parse_curriculum_subjects_from_text((string) ($_POST['list_text'] ?? ''));
            } else {
                $parsed = parse_curriculum_subjects_from_uploaded_word($_FILES['word_file'] ?? []);
            }
            if (!$parsed['success']) {
                $error = $parsed['error'] ?? 'Не удалось разобрать документ.';
                unset($_SESSION[$sessionKey]);
            } else {
                $rows = $parsed['rows'] ?? [];
                if ($source === 'text' || ($parsed['mode'] ?? '') === 'list') {
                    foreach ($rows as &$row) {
                        $row['semester'] = $defaultSemester;
                    }
                    unset($row);
                }
                $_SESSION[$sessionKey] = [
                    'mode' => (string) ($parsed['mode'] ?? 'list'),
                    'rows' => $rows,
                    'default_semester' => $defaultSemester,
                ];
                header('Location: curriculum_import.php?group_id=' . $groupId . '&year=' . urlencode($academicYear));
                exit;
            }
        } elseif ($action === 'import') {
            $selected = $_POST['selected'] ?? [];
            if (!is_array($selected)) {
                $selected = [];
            }
            $names = $_POST['name'] ?? [];
            $semesters = $_POST['semester'] ?? [];
            $teacherIds = $_POST['teacher_id'] ?? [];
            if (!is_array($names)) {
                $names = [];
            }
            if (!is_array($semesters)) {
                $semesters = [];
            }
            if (!is_array($teacherIds)) {
                $teacherIds = [];
            }
            $rows = [];
            foreach ($selected as $index) {
                $i = (int) $index;
                $rows[] = [
                    'name' => (string) ($names[$i] ?? ''),
                    'semester' => (string) ($semesters[$i] ?? $defaultSemester),
                    'teacher_id' => (int) ($teacherIds[$i] ?? 0),
                ];
            }
            if ($rows === []) {
                $error = 'Отметьте хотя бы один предмет для добавления.';
            } else {
                $result = import_curriculum_subjects_rows($planId, $rows);
                if ($result['success']) {
                    $added = (int) ($result['added'] ?? 0);
                    $skipped = (int) ($result['skipped'] ?? 0);
                    $msg = 'Добавлено предметов: ' . $added . '.';
                    if ($skipped > 0) {
                        $msg .= ' Пропущено (уже в плане или пустые): ' . $skipped . '.';
                    }
                    $errList = $result['errors'] ?? [];
                    if ($errList !== []) {
                        $msg .= ' С ошибками: ' . count($errList) . '.';
                    }
                    unset($_SESSION[$sessionKey]);
                    flash_set('success', $msg);
                    header('Location: ' . $backUrl);
                    exit;
                }
                $error = $result['error'] ?? 'Не удалось добавить предметы.';
            }
        } elseif ($action === 'clear') {
            unset($_SESSION[$sessionKey]);
            header('Location: curriculum_import.php?group_id=' . $groupId . '&year=' . urlencode($academicYear));
            exit;
        }
    }
}

$sessionData = $_SESSION[$sessionKey] ?? null;
if (is_array($sessionData) && isset($sessionData['rows']) && is_array($sessionData['rows'])) {
    $preview = $sessionData['rows'];
    $importMode = (string) ($sessionData['mode'] ?? 'list');
    if (isset($sessionData['default_semester']) && validate_semester((string) $sessionData['default_semester'])) {
        $defaultSemester = (string) $sessionData['default_semester'];
    }
} elseif (is_array($sessionData) && isset($sessionData['subjects']) && is_array($sessionData['subjects'])) {
    // Совместимость со старой сессией
    foreach ($sessionData['subjects'] as $name) {
        $preview[] = [
            'name' => (string) $name,
            'semester' => (string) ($sessionData['default_semester'] ?? $defaultSemester),
            'teacher_id' => null,
            'teacher_raw' => '',
        ];
    }
    if (isset($sessionData['default_semester']) && validate_semester((string) $sessionData['default_semester'])) {
        $defaultSemester = (string) $sessionData['default_semester'];
    }
}

$existing = curriculum_plan_subject_name_set($planId);

$teacherOptionsHtml = static function (array $teachers, ?int $selectedId): string {
    $html = '<option value="0">— Не назначен —</option>';
    foreach ($teachers as $teacher) {
        if (!(int) ($teacher['is_active'] ?? 1)) {
            continue;
        }
        $id = (int) $teacher['id'];
        $sel = $selectedId !== null && $selectedId === $id ? ' selected' : '';
        $html .= '<option value="' . $id . '"' . $sel . '>' . e((string) $teacher['full_name']) . '</option>';
    }

    return $html;
};

$pageTitle = 'Импорт предметов — ' . (string) $group['number'];
$showHeader = true;
$basePath = '../';
if ($curriculumPanel === 'admin') {
    $currentAdminTab = 'curriculum';
} else {
    $currentDeputyTab = 'curriculum';
}
require __DIR__ . '/../header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Импорт предметов из Word</h1>
                <p class="text-muted">
                    Группа <?= e((string) $group['number']) ?>
                    · <?= e($academicYear) ?>
                </p>
            </div>
            <div class="panel__header-actions">
                <a href="<?= e($backUrl) ?>" class="btn btn--ghost">← К учебному плану</a>
            </div>
        </div>
        <?php
        if ($curriculumPanel === 'admin') {
            require __DIR__ . '/../admin_nav.php';
        } else {
            require __DIR__ . '/../deputy_nav.php';
        }
        ?>
    </section>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($preview === []): ?>
    <section class="panel">
        <h2>Загрузка учебного плана</h2>
        <p class="text-muted">
            Загрузите рабочий учебный план (.docx): система распознает дисциплину, полугодие и преподавателя.
            Перед сохранением можно скорректировать список.
        </p>

        <form method="post" enctype="multipart/form-data" class="form form--medium" action="?group_id=<?= $groupId ?>&year=<?= e(urlencode($academicYear)) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="parse">
            <input type="hidden" name="source" value="file">
            <input type="hidden" name="group_id" value="<?= $groupId ?>">
            <input type="hidden" name="year" value="<?= e($academicYear) ?>">
            <input type="hidden" name="default_semester" value="1">

            <div class="form__group">
                <label for="word_file">Файл Word (.docx)</label>
                <input type="file" id="word_file" name="word_file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary" data-page-loader>Распознать план</button>
            </div>
        </form>

        <hr class="panel-divider">

        <h3 class="subsection-title">Или вставьте список текстом</h3>
        <form method="post" class="form form--medium" action="?group_id=<?= $groupId ?>&year=<?= e(urlencode($academicYear)) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="parse">
            <input type="hidden" name="source" value="text">
            <input type="hidden" name="group_id" value="<?= $groupId ?>">
            <input type="hidden" name="year" value="<?= e($academicYear) ?>">
            <div class="form__group">
                <label for="list_text">Список предметов</label>
                <textarea id="list_text" name="list_text" rows="8" placeholder="1. Математика&#10;2. История&#10;ОУД.01 Русский язык"></textarea>
            </div>
            <div class="form__group">
                <label for="default_semester_text">Семестр по умолчанию</label>
                <select id="default_semester_text" name="default_semester" required>
                    <?= render_semester_options($defaultSemester) ?>
                </select>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Распознать текст</button>
            </div>
        </form>
    </section>
    <?php else: ?>
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2>Подтверждение импорта</h2>
                <p class="text-muted">
                    Найдено: <?= count($preview) ?>.
                    Отметьте нужные строки и при необходимости скорректируйте название, семестр и преподавателя.
                    <?php if ($importMode === 'rup'): ?>
                        Семестр и преподаватель подставлены из учебного плана.
                    <?php endif; ?>
                </p>
            </div>
            <div class="panel__header-actions">
                <form method="post" action="?group_id=<?= $groupId ?>&year=<?= e(urlencode($academicYear)) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="clear">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <input type="hidden" name="year" value="<?= e($academicYear) ?>">
                    <button type="submit" class="btn btn--ghost">Загрузить заново</button>
                </form>
            </div>
        </div>

        <form method="post" class="form" action="?group_id=<?= $groupId ?>&year=<?= e(urlencode($academicYear)) ?>" data-curriculum-import-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import">
            <input type="hidden" name="group_id" value="<?= $groupId ?>">
            <input type="hidden" name="year" value="<?= e($academicYear) ?>">
            <input type="hidden" name="default_semester" value="<?= e($defaultSemester) ?>">

            <div class="form__row form__row--filter">
                <div class="form__group">
                    <label for="bulk_semester">Семестр для отмеченных</label>
                    <select id="bulk_semester" data-curriculum-bulk-semester>
                        <?= render_semester_options($defaultSemester) ?>
                    </select>
                </div>
                <div class="form__group form__group--align-end">
                    <button type="button" class="btn btn--ghost" data-curriculum-apply-semester>Применить семестр</button>
                </div>
                <div class="form__group">
                    <label for="bulk_teacher">Преподаватель для отмеченных</label>
                    <select id="bulk_teacher" data-curriculum-bulk-teacher>
                        <?= $teacherOptionsHtml($teachers, null) ?>
                    </select>
                </div>
                <div class="form__group form__group--align-end">
                    <button type="button" class="btn btn--ghost" data-curriculum-apply-teacher>Применить преподавателя</button>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 2.5rem;">
                                <label class="checkbox-label checkbox-label--table" title="Выбрать все доступные">
                                    <input type="checkbox" data-curriculum-select-all>
                                </label>
                            </th>
                            <th>№</th>
                            <th>Название предмета</th>
                            <th style="width: 11rem;">Семестр</th>
                            <th style="min-width: 14rem;">Преподаватель</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview as $index => $row): ?>
                        <?php
                        $name = (string) ($row['name'] ?? '');
                        $semester = validate_semester((string) ($row['semester'] ?? $defaultSemester))
                            ? (string) $row['semester']
                            : $defaultSemester;
                        $teacherId = isset($row['teacher_id']) ? (int) $row['teacher_id'] : 0;
                        $teacherRaw = trim((string) ($row['teacher_raw'] ?? ''));
                        $already = isset($existing[mb_strtolower($name)]);
                        ?>
                        <tr<?= $already ? ' class="is-muted"' : '' ?>>
                            <td>
                                <label class="checkbox-label checkbox-label--table">
                                    <input
                                        type="checkbox"
                                        name="selected[]"
                                        value="<?= (int) $index ?>"
                                        data-curriculum-item
                                        <?= $already ? ' disabled' : ' checked' ?>
                                    >
                                </label>
                            </td>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <input
                                    type="text"
                                    name="name[<?= (int) $index ?>]"
                                    value="<?= e($name) ?>"
                                    <?= $already ? ' readonly' : ' required' ?>
                                >
                                <?php if ($teacherRaw !== '' && $teacherId <= 0 && !$already): ?>
                                    <div class="text-muted" style="font-size: .85em; margin-top: .25rem;">
                                        В плане: <?= e($teacherRaw) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select
                                    name="semester[<?= (int) $index ?>]"
                                    data-curriculum-row-semester
                                    <?= $already ? ' disabled' : '' ?>
                                >
                                    <?= render_semester_options($semester) ?>
                                </select>
                            </td>
                            <td>
                                <select
                                    name="teacher_id[<?= (int) $index ?>]"
                                    data-curriculum-row-teacher
                                    <?= $already ? ' disabled' : '' ?>
                                >
                                    <?= $teacherOptionsHtml($teachers, $teacherId > 0 ? $teacherId : null) ?>
                                </select>
                            </td>
                            <td>
                                <?php if ($already): ?>
                                    <span class="text-muted">Уже в плане</span>
                                <?php elseif ($teacherRaw !== '' && $teacherId <= 0): ?>
                                    <span class="text-muted">Преподаватель не найден</span>
                                <?php else: ?>
                                    <span class="text-muted">Можно добавить</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary" data-page-loader>Добавить отмеченные</button>
                <a href="<?= e($backUrl) ?>" class="btn btn--ghost">Отмена</a>
            </div>
        </form>
    </section>
    <script>
    (() => {
        const form = document.querySelector('[data-curriculum-import-form]');
        if (!form) return;
        const selectAll = form.querySelector('[data-curriculum-select-all]');
        const items = () => Array.from(form.querySelectorAll('[data-curriculum-item]:not(:disabled)'));
        const bulk = form.querySelector('[data-curriculum-bulk-semester]');
        const applyBtn = form.querySelector('[data-curriculum-apply-semester]');
        const bulkTeacher = form.querySelector('[data-curriculum-bulk-teacher]');
        const applyTeacherBtn = form.querySelector('[data-curriculum-apply-teacher]');

        const sync = () => {
            const list = items();
            const checked = list.filter((el) => el.checked);
            if (selectAll) {
                selectAll.checked = list.length > 0 && checked.length === list.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < list.length;
            }
        };

        selectAll?.addEventListener('change', () => {
            items().forEach((el) => {
                el.checked = !!selectAll.checked;
            });
            sync();
        });
        items().forEach((el) => el.addEventListener('change', sync));
        applyBtn?.addEventListener('click', () => {
            if (!bulk) return;
            form.querySelectorAll('[data-curriculum-item]:checked').forEach((el) => {
                const row = el.closest('tr');
                const select = row?.querySelector('[data-curriculum-row-semester]');
                if (select && !select.disabled) {
                    select.value = bulk.value;
                }
            });
        });
        applyTeacherBtn?.addEventListener('click', () => {
            if (!bulkTeacher) return;
            form.querySelectorAll('[data-curriculum-item]:checked').forEach((el) => {
                const row = el.closest('tr');
                const select = row?.querySelector('[data-curriculum-row-teacher]');
                if (select && !select.disabled) {
                    select.value = bulkTeacher.value;
                }
            });
        });
        sync();
    })();
    </script>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
