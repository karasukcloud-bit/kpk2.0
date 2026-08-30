<?php

declare(strict_types=1);

$currentCuratorTab = $currentCuratorTab ?? 'group';
$curatorGroupId = (int) ($curatorGroupId ?? 0);
$curatorGroups = $curatorGroups ?? [];
$curatorGroupPreserveParams = $curatorGroupPreserveParams ?? [];
$groupQuery = $curatorGroupId > 0 ? '?group_id=' . $curatorGroupId : '';
?>
<nav class="admin-tabs">
    <a href="group.php<?= $groupQuery ?>"
       class="admin-tabs__item<?= $currentCuratorTab === 'group' ? ' admin-tabs__item--active' : '' ?>">
        Список группы
    </a>
    <a href="attendance.php<?= $groupQuery ?>"
       class="admin-tabs__item<?= $currentCuratorTab === 'attendance' ? ' admin-tabs__item--active' : '' ?>">
        Посещаемость
    </a>
    <a href="grades.php<?= $groupQuery ?>"
       class="admin-tabs__item<?= $currentCuratorTab === 'grades' ? ' admin-tabs__item--active' : '' ?>">
        Электронная ведомость
    </a>
    <a href="report.php<?= $groupQuery ?>"
       class="admin-tabs__item<?= $currentCuratorTab === 'report' ? ' admin-tabs__item--active' : '' ?>">
        Справка по группе
    </a>
    <a href="archive.php"
       class="admin-tabs__item<?= $currentCuratorTab === 'archive' ? ' admin-tabs__item--active' : '' ?>">
        Архив
    </a>
</nav>
<?php if ($curatorGroups !== []): ?>
    <?php require __DIR__ . '/curator/group_selector.php'; ?>
<?php endif; ?>
