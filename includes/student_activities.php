<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function ensure_student_activities_schema(?PDO $pdo = null): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo = $pdo ?? db();
    if ($pdo->query("SHOW TABLES LIKE 'student_activities'")->fetch()) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE student_activities (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            student_id INT UNSIGNED NOT NULL,
            activity_type ENUM('club', 'section') NOT NULL DEFAULT 'club',
            title VARCHAR(255) NOT NULL,
            place VARCHAR(255) NOT NULL DEFAULT '',
            sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_student_activities_student (student_id),
            CONSTRAINT fk_student_activities_student
                FOREIGN KEY (student_id) REFERENCES students(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function student_activity_type_label(string $type): string
{
    return $type === 'section' ? 'Секция' : 'Кружок';
}

/**
 * @return list<array<string, mixed>>
 */
function get_student_activities(int $studentId): array
{
    ensure_student_activities_schema();
    $stmt = db()->prepare(
        'SELECT * FROM student_activities
         WHERE student_id = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([$studentId]);

    return $stmt->fetchAll();
}

/**
 * @param list<int> $studentIds
 * @return array<int, list<array<string, mixed>>>
 */
function get_students_activities_map(array $studentIds): array
{
    ensure_student_activities_schema();
    $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
    if ($studentIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $stmt = db()->prepare(
        "SELECT * FROM student_activities
         WHERE student_id IN ($placeholders)
         ORDER BY student_id ASC, sort_order ASC, id ASC"
    );
    $stmt->execute($studentIds);

    $map = [];
    foreach ($studentIds as $id) {
        $map[$id] = [];
    }
    foreach ($stmt->fetchAll() as $row) {
        $map[(int) $row['student_id']][] = $row;
    }

    return $map;
}

function format_student_activity_line(array $activity): string
{
    $type = student_activity_type_label((string) ($activity['activity_type'] ?? 'club'));
    $title = trim((string) ($activity['title'] ?? ''));
    $place = trim((string) ($activity['place'] ?? ''));
    if ($title === '') {
        return '';
    }
    $line = $type . ' «' . $title . '»';
    if ($place !== '') {
        $line .= ' (' . $place . ')';
    }

    return $line;
}

/**
 * @param list<array<string, mixed>> $activities
 */
function format_student_activities_summary(array $activities): string
{
    $parts = [];
    foreach ($activities as $activity) {
        $line = format_student_activity_line($activity);
        if ($line !== '') {
            $parts[] = $line;
        }
    }

    return implode('; ', $parts);
}

/**
 * @param list<array<string, mixed>> $items
 * @return array{success: bool, error?: string, count?: int}
 */
function save_student_activities(int $studentId, array $items): array
{
    ensure_student_activities_schema();

    if ($studentId <= 0) {
        return ['success' => false, 'error' => 'Студент не указан.'];
    }

    $stmt = db()->prepare('SELECT id, group_id FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    if (!$student) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    $normalized = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $title = trim((string) ($item['title'] ?? ''));
        if ($title === '') {
            continue;
        }
        if (function_exists('mb_substr')) {
            $title = mb_substr($title, 0, 255);
            $place = mb_substr(trim((string) ($item['place'] ?? '')), 0, 255);
        } else {
            $title = substr($title, 0, 255);
            $place = substr(trim((string) ($item['place'] ?? '')), 0, 255);
        }
        $type = (string) ($item['activity_type'] ?? 'club');
        if ($type !== 'section') {
            $type = 'club';
        }
        $normalized[] = [
            'activity_type' => $type,
            'title' => $title,
            'place' => $place,
        ];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare('DELETE FROM student_activities WHERE student_id = ?');
        $del->execute([$studentId]);

        if ($normalized !== []) {
            $ins = $pdo->prepare(
                'INSERT INTO student_activities (student_id, activity_type, title, place, sort_order)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($normalized as $index => $row) {
                $ins->execute([
                    $studentId,
                    $row['activity_type'],
                    $row['title'],
                    $row['place'],
                    $index + 1,
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();

        return ['success' => false, 'error' => 'Не удалось сохранить занятость.'];
    }

    return ['success' => true, 'count' => count($normalized)];
}

/**
 * @param list<array<string, mixed>> $students
 * @return array{
 *   map: array<int, list<array<string, mixed>>>,
 *   with_activities: int,
 *   without_activities: int,
 *   club_count: int,
 *   section_count: int,
 *   total_activities: int,
 *   rows: list<array{student_id: int, full_name: string, activities: list<array>, summary: string}>
 * }
 */
function build_group_activities_report(array $students): array
{
    $ids = array_map(static fn (array $s): int => (int) $s['id'], $students);
    $map = get_students_activities_map($ids);

    $with = 0;
    $allActivities = [];
    $rows = [];

    foreach ($students as $student) {
        $id = (int) $student['id'];
        $list = $map[$id] ?? [];
        if ($list !== []) {
            $with++;
        }
        foreach ($list as $activity) {
            $allActivities[] = $activity;
        }
        $rows[] = [
            'student_id' => $id,
            'full_name' => (string) ($student['full_name'] ?? ''),
            'activities' => $list,
            'summary' => format_student_activities_summary($list),
        ];
    }

    $total = count($students);
    $distinct = count_distinct_activity_titles($allActivities);

    return [
        'map' => $map,
        'with_activities' => $with,
        'without_activities' => max(0, $total - $with),
        'club_count' => $distinct['club_count'],
        'section_count' => $distinct['section_count'],
        'total_activities' => $distinct['total_activities'],
        'total_students' => $total,
        'pct_with' => activities_percent($with, $total),
        'pct_without' => activities_percent(max(0, $total - $with), $total),
        'rows' => $rows,
    ];
}

function activities_percent(int $part, int $total): float
{
    if ($total <= 0) {
        return 0.0;
    }

    return round(($part / $total) * 100, 1);
}

function normalize_activity_title_key(string $title): string
{
    $title = trim($title);
    if ($title === '') {
        return '';
    }
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($title, 'UTF-8');
    }

    return strtolower($title);
}

/**
 * @param list<array<string, mixed>> $activities
 * @return array{club_count: int, section_count: int, total_activities: int}
 */
function count_distinct_activity_titles(array $activities): array
{
    $clubs = [];
    $sections = [];
    foreach ($activities as $activity) {
        $key = normalize_activity_title_key((string) ($activity['title'] ?? ''));
        if ($key === '') {
            continue;
        }
        if (($activity['activity_type'] ?? '') === 'section') {
            $sections[$key] = true;
        } else {
            $clubs[$key] = true;
        }
    }

    $clubCount = count($clubs);
    $sectionCount = count($sections);

    return [
        'club_count' => $clubCount,
        'section_count' => $sectionCount,
        'total_activities' => $clubCount + $sectionCount,
    ];
}

/**
 * Аналитика занятости по всему колледжу и по группам.
 *
 * @return array{
 *   total_students: int,
 *   with_activities: int,
 *   without_activities: int,
 *   pct_with: float,
 *   pct_without: float,
 *   club_count: int,
 *   section_count: int,
 *   groups: list<array{
 *     group_id: int,
 *     group_number: string,
 *     specialty_name: string,
 *     total_students: int,
 *     with_activities: int,
 *     without_activities: int,
 *     pct_with: float,
 *     pct_without: float
 *   }>
 * }
 */
function build_college_activities_analytics(): array
{
    ensure_student_activities_schema();
    require_once __DIR__ . '/organization.php';
    require_once __DIR__ . '/students.php';

    $busyStmt = db()->query('SELECT DISTINCT student_id FROM student_activities');
    $busySet = [];
    foreach ($busyStmt->fetchAll() as $row) {
        $busySet[(int) $row['student_id']] = true;
    }

    $titlesStmt = db()->query('SELECT activity_type, title FROM student_activities');
    $distinct = count_distinct_activity_titles($titlesStmt->fetchAll());

    $groupRows = [];
    $totalStudents = 0;
    $totalWith = 0;

    foreach (get_all_groups() as $group) {
        $groupId = (int) $group['id'];
        $students = get_students_by_group($groupId);
        $count = count($students);
        $with = 0;
        foreach ($students as $student) {
            if (isset($busySet[(int) $student['id']])) {
                $with++;
            }
        }
        $without = max(0, $count - $with);
        $groupRows[] = [
            'group_id' => $groupId,
            'group_number' => (string) ($group['number'] ?? ''),
            'specialty_name' => (string) ($group['specialty_name'] ?? ''),
            'total_students' => $count,
            'with_activities' => $with,
            'without_activities' => $without,
            'pct_with' => activities_percent($with, $count),
            'pct_without' => activities_percent($without, $count),
        ];
        $totalStudents += $count;
        $totalWith += $with;
    }

    $totalWithout = max(0, $totalStudents - $totalWith);

    return [
        'total_students' => $totalStudents,
        'with_activities' => $totalWith,
        'without_activities' => $totalWithout,
        'pct_with' => activities_percent($totalWith, $totalStudents),
        'pct_without' => activities_percent($totalWithout, $totalStudents),
        'club_count' => $distinct['club_count'],
        'section_count' => $distinct['section_count'],
        'groups' => $groupRows,
    ];
}
