<?php

declare(strict_types=1);

/**
 * Общая логика импорта ведомости в archive_gradebook_*.
 * Студент ищется по ФИО во всей системе; новая карточка создаётся только если ФИО нет.
 */

require_once __DIR__ . '/db.php';

function archive_import_pdo(): PDO
{
    return db();
}

/** @return array{dry_run:bool,allow_create:bool} */
function archive_import_cli_flags(array $argv): array
{
    return [
        'dry_run' => in_array('--dry-run', $argv, true),
        'allow_create' => in_array('--allow-create', $argv, true),
    ];
}

function archive_import_normalize_name(string $name): string
{
    $name = preg_replace('/\s+/u', ' ', trim($name)) ?? '';

    return mb_strtolower($name, 'UTF-8');
}

/**
 * Нормализация оценки.
 * @return array{ok:bool,grade:?int} grade=null — пустая ячейка; Зачтено→5, Не зачтено→2
 */
function archive_import_normalize_grade(mixed $raw): array
{
    if ($raw === null) {
        return ['ok' => true, 'grade' => null];
    }
    if (is_string($raw) && trim($raw) === '') {
        return ['ok' => true, 'grade' => null];
    }
    if (is_int($raw) || is_float($raw) || (is_string($raw) && is_numeric(trim($raw)))) {
        $gi = (int) $raw;
        return ($gi >= 2 && $gi <= 5)
            ? ['ok' => true, 'grade' => $gi]
            : ['ok' => false, 'grade' => null];
    }
    if (!is_string($raw)) {
        return ['ok' => false, 'grade' => null];
    }
    $text = mb_strtolower(trim($raw), 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    if (in_array($text, ['зачтено', 'зачет', 'зачёт', 'зчт', 'з'], true)) {
        return ['ok' => true, 'grade' => 5];
    }
    if (in_array($text, ['не зачтено', 'незачтено', 'не зачет', 'не зачёт', 'нз'], true)) {
        return ['ok' => true, 'grade' => 2];
    }

    return ['ok' => false, 'grade' => null];
}

function archive_import_active_academic_year(PDO $pdo): string
{
    try {
        $stmt = $pdo->prepare(
            "SELECT setting_value FROM app_settings WHERE setting_key = 'active_academic_year' LIMIT 1"
        );
        $stmt->execute();
        $year = trim((string) ($stmt->fetchColumn() ?: ''));
        if (preg_match('/^\d{4}-\d{4}$/', $year)) {
            return $year;
        }
    } catch (Throwable $e) {
        // ignore
    }

    $y = (int) date('Y');
    $m = (int) date('n');

    return $m >= 9 ? $y . '-' . ($y + 1) : ($y - 1) . '-' . $y;
}

/**
 * Импорт прошлого периода: номер группы уже занят новым набором (после перевода 321→421).
 */
function archive_import_is_historical(string $importYear, string $activeYear): bool
{
    return $importYear !== '' && $activeYear !== '' && $importYear < $activeYear;
}

/**
 * Найти студента по ФИО с учётом истории групп.
 *
 * Текущий период: та же группа → иначе активный с большим номером группы.
 * Прошлый период: не брать активных из этой же группы (это уже другой набор);
 * сначала выпускники (removed_at), затем активные в других группах.
 *
 * @return array{id:int,group_id:int,group_number:string,full_name:string,removed:bool}|null
 */
function archive_import_find_student_by_name(
    PDO $pdo,
    string $fullName,
    int $preferGroupId,
    bool $historical = false
): ?array {
    $norm = archive_import_normalize_name($fullName);
    $rows = $pdo->query(
        "SELECT st.id, st.group_id, st.full_name, st.removed_at, g.number AS group_number
         FROM students st
         INNER JOIN study_groups g ON g.id = st.group_id"
    )->fetchAll();

    $matches = [];
    foreach ($rows as $row) {
        if (archive_import_normalize_name((string) $row['full_name']) !== $norm) {
            continue;
        }
        $removed = $row['removed_at'] !== null;
        $sameGroup = (int) $row['group_id'] === $preferGroupId;
        // Прошлый год: активный состав этой группы — другой поток после перевода номера.
        if ($historical && !$removed && $sameGroup) {
            continue;
        }
        $matches[] = $row;
    }
    if ($matches === []) {
        return null;
    }

    usort($matches, static function (array $a, array $b) use ($preferGroupId, $historical): int {
        $aRemoved = $a['removed_at'] !== null ? 1 : 0;
        $bRemoved = $b['removed_at'] !== null ? 1 : 0;
        $aSame = ((int) $a['group_id'] === $preferGroupId) ? 0 : 1;
        $bSame = ((int) $b['group_id'] === $preferGroupId) ? 0 : 1;

        if ($historical) {
            // Сначала выпускники, потом активные в других группах.
            if ($aRemoved !== $bRemoved) {
                return $bRemoved <=> $aRemoved;
            }
            if ($aSame !== $bSame) {
                return $aSame <=> $bSame;
            }

            return ((int) $b['group_number']) <=> ((int) $a['group_number']);
        }

        if ($aRemoved !== $bRemoved) {
            return $aRemoved <=> $bRemoved; // активные раньше
        }
        if ($aSame !== $bSame) {
            return $aSame <=> $bSame;
        }

        return ((int) $b['group_number']) <=> ((int) $a['group_number']);
    });

    $best = $matches[0];

    return [
        'id' => (int) $best['id'],
        'group_id' => (int) $best['group_id'],
        'group_number' => (string) $best['group_number'],
        'full_name' => (string) $best['full_name'],
        'removed' => $best['removed_at'] !== null,
    ];
}

/**
 * @return array{id:int,created:bool,linked_from_group:?string,missing:bool,alumni:bool}
 */
function archive_import_resolve_student(
    PDO $pdo,
    string $fullName,
    int $archiveGroupId,
    bool $dryRun = false,
    bool $allowCreate = true,
    bool $historical = false,
    bool $forceAlumni = false
): array {
    $asAlumni = $historical || $forceAlumni;
    $found = archive_import_find_student_by_name($pdo, $fullName, $archiveGroupId, $historical);
    if ($found !== null) {
        $linked = ((int) $found['group_id'] !== $archiveGroupId)
            ? (string) $found['group_number']
            : null;

        return [
            'id' => $found['id'],
            'created' => false,
            'linked_from_group' => $linked,
            'missing' => false,
            'alumni' => $found['removed'],
        ];
    }

    if ($dryRun || !$allowCreate) {
        return [
            'id' => 0,
            'created' => false,
            'linked_from_group' => null,
            'missing' => true,
            'alumni' => $asAlumni,
        ];
    }

    // Прошлый период / выпуск: карточка не попадает в текущий состав группы.
    $ins = $pdo->prepare(
        'INSERT INTO students (group_id, full_name, phone, snils, mother_name, mother_phone, mother_workplace,
            mother_education, father_name, father_phone, father_workplace, father_education,
            address_registered, address_region, address_district, address_locality, address_street, address_house,
            address_actual, district, removed_at)
         VALUES (?, ?, \'\', \'\', \'\', \'\', \'\', \'\', \'\', \'\', \'\', \'\', \'\', \'\', \'\', \'\', \'\', \'\', \'\', \'\', '
        . ($asAlumni ? 'NOW()' : 'NULL') . ')'
    );
    $ins->execute([$archiveGroupId, $fullName]);

    return [
        'id' => (int) $pdo->lastInsertId(),
        'created' => true,
        'linked_from_group' => null,
        'missing' => false,
        'alumni' => $asAlumni,
    ];
}

/** @return list<string> */
function archive_import_student_id_tables(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT TABLE_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'student_id'
         ORDER BY TABLE_NAME"
    );

    return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Перенести все ссылки с duplicateId на keepId и удалить дубликат.
 */
function archive_import_merge_students(PDO $pdo, int $keepId, int $duplicateId): void
{
    if ($keepId === $duplicateId) {
        return;
    }
    if (!$pdo->query("SELECT id FROM students WHERE id = {$keepId}")->fetch()) {
        throw new RuntimeException("keep student {$keepId} not found");
    }
    if (!$pdo->query("SELECT id FROM students WHERE id = {$duplicateId}")->fetch()) {
        throw new RuntimeException("duplicate student {$duplicateId} not found");
    }

    foreach (archive_import_student_id_tables($pdo) as $table) {
        if ($table === 'archive_gradebook_grades') {
            // UNIQUE (archive_id, student_id, curriculum_item_id)
            $pdo->exec(
                "DELETE d FROM archive_gradebook_grades d
                 INNER JOIN archive_gradebook_grades k
                    ON k.archive_id = d.archive_id
                   AND k.curriculum_item_id = d.curriculum_item_id
                   AND k.student_id = {$keepId}
                 WHERE d.student_id = {$duplicateId}"
            );
        }
        if ($table === 'archive_gradebook_students') {
            $pdo->exec(
                "DELETE d FROM archive_gradebook_students d
                 INNER JOIN archive_gradebook_students k
                    ON k.archive_id = d.archive_id
                   AND k.group_id = d.group_id
                   AND k.student_id = {$keepId}
                 WHERE d.student_id = {$duplicateId}"
            );
        }
        $pdo->exec("UPDATE `{$table}` SET student_id = {$keepId} WHERE student_id = {$duplicateId}");
    }

    $pdo->exec("DELETE FROM students WHERE id = {$duplicateId}");
}

/**
 * Склеить всех активных студентов с одинаковым ФИО (оставить карточку с макс. номером группы).
 *
 * @return list<array{full_name:string,keep:int,removed:list<int>}>
 */
function archive_import_merge_all_fio_duplicates(PDO $pdo): array
{
    $rows = $pdo->query(
        "SELECT st.id, st.full_name, st.group_id, g.number AS group_number
         FROM students st
         INNER JOIN study_groups g ON g.id = st.group_id
         WHERE st.removed_at IS NULL
         ORDER BY st.full_name, CAST(g.number AS UNSIGNED) DESC, st.id ASC"
    )->fetchAll();

    $byName = [];
    foreach ($rows as $row) {
        $key = archive_import_normalize_name((string) $row['full_name']);
        $byName[$key][] = $row;
    }

    $report = [];
    foreach ($byName as $group) {
        if (count($group) < 2) {
            continue;
        }
        $keep = (int) $group[0]['id'];
        $removed = [];
        for ($i = 1, $n = count($group); $i < $n; $i++) {
            $dup = (int) $group[$i]['id'];
            archive_import_merge_students($pdo, $keep, $dup);
            $removed[] = $dup;
        }
        $report[] = [
            'full_name' => (string) $group[0]['full_name'],
            'keep' => $keep,
            'removed' => $removed,
        ];
    }

    return $report;
}

/**
 * @param list<string> $subjectNames
 * @param list<array{0:string,1:list<int>}> $rows [full_name, grades]
 * @return array{
 *   archive_id:int,
 *   group_id:int,
 *   students:list<array<string,mixed>>,
 *   grades:int,
 *   subjects:int,
 *   dry_run:bool,
 *   missing_count:int
 * }
 */
function archive_import_gradebook_sheet(
    PDO $pdo,
    string $groupNumber,
    string $academicYear,
    string $semester,
    array $subjectNames,
    array $rows,
    bool $dryRun = false,
    bool $allowCreate = false,
    bool $forceAlumni = false,
    ?string $specialtyNameOverride = null,
    ?string $specialtyCodeOverride = null,
    ?bool $isGeneralEducationOverride = null
): array {
    foreach ($rows as $row) {
        if (count($row[1]) !== count($subjectNames)) {
            throw new InvalidArgumentException("Grade count mismatch for {$row[0]}");
        }
    }

    $group = $pdo->query(
        "SELECT g.*, s.name AS specialty_name, s.code AS specialty_code
         FROM study_groups g
         LEFT JOIN specialties s ON s.id = g.specialty_id
         WHERE g.number = " . $pdo->quote($groupNumber)
    )->fetch();
    if (!$group) {
        throw new RuntimeException("Group {$groupNumber} not found");
    }
    $groupId = (int) $group['id'];
    $activeYear = archive_import_active_academic_year($pdo);
    $historical = archive_import_is_historical($academicYear, $activeYear);
    $asAlumni = $historical || $forceAlumni;

    if ($dryRun) {
        $studentIds = [];
        $missing = 0;
        foreach ($rows as $i => [$fullName, $grades]) {
            $resolved = archive_import_resolve_student(
                $pdo,
                $fullName,
                $groupId,
                true,
                false,
                $historical,
                $forceAlumni
            );
            if ($resolved['missing']) {
                $missing++;
            }
            $studentIds[] = [
                'id' => $resolved['id'],
                'full_name' => $fullName,
                'grades' => $grades,
                'sort' => $i + 1,
                'created' => false,
                'linked_from_group' => $resolved['linked_from_group'],
                'missing' => $resolved['missing'],
                'alumni' => $resolved['alumni'] || ($resolved['missing'] && $asAlumni),
                'historical' => $historical,
            ];
        }

        return [
            'archive_id' => 0,
            'group_id' => $groupId,
            'students' => $studentIds,
            'grades' => count($rows) * count($subjectNames),
            'subjects' => count($subjectNames),
            'dry_run' => true,
            'missing_count' => $missing,
            'historical' => $historical,
            'active_year' => $activeYear,
        ];
    }

    $pdo->beginTransaction();
    try {
        $studentIds = [];
        $missing = 0;
        foreach ($rows as $i => [$fullName, $grades]) {
            $resolved = archive_import_resolve_student(
                $pdo,
                $fullName,
                $groupId,
                false,
                $allowCreate,
                $historical,
                $forceAlumni
            );
            if ($resolved['missing']) {
                $missing++;
                throw new RuntimeException(
                    "Студент не найден в системе: {$fullName}. "
                    . 'Исправьте ФИО или запустите с --allow-create (на проде для прошлых лет создаст выпускника).'
                );
            }
            $studentIds[] = [
                'id' => $resolved['id'],
                'full_name' => $fullName,
                'grades' => $grades,
                'sort' => $i + 1,
                'created' => $resolved['created'],
                'linked_from_group' => $resolved['linked_from_group'],
                'missing' => false,
                'alumni' => $resolved['alumni'],
                'historical' => $historical,
            ];
        }

        $findSub = $pdo->prepare('SELECT id FROM subjects WHERE name = ? LIMIT 1');
        $insSub = $pdo->prepare('INSERT INTO subjects (name) VALUES (?)');
        $subjectIds = [];
        foreach ($subjectNames as $name) {
            $findSub->execute([$name]);
            $id = (int) ($findSub->fetchColumn() ?: 0);
            if ($id === 0) {
                $insSub->execute([$name]);
                $id = (int) $pdo->lastInsertId();
            }
            $subjectIds[] = $id;
        }

        $planStmt = $pdo->prepare(
            'SELECT id FROM curriculum_plans WHERE group_id = ? AND academic_year = ? LIMIT 1'
        );
        $planStmt->execute([$groupId, $academicYear]);
        $planId = (int) ($planStmt->fetchColumn() ?: 0);
        if ($planId === 0) {
            $pdo->prepare(
                'INSERT INTO curriculum_plans (group_id, academic_year) VALUES (?, ?)'
            )->execute([$groupId, $academicYear]);
            $planId = (int) $pdo->lastInsertId();
        }

        // UNIQUE (curriculum_plan_id, subject_id) — один предмет на план, семестр может быть both.
        $findItem = $pdo->prepare(
            'SELECT ci.id, ci.semester FROM curriculum_items ci
             WHERE ci.curriculum_plan_id = ? AND ci.subject_id = ?
             LIMIT 1'
        );
        $insItem = $pdo->prepare(
            'INSERT INTO curriculum_items
                (curriculum_plan_id, subject_id, item_type, practice_kind, semester, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $updSem = $pdo->prepare(
            'UPDATE curriculum_items SET semester = ? WHERE id = ?'
        );
        $itemIds = [];
        foreach ($subjectNames as $idx => $name) {
            $sid = $subjectIds[$idx];
            $findItem->execute([$planId, $sid]);
            $existing = $findItem->fetch();
            $isUp = str_starts_with($name, 'УП ');
            if ($existing) {
                $itemId = (int) $existing['id'];
                $curSem = (string) $existing['semester'];
                if ($curSem !== $semester && $curSem !== 'both') {
                    $updSem->execute(['both', $itemId]);
                }
            } else {
                $insItem->execute([
                    $planId,
                    $sid,
                    $isUp ? 'practice' : 'subject',
                    $isUp ? 'up' : null,
                    $semester,
                    $idx + 1,
                ]);
                $itemId = (int) $pdo->lastInsertId();
            }
            $itemIds[] = $itemId;
        }

        $archStmt = $pdo->prepare(
            "SELECT id FROM archive_periods
             WHERE archive_type = 'gradebook' AND academic_year = ? AND semester = ?
             LIMIT 1"
        );
        $archStmt->execute([$academicYear, $semester]);
        $archiveId = (int) ($archStmt->fetchColumn() ?: 0);
        if ($archiveId === 0) {
            $pdo->prepare(
                "INSERT INTO archive_periods (archive_type, academic_year, semester, archived_by)
                 VALUES ('gradebook', ?, ?, 1)"
            )->execute([$academicYear, $semester]);
            $archiveId = (int) $pdo->lastInsertId();
        }

        $pdo->prepare(
            'DELETE FROM archive_gradebook_grades WHERE archive_id = ? AND group_id = ?'
        )->execute([$archiveId, $groupId]);
        $pdo->prepare(
            'DELETE FROM archive_gradebook_subjects WHERE archive_id = ? AND group_id = ?'
        )->execute([$archiveId, $groupId]);
        $pdo->prepare(
            'DELETE FROM archive_gradebook_students WHERE archive_id = ? AND group_id = ?'
        )->execute([$archiveId, $groupId]);
        $pdo->prepare(
            'DELETE FROM archive_gradebook_groups WHERE archive_id = ? AND group_id = ?'
        )->execute([$archiveId, $groupId]);

        $specialtyName = $specialtyNameOverride !== null && trim($specialtyNameOverride) !== ''
            ? trim($specialtyNameOverride)
            : (string) ($group['specialty_name'] ?? '');
        $specialtyCode = $specialtyCodeOverride !== null && trim($specialtyCodeOverride) !== ''
            ? trim($specialtyCodeOverride)
            : (string) ($group['specialty_code'] ?? '');
        $isGe = $isGeneralEducationOverride !== null
            ? ($isGeneralEducationOverride ? 1 : 0)
            : (!empty($group['is_general_education']) ? 1 : 0);

        $pdo->prepare(
            'INSERT INTO archive_gradebook_groups
                (archive_id, group_id, group_number, specialty_name, specialty_code, is_general_education, curator_name)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $archiveId,
            $groupId,
            $groupNumber,
            $specialtyName,
            $specialtyCode,
            $isGe,
            '',
        ]);

        $insASt = $pdo->prepare(
            'INSERT INTO archive_gradebook_students (archive_id, group_id, student_id, full_name, sort_order)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($studentIds as $st) {
            $insASt->execute([$archiveId, $groupId, $st['id'], $st['full_name'], $st['sort']]);
        }

        $insASub = $pdo->prepare(
            'INSERT INTO archive_gradebook_subjects
                (archive_id, group_id, curriculum_item_id, subject_name, teacher_name, sort_order)
             VALUES (?, ?, ?, ?, \'\', ?)'
        );
        foreach ($subjectNames as $idx => $name) {
            $insASub->execute([$archiveId, $groupId, $itemIds[$idx], $name, $idx + 1]);
        }

        $insGr = $pdo->prepare(
            'INSERT INTO archive_gradebook_grades
                (archive_id, group_id, student_id, curriculum_item_id, grade)
             VALUES (?, ?, ?, ?, ?)'
        );
        $gradeCount = 0;
        foreach ($studentIds as $st) {
            foreach ($itemIds as $idx => $itemId) {
                $insGr->execute([$archiveId, $groupId, $st['id'], $itemId, $st['grades'][$idx]]);
                $gradeCount++;
            }
        }

        $pdo->commit();

        return [
            'archive_id' => $archiveId,
            'group_id' => $groupId,
            'students' => $studentIds,
            'grades' => $gradeCount,
            'subjects' => count($subjectNames),
            'dry_run' => false,
            'missing_count' => $missing,
            'historical' => $historical,
            'active_year' => $activeYear,
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function archive_import_print_result(array $result, string $label = ''): void
{
    $mode = !empty($result['dry_run']) ? 'DRY-RUN' : 'OK';
    $prefix = $label !== '' ? "[{$label}] " : '';
    $hist = !empty($result['historical']) ? ' historical' : '';
    $active = isset($result['active_year']) ? " active={$result['active_year']}" : '';
    echo "{$prefix}{$mode} archive_id={$result['archive_id']} grades={$result['grades']}"
        . " missing={$result['missing_count']}{$hist}{$active}\n";
    foreach ($result['students'] as $st) {
        if (!empty($st['missing'])) {
            $flag = !empty($st['alumni']) ? 'MISSING→ALUMNI' : 'MISSING';
        } elseif (!empty($st['created'])) {
            $flag = !empty($st['alumni']) ? 'NEW-ALUMNI' : 'NEW';
        } elseif (!empty($st['linked_from_group'])) {
            $flag = 'LINK←' . $st['linked_from_group'];
        } elseif (!empty($st['alumni'])) {
            $flag = 'ALUMNI';
        } else {
            $flag = 'SAME';
        }
        echo "  {$st['id']}\t{$flag}\t{$st['full_name']}\n";
    }
}

/**
 * Разбор одного JSON-листа ведомости.
 *
 * @return array{
 *   academic_year:string,
 *   semester:string,
 *   group:string,
 *   subjects:list<string>,
 *   rows:list<array{0:string,1:list<int>}>
 * }
 */
function archive_import_parse_sheet(array $data, string $source = ''): array
{
    $where = $source !== '' ? " ({$source})" : '';
    foreach (['academic_year', 'semester', 'group', 'subjects', 'students'] as $key) {
        if (!isset($data[$key])) {
            throw new InvalidArgumentException("В JSON нет поля \"{$key}\"{$where}");
        }
    }

    $year = trim((string) $data['academic_year']);
    $semester = trim((string) $data['semester']);
    $group = trim((string) $data['group']);
    if ($year === '' || !in_array($semester, ['1', '2'], true) || $group === '') {
        throw new InvalidArgumentException("Некорректные academic_year/semester/group{$where}");
    }

    $subjects = [];
    foreach ((array) $data['subjects'] as $name) {
        $name = trim((string) $name);
        if ($name !== '') {
            $subjects[] = $name;
        }
    }
    if ($subjects === []) {
        throw new InvalidArgumentException("Пустой список subjects{$where}");
    }

    $rows = [];
    foreach ((array) $data['students'] as $i => $student) {
        if (!is_array($student)) {
            throw new InvalidArgumentException("students[{$i}] должен быть объектом{$where}");
        }
        $name = trim((string) ($student['name'] ?? ''));
        $gradesRaw = $student['grades'] ?? null;
        if ($name === '' || !is_array($gradesRaw)) {
            throw new InvalidArgumentException("students[{$i}]: нужны name и grades[]{$where}");
        }
        $grades = [];
        foreach ($gradesRaw as $g) {
            $parsed = archive_import_normalize_grade($g);
            if (!$parsed['ok']) {
                throw new InvalidArgumentException(
                    "students[{$i}] ({$name}): оценка вне 2..5 / Зачтено{$where}"
                );
            }
            $grades[] = $parsed['grade'];
        }
        if (count($grades) !== count($subjects)) {
            throw new InvalidArgumentException(
                "students[{$i}] ({$name}): оценок " . count($grades)
                . ', предметов ' . count($subjects) . $where
            );
        }
        $rows[] = [$name, $grades];
    }
    if ($rows === []) {
        throw new InvalidArgumentException("Пустой список students{$where}");
    }

    $specialtyName = isset($data['specialty_name']) ? trim((string) $data['specialty_name']) : null;
    $specialtyCode = isset($data['specialty_code']) ? trim((string) $data['specialty_code']) : null;
    $isGe = null;
    if (array_key_exists('is_general_education', $data)) {
        $isGe = !empty($data['is_general_education']);
    }

    return [
        'academic_year' => $year,
        'semester' => $semester,
        'group' => $group,
        'subjects' => $subjects,
        'rows' => $rows,
        // Явно: выпускники (не текущий состав группы). Иначе определяется по году < active.
        'alumni' => !empty($data['alumni']),
        'specialty_name' => ($specialtyName !== null && $specialtyName !== '') ? $specialtyName : null,
        'specialty_code' => ($specialtyCode !== null && $specialtyCode !== '') ? $specialtyCode : null,
        'is_general_education' => $isGe,
    ];
}

/**
 * @return list<array{sheet:array,source:string}>
 */
function archive_import_load_json_string(string $raw, string $source = 'upload.json'): array
{
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Некорректный JSON: ' . $source . ' — ' . json_last_error_msg());
    }

    if (isset($decoded['group']) || isset($decoded['students'])) {
        return [['sheet' => archive_import_parse_sheet($decoded, $source), 'source' => $source]];
    }

    $out = [];
    foreach ($decoded as $i => $item) {
        if (!is_array($item)) {
            throw new InvalidArgumentException("Элемент [{$i}] в {$source} не объект");
        }
        $out[] = [
            'sheet' => archive_import_parse_sheet($item, "{$source}#{$i}"),
            'source' => "{$source}#{$i}",
        ];
    }
    if ($out === []) {
        throw new InvalidArgumentException("Пустой JSON: {$source}");
    }

    return $out;
}

/**
 * @return list<array{sheet:array,source:string}>
 */
function archive_import_load_json_file(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException("Файл не найден: {$path}");
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException("Не удалось прочитать: {$path}");
    }

    return archive_import_load_json_string($raw, basename($path));
}

/**
 * Импорт загруженных JSON (веб-форма).
 *
 * @param array $files типичный $_FILES['files']
 * @return array{success:bool,dry_run:bool,results:list<array>,error?:string}
 */
function archive_import_uploaded_json_files(
    array $files,
    bool $dryRun = true,
    bool $allowCreate = false
): array {
    if (!isset($files['name'], $files['tmp_name'], $files['error']) || !is_array($files['name'])) {
        return ['success' => false, 'dry_run' => $dryRun, 'results' => [], 'error' => 'Файлы не переданы.'];
    }

    $count = count($files['name']);
    if ($count === 0) {
        return ['success' => false, 'dry_run' => $dryRun, 'results' => [], 'error' => 'Выберите хотя бы один JSON-файл.'];
    }
    if ($count > 40) {
        return ['success' => false, 'dry_run' => $dryRun, 'results' => [], 'error' => 'Слишком много файлов (макс. 40 за раз).'];
    }

    $pdo = archive_import_pdo();
    $results = [];
    $failed = 0;

    for ($i = 0; $i < $count; $i++) {
        $name = (string) ($files['name'][$i] ?? 'file.json');
        $tmp = (string) ($files['tmp_name'][$i] ?? '');
        $err = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        $size = (int) ($files['size'][$i] ?? 0);

        if ($err !== UPLOAD_ERR_OK) {
            $results[] = [
                'file' => $name,
                'ok' => false,
                'error' => 'Ошибка загрузки файла (код ' . $err . ').',
            ];
            $failed++;
            continue;
        }
        if ($size <= 0 || $size > 2 * 1024 * 1024) {
            $results[] = [
                'file' => $name,
                'ok' => false,
                'error' => 'Размер файла должен быть от 1 байта до 2 МБ.',
            ];
            $failed++;
            continue;
        }
        if (!preg_match('/\.json$/i', $name)) {
            $results[] = [
                'file' => $name,
                'ok' => false,
                'error' => 'Допустимы только файлы .json.',
            ];
            $failed++;
            continue;
        }
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $results[] = [
                'file' => $name,
                'ok' => false,
                'error' => 'Некорректный временный файл.',
            ];
            $failed++;
            continue;
        }

        $raw = file_get_contents($tmp);
        if ($raw === false) {
            $results[] = ['file' => $name, 'ok' => false, 'error' => 'Не удалось прочитать файл.'];
            $failed++;
            continue;
        }

        try {
            $items = archive_import_load_json_string($raw, $name);
        } catch (Throwable $e) {
            $results[] = ['file' => $name, 'ok' => false, 'error' => $e->getMessage()];
            $failed++;
            continue;
        }

        foreach ($items as $item) {
            $sheet = $item['sheet'];
            $label = $sheet['group'] . ' / ' . $sheet['academic_year'] . ' сем.' . $sheet['semester'];
            try {
                $imported = archive_import_gradebook_sheet(
                    $pdo,
                    $sheet['group'],
                    $sheet['academic_year'],
                    $sheet['semester'],
                    $sheet['subjects'],
                    $sheet['rows'],
                    $dryRun,
                    $allowCreate,
                    !empty($sheet['alumni']),
                    $sheet['specialty_name'] ?? null,
                    $sheet['specialty_code'] ?? null,
                    array_key_exists('is_general_education', $sheet)
                        ? $sheet['is_general_education']
                        : null
                );
                $students = [];
                foreach ($imported['students'] as $st) {
                    if (!empty($st['missing'])) {
                        $flag = 'MISSING';
                    } elseif (!empty($st['created'])) {
                        $flag = !empty($st['alumni']) ? 'NEW-ALUMNI' : 'NEW';
                    } elseif (!empty($st['linked_from_group'])) {
                        $flag = 'LINK←' . $st['linked_from_group'];
                    } elseif (!empty($st['alumni'])) {
                        $flag = 'ALUMNI';
                    } else {
                        $flag = 'SAME';
                    }
                    $students[] = [
                        'id' => (int) $st['id'],
                        'name' => (string) $st['full_name'],
                        'flag' => $flag,
                    ];
                }
                $missingCount = (int) $imported['missing_count'];
                $results[] = [
                    'file' => $name,
                    'ok' => true,
                    'label' => $label,
                    'archive_id' => (int) $imported['archive_id'],
                    'grades' => (int) $imported['grades'],
                    'missing' => $missingCount,
                    'historical' => !empty($imported['historical']),
                    'students' => $students,
                ];
                if ($missingCount > 0) {
                    $failed++;
                }
            } catch (Throwable $e) {
                $results[] = [
                    'file' => $name,
                    'ok' => false,
                    'label' => $label,
                    'error' => $e->getMessage(),
                ];
                $failed++;
            }
        }
    }

    return [
        'success' => $failed === 0,
        'dry_run' => $dryRun,
        'results' => $results,
        'failed' => $failed,
    ];
}

/**
 * @return list<string>
 */
function archive_import_collect_json_paths(array $argv): array
{
    $paths = [];
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--file=')) {
            $paths[] = substr($arg, 7);
        } elseif (str_starts_with($arg, '--dir=')) {
            $dir = substr($arg, 6);
            if (!is_dir($dir)) {
                throw new RuntimeException("Каталог не найден: {$dir}");
            }
            $files = glob(rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '*.json') ?: [];
            sort($files, SORT_NATURAL);
            foreach ($files as $file) {
                $paths[] = $file;
            }
        }
    }

    return array_values(array_unique($paths));
}
