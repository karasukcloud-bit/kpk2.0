<?php
$basePath = $basePath ?? '';
require_once __DIR__ . '/app_shell.php';
$appShellRole = null;
$appShellTab = null;
if (isset($currentCuratorTab)) {
    $appShellRole = 'curator';
    $appShellTab = (string) $currentCuratorTab;
} elseif (isset($currentAdminTab)) {
    $appShellRole = 'admin';
    $appShellTab = (string) $currentAdminTab;
} elseif (isset($currentDeputyTab)) {
    $appShellRole = 'deputy';
    $appShellTab = (string) $currentDeputyTab;
} elseif (isset($currentTeacherTab)) {
    $appShellRole = 'teacher';
    $appShellTab = (string) $currentTeacherTab;
} elseif (isset($currentEducatorTab)) {
    $appShellRole = 'educator';
    $appShellTab = (string) $currentEducatorTab;
}
$appBaseEarly = function_exists('app_base_path') ? app_base_path() : '';
$appShell = $appShellRole !== null
    ? build_app_shell(
        $appShellRole,
        $appShellTab ?? '',
        $appBaseEarly,
        (int) ($curatorGroupId ?? 0)
    )
    : null;
$useAppShell = is_array($appShell);
$useCuratorShell = $useAppShell;

$scriptBase = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$heavyPageScripts = [
    'glaz.php',
    'summary.php',
    'rating.php',
    'subject_performance.php',
    'gradebook.php',
    'grades.php',
    'record_book.php',
    'record_books.php',
    'manual_brs_gradebook.php',
    'report.php',
    'parent_reports.php',
    'monthly_report.php',
    'characteristics.php',
    'activities.php',
    'attendance.php',
    'absence_rating.php',
    'group_attendance.php',
    'daily_attendance.php',
    'all_students.php',
    'stats.php',
    'logs.php',
    'students.php',
    'teachers.php',
];
$showPageLoader = !empty($showPageLoader) || in_array($scriptBase, $heavyPageScripts, true);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= e($pageTitle ?? 'КПК — Успеваемость') ?></title>
    <link rel="icon" href="<?= e($basePath) ?>assets/img/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="<?= e($basePath) ?>assets/img/favicon.png" type="image/png" sizes="32x32">
    <link rel="icon" href="<?= e($basePath) ?>assets/img/favicon-16.png" type="image/png" sizes="16x16">
    <link rel="shortcut icon" href="<?= e($basePath) ?>assets/img/favicon.ico">
    <link rel="apple-touch-icon" href="<?= e($basePath) ?>assets/img/apple-touch-icon.png" sizes="180x180">
    <link rel="icon" href="<?= e($basePath) ?>assets/img/icon-192.png" type="image/png" sizes="192x192">
    <link rel="icon" href="<?= e($basePath) ?>assets/img/icon-512.png" type="image/png" sizes="512x512">
    <link rel="stylesheet" href="<?= e($basePath) ?>assets/css/style.css?v=20261112glaz">
    <style>
        .page-loader{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:1rem;background:rgba(247,248,252,.88);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);opacity:0;visibility:hidden;pointer-events:none;transition:opacity .15s ease,visibility .15s ease}
        html.page-loader-on .page-loader{opacity:1;visibility:visible;pointer-events:auto}
        html.page-loader-on,html.page-loader-on body{cursor:progress}
        .page-loader__spinner{width:2.4rem;height:2.4rem;border:3px solid #dbe3f0;border-top-color:#2563eb;border-radius:50%;animation:page-loader-spin .7s linear infinite}
        .page-loader__text{margin:0;font:600 .95rem/1.3 system-ui,sans-serif;color:#334155}
        @keyframes page-loader-spin{to{transform:rotate(360deg)}}
    </style>
    <script>
    (function () {
        try {
            if (sessionStorage.getItem('kpk_page_loader') === '1') {
                document.documentElement.classList.add('page-loader-on');
            }
        } catch (e) {}
        <?php if (!empty($showPageLoader)): ?>
        document.documentElement.classList.add('page-loader-on');
        <?php endif; ?>
        <?php if (!empty($useAppShell)): ?>
        try {
            var key = localStorage.getItem('kpk_app_sidebar_collapsed');
            var legacy = localStorage.getItem('kpk_curator_sidebar_collapsed');
            if (key === '1' || (key === null && legacy === '1')) {
                document.documentElement.classList.add('app-sidebar-collapsed');
            }
        } catch (e) {}
        <?php endif; ?>
    })();
    </script>
</head>
<body<?= !empty($useAppShell) ? ' class="body--curator-shell body--app-shell"' : '' ?><?= !empty($showPageLoader) ? ' data-page-loader-auto' : '' ?>>
    <div class="page-loader" id="pageLoader" role="status" aria-live="polite" aria-busy="true">
        <div class="page-loader__spinner" aria-hidden="true"></div>
        <p class="page-loader__text" data-page-loader-text>Загрузка…</p>
    </div>
    <div class="page<?= !empty($useAppShell) ? ' page--curator-shell' : '' ?>">
        <?php if (!empty($useAppShell)): ?>
        <div class="curator-shell" id="curatorShell">
            <?php require __DIR__ . '/app_sidebar.php'; ?>
            <div class="curator-shell__body">
        <?php endif; ?>
        <?php if (!empty($showHeader)): ?>
        <?php
        $headerUser = null;
        $headerName = '';
        $headerAvatar = 'icon:person';
        $appBase = $appBaseEarly;
        $activePanel = '';
        $headerUnreadCount = 0;
        $panelSwitchLabel = is_array($appShell) ? (string) ($appShell['label'] ?? 'Меню') : 'Меню';
        if (is_logged_in()) {
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
            if (!is_array($appShell)) {
                $panelLabels = [
                    'admin' => 'Админ панель',
                    'curator' => 'Панель куратора',
                    'teacher' => 'Панель преподавателя',
                    'deputy' => 'Панель завуча',
                    'educator' => 'Панель воспитателя',
                    'cabinet' => 'Личный кабинет',
                    'student' => 'Личный кабинет',
                ];
                $panelSwitchLabel = $panelLabels[$activePanel] ?? 'Меню';
            }
        }
        ?>
        <header class="header<?= !empty($useAppShell) ? ' header--curator-shell' : '' ?>">
            <?php if (!empty($useAppShell)): ?>
            <button
                type="button"
                class="header__sidebar-btn"
                data-app-sidebar-open
                aria-label="Открыть меню разделов"
                title="Меню разделов"
            >
                <span class="header__sidebar-btn-icon" aria-hidden="true"></span>
            </button>
            <?php endif; ?>
            <a href="<?= e($basePath) ?>dashboard.php" class="header__brand" title="На главную">
                <span class="header__logo">
                    <img
                        class="header__logo-img"
                        src="<?= e($basePath) ?>assets/img/logo-icon.png"
                        alt=""
                        width="56"
                        height="56"
                    >
                </span>
                <span class="header__title" aria-label="СПО-ПРОГРЕСС">
                    <span class="header__title-spo">СПО-</span><span class="header__title-progress">ПРОГРЕСС</span>
                </span>
            </a>
            <?php if (empty($useAppShell)): ?>
            <a
                class="header__rustore"
                href="https://www.rustore.ru/catalog/app/ru.kpk.attendance"
                target="_blank"
                rel="noopener noreferrer"
                hidden
                data-rustore-link
                title="Скачать приложение в RuStore"
            >
                <span class="header__rustore-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" focusable="false">
                        <rect x="5" y="2" width="14" height="20" rx="3" fill="none" stroke="currentColor" stroke-width="1.8"/>
                        <circle cx="12" cy="17.5" r="1.1" fill="currentColor"/>
                        <path d="M9 8.2h6M9 11h6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="header__rustore-text">
                    <span class="header__rustore-label">Приложение</span>
                    <span class="header__rustore-name">RuStore</span>
                </span>
            </a>
            <?php endif; ?>

            <?php if (is_logged_in() && !empty($useAppShell)): ?>
            <div class="header-shell-bar">
                <details class="header-panel-switch" data-header-panel-switch>
                    <summary class="header-panel-switch__summary">
                        <span class="header-panel-switch__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </span>
                        <span class="header-panel-switch__label"><?= e($panelSwitchLabel) ?></span>
                        <span class="header-panel-switch__chevron" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </span>
                    </summary>
                    <div class="header-panel-switch__menu">
                        <?php if (is_admin()): ?>
                        <a href="<?= e($appBase) ?>admin/teachers.php" class="header-panel-switch__item<?= $activePanel === 'admin' ? ' is-active' : '' ?>">Админ панель</a>
                        <?php endif; ?>
                        <?php if (can_use_curator_panel()): ?>
                        <a href="<?= e($appBase) ?>curator/group.php" class="header-panel-switch__item<?= $activePanel === 'curator' ? ' is-active' : '' ?>">Панель куратора</a>
                        <?php endif; ?>
                        <?php if (can_use_teacher_panel()): ?>
                        <a href="<?= e($appBase) ?>teacher/subjects.php" class="header-panel-switch__item<?= $activePanel === 'teacher' ? ' is-active' : '' ?>">Панель преподавателя</a>
                        <?php endif; ?>
                        <?php if (can_use_deputy_panel()): ?>
                        <a href="<?= e($appBase) ?>deputy/curriculum.php" class="header-panel-switch__item<?= $activePanel === 'deputy' ? ' is-active' : '' ?>">Панель завуча</a>
                        <?php endif; ?>
                        <?php if (can_use_educator_panel()): ?>
                        <a href="<?= e($appBase) ?>educator/attendance.php" class="header-panel-switch__item<?= $activePanel === 'educator' ? ' is-active' : '' ?>">Панель воспитателя</a>
                        <?php endif; ?>
                        <a href="<?= e($appBase) ?><?= is_student_user() ? 'student/cabinet.php' : 'cabinet.php' ?>" class="header-panel-switch__item">Личный кабинет</a>
                        <a href="<?= e($basePath) ?>logout.php" class="header-panel-switch__item header-panel-switch__item--danger">Выйти</a>
                    </div>
                </details>

                <div class="header-shell-bar__actions">
                    <a
                        href="<?= e($appBase) ?>notifications.php"
                        class="header__notify-btn<?= basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'notifications.php' ? ' is-active' : '' ?>"
                        title="Уведомления"
                    >
                        <span class="header__notify-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>
                        </span>
                        <?php if ($headerUnreadCount > 0): ?>
                        <span class="header__notify-badge"><?= $headerUnreadCount > 99 ? '99+' : (int) $headerUnreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a
                        href="<?= e($appBase) ?><?= is_student_user() ? 'student/cabinet.php' : 'cabinet.php' ?>"
                        class="header-shell-avatar"
                        title="Личный кабинет"
                    >
                        <?= render_user_avatar($headerAvatar, 'user-avatar--header', $basePath) ?>
                    </a>
                </div>
            </div>
            <?php elseif (is_logged_in()): ?>
            <button
                type="button"
                class="header__menu-btn"
                aria-label="Меню"
                aria-expanded="false"
                aria-controls="headerNav"
            >
                <span class="header__menu-icon" aria-hidden="true"></span>
            </button>
            <div class="header__overlay" aria-hidden="true"></div>
            <nav class="header__nav" id="headerNav">
                <div class="header__nav-head">
                    <h2 class="header__nav-title">Меню</h2>
                    <button
                        type="button"
                        class="header__nav-close"
                        aria-label="Закрыть меню"
                    >
                        <span class="header__nav-close-icon" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="header__nav-body">
                <div class="header__panels">
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
                </div>
                <div class="header__actions">
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
                </div>
                </div>
            </nav>
            <?php endif; ?>
        </header>
        <?php endif; ?>
        <main class="main">
