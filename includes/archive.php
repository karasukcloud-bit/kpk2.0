<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/organization.php';
require_once __DIR__ . '/students.php';
require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/journal.php';
require_once __DIR__ . '/ktp.php';
require_once __DIR__ . '/grading.php';
require_once __DIR__ . '/record_book.php';

function archive_grade_change_reasons(): array
{
    return [
        'retake' => 'Пересдал',
        'error' => 'Ошибка',
        'appeal' => 'Апелляция',
        'other' => 'Другое',
    ];
}

function archive_reason_label(string $code): string
{
    return archive_grade_change_reasons()[$code] ?? $code;
}

function get_archive_period(string $type, string $academicYear, string $semester): ?array
{
    $stmt = db()->prepare(
        "SELECT ap.*, u.full_name AS archived_by_name
         FROM archive_periods ap
         LEFT JOIN users u ON u.id = ap.archived_by
         WHERE ap.archive_type = ? AND ap.academic_year = ? AND ap.semester = ?
         LIMIT 1"
    );
    $stmt->execute([$type, $academicYear, $semester]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function get_archive_period_by_id(int $id): ?array
{
    $stmt = db()->prepare(
        "SELECT ap.*, u.full_name AS archived_by_name
         FROM archive_periods ap
         LEFT JOIN users u ON u.id = ap.archived_by
         WHERE ap.id = ?
         LIMIT 1"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function list_archive_periods(?string $type = null): array
{
    $sql = "SELECT ap.*, u.full_name AS archived_by_name
            FROM archive_periods ap
            LEFT JOIN users u ON u.id = ap.archived_by";
    $params = [];
    if ($type !== null) {
        $sql .= ' WHERE ap.archive_type = ?';
        $params[] = $type;
    }
    $sql .= ' ORDER BY ap.academic_year DESC, ap.semester DESC, ap.archive_type ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/** Уникальные сохранённые периоды (год + семестр) для просмотра архива. */
function list_saved_archive_periods(): array
{
    $stmt = db()->query(
        'SELECT academic_year, semester
         FROM archive_periods
         GROUP BY academic_year, semester
         ORDER BY academic_year DESC, semester DESC'
    );

    return $stmt->fetchAll();
}

/** Периоды с архивом ведомостей. */
function list_saved_archive_gradebook_periods(): array
{
    $stmt = db()->query(
        "SELECT id AS archive_id, academic_year, semester
         FROM archive_periods
         WHERE archive_type = 'gradebook'
         ORDER BY academic_year DESC, semester DESC"
    );

    return $stmt->fetchAll();
}

function can_view_archive_gradebook(): bool
{
    return can_manage_archives() || can_use_curator_panel();
}

function delete_archive_period(string $type, string $academicYear, string $semester): void
{
    $stmt = db()->prepare(
        'DELETE FROM archive_periods WHERE archive_type = ? AND academic_year = ? AND semester = ?'
    );
    $stmt->execute([$type, $academicYear, $semester]);
}

function delete_archive_period_by_admin(
    string $type,
    string $academicYear,
    string $semester,
    string $confirmText,
    bool $acknowledged
): array {
    if (!can_delete_archives()) {
        return ['success' => false, 'error' => 'Удалять архив может только администратор.'];
    }

    if ($type !== 'gradebook' && $type !== 'journal') {
        return ['success' => false, 'error' => 'Некорректный тип архива.'];
    }

    $academicYear = normalize_academic_year($academicYear) ?? '';
    $semester = normalize_gradebook_semester($semester);
    if ($academicYear === '') {
        return ['success' => false, 'error' => 'Некорректный учебный год.'];
    }

    if (!$acknowledged || trim($confirmText) !== 'УДАЛИТЬ') {
        return ['success' => false, 'error' => 'Для удаления введите слово УДАЛИТЬ и подтвердите действие.'];
    }

    if (get_archive_period($type, $academicYear, $semester) === null) {
        return ['success' => false, 'error' => 'Архив за выбранный период не найден.'];
    }

    delete_archive_period($type, $academicYear, $semester);

    return ['success' => true];
}

function archive_gradebooks_for_period(string $academicYear, string $semester, bool $replace = false): array
{
    $academicYear = normalize_academic_year($academicYear) ?? '';
    $semester = normalize_gradebook_semester($semester);
    if ($academicYear === '') {
        return ['success' => false, 'error' => 'Некорректный учебный год.'];
    }

    $existing = get_archive_period('gradebook', $academicYear, $semester);
    if ($existing && !$replace) {
        return ['success' => false, 'error' => 'already_exists'];
    }

    $groups = get_all_groups();
    if ($groups === []) {
        return ['success' => false, 'error' => 'В системе нет групп для архивации.'];
    }

    $incomplete = [];
    foreach ($groups as $group) {
        $status = get_gradebook_completion_status((int) $group['id'], $academicYear, $semester);
        if (empty($status['empty']) && empty($status['complete'])) {
            $incomplete[] = (string) $group['number'];
        }
    }
    if ($incomplete !== []) {
        return [
            'success' => false,
            'error' => 'Нельзя архивировать: не все оценки выставлены в группах '
                . implode(', ', $incomplete) . '.',
        ];
    }

    $user = current_user();
    $archiveId = 0;
    $pdo = db();
    $pdo->beginTransaction();

    try {
        if ($existing) {
            delete_archive_period('gradebook', $academicYear, $semester);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO archive_periods (archive_type, academic_year, semester, archived_by)
             VALUES (\'gradebook\', ?, ?, ?)'
        );
        $stmt->execute([$academicYear, $semester, $user ? (int) $user['id'] : null]);
        $archiveId = (int) $pdo->lastInsertId();

        $groupStmt = $pdo->prepare(
            'INSERT INTO archive_gradebook_groups
                (archive_id, group_id, group_number, specialty_name, specialty_code, curator_name)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $studentStmt = $pdo->prepare(
            'INSERT INTO archive_gradebook_students (archive_id, group_id, student_id, full_name, sort_order)
             VALUES (?, ?, ?, ?, ?)'
        );
        $subjectStmt = $pdo->prepare(
            'INSERT INTO archive_gradebook_subjects
                (archive_id, group_id, curriculum_item_id, subject_name, teacher_name, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $gradeStmt = $pdo->prepare(
            'INSERT INTO archive_gradebook_grades
                (archive_id, group_id, student_id, curriculum_item_id, grade)
             VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($groups as $group) {
            $groupId = (int) $group['id'];
            $groupStmt->execute([
                $archiveId,
                $groupId,
                (string) $group['number'],
                (string) ($group['specialty_name'] ?? ''),
                (string) ($group['specialty_code'] ?? ''),
                (string) ($group['curator_name'] ?? ''),
            ]);

            $students = get_students_by_group($groupId);
            $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);
            $grades = get_gradebook_grades_from_journal($groupId, $academicYear, $semester);

            foreach ($students as $index => $student) {
                $studentStmt->execute([
                    $archiveId,
                    $groupId,
                    (int) $student['id'],
                    (string) $student['full_name'],
                    $index + 1,
                ]);
            }

            foreach ($subjects as $index => $subject) {
                $subjectStmt->execute([
                    $archiveId,
                    $groupId,
                    (int) $subject['curriculum_item_id'],
                    (string) $subject['subject_name'],
                    (string) ($subject['teacher_name'] ?? ''),
                    $index + 1,
                ]);
            }

            foreach ($students as $student) {
                $studentId = (int) $student['id'];
                foreach ($subjects as $subject) {
                    $itemId = (int) $subject['curriculum_item_id'];
                    $grade = $grades[$studentId][$itemId] ?? null;
                    $gradeStmt->execute([$archiveId, $groupId, $studentId, $itemId, $grade]);
                }
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Не удалось сохранить архив ведомостей.'];
    }

    sync_record_book_from_gradebook_archive($archiveId, $academicYear, $semester);

    return ['success' => true];
}

function archive_journals_for_period(string $academicYear, string $semester, bool $replace = false): array
{
    $academicYear = normalize_academic_year($academicYear) ?? '';
    $semester = normalize_gradebook_semester($semester);
    if ($academicYear === '') {
        return ['success' => false, 'error' => 'Некорректный учебный год.'];
    }

    $existing = get_archive_period('journal', $academicYear, $semester);
    if ($existing && !$replace) {
        return ['success' => false, 'error' => 'already_exists'];
    }

    $groups = get_all_groups();
    if ($groups === []) {
        return ['success' => false, 'error' => 'В системе нет групп для архивации.'];
    }

    $user = current_user();
    $config = get_grading_config();
    $pdo = db();
    $pdo->beginTransaction();

    try {
        if ($existing) {
            delete_archive_period('journal', $academicYear, $semester);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO archive_periods (archive_type, academic_year, semester, archived_by)
             VALUES (\'journal\', ?, ?, ?)'
        );
        $stmt->execute([$academicYear, $semester, $user ? (int) $user['id'] : null]);
        $archiveId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO archive_journal_items
                (archive_id, group_id, group_number, curriculum_item_id, subject_name, teacher_name, semester)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $studentStmt = $pdo->prepare(
            'INSERT INTO archive_journal_students (item_id, student_id, full_name, sort_order)
             VALUES (?, ?, ?, ?)'
        );
        $lessonStmt = $pdo->prepare(
            'INSERT INTO archive_journal_lessons
                (item_id, source_lesson_id, lesson_date, topic_title, topic_lesson_type, topic_hours, grade_type, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $topicStmt = $pdo->prepare(
            'INSERT INTO archive_journal_topics
                (item_id, source_topic_id, title, lesson_type, hours, sort_order, completed, first_lesson_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $gradeStmt = $pdo->prepare(
            'INSERT INTO archive_journal_grades (lesson_id, student_id, mark, activity, late)
             VALUES (?, ?, ?, ?, ?)'
        );
        $totalStmt = $pdo->prepare(
            'INSERT INTO archive_journal_totals
                (item_id, student_id, final_grade, average, points, display)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        foreach ($groups as $group) {
            $groupId = (int) $group['id'];
            $students = get_students_by_group($groupId);
            $subjects = get_group_curriculum_subjects($groupId, $academicYear, $semester);

            foreach ($subjects as $subject) {
                $curriculumItemId = (int) $subject['curriculum_item_id'];
                $itemStmt->execute([
                    $archiveId,
                    $groupId,
                    (string) $group['number'],
                    $curriculumItemId,
                    (string) $subject['subject_name'],
                    (string) ($subject['teacher_name'] ?? ''),
                    (string) ($subject['semester'] ?? $semester),
                ]);
                $archiveItemId = (int) $pdo->lastInsertId();

                foreach ($students as $index => $student) {
                    $studentStmt->execute([
                        $archiveItemId,
                        (int) $student['id'],
                        (string) $student['full_name'],
                        $index + 1,
                    ]);
                }

                $lessons = get_journal_lessons($curriculumItemId);
                $journalGrades = get_journal_grades($curriculumItemId);
                $totals = build_journal_totals($students, $lessons, $journalGrades, $config);
                $ktpTopics = get_ktp_topics_with_progress($curriculumItemId);

                foreach ($ktpTopics as $index => $topic) {
                    $firstDate = $topic['first_lesson_date'] ?? null;
                    $topicStmt->execute([
                        $archiveItemId,
                        (int) $topic['id'],
                        (string) $topic['title'],
                        (string) ($topic['lesson_type'] ?? 'lecture'),
                        $topic['hours'] ?? 2,
                        (int) ($topic['sort_order'] ?? ($index + 1)),
                        !empty($topic['completed']) ? 1 : 0,
                        $firstDate !== null && $firstDate !== '' ? $firstDate : null,
                    ]);
                }

                foreach ($lessons as $index => $lesson) {
                    $topicHours = $lesson['topic_hours'] ?? null;
                    $lessonStmt->execute([
                        $archiveItemId,
                        (int) $lesson['id'],
                        (string) $lesson['lesson_date'],
                        (string) ($lesson['topic_title'] ?? ''),
                        (string) ($lesson['topic_lesson_type'] ?? ''),
                        $topicHours !== null && $topicHours !== '' ? $topicHours : null,
                        (string) ($lesson['grade_type'] ?? 'current'),
                        $index + 1,
                    ]);
                    $archiveLessonId = (int) $pdo->lastInsertId();

                    foreach ($students as $student) {
                        $studentId = (int) $student['id'];
                        $entry = $journalGrades[$studentId][(int) $lesson['id']] ?? empty_journal_entry();
                        $gradeStmt->execute([
                            $archiveLessonId,
                            $studentId,
                            (string) ($entry['mark'] ?? ''),
                            !empty($entry['activity']) ? 1 : 0,
                            !empty($entry['late']) ? 1 : 0,
                        ]);
                    }
                }

                foreach ($students as $student) {
                    $studentId = (int) $student['id'];
                    $total = $totals[$studentId] ?? [];
                    $final = resolve_journal_final_grade_for_gradebook($total);
                    $totalStmt->execute([
                        $archiveItemId,
                        $studentId,
                        $final,
                        $total['average'] ?? null,
                        $total['points'] ?? null,
                        (string) ($total['display'] ?? ''),
                    ]);
                }
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Не удалось сохранить архив журналов.'];
    }

    return ['success' => true];
}

function get_archive_gradebook_groups(int $archiveId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM archive_gradebook_groups WHERE archive_id = ? ORDER BY group_number ASC'
    );
    $stmt->execute([$archiveId]);

    return $stmt->fetchAll();
}

function get_archive_gradebook_group(int $archiveId, int $groupId): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM archive_gradebook_groups WHERE archive_id = ? AND group_id = ? LIMIT 1'
    );
    $stmt->execute([$archiveId, $groupId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function get_archive_gradebook_sheet(int $archiveId, int $groupId): array
{
    $st = db()->prepare(
        'SELECT * FROM archive_gradebook_students
         WHERE archive_id = ? AND group_id = ?
         ORDER BY sort_order ASC, full_name ASC'
    );
    $st->execute([$archiveId, $groupId]);
    $students = $st->fetchAll();

    $sub = db()->prepare(
        'SELECT * FROM archive_gradebook_subjects
         WHERE archive_id = ? AND group_id = ?
         ORDER BY sort_order ASC, subject_name ASC'
    );
    $sub->execute([$archiveId, $groupId]);
    $subjects = $sub->fetchAll();

    $gr = db()->prepare(
        'SELECT student_id, curriculum_item_id, grade
         FROM archive_gradebook_grades
         WHERE archive_id = ? AND group_id = ?'
    );
    $gr->execute([$archiveId, $groupId]);
    $grades = [];
    foreach ($gr->fetchAll() as $row) {
        $grades[(int) $row['student_id']][(int) $row['curriculum_item_id']] = $row['grade'] !== null
            ? (int) $row['grade']
            : null;
    }

    $ch = db()->prepare(
        "SELECT c.*, u.full_name AS changed_by_name
         FROM archive_grade_changes c
         LEFT JOIN users u ON u.id = c.changed_by
         WHERE c.archive_id = ? AND c.group_id = ?
         ORDER BY c.changed_at DESC, c.id DESC"
    );
    $ch->execute([$archiveId, $groupId]);
    $changes = [];
    foreach ($ch->fetchAll() as $row) {
        $key = (int) $row['student_id'] . ':' . (int) $row['curriculum_item_id'];
        $changes[$key][] = $row;
    }

    return [
        'students' => $students,
        'subjects' => $subjects,
        'grades' => $grades,
        'changes' => $changes,
    ];
}

function get_archive_journal_groups(int $archiveId): array
{
    $stmt = db()->prepare(
        'SELECT group_id, group_number, COUNT(*) AS subjects_count
         FROM archive_journal_items
         WHERE archive_id = ?
         GROUP BY group_id, group_number
         ORDER BY group_number ASC'
    );
    $stmt->execute([$archiveId]);

    return $stmt->fetchAll();
}

function get_archive_journal_items(int $archiveId, int $groupId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM archive_journal_items
         WHERE archive_id = ? AND group_id = ?
         ORDER BY subject_name ASC'
    );
    $stmt->execute([$archiveId, $groupId]);

    return $stmt->fetchAll();
}

function get_archive_journal_item(int $itemId): ?array
{
    $stmt = db()->prepare('SELECT * FROM archive_journal_items WHERE id = ? LIMIT 1');
    $stmt->execute([$itemId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function get_archive_journal_sheet(int $itemId): array
{
    $st = db()->prepare(
        'SELECT * FROM archive_journal_students WHERE item_id = ? ORDER BY sort_order ASC, full_name ASC'
    );
    $st->execute([$itemId]);
    $students = $st->fetchAll();

    $ls = db()->prepare(
        'SELECT * FROM archive_journal_lessons WHERE item_id = ? ORDER BY sort_order ASC, lesson_date ASC, id ASC'
    );
    $ls->execute([$itemId]);
    $lessons = $ls->fetchAll();

    $grades = [];
    if ($lessons !== []) {
        $ids = array_map(static fn (array $lesson): int => (int) $lesson['id'], $lessons);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $gr = db()->prepare(
            "SELECT lesson_id, student_id, mark, activity, late
             FROM archive_journal_grades
             WHERE lesson_id IN ($placeholders)"
        );
        $gr->execute($ids);
        foreach ($gr->fetchAll() as $row) {
            $grades[(int) $row['student_id']][(int) $row['lesson_id']] = [
                'mark' => (string) $row['mark'],
                'activity' => (int) $row['activity'] === 1,
                'late' => (int) $row['late'] === 1,
            ];
        }
    }

    $tot = db()->prepare(
        'SELECT * FROM archive_journal_totals WHERE item_id = ?'
    );
    $tot->execute([$itemId]);
    $totals = [];
    foreach ($tot->fetchAll() as $row) {
        $totals[(int) $row['student_id']] = $row;
    }

    $topics = [];
    $topicsTable = db()->query("SHOW TABLES LIKE 'archive_journal_topics'")->fetch();
    if ($topicsTable) {
        $tp = db()->prepare(
            'SELECT * FROM archive_journal_topics WHERE item_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $tp->execute([$itemId]);
        $topics = $tp->fetchAll();
    }

    return [
        'students' => $students,
        'lessons' => $lessons,
        'grades' => $grades,
        'totals' => $totals,
        'topics' => $topics,
    ];
}

function update_archived_gradebook_grade(
    int $archiveId,
    int $groupId,
    int $studentId,
    int $curriculumItemId,
    int $newGrade,
    string $reasonCode,
    string $reasonText = ''
): array {
    if (!can_manage_archives()) {
        return ['success' => false, 'error' => 'Редактировать архив могут только завуч и администратор.'];
    }

    if (!in_array($newGrade, GRADE_VALUES, true)) {
        return ['success' => false, 'error' => 'Оценка должна быть от 2 до 5.'];
    }

    $reasons = archive_grade_change_reasons();
    if (!isset($reasons[$reasonCode])) {
        return ['success' => false, 'error' => 'Укажите причину изменения.'];
    }

    $reasonText = trim($reasonText);
    if ($reasonCode === 'other' && $reasonText === '') {
        return ['success' => false, 'error' => 'Укажите причину изменения.'];
    }

    $archive = get_archive_period_by_id($archiveId);
    if ($archive === null || $archive['archive_type'] !== 'gradebook') {
        return ['success' => false, 'error' => 'Архив ведомости не найден.'];
    }

    $stmt = db()->prepare(
        'SELECT grade FROM archive_gradebook_grades
         WHERE archive_id = ? AND group_id = ? AND student_id = ? AND curriculum_item_id = ?
         LIMIT 1'
    );
    $stmt->execute([$archiveId, $groupId, $studentId, $curriculumItemId]);
    $current = $stmt->fetch();
    if (!$current) {
        return ['success' => false, 'error' => 'Оценка в архиве не найдена.'];
    }

    $oldGrade = $current['grade'] !== null ? (int) $current['grade'] : null;
    if ($oldGrade === $newGrade) {
        return ['success' => false, 'error' => 'Новая оценка совпадает с текущей.'];
    }

    $user = current_user();
    $subjectName = '';
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $upd = $pdo->prepare(
            'UPDATE archive_gradebook_grades
             SET grade = ?
             WHERE archive_id = ? AND group_id = ? AND student_id = ? AND curriculum_item_id = ?'
        );
        $upd->execute([$newGrade, $archiveId, $groupId, $studentId, $curriculumItemId]);

        $log = $pdo->prepare(
            'INSERT INTO archive_grade_changes
                (archive_id, group_id, student_id, curriculum_item_id, old_grade, new_grade,
                 reason_code, reason_text, changed_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $log->execute([
            $archiveId,
            $groupId,
            $studentId,
            $curriculumItemId,
            $oldGrade,
            $newGrade,
            $reasonCode,
            $reasonText,
            $user ? (int) $user['id'] : null,
        ]);

        $journalArchive = get_archive_period(
            'journal',
            (string) $archive['academic_year'],
            (string) $archive['semester']
        );
        if ($journalArchive) {
            $item = $pdo->prepare(
                'SELECT id FROM archive_journal_items
                 WHERE archive_id = ? AND group_id = ? AND curriculum_item_id = ?
                 LIMIT 1'
            );
            $item->execute([(int) $journalArchive['id'], $groupId, $curriculumItemId]);
            $journalItemId = (int) ($item->fetchColumn() ?: 0);
            if ($journalItemId > 0) {
                $tot = $pdo->prepare(
                    'INSERT INTO archive_journal_totals (item_id, student_id, final_grade, display)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE final_grade = VALUES(final_grade), display = VALUES(display)'
                );
                $tot->execute([$journalItemId, $studentId, $newGrade, (string) $newGrade]);
            }
        }

        $subj = $pdo->prepare(
            'SELECT subject_name, teacher_name FROM archive_gradebook_subjects
             WHERE archive_id = ? AND group_id = ? AND curriculum_item_id = ?
             LIMIT 1'
        );
        $subj->execute([$archiveId, $groupId, $curriculumItemId]);
        $subjRow = $subj->fetch() ?: [];
        $subjectName = (string) ($subjRow['subject_name'] ?? '');
        $teacherName = (string) ($subjRow['teacher_name'] ?? '');

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Не удалось сохранить изменение оценки.'];
    }

    if ($subjectName !== '') {
        sync_record_book_grade(
            $studentId,
            (string) $archive['academic_year'],
            (string) $archive['semester'],
            $curriculumItemId,
            $subjectName,
            $newGrade,
            $teacherName
        );
    }

    return ['success' => true];
}
