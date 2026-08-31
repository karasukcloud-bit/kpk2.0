<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/student_accounts.php';
require_once __DIR__ . '/../includes/expelled.php';

require_curator_panel();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$student = get_student_by_id($id);

if ($student === null) {
    flash_set('error', 'Студент не найден.');
    header('Location: group.php');
    exit;
}

$group = require_group_access((int) $student['group_id']);
$groupId = (int) $group['id'];
$error = null;
$showStudentEdit = false;
$data = $student;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'delete') {
            $result = delete_student($id);
            if ($result['success']) {
                flash_set('success', 'Студент удалён из группы.');
                header('Location: group.php?group_id=' . $groupId);
                exit;
            }
            $error = $result['error'];
        } elseif ($action === 'expel') {
            $result = expel_student(
                $id,
                (string) ($_POST['expulsion_order'] ?? ''),
                (string) ($_POST['expulsion_date'] ?? ''),
                (string) ($_POST['expulsion_reason'] ?? '')
            );
            if ($result['success']) {
                flash_set('success', 'Студент отчислен и перенесён в архив отчисленных.');
                header('Location: group.php?group_id=' . $groupId);
                exit;
            }
            $error = $result['error'];
        } elseif ($action === 'create_account') {
            $result = ensure_student_account($id);
            if ($result['success']) {
                flash_set(
                    'success',
                    curator_show_student_auth_data()
                        ? (!empty($result['created'])
                            ? ('Учётная запись создана. Логин: ' . $result['login'] . ' · Пароль: ' . $result['password'])
                            : ('Учётная запись уже есть. Логин: ' . $result['login']))
                        : (!empty($result['created'])
                            ? 'Учётная запись создана.'
                            : 'Учётная запись уже есть.')
                );
                header('Location: student_edit.php?id=' . $id);
                exit;
            }
            $error = $result['error'];
        } elseif ($action === 'regenerate_password') {
            $result = regenerate_student_password($id);
            if ($result['success']) {
                flash_set(
                    'success',
                    curator_show_student_auth_data()
                        ? ('Новый пароль: ' . $result['password'] . ' (логин: ' . $result['login'] . ')')
                        : 'Новый пароль сгенерирован.'
                );
                header('Location: student_edit.php?id=' . $id);
                exit;
            }
            $error = $result['error'];
        } else {
            $data = student_payload_from_post($_POST);
            $result = update_student($id, $data);
            if ($result['success']) {
                flash_set('success', 'Данные студента сохранены.');
                header('Location: student_edit.php?id=' . $id);
                exit;
            }
            $error = $result['error'];
            $showStudentEdit = true;
        }
    }
}

$student = get_student_by_id($id) ?? $student;
$account = get_student_account($id);
$pageTitle = 'Карточка студента';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'group';
$curatorGroupId = $groupId;
$curatorGroups = get_groups_for_curator();
require __DIR__ . '/../includes/header.php';
$success = flash_get('success');

$displayOrDash = static function (?string $value): string {
    $value = trim((string) $value);

    return $value !== '' ? $value : '—';
};

