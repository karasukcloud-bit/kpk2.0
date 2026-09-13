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
}

function manual_brs_is_enabled(): bool
{
    return is_file(__DIR__ . '/bootstrap.php');
}

function manual_brs_asset_url(string $file, string $basePath = '../'): string
{
    return $basePath . 'modules/manual_brs/assets/' . ltrim($file, '/');
}
