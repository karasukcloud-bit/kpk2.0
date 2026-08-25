<?php

declare(strict_types=1);

$curriculumPanel = $curriculumPanel ?? 'admin';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../curriculum.php';
require_once __DIR__ . '/../teachers.php';
require_once __DIR__ . '/../ktp.php';

require_curriculum_manager();

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$academicYear = normalize_academic_year($_GET['year'] ?? '') ?? get_default_academic_year();
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
$editFormData = [
    'item_id' => 0,
    'subject_name' => '',
    'semester' => '1',
    'teacher_id' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = $_POST['action'] ?? '';

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
        } else {
            $result = ['success' => false, 'error' => 'Неизвестное действие.'];
        }

        if ($result['success']) {
            if ($action === 'add_item') {
                $successMessage = 'Предмет добавлен в учебный план.';
            } elseif ($action === 'update_item') {
                $successMessage = 'Предмет обновлён.';
            } elseif ($action === 'delete_item') {
                $successMessage = 'Предмет удалён из учебного плана.';
            } else {
                $successMessage = 'Изменения сохранены.';
            }
            flash_set('success', $successMessage);
            header('Location: curriculum_edit.php?group_id=' . $groupId . '&year=' . urlencode($academicYear));
            exit;
        }

        $error = $result['error'];
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

$items = get_curriculum_items($planId);
$semester1Items = array_values(array_filter(
    $items,
    static fn (array $item): bool => $item['semester'] === '1' || $item['semester'] === 'both'
));
$semester2Items = array_values(array_filter(
    $items,
    static fn (array $item): bool => $item['semester'] === '2' || $item['semester'] === 'both'
));
$subjectNames = get_all_subject_names();
$teachers = get_all_teachers();
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
                    <?= e($plan['specialty_name']) ?> (<?= e($plan['specialty_code']) ?>)
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

    <section class="panel">
        <h2>Предметы на учебный год</h2>

        <?php if (empty($items)): ?>
            <p class="text-muted">Предметы пока не добавлены.</p>
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

    <section class="panel panel--info">
        <h2>Справка</h2>
        <p class="text-muted">
            Каждый предмет сохраняется в справочнике и привязывается к учебному плану группы.
            КТП по предмету составляет назначенный преподаватель; просмотр — по кнопке «КТП».
        </p>
    </section>
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

<?php if ($openEditModal): ?>
<script>document.body.classList.add('modal-open');</script>
<?php endif; ?>

<?php require __DIR__ . '/../footer.php'; ?>
