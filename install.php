<?php

declare(strict_types=1);

/**
 * Скрипт первоначальной установки базы данных.
 * Запустите один раз: http://localhost/kpk2.0/install.php
 * После успешной установки удалите этот файл!
 */

$config = require __DIR__ . '/config/database.php';

$messages = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dsn = sprintf('mysql:host=%s;charset=%s', $config['host'], $config['charset']);
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec(
            'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $config['dbname']) . '`
             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $messages[] = 'База данных создана.';

        $pdo->exec('USE `' . str_replace('`', '``', $config['dbname']) . '`');

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email         VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                full_name     VARCHAR(255) NOT NULL,
                role          ENUM('admin', 'teacher') NOT NULL DEFAULT 'teacher',
                is_active     TINYINT(1) NOT NULL DEFAULT 1,
                created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = 'Таблица users создана.';

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_roles (
                user_id INT UNSIGNED NOT NULL,
                role    ENUM('teacher', 'curator', 'deputy', 'educator', 'specialty_head') NOT NULL,
                PRIMARY KEY (user_id, role),
                CONSTRAINT fk_user_roles_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = 'Таблица user_roles создана.';

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS organization (
                id              TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
                name            VARCHAR(500) NOT NULL DEFAULT '',
                address         VARCHAR(500) NOT NULL DEFAULT '',
                phone           VARCHAR(100) NOT NULL DEFAULT '',
                email           VARCHAR(255) NOT NULL DEFAULT '',
                additional_info TEXT NOT NULL,
                updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = 'Таблица organization создана.';

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS specialties (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name       VARCHAR(255) NOT NULL,
                code       VARCHAR(50) NOT NULL UNIQUE,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = 'Таблица specialties создана.';

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_specialty_heads (
                user_id      INT UNSIGNED NOT NULL PRIMARY KEY,
                specialty_id INT UNSIGNED NOT NULL,
                CONSTRAINT fk_user_specialty_heads_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_user_specialty_heads_specialty
                    FOREIGN KEY (specialty_id) REFERENCES specialties(id)
                    ON DELETE RESTRICT,
                UNIQUE KEY uq_user_specialty_heads_specialty (specialty_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = 'Таблица user_specialty_heads создана.';

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS study_groups (
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
        $messages[] = 'Таблица study_groups создана.';

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS subjects (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name       VARCHAR(255) NOT NULL UNIQUE,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = 'Таблица subjects создана.';

        $pdo->exec("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = 'Таблица curriculum_plans создана.';

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS curriculum_items (
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
        $messages[] = 'Таблица curriculum_items создана.';

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS app_settings (
                setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = 'Таблица app_settings создана.';

        $defaultYear = date('n') >= 9
            ? date('Y') . '-' . ((int) date('Y') + 1)
            : ((int) date('Y') - 1) . '-' . date('Y');

        $stmt = $pdo->prepare(
            'INSERT INTO app_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute(['active_academic_year', $defaultYear]);
        $stmt->execute(['active_semester', '1']);

        $pdo->exec("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = 'Таблица grade_entries создана.';

        $adminEmail = 'admin@kpk.local';
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$adminEmail]);

        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare(
                'INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $adminEmail,
                password_hash('admin123', PASSWORD_DEFAULT),
                'Администратор системы',
                'admin',
            ]);
            $messages[] = 'Администратор создан: admin@kpk.local / admin123';
        } else {
            $messages[] = 'Администратор уже существует.';
        }

        $messages[] = 'Установка завершена! Перейдите на страницу входа.';
    } catch (PDOException $e) {
        $error = 'Ошибка подключения: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка — КПК</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="page">
        <main class="main">
            <div class="auth-card">
                <div class="auth-card__header">
                    <h1>Установка системы</h1>
                    <p>Создание базы данных и учётной записи администратора</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php foreach ($messages as $msg): ?>
                    <div class="alert alert--success"><?= htmlspecialchars($msg) ?></div>
                <?php endforeach; ?>

                <?php if (empty($messages)): ?>
                    <p class="text-muted">
                        Убедитесь, что MySQL запущен в OSPanel и настройки в
                        <code>config/database.php</code> верны.
                    </p>
                    <form method="post">
                        <button type="submit" class="btn btn--primary btn--block">Установить</button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="btn btn--primary btn--block">Перейти ко входу</a>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
