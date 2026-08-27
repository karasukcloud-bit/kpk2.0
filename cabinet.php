<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/profile.php';
require_once __DIR__ . '/includes/roles.php';

require_login();

if (is_student_user()) {
    redirect_to('student/cabinet.php');
}

$user = current_user();
$error = null;
$showProfileEdit = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_profile') {
            $result = update_own_profile($_POST, $_FILES);
            $showProfileEdit = true;
        } elseif ($action === 'change_password') {
            $result = ['success' => false, 'error' => 'Смена пароля доступна только администратору.'];
            $showProfileEdit = true;
        } else {
            $result = ['success' => false, 'error' => 'Неизвестное действие.'];
        }

        if ($result['success']) {
            flash_set('success', 'Профиль сохранён.');
            header('Location: cabinet.php');
            exit;
        }

        $error = $result['error'];
        if ($action === 'save_profile') {
            $composedName = compose_person_full_name(
                (string) ($_POST['last_name'] ?? ''),
                (string) ($_POST['first_name'] ?? ''),
                (string) ($_POST['middle_name'] ?? '')
            );
            $user = array_merge($user, [
                'full_name' => $composedName !== '' ? $composedName : $user['full_name'],
                'email' => $_POST['email'] ?? $user['email'],
                'phone' => $_POST['phone'] ?? ($user['phone'] ?? ''),
                'position' => $_POST['position'] ?? ($user['position'] ?? ''),
                'education' => $_POST['education'] ?? ($user['education'] ?? ''),
                'additional_info' => $_POST['additional_info'] ?? ($user['additional_info'] ?? ''),
            ]);
        }
    }
}

$success = flash_get('success');
$flashError = flash_get('error');
$error = $error ?? $flashError;
$pageTitle = 'Личный кабинет';
$showHeader = true;
require __DIR__ . '/includes/header.php';

$displayOrDash = static function (?string $value): string {
    $value = trim((string) $value);
    return $value !== '' ? $value : '—';
};

