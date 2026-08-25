<?php

declare(strict_types=1);

$currentStudentTab = $currentStudentTab ?? 'cabinet';
?>
<nav class="admin-tabs">
    <a href="cabinet.php"
       class="admin-tabs__item<?= $currentStudentTab === 'cabinet' ? ' admin-tabs__item--active' : '' ?>">
        Профиль
    </a>
    <a href="journal.php"
       class="admin-tabs__item<?= $currentStudentTab === 'journal' ? ' admin-tabs__item--active' : '' ?>">
        Журнал
    </a>
    <a href="record_book.php"
       class="admin-tabs__item<?= $currentStudentTab === 'record_book' ? ' admin-tabs__item--active' : '' ?>">
        Электронная зачётка
    </a>
    <a href="analytics.php"
       class="admin-tabs__item<?= $currentStudentTab === 'analytics' ? ' admin-tabs__item--active' : '' ?>">
        Аналитика
    </a>
    <a href="help.php"
       class="admin-tabs__item<?= $currentStudentTab === 'help' ? ' admin-tabs__item--active' : '' ?>">
        Инструкция
    </a>
</nav>
