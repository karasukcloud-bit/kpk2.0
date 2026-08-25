<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/profile.php';
require_once __DIR__ . '/../includes/student_accounts.php';

require_student();

$user = current_user();
$student = current_student();
$error = null;
$showAvatarEdit = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
        $showAvatarEdit = true;
    } elseif (($_POST['action'] ?? '') === 'save_avatar') {
        $result = update_own_avatar($_POST, $_FILES);
        if ($result['success']) {
            flash_set('success', 'Аватар обновлён.');
            header('Location: cabinet.php');
            exit;
        }
        $error = $result['error'];
        $showAvatarEdit = true;
    } else {
        $error = 'Неизвестное действие.';
        $showAvatarEdit = true;
    }
}

$user = current_user();
$success = flash_get('success');
$flashError = flash_get('error');
$error = $error ?? $flashError;
$pageTitle = 'Личный кабинет студента';
$showHeader = true;
$basePath = '../';
$currentStudentTab = 'cabinet';
require __DIR__ . '/../includes/header.php';

$displayOrDash = static function (?string $value): string {
    $value = trim((string) $value);

    return $value !== '' ? $value : '—';
};

$userAvatar = normalize_avatar_value((string) ($user['avatar'] ?? 'icon:person'));
$avatarIsUpload = strpos($userAvatar, 'file:') === 0;
$selectedIcon = $avatarIsUpload ? 'person' : user_avatar_icon_key($userAvatar);
$avatarPresets = avatar_presets();
$fullName = (string) ($student['full_name'] ?? $user['full_name'] ?? '');
$phone = (string) ($student['phone'] ?? $user['phone'] ?? '');
$birthDate = $student['birth_date'] ?? null;
$gender = $student['gender'] ?? null;
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Личный кабинет</h1>
                <p class="text-muted">
                    <?php if ($student): ?>
                        Группа <?= e($student['group_number']) ?>
                        · <?= e($student['specialty_name']) ?>
                    <?php else: ?>
                        Профиль студента
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/student_nav.php'; ?>
    </section>

    <section class="panel">
        <?php if ($success): ?>
            <div class="alert alert--success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="cabinet-profile-view" data-profile-view<?= $showAvatarEdit ? ' hidden' : '' ?>>
            <div class="cabinet-avatar-block cabinet-avatar-block--view">
                <?= render_user_avatar($userAvatar, 'user-avatar--lg', $basePath) ?>
            </div>
            <dl class="profile-list">
                <dt>ФИО</dt>
                <dd><?= e($displayOrDash($fullName)) ?></dd>
                <dt>Телефон</dt>
                <dd><?= e($displayOrDash($phone)) ?></dd>
                <dt>Дата рождения</dt>
                <dd><?= e(format_student_birth_date($birthDate !== null ? (string) $birthDate : null)) ?></dd>
                <dt>Пол</dt>
                <dd><?= e(student_gender_label($gender !== null ? (string) $gender : null)) ?></dd>
            </dl>
            <div class="form__actions">
                <button type="button" class="btn btn--primary" data-profile-edit-open>Редактировать</button>
            </div>
            <p class="text-muted table-hint">
                Можно изменить только аватар. Остальные данные и пароль меняет куратор или администратор.
            </p>
        </div>

        <div class="cabinet-profile-edit" data-profile-edit<?= $showAvatarEdit ? '' : ' hidden' ?>>
            <h2 class="subsection-title">Смена аватара</h2>
            <form method="post" enctype="multipart/form-data" class="form form--medium" data-avatar-form>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_avatar">

                <div class="cabinet-avatar-block">
                    <div class="cabinet-avatar-block__preview">
                        <?= render_user_avatar($userAvatar, 'user-avatar--lg', $basePath) ?>
                        <div>
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

                <div class="form__actions">
                    <button type="submit" class="btn btn--primary">Сохранить</button>
                    <button type="button" class="btn btn--ghost" data-profile-edit-cancel>Отмена</button>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
