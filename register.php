<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

header('Location: login.php');
exit;

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
            $role = 'teacher';

            // Только администратор может создавать других администраторов
            if (is_admin() && ($_POST['role'] ?? '') === 'admin') {
                $role = 'admin';
            }

            $result = register_user(
                $_POST['email'] ?? '',
                $_POST['password'] ?? '',
                $_POST['full_name'] ?? '',
                $role
            );

            if ($result['success']) {
                flash_set('success', 'Регистрация успешна. Теперь вы можете войти в систему.');
                header('Location: login.php');
                exit;
            }

            $error = $result['error'];
        }
    }
}

$pageTitle = 'Регистрация';
$showHeader = false;
require __DIR__ . '/includes/header.php';
?>

<div class="auth-card">
    <div class="auth-card__header">
        <h1>Регистрация</h1>
        <p>Создание учётной записи преподавателя</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="form" novalidate>
        <?= csrf_field() ?>

        <div class="form__group">
            <label for="full_name">ФИО</label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                required
                autocomplete="name"
                value="<?= e($_POST['full_name'] ?? '') ?>"
                placeholder="Иванов Иван Иванович"
            >
        </div>

        <div class="form__group">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                required
                autocomplete="email"
                value="<?= e($_POST['email'] ?? '') ?>"
                placeholder="teacher@kpk.local"
            >
        </div>

        <div class="form__group">
            <label for="password">Пароль</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="new-password"
                minlength="6"
                placeholder="Минимум 6 символов"
            >
        </div>

        <div class="form__group">
            <label for="password_confirm">Подтверждение пароля</label>
            <input
                type="password"
                id="password_confirm"
                name="password_confirm"
                required
                autocomplete="new-password"
                minlength="6"
                placeholder="Повторите пароль"
            >
        </div>

        <button type="submit" class="btn btn--primary btn--block">Зарегистрироваться</button>
    </form>

    <p class="auth-card__footer">
        Уже есть аккаунт? <a href="login.php">Войти</a>
    </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
