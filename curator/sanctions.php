<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/student_sanctions.php';

require_curator_panel();
ensure_student_sanctions_schema();

$ctx = resolve_curator_group_context(isset($_GET['group_id']) ? (int) $_GET['group_id'] : null);
$groups = $ctx['groups'];
$groupId = $ctx['group_id'];
$group = $ctx['group'];
$students = $group ? get_students_by_group($groupId) : [];
$error = flash_get('error') ?: $ctx['error'];
$success = flash_get('success');
$user = current_user();

$studentIds = array_map(static fn (array $s): int => (int) $s['id'], $students);
$allowed = array_fill_keys($studentIds, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $group !== null) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Ошибка безопасности. Обновите страницу и попробуйте снова.');
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $studentId = (int) ($_POST['student_id'] ?? 0);

        if (!isset($allowed[$studentId])) {
            flash_set('error', 'Студент не принадлежит выбранной группе.');
        } elseif ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $result = save_student_sanction(
                $studentId,
                (string) ($_POST['issued_on'] ?? ''),
                (string) ($_POST['order_number'] ?? ''),
                (string) ($_POST['description'] ?? ''),
                $_FILES['scan_file'] ?? null,
                $user ? (int) ($user['id'] ?? 0) ?: null : null,
                $id > 0 ? $id : null,
                (string) ($_POST['sanction_type'] ?? 'remark')
            );
            if ($result['success']) {
                flash_set('success', $id > 0 ? 'Взыскание обновлено.' : 'Взыскание добавлено.');
            } else {
                flash_set('error', $result['error'] ?? 'Не удалось сохранить.');
            }
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $result = delete_student_sanction($id, $studentId);
            if ($result['success']) {
                flash_set('success', 'Запись о взыскании удалена.');
            } else {
                flash_set('error', $result['error'] ?? 'Не удалось удалить.');
            }
        } else {
            flash_set('error', 'Неизвестное действие.');
        }
    }

    $redirectStudent = (int) ($_POST['student_id'] ?? 0);
    $q = 'sanctions.php?group_id=' . $groupId;
    if ($redirectStudent > 0 && isset($allowed[$redirectStudent])) {
        $q .= '&student_id=' . $redirectStudent;
    }
    header('Location: ' . $q);
    exit;
}

$selectedStudentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
if ($selectedStudentId > 0 && !isset($allowed[$selectedStudentId])) {
    $selectedStudentId = 0;
}

$sanctionsMap = $students !== [] ? get_students_sanctions_map($studentIds) : [];
$allRows = [];
foreach ($sanctionsMap as $sid => $rows) {
    $name = '';
    foreach ($students as $st) {
        if ((int) $st['id'] === (int) $sid) {
            $name = (string) $st['full_name'];
            break;
        }
    }
    foreach ($rows as $row) {
        $row['student_name'] = $name;
        $allRows[] = $row;
    }
}
$byYear = group_sanctions_by_academic_year($allRows);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editRow = $editId > 0 ? get_student_sanction($editId) : null;
if ($editRow !== null && !isset($allowed[(int) $editRow['student_id']])) {
    $editRow = null;
    $editId = 0;
}
if ($editRow !== null) {
    $selectedStudentId = (int) $editRow['student_id'];
}

