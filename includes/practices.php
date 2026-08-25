<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function get_student_practices(int $studentId): array
{
    if (!db()->query("SHOW TABLES LIKE 'student_practices'")->fetch()) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT id, student_id, module_name, org_supervisor_name, college_supervisor_name, grade, sort_order
         FROM student_practices
         WHERE student_id = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([$studentId]);

    return $stmt->fetchAll();
}

function get_expelled_practices(int $expelledId): array
{
    if (!db()->query("SHOW TABLES LIKE 'expelled_practices'")->fetch()) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT id, expelled_id, module_name, org_supervisor_name, college_supervisor_name, grade, sort_order
         FROM expelled_practices
         WHERE expelled_id = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([$expelledId]);

    return $stmt->fetchAll();
}

function get_student_practice(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM student_practices WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function normalize_practice_grade($grade): ?int
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

function save_student_practice(int $studentId, array $data, int $id = 0): array
{
    $moduleName = trim((string) ($data['module_name'] ?? ''));
    $orgSupervisor = trim((string) ($data['org_supervisor_name'] ?? ''));
    $collegeSupervisor = trim((string) ($data['college_supervisor_name'] ?? ''));
    $grade = normalize_practice_grade($data['grade'] ?? null);

    if ($moduleName === '') {
        return ['success' => false, 'error' => 'Укажите наименование профессионального модуля (ПМ).'];
    }
    if (($data['grade'] ?? '') !== '' && $grade === null) {
        return ['success' => false, 'error' => 'Оценка должна быть от 2 до 5.'];
    }

    if ($id > 0) {
        $existing = get_student_practice($id);
        if ($existing === null || (int) $existing['student_id'] !== $studentId) {
            return ['success' => false, 'error' => 'Запись о практике не найдена.'];
        }
        db()->prepare(
            'UPDATE student_practices
             SET module_name = ?, org_supervisor_name = ?, college_supervisor_name = ?, grade = ?
             WHERE id = ? AND student_id = ?'
        )->execute([$moduleName, $orgSupervisor, $collegeSupervisor, $grade, $id, $studentId]);

        return ['success' => true, 'id' => $id];
    }

    $stmtSort = db()->prepare(
        'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM student_practices WHERE student_id = ?'
    );
    $stmtSort->execute([$studentId]);
    $sort = (int) $stmtSort->fetchColumn();

    db()->prepare(
        'INSERT INTO student_practices
            (student_id, module_name, org_supervisor_name, college_supervisor_name, grade, sort_order)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$studentId, $moduleName, $orgSupervisor, $collegeSupervisor, $grade, $sort]);

    return ['success' => true, 'id' => (int) db()->lastInsertId()];
}

function delete_student_practice(int $studentId, int $id): array
{
    $existing = get_student_practice($id);
    if ($existing === null || (int) $existing['student_id'] !== $studentId) {
        return ['success' => false, 'error' => 'Запись о практике не найдена.'];
    }

    db()->prepare('DELETE FROM student_practices WHERE id = ? AND student_id = ?')
        ->execute([$id, $studentId]);

    return ['success' => true];
}

function copy_practices_to_expelled(int $studentId, int $expelledId): void
{
    if (!db()->query("SHOW TABLES LIKE 'expelled_practices'")->fetch()) {
        return;
    }

    $rows = get_student_practices($studentId);
    if ($rows === []) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO expelled_practices
            (expelled_id, module_name, org_supervisor_name, college_supervisor_name, grade, sort_order)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($rows as $row) {
        $stmt->execute([
            $expelledId,
            (string) $row['module_name'],
            (string) ($row['org_supervisor_name'] ?? ''),
            (string) ($row['college_supervisor_name'] ?? ''),
            $row['grade'] !== null && $row['grade'] !== '' ? (int) $row['grade'] : null,
            (int) ($row['sort_order'] ?? 1),
        ]);
    }
}

function restore_practices_to_student(int $expelledId, int $studentId): void
{
    if (!db()->query("SHOW TABLES LIKE 'student_practices'")->fetch()) {
        return;
    }

    foreach (get_expelled_practices($expelledId) as $row) {
        save_student_practice($studentId, [
            'module_name' => $row['module_name'],
            'org_supervisor_name' => $row['org_supervisor_name'] ?? '',
            'college_supervisor_name' => $row['college_supervisor_name'] ?? '',
            'grade' => $row['grade'],
        ]);
    }
}
