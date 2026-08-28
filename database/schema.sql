-- База данных системы контроля успеваемости и посещаемости
-- Выполните в phpMyAdmin или через консоль MySQL

CREATE DATABASE IF NOT EXISTS spo_progress
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE spo_progress;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(255) NOT NULL,
    phone         VARCHAR(50) NOT NULL DEFAULT '',
    position      VARCHAR(255) NOT NULL DEFAULT '',
    education     VARCHAR(500) NOT NULL DEFAULT '',
    additional_info TEXT NULL,
    avatar          VARCHAR(255) NOT NULL DEFAULT 'icon:person',
    role          ENUM('admin', 'teacher') NOT NULL DEFAULT 'teacher',
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT UNSIGNED NOT NULL,
    role    ENUM('teacher', 'curator', 'deputy', 'educator') NOT NULL,
    PRIMARY KEY (user_id, role),
    CONSTRAINT fk_user_roles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS organization (
    id              TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    name            VARCHAR(500) NOT NULL DEFAULT '',
    address         VARCHAR(500) NOT NULL DEFAULT '',
    phone           VARCHAR(100) NOT NULL DEFAULT '',
    email           VARCHAR(255) NOT NULL DEFAULT '',
    additional_info TEXT NOT NULL,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS specialties (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    code       VARCHAR(50) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS study_groups (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    number               VARCHAR(50) NOT NULL UNIQUE,
    specialty_id         INT UNSIGNED NOT NULL,
    curator_id           INT UNSIGNED NULL DEFAULT NULL,
    is_professionality   TINYINT(1) NOT NULL DEFAULT 0,
    is_general_education TINYINT(1) NOT NULL DEFAULT 0,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_study_groups_specialty
        FOREIGN KEY (specialty_id) REFERENCES specialties(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_study_groups_curator
        FOREIGN KEY (curator_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS group_promotions (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id      INT UNSIGNED NOT NULL,
    from_number   VARCHAR(50) NOT NULL,
    to_number     VARCHAR(50) NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    promoted_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_group_promotions_group
        FOREIGN KEY (group_id) REFERENCES study_groups(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grade_entries (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_reasons (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL UNIQUE,
    sort_order INT UNSIGNED NOT NULL DEFAULT 1,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_days (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id        INT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    academic_year   VARCHAR(9) NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_attendance_group_date_year (group_id, attendance_date, academic_year),
    CONSTRAINT fk_attendance_days_group
        FOREIGN KEY (group_id) REFERENCES study_groups(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_entries (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subjects (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curriculum_plans (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id      INT UNSIGNED NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_curriculum_group_year (group_id, academic_year),
    CONSTRAINT fk_curriculum_plans_group
        FOREIGN KEY (group_id) REFERENCES study_groups(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curriculum_items (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    curriculum_plan_id INT UNSIGNED NOT NULL,
    subject_id         INT UNSIGNED NOT NULL,
    teacher_id         INT UNSIGNED NULL DEFAULT NULL,
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
        ON DELETE RESTRICT,
    CONSTRAINT fk_curriculum_items_teacher
        FOREIGN KEY (teacher_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ktp_topics (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS journal_lessons (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    curriculum_item_id INT UNSIGNED NOT NULL,
    lesson_date        DATE NOT NULL,
    ktp_topic_id       INT UNSIGNED NULL DEFAULT NULL,
    grade_type         ENUM('current', 'control') NOT NULL DEFAULT 'current',
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_journal_item_date (curriculum_item_id, lesson_date),
    CONSTRAINT fk_journal_lessons_item
        FOREIGN KEY (curriculum_item_id) REFERENCES curriculum_items(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_journal_lessons_ktp_topic
        FOREIGN KEY (ktp_topic_id) REFERENCES ktp_topics(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS journal_grades (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Администратор создаётся через install.php:
-- Email: admin@kpk.local
-- Пароль: admin123
-- (обязательно смените пароль после первого входа)
