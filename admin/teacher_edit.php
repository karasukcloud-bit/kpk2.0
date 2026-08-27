<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/teachers.php';

require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$teacher = get_teacher_by_id($id);

if ($teacher === null) {
    flash_set('error', 'Преподаватель не найден.');
    header('Location: teachers.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = $_POST['action'] ?? 'save';

        if ($action === 'delete') {
            $result = delete_teacher($id);

            if ($result['success']) {
                flash_set('success', 'Преподаватель удалён из системы.');
                header('Location: teachers.php');
                exit;
            }

            $error = $result['error'];
        } elseif ($action === 'change_password') {
            $result = admin_set_teacher_password(
                $id,
                (string) ($_POST['new_password'] ?? ''),
                (string) ($_POST['new_password_confirm'] ?? '')
            );

            if ($result['success']) {
                flash_set('success', 'Пароль преподавателя изменён.');
                header('Location: teacher_edit.php?id=' . $id);
                exit;
            }

            $error = $result['error'];
        } elseif ($action === 'regenerate_password') {
            $result = regenerate_teacher_password($id);

            if ($result['success']) {
                flash_set(
                    'success',
                    'Новый пароль сгенерирован. Логин: ' . $result['login'] . ' · Пароль: ' . $result['password']
                );
                header('Location: teacher_edit.php?id=' . $id);
                exit;
            }

            $error = $result['error'];
        } else {
            $fullName = compose_person_full_name(
                (string) ($_POST['last_name'] ?? ''),
                (string) ($_POST['first_name'] ?? ''),
                (string) ($_POST['middle_name'] ?? '')
            );

            $result = update_teacher(
                $id,
                $_POST['email'] ?? '',
                $fullName,
                isset($_POST['is_active']),
                posted_staff_roles(),
                null,
                posted_curator_group_id(),
                $_POST['phone'] ?? ''
            );

            if ($result['success']) {
                flash_set('success', 'Данные преподавателя обновлены.');
                header('Location: teachers.php');
                exit;
            }

            $error = $result['error'];
            $teacher = array_merge($teacher, [
                'full_name' => $fullName,
                'email' => $_POST['email'] ?? $teacher['email'],
                'phone' => $_POST['phone'] ?? ($teacher['phone'] ?? ''),
            ]);
        }
    }
}

$pageTitle = 'Редактирование — ' . $teacher['full_name'];
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'teachers';
require __DIR__ . '/../includes/header.php';

$success = flash_get('success');
$nameParts = split_person_full_name((string) $teacher['full_name']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'save')) {
    $nameParts = [
        'last_name' => (string) ($_POST['last_name'] ?? ''),
        'first_name' => (string) ($_POST['first_name'] ?? ''),
        'middle_name' => (string) ($_POST['middle_name'] ?? ''),
    ];
}
$emailDisplay = $_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'save')
    ? (string) ($_POST['email'] ?? '')
    : display_user_email((string) ($teacher['email'] ?? ''));
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Редактирование преподавателя</h1>
                <p class="text-muted"><?= e($teacher['full_name']) ?></p>
            </div>
            <a href="teachers.php" class="btn btn--ghost">← К списку</a>
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
        <form method="post" class="form form--narrow">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">

            <div class="form__group">
                <label for="last_name">Фамилия</label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    required
                    value="<?= e($nameParts['last_name']) ?>"
                    autocomplete="family-name"
                >
            </div>

            <div class="form__group">
                <label for="first_name">Имя</label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    required
                    value="<?= e($nameParts['first_name']) ?>"
                    autocomplete="given-name"
                >
            </div>

            <div class="form__group">
                <label for="middle_name">Отчество</label>
                <input
                    type="text"
                    id="middle_name"
                    name="middle_name"
                    value="<?= e($nameParts['middle_name']) ?>"
                    autocomplete="additional-name"
                >
            </div>

            <div class="form__group">
                <label for="phone">Телефон (логин) <span class="text-muted">*</span></label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    required
                    data-phone-login
                    value="<?= e(format_login_phone($_POST['phone'] ?? ($teacher['phone'] ?? ''))) ?>"
                    placeholder="+7XXXXXXXXXX"
                >
            </div>

            <div class="form__group">
                <label for="email">Email <span class="text-muted">(необязательно)</span></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($emailDisplay) ?>"
                >
            </div>

            <?php
            $selectedRoles = $_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'save')
                ? posted_staff_roles()
                : ($teacher['staff_roles'] ?? ['teacher']);
            $selectedCuratorGroupId = $_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'save')
                ? posted_curator_group_id()
                : ($teacher['curator_group_id'] ?? null);
            $forCuratorUserId = $id;
            require __DIR__ . '/../includes/role_fields.php';
            ?>

            <div class="form__group form__group--checkbox">
                <?php
                $isActiveChecked = $_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'save')
                    ? isset($_POST['is_active'])
                    : (bool) $teacher['is_active'];
                ?>
                <label class="checkbox-label">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        <?= $isActiveChecked ? 'checked' : '' ?>
                    >
                    Активная учётная запись
                </label>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сохранить изменения</button>
                <a href="teachers.php" class="btn btn--ghost">Отмена</a>
            </div>
        </form>

        <hr class="divider">

        <div class="student-creds teacher-creds">
            <h3>Регистрационные данные преподавателя</h3>
            <dl class="profile-list">
                <dt>Логин (телефон)</dt>
                <dd><?= e(($teacher['phone'] ?? '') !== '' ? format_login_phone($teacher['phone']) : '—') ?></dd>
                <dt>Пароль</dt>
                <dd><?= e(($teacher['password_plain'] ?? '') !== '' ? $teacher['password_plain'] : '—') ?></dd>
            </dl>
            <form method="post" class="student-creds__actions form-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="regenerate_password">
                <button
                    type="submit"
                    class="btn btn--primary btn--sm"
                    onclick="return confirm('Сгенерировать новый пароль для преподавателя?')"
                >Сгенерировать пароль</button>
            </form>
        </div>

        <hr class="divider">

        <h3 class="subsection-title">Смена пароля вручную</h3>
        <p class="text-muted form-hint">Можно задать свой пароль вместо сгенерированного.</p>
        <form method="post" class="form form--narrow" data-password-generate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">

            <div class="form__group">
                <label for="new_password">Новый пароль</label>
                <div class="form__inline-actions">
                    <input
                        type="text"
                        id="new_password"
                        name="new_password"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        placeholder="Минимум 6 символов"
                    >
                    <button
                        type="button"
                        class="btn btn--ghost"
                        data-generate-password
                        data-password-target="new_password"
                        data-password-confirm-target="new_password_confirm"
                    >Сгенерировать</button>
                </div>
            </div>

            <div class="form__group">
                <label for="new_password_confirm">Подтверждение пароля</label>
                <input
                    type="text"
                    id="new_password_confirm"
                    name="new_password_confirm"
                    required
                    minlength="6"
                    autocomplete="new-password"
                    placeholder="Повторите пароль"
                >
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сменить пароль</button>
            </div>
        </form>

        <hr class="divider">

        <form method="post" class="form form--narrow" onsubmit="return confirm('Удалить преподавателя из системы? Это действие необратимо.');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn--danger">Удалить преподавателя</button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
