<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/gradebook.php';

function admin_table_exists(string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $stmt = db()->prepare(
        'SELECT 1 FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?
         LIMIT 1'
    );
    $stmt->execute([$table]);
    $cache[$table] = (bool) $stmt->fetchColumn();

    return $cache[$table];
}

function admin_count(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function build_admin_app_statistics(): array
{
    $period = get_active_gradebook_period();
    $year = $period['academic_year'];
    $semester = $period['semester'];

    $usersTotal = admin_count('SELECT COUNT(*) FROM users');
    $usersActive = admin_count('SELECT COUNT(*) FROM users WHERE is_active = 1');
    $usersInactive = $usersTotal - $usersActive;

    $usersByAccountRole = [
        'admin' => admin_count("SELECT COUNT(*) FROM users WHERE role = 'admin'"),
        'teacher' => admin_count("SELECT COUNT(*) FROM users WHERE role = 'teacher'"),
        'student' => admin_count("SELECT COUNT(*) FROM users WHERE role = 'student'"),
    ];

    $staffByRole = [];
    foreach (STAFF_ROLES as $role) {
        $staffByRole[$role] = admin_table_exists('user_roles')
            ? admin_count('SELECT COUNT(*) FROM user_roles WHERE role = ?', [$role])
            : 0;
    }

    $studentsTotal = admin_table_exists('students')
        ? admin_count('SELECT COUNT(*) FROM students')
        : 0;
    $studentsWithAccount = admin_table_exists('students')
        ? admin_count('SELECT COUNT(*) FROM students WHERE user_id IS NOT NULL')
        : 0;

    $specialties = admin_table_exists('specialties')
        ? admin_count('SELECT COUNT(*) FROM specialties')
        : 0;
    $groups = admin_table_exists('study_groups')
        ? admin_count('SELECT COUNT(*) FROM study_groups')
        : 0;
    $groupsWithCurator = admin_table_exists('study_groups')
        ? admin_count('SELECT COUNT(*) FROM study_groups WHERE curator_id IS NOT NULL')
        : 0;

    $subjects = admin_table_exists('subjects')
        ? admin_count('SELECT COUNT(*) FROM subjects')
        : 0;
    $curriculumPlans = admin_table_exists('curriculum_plans')
        ? admin_count('SELECT COUNT(*) FROM curriculum_plans')
        : 0;
    $curriculumItems = admin_table_exists('curriculum_items')
        ? admin_count('SELECT COUNT(*) FROM curriculum_items')
        : 0;
    $curriculumItemsYear = admin_table_exists('curriculum_items') && admin_table_exists('curriculum_plans')
        ? admin_count(
            'SELECT COUNT(*) FROM curriculum_items ci
             INNER JOIN curriculum_plans cp ON cp.id = ci.curriculum_plan_id
             WHERE cp.academic_year = ?',
            [$year]
        )
        : 0;

    $gradeEntries = admin_table_exists('grade_entries')
        ? admin_count('SELECT COUNT(*) FROM grade_entries')
        : 0;

    $attendanceDays = admin_table_exists('attendance_days')
        ? admin_count('SELECT COUNT(*) FROM attendance_days')
        : 0;
    $attendanceDaysYear = admin_table_exists('attendance_days')
        ? admin_count('SELECT COUNT(*) FROM attendance_days WHERE academic_year = ?', [$year])
        : 0;
    $attendanceEntries = admin_table_exists('attendance_entries')
        ? admin_count('SELECT COUNT(*) FROM attendance_entries')
        : 0;

    $journalLessons = admin_table_exists('journal_lessons')
        ? admin_count('SELECT COUNT(*) FROM journal_lessons')
        : 0;
    $journalGrades = admin_table_exists('journal_grades')
        ? admin_count('SELECT COUNT(*) FROM journal_grades')
        : 0;

    $archivePeriods = admin_table_exists('archive_periods')
        ? admin_count('SELECT COUNT(*) FROM archive_periods')
        : 0;
    $archiveGradebooks = admin_table_exists('archive_periods')
        ? admin_count("SELECT COUNT(*) FROM archive_periods WHERE archive_type = 'gradebook'")
        : 0;
    $archiveJournals = admin_table_exists('archive_periods')
        ? admin_count("SELECT COUNT(*) FROM archive_periods WHERE archive_type = 'journal'")
        : 0;

    $notifications = admin_table_exists('notifications')
        ? admin_count('SELECT COUNT(*) FROM notifications')
        : 0;
    $notificationsUnread = 0;
    if (admin_table_exists('notifications') && admin_table_exists('notification_reads')) {
        $notificationsUnread = admin_count(
            "SELECT COUNT(*) FROM notifications n
             WHERE n.notification_type = 'personal'
               AND NOT EXISTS (
                   SELECT 1 FROM notification_reads r
                   WHERE r.notification_id = n.id AND r.user_id = n.recipient_id
               )"
        );
    }

    $glazSchedules = admin_table_exists('glaz_schedules')
        ? admin_count('SELECT COUNT(*) FROM glaz_schedules')
        : 0;
    $glazScheduled = admin_table_exists('glaz_schedules')
        ? admin_count('SELECT COUNT(*) FROM glaz_schedules WHERE liquidation_date IS NOT NULL')
        : 0;
    $glazCommission = admin_table_exists('glaz_commission_members')
        ? admin_count('SELECT COUNT(DISTINCT schedule_id) FROM glaz_commission_members')
        : 0;

    $ktpTopics = admin_table_exists('ktp_topics')
        ? admin_count('SELECT COUNT(*) FROM ktp_topics')
        : 0;

    return [
        'period' => [
            'academic_year' => $year,
            'semester' => $semester,
        ],
        'users' => [
            'total' => $usersTotal,
            'active' => $usersActive,
            'inactive' => $usersInactive,
            'by_account_role' => $usersByAccountRole,
            'by_staff_role' => $staffByRole,
        ],
        'organization' => [
            'specialties' => $specialties,
            'groups' => $groups,
            'groups_with_curator' => $groupsWithCurator,
            'students' => $studentsTotal,
            'students_with_account' => $studentsWithAccount,
            'subjects' => $subjects,
        ],
        'curriculum' => [
            'plans' => $curriculumPlans,
            'items' => $curriculumItems,
            'items_active_year' => $curriculumItemsYear,
            'ktp_topics' => $ktpTopics,
        ],
        'learning' => [
            'grade_entries' => $gradeEntries,
            'journal_lessons' => $journalLessons,
            'journal_grades' => $journalGrades,
            'attendance_days' => $attendanceDays,
            'attendance_days_year' => $attendanceDaysYear,
            'attendance_entries' => $attendanceEntries,
        ],
        'archive' => [
            'periods' => $archivePeriods,
            'gradebooks' => $archiveGradebooks,
            'journals' => $archiveJournals,
        ],
        'other' => [
            'notifications' => $notifications,
            'notifications_unread_personal' => $notificationsUnread,
            'glaz_schedules' => $glazSchedules,
            'glaz_scheduled' => $glazScheduled,
            'glaz_with_commission' => $glazCommission,
        ],
    ];
}
