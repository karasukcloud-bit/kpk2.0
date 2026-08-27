<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/students.php';
require_once __DIR__ . '/curriculum.php';
require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/ktp.php';
require_once __DIR__ . '/grading.php';
require_once __DIR__ . '/activity_log.php';

function get_journal_assignments_for_user(
    ?int $userId = null,
    ?string $academicYear = null,
    ?string $semester = null
): array {
    $academicYear = normalize_academic_year($academicYear ?? get_default_academic_year())
        ?? get_default_academic_year();

    $sql = 'SELECT ci.id AS curriculum_item_id, ci.semester, ci.teacher_id,
                   sub.name AS subject_name,
                   g.id AS group_id, g.number AS group_number,
                   sp.name AS specialty_name,
                   cp.academic_year,
                   u.full_name AS teacher_name
            FROM curriculum_items ci
            INNER JOIN subjects sub ON sub.id = ci.subject_id
            INNER JOIN curriculum_plans cp ON cp.id = ci.curriculum_plan_id
            INNER JOIN study_groups g ON g.id = cp.group_id
            INNER JOIN specialties sp ON sp.id = g.specialty_id
            LEFT JOIN users u ON u.id = ci.teacher_id
            WHERE cp.academic_year = ?';
    $params = [$academicYear];

    if ($semester !== null && in_array($semester, ['1', '2'], true)) {
        $sql .= " AND (ci.semester = ? OR ci.semester = 'both')";
        $params[] = $semester;
    }

    // Журнал доступен всем преподавателям (замещения): без фильтра по teacher_id
    $sql .= ' ORDER BY g.number ASC, sub.name ASC, ci.semester ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function group_journal_assignments_by_group(array $assignments): array
{
    $groups = [];

    foreach ($assignments as $row) {
        $groupId = (int) $row['group_id'];
        if (!isset($groups[$groupId])) {
            $groups[$groupId] = [
                'group_id' => $groupId,
                'group_number' => (string) $row['group_number'],
                'specialty_name' => (string) $row['specialty_name'],
                'subjects' => [],
            ];
        }

        $groups[$groupId]['subjects'][] = $row;
    }

    return array_values($groups);
}

/** Все группы системы + предметы из учебного плана текущего периода. */
function get_journal_groups(?string $academicYear = null, ?string $semester = null): array
{
    $assignmentsByGroup = [];
    foreach (get_journal_assignments_for_user(null, $academicYear, $semester) as $row) {
        $groupId = (int) $row['group_id'];
        if (!isset($assignmentsByGroup[$groupId])) {
            $assignmentsByGroup[$groupId] = [
                'group_id' => $groupId,
                'group_number' => (string) $row['group_number'],
                'specialty_name' => (string) $row['specialty_name'],
                'subjects' => [],
            ];
        }
        $assignmentsByGroup[$groupId]['subjects'][] = $row;
    }

    $groups = [];
    foreach (get_all_groups() as $group) {
        $groupId = (int) $group['id'];
        if (isset($assignmentsByGroup[$groupId])) {
            $groups[] = $assignmentsByGroup[$groupId];
            continue;
        }

        $groups[] = [
            'group_id' => $groupId,
            'group_number' => (string) $group['number'],
            'specialty_name' => (string) ($group['specialty_name'] ?? ''),
            'subjects' => [],
        ];
    }

    return $groups;
}

function can_access_journal_item(int $curriculumItemId): bool
{
    if (!can_use_teacher_panel()) {
        return false;
    }

    return get_curriculum_item_by_id($curriculumItemId) !== null;
}

function get_student_journal_subjects(int $groupId, ?string $academicYear = null, ?string $semester = null): array
{
    $academicYear = normalize_academic_year($academicYear ?? get_default_academic_year())
        ?? get_default_academic_year();

    $sql = 'SELECT ci.id AS curriculum_item_id, ci.semester, ci.teacher_id,
                   sub.name AS subject_name,
                   g.id AS group_id, g.number AS group_number,
                   sp.name AS specialty_name,
                   cp.academic_year,
                   u.full_name AS teacher_name
            FROM curriculum_items ci
            INNER JOIN subjects sub ON sub.id = ci.subject_id
            INNER JOIN curriculum_plans cp ON cp.id = ci.curriculum_plan_id
            INNER JOIN study_groups g ON g.id = cp.group_id
            INNER JOIN specialties sp ON sp.id = g.specialty_id
            LEFT JOIN users u ON u.id = ci.teacher_id
            WHERE cp.academic_year = ? AND g.id = ?';
    $params = [$academicYear, $groupId];

    if ($semester !== null && in_array($semester, ['1', '2'], true)) {
        $sql .= " AND (ci.semester = ? OR ci.semester = 'both')";
        $params[] = $semester;
    }

    $sql .= ' ORDER BY sub.name ASC, ci.semester ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function get_journal_grades_for_student(int $curriculumItemId, int $studentId): array
{
    $lessons = get_journal_lessons($curriculumItemId);
    if ($lessons === []) {
        return [];
    }

    $lessonIds = array_map(static fn (array $lesson): int => (int) $lesson['id'], $lessons);
    $placeholders = implode(',', array_fill(0, count($lessonIds), '?'));
    $params = $lessonIds;
    $params[] = $studentId;

    $stmt = db()->prepare(
        "SELECT lesson_id, mark, activity, late
         FROM journal_grades
         WHERE lesson_id IN ($placeholders) AND student_id = ?"
    );
    $stmt->execute($params);

    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int) $row['lesson_id']] = [
            'mark' => (string) ($row['mark'] ?? ''),
            'activity' => (int) $row['activity'] === 1,
            'late' => (int) $row['late'] === 1,
        ];
    }

    return $result;
}

