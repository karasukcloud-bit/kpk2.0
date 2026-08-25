<?php

declare(strict_types=1);

$currentEducatorTab = $currentEducatorTab ?? 'summary';
?>
<nav class="admin-tabs">
    <a href="attendance.php"
       class="admin-tabs__item<?= $currentEducatorTab === 'summary' ? ' admin-tabs__item--active' : '' ?>">
        Сводка по пропускам
    </a>
    <a href="group_attendance.php"
       class="admin-tabs__item<?= $currentEducatorTab === 'group_attendance' ? ' admin-tabs__item--active' : '' ?>">
        Пропуски
    </a>
    <a href="students.php"
       class="admin-tabs__item<?= $currentEducatorTab === 'students' ? ' admin-tabs__item--active' : '' ?>">
        Информация по студентам
    </a>
</nav>
