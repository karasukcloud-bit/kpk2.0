<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/db_backup.php';

/** Поля профиля студента, которые можно дописать из снимка. */
function backup_merge_student_profile_fields(): array
{
    return [
        'phone',
        'snils',
        'birth_date',
        'gender',
        'mother_name',
        'mother_phone',
        'mother_workplace',
        'mother_education',
        'father_name',
        'father_phone',
        'father_workplace',
        'father_education',
        'address_registered',
        'address_region',
        'address_district',
        'address_locality',
        'address_street',
        'address_house',
        'address_actual',
        'district',
        'is_low_income',
        'family_type',
        'siblings_under_18',
        'residence_type',
        'is_nonresident',
        'without_parental_care',
    ];
}

/** @return array<string,string> */
function backup_merge_field_labels(): array
{
    return [
        'full_name' => 'ФИО',
        'phone' => 'Телефон',
        'snils' => 'СНИЛС',
        'birth_date' => 'Дата рождения',
        'gender' => 'Пол',
        'mother_name' => 'Мать: ФИО',
        'mother_phone' => 'Мать: телефон',
        'mother_workplace' => 'Мать: место работы',
        'mother_education' => 'Мать: образование',
        'father_name' => 'Отец: ФИО',
        'father_phone' => 'Отец: телефон',
        'father_workplace' => 'Отец: место работы',
        'father_education' => 'Отец: образование',
        'address_registered' => 'Адрес по прописке',
        'address_region' => 'Область / край',
        'address_district' => 'Район',
        'address_locality' => 'Населённый пункт',
        'address_street' => 'Улица',
        'address_house' => 'Дом',
        'address_actual' => 'Фактический адрес',
        'district' => 'Район (старое поле)',
        'is_low_income' => 'Малообеспеченная семья',
        'family_type' => 'Состав семьи',
        'siblings_under_18' => 'Братьев/сестёр < 18',
        'residence_type' => 'Место проживания',
        'is_nonresident' => 'Иногородний',
        'without_parental_care' => 'Без попечительства',
    ];
}

