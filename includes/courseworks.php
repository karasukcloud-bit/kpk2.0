<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function get_student_courseworks(int $studentId): array
{
    if (!db()->query("SHOW TABLES LIKE 'student_courseworks'")->fetch()) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT id, student_id, subject_name, topic, defense_date, teacher_name, grade, sort_order
         FROM student_courseworks
         WHERE student_id = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([$studentId]);

    return $stmt->fetchAll();
}

function get_expelled_courseworks(int $expelledId): array
{
    if (!db()->query("SHOW TABLES LIKE 'expelled_courseworks'")->fetch()) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT id, expelled_id, subject_name, topic, defense_date, teacher_name, grade, sort_order
         FROM expelled_courseworks
         WHERE expelled_id = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([$expelledId]);

    return $stmt->fetchAll();
}

function get_student_coursework(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM student_courseworks WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function normalize_coursework_grade($grade): ?int
{
    if ($grade === null || $grade === '') {
        return null;
    }
    $value = (int) $grade;
    if ($value < 2 || $value > 5) {
        return null;
    }

    return $value;
}

function normalize_coursework_date(string $date): ?string
{
    $date = trim($date);
    if ($date === '') {
        return null;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return null;
    }

    return $date;
}

function save_student_coursework(int $studentId, array $data, int $id = 0): array
{
    $subjectName = trim((string) ($data['subject_name'] ?? ''));
    $topic = trim((string) ($data['topic'] ?? ''));
    $teacherName = trim((string) ($data['teacher_name'] ?? ''));
    $defenseDate = normalize_coursework_date((string) ($data['defense_date'] ?? ''));
    $grade = normalize_coursework_grade($data['grade'] ?? null);

    if ($subjectName === '') {
        return ['success' => false, 'error' => 'Укажите наименование дисциплины (модуля), МДК.'];
    }
    if ($topic === '') {
        return ['success' => false, 'error' => 'Укажите тему курсового проекта (работы).'];
    }
    if (($data['defense_date'] ?? '') !== '' && $defenseDate === null) {
        return ['success' => false, 'error' => 'Некорректная дата защиты.'];
    }
    if (($data['grade'] ?? '') !== '' && $grade === null) {
        return ['success' => false, 'error' => 'Оценка должна быть от 2 до 5.'];
    }

    if ($id > 0) {
        $existing = get_student_coursework($id);
        if ($existing === null || (int) $existing['student_id'] !== $studentId) {
            return ['success' => false, 'error' => 'Запись курсовой работы не найдена.'];
        }
        db()->prepare(
            'UPDATE student_courseworks
             SET subject_name = ?, topic = ?, defense_date = ?, teacher_name = ?, grade = ?
             WHERE id = ? AND student_id = ?'
        )->execute([$subjectName, $topic, $defenseDate, $teacherName, $grade, $id, $studentId]);

        return ['success' => true, 'id' => $id];
    }

    $stmtSort = db()->prepare(
        'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM student_courseworks WHERE student_id = ?'
    );
    $stmtSort->execute([$studentId]);
    $sort = (int) $stmtSort->fetchColumn();

    db()->prepare(
        'INSERT INTO student_courseworks
            (student_id, subject_name, topic, defense_date, teacher_name, grade, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([$studentId, $subjectName, $topic, $defenseDate, $teacherName, $grade, $sort]);

    return ['success' => true, 'id' => (int) db()->lastInsertId()];
}

function delete_student_coursework(int $studentId, int $id): array
{
    $existing = get_student_coursework($id);
    if ($existing === null || (int) $existing['student_id'] !== $studentId) {
        return ['success' => false, 'error' => 'Запись курсовой работы не найдена.'];
    }

    db()->prepare('DELETE FROM student_courseworks WHERE id = ? AND student_id = ?')
        ->execute([$id, $studentId]);

    return ['success' => true];
}

function format_coursework_defense_date(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }
    $ts = strtotime($date);

    return $ts ? date('d.m.Y', $ts) : '—';
}

function copy_courseworks_to_expelled(int $studentId, int $expelledId): void
{
    if (!db()->query("SHOW TABLES LIKE 'expelled_courseworks'")->fetch()) {
        return;
    }

    $rows = get_student_courseworks($studentId);
    if ($rows === []) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO expelled_courseworks
            (expelled_id, subject_name, topic, defense_date, teacher_name, grade, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($rows as $row) {
        $stmt->execute([
            $expelledId,
            (string) $row['subject_name'],
            (string) $row['topic'],
            $row['defense_date'] ?: null,
            (string) ($row['teacher_name'] ?? ''),
            $row['grade'] !== null && $row['grade'] !== '' ? (int) $row['grade'] : null,
            (int) ($row['sort_order'] ?? 1),
        ]);
    }
}

function restore_courseworks_to_student(int $expelledId, int $studentId): void
{
    if (!db()->query("SHOW TABLES LIKE 'student_courseworks'")->fetch()) {
        return;
    }

    $rows = get_expelled_courseworks($expelledId);
    foreach ($rows as $row) {
        save_student_coursework($studentId, [
            'subject_name' => $row['subject_name'],
            'topic' => $row['topic'],
            'defense_date' => (string) ($row['defense_date'] ?? ''),
            'teacher_name' => $row['teacher_name'] ?? '',
            'grade' => $row['grade'],
        ]);
    }
}