$userAvatar = normalize_avatar_value((string) ($user['avatar'] ?? 'icon:person'));
$avatarIsUpload = strpos($userAvatar, 'file:') === 0;
$selectedIcon = $avatarIsUpload ? 'person' : user_avatar_icon_key($userAvatar);
$avatarPresets = avatar_presets();
$nameParts = split_person_full_name((string) ($user['full_name'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'save_profile')) {
    $nameParts = [
        'last_name' => (string) ($_POST['last_name'] ?? ''),
        'first_name' => (string) ($_POST['first_name'] ?? ''),
        'middle_name' => (string) ($_POST['middle_name'] ?? ''),
    ];
}
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <h1>Личный кабинет</h1>
        <p class="text-muted">Личные данные и настройки профиля.</p>

        <?php if ($success): ?>
            <div class="alert alert--success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="cabinet-profile-view" data-profile-view<?= $showProfileEdit ? ' hidden' : '' ?>>
            <div class="cabinet-avatar-block cabinet-avatar-block--view">
                <?= render_user_avatar($userAvatar, 'user-avatar--lg', $basePath) ?>
            </div>
            <dl class="profile-list">
                <dt>ФИО</dt>
                <dd><?= e($displayOrDash($user['full_name'] ?? '')) ?></dd>
                <dt>Email</dt>
                <dd><?= e($displayOrDash(display_user_email((string) ($user['email'] ?? '')))) ?></dd>
                <dt>Телефон (логин)</dt>
                <dd><?= e($displayOrDash($user['phone'] ?? '')) ?></dd>
                <dt>Должность</dt>
                <dd><?= e($displayOrDash($user['position'] ?? '')) ?></dd>
                <dt>Образование</dt>
                <dd><?= e($displayOrDash($user['education'] ?? '')) ?></dd>
                <dt>Дополнительная информация</dt>
                <dd class="cabinet-profile-multiline"><?= e($displayOrDash($user['additional_info'] ?? '')) ?></dd>
                <dt>Роли</dt>
                <dd>
                    <?php if (is_admin()): ?>
                        <?= e(role_label('admin')) ?>
                    <?php else: ?>
                        <?= render_staff_roles_badges($user['staff_roles'] ?? []) ?>
                    <?php endif; ?>
                </dd>
            </dl>
            <div class="form__actions">
                <button type="button" class="btn btn--primary" data-profile-edit-open>Редактировать</button>
            </div>
        </div>

        <div class="cabinet-profile-edit" data-profile-edit<?= $showProfileEdit ? '' : ' hidden' ?>>
            <h2 class="subsection-title">Редактирование профиля</h2>
            <form method="post" enctype="multipart/form-data" class="form form--medium" data-avatar-form>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_profile">

                <div class="cabinet-avatar-block">
                    <div class="cabinet-avatar-block__preview">
                        <?= render_user_avatar($userAvatar, 'user-avatar--lg', $basePath) ?>
                        <div>
                            <h3 class="subsection-title">Аватар</h3>
                            <p class="text-muted">Выберите иконку или загрузите своё фото.</p>
                        </div>
                    </div>

                    <div class="avatar-mode-tabs" role="tablist">
                        <label class="avatar-mode-tabs__item">
                            <input
                                type="radio"
                                name="avatar_mode"
                                value="icon"
                                data-avatar-mode
                                <?= $avatarIsUpload ? '' : 'checked' ?>
                            >
                            <span>Иконка</span>
                        </label>
                        <label class="avatar-mode-tabs__item">
                            <input
                                type="radio"
                                name="avatar_mode"
                                value="upload"
                                data-avatar-mode
                                <?= $avatarIsUpload ? 'checked' : '' ?>
                            >
                            <span>Своё фото</span>
                        </label>
                    </div>

                    <div class="avatar-icons" data-avatar-icons<?= $avatarIsUpload ? ' hidden' : '' ?>>
                        <?php foreach ($avatarPresets as $iconKey => $iconLabel): ?>
                        <label class="avatar-icon-option" title="<?= e($iconLabel) ?>">
                            <input
                                type="radio"
                                name="avatar_icon"
                                value="<?= e($iconKey) ?>"
                                <?= $selectedIcon === $iconKey ? 'checked' : '' ?>
                            >
                            <?= render_user_avatar('icon:' . $iconKey, 'user-avatar--pick') ?>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="form__group" data-avatar-upload<?= $avatarIsUpload ? '' : ' hidden' ?>>
                        <label for="avatar_file">Файл изображения</label>
                        <input
                            type="file"
                            id="avatar_file"
                            name="avatar_file"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            data-avatar-file
                        >
                        <p class="form__hint">JPG, PNG, WEBP или GIF, до 2 МБ.</p>
                    </div>
                </div>

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

                <div class="form__row">
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
                </div>

                <div class="form__row">
                    <div class="form__group">
                        <label for="phone">Телефон (логин) <span class="text-muted">*</span></label>
                        <input type="tel" id="phone" name="phone" required value="<?= e($user['phone'] ?? '') ?>" placeholder="+7 (___) ___-__-__">
                    </div>
                    <div class="form__group">
                        <label for="email">Email <span class="text-muted">(необязательно)</span></label>
                        <input type="email" id="email" name="email" value="<?= e(display_user_email((string) ($user['email'] ?? ''))) ?>">
                    </div>
                </div>

                <div class="form__group">
                    <label for="position">Должность</label>
                    <input type="text" id="position" name="position" value="<?= e($user['position'] ?? '') ?>">
                </div>

                <div class="form__group">
                    <label for="education">Образование</label>
                    <input type="text" id="education" name="education" value="<?= e($user['education'] ?? '') ?>">
                </div>

                <div class="form__group">
                    <label for="additional_info">Дополнительная информация</label>
                    <textarea id="additional_info" name="additional_info" rows="4"><?= e($user['additional_info'] ?? '') ?></textarea>
                </div>

                <div class="form__actions">
                    <button type="submit" class="btn btn--primary">Сохранить</button>
                    <button type="button" class="btn btn--ghost" data-profile-edit-cancel>Отмена</button>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