function backup_merge_get_backup_student(PDO $bak, int $id): ?array
{
    $stmt = $bak->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * Обновить карточку студента в БД-снимке.
 *
 * @return array{success:bool,error?:string}
 */
function backup_merge_update_backup_student(PDO $bak, int $id, array $data): array
{
    require_once __DIR__ . '/students.php';

    $student = backup_merge_get_backup_student($bak, $id);
    if ($student === null) {
        return ['success' => false, 'error' => 'Студент в снимке не найден.'];
    }

    $check = validate_student_data($data);
    if (!$check['success']) {
        return $check;
    }

    $data = apply_student_auto_address($data);

    $stmt = $bak->prepare(
        'UPDATE students SET
            full_name = ?, phone = ?, snils = ?, birth_date = ?, gender = ?,
            mother_name = ?, mother_phone = ?, mother_workplace = ?, mother_education = ?,
            father_name = ?, father_phone = ?, father_workplace = ?, father_education = ?,
            address_region = ?, address_district = ?, address_locality = ?,
            address_street = ?, address_house = ?,
            address_registered = ?, address_actual = ?,
            district = ?, is_low_income = ?, family_type = ?, siblings_under_18 = ?,
            residence_type = ?, is_nonresident = ?, without_parental_care = ?,
            updated_at = NOW()
         WHERE id = ?'
    );
    $stmt->execute([
        $data['full_name'],
        $data['phone'],
        normalize_student_snils($data['snils'] ?? null),
        normalize_student_birth_date($data['birth_date'] ?? null),
        normalize_student_gender($data['gender'] ?? null),
        $data['mother_name'],
        $data['mother_phone'],
        (string) ($data['mother_workplace'] ?? ''),
        (string) ($data['mother_education'] ?? ''),
        $data['father_name'],
        $data['father_phone'],
        (string) ($data['father_workplace'] ?? ''),
        (string) ($data['father_education'] ?? ''),
        (string) ($data['address_region'] ?? ''),
        (string) ($data['address_district'] ?? ''),
        (string) ($data['address_locality'] ?? ''),
        (string) ($data['address_street'] ?? ''),
        (string) ($data['address_house'] ?? ''),
        $data['address_registered'],
        $data['address_actual'],
        (string) ($data['district'] ?? ''),
        !empty($data['is_low_income']) ? 1 : 0,
        normalize_student_family_type($data['family_type'] ?? null),
        normalize_student_siblings_under_18($data['siblings_under_18'] ?? 0),
        normalize_student_residence_type($data['residence_type'] ?? null),
        !empty($data['is_nonresident']) ? 1 : 0,
        !empty($data['without_parental_care']) ? 1 : 0,
        $id,
    ]);

    return ['success' => true];
}

/**
 * Дописать пустые поля одному live-студенту из строки снимка.
 *
 * @return array{success:bool,error?:string,updated_fields:list<string>}
 */
function backup_merge_apply_one_student_from_backup(array $live, array $backup, bool $alsoFullName = true): array
{
    require_once __DIR__ . '/students.php';

    $liveId = (int) ($live['id'] ?? 0);
    if ($liveId <= 0 || get_student_by_id($liveId) === null) {
        return ['success' => false, 'error' => 'Студент live не найден.', 'updated_fields' => []];
    }

    $pair = backup_merge_match_students([$live], [$backup]);
    if ($pair['matches'] === []) {
        // даже без совпадения ФИО разрешаем ручное заполнение по выбранной паре
        $fields = [];
        foreach (backup_merge_student_profile_fields() as $field) {
            if (backup_merge_is_empty_value($live[$field] ?? null)
                && !backup_merge_is_empty_value($backup[$field] ?? null)) {
                $fields[] = $field;
            }
        }
        if ($alsoFullName) {
            $liveName = trim((string) ($live['full_name'] ?? ''));
            $bakName = trim((string) ($backup['full_name'] ?? ''));
            if ($bakName !== '' && $liveName !== $bakName) {
                $fields[] = 'full_name';
            }
        }
    } else {
        $fields = $pair['matches'][0]['fields_to_fill'];
        if (!$alsoFullName) {
            $fields = array_values(array_filter($fields, static fn(string $f): bool => $f !== 'full_name'));
        }
    }

    $fields = array_values(array_unique($fields));
    if ($fields === []) {
        return ['success' => true, 'updated_fields' => []];
    }

    $sets = [];
    $params = [];
    foreach ($fields as $field) {
        $sets[] = '`' . $field . '` = ?';
        $params[] = $backup[$field] ?? null;
    }
    $sets[] = '`updated_at` = NOW()';
    $params[] = $liveId;

    db()->prepare('UPDATE students SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);

    return ['success' => true, 'updated_fields' => $fields];
}

function backup_merge_normalize_name(string $name): string
{
    $name = preg_replace('/\s+/u', ' ', trim($name)) ?? '';
    $name = mb_strtolower($name, 'UTF-8');
    // е и ё считаем одной буквой при сопоставлении
    $name = str_replace(['ё', 'Ё'], ['е', 'Е'], $name);

    return $name;
}

/**
 * Сопоставление ФИО: точное (с учётом е/ё), или live = начало backup (без отчества).
 */
function backup_merge_names_match(string $liveName, string $backupName): bool
{
    $live = backup_merge_normalize_name($liveName);
    $bak = backup_merge_normalize_name($backupName);

    if ($live === '' || $bak === '') {
        return false;
    }
    if ($live === $bak) {
        return true;
    }
    if (strpos($bak, $live . ' ') === 0) {
        return true;
    }

    $parts = preg_split('/\s+/u', $bak, -1, PREG_SPLIT_NO_EMPTY);
    if (is_array($parts) && count($parts) >= 2) {
        $two = $parts[0] . ' ' . $parts[1];
        if ($two === $live) {
            return true;
        }
    }

    return false;
}

/**
 * @return list<array<string,mixed>>
 */
function backup_merge_live_groups(): array
{
    $stmt = db()->query(
        'SELECT g.id, g.number, g.course, g.specialty_id,
                (SELECT COUNT(*) FROM students s WHERE s.group_id = g.id AND s.removed_at IS NULL) AS student_count
         FROM study_groups g
         ORDER BY g.number'
    );

    return $stmt->fetchAll();
}

/**
 * @return list<array<string,mixed>>
 */
function backup_merge_backup_groups(PDO $bak): array
{
    $stmt = $bak->query(
        'SELECT g.id, g.number, g.course, g.specialty_id,
                (SELECT COUNT(*) FROM students s WHERE s.group_id = g.id AND s.removed_at IS NULL) AS student_count
         FROM study_groups g
         ORDER BY g.number'
    );

    return $stmt->fetchAll();
}

/**
 * @return list<array<string,mixed>>
 */
function backup_merge_live_students_in_group(int $liveGroupId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM students WHERE group_id = ? AND removed_at IS NULL ORDER BY full_name'
    );
    $stmt->execute([$liveGroupId]);

    return $stmt->fetchAll();
}

/**
 * @return list<array<string,mixed>>
 */
function backup_merge_backup_students_in_group(PDO $bak, int $backupGroupId): array
{
    $stmt = $bak->prepare(
        'SELECT * FROM students WHERE group_id = ? AND removed_at IS NULL ORDER BY full_name'
    );
    $stmt->execute([$backupGroupId]);

    return $stmt->fetchAll();
}

/**
 * @param list<array<string,mixed>> $liveStudents
 * @param list<array<string,mixed>> $backupStudents
 * @return array{
 *   matches: list<array{live:array,backup:array,fields_to_fill:list<string>}>,
 *   live_unmatched: list<array>,
 *   backup_unmatched: list<array>
 * }
 */
function backup_merge_match_students(array $liveStudents, array $backupStudents): array
{
    $matches = [];
    $usedBackup = [];

    foreach ($liveStudents as $live) {
        $foundIdx = null;
        foreach ($backupStudents as $idx => $bak) {
            if (isset($usedBackup[$idx])) {
                continue;
            }
            if (backup_merge_names_match((string) $live['full_name'], (string) $bak['full_name'])) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx === null) {
            continue;
        }

        $usedBackup[$foundIdx] = true;
        $bak = $backupStudents[$foundIdx];
        $fields = [];
        foreach (backup_merge_student_profile_fields() as $field) {
            $liveVal = $live[$field] ?? null;
            $bakVal = $bak[$field] ?? null;
            if (backup_merge_is_empty_value($liveVal) && !backup_merge_is_empty_value($bakVal)) {
                $fields[] = $field;
            }
        }

        // Дописать отчество в ФИО, если live короче
        $liveName = trim((string) $live['full_name']);
        $bakName = trim((string) $bak['full_name']);
        if ($bakName !== '' && backup_merge_names_match($liveName, $bakName) && $liveName !== $bakName
            && mb_strlen($bakName, 'UTF-8') > mb_strlen($liveName, 'UTF-8')) {
            $fields[] = 'full_name';
        }

        $matches[] = [
            'live' => $live,
            'backup' => $bak,
            'fields_to_fill' => array_values(array_unique($fields)),
        ];
    }

    $liveMatchedIds = [];
    foreach ($matches as $m) {
        $liveMatchedIds[(int) $m['live']['id']] = true;
    }

    $liveUnmatched = [];
    foreach ($liveStudents as $live) {
        if (!isset($liveMatchedIds[(int) $live['id']])) {
            $liveUnmatched[] = $live;
        }
    }

    $backupUnmatched = [];
    foreach ($backupStudents as $idx => $bak) {
        if (!isset($usedBackup[$idx])) {
            $backupUnmatched[] = $bak;
        }
    }

    return [
        'matches' => $matches,
        'live_unmatched' => $liveUnmatched,
        'backup_unmatched' => $backupUnmatched,
    ];
}

function backup_merge_is_empty_value(mixed $value): bool
{
    if ($value === null) {
        return true;
    }
    if (is_string($value)) {
        return trim($value) === '';
    }
    // 0 для флагов — не «пусто», не перезаписываем нулями из backup если live уже 0
    return false;
}

/**
 * Сводка по всем группам (для главной таблицы).
 *
 * @return list<array<string,mixed>>
 */
function backup_merge_group_summaries(PDO $bak): array
{
    $liveGroups = backup_merge_live_groups();
    $bakGroups = backup_merge_backup_groups($bak);
    $bakByNumber = [];
    foreach ($bakGroups as $g) {
        $bakByNumber[(string) $g['number']] = $g;
    }

    $rows = [];
    foreach ($liveGroups as $liveGroup) {
        $number = (string) $liveGroup['number'];
        $bakGroup = $bakByNumber[$number] ?? null;
        $row = [
            'number' => $number,
            'live_group_id' => (int) $liveGroup['id'],
            'backup_group_id' => $bakGroup ? (int) $bakGroup['id'] : null,
            'live_count' => (int) $liveGroup['student_count'],
            'backup_count' => $bakGroup ? (int) $bakGroup['student_count'] : 0,
            'matched' => 0,
            'with_fields' => 0,
            'live_unmatched' => (int) $liveGroup['student_count'],
            'backup_unmatched' => $bakGroup ? (int) $bakGroup['student_count'] : 0,
        ];

        if ($bakGroup) {
            $liveStudents = backup_merge_live_students_in_group((int) $liveGroup['id']);
            $bakStudents = backup_merge_backup_students_in_group($bak, (int) $bakGroup['id']);
            $match = backup_merge_match_students($liveStudents, $bakStudents);
            $row['matched'] = count($match['matches']);
            $row['with_fields'] = count(array_filter(
                $match['matches'],
                static fn(array $m): bool => $m['fields_to_fill'] !== []
            ));
            $row['live_unmatched'] = count($match['live_unmatched']);
            $row['backup_unmatched'] = count($match['backup_unmatched']);
        }

        $rows[] = $row;
        unset($bakByNumber[$number]);
    }

    foreach ($bakByNumber as $number => $bakGroup) {
        $rows[] = [
            'number' => (string) $number,
            'live_group_id' => null,
            'backup_group_id' => (int) $bakGroup['id'],
            'live_count' => 0,
            'backup_count' => (int) $bakGroup['student_count'],
            'matched' => 0,
            'with_fields' => 0,
            'live_unmatched' => 0,
            'backup_unmatched' => (int) $bakGroup['student_count'],
        ];
    }

    usort($rows, static fn(array $a, array $b): int => strnatcmp((string) $a['number'], (string) $b['number']));

    return $rows;
}

/**
 * Дописать пустые поля профиля у сопоставленных студентов группы.
 *
 * @return array{success:bool,error?:string,updated:int,skipped:int,details:list<string>}
 */
function backup_merge_apply_students_for_group(PDO $bak, int $liveGroupId): array
{
    $stmt = db()->prepare('SELECT id, number FROM study_groups WHERE id = ?');
    $stmt->execute([$liveGroupId]);
    $liveGroup = $stmt->fetch();
    if (!$liveGroup) {
        return ['success' => false, 'error' => 'Группа не найдена в live.', 'updated' => 0, 'skipped' => 0, 'details' => []];
    }

    $stmt = $bak->prepare('SELECT id, number FROM study_groups WHERE number = ?');
    $stmt->execute([(string) $liveGroup['number']]);
    $bakGroup = $stmt->fetch();
    if (!$bakGroup) {
        return ['success' => false, 'error' => 'Группы с таким номером нет в снимке.', 'updated' => 0, 'skipped' => 0, 'details' => []];
    }

    $liveStudents = backup_merge_live_students_in_group($liveGroupId);
    $bakStudents = backup_merge_backup_students_in_group($bak, (int) $bakGroup['id']);
    $match = backup_merge_match_students($liveStudents, $bakStudents);

    $updated = 0;
    $skipped = 0;
    $details = [];
    $pdo = db();

    foreach ($match['matches'] as $pair) {
        $fields = $pair['fields_to_fill'];
        if ($fields === []) {
            $skipped++;
            continue;
        }

        $sets = [];
        $params = [];
        foreach ($fields as $field) {
            $sets[] = '`' . $field . '` = ?';
            $params[] = $pair['backup'][$field];
        }
        $sets[] = '`updated_at` = NOW()';
        $params[] = (int) $pair['live']['id'];

        $sql = 'UPDATE students SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $upd = $pdo->prepare($sql);
        $upd->execute($params);
        $updated++;
        $details[] = (string) $pair['live']['full_name'] . ' ← ' . implode(', ', $fields);
    }

    return [
        'success' => true,
        'updated' => $updated,
        'skipped' => $skipped,
        'details' => $details,
    ];
}

/**
 * Импорт пропусков сен–окт из снимка в live для одной группы (по ФИО).
 *
 * @return array{success:bool,error?:string,days:int,entries:int,skipped_entries:int}
 */
function backup_merge_apply_attendance_for_group(
    PDO $bak,
    int $liveGroupId,
    string $fromDate = '2026-09-01',
    string $toDateExclusive = '2026-11-01'
): array {
    $stmt = db()->prepare('SELECT id, number FROM study_groups WHERE id = ?');
    $stmt->execute([$liveGroupId]);
    $liveGroup = $stmt->fetch();
    if (!$liveGroup) {
        return ['success' => false, 'error' => 'Группа не найдена в live.', 'days' => 0, 'entries' => 0, 'skipped_entries' => 0];
    }

    $stmt = $bak->prepare('SELECT id, number FROM study_groups WHERE number = ?');
    $stmt->execute([(string) $liveGroup['number']]);
    $bakGroup = $stmt->fetch();
    if (!$bakGroup) {
        return ['success' => false, 'error' => 'Группы нет в снимке.', 'days' => 0, 'entries' => 0, 'skipped_entries' => 0];
    }

    $liveStudents = backup_merge_live_students_in_group($liveGroupId);
    $bakStudents = backup_merge_backup_students_in_group($bak, (int) $bakGroup['id']);
    $match = backup_merge_match_students($liveStudents, $bakStudents);
    $bakToLive = [];
    foreach ($match['matches'] as $pair) {
        $bakToLive[(int) $pair['backup']['id']] = (int) $pair['live']['id'];
    }

    if ($bakToLive === []) {
        return [
            'success' => false,
            'error' => 'Нет сопоставленных студентов по ФИО — сначала выровняйте списки и допишите профили.',
            'days' => 0,
            'entries' => 0,
            'skipped_entries' => 0,
        ];
    }

    $dayStmt = $bak->prepare(
        'SELECT * FROM attendance_days
         WHERE group_id = ?
           AND attendance_date >= ?
           AND attendance_date < ?
         ORDER BY attendance_date'
    );
    $dayStmt->execute([(int) $bakGroup['id'], $fromDate, $toDateExclusive]);
    $bakDays = $dayStmt->fetchAll();

    $pdo = db();
    $daysInserted = 0;
    $entriesInserted = 0;
    $skippedEntries = 0;

    $findDay = $pdo->prepare(
        'SELECT id FROM attendance_days
         WHERE group_id = ? AND attendance_date = ? AND academic_year = ?'
    );
    $insertDay = $pdo->prepare(
        'INSERT INTO attendance_days (group_id, attendance_date, academic_year, created_at)
         VALUES (?, ?, ?, NOW())'
    );
    $entryExists = $pdo->prepare(
        'SELECT id FROM attendance_entries WHERE attendance_day_id = ? AND student_id = ?'
    );
    $insertEntry = $pdo->prepare(
        'INSERT INTO attendance_entries
            (attendance_day_id, student_id, student_name, excused_lessons, unexcused_lessons, reason_id, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
    );
    $entriesStmt = $bak->prepare('SELECT * FROM attendance_entries WHERE attendance_day_id = ?');

    $pdo->beginTransaction();
    try {
        foreach ($bakDays as $bakDay) {
            $findDay->execute([
                $liveGroupId,
                $bakDay['attendance_date'],
                $bakDay['academic_year'],
            ]);
            $liveDayId = $findDay->fetchColumn();
            if ($liveDayId === false) {
                $insertDay->execute([
                    $liveGroupId,
                    $bakDay['attendance_date'],
                    $bakDay['academic_year'],
                ]);
                $liveDayId = (int) $pdo->lastInsertId();
                $daysInserted++;
            } else {
                $liveDayId = (int) $liveDayId;
            }

            $entriesStmt->execute([(int) $bakDay['id']]);
            foreach ($entriesStmt->fetchAll() as $entry) {
                $bakStudentId = (int) ($entry['student_id'] ?? 0);
                if ($bakStudentId <= 0 || !isset($bakToLive[$bakStudentId])) {
                    $skippedEntries++;
                    continue;
                }
                $liveStudentId = $bakToLive[$bakStudentId];

                $entryExists->execute([$liveDayId, $liveStudentId]);
                if ($entryExists->fetchColumn() !== false) {
                    $skippedEntries++;
                    continue;
                }

                $reasonId = $entry['reason_id'] !== null ? (int) $entry['reason_id'] : null;
                $insertEntry->execute([
                    $liveDayId,
                    $liveStudentId,
                    (string) ($entry['student_name'] ?? ''),
                    (int) ($entry['excused_lessons'] ?? 0),
                    (int) ($entry['unexcused_lessons'] ?? 0),
                    $reasonId,
                ]);
                $entriesInserted++;
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();

        return [
            'success' => false,
            'error' => 'Ошибка импорта пропусков: ' . $e->getMessage(),
            'days' => 0,
            'entries' => 0,
            'skipped_entries' => 0,
        ];
    }

    return [
        'success' => true,
        'days' => $daysInserted,
        'entries' => $entriesInserted,
        'skipped_entries' => $skippedEntries,
    ];
}

/**
 * Занятость из снимка для студента.
 *
 * @return list<array<string,mixed>>
 */
function backup_merge_backup_activities(PDO $bak, int $backupStudentId): array
{
    try {
        $stmt = $bak->prepare(
            'SELECT activity_type, title, place, sort_order
             FROM student_activities
             WHERE student_id = ?
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$backupStudentId]);

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Превью занятости группы: данные из снимка, сопоставление с live по ФИО.
 *
 * @return array{
 *   rows: list<array<string,mixed>>,
 *   to_import: int,
 *   already_filled: int,
 *   no_backup_data: int,
 *   unmatched: int
 * }
 */
function backup_merge_activities_preview_for_group(PDO $bak, int $liveGroupId): array
{
    require_once __DIR__ . '/student_activities.php';
    ensure_student_activities_schema();

    $stmt = db()->prepare('SELECT id, number FROM study_groups WHERE id = ?');
    $stmt->execute([$liveGroupId]);
    $liveGroup = $stmt->fetch();
    if (!$liveGroup) {
        return [
            'rows' => [],
            'to_import' => 0,
            'already_filled' => 0,
            'no_backup_data' => 0,
            'unmatched' => 0,
        ];
    }

    $stmt = $bak->prepare('SELECT id, number FROM study_groups WHERE number = ?');
    $stmt->execute([(string) $liveGroup['number']]);
    $bakGroup = $stmt->fetch();
    if (!$bakGroup) {
        return [
            'rows' => [],
            'to_import' => 0,
            'already_filled' => 0,
            'no_backup_data' => 0,
            'unmatched' => 0,
        ];
    }

    $liveStudents = backup_merge_live_students_in_group($liveGroupId);
    $bakStudents = backup_merge_backup_students_in_group($bak, (int) $bakGroup['id']);
    $match = backup_merge_match_students($liveStudents, $bakStudents);

    $liveIds = array_map(static function (array $s): int {
        return (int) $s['id'];
    }, $liveStudents);
    $liveMap = get_students_activities_map($liveIds);

    $rows = [];
    $toImport = 0;
    $alreadyFilled = 0;
    $noBackupData = 0;

    foreach ($match['matches'] as $pair) {
        $liveId = (int) $pair['live']['id'];
        $bakId = (int) $pair['backup']['id'];
        $bakActivities = backup_merge_backup_activities($bak, $bakId);
        $liveActivities = $liveMap[$liveId] ?? [];
        $summary = format_student_activities_summary($bakActivities);
        $willImport = $bakActivities !== [] && $liveActivities === [];

        if ($bakActivities === []) {
            $noBackupData++;
            $status = 'no_backup';
        } elseif ($liveActivities !== []) {
            $alreadyFilled++;
            $status = 'already';
        } else {
            $toImport++;
            $status = 'import';
        }

        $rows[] = [
            'live_id' => $liveId,
            'backup_id' => $bakId,
            'live_name' => (string) $pair['live']['full_name'],
            'backup_name' => (string) $pair['backup']['full_name'],
            'backup_activities' => $bakActivities,
            'summary' => $summary,
            'live_has' => $liveActivities !== [],
            'will_import' => $willImport,
            'status' => $status,
        ];
    }

    return [
        'rows' => $rows,
        'to_import' => $toImport,
        'already_filled' => $alreadyFilled,
        'no_backup_data' => $noBackupData,
        'unmatched' => count($match['live_unmatched']) + count($match['backup_unmatched']),
    ];
}

/**
 * Записать занятость из снимка в live по ФИО (только если в live пусто).
 *
 * @return array{success:bool,error?:string,imported:int,skipped:int,activities:int}
 */
function backup_merge_apply_activities_for_group(PDO $bak, int $liveGroupId): array
{
    require_once __DIR__ . '/student_activities.php';
    ensure_student_activities_schema();

    $preview = backup_merge_activities_preview_for_group($bak, $liveGroupId);
    $imported = 0;
    $skipped = 0;
    $activities = 0;

    foreach ($preview['rows'] as $row) {
        if (empty($row['will_import'])) {
            $skipped++;
            continue;
        }
        $items = [];
        foreach ($row['backup_activities'] as $activity) {
            $items[] = [
                'activity_type' => (($activity['activity_type'] ?? '') === 'section') ? 'section' : 'club',
                'title' => (string) ($activity['title'] ?? ''),
                'place' => (string) ($activity['place'] ?? ''),
            ];
        }
        $result = save_student_activities((int) $row['live_id'], $items);
        if ($result['success']) {
            $imported++;
            $activities += (int) ($result['count'] ?? count($items));
        } else {
            $skipped++;
        }
    }

    return [
        'success' => true,
        'imported' => $imported,
        'skipped' => $skipped,
        'activities' => $activities,
    ];
}

/**
 * Карточка дисциплины журнала в снимке.
 *
 * @return array<string,mixed>|null
 */
function backup_merge_get_backup_journal_item(PDO $bak, int $itemId): ?array
{
    if ($itemId <= 0 || !backup_merge_pdo_table_exists($bak, 'curriculum_items')) {
        return null;
    }

    $hasItemType = backup_merge_pdo_column_exists($bak, 'curriculum_items', 'item_type');
    $itemTypeSelect = $hasItemType ? 'ci.item_type' : "'' AS item_type";
    $sql = "SELECT ci.id AS curriculum_item_id,
                   sub.name AS subject_name,
                   ci.semester,
                   {$itemTypeSelect},
                   cp.academic_year,
                   cp.group_id,
                   g.number AS group_number,
                   u.full_name AS teacher_name
            FROM curriculum_items ci
            INNER JOIN curriculum_plans cp ON cp.id = ci.curriculum_plan_id
            INNER JOIN subjects sub ON sub.id = ci.subject_id
            INNER JOIN study_groups g ON g.id = cp.group_id
            LEFT JOIN users u ON u.id = ci.teacher_id
            WHERE ci.id = ?
            LIMIT 1";
    try {
        $stmt = $bak->prepare($sql);
        $stmt->execute([$itemId]);
        $row = $stmt->fetch();

        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Лист журнала из снимка: студенты группы, уроки, оценки, итоги.
 *
 * @return array{
 *   item: array<string,mixed>,
 *   students: list<array<string,mixed>>,
 *   lessons: list<array<string,mixed>>,
 *   grades: array<int, array<int, array{mark:string,activity:int,late:int}>>,
 *   totals: array<int, array<string,mixed>>
 * }|null
 */
function backup_merge_backup_journal_sheet(PDO $bak, int $itemId): ?array
{
    require_once __DIR__ . '/journal.php';
    require_once __DIR__ . '/grading.php';

    $item = backup_merge_get_backup_journal_item($bak, $itemId);
    if ($item === null || !backup_merge_pdo_table_exists($bak, 'journal_lessons')) {
        return null;
    }

    $students = backup_merge_backup_students_in_group($bak, (int) $item['group_id']);
    usort($students, static function (array $a, array $b): int {
        return strnatcasecmp((string) ($a['full_name'] ?? ''), (string) ($b['full_name'] ?? ''));
    });

    $hasGradeType = backup_merge_pdo_column_exists($bak, 'journal_lessons', 'grade_type');
    $hasNote = backup_merge_pdo_column_exists($bak, 'journal_lessons', 'note');
    $hasKtp = backup_merge_pdo_column_exists($bak, 'journal_lessons', 'ktp_topic_id')
        && backup_merge_pdo_table_exists($bak, 'ktp_topics');

    $cols = ['jl.id', 'jl.lesson_date'];
    $cols[] = $hasGradeType ? 'jl.grade_type' : "'current' AS grade_type";
    $cols[] = $hasNote ? 'jl.note' : "'' AS note";
    $join = '';
    if ($hasKtp) {
        $cols[] = 'kt.title AS topic_title';
        $join = 'LEFT JOIN ktp_topics kt ON kt.id = jl.ktp_topic_id';
    } else {
        $cols[] = "'' AS topic_title";
    }

    $lessonsStmt = $bak->prepare(
        'SELECT ' . implode(', ', $cols) . '
         FROM journal_lessons jl
         ' . $join . '
         WHERE jl.curriculum_item_id = ?
         ORDER BY jl.lesson_date ASC, jl.id ASC'
    );
    $lessonsStmt->execute([$itemId]);
    $lessons = $lessonsStmt->fetchAll();

    $grades = [];
    if ($lessons !== [] && backup_merge_pdo_table_exists($bak, 'journal_grades')) {
        $hasMark = backup_merge_pdo_column_exists($bak, 'journal_grades', 'mark');
        $hasGrade = backup_merge_pdo_column_exists($bak, 'journal_grades', 'grade');
        $hasActivity = backup_merge_pdo_column_exists($bak, 'journal_grades', 'activity');
        $hasLate = backup_merge_pdo_column_exists($bak, 'journal_grades', 'late');
        $markExpr = $hasMark ? 'jg.mark' : ($hasGrade ? 'CAST(jg.grade AS CHAR)' : "''");
        $gradesStmt = $bak->prepare(
            "SELECT jg.lesson_id, jg.student_id, {$markExpr} AS mark,
                    " . ($hasActivity ? 'jg.activity' : '0') . ' AS activity,
                    ' . ($hasLate ? 'jg.late' : '0') . ' AS late
             FROM journal_grades jg
             INNER JOIN journal_lessons jl ON jl.id = jg.lesson_id
             WHERE jl.curriculum_item_id = ?'
        );
        $gradesStmt->execute([$itemId]);
        foreach ($gradesStmt->fetchAll() as $row) {
            $sid = (int) $row['student_id'];
            $lid = (int) $row['lesson_id'];
            $grades[$sid][$lid] = [
                'mark' => (string) ($row['mark'] ?? ''),
                'activity' => (int) ($row['activity'] ?? 0) ? 1 : 0,
                'late' => (int) ($row['late'] ?? 0) ? 1 : 0,
            ];
        }
    }

    $totals = build_journal_totals($students, $lessons, $grades);

    return [
        'item' => $item,
        'students' => $students,
        'lessons' => $lessons,
        'grades' => $grades,
        'totals' => $totals,
    ];
}

function backup_merge_pdo_table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));

        return (bool) $stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

function backup_merge_pdo_column_exists(PDO $pdo, string $table, string $column): bool
{
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '` LIKE ' . $pdo->quote($column));

        return (bool) $stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

function backup_merge_journal_subject_key(string $academicYear, string $semester, string $subjectName, string $itemType = ''): string
{
    $name = mb_strtolower(trim($subjectName), 'UTF-8');
    $type = mb_strtolower(trim($itemType), 'UTF-8');

    return $academicYear . '|' . $semester . '|' . $name . '|' . $type;
}

/**
 * Дисциплины группы с уроками электронного журнала.
 *
 * @return list<array<string,mixed>>
 */
function backup_merge_journal_items_for_group(PDO $pdo, int $groupId): array
{
    if ($groupId <= 0
        || !backup_merge_pdo_table_exists($pdo, 'journal_lessons')
        || !backup_merge_pdo_table_exists($pdo, 'curriculum_items')
    ) {
        return [];
    }

    $hasItemType = backup_merge_pdo_column_exists($pdo, 'curriculum_items', 'item_type');
    $itemTypeSelect = $hasItemType ? 'ci.item_type' : "'' AS item_type";
    $hasGrades = backup_merge_pdo_table_exists($pdo, 'journal_grades');
    $gradesJoin = $hasGrades
        ? 'LEFT JOIN journal_grades jg ON jg.lesson_id = jl.id'
        : '';
    $gradesCount = $hasGrades ? 'COUNT(jg.id)' : '0';

    $sql = "SELECT ci.id AS curriculum_item_id,
                   sub.name AS subject_name,
                   ci.semester,
                   {$itemTypeSelect},
                   cp.academic_year,
                   COUNT(DISTINCT jl.id) AS lessons_count,
                   {$gradesCount} AS grades_count
            FROM curriculum_items ci
            INNER JOIN curriculum_plans cp ON cp.id = ci.curriculum_plan_id
            INNER JOIN subjects sub ON sub.id = ci.subject_id
            INNER JOIN journal_lessons jl ON jl.curriculum_item_id = ci.id
            {$gradesJoin}
            WHERE cp.group_id = ?
            GROUP BY ci.id, sub.name, ci.semester, cp.academic_year"
        . ($hasItemType ? ', ci.item_type' : '')
        . ' HAVING lessons_count > 0
            ORDER BY cp.academic_year DESC, sub.name ASC, ci.semester ASC';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Карты live curriculum_item_id: полный ключ и без типа.
 *
 * @return array{full: array<string,int>, soft: array<string,int>}
 */
function backup_merge_live_curriculum_map(int $liveGroupId): array
{
    $hasItemType = backup_merge_pdo_column_exists(db(), 'curriculum_items', 'item_type');
    $itemTypeSelect = $hasItemType ? 'ci.item_type' : "'' AS item_type";
    $sql = "SELECT ci.id, sub.name AS subject_name, ci.semester, {$itemTypeSelect}, cp.academic_year
            FROM curriculum_items ci
            INNER JOIN curriculum_plans cp ON cp.id = ci.curriculum_plan_id
            INNER JOIN subjects sub ON sub.id = ci.subject_id
            WHERE cp.group_id = ?";
    $stmt = db()->prepare($sql);
    $stmt->execute([$liveGroupId]);
    $full = [];
    $soft = [];
    foreach ($stmt->fetchAll() as $row) {
        $id = (int) $row['id'];
        $fullKey = backup_merge_journal_subject_key(
            (string) $row['academic_year'],
            (string) $row['semester'],
            (string) $row['subject_name'],
            (string) ($row['item_type'] ?? '')
        );
        $softKey = backup_merge_journal_subject_key(
            (string) $row['academic_year'],
            (string) $row['semester'],
            (string) $row['subject_name'],
            ''
        );
        if (!isset($full[$fullKey])) {
            $full[$fullKey] = $id;
        }
        if (!isset($soft[$softKey])) {
            $soft[$softKey] = $id;
        }
    }

    return ['full' => $full, 'soft' => $soft];
}

/**
 * Превью журнала группы: дисциплины со снимка и сопоставление с live.
 *
 * @return array{
 *   rows: list<array<string,mixed>>,
 *   subjects_with_data: int,
 *   matched: int,
 *   unmatched: int,
 *   bak_lessons: int,
 *   bak_grades: int,
 *   live_lessons: int,
 *   live_grades: int
 * }
 */
function backup_merge_journal_preview_for_group(PDO $bak, int $liveGroupId): array
{
    $empty = [
        'rows' => [],
        'subjects_with_data' => 0,
        'matched' => 0,
        'unmatched' => 0,
        'bak_lessons' => 0,
        'bak_grades' => 0,
        'live_lessons' => 0,
        'live_grades' => 0,
    ];

    $stmt = db()->prepare('SELECT id, number FROM study_groups WHERE id = ?');
    $stmt->execute([$liveGroupId]);
    $liveGroup = $stmt->fetch();
    if (!$liveGroup) {
        return $empty;
    }

    $stmt = $bak->prepare('SELECT id, number FROM study_groups WHERE number = ?');
    $stmt->execute([(string) $liveGroup['number']]);
    $bakGroup = $stmt->fetch();
    if (!$bakGroup) {
        return $empty;
    }

    $bakItems = backup_merge_journal_items_for_group($bak, (int) $bakGroup['id']);
    $liveMap = backup_merge_live_curriculum_map($liveGroupId);
    $liveItemsById = [];
    foreach (backup_merge_journal_items_for_group(db(), $liveGroupId) as $liveItem) {
        $liveItemsById[(int) $liveItem['curriculum_item_id']] = $liveItem;
    }

    $rows = [];
    $matched = 0;
    $unmatched = 0;
    $bakLessons = 0;
    $bakGrades = 0;
    $liveLessons = 0;
    $liveGrades = 0;

    foreach ($bakItems as $item) {
        $fullKey = backup_merge_journal_subject_key(
            (string) $item['academic_year'],
            (string) $item['semester'],
            (string) $item['subject_name'],
            (string) ($item['item_type'] ?? '')
        );
        $softKey = backup_merge_journal_subject_key(
            (string) $item['academic_year'],
            (string) $item['semester'],
            (string) $item['subject_name'],
            ''
        );
        $liveItemId = $liveMap['full'][$fullKey] ?? $liveMap['soft'][$softKey] ?? null;
        $liveItem = $liveItemId !== null ? ($liveItemsById[$liveItemId] ?? null) : null;
        $status = $liveItemId !== null ? 'matched' : 'no_live_item';
        if ($liveItemId !== null) {
            $matched++;
        } else {
            $unmatched++;
        }

        $bLessons = (int) $item['lessons_count'];
        $bGrades = (int) $item['grades_count'];
        $lLessons = $liveItem ? (int) $liveItem['lessons_count'] : 0;
        $lGrades = $liveItem ? (int) $liveItem['grades_count'] : 0;
        $bakLessons += $bLessons;
        $bakGrades += $bGrades;
        $liveLessons += $lLessons;
        $liveGrades += $lGrades;

        $rows[] = [
            'subject_name' => (string) $item['subject_name'],
            'academic_year' => (string) $item['academic_year'],
            'semester' => (string) $item['semester'],
            'item_type' => (string) ($item['item_type'] ?? ''),
            'backup_item_id' => (int) $item['curriculum_item_id'],
            'live_item_id' => $liveItemId,
            'bak_lessons' => $bLessons,
            'bak_grades' => $bGrades,
            'live_lessons' => $lLessons,
            'live_grades' => $lGrades,
            'status' => $status,
            'will_import' => $liveItemId !== null && $bLessons > 0,
        ];
    }

    return [
        'rows' => $rows,
        'subjects_with_data' => count($rows),
        'matched' => $matched,
        'unmatched' => $unmatched,
        'bak_lessons' => $bakLessons,
        'bak_grades' => $bakGrades,
        'live_lessons' => $liveLessons,
        'live_grades' => $liveGrades,
    ];
}

/**
 * Импорт уроков и оценок журнала из снимка в live для группы.
 *
 * @return array{
 *   success:bool,
 *   error?:string,
 *   subjects:int,
 *   lessons:int,
 *   grades:int,
 *   skipped_lessons:int,
 *   skipped_grades:int,
 *   skipped_subjects:int
 * }
 */
function backup_merge_apply_journal_for_group(PDO $bak, int $liveGroupId): array
{
    $fail = static function (string $error): array {
        return [
            'success' => false,
            'error' => $error,
            'subjects' => 0,
            'lessons' => 0,
            'grades' => 0,
            'skipped_lessons' => 0,
            'skipped_grades' => 0,
            'skipped_subjects' => 0,
        ];
    };

    if (!backup_merge_pdo_table_exists($bak, 'journal_lessons')
        || !backup_merge_pdo_table_exists(db(), 'journal_lessons')
    ) {
        return $fail('Таблицы журнала отсутствуют в снимке или в live.');
    }

    $stmt = db()->prepare('SELECT id, number FROM study_groups WHERE id = ?');
    $stmt->execute([$liveGroupId]);
    $liveGroup = $stmt->fetch();
    if (!$liveGroup) {
        return $fail('Группа не найдена в live.');
    }

    $stmt = $bak->prepare('SELECT id, number FROM study_groups WHERE number = ?');
    $stmt->execute([(string) $liveGroup['number']]);
    $bakGroup = $stmt->fetch();
    if (!$bakGroup) {
        return $fail('Группы нет в снимке.');
    }

    $liveStudents = backup_merge_live_students_in_group($liveGroupId);
    $bakStudents = backup_merge_backup_students_in_group($bak, (int) $bakGroup['id']);
    $match = backup_merge_match_students($liveStudents, $bakStudents);
    $bakToLive = [];
    foreach ($match['matches'] as $pair) {
        $bakToLive[(int) $pair['backup']['id']] = (int) $pair['live']['id'];
    }
    if ($bakToLive === []) {
        return $fail('Нет сопоставленных студентов по ФИО — сначала выровняйте списки.');
    }

    $preview = backup_merge_journal_preview_for_group($bak, $liveGroupId);
    if ($preview['rows'] === []) {
        return $fail('В снимке нет уроков электронного журнала по этой группе.');
    }

    $pdo = db();
    $hasGradeType = backup_merge_pdo_column_exists($pdo, 'journal_lessons', 'grade_type');
    $hasBakGradeType = backup_merge_pdo_column_exists($bak, 'journal_lessons', 'grade_type');
    $hasNote = backup_merge_pdo_column_exists($pdo, 'journal_lessons', 'note');
    $hasBakNote = backup_merge_pdo_column_exists($bak, 'journal_lessons', 'note');
    $hasKtp = backup_merge_pdo_column_exists($pdo, 'journal_lessons', 'ktp_topic_id');
    $hasMark = backup_merge_pdo_column_exists($bak, 'journal_grades', 'mark');
    $hasGrade = backup_merge_pdo_column_exists($bak, 'journal_grades', 'grade');
    $hasActivity = backup_merge_pdo_column_exists($bak, 'journal_grades', 'activity');
    $hasLate = backup_merge_pdo_column_exists($bak, 'journal_grades', 'late');
    $hasLiveGrades = backup_merge_pdo_table_exists($pdo, 'journal_grades');

    $bakLessonCols = ['id', 'curriculum_item_id', 'lesson_date'];
    if ($hasBakGradeType) {
        $bakLessonCols[] = 'grade_type';
    }
    if ($hasBakNote) {
        $bakLessonCols[] = 'note';
    }
    $bakLessonsStmt = $bak->prepare(
        'SELECT ' . implode(', ', $bakLessonCols) . '
         FROM journal_lessons
         WHERE curriculum_item_id = ?
         ORDER BY lesson_date ASC, id ASC'
    );

    $bakGradeSelect = ['student_id'];
    if ($hasMark) {
        $bakGradeSelect[] = 'mark';
    } elseif ($hasGrade) {
        $bakGradeSelect[] = 'CAST(grade AS CHAR) AS mark';
    } else {
        $bakGradeSelect[] = "'' AS mark";
    }
    $bakGradeSelect[] = $hasActivity ? 'activity' : '0 AS activity';
    $bakGradeSelect[] = $hasLate ? 'late' : '0 AS late';
    $bakGradesStmt = $hasLiveGrades
        ? $bak->prepare(
            'SELECT ' . implode(', ', $bakGradeSelect) . '
             FROM journal_grades
             WHERE lesson_id = ?'
        )
        : null;

    if ($hasGradeType) {
        $findLiveLesson = $pdo->prepare(
            'SELECT id FROM journal_lessons
             WHERE curriculum_item_id = ? AND lesson_date = ? AND grade_type = ?
             LIMIT 1'
        );
    } else {
        $findLiveLesson = $pdo->prepare(
            'SELECT id FROM journal_lessons
             WHERE curriculum_item_id = ? AND lesson_date = ?
             LIMIT 1'
        );
    }

    if ($hasGradeType && $hasNote && $hasKtp) {
        $insertLesson = $pdo->prepare(
            'INSERT INTO journal_lessons (curriculum_item_id, lesson_date, ktp_topic_id, grade_type, note, created_at)
             VALUES (?, ?, NULL, ?, ?, NOW())'
        );
    } elseif ($hasGradeType && $hasKtp) {
        $insertLesson = $pdo->prepare(
            'INSERT INTO journal_lessons (curriculum_item_id, lesson_date, ktp_topic_id, grade_type, created_at)
             VALUES (?, ?, NULL, ?, NOW())'
        );
    } elseif ($hasGradeType) {
        $insertLesson = $pdo->prepare(
            'INSERT INTO journal_lessons (curriculum_item_id, lesson_date, grade_type, created_at)
             VALUES (?, ?, ?, NOW())'
        );
    } else {
        $insertLesson = $pdo->prepare(
            'INSERT INTO journal_lessons (curriculum_item_id, lesson_date, created_at)
             VALUES (?, ?, NOW())'
        );
    }

    $gradeExists = $hasLiveGrades
        ? $pdo->prepare('SELECT id FROM journal_grades WHERE lesson_id = ? AND student_id = ? LIMIT 1')
        : null;
    $insertGrade = $hasLiveGrades
        ? $pdo->prepare(
            'INSERT INTO journal_grades (lesson_id, student_id, mark, activity, late, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())'
        )
        : null;

    $subjects = 0;
    $lessons = 0;
    $grades = 0;
    $skippedLessons = 0;
    $skippedGrades = 0;
    $skippedSubjects = 0;

    $pdo->beginTransaction();
    try {
        foreach ($preview['rows'] as $row) {
            if (empty($row['will_import']) || empty($row['live_item_id'])) {
                $skippedSubjects++;
                continue;
            }

            $liveItemId = (int) $row['live_item_id'];
            $bakItemId = (int) $row['backup_item_id'];
            $bakLessonsStmt->execute([$bakItemId]);
            $bakLessons = $bakLessonsStmt->fetchAll();
            if ($bakLessons === []) {
                $skippedSubjects++;
                continue;
            }

            $subjects++;
            foreach ($bakLessons as $bakLesson) {
                $date = (string) $bakLesson['lesson_date'];
                $gradeType = (string) ($bakLesson['grade_type'] ?? 'current');
                if (!in_array($gradeType, ['current', 'control', 'attestation'], true)) {
                    $gradeType = 'current';
                }
                $note = (string) ($bakLesson['note'] ?? '');

                if ($hasGradeType) {
                    $findLiveLesson->execute([$liveItemId, $date, $gradeType]);
                } else {
                    $findLiveLesson->execute([$liveItemId, $date]);
                }
                $liveLessonId = $findLiveLesson->fetchColumn();
                if ($liveLessonId === false) {
                    if ($hasGradeType && $hasNote && $hasKtp) {
                        $insertLesson->execute([$liveItemId, $date, $gradeType, $note]);
                    } elseif ($hasGradeType && $hasKtp) {
                        $insertLesson->execute([$liveItemId, $date, $gradeType]);
                    } elseif ($hasGradeType) {
                        $insertLesson->execute([$liveItemId, $date, $gradeType]);
                    } else {
                        $insertLesson->execute([$liveItemId, $date]);
                    }
                    $liveLessonId = (int) $pdo->lastInsertId();
                    $lessons++;
                } else {
                    $liveLessonId = (int) $liveLessonId;
                    $skippedLessons++;
                }

                if ($bakGradesStmt === null || $gradeExists === null || $insertGrade === null) {
                    continue;
                }

                $bakGradesStmt->execute([(int) $bakLesson['id']]);
                foreach ($bakGradesStmt->fetchAll() as $grade) {
                    $bakStudentId = (int) ($grade['student_id'] ?? 0);
                    if ($bakStudentId <= 0 || !isset($bakToLive[$bakStudentId])) {
                        $skippedGrades++;
                        continue;
                    }
                    $liveStudentId = $bakToLive[$bakStudentId];
                    $gradeExists->execute([$liveLessonId, $liveStudentId]);
                    if ($gradeExists->fetchColumn() !== false) {
                        $skippedGrades++;
                        continue;
                    }
                    $mark = trim((string) ($grade['mark'] ?? ''));
                    $activity = (int) ($grade['activity'] ?? 0) ? 1 : 0;
                    $late = (int) ($grade['late'] ?? 0) ? 1 : 0;
                    if ($mark === '' && $activity === 0 && $late === 0) {
                        $skippedGrades++;
                        continue;
                    }
                    $insertGrade->execute([$liveLessonId, $liveStudentId, $mark, $activity, $late]);
                    $grades++;
                }
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();

        return $fail('Ошибка импорта журнала: ' . $e->getMessage());
    }

    return [
        'success' => true,
        'subjects' => $subjects,
        'lessons' => $lessons,
        'grades' => $grades,
        'skipped_lessons' => $skippedLessons,
        'skipped_grades' => $skippedGrades,
        'skipped_subjects' => $skippedSubjects,
    ];
}
