<?php

declare(strict_types=1);

/** @var array|null $appShell */
/** @var string $basePath */

if (empty($useAppShell) || !is_array($appShell ?? null)) {
    return;
}

$appBase = $appBase ?? app_base_path();
$role = (string) ($appShell['role'] ?? '');
$tab = (string) ($appShell['tab'] ?? '');
$groupId = (int) ($curatorGroupId ?? 0);
$groupQuery = $groupId > 0 ? '?group_id=' . $groupId : '';

$icon = static function (string $name): string {
    $paths = [
        'home' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'group' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'report' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 18V14M12 18v-6M16 18v-3"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'grades' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/>',
        'attendance' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>',
    ];
    $body = $paths[$name] ?? $paths['home'];

    return '<span class="app-bottom-nav__icon" aria-hidden="true">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        . 'stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg></span>';
};

$items = [];
if ($role === 'curator') {
    $items = [
        ['tabs' => ['group'], 'href' => $appBase . 'curator/group.php' . $groupQuery, 'icon' => 'home', 'label' => 'Главная'],
        ['tabs' => ['student_edit'], 'href' => $appBase . 'curator/group.php' . $groupQuery . '#students', 'icon' => 'group', 'label' => 'Студенты'],
        ['tabs' => ['monthly_report', 'report', 'parent_reports'], 'href' => $appBase . 'curator/monthly_report.php' . $groupQuery, 'icon' => 'report', 'label' => 'Отчёты'],
        ['tabs' => ['cabinet'], 'href' => $appBase . 'cabinet.php', 'icon' => 'user', 'label' => 'Профиль'],
    ];
} elseif ($role === 'teacher') {
    $items = [
        ['tabs' => ['subjects'], 'href' => $appBase . 'teacher/subjects.php', 'icon' => 'home', 'label' => 'Главная'],
        ['tabs' => ['journal'], 'href' => $appBase . 'teacher/journal.php', 'icon' => 'grades', 'label' => 'Журнал'],
        ['tabs' => ['practice', 'ktp_constructor', 'manual_brs'], 'href' => $appBase . 'teacher/practice.php', 'icon' => 'report', 'label' => 'Ещё'],
        ['tabs' => ['cabinet'], 'href' => $appBase . 'cabinet.php', 'icon' => 'user', 'label' => 'Профиль'],
    ];
} elseif ($role === 'deputy') {
    $items = [
        ['tabs' => ['summary', 'curriculum'], 'href' => $appBase . 'deputy/summary.php', 'icon' => 'home', 'label' => 'Сводка'],
        ['tabs' => ['grades'], 'href' => $appBase . 'deputy/grades.php', 'icon' => 'grades', 'label' => 'Ведомости'],
        ['tabs' => ['rating', 'subject_performance', 'glaz'], 'href' => $appBase . 'deputy/rating.php', 'icon' => 'report', 'label' => 'Рейтинг'],
        ['tabs' => ['cabinet'], 'href' => $appBase . 'cabinet.php', 'icon' => 'user', 'label' => 'Профиль'],
    ];
} elseif ($role === 'educator') {
    $items = [
        ['tabs' => ['summary'], 'href' => $appBase . 'educator/attendance.php', 'icon' => 'home', 'label' => 'Сводка'],
        ['tabs' => ['group_attendance', 'daily_attendance', 'absence_rating'], 'href' => $appBase . 'educator/group_attendance.php', 'icon' => 'attendance', 'label' => 'Пропуски'],
        ['tabs' => ['all_students', 'activities'], 'href' => $appBase . 'educator/all_students.php', 'icon' => 'group', 'label' => 'Студенты'],
        ['tabs' => ['cabinet'], 'href' => $appBase . 'cabinet.php', 'icon' => 'user', 'label' => 'Профиль'],
    ];
} elseif ($role === 'admin') {
    $items = [
        ['tabs' => ['teachers', 'info'], 'href' => $appBase . 'admin/teachers.php', 'icon' => 'home', 'label' => 'Главная'],
        ['tabs' => ['students'], 'href' => $appBase . 'admin/students.php', 'icon' => 'group', 'label' => 'Студенты'],
        ['tabs' => ['stats', 'logs'], 'href' => $appBase . 'admin/stats.php', 'icon' => 'report', 'label' => 'Статистика'],
        ['tabs' => ['cabinet'], 'href' => $appBase . 'cabinet.php', 'icon' => 'user', 'label' => 'Профиль'],
    ];
}

if ($items === []) {
    return;
}
?>
<nav class="app-bottom-nav" aria-label="Нижнее меню">
    <?php foreach ($items as $item): ?>
    <?php
    $tabs = $item['tabs'] ?? [];
    $active = in_array($tab, $tabs, true) ? ' is-active' : '';
    ?>
    <a href="<?= e((string) $item['href']) ?>" class="app-bottom-nav__item<?= $active ?>">
        <?= $icon((string) $item['icon']) ?>
        <span class="app-bottom-nav__label"><?= e((string) $item['label']) ?></span>
    </a>
    <?php endforeach; ?>
</nav>
