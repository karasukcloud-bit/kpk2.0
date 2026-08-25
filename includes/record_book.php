<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function get_teacher_name_by_curriculum_item(int $curriculumItemId): string
{
    if ($curriculumItemId <= 0) {
        return '';
    }

    $stmt = db()->prepare(
        'SELECT u.full_name
         FROM curriculum_items ci
         LEFT JOIN users u ON u.id = ci.teacher_id
         WHERE ci.id = ?
         LIMIT 1'
    );
    $stmt->execute([$curriculumItemId]);

    return trim((string) ($stmt->fetchColumn() ?: ''));
}

function get_attestation_form_by_curriculum_item(int $curriculumItemId): string
{
    if ($curriculumItemId <= 0) {
        return '';
    }

    if (!db()->query("SHOW TABLES LIKE 'ktp_topics'")->fetch()) {
        return '';
    }

    $stmt = db()->prepare(
        "SELECT lesson_type
         FROM ktp_topics
         WHERE curriculum_item_id = ?
           AND lesson_type IN ('exam', 'diff_credit', 'credit', 'control')
         ORDER BY FIELD(lesson_type, 'exam', 'diff_credit', 'credit', 'control')
         LIMIT 1"
    );
    $stmt->execute([$curriculumItemId]);

    return (string) ($stmt->fetchColumn() ?: '');
}

function record_book_attestation_label(string $form): string
{
    $labels = [
        'diff_credit' => 'Дифференцированный зачёт',
        'credit' => 'Зачёт',
        'exam' => 'Экзамен',
        'control' => 'Контрольная работа',
    ];

    return $labels[$form] ?? '';
}

function is_record_book_exam_form(string $form): bool
{
    return $form === 'exam';
}

function split_record_book_entries(array $entries): array
{
    $credits = [];
    $exams = [];

    foreach ($entries as $entry) {
        $form = trim((string) ($entry['attestation_form'] ?? ''));
        if ($form === '' && (int) ($entry['curriculum_item_id'] ?? 0) > 0) {
            $form = get_attestation_form_by_curriculum_item((int) $entry['curriculum_item_id']);
            $entry['attestation_form'] = $form;
        }

        if (is_record_book_exam_form($form)) {
            $exams[] = $entry;
        } else {
            $credits[] = $entry;
        }
    }

    return [
        'credits' => $credits,
        'exams' => $exams,
    ];
}

