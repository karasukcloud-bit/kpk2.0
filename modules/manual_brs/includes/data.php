<?php

declare(strict_types=1);

function manual_brs_get_or_create_sheet(
    string $academicYear,
    int $period,
    int $groupId,
    int $curriculumItemId
): array {
    $period = manual_brs_normalize_period($period);
    $stmt = db()->prepare(
        'SELECT * FROM manual_brs_sheets
         WHERE academic_year = ? AND period = ? AND curriculum_item_id = ?
         LIMIT 1'
    );
    $stmt->execute([$academicYear, $period, $curriculumItemId]);
    $row = $stmt->fetch();
    if ($row) {
        return $row;
    }

    $ins = db()->prepare(
        'INSERT INTO manual_brs_sheets
         (academic_year, period, group_id, curriculum_item_id, lessons_total, updated_by)
         VALUES (?, ?, ?, ?, 0, ?)'
    );
    $ins->execute([
        $academicYear,
        $period,
        $groupId,
        $curriculumItemId,
        (int) (current_user()['id'] ?? 0) ?: null,
    ]);

    $stmt->execute([$academicYear, $period, $curriculumItemId]);
    $row = $stmt->fetch();

    return $row ?: [
        'id' => (int) db()->lastInsertId(),
        'academic_year' => $academicYear,
        'period' => $period,
        'group_id' => $groupId,
        'curriculum_item_id' => $curriculumItemId,
        'lessons_total' => 0,
    ];
}

function manual_brs_update_lessons_total(int $sheetId, int $lessonsTotal): array
{
    $lessonsTotal = max(0, min(200, $lessonsTotal));
    $stmt = db()->prepare(
        'UPDATE manual_brs_sheets
         SET lessons_total = ?, updated_by = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $lessonsTotal,
        (int) (current_user()['id'] ?? 0) ?: null,
        $sheetId,
    ]);

    $entries = manual_brs_get_entries_map($sheetId);
    foreach ($entries as $entry) {
        manual_brs_save_entry(
            $sheetId,
            (int) $entry['student_id'],
            [
                'current_marks' => manual_brs_marks_to_input(
                    manual_brs_parse_marks((string) $entry['current_marks'])
                ),
                'control_marks' => manual_brs_marks_to_input(
                    manual_brs_parse_marks((string) $entry['control_marks'])
                ),
                'absent_count' => (int) $entry['absent_count'],
                'late_count' => (int) $entry['late_count'],
                'activity_count' => (int) $entry['activity_count'],
            ],
            $lessonsTotal
        );
    }

    return ['success' => true, 'lessons_total' => $lessonsTotal];
}

function manual_brs_get_entries_map(int $sheetId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM manual_brs_entries WHERE sheet_id = ?'
    );
    $stmt->execute([$sheetId]);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int) $row['student_id']] = $row;
    }

    return $map;
}

function manual_brs_get_entry(int $sheetId, int $studentId): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM manual_brs_entries WHERE sheet_id = ? AND student_id = ? LIMIT 1'
    );
    $stmt->execute([$sheetId, $studentId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function manual_brs_save_entry(
    int $sheetId,
    int $studentId,
    array $input,
    int $lessonsTotal
): array {
    $student = get_student_by_id($studentId);
    if ($student === null) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    $sheetStmt = db()->prepare('SELECT * FROM manual_brs_sheets WHERE id = ? LIMIT 1');
    $sheetStmt->execute([$sheetId]);
    $sheet = $sheetStmt->fetch();
    if (!$sheet) {
        return ['success' => false, 'error' => 'Ведомость периода не найдена.'];
    }
    if ((int) $student['group_id'] !== (int) $sheet['group_id']) {
        return ['success' => false, 'error' => 'Студент не из этой группы.'];
    }

    $calc = manual_brs_calculate([
        'current_marks' => (string) ($input['current_marks'] ?? ''),
        'control_marks' => (string) ($input['control_marks'] ?? ''),
        'absent_count' => (int) ($input['absent_count'] ?? 0),
        'late_count' => (int) ($input['late_count'] ?? 0),
        'activity_count' => (int) ($input['activity_count'] ?? 0),
        'lessons_total' => $lessonsTotal,
    ]);

    $currentStorage = manual_brs_marks_to_storage($calc['current_marks']);
    $controlStorage = manual_brs_marks_to_storage($calc['control_marks']);

    $existing = manual_brs_get_entry($sheetId, $studentId);
    if ($existing) {
        $stmt = db()->prepare(
            'UPDATE manual_brs_entries
             SET current_marks = ?, control_marks = ?,
                 absent_count = ?, late_count = ?, activity_count = ?,
                 current_avg = ?, control_avg = ?, points = ?, grade = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $currentStorage,
            $controlStorage,
            $calc['absent_count'],
            $calc['late_count'],
            $calc['activity_count'],
            $calc['current_avg'],
            $calc['control_avg'],
            $calc['points'],
            $calc['grade'],
            (int) $existing['id'],
        ]);
    } else {
        $stmt = db()->prepare(
            'INSERT INTO manual_brs_entries
             (sheet_id, student_id, current_marks, control_marks,
              absent_count, late_count, activity_count,
              current_avg, control_avg, points, grade)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $sheetId,
            $studentId,
            $currentStorage,
            $controlStorage,
            $calc['absent_count'],
            $calc['late_count'],
            $calc['activity_count'],
            $calc['current_avg'],
            $calc['control_avg'],
            $calc['points'],
            $calc['grade'],
        ]);
    }

    return [
        'success' => true,
        'calc' => $calc,
        'entry' => manual_brs_get_entry($sheetId, $studentId),
    ];
}

