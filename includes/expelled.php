<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/organization.php';
require_once __DIR__ . '/students.php';
require_once __DIR__ . '/record_book.php';
require_once __DIR__ . '/courseworks.php';
require_once __DIR__ . '/practices.php';
require_once __DIR__ . '/gia.php';
require_once __DIR__ . '/glaz.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/curriculum.php';

function can_manage_expelled_students(): bool
{
    return is_admin() || can_use_deputy_panel();
}

function require_expelled_manager(): void
{
    require_login();
    if (!can_manage_expelled_students()) {
        http_response_code(403);
        exit('Доступ запрещён. Требуются права администратора или завуча.');
    }
}

function get_expelled_student(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT e.*, u.full_name AS expelled_by_name
         FROM expelled_students e
         LEFT JOIN users u ON u.id = e.expelled_by
         WHERE e.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function list_expelled_students(
    bool $includeRestored = true,
    ?string $academicYear = null,
    ?string $semester = null
): array {
    $sql = 'SELECT e.*, u.full_name AS expelled_by_name
            FROM expelled_students e
            LEFT JOIN users u ON u.id = e.expelled_by';
    $where = [];
    $params = [];

    if (!$includeRestored) {
        $where[] = 'e.is_restored = 0';
    }

    if ($academicYear !== null && $academicYear !== '') {
        $where[] = 'e.expulsion_academic_year = ?';
        $params[] = $academicYear;
        if ($semester !== null && $semester !== '') {
            $where[] = 'e.expulsion_semester = ?';
            $params[] = $semester;
        }
    }

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY e.expulsion_date DESC, e.full_name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function expelled_period_from_date(string $date): array
{
    $ts = strtotime($date);
    if ($ts === false) {
        $period = get_active_gradebook_period();

        return [
            'academic_year' => $period['academic_year'],
            'semester' => $period['semester'],
        ];
    }

    $month = (int) date('n', $ts);
    $year = (int) date('Y');

    if ($month >= 9) {
        return [
            'academic_year' => $year . '-' . ($year + 1),
            'semester' => '1',
        ];
    }

    $startYear = $year - 1;
    $semester = ($month >= 2 && $month <= 6) ? '2' : '1';

    return [
        'academic_year' => $startYear . '-' . $year,
        'semester' => $semester,
    ];
}

function parse_expelled_list_filter(array $query): array
{
    $year = trim((string) ($query['academic_year'] ?? ''));
    $year = $year !== '' ? (normalize_academic_year($year) ?? '') : '';
    $semester = normalize_gradebook_semester((string) ($query['semester'] ?? '1'));

    return [
        'academic_year' => $year,
        'semester' => $semester,
        'show_all_periods' => $year === '',
    ];
}

function get_expelled_academic_year_filter_options(): array
{
    $years = [];

    if (db()->query("SHOW TABLES LIKE 'expelled_students'")->fetch()
        && db()->query("SHOW COLUMNS FROM expelled_students LIKE 'expulsion_academic_year'")->fetch()
    ) {
        $stmt = db()->query(
            "SELECT DISTINCT expulsion_academic_year AS academic_year
             FROM expelled_students
             WHERE expulsion_academic_year IS NOT NULL AND expulsion_academic_year <> ''
             ORDER BY academic_year DESC"
        );
        foreach ($stmt->fetchAll() as $row) {
            $years[] = (string) $row['academic_year'];
        }
    }

    foreach (get_academic_year_options() as $year) {
        $years[] = $year;
    }

    $years = array_values(array_unique($years));
    rsort($years, SORT_STRING);

    return $years;
}

function expelled_period_label(array $row): string
{
    $year = trim((string) ($row['expulsion_academic_year'] ?? ''));
    $semester = trim((string) ($row['expulsion_semester'] ?? ''));

    if ($year === '') {
        return '—';
    }

    return $year . ', ' . $semester . ' семестр';
}

function build_expelled_list_query(array $filter, bool $showRestored): string
{
    $params = [];

    if (!$showRestored) {
        $params['active_only'] = '1';
    }

    if (!$filter['show_all_periods']) {
        $params['academic_year'] = $filter['academic_year'];
        $params['semester'] = $filter['semester'];
    }

    return $params === [] ? '' : ('?' . http_build_query($params));
}

function purge_student_data_completely(int $studentId, ?PDO $pdo = null): void
{
    $pdo = $pdo ?? db();
    $tables = [
        'journal_grades',
        'grade_entries',
        'student_record_book',
        'student_courseworks',
        'student_practices',
        'student_gia',
        'attendance_records',
    ];

    foreach ($tables as $table) {
        if ($pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetch()) {
            $pdo->prepare("DELETE FROM {$table} WHERE student_id = ?")->execute([$studentId]);
        }
    }

    if ($pdo->query("SHOW TABLES LIKE 'glaz_schedules'")->fetch()) {
        $scheduleIds = $pdo->prepare('SELECT id FROM glaz_schedules WHERE student_id = ?');
        $scheduleIds->execute([$studentId]);
        $ids = $scheduleIds->fetchAll(PDO::FETCH_COLUMN);
        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            if ($pdo->query("SHOW TABLES LIKE 'glaz_commission_members'")->fetch()) {
                $pdo->prepare(
                    "DELETE FROM glaz_commission_members WHERE schedule_id IN ($placeholders)"
                )->execute($ids);
            }
        }
        $pdo->prepare('DELETE FROM glaz_schedules WHERE student_id = ?')->execute([$studentId]);
    }

    $student = get_student_by_id($studentId);
    $userId = $student ? (int) ($student['user_id'] ?? 0) : 0;

    $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([$studentId]);

    if ($userId > 0) {
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'")->execute([$userId]);
    }
}

function delete_expelled_student(int $expelledId): array
{
    if (!can_manage_expelled_students()) {
        return ['success' => false, 'error' => 'Недостаточно прав для удаления.'];
    }

    $expelled = get_expelled_student($expelledId);
    if ($expelled === null) {
        return ['success' => false, 'error' => 'Запись об отчислении не найдена.'];
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $restoredStudentId = (int) ($expelled['restored_student_id'] ?? 0);

        $pdo->prepare('DELETE FROM expelled_students WHERE id = ?')->execute([$expelledId]);

        if ($restoredStudentId > 0 && get_student_by_id($restoredStudentId) !== null) {
            purge_student_data_completely($restoredStudentId, $pdo);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();

        return ['success' => false, 'error' => 'Не удалось удалить отчисленного студента.'];
    }

    return ['success' => true];
}

function get_expelled_record_book(int $expelledId): array
{
    $stmt = db()->prepare(
        'SELECT academic_year, semester, curriculum_item_id, subject_name, teacher_name, attestation_form, grade
         FROM expelled_record_book
         WHERE expelled_id = ?
         ORDER BY academic_year DESC, semester DESC, subject_name ASC'
    );
    $stmt->execute([$expelledId]);
    $rows = $stmt->fetchAll();

    require_once __DIR__ . '/record_book.php';

    $periods = [];
    foreach ($rows as $row) {
        $itemId = (int) ($row['curriculum_item_id'] ?? 0);
        if (trim((string) ($row['teacher_name'] ?? '')) === '' && $itemId > 0) {
            $teacher = get_teacher_name_by_curriculum_item($itemId);
            if ($teacher !== '') {
                $row['teacher_name'] = $teacher;
            }
        }
        if (trim((string) ($row['attestation_form'] ?? '')) === '' && $itemId > 0) {
            $form = get_attestation_form_by_curriculum_item($itemId);
            if ($form !== '') {
                $row['attestation_form'] = $form;
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

function get_expelled_debts(int $expelledId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM expelled_debts WHERE expelled_id = ?
         ORDER BY academic_year DESC, semester DESC, subject_name ASC'
    );
    $stmt->execute([$expelledId]);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $commission = [];
        if (!empty($row['commission_json'])) {
            $decoded = json_decode((string) $row['commission_json'], true);
            if (is_array($decoded)) {
                $commission = $decoded;
            }
        }
        $rows[] = [
            'subject_name' => (string) $row['subject_name'],
            'group_number' => (string) $row['group_number'],
            'academic_year' => (string) $row['academic_year'],
            'semester' => (string) $row['semester'],
            'period_label' => glaz_period_label((string) $row['academic_year'], (string) $row['semester']),
            'archived_at' => $row['archived_at'] !== null ? (string) $row['archived_at'] : null,
            'liquidation_date' => $row['liquidation_date'] !== null ? (string) $row['liquidation_date'] : '',
            'liquidation_time' => $row['liquidation_time'] !== null
                ? substr((string) $row['liquidation_time'], 0, 5)
                : '',
            'commission' => $commission,
            'commission_label' => implode(', ', array_map(
                static fn (array $m): string => (string) ($m['full_name'] ?? ''),
                $commission
            )),
        ];
    }

    return $rows;
}

function get_expelled_restoration(int $expelledId): ?array
{
    $stmt = db()->prepare(
        'SELECT r.*, u.full_name AS restored_by_name
         FROM expelled_restorations r
         LEFT JOIN users u ON u.id = r.restored_by
         WHERE r.expelled_id = ?
         ORDER BY r.id DESC
         LIMIT 1'
    );
    $stmt->execute([$expelledId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function collect_student_debts_snapshot(int $studentId): array
{
    $debts = [];
    foreach (get_all_academic_debts() as $debt) {
        if ((int) $debt['student_id'] === $studentId) {
            $debts[] = $debt;
        }
    }

    $schedules = get_glaz_schedules_index()['schedules'];
    $result = [];
    foreach ($debts as $debt) {
        $key = (string) $debt['debt_key'];
        $schedule = $schedules[$key] ?? null;
        $result[] = [
            'curriculum_item_id' => (int) $debt['curriculum_item_id'],
            'group_id' => (int) $debt['group_id'],
            'group_number' => (string) $debt['group_number'],
            'subject_name' => (string) $debt['subject_name'],
            'academic_year' => (string) $debt['academic_year'],
            'semester' => (string) $debt['semester'],
            'archived_at' => $debt['archived_at'] ?? null,
            'liquidation_date' => $schedule['liquidation_date'] ?? '',
            'liquidation_time' => $schedule['liquidation_time'] ?? '',
            'commission' => $schedule['commission'] ?? [],
        ];
    }

    return $result;
}

function expel_student(
    int $studentId,
    string $orderNumber,
    string $expulsionDate,
    string $reason
): array {
    $student = get_student_by_id($studentId);
    if ($student === null) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    $orderNumber = trim($orderNumber);
    $reason = trim($reason);
    $expulsionDate = trim($expulsionDate);

    if ($orderNumber === '') {
        return ['success' => false, 'error' => 'Укажите номер приказа об отчислении.'];
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expulsionDate)) {
        return ['success' => false, 'error' => 'Укажите корректную дату отчисления.'];
    }
    if ($reason === '') {
        return ['success' => false, 'error' => 'Укажите причину отчисления.'];
    }

    $group = get_group_by_id((int) $student['group_id']);
    if ($group === null) {
        return ['success' => false, 'error' => 'Группа студента не найдена.'];
    }

    $recordBook = get_student_record_book($studentId);
    $debts = collect_student_debts_snapshot($studentId);
    $actor = current_user();
    $period = get_active_gradebook_period();

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO expelled_students (
                original_student_id, group_id, group_number, specialty_name, specialty_code,
                full_name, phone, birth_date, gender, mother_name, mother_phone, mother_workplace,
                mother_education, father_name, father_phone, father_workplace, father_education,
                address_region, address_district, address_locality, address_street, address_house,
                address_registered, address_actual,
                district, is_low_income, family_type, siblings_under_18, residence_type, is_nonresident,
                without_parental_care,
                expulsion_order, expulsion_date, expulsion_reason,
                expulsion_academic_year, expulsion_semester, expelled_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $studentId,
            (int) $group['id'],
            (string) $group['number'],
            (string) ($group['specialty_name'] ?? ''),
            (string) ($group['specialty_code'] ?? ''),
            (string) $student['full_name'],
            (string) ($student['phone'] ?? ''),
            $student['birth_date'] ?? null,
            $student['gender'] ?? null,
            (string) ($student['mother_name'] ?? ''),
            (string) ($student['mother_phone'] ?? ''),
            (string) ($student['mother_workplace'] ?? ''),
            (string) ($student['mother_education'] ?? ''),
            (string) ($student['father_name'] ?? ''),
            (string) ($student['father_phone'] ?? ''),
            (string) ($student['father_workplace'] ?? ''),
            (string) ($student['father_education'] ?? ''),
            (string) ($student['address_region'] ?? ''),
            (string) ($student['address_district'] ?? ''),
            (string) ($student['address_locality'] ?? ''),
            (string) ($student['address_street'] ?? ''),
            (string) ($student['address_house'] ?? ''),
            (string) ($student['address_registered'] ?? ''),
            (string) ($student['address_actual'] ?? ''),
            (string) ($student['district'] ?? ''),
            !empty($student['is_low_income']) ? 1 : 0,
            $student['family_type'] ?? null,
            (int) ($student['siblings_under_18'] ?? 0),
            $student['residence_type'] ?? null,
            !empty($student['is_nonresident']) ? 1 : 0,
            !empty($student['without_parental_care']) ? 1 : 0,
            $orderNumber,
            $expulsionDate,
            $reason,
            (string) $period['academic_year'],
            (string) $period['semester'],
            $actor ? (int) $actor['id'] : null,
        ]);
        $expelledId = (int) $pdo->lastInsertId();

        $rbStmt = $pdo->prepare(
            'INSERT INTO expelled_record_book
                (expelled_id, academic_year, semester, curriculum_item_id, subject_name, teacher_name, attestation_form, grade)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($recordBook as $period) {
            foreach ($period['entries'] as $entry) {
                $rbStmt->execute([
                    $expelledId,
                    (string) $period['academic_year'],
                    (string) $period['semester'],
                    (int) ($entry['curriculum_item_id'] ?? 0),
                    (string) $entry['subject_name'],
                    (string) ($entry['teacher_name'] ?? ''),
                    (string) ($entry['attestation_form'] ?? ''),
                    $entry['grade'] !== null && $entry['grade'] !== '' ? (int) $entry['grade'] : null,
                ]);
            }
        }

        copy_courseworks_to_expelled($studentId, $expelledId);
        copy_practices_to_expelled($studentId, $expelledId);
        copy_gia_to_expelled($studentId, $expelledId);

        $debtStmt = $pdo->prepare(
            'INSERT INTO expelled_debts (
                expelled_id, curriculum_item_id, group_id, group_number, subject_name,
                academic_year, semester, archived_at, liquidation_date, liquidation_time, commission_json
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($debts as $debt) {
            $liqDate = $debt['liquidation_date'] !== '' ? $debt['liquidation_date'] : null;
            $liqTime = $debt['liquidation_time'] !== '' ? $debt['liquidation_time'] . ':00' : null;
            $debtStmt->execute([
                $expelledId,
                (int) $debt['curriculum_item_id'],
                (int) $debt['group_id'],
                (string) $debt['group_number'],
                (string) $debt['subject_name'],
                (string) $debt['academic_year'],
                (string) $debt['semester'],
                $debt['archived_at'],
                $liqDate,
                $liqTime,
                $debt['commission'] !== [] ? json_encode($debt['commission'], JSON_UNESCAPED_UNICODE) : null,
            ]);
        }

        // Удаляем только из ГЛАЗ (расписания ликвидации). Архивные ведомости и журналы не трогаем.
        if ($pdo->query("SHOW TABLES LIKE 'glaz_schedules'")->fetch()) {
            $scheduleIds = $pdo->prepare('SELECT id FROM glaz_schedules WHERE student_id = ?');
            $scheduleIds->execute([$studentId]);
            $ids = $scheduleIds->fetchAll(PDO::FETCH_COLUMN);
            if ($ids !== []) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                if ($pdo->query("SHOW TABLES LIKE 'glaz_commission_members'")->fetch()) {
                    $pdo->prepare(
                        "DELETE FROM glaz_commission_members WHERE schedule_id IN ($placeholders)"
                    )->execute($ids);
                }
            }
            $pdo->prepare('DELETE FROM glaz_schedules WHERE student_id = ?')->execute([$studentId]);
        }

        $userId = (int) ($student['user_id'] ?? 0);
        $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([$studentId]);
        if ($userId > 0) {
            $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'")->execute([$userId]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Не удалось отчислить студента.'];
    }

    return ['success' => true, 'expelled_id' => $expelledId];
}

function restore_expelled_student(
    int $expelledId,
    string $restoreDate,
    int $groupId,
    string $additionalInfo
): array {
    if (!can_manage_expelled_students()) {
        return ['success' => false, 'error' => 'Недостаточно прав для восстановления.'];
    }

    $expelled = get_expelled_student($expelledId);
    if ($expelled === null) {
        return ['success' => false, 'error' => 'Запись об отчислении не найдена.'];
    }
    if ((int) $expelled['is_restored'] === 1) {
        return ['success' => false, 'error' => 'Студент уже восстановлен.'];
    }

    $restoreDate = trim($restoreDate);
    $additionalInfo = trim($additionalInfo);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $restoreDate)) {
        return ['success' => false, 'error' => 'Укажите корректную дату восстановления.'];
    }

    $group = get_group_by_id($groupId);
    if ($group === null) {
        return ['success' => false, 'error' => 'Выберите группу для восстановления.'];
    }

    $parts = split_person_full_name((string) $expelled['full_name']);
    if ($parts['last_name'] === '') {
        $parts['last_name'] = (string) $expelled['full_name'];
    }
    if ($parts['first_name'] === '') {
        $parts['first_name'] = '—';
    }
    $payload = [
        'last_name' => $parts['last_name'],
        'first_name' => $parts['first_name'],
        'middle_name' => $parts['middle_name'],
        'full_name' => (string) $expelled['full_name'],
        'phone' => (string) ($expelled['phone'] ?? ''),
        'birth_date' => (string) ($expelled['birth_date'] ?? ''),
        'gender' => (string) ($expelled['gender'] ?? ''),
        'mother_name' => (string) ($expelled['mother_name'] ?? ''),
        'mother_phone' => (string) ($expelled['mother_phone'] ?? ''),
        'mother_workplace' => (string) ($expelled['mother_workplace'] ?? ''),
        'mother_education' => (string) ($expelled['mother_education'] ?? ''),
        'father_name' => (string) ($expelled['father_name'] ?? ''),
        'father_phone' => (string) ($expelled['father_phone'] ?? ''),
        'father_workplace' => (string) ($expelled['father_workplace'] ?? ''),
        'father_education' => (string) ($expelled['father_education'] ?? ''),
        'address_region' => (string) ($expelled['address_region'] ?? ''),
        'address_district' => (string) ($expelled['address_district'] ?? ''),
        'address_locality' => (string) ($expelled['address_locality'] ?? ''),
        'address_street' => (string) ($expelled['address_street'] ?? ''),
        'address_house' => (string) ($expelled['address_house'] ?? ''),
        'address_registered' => (string) ($expelled['address_registered'] ?? ''),
        'address_actual' => (string) ($expelled['address_actual'] ?? ''),
        'district' => (string) ($expelled['district'] ?? ''),
        'is_low_income' => !empty($expelled['is_low_income']),
        'without_parental_care' => !empty($expelled['without_parental_care']),
        'family_type' => (string) ($expelled['family_type'] ?? ''),
        'siblings_under_18' => (int) ($expelled['siblings_under_18'] ?? 0),
        'residence_type' => (string) ($expelled['residence_type'] ?? ''),
        'is_nonresident' => !empty($expelled['is_nonresident']),
    ];

    $create = create_student($groupId, $payload);
    if (!$create['success']) {
        return $create;
    }

    $newStudentId = (int) $create['id'];
    $periods = get_expelled_record_book($expelledId);
    foreach ($periods as $period) {
        foreach ($period['entries'] as $entry) {
            upsert_record_book_entry(
                $newStudentId,
                (string) $period['academic_year'],
                (string) $period['semester'],
                (int) ($entry['curriculum_item_id'] ?? 0),
                (string) $entry['subject_name'],
                $entry['grade'] ?? null,
                (string) ($entry['teacher_name'] ?? ''),
                (string) ($entry['attestation_form'] ?? '')
            );
        }
    }

    restore_courseworks_to_student($expelledId, $newStudentId);
    restore_practices_to_student($expelledId, $newStudentId);
    restore_gia_to_student($expelledId, $newStudentId);

    $actor = current_user();
    $pdo = db();
    $pdo->prepare(
        'INSERT INTO expelled_restorations
            (expelled_id, restore_date, group_id, group_number, additional_info, restored_by, new_student_id)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $expelledId,
        $restoreDate,
        $groupId,
        (string) $group['number'],
        $additionalInfo,
        $actor ? (int) $actor['id'] : null,
        $newStudentId,
    ]);

    $pdo->prepare(
        'UPDATE expelled_students
         SET is_restored = 1, restored_at = NOW(), restored_student_id = ?
         WHERE id = ?'
    )->execute([$newStudentId, $expelledId]);

    $curatorId = (int) ($group['curator_id'] ?? 0);
    if ($curatorId > 0) {
        $infoLine = $additionalInfo !== '' ? ("\nДополнительно: " . $additionalInfo) : '';
        notify_user_direct(
            $curatorId,
            'Восстановление студента в группу ' . $group['number'],
            'Студент ' . $expelled['full_name'] . ' восстановлен в вашу группу '
            . $group['number'] . ' с ' . date('d.m.Y', strtotime($restoreDate)) . '.'
            . $infoLine
        );
    }

    return [
        'success' => true,
        'student_id' => $newStudentId,
        'group_id' => $groupId,
    ];
}
