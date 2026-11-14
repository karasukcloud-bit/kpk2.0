<?php

declare(strict_types=1);

/**
 * Индивидуальный график обучения (ИГО) на семестр.
 */

function ensure_student_individual_schedules_schema(?PDO $pdo = null): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo = $pdo ?? db();
    if ($pdo->query("SHOW TABLES LIKE 'student_individual_schedules'")->fetch()) {
        return;
    }

    $pdo->exec("
        CREATE TABLE student_individual_schedules (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id      INT UNSIGNED NOT NULL,
            academic_year   VARCHAR(9) NOT NULL,
            semester        ENUM('1', '2') NOT NULL,
            created_by      INT UNSIGNED NULL,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_igo_student_period (student_id, academic_year, semester),
            KEY idx_igo_period (academic_year, semester),
            CONSTRAINT fk_igo_student
                FOREIGN KEY (student_id) REFERENCES students(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_igo_created_by
                FOREIGN KEY (created_by) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function can_manage_individual_schedule(): bool
{
    return can_use_deputy_panel();
}

function normalize_individual_schedule_semester(string $semester): string
{
    return $semester === '2' ? '2' : '1';
}

/**
 * @return array<int, true> student_id => true
 */
function get_group_individual_schedule_ids(
    int $groupId,
    string $academicYear,
    string $semester
): array {
    ensure_student_individual_schedules_schema();
    $semester = normalize_individual_schedule_semester($semester);

    $stmt = db()->prepare(
        'SELECT i.student_id
         FROM student_individual_schedules i
         INNER JOIN students s ON s.id = i.student_id
         WHERE s.group_id = ?
           AND s.removed_at IS NULL
           AND i.academic_year = ?
           AND i.semester = ?'
    );
    $stmt->execute([$groupId, $academicYear, $semester]);

    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $map[(int) $id] = true;
    }

    return $map;
}

function student_has_individual_schedule(
    int $studentId,
    string $academicYear,
    string $semester
): bool {
    ensure_student_individual_schedules_schema();
    $semester = normalize_individual_schedule_semester($semester);

    $stmt = db()->prepare(
        'SELECT 1 FROM student_individual_schedules
         WHERE student_id = ? AND academic_year = ? AND semester = ?
         LIMIT 1'
    );
    $stmt->execute([$studentId, $academicYear, $semester]);

    return (bool) $stmt->fetchColumn();
}

/**
 * @return array{success:bool, error?:string, enabled?:bool}
 */
function set_student_individual_schedule(
    int $studentId,
    string $academicYear,
    string $semester,
    bool $enabled
): array {
    if (!can_manage_individual_schedule()) {
        return ['success' => false, 'error' => 'Недостаточно прав.'];
    }

    $student = get_student_by_id($studentId);
    if ($student === null || is_student_removed($student)) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    $academicYear = trim($academicYear);
    if ($academicYear === '' || !preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
        return ['success' => false, 'error' => 'Некорректный учебный год.'];
    }

    $semester = normalize_individual_schedule_semester($semester);
    ensure_student_individual_schedules_schema();

    $wasEnabled = student_has_individual_schedule($studentId, $academicYear, $semester);
    if ($enabled === $wasEnabled) {
        return ['success' => true, 'enabled' => $enabled];
    }

    $user = current_user();
    $userId = $user ? (int) $user['id'] : null;

    if ($enabled) {
        $stmt = db()->prepare(
            'INSERT INTO student_individual_schedules
             (student_id, academic_year, semester, created_by)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$studentId, $academicYear, $semester, $userId]);
    } else {
        $stmt = db()->prepare(
            'DELETE FROM student_individual_schedules
             WHERE student_id = ? AND academic_year = ? AND semester = ?'
        );
        $stmt->execute([$studentId, $academicYear, $semester]);
    }

    require_once __DIR__ . '/activity_log.php';
    log_activity(
        $enabled ? 'student.igo_enable' : 'student.igo_disable',
        'student',
        $studentId,
        (int) ($student['group_id'] ?? 0) ?: null,
        [
            'module' => 'Ведомость',
            'student_name' => person_last_first_name((string) ($student['full_name'] ?? '')),
            'academic_year' => $academicYear,
            'period_label' => $semester === '2' ? '2 семестр' : '1 семестр',
            'extra' => $enabled ? 'Включён ИГО' : 'Снят ИГО',
        ]
    );

    return ['success' => true, 'enabled' => $enabled];
}

/**
 * Список студентов на ИГО за период.
 *
 * @return list<array<string,mixed>>
 */
function list_individual_schedule_students(
    string $academicYear,
    string $semester,
    int $groupId = 0,
    string $search = ''
): array {
    ensure_student_individual_schedules_schema();
    $semester = normalize_individual_schedule_semester($semester);
    $search = trim($search);

    $sql = 'SELECT i.id AS igo_id,
                   i.student_id,
                   i.academic_year,
                   i.semester,
                   i.created_at,
                   i.created_by,
                   s.full_name,
                   s.group_id,
                   g.number AS group_number,
                   sp.name AS specialty_name,
                   u.full_name AS created_by_name
            FROM student_individual_schedules i
            INNER JOIN students s ON s.id = i.student_id AND s.removed_at IS NULL
            INNER JOIN study_groups g ON g.id = s.group_id
            INNER JOIN specialties sp ON sp.id = g.specialty_id
            LEFT JOIN users u ON u.id = i.created_by
            WHERE i.academic_year = ?
              AND i.semester = ?';
    $params = [$academicYear, $semester];

    if ($groupId > 0) {
        $sql .= ' AND s.group_id = ?';
        $params[] = $groupId;
    }
    if ($search !== '') {
        $sql .= ' AND s.full_name LIKE ?';
        $params[] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY g.number ASC, s.full_name ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    if ($rows === []) {
        return [];
    }

    require_once __DIR__ . '/gradebook.php';

    $byGroup = [];
    foreach ($rows as $row) {
        $gid = (int) $row['group_id'];
        $byGroup[$gid][] = (int) $row['student_id'];
    }

    $gradeStats = [];
    foreach ($byGroup as $gid => $studentIds) {
        $subjects = get_group_curriculum_subjects($gid, $academicYear, $semester);
        $subjectCount = count($subjects);
        $grades = $subjectCount > 0
            ? get_gradebook_grades_by_source(
                $gid,
                $academicYear,
                $semester,
                is_manual_brs_gradebook_active() ? 'manual_brs' : 'journal'
            )
            : [];

        foreach ($studentIds as $studentId) {
            $filled = 0;
            if ($subjectCount > 0) {
                foreach ($subjects as $subject) {
                    $itemId = (int) ($subject['curriculum_item_id'] ?? 0);
                    if ($itemId > 0 && isset($grades[$studentId][$itemId])) {
                        $filled++;
                    }
                }
            }
            $gradeStats[$studentId] = [
                'grades_filled' => $filled,
                'subjects_total' => $subjectCount,
            ];
        }
    }

    foreach ($rows as &$row) {
        $studentId = (int) $row['student_id'];
        $stats = $gradeStats[$studentId] ?? ['grades_filled' => 0, 'subjects_total' => 0];
        $row['grades_filled'] = $stats['grades_filled'];
        $row['subjects_total'] = $stats['subjects_total'];
        $row['display_name'] = person_last_first_name((string) ($row['full_name'] ?? ''));
    }
    unset($row);

    return $rows;
}

/**
 * Подсчёт filled/expected с учётом ИГО: пустые ячейки ИГО не входят в expected.
 *
 * @param list<array<string,mixed>> $students
 * @param list<array<string,mixed>> $subjects
 * @param array<int, array<int, int>> $grades
 * @param array<int, true> $igoIds
 * @return array{filled:int, expected:int}
 */
function gradebook_count_filled_expected(
    array $students,
    array $subjects,
    array $grades,
    array $igoIds = []
): array {
    $filled = 0;
    $expected = 0;

    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $isIgo = isset($igoIds[$studentId]);
        foreach ($subjects as $subject) {
            $itemId = (int) ($subject['curriculum_item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }
            $hasGrade = isset($grades[$studentId][$itemId]);
            if ($isIgo && !$hasGrade) {
                continue;
            }
            $expected++;
            if ($hasGrade) {
                $filled++;
            }
        }
    }

    return ['filled' => $filled, 'expected' => $expected];
}

/**
 * Годовой подсчёт: ИГО применяется к предметам соответствующего семестра.
 *
 * @param list<array<string,mixed>> $students
 * @param list<array<string,mixed>> $subjectsMerged
 * @param array<int, array<int, int>> $grades
 * @param list<array<string,mixed>> $subjects1
 * @param list<array<string,mixed>> $subjects2
 * @param array<int, true> $igo1
 * @param array<int, true> $igo2
 * @return array{filled:int, expected:int}
 */
function gradebook_count_filled_expected_year(
    array $students,
    array $subjectsMerged,
    array $grades,
    array $subjects1,
    array $subjects2,
    array $igo1,
    array $igo2
): array {
    $semByItem = [];
    foreach ($subjects1 as $subject) {
        $id = (int) ($subject['curriculum_item_id'] ?? 0);
        if ($id > 0) {
            $semByItem[$id]['1'] = true;
        }
    }
    foreach ($subjects2 as $subject) {
        $id = (int) ($subject['curriculum_item_id'] ?? 0);
        if ($id > 0) {
            $semByItem[$id]['2'] = true;
        }
    }

    $filled = 0;
    $expected = 0;

    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        foreach ($subjectsMerged as $subject) {
            $itemId = (int) ($subject['curriculum_item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }
            $hasGrade = isset($grades[$studentId][$itemId]);
            $in1 = !empty($semByItem[$itemId]['1']);
            $in2 = !empty($semByItem[$itemId]['2']);
            if ($in1 && !$in2) {
                $isIgo = isset($igo1[$studentId]);
            } elseif ($in2 && !$in1) {
                $isIgo = isset($igo2[$studentId]);
            } else {
                $isIgo = isset($igo1[$studentId]) && isset($igo2[$studentId]);
            }
            if ($isIgo && !$hasGrade) {
                continue;
            }
            $expected++;
            if ($hasGrade) {
                $filled++;
            }
        }
    }

    return ['filled' => $filled, 'expected' => $expected];
}

/**
 * HTML ячейки ФИО + бейдж/кнопка ИГО.
 */
function render_gradebook_igo_student_cell(
    array $student,
    bool $isIgo,
    bool $canToggle,
    string $academicYear,
    string $semester
): void {
    $studentId = (int) ($student['id'] ?? 0);
    $name = person_last_first_name((string) ($student['full_name'] ?? ''));
    ?>
    <td class="gradebook-table__student-col" data-igo-student="<?= $studentId ?>">
        <span class="gradebook-igo-name"><?= e($name) ?></span>
        <?php if ($isIgo): ?>
            <span class="badge badge--igo" title="Индивидуальный график обучения">ИГО</span>
        <?php endif; ?>
        <?php if ($canToggle): ?>
            <button
                type="button"
                class="btn btn--ghost btn--sm gradebook-igo-toggle"
                data-igo-toggle
                data-student-id="<?= $studentId ?>"
                data-year="<?= e($academicYear) ?>"
                data-semester="<?= e(normalize_individual_schedule_semester($semester)) ?>"
                data-enabled="<?= $isIgo ? '1' : '0' ?>"
                title="<?= $isIgo ? 'Снять ИГО' : 'Включить ИГО' ?>"
            ><?= $isIgo ? 'Снять ИГО' : 'ИГО' ?></button>
        <?php endif; ?>
    </td>
    <?php
}

function gradebook_empty_cell_label(bool $isIgo): string
{
    return $isIgo ? 'ИГО' : '—';
}
