<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = flash_get('error');
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $result = authenticate_user(
            $_POST['phone'] ?? '',
            $_POST['password'] ?? ''
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
        <p>Контроль успеваемости и посещаемости</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <form method="post" class="form" novalidate>
        <?= csrf_field() ?>

        <div class="form__group">
            <label for="phone">Телефон</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                required
                autocomplete="tel"
                value="<?= e($_POST['phone'] ?? '') ?>"
                placeholder="+7 (___) ___-__-__"
            >
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
