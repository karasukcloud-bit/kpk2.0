<?php

declare(strict_types=1);

$curriculumPanel = $curriculumPanel ?? 'admin';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../curriculum.php';
require_once __DIR__ . '/../curriculum_modules.php';
require_once __DIR__ . '/../teachers.php';
require_once __DIR__ . '/../ktp.php';

require_curriculum_manager();

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$academicYear = normalize_academic_year($_GET['year'] ?? '') ?? get_default_academic_year();
$activeTab = ($_GET['tab'] ?? 'subjects') === 'modules' ? 'modules' : 'subjects';
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

$planId = $planResult['plan_id'];
$plan = get_curriculum_plan_by_id($planId);
$error = null;
$openEditModal = false;
$openModuleModal = false;
$openMdkModal = false;
$editFormData = [
    'item_id' => 0,
    'subject_name' => '',
    'semester' => '1',
    'teacher_id' => 0,
];
$moduleFormData = [
    'module_id' => 0,
    'module_number' => 1,
    'module_title' => '',
];
$mdkFormData = [
    'item_id' => 0,
    'module_id' => 0,
    'component_index' => 1,
    'component_title' => '',
    'start_abs_semester' => 1,
    'end_abs_semester' => 1,
    'teacher_id' => 0,
    'code' => '',
];
$openPracticeModal = false;
$practiceFormData = [
    'item_id' => 0,
    'module_id' => 0,
    'component_title' => '',
    'start_abs_semester' => 1,
    'end_abs_semester' => 1,
    'teacher_id' => 0,
    'code' => '',
    'practice_kind' => 'up',
];

