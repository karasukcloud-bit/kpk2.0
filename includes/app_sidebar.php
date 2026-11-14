<?php

declare(strict_types=1);

/** @var array $appShell */
/** @var string $basePath */

$appShell = $appShell ?? null;
if (!is_array($appShell)) {
    return;
}

$appSidebarIcon = static function (string $name): string {
    $paths = [
        'group' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'student' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'attendance' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>',
        'grades' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/>',
        'report' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 18V14M12 18v-6M16 18v-3"/>',
        'archive' => '<path d="M21 8v13H3V8"/><path d="M23 3H1v5h22V3z"/><path d="M10 12h4"/>',
        'activities' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><path d="M19 8v4M17 10h4"/>',
        'characteristics' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>',
        'parent_reports' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><path d="M12 11V7M10 9h4"/>',
        'monthly_report' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/>',
        'stats' => '<path d="M4 19V5M4 19h16"/><path d="M8 17V9M12 17v-5M16 17V7"/>',
        'rating' => '<path d="M12 2l2.4 7.2H22l-6 4.8 2.3 7L12 16.8 5.7 21l2.3-7-6-4.8h7.6z"/>',
        'glaz' => '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>',
        'expelled' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 8 5 5M22 8l-5 5"/>',
        'igo' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M16 11h6"/>',
        'testoria' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'notify' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'phone' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>',
        'logs' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'collapse' => '<path d="M15 18l-6-6 6-6"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
    ];
    $body = $paths[$name] ?? $paths['grades'];

    return '<span class="curator-sidebar__icon" aria-hidden="true">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        . 'stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg></span>';
};

$currentTab = (string) ($appShell['tab'] ?? '');
$home = (string) ($appShell['home'] ?? '#');
$label = (string) ($appShell['label'] ?? 'Меню');
$sections = $appShell['sections'] ?? [];
$sidebarUserName = '';
$sidebarUserAvatar = 'icon:person';
if (function_exists('is_logged_in') && is_logged_in()) {
    $sidebarUser = current_user();
    $sidebarUserName = user_first_middle_name((string) ($sidebarUser['full_name'] ?? ''));
    if ($sidebarUserName === '') {
        $sidebarUserName = (string) ($sidebarUser['full_name'] ?? '');
    }
    $sidebarUserAvatar = (string) ($sidebarUser['avatar'] ?? 'icon:person');
}
?>
<aside class="curator-sidebar" id="appSidebar" aria-label="<?= e($label) ?>">
    <div class="curator-sidebar__top curator-sidebar__top--desktop">
        <a href="<?= e($home) ?>" class="curator-sidebar__brand" title="<?= e($label) ?>">
            <img
                class="curator-sidebar__logo"
                src="<?= e($basePath ?? '') ?>assets/img/logo-icon.png"
                alt=""
                width="36"
                height="36"
            >
            <span class="curator-sidebar__brand-text" aria-label="СПО-ПРОГРЕСС">
                <span class="curator-sidebar__brand-spo">СПО-</span><span class="curator-sidebar__brand-progress">ПРОГРЕСС</span>
            </span>
        </a>
        <button
            type="button"
            class="curator-sidebar__collapse"
            data-app-sidebar-toggle
            aria-label="Свернуть меню"
            title="Свернуть меню"
        >
            <?= $appSidebarIcon('collapse') ?>
        </button>
    </div>

    <div class="curator-sidebar__mobile-head">
        <div class="curator-sidebar__mobile-brand">
            <img
                class="curator-sidebar__logo"
                src="<?= e($basePath ?? '') ?>assets/img/logo-icon.png"
                alt=""
                width="40"
                height="40"
            >
            <span class="curator-sidebar__brand-text" aria-label="СПО-ПРОГРЕСС">
                <span class="curator-sidebar__brand-spo">СПО-</span><span class="curator-sidebar__brand-progress">ПРОГРЕСС</span>
            </span>
        </div>
        <?php if ($sidebarUserName !== ''): ?>
        <div class="curator-sidebar__mobile-user">
            <?= render_user_avatar($sidebarUserAvatar, 'user-avatar--sidebar', $basePath ?? '') ?>
            <div class="curator-sidebar__mobile-user-meta">
                <div class="curator-sidebar__mobile-user-name"><?= e($sidebarUserName) ?></div>
                <div class="curator-sidebar__mobile-user-role"><?= e($label) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <nav class="curator-sidebar__nav">
        <?php foreach ($sections as $section): ?>
        <div class="curator-sidebar__section">
            <div class="curator-sidebar__section-title"><?= e((string) ($section['title'] ?? '')) ?></div>
            <?php foreach (($section['items'] ?? []) as $item): ?>
            <?php
            $itemTab = (string) ($item['tab'] ?? '');
            $itemLabel = (string) ($item['label'] ?? '');
            $active = $itemTab !== '' && $itemTab === $currentTab ? ' is-active' : '';
            ?>
            <a
                href="<?= e((string) ($item['href'] ?? '#')) ?>"
                class="curator-sidebar__link<?= $active ?>"
                title="<?= e($itemLabel) ?>"
                <?php if (!empty($item['external'])): ?>
                    target="_blank"
                    rel="noopener noreferrer"
                <?php endif; ?>
            >
                <?= $appSidebarIcon((string) ($item['icon'] ?? 'grades')) ?>
                <span class="curator-sidebar__label"><?= e($itemLabel) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </nav>

    <div class="curator-sidebar__mobile-footer">
        <a href="<?= e($basePath ?? '') ?>logout.php" class="curator-sidebar__logout">
            <?= $appSidebarIcon('logout') ?>
            <span>Выход</span>
        </a>
    </div>
</aside>
<div class="curator-sidebar__backdrop" data-app-sidebar-backdrop hidden></div>
