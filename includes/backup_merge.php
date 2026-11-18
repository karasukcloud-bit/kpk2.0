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

function backup_merge_normalize_name(string $name): string
{
    $name = preg_replace('/\s+/u', ' ', trim($name)) ?? '';

    return mb_strtolower($name, 'UTF-8');
}

/**
 * Сопоставление ФИО: точное, или live = начало backup (без отчества).
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
    if (str_starts_with($bak, $live . ' ')) {
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