function upsert_record_book_entry(
    int $studentId,
    string $academicYear,
    string $semester,
    int $curriculumItemId,
    string $subjectName,
    $grade,
    ?string $teacherName = null,
    ?string $attestationForm = null
): void {
    $gradeValue = ($grade === null || $grade === '') ? null : (int) $grade;
    $teacher = trim((string) ($teacherName ?? ''));
    if ($teacher === '') {
        $teacher = get_teacher_name_by_curriculum_item($curriculumItemId);
    }

    $form = trim((string) ($attestationForm ?? ''));
    if ($form === '') {
        $form = get_attestation_form_by_curriculum_item($curriculumItemId);
    }

    $stmt = db()->prepare(
        'INSERT INTO student_record_book
            (student_id, academic_year, semester, curriculum_item_id, subject_name, teacher_name, attestation_form, grade)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            subject_name = VALUES(subject_name),
            teacher_name = IF(VALUES(teacher_name) = \'\', teacher_name, VALUES(teacher_name)),
            attestation_form = IF(VALUES(attestation_form) = \'\', attestation_form, VALUES(attestation_form)),
            grade = VALUES(grade)'
    );
    $stmt->execute([
        $studentId,
        $academicYear,
        $semester,
        $curriculumItemId,
        $subjectName,
        $teacher,
        $form,
        $gradeValue,
    ]);
}

function sync_record_book_from_gradebook_archive(int $archiveId, string $academicYear, string $semester): void
{
    $stmt = db()->prepare(
        'SELECT g.student_id, g.curriculum_item_id, g.grade, s.subject_name, s.teacher_name
         FROM archive_gradebook_grades g
         INNER JOIN archive_gradebook_subjects s
            ON s.archive_id = g.archive_id
           AND s.group_id = g.group_id
           AND s.curriculum_item_id = g.curriculum_item_id
         WHERE g.archive_id = ?'
    );
    $stmt->execute([$archiveId]);

    foreach ($stmt->fetchAll() as $row) {
        upsert_record_book_entry(
            (int) $row['student_id'],
            $academicYear,
            $semester,
            (int) $row['curriculum_item_id'],
            (string) $row['subject_name'],
            $row['grade'],
            (string) ($row['teacher_name'] ?? '')
        );
    }
}

function sync_record_book_grade(
    int $studentId,
    string $academicYear,
    string $semester,
    int $curriculumItemId,
    string $subjectName,
    int $grade,
    ?string $teacherName = null
): void {
    upsert_record_book_entry(
        $studentId,
        $academicYear,
        $semester,
        $curriculumItemId,
        $subjectName,
        $grade,
        $teacherName
    );
}

function get_student_record_book(int $studentId): array
{
    sync_student_record_book_from_archives($studentId);

    $stmt = db()->prepare(
        'SELECT academic_year, semester, curriculum_item_id, subject_name, teacher_name, attestation_form, grade, updated_at
         FROM student_record_book
         WHERE student_id = ?
         ORDER BY academic_year DESC, semester DESC, subject_name ASC'
    );
    $stmt->execute([$studentId]);
    $rows = $stmt->fetchAll();

    $periods = [];
    foreach ($rows as $row) {
        $itemId = (int) $row['curriculum_item_id'];

        if (trim((string) ($row['teacher_name'] ?? '')) === '') {
            $teacher = get_teacher_name_by_curriculum_item($itemId);
            if ($teacher !== '') {
                $row['teacher_name'] = $teacher;
                db()->prepare(
                    'UPDATE student_record_book
                     SET teacher_name = ?
                     WHERE student_id = ? AND academic_year = ? AND semester = ? AND curriculum_item_id = ?'
                )->execute([
                    $teacher,
                    $studentId,
                    $row['academic_year'],
                    $row['semester'],
                    $itemId,
                ]);
            }
        }

        if (trim((string) ($row['attestation_form'] ?? '')) === '') {
            $form = get_attestation_form_by_curriculum_item($itemId);
            if ($form !== '') {
                $row['attestation_form'] = $form;
                db()->prepare(
                    'UPDATE student_record_book
                     SET attestation_form = ?
                     WHERE student_id = ? AND academic_year = ? AND semester = ? AND curriculum_item_id = ?'
                )->execute([
                    $form,
                    $studentId,
                    $row['academic_year'],
                    $row['semester'],
                    $itemId,
                ]);
            }
        }

        $key = $row['academic_year'] . '|' . $row['semester'];
        if (!isset($periods[$key])) {
            $periods[$key] = [
                'academic_year' => (string) $row['academic_year'],
                'semester' => (string) $row['semester'],
                'entries' => [],
            ];
        }
        $periods[$key]['entries'][] = $row;
    }

    return array_values($periods);
}

function sync_student_record_book_from_archives(int $studentId): void
{
    static $done = [];
    if (isset($done[$studentId])) {
        return;
    }
    $done[$studentId] = true;

    $table = db()->query("SHOW TABLES LIKE 'student_record_book'")->fetch();
    if (!$table) {
        return;
    }

    $stmt = db()->prepare(
        'SELECT ap.id, ap.academic_year, ap.semester
         FROM archive_periods ap
         WHERE ap.archive_type = \'gradebook\''
    );
    $stmt->execute();

    foreach ($stmt->fetchAll() as $archive) {
        $grades = db()->prepare(
            'SELECT g.curriculum_item_id, g.grade, s.subject_name, s.teacher_name
             FROM archive_gradebook_grades g
             INNER JOIN archive_gradebook_subjects s
                ON s.archive_id = g.archive_id
               AND s.group_id = g.group_id
               AND s.curriculum_item_id = g.curriculum_item_id
             WHERE g.archive_id = ? AND g.student_id = ?'
        );
        $grades->execute([(int) $archive['id'], $studentId]);
        foreach ($grades->fetchAll() as $row) {
            upsert_record_book_entry(
                $studentId,
                (string) $archive['academic_year'],
                (string) $archive['semester'],
                (int) $row['curriculum_item_id'],
                (string) $row['subject_name'],
                $row['grade'],
                (string) ($row['teacher_name'] ?? '')
            );
        }
    }
}

function summarize_record_book_period(array $entries): array
{
    $grades = [];
    foreach ($entries as $entry) {
        if ($entry['grade'] !== null && $entry['grade'] !== '') {
            $grades[] = (int) $entry['grade'];
        }
    }

    $count = count($grades);

    return [
        'total' => count($entries),
        'graded' => $count,
        'average' => $count > 0 ? round(array_sum($grades) / $count, 2) : null,
    ];
}