$redirectBase = 'curriculum_edit.php?group_id=' . $groupId
    . '&year=' . urlencode($academicYear);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = $_POST['action'] ?? '';
        $tabAfter = 'subjects';

        if ($action === 'add_item') {
            $result = add_curriculum_item(
                $planId,
                $_POST['subject_name'] ?? '',
                $_POST['semester'] ?? '',
                (int) ($_POST['teacher_id'] ?? 0) ?: null
            );
        } elseif ($action === 'update_item') {
            $result = update_curriculum_item(
                (int) ($_POST['item_id'] ?? 0),
                $_POST['subject_name'] ?? '',
                $_POST['semester'] ?? '',
                (int) ($_POST['teacher_id'] ?? 0) ?: null
            );
        } elseif ($action === 'delete_item') {
            $result = delete_curriculum_item((int) ($_POST['item_id'] ?? 0));
        } elseif ($action === 'add_module') {
            $tabAfter = 'modules';
            $result = create_curriculum_module(
                $groupId,
                (int) ($_POST['module_number'] ?? 0),
                (string) ($_POST['module_title'] ?? ''),
                (int) ($_POST['program_semesters'] ?? 0) ?: null,
                $planId
            );
        } elseif ($action === 'update_module') {
            $tabAfter = 'modules';
            $result = update_curriculum_module(
                (int) ($_POST['module_id'] ?? 0),
                (string) ($_POST['module_title'] ?? ''),
                (int) ($_POST['module_number'] ?? 0) ?: null
            );
            if (!$result['success']) {
                $openModuleModal = true;
                $moduleFormData = [
                    'module_id' => (int) ($_POST['module_id'] ?? 0),
                    'module_number' => (int) ($_POST['module_number'] ?? 0),
                    'module_title' => (string) ($_POST['module_title'] ?? ''),
                ];
            }
        } elseif ($action === 'delete_module') {
            $tabAfter = 'modules';
            $result = delete_curriculum_module((int) ($_POST['module_id'] ?? 0));
        } elseif ($action === 'add_mdk' || $action === 'add_practice') {
            $tabAfter = 'modules';
            $itemType = $action === 'add_mdk' ? 'mdk' : 'practice';
            $result = add_curriculum_module_component(
                $planId,
                (int) ($_POST['module_id'] ?? 0),
                $itemType,
                (string) ($_POST['component_title'] ?? ''),
                (int) ($_POST['start_abs_semester'] ?? 0),
                (int) ($_POST['end_abs_semester'] ?? 0),
                (int) ($_POST['teacher_id'] ?? 0) ?: null,
                $itemType === 'practice' ? (string) ($_POST['practice_kind'] ?? '') : null
            );
        } elseif ($action === 'update_mdk') {
            $tabAfter = (($_POST['redirect_tab'] ?? '') === 'subjects') ? 'subjects' : 'modules';
            $result = update_curriculum_mdk(
                (int) ($_POST['item_id'] ?? 0),
                (string) ($_POST['component_title'] ?? ''),
                (int) ($_POST['start_abs_semester'] ?? 0),
                (int) ($_POST['end_abs_semester'] ?? 0),
                (int) ($_POST['teacher_id'] ?? 0) ?: null
            );
            if (!$result['success']) {
                $openMdkModal = true;
                $mdkFormData = [
                    'item_id' => (int) ($_POST['item_id'] ?? 0),
                    'module_id' => (int) ($_POST['module_id'] ?? 0),
                    'component_index' => (int) ($_POST['component_index'] ?? 1),
                    'component_title' => (string) ($_POST['component_title'] ?? ''),
                    'start_abs_semester' => (int) ($_POST['start_abs_semester'] ?? 1),
                    'end_abs_semester' => (int) ($_POST['end_abs_semester'] ?? 1),
                    'teacher_id' => (int) ($_POST['teacher_id'] ?? 0),
                    'code' => (string) ($_POST['mdk_code'] ?? ''),
                ];
            }
        } elseif ($action === 'update_practice') {
            $tabAfter = 'modules';
            $result = update_curriculum_practice(
                (int) ($_POST['item_id'] ?? 0),
                (string) ($_POST['component_title'] ?? ''),
                (int) ($_POST['start_abs_semester'] ?? 0),
                (int) ($_POST['end_abs_semester'] ?? 0),
                (int) ($_POST['teacher_id'] ?? 0) ?: null
            );
            if (!$result['success']) {
                $openPracticeModal = true;
                $practiceFormData = [
                    'item_id' => (int) ($_POST['item_id'] ?? 0),
                    'module_id' => (int) ($_POST['module_id'] ?? 0),
                    'component_title' => (string) ($_POST['component_title'] ?? ''),
                    'start_abs_semester' => (int) ($_POST['start_abs_semester'] ?? 1),
                    'end_abs_semester' => (int) ($_POST['end_abs_semester'] ?? 1),
                    'teacher_id' => (int) ($_POST['teacher_id'] ?? 0),
                    'code' => (string) ($_POST['practice_code'] ?? ''),
                    'practice_kind' => (string) ($_POST['practice_kind'] ?? 'up'),
                ];
            }
        } elseif ($action === 'delete_module_component') {
            $tabAfter = (($_POST['redirect_tab'] ?? '') === 'subjects') ? 'subjects' : 'modules';
            $result = delete_curriculum_module_component((int) ($_POST['item_id'] ?? 0));
        } elseif ($action === 'set_program_semesters') {
            $tabAfter = 'modules';
            $result = set_group_program_semesters($groupId, (int) ($_POST['program_semesters'] ?? 0));
        } else {
            $result = ['success' => false, 'error' => 'Неизвестное действие.'];
        }

        if ($result['success']) {
            $messages = [
                'add_item' => 'Предмет добавлен в учебный план.',
                'update_item' => 'Предмет обновлён.',
                'delete_item' => 'Предмет удалён из учебного плана.',
                'add_module' => 'Профессиональный модуль добавлен.',
                'update_module' => 'Модуль обновлён.',
                'delete_module' => 'Модуль удалён.',
                'add_mdk' => 'МДК добавлен в модуль.',
                'add_practice' => 'Практика добавлена в модуль.',
                'update_mdk' => 'МДК обновлён.',
                'update_practice' => 'Практика обновлена.',
                'delete_module_component' => 'Элемент модуля удалён.',
                'set_program_semesters' => 'Срок обучения группы обновлён.',
            ];
            flash_set('success', $messages[$action] ?? 'Изменения сохранены.');
            header('Location: ' . $redirectBase . '&tab=' . $tabAfter);
            exit;
        }

        $error = $result['error'];
        $activeTab = $tabAfter;
        if ($action === 'update_mdk') {
            $activeTab = 'modules';
            if ((($_POST['redirect_tab'] ?? '') === 'subjects')) {
                $activeTab = 'subjects';
            }
        }
        if ($action === 'update_practice') {
            $activeTab = 'modules';
        }
        if ($action === 'update_item') {
            $openEditModal = true;
            $editFormData = [
                'item_id' => (int) ($_POST['item_id'] ?? 0),
                'subject_name' => (string) ($_POST['subject_name'] ?? ''),
                'semester' => (string) ($_POST['semester'] ?? '1'),
                'teacher_id' => (int) ($_POST['teacher_id'] ?? 0),
            ];
        }
    }
}

$items = get_curriculum_subjects_with_mdk($planId, $groupId, get_group_course($group));
$groupCourse = get_group_course($group);
$semester1Items = array_values(array_filter(
    $items,
    static function (array $item) use ($groupCourse): bool {
        return curriculum_list_item_in_semester($item, '1', $groupCourse);
    }
));
$semester2Items = array_values(array_filter(
    $items,
    static function (array $item) use ($groupCourse): bool {
        return curriculum_list_item_in_semester($item, '2', $groupCourse);
    }
));
$subjectNames = get_all_subject_names();
$teachers = get_all_teachers();
$modules = get_curriculum_modules_for_group($groupId);
$usedModuleNumbers = [];
foreach ($modules as $mRow) {
    $usedModuleNumbers[(int) $mRow['number']] = (int) $mRow['id'];
}
$programSemesters = get_group_program_semesters($groupId);
$moduleMdkCount = 0;
$modulePracticeCount = 0;
foreach ($modules as $moduleRow) {
    foreach ($moduleRow['children'] ?? [] as $childRow) {
        if (($childRow['item_type'] ?? '') === 'mdk') {
            $moduleMdkCount++;
        } elseif (($childRow['item_type'] ?? '') === 'practice') {
            $modulePracticeCount++;
        }
    }
}
$plainSubjectsCount = count(array_filter(
    $items,
    static function (array $item): bool {
        return ($item['item_type'] ?? 'subject') === 'subject';
    }
));
$otherYearSubjectHint = null;
if ($plainSubjectsCount === 0) {
    $yearParts = explode('-', $academicYear);
    if (count($yearParts) === 2) {
        $prevYear = ((int) $yearParts[0] - 1) . '-' . ((int) $yearParts[1] - 1);
        $prevPlan = get_curriculum_plan($groupId, $prevYear);
        if ($prevPlan !== null) {
            $prevSubjects = get_curriculum_items((int) $prevPlan['id'], 'subject');
            if ($prevSubjects !== []) {
                $otherYearSubjectHint = [
                    'year' => $prevYear,
                    'count' => count($prevSubjects),
                ];
            }
        }
    }
}
$success = flash_get('success');

