<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function ensure_mobile_notification_schedules_schema(?PDO $pdo = null): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo = $pdo ?? db();
    $table = $pdo->query("SHOW TABLES LIKE 'mobile_notification_schedules'")->fetch();
    if ($table) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE mobile_notification_schedules (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(120) NOT NULL DEFAULT 'СПО-ПРОГРЕСС',
            body TEXT NOT NULL,
            notify_time TIME NOT NULL,
            frequency ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
            weekday TINYINT UNSIGNED NULL,
            month_day TINYINT UNSIGNED NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_mobile_notif_active (is_active),
            CONSTRAINT fk_mobile_notif_creator
                FOREIGN KEY (created_by) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function mobile_notification_frequency_label(string $frequency): string
{
    return match ($frequency) {
        'weekly' => 'Еженедельно',
        'monthly' => 'Ежемесячно',
        default => 'Ежедневно',
    };
}

function mobile_notification_weekday_label(?int $weekday): string
{
    $map = [
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
        7 => 'Воскресенье',
    ];

    return $map[$weekday] ?? '—';
}

/**
 * @return list<array<string, mixed>>
 */
function list_mobile_notification_schedules(bool $activeOnly = false): array
{
    ensure_mobile_notification_schedules_schema();
    $sql = 'SELECT * FROM mobile_notification_schedules';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY notify_time ASC, id ASC';

    return db()->query($sql)->fetchAll();
}

function get_mobile_notification_schedule(int $id): ?array
{
    ensure_mobile_notification_schedules_schema();
    $stmt = db()->prepare('SELECT * FROM mobile_notification_schedules WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * @param array<string, mixed> $input
 * @return array{success: bool, error?: string, id?: int}
 */
function save_mobile_notification_schedule(array $input, ?int $id = null): array
{
    ensure_mobile_notification_schedules_schema();

    $title = trim((string) ($input['title'] ?? 'СПО-ПРОГРЕСС'));
    $body = trim((string) ($input['body'] ?? ''));
    $time = trim((string) ($input['notify_time'] ?? ''));
    $frequency = (string) ($input['frequency'] ?? 'daily');
    $weekday = isset($input['weekday']) && $input['weekday'] !== '' ? (int) $input['weekday'] : null;
    $monthDay = isset($input['month_day']) && $input['month_day'] !== '' ? (int) $input['month_day'] : null;
    $isActive = !empty($input['is_active']) ? 1 : 0;

    if ($title === '') {
        $title = 'СПО-ПРОГРЕСС';
    }
    if (mb_strlen($title) > 120) {
        return ['success' => false, 'error' => 'Заголовок слишком длинный.'];
    }
    if ($body === '') {
        return ['success' => false, 'error' => 'Укажите текст уведомления.'];
    }
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
        return ['success' => false, 'error' => 'Укажите корректное время (ЧЧ:ММ).'];
    }
    if (strlen($time) === 5) {
        $time .= ':00';
    }
    if (!in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
        return ['success' => false, 'error' => 'Некорректная периодичность.'];
    }

    if ($frequency === 'weekly') {
        if ($weekday === null || $weekday < 1 || $weekday > 7) {
            return ['success' => false, 'error' => 'Укажите день недели.'];
        }
        $monthDay = null;
    } elseif ($frequency === 'monthly') {
        if ($monthDay === null || $monthDay < 1 || $monthDay > 31) {
            return ['success' => false, 'error' => 'Укажите число месяца (1–31).'];
        }
        $weekday = null;
    } else {
        $weekday = null;
        $monthDay = null;
    }

    $userId = (int) (current_user()['id'] ?? 0) ?: null;

    if ($id !== null && $id > 0) {
        $existing = get_mobile_notification_schedule($id);
        if ($existing === null) {
            return ['success' => false, 'error' => 'Расписание не найдено.'];
        }
        $stmt = db()->prepare(
            'UPDATE mobile_notification_schedules
             SET title = ?, body = ?, notify_time = ?, frequency = ?,
                 weekday = ?, month_day = ?, is_active = ?
             WHERE id = ?'
        );
        $stmt->execute([$title, $body, $time, $frequency, $weekday, $monthDay, $isActive, $id]);

        return ['success' => true, 'id' => $id];
    }

    $stmt = db()->prepare(
        'INSERT INTO mobile_notification_schedules
         (title, body, notify_time, frequency, weekday, month_day, is_active, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$title, $body, $time, $frequency, $weekday, $monthDay, $isActive, $userId]);

    return ['success' => true, 'id' => (int) db()->lastInsertId()];
}

function delete_mobile_notification_schedule(int $id): array
{
    ensure_mobile_notification_schedules_schema();
    $stmt = db()->prepare('DELETE FROM mobile_notification_schedules WHERE id = ?');
    $stmt->execute([$id]);

    return ['success' => true];
}

/**
 * @return list<array<string, mixed>>
 */
function mobile_notification_schedules_public_payload(): array
{
    $rows = list_mobile_notification_schedules(true);
    $out = [];
    foreach ($rows as $row) {
        $time = substr((string) $row['notify_time'], 0, 5);
        $out[] = [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'body' => (string) $row['body'],
            'time' => $time,
            'frequency' => (string) $row['frequency'],
            'weekday' => $row['weekday'] !== null ? (int) $row['weekday'] : null,
            'month_day' => $row['month_day'] !== null ? (int) $row['month_day'] : null,
        ];
    }

    return $out;
}
