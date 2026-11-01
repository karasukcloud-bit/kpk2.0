<?php

declare(strict_types=1);

/**
 * @return array{
 *   role:string,
 *   tab:string,
 *   label:string,
 *   home:string,
 *   sections:list<array{title:string, items:list<array{tab:string, href:string, icon:string, label:string}>}>
 * }|null
 */
function build_app_shell(string $role, string $tab, string $appBase, int $curatorGroupId = 0): ?array
{
    $manualBrs = is_file(__DIR__ . '/../modules/manual_brs/bootstrap.php');
    $role = strtolower(trim($role));

    if ($role === 'curator') {
        $groupQuery = $curatorGroupId > 0 ? '?group_id=' . $curatorGroupId : '';
        $base = $appBase . 'curator/';
        $docs = [
            ['tab' => 'grades', 'href' => $base . 'grades.php' . $groupQuery, 'icon' => 'grades', 'label' => 'Электронная ведомость'],
        ];
        if ($manualBrs) {
            $docs[] = [
                'tab' => 'manual_brs_gradebook',
                'href' => $base . 'manual_brs_gradebook.php' . $groupQuery,
                'icon' => 'grades',
                'label' => 'Ручное БРС',
            ];
        }
        $docs[] = ['tab' => 'characteristics', 'href' => $base . 'characteristics.php' . $groupQuery, 'icon' => 'characteristics', 'label' => 'Характеристики'];
        $docs[] = ['tab' => 'archive', 'href' => $base . 'archive.php', 'icon' => 'archive', 'label' => 'Архив'];

        return [
            'role' => 'curator',
            'tab' => $tab,
            'label' => 'Панель куратора',
            'home' => $base . 'group.php' . $groupQuery,
            'sections' => [
                [
                    'title' => 'Работа с группой',
                    'items' => [
                        ['tab' => 'group', 'href' => $base . 'group.php' . $groupQuery, 'icon' => 'group', 'label' => 'Список студентов'],
                        ['tab' => 'attendance', 'href' => $base . 'attendance.php' . $groupQuery, 'icon' => 'attendance', 'label' => 'Посещаемость'],
                        ['tab' => 'activities', 'href' => $base . 'activities.php' . $groupQuery, 'icon' => 'activities', 'label' => 'Занятость студентов'],
                    ],
                ],
                ['title' => 'Ведомости и документы', 'items' => $docs],
                [
                    'title' => 'Аналитика и отчёты',
                    'items' => [
                        ['tab' => 'report', 'href' => $base . 'report.php' . $groupQuery, 'icon' => 'report', 'label' => 'О группе'],
                        ['tab' => 'monthly_report', 'href' => $base . 'monthly_report.php' . $groupQuery, 'icon' => 'monthly_report', 'label' => 'Отчёт по группе'],
                        ['tab' => 'parent_reports', 'href' => $base . 'parent_reports.php' . $groupQuery, 'icon' => 'parent_reports', 'label' => 'Отчёты родителям'],
                    ],
                ],
            ],
        ];
    }

    if ($role === 'admin') {
        $base = $appBase . 'admin/';
        $items = [
            ['tab' => 'teachers', 'href' => $base . 'teachers.php', 'icon' => 'group', 'label' => 'Преподаватели'],
            ['tab' => 'info', 'href' => $base . 'info.php', 'icon' => 'report', 'label' => 'Инфо'],
            ['tab' => 'students', 'href' => $base . 'students.php', 'icon' => 'student', 'label' => 'Студенты'],
            ['tab' => 'promote_groups', 'href' => $base . 'promote_groups.php', 'icon' => 'activities', 'label' => 'Перевод'],
            ['tab' => 'curriculum', 'href' => $base . 'curriculum.php', 'icon' => 'grades', 'label' => 'План'],
        ];
        if ($manualBrs) {
            $items[] = ['tab' => 'manual_brs_gradebook', 'href' => $base . 'manual_brs_gradebook.php', 'icon' => 'grades', 'label' => 'БРС'];
        }
        $items = array_merge($items, [
            ['tab' => 'archive', 'href' => $base . 'archive.php', 'icon' => 'archive', 'label' => 'Архив'],
            ['tab' => 'notifications', 'href' => $base . 'notifications.php', 'icon' => 'notify', 'label' => 'Уведомления'],
            ['tab' => 'mobile_notifications', 'href' => $base . 'mobile_notifications.php', 'icon' => 'phone', 'label' => 'Телефон'],
            ['tab' => 'logs', 'href' => $base . 'logs.php', 'icon' => 'logs', 'label' => 'Журнал'],
            ['tab' => 'expelled', 'href' => $base . 'expelled.php', 'icon' => 'expelled', 'label' => 'Отчисленные'],
            ['tab' => 'stats', 'href' => $base . 'stats.php', 'icon' => 'stats', 'label' => 'Статистика'],
        ]);

        return [
            'role' => 'admin',
            'tab' => $tab,
            'label' => 'Админ панель',
            'home' => $base . 'teachers.php',
            'sections' => [['title' => 'Управление', 'items' => $items]],
        ];
    }

    if ($role === 'deputy') {
        $base = $appBase . 'deputy/';

        return [
            'role' => 'deputy',
            'tab' => $tab,
            'label' => 'Панель завуча',
            'home' => $base . 'curriculum.php',
            'sections' => [
                [
                    'title' => 'Учёба',
                    'items' => [
                        ['tab' => 'curriculum', 'href' => $base . 'curriculum.php', 'icon' => 'grades', 'label' => 'Учебный план'],
                        ['tab' => 'grades', 'href' => $base . 'grades.php', 'icon' => 'grades', 'label' => 'Электронные ведомости'],
                        ['tab' => 'record_books', 'href' => $base . 'record_books.php', 'icon' => 'characteristics', 'label' => 'Зачётные книжки'],
                    ],
                ],
                [
                    'title' => 'Аналитика',
                    'items' => [
                        ['tab' => 'summary', 'href' => $base . 'summary.php', 'icon' => 'stats', 'label' => 'Сводная ведомость'],
                        ['tab' => 'subject_performance', 'href' => $base . 'subject_performance.php', 'icon' => 'stats', 'label' => 'Успеваемость по предметам'],
                        ['tab' => 'rating', 'href' => $base . 'rating.php', 'icon' => 'rating', 'label' => 'Рейтинг студентов'],
                        ['tab' => 'glaz', 'href' => $base . 'glaz.php', 'icon' => 'glaz', 'label' => 'ГЛАЗ'],
                    ],
                ],
                [
                    'title' => 'Документы',
                    'items' => [
                        ['tab' => 'expelled', 'href' => $base . 'expelled.php', 'icon' => 'expelled', 'label' => 'Отчисленные'],
                        ['tab' => 'archive', 'href' => $base . 'archive.php', 'icon' => 'archive', 'label' => 'Архив'],
                        ['tab' => 'notifications', 'href' => $base . 'notifications.php', 'icon' => 'notify', 'label' => 'Уведомления'],
                    ],
                ],
            ],
        ];
    }

    if ($role === 'teacher') {
        $base = $appBase . 'teacher/';
        $items = [
            ['tab' => 'subjects', 'href' => $base . 'subjects.php', 'icon' => 'grades', 'label' => 'Мои предметы'],
            ['tab' => 'ktp_constructor', 'href' => $base . 'ktp_constructor.php', 'icon' => 'characteristics', 'label' => 'Конструктор КТП'],
            ['tab' => 'journal', 'href' => $base . 'journal.php', 'icon' => 'grades', 'label' => 'Электронный журнал'],
            ['tab' => 'practice', 'href' => $base . 'practice.php', 'icon' => 'activities', 'label' => 'Практика'],
        ];
        if ($manualBrs) {
            $items[] = ['tab' => 'manual_brs', 'href' => $base . 'manual_brs.php', 'icon' => 'grades', 'label' => 'Ручное БРС'];
        }

        return [
            'role' => 'teacher',
            'tab' => $tab,
            'label' => 'Панель преподавателя',
            'home' => $base . 'subjects.php',
            'sections' => [['title' => 'Преподавание', 'items' => $items]],
        ];
    }

    if ($role === 'educator') {
        $base = $appBase . 'educator/';

        return [
            'role' => 'educator',
            'tab' => $tab,
            'label' => 'Панель воспитателя',
            'home' => $base . 'attendance.php',
            'sections' => [
                [
                    'title' => 'Посещаемость',
                    'items' => [
                        ['tab' => 'summary', 'href' => $base . 'attendance.php', 'icon' => 'attendance', 'label' => 'Сводка по пропускам'],
                        ['tab' => 'absence_rating', 'href' => $base . 'absence_rating.php', 'icon' => 'rating', 'label' => 'Рейтинг по пропускам'],
                        ['tab' => 'group_attendance', 'href' => $base . 'group_attendance.php', 'icon' => 'attendance', 'label' => 'Пропуски'],
                        ['tab' => 'daily_attendance', 'href' => $base . 'daily_attendance.php', 'icon' => 'attendance', 'label' => 'Пропуски по дням'],
                    ],
                ],
                [
                    'title' => 'Студенты',
                    'items' => [
                        ['tab' => 'all_students', 'href' => $base . 'all_students.php', 'icon' => 'group', 'label' => 'Студенты'],
                        ['tab' => 'activities', 'href' => $base . 'activities.php', 'icon' => 'activities', 'label' => 'Занятость студентов'],
                        ['tab' => 'curator_reports', 'href' => $base . 'curator_reports.php', 'icon' => 'monthly_report', 'label' => 'Отчёты кураторов'],
                    ],
                ],
            ],
        ];
    }

    return null;
}