$pageTitle = 'Взыскания — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'sanctions';
$curatorGroupId = $groupId;
$curatorGroups = $groups;
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide curator-page curator-sanctions-page">
    <?php
    $curatorPageTitle = 'Взыскания';
    $curatorPageSubtitle = $group
        ? ('Группа ' . (string) $group['number'] . ' · дисциплинарные взыскания')
        : 'Учёт дисциплинарных взысканий студентов';
    $curatorPageIcon = 'sanctions';
    require __DIR__ . '/../includes/curator_page_hero.php';
    ?>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($groups === []): ?>
        <section class="panel">
            <p class="text-muted">Вам ещё не назначена группа. Обратитесь к администратору.</p>
        </section>
    <?php elseif ($group === null): ?>
        <section class="panel">
            <p class="text-muted">Выберите группу, чтобы вести учёт взысканий.</p>
        </section>
    <?php else: ?>
        <section class="panel">
            <h2><?= $editRow ? 'Редактировать взыскание' : 'Добавить взыскание' ?></h2>
            <p class="text-muted">
                Укажите студента, дату, номер приказа и при необходимости приложите скан приказа
                (PDF или изображение, до 10 МБ). Записи хранятся за весь период обучения и группируются по учебным годам.
            </p>
            <form method="post" enctype="multipart/form-data" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $editRow ? (int) $editRow['id'] : 0 ?>">

                <div class="form__grid form__grid--2">
                    <div class="form__group">
                        <label for="sanction_student">Студент</label>
                        <select id="sanction_student" name="student_id" required<?= $editRow ? ' disabled' : '' ?>>
                            <option value="">— выберите —</option>
                            <?php foreach ($students as $st): ?>
                                <option value="<?= (int) $st['id'] ?>"
                                    <?= $selectedStudentId === (int) $st['id'] ? ' selected' : '' ?>>
                                    <?= e((string) $st['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($editRow): ?>
                            <input type="hidden" name="student_id" value="<?= (int) $editRow['student_id'] ?>">
                        <?php endif; ?>
                    </div>
                    <div class="form__group">
                        <label for="sanction_type">Тип взыскания</label>
                        <?php
                        $selectedType = normalize_student_sanction_type(
                            (string) ($editRow['sanction_type'] ?? 'remark')
                        );
                        ?>
                        <select id="sanction_type" name="sanction_type" required>
                            <?php foreach (student_sanction_type_options() as $typeValue => $typeLabel): ?>
                                <option value="<?= e($typeValue) ?>"<?= $selectedType === $typeValue ? ' selected' : '' ?>>
                                    <?= e($typeLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form__group">
                        <label for="sanction_date">Дата вынесения</label>
                        <input
                            type="date"
                            id="sanction_date"
                            name="issued_on"
                            required
                            value="<?= e($editRow ? (string) $editRow['issued_on'] : date('Y-m-d')) ?>"
                        >
                    </div>
                    <div class="form__group">
                        <label for="sanction_order">Номер приказа</label>
                        <input
                            type="text"
                            id="sanction_order"
                            name="order_number"
                            required
                            maxlength="100"
                            placeholder="например, 45-к"
                            value="<?= e($editRow ? (string) $editRow['order_number'] : '') ?>"
                        >
                    </div>
                    <div class="form__group">
                        <label for="sanction_desc">Краткое описание (необязательно)</label>
                        <input
                            type="text"
                            id="sanction_desc"
                            name="description"
                            maxlength="500"
                            placeholder="за пропуски без уважительной причины"
                            value="<?= e($editRow ? (string) ($editRow['description'] ?? '') : '') ?>"
                        >
                    </div>
                    <div class="form__group form__group--full">
                        <label for="sanction_scan">Скан / фото приказа</label>
                        <input
                            type="file"
                            id="sanction_scan"
                            name="scan_file"
                            accept=".pdf,.jpg,.jpeg,.jpe,.jfif,.png,.webp,.gif,.bmp,.tif,.tiff,application/pdf,image/*"
                        >
                        <?php if ($editRow && !empty($editRow['scan_stored_name'])): ?>
                            <p class="form__hint">
                                Сейчас прикреплён:
                                <a href="sanction_file.php?id=<?= (int) $editRow['id'] ?>" target="_blank" rel="noopener">
                                    <?= e((string) ($editRow['scan_original_name'] ?: $editRow['scan_stored_name'])) ?>
                                </a>
                                — новый файл заменит текущий.
                            </p>
                        <?php else: ?>
                            <p class="form__hint">
                                Можно прикрепить фото или скан приказа (как «ПРИКАЗ № …»): PDF, JPG, PNG, WEBP, GIF, BMP, TIFF, до 20 МБ.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form__actions">
                    <button type="submit" class="btn btn--primary">
                        <?= $editRow ? 'Сохранить изменения' : 'Добавить' ?>
                    </button>
                    <?php if ($editRow): ?>
                        <a class="btn btn--ghost" href="sanctions.php?group_id=<?= (int) $groupId ?>">Отмена</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2>Взыскания группы</h2>
            <?php if ($byYear === []): ?>
                <p class="text-muted">Записей пока нет.</p>
            <?php else: ?>
                <?php foreach ($byYear as $yearBlock): ?>
                    <h3 class="subsection-title">Учебный год <?= e((string) $yearBlock['academic_year']) ?></h3>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Студент</th>
                                    <th>Тип</th>
                                    <th>Дата</th>
                                    <th>Приказ</th>
                                    <th>Описание</th>
                                    <th>Скан</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($yearBlock['items'] as $row): ?>
                                    <tr>
                                        <td><strong><?= e((string) $row['student_name']) ?></strong></td>
                                        <td><?= e(student_sanction_type_label((string) ($row['sanction_type'] ?? 'remark'))) ?></td>
                                        <td><?= e(format_sanction_date((string) $row['issued_on'])) ?></td>
                                        <td>№ <?= e((string) $row['order_number']) ?></td>
                                        <td><?= e((string) ($row['description'] ?? '') ?: '—') ?></td>
                                        <td>
                                            <?php if (!empty($row['scan_stored_name'])): ?>
                                                <a href="sanction_file.php?id=<?= (int) $row['id'] ?>" target="_blank" rel="noopener">Открыть</a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="table-actions">
                                            <a
                                                class="btn btn--ghost btn--sm"
                                                href="sanctions.php?group_id=<?= (int) $groupId ?>&edit=<?= (int) $row['id'] ?>"
                                            >Изменить</a>
                                            <form method="post" class="inline-form" onsubmit="return confirm('Удалить запись о взыскании?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                <input type="hidden" name="student_id" value="<?= (int) $row['student_id'] ?>">
                                                <button type="submit" class="btn btn--ghost btn--sm">Удалить</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