$pageTitle = 'Учебный план — ' . ($group['number'] ?? '');
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
                <h1>Учебный план группы <?= e($group['number']) ?></h1>
                <p class="text-muted">
                    Учебный год <?= e($academicYear) ?> ·
                    <?= e($plan['specialty_name']) ?> (<?= e($plan['specialty_code']) ?>) ·
                    сейчас <?= (int) $groupCourse ?> курс
                </p>
            </div>
            <a href="curriculum.php?year=<?= e(urlencode($academicYear)) ?>" class="btn btn--ghost">← К списку групп</a>
        </div>

        <?php
        if ($curriculumPanel === 'admin') {
            require __DIR__ . '/../admin_nav.php';
        } else {
            require __DIR__ . '/../deputy_nav.php';
        }
        ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <nav class="admin-tabs curriculum-edit-tabs">
        <a href="<?= e($redirectBase) ?>&tab=subjects"
           class="admin-tabs__item<?= $activeTab === 'subjects' ? ' admin-tabs__item--active' : '' ?>">
            Предметы
        </a>
        <a href="<?= e($redirectBase) ?>&tab=modules"
           class="admin-tabs__item<?= $activeTab === 'modules' ? ' admin-tabs__item--active' : '' ?>">
            Профессиональные модули
        </a>
    </nav>

    <?php if ($activeTab === 'subjects'): ?>
    <section class="panel">
        <h2>Предметы на учебный год <?= e($academicYear) ?></h2>
        <p class="text-muted">
            Обычные дисциплины и МДК текущего курса группы.
            Практики остаются во вкладке
            <a href="<?= e($redirectBase) ?>&tab=modules">Профессиональные модули</a>
            <?php if ($modulePracticeCount > 0): ?>
                (<?= (int) $modulePracticeCount ?>)
            <?php endif; ?>.
        </p>

        <?php if (empty($items)): ?>
            <p class="text-muted">В <?= e($academicYear) ?> предметы и МДК ещё не добавлены.</p>
            <?php if ($otherYearSubjectHint !== null): ?>
                <p class="text-muted">
                    В <?= e($otherYearSubjectHint['year']) ?> у группы уже есть
                    <?= (int) $otherYearSubjectHint['count'] ?> обычных предмет(ов) —
                    <a href="curriculum_edit.php?group_id=<?= (int) $groupId ?>&year=<?= e(urlencode($otherYearSubjectHint['year'])) ?>&tab=subjects">
                        открыть тот год
                    </a>.
                    На новый год дисциплины добавляются отдельно.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <div class="semester-columns">
                <div class="semester-column">
                    <h3>1 семестр</h3>
                    <?= render_curriculum_semester_table($semester1Items, $groupId, $academicYear) ?>
                </div>
                <div class="semester-column">
                    <h3>2 семестр</h3>
                    <?= render_curriculum_semester_table($semester2Items, $groupId, $academicYear) ?>
                </div>
            </div>
        <?php endif; ?>

        <h3 class="subsection-title">Добавить предмет</h3>

        <form method="post" class="form form--medium">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_item">

            <div class="form__row">
                <div class="form__group">
                    <label for="subject_name">Название предмета</label>
                    <input type="text" id="subject_name" name="subject_name" required
                           list="subject_catalog"
                           value="<?= e((!$openEditModal && ($_POST['action'] ?? '') === 'add_item') ? ($_POST['subject_name'] ?? '') : '') ?>"
                           placeholder="Математика">
                    <datalist id="subject_catalog">
                        <?php foreach ($subjectNames as $name): ?>
                        <option value="<?= e($name) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form__group">
                    <label for="semester">Семестр</label>
                    <select id="semester" name="semester" required>
                        <?= render_semester_options(
                            (!$openEditModal && ($_POST['action'] ?? '') === 'add_item')
                                ? (string) ($_POST['semester'] ?? '1')
                                : '1'
                        ) ?>
                    </select>
                </div>
            </div>

            <div class="form__group">
                <label for="teacher_id">Преподаватель</label>
                <select id="teacher_id" name="teacher_id">
                    <option value="">— Не назначен —</option>
                    <?php
                    $selectedTeacherId = (!$openEditModal && ($_POST['action'] ?? '') === 'add_item')
                        ? (int) ($_POST['teacher_id'] ?? 0)
                        : 0;
                    foreach ($teachers as $teacher):
                        if (!(int) $teacher['is_active'] && (int) $teacher['id'] !== $selectedTeacherId) {
                            continue;
                        }
                    ?>
                    <option value="<?= (int) $teacher['id'] ?>"<?= (int) $teacher['id'] === $selectedTeacherId ? ' selected' : '' ?>>
                        <?= e($teacher['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Добавить предмет</button>
            </div>
        </form>
    </section>
    <?php else: ?>
    <section class="panel">
        <h2>Профессиональные модули</h2>
        <p class="text-muted">
            ПМ — на весь срок обучения группы. Раскройте модуль, чтобы увидеть МДК и практики.
            Удалить можно только пустой модуль.
        </p>

        <form method="post" class="form form--inline curriculum-program-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="set_program_semesters">
            <label for="program_semesters">Срок обучения группы</label>
            <select id="program_semesters" name="program_semesters">
                <option value="6"<?= $programSemesters === 6 ? ' selected' : '' ?>>3 курса (6 семестров)</option>
                <option value="8"<?= $programSemesters === 8 ? ' selected' : '' ?>>4 курса (8 семестров)</option>
            </select>
            <button type="submit" class="btn btn--ghost btn--sm">Сохранить</button>
        </form>

        <?php if ($modules === []): ?>
            <p class="text-muted">Профессиональные модули пока не добавлены.</p>
        <?php else: ?>
            <ul class="pm-tree">
                <?php foreach ($modules as $module): ?>
                    <?php
                    $moduleId = (int) $module['id'];
                    $moduleNumber = (int) $module['number'];
                    $children = $module['children'] ?? [];
                    $childCount = count($children);
                    $mdkChildren = array_values(array_filter(
                        $children,
                        static function (array $c): bool {
                            return ($c['item_type'] ?? '') === 'mdk';
                        }
                    ));
                    $practiceChildren = array_values(array_filter(
                        $children,
                        static function (array $c): bool {
                            return ($c['item_type'] ?? '') === 'practice';
                        }
                    ));
                    $isOpen = ($openModuleModal && (int) $moduleFormData['module_id'] === $moduleId)
                        || ($openMdkModal && (int) $mdkFormData['module_id'] === $moduleId)
                        || ($openPracticeModal && (int) $practiceFormData['module_id'] === $moduleId);
                    ?>
                    <li class="pm-tree__node">
                        <details class="pm-tree__module"<?= $isOpen ? ' open' : '' ?>>
                            <summary class="pm-tree__summary">
                                <span class="pm-tree__toggle" aria-hidden="true"></span>
                                <span class="pm-tree__code"><?= e($module['code']) ?></span>
                                <span class="pm-tree__title"><?= e($module['title'] !== '' ? $module['title'] : 'Без названия') ?></span>
                                <span class="pm-tree__meta">
                                    весь срок · <?= (int) $childCount ?> элем.
                                </span>
                                <span class="pm-tree__actions" onclick="event.stopPropagation(); if (!event.target.closest('form')) event.preventDefault();">
                                    <button
                                        type="button"
                                        class="btn btn--ghost btn--sm"
                                        data-curriculum-module-edit-open
                                        data-module-id="<?= $moduleId ?>"
                                        data-module-number="<?= $moduleNumber ?>"
                                        data-module-title="<?= e($module['title']) ?>"
                                    >Редактировать</button>
                                    <?php if ($childCount === 0): ?>
                                    <form method="post" class="inline-form" onsubmit="return confirm('Удалить пустой модуль <?= e($module['code']) ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_module">
                                        <input type="hidden" name="module_id" value="<?= $moduleId ?>">
                                        <button type="submit" class="btn btn--danger btn--sm">Удалить</button>
                                    </form>
                                    <?php else: ?>
                                    <button
                                        type="button"
                                        class="btn btn--danger btn--sm"
                                        disabled
                                        aria-disabled="true"
                                        title="Сначала удалите МДК и практики"
                                    >Удалить</button>
                                    <?php endif; ?>
                                </span>
                            </summary>

                            <div class="pm-tree__body">
                                <div class="pm-tree__branch">
                                    <h4 class="pm-tree__section-title">МДК</h4>
                                    <?php if ($mdkChildren === []): ?>
                                        <p class="text-muted pm-tree__empty">МДК пока нет.</p>
                                    <?php else: ?>
                                        <ul class="pm-tree__list">
                                            <?php foreach ($mdkChildren as $child): ?>
                                            <?php
                                            $mdkTitle = mdk_title_from_subject_name(
                                                (string) $child['subject_name'],
                                                $moduleNumber,
                                                (int) $child['component_index']
                                            );
                                            ?>
                                            <li class="pm-tree__item">
                                                <div class="pm-tree__item-main">
                                                    <strong><?= e($child['code']) ?></strong>
                                                    <span><?= e($mdkTitle) ?></span>
                                                </div>
                                                <div class="pm-tree__item-meta">
                                                    <?= e($child['start_label']) ?> → <?= e($child['end_label']) ?>
                                                    · <?= e($child['teacher_name'] ?? 'без преподавателя') ?>
                                                </div>
                                                <div class="pm-tree__item-actions">
                                                    <button
                                                        type="button"
                                                        class="btn btn--ghost btn--sm"
                                                        data-curriculum-mdk-edit-open
                                                        data-item-id="<?= (int) $child['id'] ?>"
                                                        data-module-id="<?= $moduleId ?>"
                                                        data-component-index="<?= (int) $child['component_index'] ?>"
                                                        data-mdk-code="<?= e($child['code']) ?>"
                                                        data-component-title="<?= e($mdkTitle) ?>"
                                                        data-start-abs="<?= (int) $child['start_abs_semester'] ?>"
                                                        data-end-abs="<?= (int) $child['end_abs_semester'] ?>"
                                                        data-teacher-id="<?= (int) ($child['teacher_id'] ?? 0) ?>"
                                                    >Редактировать</button>
                                                    <form method="post" class="inline-form" onsubmit="return confirm('Удалить МДК?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="delete_module_component">
                                                        <input type="hidden" name="item_id" value="<?= (int) $child['id'] ?>">
                                                        <button type="submit" class="btn btn--danger btn--sm">Удалить</button>
                                                    </form>
                                                </div>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>

                                    <details class="pm-tree__add">
                                        <summary>Добавить МДК</summary>
                                        <form method="post" class="form form--medium">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="add_mdk">
                                            <input type="hidden" name="module_id" value="<?= $moduleId ?>">
                                            <div class="form__group">
                                                <label>Название МДК</label>
                                                <input type="text" name="component_title" required
                                                       placeholder="Выполнение работ по профессии…">
                                                <p class="form__hint">Код <?= e(format_mdk_code($moduleNumber, next_module_component_index($moduleId, 'mdk'))) ?> присвоится автоматически.</p>
                                            </div>
                                            <div class="form__row">
                                                <div class="form__group">
                                                    <label>Начало</label>
                                                    <select name="start_abs_semester" required>
                                                        <?= render_abs_semester_options($programSemesters, 1) ?>
                                                    </select>
                                                </div>
                                                <div class="form__group">
                                                    <label>Окончание</label>
                                                    <select name="end_abs_semester" required>
                                                        <?= render_abs_semester_options($programSemesters, 1) ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form__group">
                                                <label>Преподаватель</label>
                                                <select name="teacher_id">
                                                    <option value="">— Не назначен —</option>
                                                    <?php foreach ($teachers as $teacher): ?>
                                                        <?php if (!(int) $teacher['is_active']) {
                                                            continue;
                                                        } ?>
                                                    <option value="<?= (int) $teacher['id'] ?>"><?= e($teacher['full_name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn--primary btn--sm">Добавить МДК</button>
                                        </form>
                                    </details>
                                </div>

                                <div class="pm-tree__branch">
                                    <h4 class="pm-tree__section-title">Практики</h4>
                                    <?php if ($practiceChildren === []): ?>
                                        <p class="text-muted pm-tree__empty">Практики пока нет.</p>
                                    <?php else: ?>
                                        <ul class="pm-tree__list">
                                            <?php foreach ($practiceChildren as $child): ?>
                                            <?php
                                            $practiceTitle = practice_title_from_subject_name(
                                                (string) $child['subject_name'],
                                                (string) $child['code']
                                            );
                                            ?>
                                            <li class="pm-tree__item">
                                                <div class="pm-tree__item-main">
                                                    <strong><?= e($child['code']) ?></strong>
                                                    <span><?= e($practiceTitle !== '' ? $practiceTitle : (string) $child['subject_name']) ?></span>
                                                </div>
                                                <div class="pm-tree__item-meta">
                                                    <?= e($child['start_label']) ?> → <?= e($child['end_label']) ?>
                                                    · <?= e($child['teacher_name'] ?? 'без преподавателя') ?>
                                                </div>
                                                <div class="pm-tree__item-actions">
                                                    <button
                                                        type="button"
                                                        class="btn btn--ghost btn--sm"
                                                        data-curriculum-practice-edit-open
                                                        data-item-id="<?= (int) $child['id'] ?>"
                                                        data-module-id="<?= $moduleId ?>"
                                                        data-practice-code="<?= e($child['code']) ?>"
                                                        data-practice-kind="<?= e((string) ($child['practice_kind'] ?? 'up')) ?>"
                                                        data-component-title="<?= e($practiceTitle) ?>"
                                                        data-start-abs="<?= (int) $child['start_abs_semester'] ?>"
                                                        data-end-abs="<?= (int) $child['end_abs_semester'] ?>"
                                                        data-teacher-id="<?= (int) ($child['teacher_id'] ?? 0) ?>"
                                                    >Редактировать</button>
                                                    <form method="post" class="inline-form" onsubmit="return confirm('Удалить практику?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="delete_module_component">
                                                        <input type="hidden" name="item_id" value="<?= (int) $child['id'] ?>">
                                                        <button type="submit" class="btn btn--danger btn--sm">Удалить</button>
                                                    </form>
                                                </div>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>

                                    <details class="pm-tree__add">
                                        <summary>Добавить практику</summary>
                                        <form method="post" class="form form--medium">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="add_practice">
                                            <input type="hidden" name="module_id" value="<?= $moduleId ?>">
                                            <div class="form__row">
                                                <div class="form__group">
                                                    <label>Вид</label>
                                                    <select name="practice_kind" required>
                                                        <option value="up">УП — учебная</option>
                                                        <option value="pp">ПП — производственная</option>
                                                        <option value="pdp">ПДП — преддипломная</option>
                                                    </select>
                                                </div>
                                                <div class="form__group">
                                                    <label>Название <span class="text-muted">(необязательно)</span></label>
                                                    <input type="text" name="component_title"
                                                           placeholder="Практика по профилю специальности">
                                                </div>
                                            </div>
                                            <div class="form__row">
                                                <div class="form__group">
                                                    <label>Начало</label>
                                                    <select name="start_abs_semester" required>
                                                        <?= render_abs_semester_options($programSemesters, max(1, $programSemesters - 1)) ?>
                                                    </select>
                                                </div>
                                                <div class="form__group">
                                                    <label>Окончание</label>
                                                    <select name="end_abs_semester" required>
                                                        <?= render_abs_semester_options($programSemesters, $programSemesters) ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form__group">
                                                <label>Преподаватель / руководитель</label>
                                                <select name="teacher_id">
                                                    <option value="">— Не назначен —</option>
                                                    <?php foreach ($teachers as $teacher): ?>
                                                        <?php if (!(int) $teacher['is_active']) {
                                                            continue;
                                                        } ?>
                                                    <option value="<?= (int) $teacher['id'] ?>"><?= e($teacher['full_name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn--primary btn--sm">Добавить практику</button>
                                        </form>
                                    </details>
                                </div>
                            </div>
                        </details>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h3 class="subsection-title">Добавить профессиональный модуль</h3>
        <form method="post" class="form form--medium">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_module">
            <div class="form__row">
                <div class="form__group">
                    <label for="module_number">Номер ПМ</label>
                    <select id="module_number" name="module_number" required>
                        <?php for ($n = 1; $n <= 15; $n++): ?>
                            <?php if (isset($usedModuleNumbers[$n])) {
                                continue;
                            } ?>
                        <option value="<?= $n ?>">ПМ <?= str_pad((string) $n, 2, '0', STR_PAD_LEFT) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form__group">
                    <label for="module_title">Название (вид проф. деятельности)</label>
                    <input type="text" id="module_title" name="module_title"
                           placeholder="Выполнение работ по профессии…">
                </div>
            </div>
            <?php if ($modules === []): ?>
            <div class="form__group">
                <label for="module_program_semesters">Срок обучения группы</label>
                <select id="module_program_semesters" name="program_semesters">
                    <option value="6"<?= $programSemesters === 6 ? ' selected' : '' ?>>3 курса (6 семестров)</option>
                    <option value="8"<?= $programSemesters === 8 ? ' selected' : '' ?>>4 курса (8 семестров)</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Добавить ПМ</button>
            </div>
        </form>
    </section>

    <section class="panel panel--info">
        <h2>Как устроены ПМ</h2>
        <ul class="text-muted">
            <li>ПМ = вид профессиональной деятельности; завершается экзаменом (квалификационным).</li>
            <li>Внутри: МДК (теория) и практики УП / ПП / при необходимости ПДП.</li>
            <li>Нумерация: ПМ 01 → МДК 01.01, МДК 01.02, УП 01.01, ПП 01.01…</li>
            <li>МДК попадает в электронный журнал в семестрах периода «начало–окончание».</li>
            <li>Практики учитываются отдельно (журнал практики — в следующей итерации).</li>
        </ul>
    </section>
    <?php endif; ?>
</div>

</div>

<div
    class="modal"
    data-curriculum-mdk-modal
    <?= $openMdkModal ? '' : 'hidden' ?>
>
    <div class="modal__backdrop" data-curriculum-mdk-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="curriculum-mdk-modal-title">
        <div class="modal__header">
            <h2 id="curriculum-mdk-modal-title">Редактировать МДК</h2>
            <button type="button" class="modal__close" data-curriculum-mdk-close aria-label="Закрыть">&times;</button>
        </div>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_mdk">
            <input type="hidden" name="item_id" value="<?= (int) $mdkFormData['item_id'] ?>" data-curriculum-mdk-id>
            <input type="hidden" name="module_id" value="<?= (int) $mdkFormData['module_id'] ?>" data-curriculum-mdk-module-id>
            <input type="hidden" name="component_index" value="<?= (int) $mdkFormData['component_index'] ?>" data-curriculum-mdk-index>
            <input type="hidden" name="mdk_code" value="<?= e($mdkFormData['code']) ?>" data-curriculum-mdk-code>
            <input type="hidden" name="redirect_tab" value="modules" data-curriculum-mdk-redirect>

            <div class="form__group">
                <label>Код МДК</label>
                <p class="text-muted" style="margin:0" data-curriculum-mdk-code-label><?= e($mdkFormData['code'] !== '' ? $mdkFormData['code'] : '—') ?></p>
            </div>

            <div class="form__group">
                <label for="edit_mdk_title">Название</label>
                <input
                    type="text"
                    id="edit_mdk_title"
                    name="component_title"
                    required
                    value="<?= e($mdkFormData['component_title']) ?>"
                    data-curriculum-mdk-title
                >
            </div>

            <div class="form__row">
                <div class="form__group">
                    <label for="edit_mdk_start">Начало</label>
                    <select id="edit_mdk_start" name="start_abs_semester" required data-curriculum-mdk-start>
                        <?= render_abs_semester_options($programSemesters, (int) $mdkFormData['start_abs_semester']) ?>
                    </select>
                </div>
                <div class="form__group">
                    <label for="edit_mdk_end">Окончание</label>
                    <select id="edit_mdk_end" name="end_abs_semester" required data-curriculum-mdk-end>
                        <?= render_abs_semester_options($programSemesters, (int) $mdkFormData['end_abs_semester']) ?>
                    </select>
                </div>
            </div>

            <div class="form__group">
                <label for="edit_mdk_teacher">Преподаватель</label>
                <select id="edit_mdk_teacher" name="teacher_id" data-curriculum-mdk-teacher>
                    <option value="">— Не назначен —</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <?php
                        if (!(int) $teacher['is_active']
                            && (int) $teacher['id'] !== (int) $mdkFormData['teacher_id']
                        ) {
                            continue;
                        }
                        ?>
                    <option
                        value="<?= (int) $teacher['id'] ?>"
                        <?= (int) $teacher['id'] === (int) $mdkFormData['teacher_id'] ? ' selected' : '' ?>
                    >
                        <?= e($teacher['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сохранить</button>
                <button type="button" class="btn btn--ghost" data-curriculum-mdk-close>Отмена</button>
            </div>
        </form>
    </div>
</div>

<div
    class="modal"
    data-curriculum-practice-modal
    <?= $openPracticeModal ? '' : 'hidden' ?>
>
    <div class="modal__backdrop" data-curriculum-practice-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="curriculum-practice-modal-title">
        <div class="modal__header">
            <h2 id="curriculum-practice-modal-title">Редактировать практику</h2>
            <button type="button" class="modal__close" data-curriculum-practice-close aria-label="Закрыть">&times;</button>
        </div>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_practice">
            <input type="hidden" name="item_id" value="<?= (int) $practiceFormData['item_id'] ?>" data-curriculum-practice-id>
            <input type="hidden" name="module_id" value="<?= (int) $practiceFormData['module_id'] ?>" data-curriculum-practice-module-id>
            <input type="hidden" name="practice_code" value="<?= e($practiceFormData['code']) ?>" data-curriculum-practice-code>
            <input type="hidden" name="practice_kind" value="<?= e($practiceFormData['practice_kind']) ?>" data-curriculum-practice-kind>

            <div class="form__group">
                <label>Код практики</label>
                <p class="text-muted" style="margin:0" data-curriculum-practice-code-label><?= e($practiceFormData['code'] !== '' ? $practiceFormData['code'] : '—') ?></p>
            </div>

            <div class="form__group">
                <label for="edit_practice_title">Название <span class="text-muted">(необязательно)</span></label>
                <input
                    type="text"
                    id="edit_practice_title"
                    name="component_title"
                    value="<?= e($practiceFormData['component_title']) ?>"
                    data-curriculum-practice-title
                >
            </div>

            <div class="form__row">
                <div class="form__group">
                    <label for="edit_practice_start">Начало</label>
                    <select id="edit_practice_start" name="start_abs_semester" required data-curriculum-practice-start>
                        <?= render_abs_semester_options($programSemesters, (int) $practiceFormData['start_abs_semester']) ?>
                    </select>
                </div>
                <div class="form__group">
                    <label for="edit_practice_end">Окончание</label>
                    <select id="edit_practice_end" name="end_abs_semester" required data-curriculum-practice-end>
                        <?= render_abs_semester_options($programSemesters, (int) $practiceFormData['end_abs_semester']) ?>
                    </select>
                </div>
            </div>

            <div class="form__group">
                <label for="edit_practice_teacher">Преподаватель / руководитель</label>
                <select id="edit_practice_teacher" name="teacher_id" data-curriculum-practice-teacher>
                    <option value="">— Не назначен —</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <?php
                        if (!(int) $teacher['is_active']
                            && (int) $teacher['id'] !== (int) $practiceFormData['teacher_id']
                        ) {
                            continue;
                        }
                        ?>
                    <option
                        value="<?= (int) $teacher['id'] ?>"
                        <?= (int) $teacher['id'] === (int) $practiceFormData['teacher_id'] ? ' selected' : '' ?>
                    ><?= e($teacher['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сохранить</button>
                <button type="button" class="btn btn--ghost" data-curriculum-practice-close>Отмена</button>
            </div>
        </form>
    </div>
</div>

<div
    class="modal"
    data-curriculum-module-modal
    <?= $openModuleModal ? '' : 'hidden' ?>
>
    <div class="modal__backdrop" data-curriculum-module-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="curriculum-module-modal-title">
        <div class="modal__header">
            <h2 id="curriculum-module-modal-title">Редактировать модуль</h2>
            <button type="button" class="modal__close" data-curriculum-module-close aria-label="Закрыть">&times;</button>
        </div>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_module">
            <input type="hidden" name="module_id" value="<?= (int) $moduleFormData['module_id'] ?>" data-curriculum-module-id>

            <div class="form__group">
                <label for="edit_module_number">Индекс ПМ</label>
                <select id="edit_module_number" name="module_number" required data-curriculum-module-number>
                    <?php
                    $editNum = (int) $moduleFormData['module_number'];
                    $editModId = (int) $moduleFormData['module_id'];
                    for ($n = 1; $n <= 15; $n++):
                        $ownerId = $usedModuleNumbers[$n] ?? 0;
                        if ($ownerId > 0 && $ownerId !== $editModId) {
                            continue;
                        }
                    ?>
                    <option value="<?= $n ?>"<?= $n === $editNum ? ' selected' : '' ?>>
                        ПМ <?= str_pad((string) $n, 2, '0', STR_PAD_LEFT) ?>
                    </option>
                    <?php endfor; ?>
                </select>
                <p class="form__hint">Нельзя выбрать индекс, уже занятый другим модулем группы.</p>
            </div>

            <div class="form__group">
                <label for="edit_module_title">Название модуля</label>
                <input
                    type="text"
                    id="edit_module_title"
                    name="module_title"
                    value="<?= e($moduleFormData['module_title']) ?>"
                    placeholder="Вид профессиональной деятельности"
                    data-curriculum-module-title
                >
            </div>

            <div class="form__group">
                <label>Срок освоения</label>
                <p class="text-muted" style="margin:0">
                    Весь период обучения группы
                    (<?= (int) $programSemesters ?> сем. /
                    <?= (int) ($programSemesters / 2) ?> курс.)
                </p>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сохранить</button>
                <button type="button" class="btn btn--ghost" data-curriculum-module-close>Отмена</button>
            </div>
        </form>
    </div>
</div>

<div
    class="modal"
    data-curriculum-edit-modal
    <?= $openEditModal ? '' : 'hidden' ?>
>
    <div class="modal__backdrop" data-curriculum-edit-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="curriculum-edit-title">
        <div class="modal__header">
            <h2 id="curriculum-edit-title">Редактировать предмет</h2>
            <button type="button" class="modal__close" data-curriculum-edit-close aria-label="Закрыть">&times;</button>
        </div>
        <form method="post" class="form" data-curriculum-edit-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_item">
            <input type="hidden" name="item_id" value="<?= (int) $editFormData['item_id'] ?>" data-curriculum-edit-id>

            <div class="form__group">
                <label for="edit_subject_name">Название предмета</label>
                <input
                    type="text"
                    id="edit_subject_name"
                    name="subject_name"
                    required
                    list="subject_catalog_edit"
                    value="<?= e($editFormData['subject_name']) ?>"
                    data-curriculum-edit-subject
                >
                <datalist id="subject_catalog_edit">
                    <?php foreach ($subjectNames as $name): ?>
                    <option value="<?= e($name) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form__group">
                <label for="edit_semester">Семестр</label>
                <select id="edit_semester" name="semester" required data-curriculum-edit-semester>
                    <?= render_semester_options($editFormData['semester']) ?>
                </select>
            </div>

            <div class="form__group">
                <label for="edit_teacher_id">Преподаватель</label>
                <select id="edit_teacher_id" name="teacher_id" data-curriculum-edit-teacher>
                    <option value="">— Не назначен —</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <?php
                        if (!(int) $teacher['is_active']
                            && (int) $teacher['id'] !== (int) $editFormData['teacher_id']
                        ) {
                            continue;
                        }
                        ?>
                    <option
                        value="<?= (int) $teacher['id'] ?>"
                        <?= (int) $teacher['id'] === (int) $editFormData['teacher_id'] ? ' selected' : '' ?>
                    >
                        <?= e($teacher['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сохранить</button>
                <button type="button" class="btn btn--ghost" data-curriculum-edit-close>Отмена</button>
            </div>
        </form>
    </div>
</div>

<?php if ($openEditModal || $openModuleModal || $openMdkModal || $openPracticeModal): ?>
<script>document.body.classList.add('modal-open');</script>
<?php endif; ?>

<?php require __DIR__ . '/../footer.php'; ?>