function get_journal_lessons(int $curriculumItemId): array
{
    $stmt = db()->prepare(
        'SELECT jl.id, jl.curriculum_item_id, jl.lesson_date, jl.ktp_topic_id, jl.grade_type,
                kt.title AS topic_title,
                kt.lesson_type AS topic_lesson_type,
                kt.hours AS topic_hours,
                kt.sort_order AS topic_sort_order
         FROM journal_lessons jl
         LEFT JOIN ktp_topics kt ON kt.id = jl.ktp_topic_id
         WHERE jl.curriculum_item_id = ?
         ORDER BY jl.lesson_date ASC, jl.id ASC'
    );
    $stmt->execute([$curriculumItemId]);

    return $stmt->fetchAll();
}

function journal_grade_type_label(string $type): string
{
    if ($type === 'control') {
        return 'Контрольная';
    }

    return 'Текущая';
}

function journal_grade_type_short(string $type): string
{
    if ($type === 'control') {
        return 'к/р';
    }

    return 'тек';
}

function normalize_journal_grade_type(string $type): string
{
    return $type === 'control' ? 'control' : 'current';
}

function get_journal_grades(int $curriculumItemId): array
{
    $lessons = get_journal_lessons($curriculumItemId);
    if ($lessons === []) {
        return [];
    }

    $lessonIds = array_map(static fn (array $lesson): int => (int) $lesson['id'], $lessons);
    $placeholders = implode(',', array_fill(0, count($lessonIds), '?'));

    $stmt = db()->prepare(
        "SELECT lesson_id, student_id, mark, activity, late
         FROM journal_grades
         WHERE lesson_id IN ($placeholders)"
    );
    $stmt->execute($lessonIds);

    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int) $row['student_id']][(int) $row['lesson_id']] = [
            'mark' => (string) ($row['mark'] ?? ''),
            'activity' => (int) $row['activity'] === 1,
            'late' => (int) $row['late'] === 1,
        ];
    }

    return $result;
}

function journal_mark_options(): array
{
    return ['', '5', '4', '3', '2', 'Н'];
}

function normalize_journal_mark(string $mark): ?string
{
    $mark = trim($mark);
    if ($mark === 'H' || $mark === 'h' || $mark === 'н') {
        $mark = 'Н';
    }

    if (!in_array($mark, journal_mark_options(), true)) {
        return null;
    }

    return $mark;
}

function empty_journal_entry(): array
{
    return [
        'mark' => '',
        'activity' => false,
        'late' => false,
    ];
}

function add_journal_lesson(
    int $curriculumItemId,
    string $date,
    ?int $ktpTopicId = null,
    string $gradeType = 'current'
): array {
    return save_journal_lesson_data($curriculumItemId, $date, $ktpTopicId, $gradeType, null);
}

