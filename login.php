<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = flash_get('error');
$success = flash_get('success');
$loginType = ($_POST['login_type'] ?? 'phone') === 'email' ? 'email' : 'phone';
$loginPhone = $loginType === 'phone' ? format_login_phone((string) ($_POST['phone'] ?? '')) : '';
$loginEmail = $loginType === 'email' ? trim((string) ($_POST['email'] ?? '')) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $login = $loginType === 'email' ? $loginEmail : $loginPhone;
        $result = authenticate_user(
            $login,
            (string) ($_POST['password'] ?? ''),
            $loginType
        );

        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        }

        $error = $result['error'];
    }
}

$pageTitle = 'Вход в систему';
$showHeader = false;
require __DIR__ . '/includes/header.php';
?>

<div class="auth-card">
    <div class="auth-card__header">
        <h1>Вход в систему</h1>
        <p>СПО-ПРОГРЕСС</p>
        <p>ГАПОУ НСО "Карасукский педагогический колледж"</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <form method="post" class="form" novalidate data-login-form>
        <?= csrf_field() ?>

        <div class="auth-login-switch" role="radiogroup" aria-label="Способ входа">
            <label class="auth-login-switch__item">
                <input
                    type="radio"
                    name="login_type"
                    value="phone"
                    data-login-type
                    <?= $loginType === 'phone' ? 'checked' : '' ?>
                >
                <span>Телефон</span>
            </label>
            <label class="auth-login-switch__item">
                <input
                    type="radio"
                    name="login_type"
                    value="email"
                    data-login-type
                    <?= $loginType === 'email' ? 'checked' : '' ?>
                >
                <span>E-mail</span>
            </label>
        </div>

        <div class="form__group" data-login-phone-group<?= $loginType === 'email' ? ' hidden' : '' ?>>
            <label for="phone">Телефон</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                data-phone-login
                inputmode="tel"
                autocomplete="tel"
                maxlength="12"
                value="<?= e($loginPhone) ?>"
                placeholder="+79001234567"
                <?= $loginType === 'phone' ? 'required' : '' ?>
            >
        </div>

        <div class="form__group" data-login-email-group<?= $loginType === 'phone' ? ' hidden' : '' ?>>
            <label for="email">E-mail</label>
            <input
                type="email"
                id="email"
                name="email"
                data-login-email
                autocomplete="email"
                value="<?= e($loginEmail) ?>"
                placeholder="name@example.com"
                <?= $loginType === 'email' ? 'required' : '' ?>
            >
            <p class="text-muted form-hint">Используйте email, указанный в личном кабинете.</p>
        </div>

        <div class="form__group">
            <label for="password">Пароль</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
            >
        </div>

        <button type="submit" class="btn btn--primary btn--block">Войти</button>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
