<?php

declare(strict_types=1);

function ensure_user_roles_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $stmt = $pdo->query("SHOW TABLES LIKE 'user_roles'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec("
        CREATE TABLE user_roles (
            user_id INT UNSIGNED NOT NULL,
            role    ENUM('teacher', 'curator', 'deputy', 'educator') NOT NULL,
            PRIMARY KEY (user_id, role),
            CONSTRAINT fk_user_roles_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        INSERT INTO user_roles (user_id, role)
        SELECT id, 'teacher' FROM users WHERE role = 'teacher'
    ");
}

function ensure_organization_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $stmt = $pdo->query("SHOW TABLES LIKE 'organization'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec("
        CREATE TABLE organization (
            id              TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
            name            VARCHAR(500) NOT NULL DEFAULT '',
            address         VARCHAR(500) NOT NULL DEFAULT '',
            phone           VARCHAR(100) NOT NULL DEFAULT '',
            email           VARCHAR(255) NOT NULL DEFAULT '',
            additional_info TEXT NOT NULL,
            updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE specialties (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(255) NOT NULL,
            code       VARCHAR(50) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE study_groups (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            number       VARCHAR(50) NOT NULL UNIQUE,
            specialty_id INT UNSIGNED NOT NULL,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_study_groups_specialty
                FOREIGN KEY (specialty_id) REFERENCES specialties(id)
                ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function run_migrations(PDO $pdo): void
{
    ensure_user_roles_schema($pdo);
    ensure_organization_schema($pdo);
    ensure_curriculum_schema($pdo);
    ensure_students_schema($pdo);
    ensure_gradebook_schema($pdo);
    ensure_attendance_schema($pdo);
    ensure_journal_schema($pdo);
    ensure_journal_brs_schema($pdo);
    ensure_ktp_schema($pdo);
    ensure_user_profile_schema($pdo);
    ensure_archive_schema($pdo);
    ensure_archive_journal_topics_schema($pdo);
    ensure_student_accounts_schema($pdo);
    ensure_notifications_schema($pdo);
    ensure_glaz_schema($pdo);
    ensure_expelled_students_schema($pdo);
    ensure_expelled_period_schema($pdo);
    ensure_student_transfers_schema($pdo);
    ensure_student_social_schema($pdo);
    ensure_activity_logs_schema($pdo);
    ensure_courseworks_schema($pdo);
    ensure_practices_schema($pdo);
    ensure_gia_schema($pdo);
    ensure_study_groups_labels_schema($pdo);
    ensure_ktp_constructor_schema($pdo);
}

function ensure_ktp_constructor_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    if (!$pdo->query("SHOW TABLES LIKE 'ktp_topics'")->fetch()) {
        return;
    }

    $addCol = static function (PDO $pdo, string $name, string $definition): void {
        if ($pdo->query("SHOW COLUMNS FROM ktp_topics LIKE " . $pdo->quote($name))->fetch()) {
            return;
        }
        $pdo->exec('ALTER TABLE ktp_topics ADD ' . $definition);
    };

    $addCol($pdo, 'deadline_date', 'deadline_date DATE NULL DEFAULT NULL AFTER hours');
    $addCol($pdo, 'ok_codes', "ok_codes VARCHAR(255) NOT NULL DEFAULT '' AFTER deadline_date");
    $addCol($pdo, 'pk_codes', "pk_codes VARCHAR(255) NOT NULL DEFAULT '' AFTER ok_codes");
    $addCol(
        $pdo,
        'control_form',
        "control_form ENUM('oral', 'written', 'test', 'practical') NULL DEFAULT NULL AFTER pk_codes"
    );
    $addCol($pdo, 'orientation_hours', 'orientation_hours DECIMAL(4,1) NOT NULL DEFAULT 0 AFTER hours');

    if (!$pdo->query("SHOW TABLES LIKE 'ktp_work_programs'")->fetch()) {
        $pdo->exec("
            CREATE TABLE ktp_work_programs (
                id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                curriculum_item_id INT UNSIGNED NOT NULL,
                original_name      VARCHAR(255) NOT NULL,
                stored_path        VARCHAR(500) NOT NULL,
                uploaded_by        INT UNSIGNED NULL,
                uploaded_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_ktp_wp_item (curriculum_item_id),
                CONSTRAINT fk_ktp_wp_item
                    FOREIGN KEY (curriculum_item_id) REFERENCES curriculum_items(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_ktp_wp_user
                    FOREIGN KEY (uploaded_by) REFERENCES users(id)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    $lessonTypeCol = $pdo->query("SHOW COLUMNS FROM ktp_topics LIKE 'lesson_type'")->fetch();
    if ($lessonTypeCol) {
        $typeDef = (string) ($lessonTypeCol['Type'] ?? '');
        if (stripos($typeDef, 'semester_2') === false) {
            $pdo->exec(
                "ALTER TABLE ktp_topics
                 MODIFY lesson_type ENUM(
                    'lecture', 'practice', 'independent',
                    'diff_credit', 'credit', 'exam', 'control', 'semester_2'
                 ) NOT NULL DEFAULT 'lecture'"
            );
        }
    }
}

function ensure_study_groups_labels_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    if (!$pdo->query("SHOW TABLES LIKE 'study_groups'")->fetch()) {
        return;
    }

    if (!$pdo->query("SHOW COLUMNS FROM study_groups LIKE 'is_professionality'")->fetch()) {
        $pdo->exec(
            'ALTER TABLE study_groups
             ADD is_professionality TINYINT(1) NOT NULL DEFAULT 0 AFTER curator_id'
        );
    }

    if (!$pdo->query("SHOW COLUMNS FROM study_groups LIKE 'is_general_education'")->fetch()) {
        $pdo->exec(
            'ALTER TABLE study_groups
             ADD is_general_education TINYINT(1) NOT NULL DEFAULT 0 AFTER is_professionality'
        );
    }
}

function ensure_activity_logs_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if ($pdo->query("SHOW TABLES LIKE 'activity_logs'")->fetch()) {
        return;
    }

    $pdo->exec("
        CREATE TABLE activity_logs (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id      INT UNSIGNED NULL,
            action       VARCHAR(64) NOT NULL,
            entity_type  VARCHAR(32) NOT NULL DEFAULT '',
            entity_id    INT UNSIGNED NULL,
            group_id     INT UNSIGNED NULL,
            details_json TEXT NULL,
            ip_address   VARCHAR(45) NOT NULL DEFAULT '',
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_activity_logs_created (created_at),
            KEY idx_activity_logs_user (user_id, created_at),
            KEY idx_activity_logs_action (action, created_at),
            KEY idx_activity_logs_entity (entity_type, entity_id),
            CONSTRAINT fk_activity_logs_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function ensure_student_transfers_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if ($pdo->query("SHOW TABLES LIKE 'student_transfers'")->fetch()) {
        return;
    }

    $pdo->exec("
        CREATE TABLE student_transfers (
            id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id         INT UNSIGNED NOT NULL,
            from_group_id      INT UNSIGNED NULL,
            from_group_number  VARCHAR(50) NOT NULL DEFAULT '',
            to_group_id        INT UNSIGNED NULL,
            to_group_number    VARCHAR(50) NOT NULL DEFAULT '',
            additional_info    TEXT NOT NULL,
            transferred_by     INT UNSIGNED NULL,
            transferred_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_student_transfers_student (student_id),
            CONSTRAINT fk_student_transfers_student
                FOREIGN KEY (student_id) REFERENCES students(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_student_transfers_by
                FOREIGN KEY (transferred_by) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function ensure_expelled_students_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!$pdo->query("SHOW TABLES LIKE 'expelled_students'")->fetch()) {
        $pdo->exec("
            CREATE TABLE expelled_students (
                id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                original_student_id   INT UNSIGNED NULL,
                group_id              INT UNSIGNED NULL,
                group_number          VARCHAR(50) NOT NULL DEFAULT '',
                specialty_name        VARCHAR(255) NOT NULL DEFAULT '',
                specialty_code        VARCHAR(50) NOT NULL DEFAULT '',
                full_name             VARCHAR(255) NOT NULL,
                phone                 VARCHAR(50) NOT NULL DEFAULT '',
                birth_date            DATE NULL,
                gender                ENUM('male', 'female') NULL,
                mother_name           VARCHAR(255) NOT NULL DEFAULT '',
                mother_phone          VARCHAR(50) NOT NULL DEFAULT '',
                father_name           VARCHAR(255) NOT NULL DEFAULT '',
                father_phone          VARCHAR(50) NOT NULL DEFAULT '',
                address_registered    VARCHAR(500) NOT NULL DEFAULT '',
                address_actual        VARCHAR(500) NOT NULL DEFAULT '',
                expulsion_order       VARCHAR(100) NOT NULL DEFAULT '',
                expulsion_date        DATE NOT NULL,
                expulsion_reason      TEXT NOT NULL,
                expelled_by           INT UNSIGNED NULL,
                expelled_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                is_restored           TINYINT(1) NOT NULL DEFAULT 0,
                restored_at           DATETIME NULL,
                restored_student_id   INT UNSIGNED NULL,
                KEY idx_expelled_name (full_name),
                KEY idx_expelled_restored (is_restored),
                CONSTRAINT fk_expelled_by
                    FOREIGN KEY (expelled_by) REFERENCES users(id)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    if (!$pdo->query("SHOW TABLES LIKE 'expelled_record_book'")->fetch()) {
        $pdo->exec("
            CREATE TABLE expelled_record_book (
                id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                expelled_id        INT UNSIGNED NOT NULL,
                academic_year      VARCHAR(9) NOT NULL,
                semester           ENUM('1', '2') NOT NULL,
                curriculum_item_id INT UNSIGNED NOT NULL DEFAULT 0,
                subject_name       VARCHAR(255) NOT NULL,
                teacher_name       VARCHAR(255) NOT NULL DEFAULT '',
                attestation_form   VARCHAR(20) NOT NULL DEFAULT '',
                grade              TINYINT UNSIGNED NULL,
                KEY idx_expelled_rb (expelled_id, academic_year, semester),
                CONSTRAINT fk_expelled_rb
                    FOREIGN KEY (expelled_id) REFERENCES expelled_students(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        $erbTeacher = $pdo->query("SHOW COLUMNS FROM expelled_record_book LIKE 'teacher_name'")->fetch();
        if (!$erbTeacher) {
            $pdo->exec(
                "ALTER TABLE expelled_record_book
                 ADD teacher_name VARCHAR(255) NOT NULL DEFAULT '' AFTER subject_name"
            );
        }
        $erbForm = $pdo->query("SHOW COLUMNS FROM expelled_record_book LIKE 'attestation_form'")->fetch();
        if (!$erbForm) {
            $pdo->exec(
                "ALTER TABLE expelled_record_book
                 ADD attestation_form VARCHAR(20) NOT NULL DEFAULT '' AFTER teacher_name"
            );
        }
    }

    if (!$pdo->query("SHOW TABLES LIKE 'expelled_debts'")->fetch()) {
        $pdo->exec("
            CREATE TABLE expelled_debts (
                id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                expelled_id        INT UNSIGNED NOT NULL,
                curriculum_item_id INT UNSIGNED NOT NULL DEFAULT 0,
                group_id           INT UNSIGNED NULL,
                group_number       VARCHAR(50) NOT NULL DEFAULT '',
                subject_name       VARCHAR(255) NOT NULL,
                academic_year      VARCHAR(9) NOT NULL,
                semester           ENUM('1', '2') NOT NULL,
                archived_at        DATETIME NULL,
                liquidation_date   DATE NULL,
                liquidation_time   TIME NULL,
                commission_json    TEXT NULL,
                KEY idx_expelled_debts (expelled_id),
                CONSTRAINT fk_expelled_debts
                    FOREIGN KEY (expelled_id) REFERENCES expelled_students(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    if (!$pdo->query("SHOW TABLES LIKE 'expelled_restorations'")->fetch()) {
        $pdo->exec("
            CREATE TABLE expelled_restorations (
                id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                expelled_id      INT UNSIGNED NOT NULL,
                restore_date     DATE NOT NULL,
                group_id         INT UNSIGNED NOT NULL,
                group_number     VARCHAR(50) NOT NULL DEFAULT '',
                additional_info  TEXT NOT NULL,
                restored_by      INT UNSIGNED NULL,
                restored_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                new_student_id   INT UNSIGNED NULL,
                KEY idx_expelled_restore (expelled_id),
                CONSTRAINT fk_expelled_restore
                    FOREIGN KEY (expelled_id) REFERENCES expelled_students(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_expelled_restore_by
                    FOREIGN KEY (restored_by) REFERENCES users(id)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}

function ensure_expelled_period_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    if (!$pdo->query("SHOW TABLES LIKE 'expelled_students'")->fetch()) {
        return;
    }

    if (!$pdo->query("SHOW COLUMNS FROM expelled_students LIKE 'expulsion_academic_year'")->fetch()) {
        $pdo->exec(
            "ALTER TABLE expelled_students
             ADD expulsion_academic_year VARCHAR(9) NULL DEFAULT NULL AFTER expulsion_reason,
             ADD expulsion_semester ENUM('1', '2') NULL DEFAULT NULL AFTER expulsion_academic_year"
        );
    }

    require_once __DIR__ . '/expelled.php';

    $rows = $pdo->query(
        "SELECT id, expulsion_date
         FROM expelled_students
         WHERE expulsion_academic_year IS NULL OR expulsion_academic_year = ''"
    )->fetchAll();

    if ($rows !== []) {
        $stmt = $pdo->prepare(
            'UPDATE expelled_students
             SET expulsion_academic_year = ?, expulsion_semester = ?
             WHERE id = ?'
        );
        foreach ($rows as $row) {
            $period = expelled_period_from_date((string) $row['expulsion_date']);
            $stmt->execute([$period['academic_year'], $period['semester'], (int) $row['id']]);
        }
    }
}

function ensure_notifications_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $table = $pdo->query("SHOW TABLES LIKE 'notifications'")->fetch();
    if (!$table) {
        $pdo->exec("
            CREATE TABLE notifications (
                id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sender_id         INT UNSIGNED NULL,
                notification_type ENUM('personal', 'announcement') NOT NULL,
                title             VARCHAR(255) NOT NULL,
                body              TEXT NOT NULL,
                recipient_id      INT UNSIGNED NULL,
                created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_notifications_recipient (recipient_id, created_at),
                KEY idx_notifications_type (notification_type, created_at),
                CONSTRAINT fk_notifications_sender
                    FOREIGN KEY (sender_id) REFERENCES users(id)
                    ON DELETE SET NULL,
                CONSTRAINT fk_notifications_recipient
                    FOREIGN KEY (recipient_id) REFERENCES users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    $reads = $pdo->query("SHOW TABLES LIKE 'notification_reads'")->fetch();
    if (!$reads) {
        $pdo->exec("
            CREATE TABLE notification_reads (
                id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                notification_id INT UNSIGNED NOT NULL,
                user_id         INT UNSIGNED NOT NULL,
                read_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_notification_read (notification_id, user_id),
                KEY idx_notification_reads_user (user_id),
                CONSTRAINT fk_notification_reads_notification
                    FOREIGN KEY (notification_id) REFERENCES notifications(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_notification_reads_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}

function ensure_student_accounts_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $roleCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
    if ($roleCol && strpos((string) ($roleCol['Type'] ?? ''), 'student') === false) {
        $pdo->exec(
            "ALTER TABLE users
             MODIFY role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'teacher'"
        );
    }

    $plainCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_plain'")->fetch();
    if (!$plainCol) {
        $pdo->exec(
            "ALTER TABLE users
             ADD password_plain VARCHAR(64) NULL DEFAULT NULL AFTER password_hash"
        );
    }

    $userIdCol = $pdo->query("SHOW COLUMNS FROM students LIKE 'user_id'")->fetch();
    if (!$userIdCol) {
        $pdo->exec(
            'ALTER TABLE students
             ADD user_id INT UNSIGNED NULL DEFAULT NULL AFTER group_id,
             ADD UNIQUE KEY uq_students_user (user_id)'
        );
        $pdo->exec(
            'ALTER TABLE students
             ADD CONSTRAINT fk_students_user
             FOREIGN KEY (user_id) REFERENCES users(id)
             ON DELETE SET NULL'
        );
    }

    $rb = $pdo->query("SHOW TABLES LIKE 'student_record_book'")->fetch();
    if (!$rb) {
        $pdo->exec("
            CREATE TABLE student_record_book (
                id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id         INT UNSIGNED NOT NULL,
                academic_year      VARCHAR(9) NOT NULL,
                semester           ENUM('1', '2') NOT NULL,
                curriculum_item_id INT UNSIGNED NOT NULL,
                subject_name       VARCHAR(255) NOT NULL,
                teacher_name       VARCHAR(255) NOT NULL DEFAULT '',
                attestation_form   VARCHAR(20) NOT NULL DEFAULT '',
                grade              TINYINT UNSIGNED NULL,
                updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_student_rb (student_id, academic_year, semester, curriculum_item_id),
                KEY idx_student_rb_student (student_id),
                CONSTRAINT fk_student_rb_student
                    FOREIGN KEY (student_id) REFERENCES students(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        $rbTeacher = $pdo->query("SHOW COLUMNS FROM student_record_book LIKE 'teacher_name'")->fetch();
        if (!$rbTeacher) {
            $pdo->exec(
                "ALTER TABLE student_record_book
                 ADD teacher_name VARCHAR(255) NOT NULL DEFAULT '' AFTER subject_name"
            );
        }
        $rbForm = $pdo->query("SHOW COLUMNS FROM student_record_book LIKE 'attestation_form'")->fetch();
        if (!$rbForm) {
            $pdo->exec(
                "ALTER TABLE student_record_book
                 ADD attestation_form VARCHAR(20) NOT NULL DEFAULT '' AFTER teacher_name"
            );
        }
    }

    $birthCol = $pdo->query("SHOW COLUMNS FROM students LIKE 'birth_date'")->fetch();
    if (!$birthCol) {
        $pdo->exec(
            "ALTER TABLE students
             ADD birth_date DATE NULL DEFAULT NULL AFTER phone,
             ADD gender ENUM('male', 'female') NULL DEFAULT NULL AFTER birth_date"
        );
    }
}

function ensure_student_social_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!$pdo->query("SHOW TABLES LIKE 'students'")->fetch()) {
        return;
    }

    $addStudentCol = static function (PDO $pdo, string $name, string $definition) {
        if ($pdo->query("SHOW COLUMNS FROM students LIKE " . $pdo->quote($name))->fetch()) {
            return;
        }
        $pdo->exec('ALTER TABLE students ADD ' . $definition);
    };

    $addStudentCol(
        $pdo,
        'snils',
        "snils VARCHAR(14) NOT NULL DEFAULT '' AFTER phone"
    );

    $addStudentCol(
        $pdo,
        'district',
        "district VARCHAR(255) NOT NULL DEFAULT '' AFTER address_actual"
    );
    $addStudentCol(
        $pdo,
        'is_low_income',
        'is_low_income TINYINT(1) NOT NULL DEFAULT 0 AFTER district'
    );
    $addStudentCol(
        $pdo,
        'family_type',
        "family_type ENUM('complete', 'no_father', 'no_mother') NULL DEFAULT NULL AFTER is_low_income"
    );
    $addStudentCol(
        $pdo,
        'siblings_under_18',
        'siblings_under_18 TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER family_type'
    );
    $addStudentCol(
        $pdo,
        'residence_type',
        "residence_type ENUM('family', 'dormitory', 'apartment') NULL DEFAULT NULL AFTER siblings_under_18"
    );
    $addStudentCol(
        $pdo,
        'is_nonresident',
        'is_nonresident TINYINT(1) NOT NULL DEFAULT 0 AFTER residence_type'
    );
    $addStudentCol(
        $pdo,
        'without_parental_care',
        'without_parental_care TINYINT(1) NOT NULL DEFAULT 0 AFTER is_nonresident'
    );
    $addStudentCol(
        $pdo,
        'mother_workplace',
        "mother_workplace VARCHAR(500) NOT NULL DEFAULT '' AFTER mother_phone"
    );
    $addStudentCol(
        $pdo,
        'father_workplace',
        "father_workplace VARCHAR(500) NOT NULL DEFAULT '' AFTER father_phone"
    );
    $addStudentCol(
        $pdo,
        'address_region',
        "address_region VARCHAR(255) NOT NULL DEFAULT '' AFTER address_registered"
    );
    $addStudentCol(
        $pdo,
        'address_district',
        "address_district VARCHAR(255) NOT NULL DEFAULT '' AFTER address_region"
    );
    $addStudentCol(
        $pdo,
        'address_locality',
        "address_locality VARCHAR(255) NOT NULL DEFAULT '' AFTER address_district"
    );
    $addStudentCol(
        $pdo,
        'address_street',
        "address_street VARCHAR(255) NOT NULL DEFAULT '' AFTER address_locality"
    );
    $addStudentCol(
        $pdo,
        'address_house',
        "address_house VARCHAR(50) NOT NULL DEFAULT '' AFTER address_street"
    );

    if (!$pdo->query("SHOW TABLES LIKE 'expelled_students'")->fetch()) {
        return;
    }

    $addExpelledCol = static function (PDO $pdo, string $name, string $definition) {
        if ($pdo->query("SHOW COLUMNS FROM expelled_students LIKE " . $pdo->quote($name))->fetch()) {
            return;
        }
        $pdo->exec('ALTER TABLE expelled_students ADD ' . $definition);
    };

    $addExpelledCol(
        $pdo,
        'district',
        "district VARCHAR(255) NOT NULL DEFAULT '' AFTER address_actual"
    );
    $addExpelledCol(
        $pdo,
        'is_low_income',
        'is_low_income TINYINT(1) NOT NULL DEFAULT 0 AFTER district'
    );
    $addExpelledCol(
        $pdo,
        'family_type',
        "family_type ENUM('complete', 'no_father', 'no_mother') NULL DEFAULT NULL AFTER is_low_income"
    );
    $addExpelledCol(
        $pdo,
        'siblings_under_18',
        'siblings_under_18 TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER family_type'
    );
    $addExpelledCol(
        $pdo,
        'residence_type',
        "residence_type ENUM('family', 'dormitory', 'apartment') NULL DEFAULT NULL AFTER siblings_under_18"
    );
    $addExpelledCol(
        $pdo,
        'is_nonresident',
        'is_nonresident TINYINT(1) NOT NULL DEFAULT 0 AFTER residence_type'
    );
    $addExpelledCol(
        $pdo,
        'without_parental_care',
        'without_parental_care TINYINT(1) NOT NULL DEFAULT 0 AFTER is_nonresident'
    );
    $addExpelledCol(
        $pdo,
        'mother_workplace',
        "mother_workplace VARCHAR(500) NOT NULL DEFAULT '' AFTER mother_phone"
    );
    $addExpelledCol(
        $pdo,
        'father_workplace',
        "father_workplace VARCHAR(500) NOT NULL DEFAULT '' AFTER father_phone"
    );
    $addExpelledCol(
        $pdo,
        'address_region',
        "address_region VARCHAR(255) NOT NULL DEFAULT '' AFTER address_registered"
    );
    $addExpelledCol(
        $pdo,
        'address_district',
        "address_district VARCHAR(255) NOT NULL DEFAULT '' AFTER address_region"
    );
    $addExpelledCol(
        $pdo,
        'address_locality',
        "address_locality VARCHAR(255) NOT NULL DEFAULT '' AFTER address_district"
    );
    $addExpelledCol(
        $pdo,
        'address_street',
        "address_street VARCHAR(255) NOT NULL DEFAULT '' AFTER address_locality"
    );
    $addExpelledCol(
        $pdo,
        'address_house',
        "address_house VARCHAR(50) NOT NULL DEFAULT '' AFTER address_street"
    );
}

function ensure_user_profile_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $phoneCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'")->fetch();
    if (!$phoneCol) {
        $pdo->exec(
            "ALTER TABLE users
             ADD phone VARCHAR(50) NOT NULL DEFAULT '' AFTER full_name,
             ADD position VARCHAR(255) NOT NULL DEFAULT '' AFTER phone,
             ADD education VARCHAR(500) NOT NULL DEFAULT '' AFTER position,
             ADD additional_info TEXT NULL AFTER education"
        );
    }

    $avatarCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'")->fetch();
    if (!$avatarCol) {
        $pdo->exec(
            "ALTER TABLE users
             ADD avatar VARCHAR(255) NOT NULL DEFAULT 'icon:person' AFTER additional_info"
        );
    }
}

function ensure_students_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $curatorCol = $pdo->query("SHOW COLUMNS FROM study_groups LIKE 'curator_id'")->fetch();
    if (!$curatorCol) {
        $pdo->exec(
            'ALTER TABLE study_groups
             ADD curator_id INT UNSIGNED NULL DEFAULT NULL AFTER specialty_id'
        );
        $pdo->exec(
            'ALTER TABLE study_groups
             ADD CONSTRAINT fk_study_groups_curator
             FOREIGN KEY (curator_id) REFERENCES users(id)
             ON DELETE SET NULL'
        );
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'students'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec("
        CREATE TABLE students (
            id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            group_id           INT UNSIGNED NOT NULL,
            full_name          VARCHAR(255) NOT NULL,
            phone              VARCHAR(50) NOT NULL DEFAULT '',
            mother_name        VARCHAR(255) NOT NULL DEFAULT '',
            mother_phone       VARCHAR(50) NOT NULL DEFAULT '',
            father_name        VARCHAR(255) NOT NULL DEFAULT '',
            father_phone       VARCHAR(50) NOT NULL DEFAULT '',
            address_registered VARCHAR(500) NOT NULL DEFAULT '',
            address_actual     VARCHAR(500) NOT NULL DEFAULT '',
            created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_students_group
                FOREIGN KEY (group_id) REFERENCES study_groups(id)
                ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE group_promotions (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            group_id      INT UNSIGNED NOT NULL,
            from_number   VARCHAR(50) NOT NULL,
            to_number     VARCHAR(50) NOT NULL,
            academic_year VARCHAR(9) NOT NULL,
            promoted_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_group_promotions_group
                FOREIGN KEY (group_id) REFERENCES study_groups(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function ensure_curriculum_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $stmt = $pdo->query("SHOW TABLES LIKE 'subjects'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec("
        CREATE TABLE subjects (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(255) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE curriculum_plans (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            group_id      INT UNSIGNED NOT NULL,
            academic_year VARCHAR(9) NOT NULL,
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_curriculum_group_year (group_id, academic_year),
            CONSTRAINT fk_curriculum_plans_group
                FOREIGN KEY (group_id) REFERENCES study_groups(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE curriculum_items (
            id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            curriculum_plan_id INT UNSIGNED NOT NULL,
            subject_id         INT UNSIGNED NOT NULL,
            semester           ENUM('1', '2', 'both') NOT NULL,
            sort_order         INT UNSIGNED NOT NULL DEFAULT 1,
            created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_curriculum_plan_subject (curriculum_plan_id, subject_id),
            CONSTRAINT fk_curriculum_items_plan
                FOREIGN KEY (curriculum_plan_id) REFERENCES curriculum_plans(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_curriculum_items_subject
                FOREIGN KEY (subject_id) REFERENCES subjects(id)
                ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function ensure_gradebook_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $stmt = $pdo->query("SHOW TABLES LIKE 'app_settings'");
    if (!$stmt->fetch()) {
        $pdo->exec("
            CREATE TABLE app_settings (
                setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $defaultYear = date('n') >= 9
            ? date('Y') . '-' . ((int) date('Y') + 1)
            : ((int) date('Y') - 1) . '-' . date('Y');

        $stmt = $pdo->prepare(
            'INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)'
        );
        $stmt->execute(['active_academic_year', $defaultYear]);
        $stmt->execute(['active_semester', '1']);
    }

    $col = $pdo->query("SHOW COLUMNS FROM app_settings LIKE 'setting_value'")->fetch();
    if ($col && stripos((string) ($col['Type'] ?? ''), 'text') === false) {
        $pdo->exec('ALTER TABLE app_settings MODIFY setting_value TEXT NOT NULL');
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'grade_entries'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec("
        CREATE TABLE grade_entries (
            id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id         INT UNSIGNED NOT NULL,
            curriculum_item_id INT UNSIGNED NOT NULL,
            grade              TINYINT UNSIGNED NOT NULL,
            created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_grade_student_item (student_id, curriculum_item_id),
            CONSTRAINT fk_grade_entries_student
                FOREIGN KEY (student_id) REFERENCES students(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_grade_entries_curriculum_item
                FOREIGN KEY (curriculum_item_id) REFERENCES curriculum_items(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function ensure_attendance_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $stmt = $pdo->query("SHOW TABLES LIKE 'attendance_reasons'");
    if (!$stmt->fetch()) {
        $pdo->exec("
            CREATE TABLE attendance_reasons (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name       VARCHAR(255) NOT NULL UNIQUE,
                sort_order INT UNSIGNED NOT NULL DEFAULT 1,
                is_active  TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            INSERT INTO attendance_reasons (name, sort_order) VALUES
            ('болезнь', 1),
            ('приказ', 2),
            ('заявление', 3)
        ");
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'attendance_days'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec("
        CREATE TABLE attendance_days (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            group_id        INT UNSIGNED NOT NULL,
            attendance_date DATE NOT NULL,
            academic_year   VARCHAR(9) NOT NULL,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_attendance_group_date_year (group_id, attendance_date, academic_year),
            CONSTRAINT fk_attendance_days_group
                FOREIGN KEY (group_id) REFERENCES study_groups(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE attendance_entries (
            id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            attendance_day_id  INT UNSIGNED NOT NULL,
            student_id         INT UNSIGNED NOT NULL,
            excused_lessons    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            unexcused_lessons  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            reason_id          INT UNSIGNED NULL DEFAULT NULL,
            created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_attendance_day_student (attendance_day_id, student_id),
            CONSTRAINT fk_attendance_entries_day
                FOREIGN KEY (attendance_day_id) REFERENCES attendance_days(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_attendance_entries_student
                FOREIGN KEY (student_id) REFERENCES students(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_attendance_entries_reason
                FOREIGN KEY (reason_id) REFERENCES attendance_reasons(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function ensure_journal_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $teacherCol = $pdo->query("SHOW COLUMNS FROM curriculum_items LIKE 'teacher_id'")->fetch();
    if (!$teacherCol) {
        $pdo->exec(
            'ALTER TABLE curriculum_items
             ADD teacher_id INT UNSIGNED NULL DEFAULT NULL AFTER subject_id'
        );
        $pdo->exec(
            'ALTER TABLE curriculum_items
             ADD CONSTRAINT fk_curriculum_items_teacher
             FOREIGN KEY (teacher_id) REFERENCES users(id)
             ON DELETE SET NULL'
        );
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'journal_lessons'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec("
        CREATE TABLE journal_lessons (
            id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            curriculum_item_id INT UNSIGNED NOT NULL,
            lesson_date        DATE NOT NULL,
            created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_journal_item_date (curriculum_item_id, lesson_date),
            CONSTRAINT fk_journal_lessons_item
                FOREIGN KEY (curriculum_item_id) REFERENCES curriculum_items(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE journal_grades (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            lesson_id  INT UNSIGNED NOT NULL,
            student_id INT UNSIGNED NOT NULL,
            mark       VARCHAR(8) NOT NULL DEFAULT '',
            activity   TINYINT(1) NOT NULL DEFAULT 0,
            late       TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_journal_lesson_student (lesson_id, student_id),
            CONSTRAINT fk_journal_grades_lesson
                FOREIGN KEY (lesson_id) REFERENCES journal_lessons(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_journal_grades_student
                FOREIGN KEY (student_id) REFERENCES students(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function ensure_journal_brs_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $stmt = $pdo->query("SHOW TABLES LIKE 'journal_grades'");
    if (!$stmt->fetch()) {
        return;
    }

    $markCol = $pdo->query("SHOW COLUMNS FROM journal_grades LIKE 'mark'")->fetch();
    if (!$markCol) {
        $pdo->exec(
            "ALTER TABLE journal_grades
             ADD mark VARCHAR(8) NOT NULL DEFAULT '' AFTER student_id,
             ADD activity TINYINT(1) NOT NULL DEFAULT 0 AFTER mark,
             ADD late TINYINT(1) NOT NULL DEFAULT 0 AFTER activity"
        );

        $gradeCol = $pdo->query("SHOW COLUMNS FROM journal_grades LIKE 'grade'")->fetch();
        if ($gradeCol) {
            $pdo->exec('UPDATE journal_grades SET mark = CAST(grade AS CHAR)');
            $pdo->exec('ALTER TABLE journal_grades MODIFY grade TINYINT UNSIGNED NULL');
        }
    }
}

function ensure_ktp_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $stmt = $pdo->query("SHOW TABLES LIKE 'ktp_topics'");
    if (!$stmt->fetch()) {
        $pdo->exec("
            CREATE TABLE ktp_topics (
                id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                curriculum_item_id INT UNSIGNED NOT NULL,
                title              VARCHAR(500) NOT NULL,
                lesson_type        ENUM(
                                       'lecture', 'practice', 'independent',
                                       'diff_credit', 'credit', 'exam', 'control'
                                   ) NOT NULL DEFAULT 'lecture',
                hours              DECIMAL(4,1) NOT NULL DEFAULT 2.0,
                sort_order         INT UNSIGNED NOT NULL DEFAULT 1,
                created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_ktp_topics_item
                    FOREIGN KEY (curriculum_item_id) REFERENCES curriculum_items(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    $lessonTypeCol = $pdo->query("SHOW COLUMNS FROM ktp_topics LIKE 'lesson_type'")->fetch();
    if (!$lessonTypeCol) {
        $pdo->exec(
            "ALTER TABLE ktp_topics
             ADD lesson_type ENUM(
                'lecture', 'practice', 'independent',
                'diff_credit', 'credit', 'exam', 'control'
             ) NOT NULL DEFAULT 'lecture' AFTER title,
             ADD hours DECIMAL(4,1) NOT NULL DEFAULT 2.0 AFTER lesson_type"
        );
    } else {
        $typeDef = (string) ($lessonTypeCol['Type'] ?? '');
        if (stripos($typeDef, 'exam') === false) {
            $pdo->exec(
                "ALTER TABLE ktp_topics
                 MODIFY lesson_type ENUM(
                    'lecture', 'practice', 'independent',
                    'diff_credit', 'credit', 'exam', 'control'
                 ) NOT NULL DEFAULT 'lecture'"
            );
        }
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'journal_lessons'");
    if (!$stmt->fetch()) {
        return;
    }

    $topicCol = $pdo->query("SHOW COLUMNS FROM journal_lessons LIKE 'ktp_topic_id'")->fetch();
    if (!$topicCol) {
        $pdo->exec(
            'ALTER TABLE journal_lessons
             ADD ktp_topic_id INT UNSIGNED NULL DEFAULT NULL AFTER lesson_date,
             ADD grade_type ENUM(\'current\', \'control\') NOT NULL DEFAULT \'current\' AFTER ktp_topic_id'
        );
        $pdo->exec(
            'ALTER TABLE journal_lessons
             ADD CONSTRAINT fk_journal_lessons_ktp_topic
             FOREIGN KEY (ktp_topic_id) REFERENCES ktp_topics(id)
             ON DELETE SET NULL'
        );
    }

    $uniqueKey = $pdo->query("SHOW INDEX FROM journal_lessons WHERE Key_name = 'uq_journal_item_date'")->fetch();
    if ($uniqueKey) {
        $fkItem = $pdo->query(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'journal_lessons'
               AND CONSTRAINT_NAME = 'fk_journal_lessons_item'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
             LIMIT 1"
        )->fetch();

        if ($fkItem) {
            $pdo->exec('ALTER TABLE journal_lessons DROP FOREIGN KEY fk_journal_lessons_item');
        }

        $pdo->exec('ALTER TABLE journal_lessons DROP INDEX uq_journal_item_date');

        $itemIndex = $pdo->query(
            "SHOW INDEX FROM journal_lessons WHERE Column_name = 'curriculum_item_id'"
        )->fetch();
        if (!$itemIndex) {
            $pdo->exec('ALTER TABLE journal_lessons ADD KEY idx_journal_item_id (curriculum_item_id)');
        }

        if ($fkItem) {
            $pdo->exec(
                'ALTER TABLE journal_lessons
                 ADD CONSTRAINT fk_journal_lessons_item
                 FOREIGN KEY (curriculum_item_id) REFERENCES curriculum_items(id)
                 ON DELETE CASCADE'
            );
        }
    }

    $dateIndex = $pdo->query("SHOW INDEX FROM journal_lessons WHERE Key_name = 'idx_journal_item_date'")->fetch();
    if (!$dateIndex) {
        $pdo->exec('ALTER TABLE journal_lessons ADD KEY idx_journal_item_date (curriculum_item_id, lesson_date)');
    }
}

function ensure_archive_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $stmt = $pdo->query("SHOW TABLES LIKE 'archive_periods'");
    if ($stmt->fetch()) {
        $agsTeacher = $pdo->query("SHOW TABLES LIKE 'archive_gradebook_subjects'")->fetch();
        if ($agsTeacher) {
            $col = $pdo->query("SHOW COLUMNS FROM archive_gradebook_subjects LIKE 'teacher_name'")->fetch();
            if (!$col) {
                $pdo->exec(
                    "ALTER TABLE archive_gradebook_subjects
                     ADD teacher_name VARCHAR(255) NOT NULL DEFAULT '' AFTER subject_name"
                );
            }
        }
        return;
    }

    $pdo->exec("
        CREATE TABLE archive_periods (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            archive_type   ENUM('gradebook', 'journal') NOT NULL,
            academic_year  VARCHAR(9) NOT NULL,
            semester       ENUM('1', '2') NOT NULL,
            archived_by    INT UNSIGNED NULL,
            archived_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_archive_period (archive_type, academic_year, semester)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE archive_gradebook_groups (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            archive_id      INT UNSIGNED NOT NULL,
            group_id        INT UNSIGNED NOT NULL,
            group_number    VARCHAR(50) NOT NULL,
            specialty_name  VARCHAR(255) NOT NULL DEFAULT '',
            specialty_code  VARCHAR(50) NOT NULL DEFAULT '',
            curator_name    VARCHAR(255) NOT NULL DEFAULT '',
            KEY idx_archive_gb_groups (archive_id, group_id),
            CONSTRAINT fk_archive_gb_groups_archive
                FOREIGN KEY (archive_id) REFERENCES archive_periods(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE archive_gradebook_students (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            archive_id INT UNSIGNED NOT NULL,
            group_id   INT UNSIGNED NOT NULL,
            student_id INT UNSIGNED NOT NULL,
            full_name  VARCHAR(255) NOT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 1,
            KEY idx_archive_gb_students (archive_id, group_id),
            CONSTRAINT fk_archive_gb_students_archive
                FOREIGN KEY (archive_id) REFERENCES archive_periods(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE archive_gradebook_subjects (
            id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            archive_id         INT UNSIGNED NOT NULL,
            group_id           INT UNSIGNED NOT NULL,
            curriculum_item_id INT UNSIGNED NOT NULL,
            subject_name       VARCHAR(255) NOT NULL,
            teacher_name       VARCHAR(255) NOT NULL DEFAULT '',
            sort_order         INT UNSIGNED NOT NULL DEFAULT 1,
            KEY idx_archive_gb_subjects (archive_id, group_id),
            CONSTRAINT fk_archive_gb_subjects_archive
                FOREIGN KEY (archive_id) REFERENCES archive_periods(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE archive_gradebook_grades (
            id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            archive_id         INT UNSIGNED NOT NULL,
            group_id           INT UNSIGNED NOT NULL,
            student_id         INT UNSIGNED NOT NULL,
            curriculum_item_id INT UNSIGNED NOT NULL,
            grade              TINYINT UNSIGNED NULL,
            UNIQUE KEY uq_archive_gb_grade (archive_id, student_id, curriculum_item_id),
            CONSTRAINT fk_archive_gb_grades_archive
                FOREIGN KEY (archive_id) REFERENCES archive_periods(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE archive_grade_changes (
            id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            archive_id         INT UNSIGNED NOT NULL,
            group_id           INT UNSIGNED NOT NULL,
            student_id         INT UNSIGNED NOT NULL,
            curriculum_item_id INT UNSIGNED NOT NULL,
            old_grade          TINYINT UNSIGNED NULL,
            new_grade          TINYINT UNSIGNED NOT NULL,
            reason_code        VARCHAR(50) NOT NULL,
            reason_text        VARCHAR(500) NOT NULL DEFAULT '',
            changed_by         INT UNSIGNED NULL,
            changed_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_archive_gb_changes (archive_id, student_id, curriculum_item_id),
            CONSTRAINT fk_archive_gb_changes_archive
                FOREIGN KEY (archive_id) REFERENCES archive_periods(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE archive_journal_items (
            id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            archive_id         INT UNSIGNED NOT NULL,
            group_id           INT UNSIGNED NOT NULL,
            group_number       VARCHAR(50) NOT NULL,
            curriculum_item_id INT UNSIGNED NOT NULL,
            subject_name       VARCHAR(255) NOT NULL,
            teacher_name       VARCHAR(255) NOT NULL DEFAULT '',
            semester           VARCHAR(10) NOT NULL DEFAULT '1',
            KEY idx_archive_jl_items (archive_id, group_id),
            CONSTRAINT fk_archive_jl_items_archive
                FOREIGN KEY (archive_id) REFERENCES archive_periods(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE archive_journal_students (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_id    INT UNSIGNED NOT NULL,
            student_id INT UNSIGNED NOT NULL,
            full_name  VARCHAR(255) NOT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 1,
            KEY idx_archive_jl_students (item_id),
            CONSTRAINT fk_archive_jl_students_item
                FOREIGN KEY (item_id) REFERENCES archive_journal_items(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE archive_journal_lessons (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_id          INT UNSIGNED NOT NULL,
            source_lesson_id INT UNSIGNED NULL,
            lesson_date      DATE NOT NULL,
            topic_title      VARCHAR(500) NOT NULL DEFAULT '',
            grade_type       VARCHAR(20) NOT NULL DEFAULT 'current',
            sort_order       INT UNSIGNED NOT NULL DEFAULT 1,
            KEY idx_archive_jl_lessons (item_id),
            CONSTRAINT fk_archive_jl_lessons_item
                FOREIGN KEY (item_id) REFERENCES archive_journal_items(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE archive_journal_grades (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            lesson_id  INT UNSIGNED NOT NULL,
            student_id INT UNSIGNED NOT NULL,
            mark       VARCHAR(8) NOT NULL DEFAULT '',
            activity   TINYINT(1) NOT NULL DEFAULT 0,
            late       TINYINT(1) NOT NULL DEFAULT 0,
            UNIQUE KEY uq_archive_jl_grade (lesson_id, student_id),
            CONSTRAINT fk_archive_jl_grades_lesson
                FOREIGN KEY (lesson_id) REFERENCES archive_journal_lessons(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE archive_journal_totals (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_id     INT UNSIGNED NOT NULL,
            student_id  INT UNSIGNED NOT NULL,
            final_grade TINYINT UNSIGNED NULL,
            average     DECIMAL(4,1) NULL,
            points      DECIMAL(5,1) NULL,
            display     VARCHAR(50) NOT NULL DEFAULT '',
            UNIQUE KEY uq_archive_jl_total (item_id, student_id),
            CONSTRAINT fk_archive_jl_totals_item
                FOREIGN KEY (item_id) REFERENCES archive_journal_items(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function ensure_archive_journal_topics_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $items = $pdo->query("SHOW TABLES LIKE 'archive_journal_items'")->fetch();
    if (!$items) {
        return;
    }

    $topics = $pdo->query("SHOW TABLES LIKE 'archive_journal_topics'")->fetch();
    if (!$topics) {
        $pdo->exec("
            CREATE TABLE archive_journal_topics (
                id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                item_id            INT UNSIGNED NOT NULL,
                source_topic_id    INT UNSIGNED NULL,
                title              VARCHAR(500) NOT NULL,
                lesson_type        VARCHAR(50) NOT NULL DEFAULT 'lecture',
                hours              DECIMAL(4,1) NOT NULL DEFAULT 2.0,
                sort_order         INT UNSIGNED NOT NULL DEFAULT 1,
                completed          TINYINT(1) NOT NULL DEFAULT 0,
                first_lesson_date  DATE NULL,
                KEY idx_archive_jl_topics (item_id),
                CONSTRAINT fk_archive_jl_topics_item
                    FOREIGN KEY (item_id) REFERENCES archive_journal_items(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    $typeCol = $pdo->query("SHOW COLUMNS FROM archive_journal_lessons LIKE 'topic_lesson_type'")->fetch();
    if (!$typeCol) {
        $pdo->exec(
            "ALTER TABLE archive_journal_lessons
             ADD topic_lesson_type VARCHAR(50) NOT NULL DEFAULT '' AFTER topic_title,
             ADD topic_hours DECIMAL(4,1) NULL AFTER topic_lesson_type"
        );
    }
}

function ensure_glaz_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $table = $pdo->query("SHOW TABLES LIKE 'glaz_schedules'")->fetch();
    if (!$table) {
        $pdo->exec("
            CREATE TABLE glaz_schedules (
                id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id         INT UNSIGNED NOT NULL,
                curriculum_item_id INT UNSIGNED NOT NULL,
                academic_year      VARCHAR(9) NOT NULL,
                semester           ENUM('1', '2') NOT NULL,
                liquidation_date   DATE NULL,
                liquidation_time   TIME NULL,
                updated_by         INT UNSIGNED NULL,
                updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_glaz_debt (student_id, curriculum_item_id, academic_year, semester),
                KEY idx_glaz_period (academic_year, semester),
                CONSTRAINT fk_glaz_student
                    FOREIGN KEY (student_id) REFERENCES students(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_glaz_item
                    FOREIGN KEY (curriculum_item_id) REFERENCES curriculum_items(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_glaz_updated_by
                    FOREIGN KEY (updated_by) REFERENCES users(id)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    $members = $pdo->query("SHOW TABLES LIKE 'glaz_commission_members'")->fetch();
    if (!$members) {
        $pdo->exec("
            CREATE TABLE glaz_commission_members (
                id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                schedule_id  INT UNSIGNED NOT NULL,
                teacher_id   INT UNSIGNED NOT NULL,
                sort_order   TINYINT UNSIGNED NOT NULL DEFAULT 1,
                UNIQUE KEY uq_glaz_comm (schedule_id, teacher_id),
                KEY idx_glaz_comm_schedule (schedule_id),
                CONSTRAINT fk_glaz_comm_schedule
                    FOREIGN KEY (schedule_id) REFERENCES glaz_schedules(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_glaz_comm_teacher
                    FOREIGN KEY (teacher_id) REFERENCES users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}

function ensure_courseworks_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!$pdo->query("SHOW TABLES LIKE 'student_courseworks'")->fetch()) {
        $pdo->exec("
            CREATE TABLE student_courseworks (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id    INT UNSIGNED NOT NULL,
                subject_name  VARCHAR(255) NOT NULL,
                topic         VARCHAR(500) NOT NULL DEFAULT '',
                defense_date  DATE NULL,
                teacher_name  VARCHAR(255) NOT NULL DEFAULT '',
                grade         TINYINT UNSIGNED NULL,
                sort_order    INT UNSIGNED NOT NULL DEFAULT 1,
                created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_student_courseworks (student_id, sort_order),
                CONSTRAINT fk_student_courseworks_student
                    FOREIGN KEY (student_id) REFERENCES students(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    if (!$pdo->query("SHOW TABLES LIKE 'expelled_courseworks'")->fetch()) {
        $pdo->exec("
            CREATE TABLE expelled_courseworks (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                expelled_id   INT UNSIGNED NOT NULL,
                subject_name  VARCHAR(255) NOT NULL,
                topic         VARCHAR(500) NOT NULL DEFAULT '',
                defense_date  DATE NULL,
                teacher_name  VARCHAR(255) NOT NULL DEFAULT '',
                grade         TINYINT UNSIGNED NULL,
                sort_order    INT UNSIGNED NOT NULL DEFAULT 1,
                KEY idx_expelled_courseworks (expelled_id, sort_order),
                CONSTRAINT fk_expelled_courseworks
                    FOREIGN KEY (expelled_id) REFERENCES expelled_students(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}

function ensure_practices_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!$pdo->query("SHOW TABLES LIKE 'student_practices'")->fetch()) {
        $pdo->exec("
            CREATE TABLE student_practices (
                id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id             INT UNSIGNED NOT NULL,
                module_name            VARCHAR(255) NOT NULL,
                org_supervisor_name    VARCHAR(255) NOT NULL DEFAULT '',
                college_supervisor_name VARCHAR(255) NOT NULL DEFAULT '',
                grade                  TINYINT UNSIGNED NULL,
                sort_order             INT UNSIGNED NOT NULL DEFAULT 1,
                created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_student_practices (student_id, sort_order),
                CONSTRAINT fk_student_practices_student
                    FOREIGN KEY (student_id) REFERENCES students(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    if (!$pdo->query("SHOW TABLES LIKE 'expelled_practices'")->fetch()) {
        $pdo->exec("
            CREATE TABLE expelled_practices (
                id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                expelled_id            INT UNSIGNED NOT NULL,
                module_name            VARCHAR(255) NOT NULL,
                org_supervisor_name    VARCHAR(255) NOT NULL DEFAULT '',
                college_supervisor_name VARCHAR(255) NOT NULL DEFAULT '',
                grade                  TINYINT UNSIGNED NULL,
                sort_order             INT UNSIGNED NOT NULL DEFAULT 1,
                KEY idx_expelled_practices (expelled_id, sort_order),
                CONSTRAINT fk_expelled_practices
                    FOREIGN KEY (expelled_id) REFERENCES expelled_students(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}

function ensure_gia_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!$pdo->query("SHOW TABLES LIKE 'student_gia'")->fetch()) {
        $pdo->exec("
            CREATE TABLE student_gia (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id    INT UNSIGNED NOT NULL,
                form_type     ENUM('demo_exam', 'vkr') NOT NULL,
                module_name   VARCHAR(255) NOT NULL DEFAULT '',
                code          VARCHAR(100) NOT NULL DEFAULT '',
                points        DECIMAL(8,2) NULL,
                topic         VARCHAR(500) NOT NULL DEFAULT '',
                defense_date  DATE NULL,
                grade         TINYINT UNSIGNED NULL,
                sort_order    INT UNSIGNED NOT NULL DEFAULT 1,
                created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_student_gia (student_id, form_type, sort_order),
                CONSTRAINT fk_student_gia_student
                    FOREIGN KEY (student_id) REFERENCES students(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    if (!$pdo->query("SHOW TABLES LIKE 'expelled_gia'")->fetch()) {
        $pdo->exec("
            CREATE TABLE expelled_gia (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                expelled_id   INT UNSIGNED NOT NULL,
                form_type     ENUM('demo_exam', 'vkr') NOT NULL,
                module_name   VARCHAR(255) NOT NULL DEFAULT '',
                code          VARCHAR(100) NOT NULL DEFAULT '',
                points        DECIMAL(8,2) NULL,
                topic         VARCHAR(500) NOT NULL DEFAULT '',
                defense_date  DATE NULL,
                grade         TINYINT UNSIGNED NULL,
                sort_order    INT UNSIGNED NOT NULL DEFAULT 1,
                KEY idx_expelled_gia (expelled_id, form_type, sort_order),
                CONSTRAINT fk_expelled_gia
                    FOREIGN KEY (expelled_id) REFERENCES expelled_students(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