function update_journal_lesson(
    int $lessonId,
    string $date,
    ?int $ktpTopicId = null,
    string $gradeType = 'current'
): array {
    $lesson = get_journal_lesson_by_id($lessonId);
    if ($lesson === null) {
        return ['success' => false, 'error' => 'Урок не найден.'];
    }

    return save_journal_lesson_data(
        (int) $lesson['curriculum_item_id'],
        $date,
        $ktpTopicId,
        $gradeType,
        $lessonId
    );
}

function save_journal_lesson_data(
    int $curriculumItemId,
    string $date,
    ?int $ktpTopicId,
    string $gradeType,
    ?int $lessonId
): array {
    if (!can_access_journal_item($curriculumItemId)) {
        return ['success' => false, 'error' => 'Нет доступа к журналу.'];
    }

    $date = trim($date);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
        return ['success' => false, 'error' => 'Некорректная дата.'];
    }

    $gradeType = normalize_journal_grade_type($gradeType);

    if ($ktpTopicId !== null && $ktpTopicId > 0) {
        $topic = get_ktp_topic_by_id($ktpTopicId);
        if ($topic === null || (int) $topic['curriculum_item_id'] !== $curriculumItemId) {
            return ['success' => false, 'error' => 'Тема КТП не найдена для этого предмета.'];
        }

        if (!ktp_is_journal_selectable_type((string) ($topic['lesson_type'] ?? 'lecture'))) {
            return [
                'success' => false,
                'error' => 'Самостоятельная работа не записывается в журнал — эти часы преподаватель не отрабатывает.',
            ];
        }

        $usedIds = get_used_ktp_topic_ids($curriculumItemId, $lessonId);
        if (in_array($ktpTopicId, $usedIds, true)) {
            return ['success' => false, 'error' => 'Эта тема КТП уже пройдена в журнале.'];
        }
    } else {
        $ktpTopicId = null;
    }

    $pdo = db();
    if ($lessonId === null) {
        $stmt = $pdo->prepare(
            'INSERT INTO journal_lessons (curriculum_item_id, lesson_date, ktp_topic_id, grade_type)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$curriculumItemId, $date, $ktpTopicId, $gradeType]);
        $newLessonId = (int) $pdo->lastInsertId();
        $savedLesson = get_journal_lesson_by_id($newLessonId);
        if ($savedLesson !== null) {
            log_activity(
                'journal.lesson_add',
                'journal_lesson',
                $newLessonId,
                (int) $savedLesson['group_id'],
                activity_log_lesson_details($savedLesson)
            );
        }

        return ['success' => true, 'lesson_id' => $newLessonId];
    }

    $stmt = $pdo->prepare(
        'UPDATE journal_lessons
         SET lesson_date = ?, ktp_topic_id = ?, grade_type = ?
         WHERE id = ? AND curriculum_item_id = ?'
    );
    $stmt->execute([$date, $ktpTopicId, $gradeType, $lessonId, $curriculumItemId]);

    $savedLesson = get_journal_lesson_by_id($lessonId);
    if ($savedLesson !== null) {
        log_activity(
            'journal.lesson_update',
            'journal_lesson',
            $lessonId,
            (int) $savedLesson['group_id'],
            activity_log_lesson_details($savedLesson)
        );
    }

    return ['success' => true, 'lesson_id' => $lessonId];
}

function delete_journal_lesson(int $lessonId): array
{
    $lesson = get_journal_lesson_by_id($lessonId);
    if ($lesson === null) {
        return ['success' => false, 'error' => 'Урок не найден.'];
    }

    if (!can_access_journal_item((int) $lesson['curriculum_item_id'])) {
        return ['success' => false, 'error' => 'Нет доступа к журналу.'];
    }

    log_activity(
        'journal.lesson_delete',
        'journal_lesson',
        $lessonId,
        (int) $lesson['group_id'],
        activity_log_lesson_details($lesson)
    );

    db()->prepare('DELETE FROM journal_lessons WHERE id = ?')->execute([$lessonId]);

    return ['success' => true];
}

