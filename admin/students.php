<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';

require_admin();

$groups = get_all_groups();
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } elseif (($_POST['action'] ?? '') === 'transfer') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $toGroupId = (int) ($_POST['to_group_id'] ?? 0);
        $result = transfer_student(
            $studentId,
            $toGroupId,
            (string) ($_POST['additional_info'] ?? '')
        );
        if ($result['success']) {
            $toGroup = get_group_by_id($toGroupId);
            flash_set(
                'success',
                'Студент переведён в группу ' . ($toGroup['number'] ?? '') . '.'
            );
            $redirectGroup = (int) ($_POST['filter_group_id'] ?? $groupId);
            header('Location: students.php' . ($redirectGroup > 0 ? '?group_id=' . $redirectGroup : ''));
            exit;
        }
        $error = $result['error'];
        $groupId = (int) ($_POST['filter_group_id'] ?? $groupId);
    }
}

$group = null;
if ($groupId > 0) {
    $group = get_group_by_id($groupId);
    if ($group === null) {
        $groupId = 0;
    }
}

$students = get_students_list($groupId > 0 ? $groupId : null);
$showAllStudents = $groupId === 0;

$success = flash_get('success');
$transferStudentId = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;

$pageTitle = 'Студенты — Администрирование';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'students';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель администратора</h1>
                <p class="text-muted">Все студенты · фильтр по группам · перевод между группами</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/admin_nav.php'; ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <?php if ($groups === []): ?>
            <p class="text-muted">В системе пока нет групп.</p>
        <?php else: ?>
            <form method="get" class="form form--filter">
                <div class="form__row form__row--filter">
                    <div class="form__group">
                        <label for="group_id">Группа</label>
                        <select id="group_id" name="group_id" onchange="this.form.submit()">
                            <option value=""<?= $groupId === 0 ? ' selected' : '' ?>>Все студенты</option>
                            <?php foreach ($groups as $item): ?>
                            <option value="<?= (int) $item['id'] ?>"<?= (int) $item['id'] === $groupId ? ' selected' : '' ?>>
                                <?= e($item['number']) ?> · <?= e($item['specialty_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>

            <?php if ($students === []): ?>
                <p class="text-muted">
                    <?php if ($showAllStudents): ?>
                        В системе пока нет студентов.
                    <?php else: ?>
                        В группе <strong><?= e($group['number']) ?></strong> пока нет студентов.
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <p class="text-muted">
                    <?php if ($showAllStudents): ?>
                        Всего студентов: <strong><?= count($students) ?></strong>
                    <?php else: ?>
                        Группа <strong><?= e($group['number']) ?></strong>
                        · <?= e($group['specialty_name']) ?>
                        · студентов: <?= count($students) ?>
                    <?php endif; ?>
                </p>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>ФИО</th>
                                <?php if ($showAllStudents): ?>
                                <th>Группа</th>
                                <?php endif; ?>
                                <th>Телефон</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($student['full_name']) ?></td>
                                <?php if ($showAllStudents): ?>
                                <td><?= e($student['group_number'] ?? '—') ?></td>
                                <?php endif; ?>
                                <td><?= e(($student['phone'] ?? '') !== '' ? $student['phone'] : '—') ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn--primary btn--sm"
                                        data-transfer-open
                                        data-student-id="<?= (int) $student['id'] ?>"
                                        data-student-name="<?= e($student['full_name']) ?>"
                                        data-from-group-id="<?= (int) $student['group_id'] ?>"
                                        data-student-group="<?= e($student['group_number'] ?? '') ?>"
                                    >Перевести студента</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php if ($groups !== [] && $students !== []): ?>
<div class="modal" id="student-transfer-modal" hidden>
    <div class="modal__backdrop" data-transfer-close></div>
    <div class="modal__dialog modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="student-transfer-title">
        <div class="modal__header">
            <h2 id="student-transfer-title">Перевод студента</h2>
            <button type="button" class="modal__close" data-transfer-close aria-label="Закрыть">×</button>
        </div>
        <p class="text-muted">
            Студент: <strong data-transfer-name></strong>.
            Текущая группа: <strong data-transfer-group></strong>.
        </p>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="transfer">
            <input type="hidden" name="filter_group_id" value="<?= $groupId ?>">
            <input type="hidden" name="from_group_id" id="transfer_from_group_id" value="">
            <input type="hidden" name="student_id" id="transfer_student_id" value="">
            <div class="form__group">
                <label for="to_group_id">Группа назначения</label>
                <select id="to_group_id" name="to_group_id" required>
                    <option value="">— Выберите группу —</option>
                    <?php foreach ($groups as $item): ?>
                    <option value="<?= (int) $item['id'] ?>">
                        <?= e($item['number']) ?> · <?= e($item['specialty_name']) ?>
                        <?php if (($item['specialty_code'] ?? '') !== ''): ?>
                            (<?= e($item['specialty_code']) ?>)
                        <?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form__group">
                <label for="additional_info">Дополнительная информация</label>
                <textarea id="additional_info" name="additional_info" rows="3"
                          placeholder="Например: смена специальности"><?= e($_POST['additional_info'] ?? '') ?></textarea>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Перевести</button>
                <button type="button" class="btn btn--ghost" data-transfer-close>Отмена</button>
            </div>
        </form>
    </div>
</div>
<script>
(() => {
    const modal = document.getElementById('student-transfer-modal');
    if (!modal) return;
    const idInput = document.getElementById('transfer_student_id');
    const fromGroupInput = document.getElementById('transfer_from_group_id');
    const nameNode = modal.querySelector('[data-transfer-name]');
    const groupNode = modal.querySelector('[data-transfer-group]');
    const toSelect = document.getElementById('to_group_id');
    const open = (studentId, studentName, fromGroupId, groupNumber) => {
        if (idInput) idInput.value = String(studentId);
        if (fromGroupInput) fromGroupInput.value = String(fromGroupId);
        if (nameNode) nameNode.textContent = studentName || '';
        if (groupNode) groupNode.textContent = groupNumber || '—';
        if (toSelect) {
            Array.from(toSelect.options).forEach((opt) => {
                if (opt.value === '') {
                    opt.hidden = false;
                    return;
                }
                opt.hidden = opt.value === String(fromGroupId);
            });
            toSelect.value = '';
        }
        modal.hidden = false;
        document.body.classList.add('modal-open');
    };
    const close = () => {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
    };
    document.querySelectorAll('[data-transfer-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            open(
                btn.dataset.studentId || '',
                btn.dataset.studentName || '',
                btn.dataset.fromGroupId || '',
                btn.dataset.studentGroup || ''
            );
        });
    });
    document.querySelectorAll('[data-transfer-close]').forEach((btn) => {
        btn.addEventListener('click', close);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) close();
    });
    <?php if ($error && $transferStudentId > 0): ?>
    <?php
    $failedName = '';
    $failedGroupId = 0;
    $failedGroupNumber = '';
    foreach ($students as $s) {
        if ((int) $s['id'] === $transferStudentId) {
            $failedName = (string) $s['full_name'];
            $failedGroupId = (int) $s['group_id'];
            $failedGroupNumber = (string) ($s['group_number'] ?? '');
            break;
        }
    }
    ?>
    open(
        <?= (int) $transferStudentId ?>,
        <?= json_encode($failedName, JSON_UNESCAPED_UNICODE) ?>,
        <?= (int) $failedGroupId ?>,
        <?= json_encode($failedGroupNumber, JSON_UNESCAPED_UNICODE) ?>
    );
    <?php endif; ?>
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
