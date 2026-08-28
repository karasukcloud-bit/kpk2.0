<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'КПК — Успеваемость') ?></title>
    <?php $basePath = $basePath ?? ''; ?>
    <link rel="icon" href="<?= e($basePath) ?>assets/img/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="<?= e($basePath) ?>assets/img/favicon.png" type="image/png" sizes="32x32">
    <link rel="icon" href="<?= e($basePath) ?>assets/img/favicon-16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="<?= e($basePath) ?>assets/img/apple-touch-icon.png" sizes="180x180">
    <link rel="stylesheet" href="<?= e($basePath) ?>assets/css/style.css">
</head>
<body>
    <div class="page">
        <?php if (!empty($showHeader)): ?>
        <header class="header">
            <a href="<?= e($basePath) ?>dashboard.php" class="header__brand" title="На главную">
                <span class="header__logo">
                    <img
                        class="header__logo-img"
                        src="<?= e($basePath) ?>assets/img/logo-icon.svg"
                        alt="СПО-ПРОГРЕСС"
                        width="56"
                        height="56"
                    >
                </span>
                <span class="header__title">СПО-ПРОГРЕСС</span>
            </a>
            <?php if (is_logged_in()): ?>
            <?php
            $headerUser = current_user();
            $headerName = user_first_middle_name((string) ($headerUser['full_name'] ?? ''));
            if ($headerName === '') {
                $headerName = (string) ($headerUser['full_name'] ?? '');
            }
            $headerAvatar = (string) ($headerUser['avatar'] ?? 'icon:person');
            $appBase = app_base_path();
            $activePanel = current_header_panel();
            require_once __DIR__ . '/notifications.php';
            $headerUnreadCount = get_unread_notifications_count((int) $headerUser['id']);
            ?>
            <nav class="header__nav">
                <?php if (is_admin()): ?>
                <a
                    href="<?= e($appBase) ?>admin/teachers.php"
                    class="btn btn--ghost btn--sm<?= $activePanel === 'admin' ? ' is-active' : '' ?>"
                >Админ панель</a>
                <?php endif; ?>
                <?php if (can_use_curator_panel()): ?>
                <a
                    href="<?= e($appBase) ?>curator/group.php"
                    class="btn btn--ghost btn--sm<?= $activePanel === 'curator' ? ' is-active' : '' ?>"
                >Панель куратора</a>
                <?php endif; ?>
                <?php if (can_use_teacher_panel()): ?>
                <a
                    href="<?= e($appBase) ?>teacher/subjects.php"
                    class="btn btn--ghost btn--sm<?= $activePanel === 'teacher' ? ' is-active' : '' ?>"
                >Панель преподавателя</a>
                <?php endif; ?>
                <?php if (can_use_deputy_panel()): ?>
                <a
                    href="<?= e($appBase) ?>deputy/curriculum.php"
                    class="btn btn--ghost btn--sm<?= $activePanel === 'deputy' ? ' is-active' : '' ?>"
                >Панель завуча</a>
                <?php endif; ?>
                <?php if (can_use_educator_panel()): ?>
                <a
                    href="<?= e($appBase) ?>educator/attendance.php"
                    class="btn btn--ghost btn--sm<?= $activePanel === 'educator' ? ' is-active' : '' ?>"
                >Панель воспитателя</a>
                <?php endif; ?>
                <a
                    href="<?= e($appBase) ?>notifications.php"
                    class="header__notify-btn<?= basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'notifications.php' ? ' is-active' : '' ?>"
                    title="Уведомления"
                >
                    <span class="header__notify-icon" aria-hidden="true">🔔</span>
                    <?php if ($headerUnreadCount > 0): ?>
                    <span class="header__notify-badge"><?= $headerUnreadCount > 99 ? '99+' : (int) $headerUnreadCount ?></span>
                    <?php endif; ?>
                </a>
                <div class="header__account">
                    <a
                        href="<?= e($appBase) ?><?= is_student_user() ? 'student/cabinet.php' : 'cabinet.php' ?>"
                        class="header__cabinet-tab<?= ($activePanel === 'cabinet' || $activePanel === 'student') ? ' header__cabinet-tab--active' : '' ?>"
                        title="Личный кабинет"
                    >
                        <span class="header__user-name"><?= e($headerName) ?></span>
                        <?= render_user_avatar($headerAvatar, 'user-avatar--header', $basePath) ?>
                    </a>
                </div>
                <a
                    href="<?= e($basePath) ?>logout.php"
                    class="header__logout-btn"
                    title="Выйти"
                    aria-label="Выйти"
                >
                    <svg class="header__logout-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 17l5-5-5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 12H9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </nav>
            <?php endif; ?>
        </header>
        <?php endif; ?>
        <main class="main">
