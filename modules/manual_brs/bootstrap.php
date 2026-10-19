<?php

declare(strict_types=1);

/**
 * Временный модуль ручного БРС.
 * Точка подключения: teacher/manual_brs.php + хуки с file_exists.
 */

require_once __DIR__ . '/includes/periods.php';
require_once __DIR__ . '/includes/calculate.php';
require_once __DIR__ . '/includes/data.php';

function manual_brs_ensure_schema(?PDO $pdo = null): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo = $pdo ?? db();

    if (!$pdo->query("SHOW TABLES LIKE 'manual_brs_sheets'")->fetch()) {
        $pdo->exec(
            "CREATE TABLE manual_brs_sheets (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                academic_year VARCHAR(9) NOT NULL,
                period TINYINT UNSIGNED NOT NULL,
                group_id INT UNSIGNED NOT NULL,
                curriculum_item_id INT UNSIGNED NOT NULL,
                lessons_total SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                updated_by INT UNSIGNED NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_manual_brs_sheet (academic_year, period, curriculum_item_id),
                KEY idx_manual_brs_sheet_group (group_id, academic_year, period),
                CONSTRAINT fk_manual_brs_sheet_group
                    FOREIGN KEY (group_id) REFERENCES study_groups(id) ON DELETE CASCADE,
                CONSTRAINT fk_manual_brs_sheet_item
                    FOREIGN KEY (curriculum_item_id) REFERENCES curriculum_items(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    if (!$pdo->query("SHOW TABLES LIKE 'manual_brs_entries'")->fetch()) {
        $pdo->exec(
            "CREATE TABLE manual_brs_entries (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                sheet_id INT UNSIGNED NOT NULL,
                student_id INT UNSIGNED NOT NULL,
                current_marks TEXT NOT NULL,
                control_marks TEXT NOT NULL,
                absent_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                late_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                activity_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                current_avg DECIMAL(4,2) NULL,
                control_avg DECIMAL(4,2) NULL,
                points DECIMAL(5,1) NULL,
                grade TINYINT UNSIGNED NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_manual_brs_entry (sheet_id, student_id),
                KEY idx_manual_brs_entry_student (student_id),
                CONSTRAINT fk_manual_brs_entry_sheet
                    FOREIGN KEY (sheet_id) REFERENCES manual_brs_sheets(id) ON DELETE CASCADE,
                CONSTRAINT fk_manual_brs_entry_student
                    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    if (!$pdo->query("SHOW TABLES LIKE 'manual_brs_attestations'")->fetch()) {
        $pdo->exec(
            "CREATE TABLE manual_brs_attestations (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                academic_year VARCHAR(9) NOT NULL,
                semester ENUM('1', '2') NOT NULL,
                curriculum_item_id INT UNSIGNED NOT NULL,
                student_id INT UNSIGNED NOT NULL,
                grade TINYINT UNSIGNED NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_manual_brs_pa (academic_year, semester, curriculum_item_id, student_id),
                KEY idx_manual_brs_pa_student (student_id),
                CONSTRAINT fk_manual_brs_pa_item
                    FOREIGN KEY (curriculum_item_id) REFERENCES curriculum_items(id) ON DELETE CASCADE,
                CONSTRAINT fk_manual_brs_pa_student
                    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    try {
        manual_brs_migrate_legacy_quarter_periods($pdo);
    } catch (Throwable $e) {
        // Не валим вход в систему из‑за миграции периодов
        error_log('manual_brs_migrate_legacy_quarter_periods: ' . $e->getMessage());
    }
    manual_brs_ensure_entry_stages_schema($pdo);
}

/** Два этапа семестра в одной записи БРС. */
function manual_brs_ensure_entry_stages_schema(PDO $pdo): void
{
    if (!$pdo->query("SHOW TABLES LIKE 'manual_brs_entries'")->fetch()) {
        return;
    }

    $columns = [
        'stage1_current_marks' => 'TEXT NULL AFTER current_marks',
        'stage1_control_marks' => 'TEXT NULL AFTER stage1_current_marks',
        'stage1_absent_count' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER stage1_control_marks',
        'stage1_late_count' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER stage1_absent_count',
        'stage1_activity_count' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER stage1_late_count',
        'stage2_current_marks' => 'TEXT NULL AFTER stage1_activity_count',
        'stage2_control_marks' => 'TEXT NULL AFTER stage2_current_marks',
        'stage2_absent_count' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER stage2_control_marks',
        'stage2_late_count' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER stage2_absent_count',
        'stage2_activity_count' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER stage2_late_count',
    ];

    foreach ($columns as $name => $definition) {
        $exists = $pdo->query("SHOW COLUMNS FROM manual_brs_entries LIKE " . $pdo->quote($name))->fetch();
        if (!$exists) {
            $pdo->exec("ALTER TABLE manual_brs_entries ADD {$name} {$definition}");
        }
    }

    // Старые записи без этапов → этап 1
    $pdo->exec(
        "UPDATE manual_brs_entries
         SET stage1_current_marks = current_marks,
             stage1_control_marks = control_marks,
             stage1_absent_count = absent_count,
             stage1_late_count = late_count,
             stage1_activity_count = activity_count
         WHERE stage1_current_marks IS NULL
           AND stage2_current_marks IS NULL
           AND (
                current_marks <> '' OR control_marks <> ''
                OR absent_count > 0 OR late_count > 0 OR activity_count > 0
           )"
    );
}

function manual_brs_merge_sheet_into(PDO $pdo, int $fromSheetId, int $intoSheetId): void
{
    if ($fromSheetId <= 0 || $intoSheetId <= 0 || $fromSheetId === $intoSheetId) {
        return;
    }

    $move = $pdo->prepare(
        'INSERT INTO manual_brs_entries
         (sheet_id, student_id, current_marks, control_marks, absent_count, late_count,
          activity_count, current_avg, control_avg, points, grade)
         SELECT ?, student_id, current_marks, control_marks, absent_count, late_count,
                activity_count, current_avg, control_avg, points, grade
         FROM manual_brs_entries WHERE sheet_id = ?
         ON DUPLICATE KEY UPDATE
            points = IF(manual_brs_entries.points IS NULL, VALUES(points), manual_brs_entries.points),
            grade = IF(manual_brs_entries.grade IS NULL, VALUES(grade), manual_brs_entries.grade)'
    );
    $move->execute([$intoSheetId, $fromSheetId]);
    $pdo->prepare('DELETE FROM manual_brs_entries WHERE sheet_id = ?')->execute([$fromSheetId]);
    $pdo->prepare('DELETE FROM manual_brs_sheets WHERE id = ?')->execute([$fromSheetId]);
}

function manual_brs_reassign_sheet_period(PDO $pdo, int $sheetId, int $targetPeriod): void
{
    $stmt = $pdo->prepare(
        'SELECT id, academic_year, period, curriculum_item_id
         FROM manual_brs_sheets WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$sheetId]);
    $sheet = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sheet) {
        return;
    }
    if ((int) $sheet['period'] === $targetPeriod) {
        return;
    }

    $find = $pdo->prepare(
        'SELECT id FROM manual_brs_sheets
         WHERE academic_year = ? AND period = ? AND curriculum_item_id = ? AND id <> ?
         ORDER BY id ASC LIMIT 1'
    );
    $find->execute([
        $sheet['academic_year'],
        $targetPeriod,
        (int) $sheet['curriculum_item_id'],
        $sheetId,
    ]);
    $existingId = (int) ($find->fetchColumn() ?: 0);

    if ($existingId > 0) {
        manual_brs_merge_sheet_into($pdo, $sheetId, $existingId);
        return;
    }

    $pdo->prepare('UPDATE manual_brs_sheets SET period = ? WHERE id = ?')
        ->execute([$targetPeriod, $sheetId]);
}

function manual_brs_merge_duplicate_sheets(PDO $pdo): void
{
    $dupes = $pdo->query(
        'SELECT academic_year, period, curriculum_item_id, COUNT(*) AS cnt, MIN(id) AS keep_id
         FROM manual_brs_sheets
         WHERE period IN (1, 2)
         GROUP BY academic_year, period, curriculum_item_id
         HAVING cnt > 1'
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dupes as $dupe) {
        $keepId = (int) $dupe['keep_id'];
        $stmt = $pdo->prepare(
            'SELECT id FROM manual_brs_sheets
             WHERE academic_year = ? AND period = ? AND curriculum_item_id = ? AND id <> ?'
        );
        $stmt->execute([$dupe['academic_year'], $dupe['period'], $dupe['curriculum_item_id'], $keepId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $otherId) {
            manual_brs_merge_sheet_into($pdo, (int) $otherId, $keepId);
        }
    }
}

/** Четверти 1–4 → семестры 1–2 без нарушения UNIQUE. */
function manual_brs_migrate_legacy_quarter_periods(PDO $pdo): void
{
    if (!$pdo->query("SHOW TABLES LIKE 'manual_brs_sheets'")->fetch()) {
        return;
    }

    $max = (int) $pdo->query('SELECT COALESCE(MAX(period), 0) FROM manual_brs_sheets')->fetchColumn();
    if ($max <= 2) {
        manual_brs_merge_duplicate_sheets($pdo);
        return;
    }

    // 1..4 → 11..14, чтобы не конфликтовать с целевыми 1/2
    $pdo->exec('UPDATE manual_brs_sheets SET period = period + 10 WHERE period BETWEEN 1 AND 4');

    $rows = $pdo->query(
        'SELECT id, period FROM manual_brs_sheets
         WHERE period IN (11, 12, 13, 14)
         ORDER BY id ASC'
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $period = (int) $row['period'];
        $target = ($period === 11 || $period === 12) ? 1 : 2;
        manual_brs_reassign_sheet_period($pdo, (int) $row['id'], $target);
    }

    manual_brs_merge_duplicate_sheets($pdo);
}

function manual_brs_is_enabled(): bool
{
    return is_file(__DIR__ . '/bootstrap.php');
}

function manual_brs_asset_url(string $file, string $basePath = '../'): string
{
    return $basePath . 'modules/manual_brs/assets/' . ltrim($file, '/');
}