$loginDisplay = '';
if ($account) {
    $loginDisplay = (string) $account['email'];
    if (substr($loginDisplay, -13) === '@student.local') {
        $loginDisplay = substr($loginDisplay, 0, -13);
    }
}
$studentAvatar = normalize_avatar_value((string) ($account['avatar'] ?? 'icon:person'));
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1><?= e($student['full_name']) ?></h1>
                <p class="text-muted">Группа <?= e($group['number']) ?></p>
            </div>
            <a href="group.php?group_id=<?= $groupId ?>" class="btn btn--ghost">← К списку группы</a>
        </div>

        <?php require __DIR__ . '/../includes/curator_nav.php'; ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <div class="student-card-view" data-profile-view<?= $showStudentEdit ? ' hidden' : '' ?>>
            <div class="cabinet-avatar-block cabinet-avatar-block--view">
                <?= render_user_avatar($studentAvatar, 'user-avatar--lg', $basePath) ?>
            </div>
            <dl class="profile-list">
                <dt>ФИО студента</dt>
                <dd><?= e($displayOrDash($student['full_name'] ?? '')) ?></dd>
                <dt>Телефон студента</dt>
                <dd><?= e($displayOrDash($student['phone'] ?? '')) ?></dd>
                <dt>СНИЛС</dt>
                <dd><?= e($displayOrDash($student['snils'] ?? '')) ?></dd>
                <dt>Дата рождения</dt>
                <dd><?= e(format_student_birth_date(isset($student['birth_date']) ? (string) $student['birth_date'] : null)) ?></dd>
                <dt>Пол</dt>
                <dd><?= e(student_gender_label(isset($student['gender']) ? (string) $student['gender'] : null)) ?></dd>
                <dt><?= e(student_parent_field_label('mother', 'name')) ?></dt>
                <dd><?= e($displayOrDash($student['mother_name'] ?? '')) ?></dd>
                <dt><?= e(student_parent_field_label('mother', 'phone')) ?></dt>
                <dd><?= e($displayOrDash($student['mother_phone'] ?? '')) ?></dd>
                <dt><?= e(student_parent_field_label('mother', 'workplace')) ?></dt>
                <dd><?= e($displayOrDash($student['mother_workplace'] ?? '')) ?></dd>
                <dt><?= e(student_parent_field_label('mother', 'education')) ?></dt>
                <dd><?= e(student_education_label($student['mother_education'] ?? null)) ?></dd>
                <dt><?= e(student_parent_field_label('father', 'name')) ?></dt>
                <dd><?= e($displayOrDash($student['father_name'] ?? '')) ?></dd>
                <dt><?= e(student_parent_field_label('father', 'phone')) ?></dt>
                <dd><?= e($displayOrDash($student['father_phone'] ?? '')) ?></dd>
                <dt><?= e(student_parent_field_label('father', 'workplace')) ?></dt>
                <dd><?= e($displayOrDash($student['father_workplace'] ?? '')) ?></dd>
                <dt><?= e(student_parent_field_label('father', 'education')) ?></dt>
                <dd><?= e(student_education_label($student['father_education'] ?? null)) ?></dd>
                <dt>Адрес по прописке</dt>
                <dd><?= e(format_student_registered_address($student)) ?></dd>
                <dt>Область / край</dt>
                <dd><?= e($displayOrDash($student['address_region'] ?? '')) ?></dd>
                <dt>Район / округ</dt>
                <dd><?= e($displayOrDash($student['address_district'] ?? '')) ?></dd>
                <dt>Населённый пункт</dt>
                <dd><?= e($displayOrDash($student['address_locality'] ?? '')) ?></dd>
                <dt>Улица</dt>
                <dd><?= e($displayOrDash($student['address_street'] ?? '')) ?></dd>
                <dt>Дом</dt>
                <dd><?= e($displayOrDash($student['address_house'] ?? '')) ?></dd>
                <dt>Фактический адрес</dt>
                <dd><?= e($displayOrDash($student['address_actual'] ?? '')) ?></dd>
                <dt>Состав семьи</dt>
                <dd><?= e(student_family_type_label(isset($student['family_type']) ? (string) $student['family_type'] : null)) ?></dd>
                <dt>Братьев/сестёр младше 18</dt>
                <dd><?= e((string) (int) ($student['siblings_under_18'] ?? 0)) ?></dd>
                <dt>Место проживания</dt>
                <dd><?= e(student_residence_type_label(isset($student['residence_type']) ? (string) $student['residence_type'] : null)) ?></dd>
                <dt>Иногородний</dt>
                <dd><?= !empty($student['is_nonresident']) ? 'Да' : 'Нет' ?></dd>
                <dt>Малообеспеченная семья</dt>
                <dd><?= !empty($student['is_low_income']) ? 'Да' : 'Нет' ?></dd>
                <dt>Без попечительства родителей</dt>
                <dd><?= !empty($student['without_parental_care']) ? 'Да' : 'Нет' ?></dd>
            </dl>

            <?php if (curator_show_student_auth_data()): ?>
            <div class="student-creds">
                <h3>Учётная запись студента</h3>
                <?php if ($account): ?>
                    <dl class="profile-list">
                        <dt>Логин</dt>
                        <dd><?= e($loginDisplay) ?></dd>
                        <dt>Пароль</dt>
                        <dd><?= e(($account['password_plain'] ?? '') !== '' ? $account['password_plain'] : '—') ?></dd>
                    </dl>
                    <form method="post" class="student-creds__actions form-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="regenerate_password">
                        <button
                            type="submit"
                            class="btn btn--primary btn--sm"
                            onclick="return confirm('Сгенерировать новый пароль для студента?')"
                        >Сгенерировать пароль</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Учётная запись ещё не создана.</p>
                    <form method="post" class="student-creds__actions">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create_account">
                        <button type="submit" class="btn btn--primary btn--sm">Создать учётную запись</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="form__actions">
                <button type="button" class="btn btn--primary" data-profile-edit-open>Редактировать</button>
                <button type="button" class="btn btn--secondary" data-expel-open>Студент отчислен</button>
                <a href="group.php?group_id=<?= $groupId ?>" class="btn btn--ghost">Отмена</a>
            </div>

            <hr class="divider">

            <form method="post" class="form form--narrow"
                  onsubmit="return confirm('Удалить студента из группы? Это действие необратимо.');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn--danger">Удалить студента</button>
            </form>
        </div>

        <div class="student-card-edit" data-profile-edit<?= $showStudentEdit ? '' : ' hidden' ?>>
            <h2 class="subsection-title">Редактирование карточки</h2>
            <form method="post" class="form form--medium">
                <?= csrf_field() ?>
                <?php require __DIR__ . '/../includes/student_fields.php'; ?>
                <div class="form__actions">
                    <button type="submit" class="btn btn--primary">Сохранить изменения</button>
                    <a href="student_edit.php?id=<?= $id ?>" class="btn btn--ghost" data-profile-edit-cancel>Отмена</a>
                </div>
            </form>
        </div>
    </section>