function manual_brs_points_for_periods(
    string $academicYear,
    int $curriculumItemId,
    int $studentId,
    array $periods
): array {
    $result = [];
    foreach ($periods as $period) {
        $period = (int) $period;
        $stmt = db()->prepare(
            'SELECT e.points, e.grade
             FROM manual_brs_sheets s
             INNER JOIN manual_brs_entries e ON e.sheet_id = s.id
             WHERE s.academic_year = ? AND s.period = ?
               AND s.curriculum_item_id = ? AND e.student_id = ?
             LIMIT 1'
        );
        $stmt->execute([$academicYear, $period, $curriculumItemId, $studentId]);
        $row = $stmt->fetch();
        $result[$period] = [
            'points' => $row && $row['points'] !== null ? (float) $row['points'] : null,
            'grade' => $row && $row['grade'] !== null ? (int) $row['grade'] : null,
            'display' => manual_brs_period_display(
                $row && $row['points'] !== null ? (float) $row['points'] : null,
                $row && $row['grade'] !== null ? (int) $row['grade'] : null
            ),
        ];
    }

    return $result;
}

function manual_brs_get_attestation(
    string $academicYear,
    string $semester,
    int $curriculumItemId,
    int $studentId
): ?int {
    $semester = $semester === '2' ? '2' : '1';
    $stmt = db()->prepare(
        'SELECT grade FROM manual_brs_attestations
         WHERE academic_year = ? AND semester = ?
           AND curriculum_item_id = ? AND student_id = ?
         LIMIT 1'
    );
    $stmt->execute([$academicYear, $semester, $curriculumItemId, $studentId]);
    $grade = $stmt->fetchColumn();
    if ($grade === false || $grade === null || $grade === '') {
        return null;
    }
    $grade = (int) $grade;

    return ($grade >= 2 && $grade <= 5) ? $grade : null;
}

function manual_brs_get_attestations_map(
    string $academicYear,
    string $semester,
    int $curriculumItemId
): array {
    $semester = $semester === '2' ? '2' : '1';
    $stmt = db()->prepare(
        'SELECT student_id, grade FROM manual_brs_attestations
         WHERE academic_year = ? AND semester = ? AND curriculum_item_id = ?'
    );
    $stmt->execute([$academicYear, $semester, $curriculumItemId]);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $grade = $row['grade'] !== null ? (int) $row['grade'] : null;
        if ($grade !== null && $grade >= 2 && $grade <= 5) {
            $map[(int) $row['student_id']] = $grade;
        }
    }

    return $map;
}

function manual_brs_save_attestation(
    string $academicYear,
    string $semester,
    int $curriculumItemId,
    int $studentId,
    $grade
): array {
    $semester = $semester === '2' ? '2' : '1';
    $student = get_student_by_id($studentId);
    if ($student === null) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    if ($grade === null || $grade === '') {
        $stmt = db()->prepare(
            'DELETE FROM manual_brs_attestations
             WHERE academic_year = ? AND semester = ?
               AND curriculum_item_id = ? AND student_id = ?'
        );
        $stmt->execute([$academicYear, $semester, $curriculumItemId, $studentId]);

        return ['success' => true, 'grade' => null];
    }

    $grade = (int) $grade;
    if ($grade < 2 || $grade > 5) {
        return ['success' => false, 'error' => 'ПА: оценка 2–5 или пусто.'];
    }

    $stmt = db()->prepare(
        'INSERT INTO manual_brs_attestations
         (academic_year, semester, curriculum_item_id, student_id, grade)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE grade = VALUES(grade)'
    );
    $stmt->execute([$academicYear, $semester, $curriculumItemId, $studentId, $grade]);

    return ['success' => true, 'grade' => $grade];
}

function manual_brs_build_student_rows(
    array $students,
    array $entries,
    string $academicYear,
    int $curriculumItemId,
    int $period
): array {
    $period = manual_brs_normalize_period($period);
    $semester = manual_brs_semester_from_period($period);
    $paMap = manual_brs_get_attestations_map($academicYear, $semester, $curriculumItemId);
    $rows = [];

    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $entry = $entries[$studentId] ?? null;

        $periodPoints = null;
        if ($entry && $entry['points'] !== null) {
            $periodPoints = (float) $entry['points'];
        }
        $paGrade = $paMap[$studentId] ?? null;
        $sem = manual_brs_semester_total($periodPoints, null, $paGrade);

        $rows[] = [
            'student' => $student,
            'entry' => $entry,
            'points' => $entry && $entry['points'] !== null ? (float) $entry['points'] : null,
            'grade' => $entry && $entry['grade'] !== null ? (int) $entry['grade'] : null,
            'display' => $entry
                ? manual_brs_period_display(
                    $entry['points'] !== null ? (float) $entry['points'] : null,
                    $entry['grade'] !== null ? (int) $entry['grade'] : null
                )
                : '',
            'pa_grade' => $paGrade,
            'semester_display' => $sem['display'],
            'semester_grade' => $sem['grade'],
            'semester_points' => $sem['points'],
            'semester_html' => $sem['html'],
            'current_marks_input' => $entry
                ? manual_brs_marks_to_input(manual_brs_parse_marks((string) $entry['current_marks']))
                : '',
            'control_marks_input' => $entry
                ? manual_brs_marks_to_input(manual_brs_parse_marks((string) $entry['control_marks']))
                : '',
            'absent_count' => $entry ? (int) $entry['absent_count'] : 0,
            'late_count' => $entry ? (int) $entry['late_count'] : 0,
            'activity_count' => $entry ? (int) $entry['activity_count'] : 0,
        ];
    }

    return $rows;
}
