<?php

declare(strict_types=1);

$currentTeacherTab = $currentTeacherTab ?? 'journal';
?>
<nav class="admin-tabs">
    <a href="<?= e(app_base_path()) ?>teacher/subjects.php"
       class="admin-tabs__item<?= $currentTeacherTab === 'subjects' ? ' admin-tabs__item--active' : '' ?>">
        Мои предметы
    </a>
    <a href="<?= e(app_base_path()) ?>teacher/ktp_constructor.php"
       class="admin-tabs__item<?= $currentTeacherTab === 'ktp_constructor' ? ' admin-tabs__item--active' : '' ?>">
        Конструктор КТП
    </a>
    <a href="<?= e(app_base_path()) ?>teacher/journal.php"
       class="admin-tabs__item<?= $currentTeacherTab === 'journal' ? ' admin-tabs__item--active' : '' ?>">
        Электронный журнал
    </a>
</nav>
