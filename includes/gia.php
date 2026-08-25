<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function gia_form_types(): array
{
    return [
        'demo_exam' => 'Демонстрационный экзамен',
        'vkr' => 'Выпускная квалификационная работа',
    ];
}

function gia_form_label(string $type): string
{
    return gia_form_types()[$type] ?? $type;
}

function normalize_gia_form_type(string $type): ?string
{
    return array_key_exists($type, gia_form_types()) ? $type : null;
}

function get_student_gia(int $studentId): array
{
    if (!db()->query("SHOW TABLES LIKE 'student_gia'")->fetch()) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT id, student_id, form_type, module_name, code, points, topic, defense_date, grade, sort_order
         FROM student_gia
         WHERE student_id = ?
         ORDER BY FIELD(form_type, \'demo_exam\', \'vkr\'), sort_order ASC, id ASC'
    );
    $stmt->execute([$studentId]);

    return $stmt->fetchAll();
}

function get_expelled_gia(int $expelledId): array
{
    if (!db()->query("SHOW TABLES LIKE 'expelled_gia'")->fetch()) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT id, expelled_id, form_type, module_name, code, points, topic, defense_date, grade, sort_order
         FROM expelled_gia
         WHERE expelled_id = ?
         ORDER BY FIELD(form_type, \'demo_exam\', \'vkr\'), sort_order ASC, id ASC'
    );
    $stmt->execute([$expelledId]);

    return $stmt->fetchAll();
}

function get_student_gia_entry(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM student_gia WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function split_gia_entries(array $entries): array
{
    $demo = [];
    $vkr = [];
    foreach ($entries as $row) {
        if (($row['form_type'] ?? '') === 'vkr') {
            $vkr[] = $row;
        } else {
            $demo[] = $row;
        }
    }

    return ['demo_exam' => $demo, 'vkr' => $vkr];
}

function normalize_gia_grade($grade): ?int
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

function normalize_gia_points($points): ?float
{
    if ($points === null || $points === '') {
        return null;
    }
    if (!is_numeric($points)) {
        return null;
    }
    $value = round((float) $points, 2);
    if ($value < 0) {
        return null;
    }

    return $value;
}

function normalize_gia_date(string $date): ?string
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

function format_gia_defense_date(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }
    $ts = strtotime($date);

    return $ts ? date('d.m.Y', $ts) : '—';
}

function save_student_gia(int $studentId, array $data, int $id = 0): array
{
    $formType = normalize_gia_form_type((string) ($data['form_type'] ?? ''));
    if ($formType === null) {
        return ['success' => false, 'error' => 'Выберите форму итоговой аттестации.'];
    }

    $grade = normalize_gia_grade($data['grade'] ?? null);
    if (($data['grade'] ?? '') !== '' && $grade === null) {
        return ['success' => false, 'error' => 'Оценка должна быть от 2 до 5.'];
    }

    $moduleName = '';
    $code = '';
    $points = null;
    $topic = '';
    $defenseDate = null;

    if ($formType === 'demo_exam') {
        $moduleName = trim((string) ($data['module_name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $points = normalize_gia_points($data['points'] ?? null);
        if ($moduleName === '') {
            return ['success' => false, 'error' => 'Укажите наименование ПМ.'];
        }
        if (($data['points'] ?? '') !== '' && $points === null) {
            return ['success' => false, 'error' => 'Некорректное количество баллов.'];
        }
    } else {
        $topic = trim((string) ($data['topic'] ?? ''));
        $defenseDate = normalize_gia_date((string) ($data['defense_date'] ?? ''));
        if ($topic === '') {
            return ['success' => false, 'error' => 'Укажите тему выпускной квалификационной работы.'];
        }
        if (($data['defense_date'] ?? '') !== '' && $defenseDate === null) {
            return ['success' => false, 'error' => 'Некорректная дата защиты.'];
        }
    }

    if ($id > 0) {
        $existing = get_student_gia_entry($id);
        if ($existing === null || (int) $existing['student_id'] !== $studentId) {
            return ['success' => false, 'error' => 'Запись ГИА не найдена.'];
        }
        db()->prepare(
            'UPDATE student_gia
             SET form_type = ?, module_name = ?, code = ?, points = ?, topic = ?, defense_date = ?, grade = ?
             WHERE id = ? AND student_id = ?'
        )->execute([
            $formType, $moduleName, $code, $points, $topic, $defenseDate, $grade, $id, $studentId,
        ]);

        return ['success' => true, 'id' => $id];
    }

    $stmtSort = db()->prepare(
        'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM student_gia WHERE student_id = ? AND form_type = ?'
    );
    $stmtSort->execute([$studentId, $formType]);
    $sort = (int) $stmtSort->fetchColumn();

    db()->prepare(
        'INSERT INTO student_gia
            (student_id, form_type, module_name, code, points, topic, defense_date, grade, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $studentId, $formType, $moduleName, $code, $points, $topic, $defenseDate, $grade, $sort,
    ]);

    return ['success' => true, 'id' => (int) db()->lastInsertId()];
}

function delete_student_gia(int $studentId, int $id): array
{
    $existing = get_student_gia_entry($id);
    if ($existing === null || (int) $existing['student_id'] !== $studentId) {
        return ['success' => false, 'error' => 'Запись ГИА не найдена.'];
    }

    db()->prepare('DELETE FROM student_gia WHERE id = ? AND student_id = ?')
        ->execute([$id, $studentId]);

    return ['success' => true];
}

function copy_gia_to_expelled(int $studentId, int $expelledId): void
{
    if (!db()->query("SHOW TABLES LIKE 'expelled_gia'")->fetch()) {
        return;
    }

    $rows = get_student_gia($studentId);
    if ($rows === []) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO expelled_gia
            (expelled_id, form_type, module_name, code, points, topic, defense_date, grade, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($rows as $row) {
        $stmt->execute([
            $expelledId,
            (string) $row['form_type'],
            (string) ($row['module_name'] ?? ''),
            (string) ($row['code'] ?? ''),
            $row['points'] !== null && $row['points'] !== '' ? (float) $row['points'] : null,
            (string) ($row['topic'] ?? ''),
            $row['defense_date'] ?: null,
            $row['grade'] !== null && $row['grade'] !== '' ? (int) $row['grade'] : null,
            (int) ($row['sort_order'] ?? 1),
        ]);
    }
}

function restore_gia_to_student(int $expelledId, int $studentId): void
{
    if (!db()->query("SHOW TABLES LIKE 'student_gia'")->fetch()) {
        return;
    }

    foreach (get_expelled_gia($expelledId) as $row) {
        save_student_gia($studentId, [
            'form_type' => $row['form_type'],
            'module_name' => $row['module_name'] ?? '',
            'code' => $row['code'] ?? '',
            'points' => $row['points'],
            'topic' => $row['topic'] ?? '',
            'defense_date' => (string) ($row['defense_date'] ?? ''),
            'grade' => $row['grade'],
        ]);
    }
}
