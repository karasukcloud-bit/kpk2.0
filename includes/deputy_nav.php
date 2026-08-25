<?php

declare(strict_types=1);

$currentDeputyTab = $currentDeputyTab ?? 'curriculum';
?>
<nav class="admin-tabs">
    <a href="curriculum.php"
       class="admin-tabs__item<?= $currentDeputyTab === 'curriculum' ? ' admin-tabs__item--active' : '' ?>">
        Учебный план
    </a>
    <a href="grades.php"
       class="admin-tabs__item<?= $currentDeputyTab === 'grades' ? ' admin-tabs__item--active' : '' ?>">
        Электронные ведомости
    </a>
    <a href="record_books.php"
       class="admin-tabs__item<?= $currentDeputyTab === 'record_books' ? ' admin-tabs__item--active' : '' ?>">
        Зачётные книжки
    </a>
    <a href="summary.php"
       class="admin-tabs__item<?= $currentDeputyTab === 'summary' ? ' admin-tabs__item--active' : '' ?>">
        Сводная ведомость
    </a>
    <a href="rating.php"
       class="admin-tabs__item<?= $currentDeputyTab === 'rating' ? ' admin-tabs__item--active' : '' ?>">
        Рейтинг студентов
    </a>
    <a href="glaz.php"
       class="admin-tabs__item<?= $currentDeputyTab === 'glaz' ? ' admin-tabs__item--active' : '' ?>">
        ГЛАЗ
    </a>
    <a href="expelled.php"
       class="admin-tabs__item<?= $currentDeputyTab === 'expelled' ? ' admin-tabs__item--active' : '' ?>">
        Отчисленные
    </a>
    <a href="archive.php"
       class="admin-tabs__item<?= $currentDeputyTab === 'archive' ? ' admin-tabs__item--active' : '' ?>">
        Архив
    </a>
    <a href="notifications.php"
       class="admin-tabs__item<?= $currentDeputyTab === 'notifications' ? ' admin-tabs__item--active' : '' ?>">
        Уведомления
    </a>
</nav>
