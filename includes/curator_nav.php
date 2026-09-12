<?php

declare(strict_types=1);

$currentCuratorTab = $currentCuratorTab ?? 'group';
$curatorGroupId = (int) ($curatorGroupId ?? 0);
$curatorGroups = $curatorGroups ?? [];
$curatorGroupPreserveParams = $curatorGroupPreserveParams ?? [];
$groupQuery = $curatorGroupId > 0 ? '?group_id=' . $curatorGroupId : '';

$curatorTabIcon = static function (string $name): string {
    $paths = [
        'group' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'attendance' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>',
        'grades' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/>',
        'report' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 18V14M12 18v-6M16 18v-3"/>',
        'archive' => '<path d="M21 8v13H3V8"/><path d="M23 3H1v5h22V3z"/><path d="M10 12h4"/>',
    ];
    $body = $paths[$name] ?? '';

    return '<span class="admin-tabs__icon" aria-hidden="true">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        . 'stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg></span>';
};
?>
<nav class="admin-tabs">
    <a href="group.php<?= $groupQuery ?>"
       class="admin-tabs__item<?= $currentCuratorTab === 'group' ? ' admin-tabs__item--active' : '' ?>">
        <?= $curatorTabIcon('group') ?>
        Список группы
    </a>
    <a href="attendance.php<?= $groupQuery ?>"
       class="admin-tabs__item<?= $currentCuratorTab === 'attendance' ? ' admin-tabs__item--active' : '' ?>">
        <?= $curatorTabIcon('attendance') ?>
        Посещаемость
    </a>
    <a href="grades.php<?= $groupQuery ?>"
       class="admin-tabs__item<?= $currentCuratorTab === 'grades' ? ' admin-tabs__item--active' : '' ?>">
        <?= $curatorTabIcon('grades') ?>
        Электронная ведомость
    </a>
    <a href="report.php<?= $groupQuery ?>"
       class="admin-tabs__item<?= $currentCuratorTab === 'report' ? ' admin-tabs__item--active' : '' ?>">
        <?= $curatorTabIcon('report') ?>
        Справка по группе
    </a>
    <a href="archive.php"
       class="admin-tabs__item<?= $currentCuratorTab === 'archive' ? ' admin-tabs__item--active' : '' ?>">
        <?= $curatorTabIcon('archive') ?>
        Архив
    </a>
</nav>
<?php if ($curatorGroups !== []): ?>
    <?php require __DIR__ . '/curator/group_selector.php'; ?>
<?php endif; ?>
