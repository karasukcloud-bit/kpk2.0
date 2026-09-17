<?php

declare(strict_types=1);

$currentAdminTab = $currentAdminTab ?? 'teachers';
?>
<nav class="admin-tabs">
    <a href="teachers.php"
       class="admin-tabs__item<?= $currentAdminTab === 'teachers' ? ' admin-tabs__item--active' : '' ?>">
        Преподаватели
    </a>
    <a href="info.php"
       class="admin-tabs__item<?= $currentAdminTab === 'info' ? ' admin-tabs__item--active' : '' ?>">
        Информация
    </a>
    <a href="students.php"
       class="admin-tabs__item<?= $currentAdminTab === 'students' ? ' admin-tabs__item--active' : '' ?>">
        Студенты
    </a>
    <a href="promote_groups.php"
       class="admin-tabs__item<?= $currentAdminTab === 'promote_groups' ? ' admin-tabs__item--active' : '' ?>">
        Перевод курсов
    </a>
    <a href="curriculum.php"
       class="admin-tabs__item<?= $currentAdminTab === 'curriculum' ? ' admin-tabs__item--active' : '' ?>">
        Учебный план
    </a>
    <?php if (is_file(__DIR__ . '/../modules/manual_brs/bootstrap.php')): ?>
    <a href="manual_brs_gradebook.php"
       class="admin-tabs__item<?= $currentAdminTab === 'manual_brs_gradebook' ? ' admin-tabs__item--active' : '' ?>">
        Ведомости (ручное БРС)
    </a>
    <?php endif; ?>
    <a href="archive.php"
       class="admin-tabs__item<?= $currentAdminTab === 'archive' ? ' admin-tabs__item--active' : '' ?>">
        Архив
    </a>
    <a href="notifications.php"
       class="admin-tabs__item<?= $currentAdminTab === 'notifications' ? ' admin-tabs__item--active' : '' ?>">
        Уведомления
    </a>
    <a href="mobile_notifications.php"
       class="admin-tabs__item<?= $currentAdminTab === 'mobile_notifications' ? ' admin-tabs__item--active' : '' ?>">
        Уведомления на телефон
    </a>
    <a href="logs.php"
       class="admin-tabs__item<?= $currentAdminTab === 'logs' ? ' admin-tabs__item--active' : '' ?>">
        Журнал действий
    </a>
    <a href="expelled.php"
       class="admin-tabs__item<?= $currentAdminTab === 'expelled' ? ' admin-tabs__item--active' : '' ?>">
        Отчисленные
    </a>
    <a href="stats.php"
       class="admin-tabs__item<?= $currentAdminTab === 'stats' ? ' admin-tabs__item--active' : '' ?>">
        Статистика
    </a>
</nav>