function get_journal_lesson_by_id(int $lessonId): ?array
{
    $stmt = db()->prepare(
        'SELECT jl.*, ci.teacher_id, cp.group_id,
                kt.title AS topic_title,
                kt.lesson_type AS topic_lesson_type,
                kt.hours AS topic_hours
         FROM journal_lessons jl
         INNER JOIN curriculum_items ci ON ci.id = jl.curriculum_item_id
         INNER JOIN curriculum_plans cp ON cp.id = ci.curriculum_plan_id
         LEFT JOIN ktp_topics kt ON kt.id = jl.ktp_topic_id
         WHERE jl.id = ?
         LIMIT 1'
    );
    $stmt->execute([$lessonId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function normalize_journal_flags(string $mark, bool $activity, bool $late): array
{
    $present = $mark !== 'Н';
    $hasGrade = in_array($mark, ['2', '3', '4', '5'], true);

    return [
        'activity' => $present && $hasGrade && $activity,
        'late' => $present && $late,
    ];
}

function save_journal_grade(
    int $lessonId,
    int $studentId,
    string $mark,
    bool $activity = false,
    bool $late = false
): array {
    $lesson = get_journal_lesson_by_id($lessonId);
    if ($lesson === null) {
        return ['success' => false, 'error' => 'Урок не найден.'];
    }

    if (!can_access_journal_item((int) $lesson['curriculum_item_id'])) {
        return ['success' => false, 'error' => 'Нет доступа к журналу.'];
    }

    $student = get_student_by_id($studentId);
    if ($student === null || (int) $student['group_id'] !== (int) $lesson['group_id']) {
        return ['success' => false, 'error' => 'Студент не относится к этой группе.'];
    }

    $normalizedMark = normalize_journal_mark($mark);
    if ($normalizedMark === null) {
        return ['success' => false, 'error' => 'Допустимы оценки 2–5 или Н.'];
    }

    $flags = normalize_journal_flags($normalizedMark, $activity, $late);
    if (!is_brs_grading()) {
        $flags = ['activity' => false, 'late' => false];
    }
    $activity = $flags['activity'];
    $late = $flags['late'];

    $oldStmt = db()->prepare(
        'SELECT mark, activity, late FROM journal_grades WHERE lesson_id = ? AND student_id = ? LIMIT 1'
    );
    $oldStmt->execute([$lessonId, $studentId]);
    $oldRow = $oldStmt->fetch();
    $oldMark = $oldRow && trim((string) ($oldRow['mark'] ?? '')) !== ''
        ? (string) $oldRow['mark']
        : '—';

    if ($normalizedMark === '' && !$activity && !$late) {
        $stmt = db()->prepare(
            'DELETE FROM journal_grades WHERE lesson_id = ? AND student_id = ?'
        );
        $stmt->execute([$lessonId, $studentId]);

        activity_log_journal_grade($lesson, $student, $oldMark, '—', $activity, $late);

        return [
            'success' => true,
            'entry' => empty_journal_entry(),
        ];
    }

    $stmt = db()->prepare(
        'INSERT INTO journal_grades (lesson_id, student_id, mark, activity, late)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            mark = VALUES(mark),
            activity = VALUES(activity),
            late = VALUES(late)'
    );
    $stmt->execute([
        $lessonId,
        $studentId,
        $normalizedMark,
        $activity ? 1 : 0,
        $late ? 1 : 0,
    ]);

    activity_log_journal_grade($lesson, $student, $oldMark, $normalizedMark, $activity, $late);

    return [
        'success' => true,
        'entry' => [
            'mark' => $normalizedMark,
            'activity' => $activity,
            'late' => $late,
        ],
    ];
}

function render_journal_mark_label(string $mark): string
{
    return $mark === '' ? '—' : $mark;
}

function format_journal_date(string $date): string
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    return date('d.m', $timestamp);
}

function render_journal_choice_icon(string $type = 'journal'): string
{
    if ($type === 'group') {
        return '<span class="journal-choice__icon journal-choice__icon--group" aria-hidden="true">'
            . '<svg viewBox="0 0 24 24" fill="none">'
            . '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>'
            . '<circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.75"/>'
            . '<path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>'
            . '</svg></span>';
    }

    return '<span class="journal-choice__icon journal-choice__icon--journal" aria-hidden="true">'
        . '<svg viewBox="0 0 24 24" fill="none">'
        . '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>'
        . '<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>'
        . '<path d="M8 7h8M8 11h6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>'
        . '</svg></span>';
}
