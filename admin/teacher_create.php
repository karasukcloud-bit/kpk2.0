<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/teachers.php';

require_admin();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($password !== $passwordConfirm) {
            $error = 'Пароли не совпадают.';
        } else {
            $fullName = compose_person_full_name(
                (string) ($_POST['last_name'] ?? ''),
                (string) ($_POST['first_name'] ?? ''),
                (string) ($_POST['middle_name'] ?? '')
            );

            $result = create_teacher(
                $_POST['email'] ?? '',
                $password,
                $fullName,
                posted_staff_roles(),
                posted_curator_group_id(),
                $_POST['phone'] ?? '',
                posted_curator_group_id_2()
            );

            if ($result['success']) {
                flash_set('success', 'Преподаватель успешно добавлен.');
                header('Location: teachers.php');
                exit;
            }

            $error = $result['error'];
        }
    }
}

$pageTitle = 'Добавить преподавателя';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'teachers';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Добавить преподавателя</h1>
                <p class="text-muted">Регистрация нового преподавателя в системе</p>
            </div>
            <a href="teachers.php" class="btn btn--ghost">← К списку</a>
        </div>

        <?php require __DIR__ . '/../includes/admin_nav.php'; ?>
    </section>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <form method="post" class="form form--narrow" data-password-generate>
            <?= csrf_field() ?>

            <div class="form__group">
                <label for="last_name">Фамилия</label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    required
                    value="<?= e($_POST['last_name'] ?? '') ?>"
                    placeholder="Иванов"
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
                    value="<?= e($_POST['first_name'] ?? '') ?>"
                    placeholder="Иван"
                    autocomplete="given-name"
                >
            </div>

            <div class="form__group">
                <label for="middle_name">Отчество</label>
                <input
                    type="text"
                    id="middle_name"
                    name="middle_name"
                    value="<?= e($_POST['middle_name'] ?? '') ?>"
                    placeholder="Иванович"
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
                    value="<?= e(format_login_phone($_POST['phone'] ?? '')) ?>"
                    placeholder="+7XXXXXXXXXX"
                >
            </div>

            <div class="form__group">
                <label for="email">Email <span class="text-muted">(необязательно)</span></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    placeholder="teacher@kpk.local"
                >
            </div>

            <div class="student-creds teacher-creds">
                <h3>Регистрационные данные преподавателя</h3>
                <p class="text-muted form-hint">Логином служит номер телефона. Пароль можно сгенерировать.</p>

                <div class="form__group">
                    <label for="password">Пароль</label>
                    <div class="form__inline-actions">
                        <input
                            type="text"
                            id="password"
                            name="password"
                            required
                            minlength="6"
                            autocomplete="new-password"
                            placeholder="Минимум 6 символов"
                            value="<?= e($_POST['password'] ?? '') ?>"
                        >
                        <button type="button" class="btn btn--ghost" data-generate-password>Сгенерировать</button>
                    </div>
                </div>

                <div class="form__group">
                    <label for="password_confirm">Подтверждение пароля</label>
                    <input
                        type="text"
                        id="password_confirm"
                        name="password_confirm"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        placeholder="Повторите пароль"
                        value="<?= e($_POST['password_confirm'] ?? '') ?>"
                    >
                </div>
            </div>

            <?php
            $selectedRoles = $_SERVER['REQUEST_METHOD'] === 'POST'
                ? posted_staff_roles()
                : ['teacher'];
            $selectedCuratorGroupId = $_SERVER['REQUEST_METHOD'] === 'POST'
                ? posted_curator_group_id()
                : null;
            $selectedCuratorGroupId2 = $_SERVER['REQUEST_METHOD'] === 'POST'
                ? posted_curator_group_id_2()
                : null;
            $forCuratorUserId = null;
            require __DIR__ . '/../includes/role_fields.php';
            ?>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сохранить</button>
                <a href="teachers.php" class="btn btn--ghost">Отмена</a>
            </div>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