</div>

<div class="modal" id="student-expel-modal" hidden>
    <div class="modal__backdrop" data-expel-close></div>
    <div class="modal__dialog modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="student-expel-title">
        <div class="modal__header">
            <h2 id="student-expel-title">Отчисление студента</h2>
            <button type="button" class="modal__close" data-expel-close aria-label="Закрыть">×</button>
        </div>
        <p class="text-muted">
            <?= e($student['full_name']) ?> будет удалён из списка группы
            <?= e($group['number']) ?> и из ГЛАЗ.
            Архивные ведомости и журналы не изменяются — данные в них сохраняются.
            Карточка отчисленного (зачётка и задолженности) будет доступна в разделе «Отчисленные».
        </p>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="expel">
            <div class="form__group">
                <label for="expulsion_order">Номер приказа</label>
                <input type="text" id="expulsion_order" name="expulsion_order" required
                       value="<?= e($_POST['expulsion_order'] ?? '') ?>" placeholder="№ ...">
            </div>
            <div class="form__group">
                <label for="expulsion_date">Дата отчисления</label>
                <input type="date" id="expulsion_date" name="expulsion_date" required
                       value="<?= e($_POST['expulsion_date'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form__group">
                <label for="expulsion_reason">Причина отчисления</label>
                <textarea id="expulsion_reason" name="expulsion_reason" rows="3" required
                          placeholder="Укажите причину"><?= e($_POST['expulsion_reason'] ?? '') ?></textarea>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--danger">Подтвердить отчисление</button>
                <button type="button" class="btn btn--ghost" data-expel-close>Отмена</button>
            </div>
        </form>
    </div>
</div>
<script>
(() => {
    const modal = document.getElementById('student-expel-modal');
    if (!modal) return;
    const open = () => { modal.hidden = false; document.body.classList.add('modal-open'); };
    const close = () => { modal.hidden = true; document.body.classList.remove('modal-open'); };
    document.querySelectorAll('[data-expel-open]').forEach((btn) => btn.addEventListener('click', open));
    document.querySelectorAll('[data-expel-close]').forEach((btn) => btn.addEventListener('click', close));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) close(); });
    <?php if ($error && ($_SERVER['REQUEST_METHOD'] === 'POST') && (($_POST['action'] ?? '') === 'expel')): ?>
    open();
    <?php endif; ?>
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
