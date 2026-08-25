<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function can_send_personal_notifications(): bool
{
    return is_admin() || can_use_deputy_panel();
}

function can_send_announcements(): bool
{
    return is_admin();
}

function require_notification_sender(): void
{
    require_login();

    if (!can_send_personal_notifications()) {
        http_response_code(403);
        exit('Доступ запрещён.');
    }
}

function get_notification_recipient_options(): array
{
    $stmt = db()->query(
        "SELECT id, full_name, email, role
         FROM users
         WHERE is_active = 1
         ORDER BY full_name COLLATE utf8mb4_unicode_ci ASC"
    );

    return $stmt->fetchAll();
}

/** @return array{teachers: array<int, array<string, mixed>>, students: array<int, array<string, mixed>>} */
function get_notification_recipient_groups(): array
{
    $groups = [
        'teachers' => [],
        'students' => [],
    ];

    foreach (get_notification_recipient_options() as $user) {
        $role = (string) ($user['role'] ?? '');
        if ($role === 'teacher') {
            $groups['teachers'][] = $user;
        } elseif ($role === 'student') {
            $groups['students'][] = $user;
        }
    }

    return $groups;
}

function notification_recipient_label(array $user): string
{
    $name = (string) ($user['full_name'] ?? '');
    $phone = trim((string) ($user['phone'] ?? ''));
    $email = display_user_email((string) ($user['email'] ?? ''));
    $role = (string) ($user['role'] ?? '');

    if ($role === 'student' && $email === '' && substr((string) ($user['email'] ?? ''), -13) === '@student.local') {
        $email = substr((string) $user['email'], 0, -13);
    }

    $parts = [$name];
    if ($phone !== '') {
        $parts[] = $phone;
    } elseif ($email !== '') {
        $parts[] = $email;
    }

    return implode(' · ', $parts);
}

function notify_user_direct(int $recipientId, string $title, string $body): bool
{
    $title = trim($title);
    $body = trim($body);
    if ($recipientId <= 0 || $title === '' || $body === '') {
        return false;
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$recipientId]);
    if (!$stmt->fetch()) {
        return false;
    }

    $sender = current_user();
    $stmt = db()->prepare(
        'INSERT INTO notifications (sender_id, notification_type, title, body, recipient_id)
         VALUES (?, \'personal\', ?, ?, ?)'
    );
    $stmt->execute([
        $sender ? (int) $sender['id'] : null,
        $title,
        $body,
        $recipientId,
    ]);

    return true;
}

function send_personal_notification(int $recipientId, string $title, string $body): array
{
    if (!can_send_personal_notifications()) {
        return ['success' => false, 'error' => 'Недостаточно прав для отправки сообщений.'];
    }

    $title = trim($title);
    $body = trim($body);
    if ($title === '') {
        return ['success' => false, 'error' => 'Укажите тему сообщения.'];
    }
    if ($body === '') {
        return ['success' => false, 'error' => 'Укажите текст сообщения.'];
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$recipientId]);
    if (!$stmt->fetch()) {
        return ['success' => false, 'error' => 'Получатель не найден.'];
    }

    $sender = current_user();
    $stmt = db()->prepare(
        'INSERT INTO notifications (sender_id, notification_type, title, body, recipient_id)
         VALUES (?, \'personal\', ?, ?, ?)'
    );
    $stmt->execute([
        $sender ? (int) $sender['id'] : null,
        $title,
        $body,
        $recipientId,
    ]);

    return ['success' => true];
}

function send_announcement(string $title, string $body): array
{
    if (!can_send_announcements()) {
        return ['success' => false, 'error' => 'Общие оповещения может отправлять только администратор.'];
    }

    $title = trim($title);
    $body = trim($body);
    if ($title === '') {
        return ['success' => false, 'error' => 'Укажите тему оповещения.'];
    }
    if ($body === '') {
        return ['success' => false, 'error' => 'Укажите текст оповещения.'];
    }

    $sender = current_user();
    $stmt = db()->prepare(
        'INSERT INTO notifications (sender_id, notification_type, title, body, recipient_id)
         VALUES (?, \'announcement\', ?, ?, NULL)'
    );
    $stmt->execute([
        $sender ? (int) $sender['id'] : null,
        $title,
        $body,
    ]);

    return ['success' => true];
}

function get_unread_notifications_count(?int $userId = null): int
{
    $userId = $userId ?? (int) (current_user()['id'] ?? 0);
    if ($userId <= 0) {
        return 0;
    }

    $stmt = db()->prepare(
        "SELECT COUNT(*)
         FROM notifications n
         LEFT JOIN notification_reads nr
            ON nr.notification_id = n.id AND nr.user_id = ?
         WHERE nr.id IS NULL
           AND (
                (n.notification_type = 'personal' AND n.recipient_id = ?)
                OR n.notification_type = 'announcement'
           )"
    );
    $stmt->execute([$userId, $userId]);

    return (int) $stmt->fetchColumn();
}

function get_user_notifications(?int $userId = null): array
{
    $userId = $userId ?? (int) (current_user()['id'] ?? 0);
    if ($userId <= 0) {
        return [];
    }

    $stmt = db()->prepare(
        "SELECT n.*, u.full_name AS sender_name,
                CASE WHEN nr.id IS NULL THEN 0 ELSE 1 END AS is_read,
                nr.read_at
         FROM notifications n
         LEFT JOIN users u ON u.id = n.sender_id
         LEFT JOIN notification_reads nr
            ON nr.notification_id = n.id AND nr.user_id = ?
         WHERE (n.notification_type = 'personal' AND n.recipient_id = ?)
            OR n.notification_type = 'announcement'
         ORDER BY n.created_at DESC, n.id DESC"
    );
    $stmt->execute([$userId, $userId]);

    return $stmt->fetchAll();
}

function user_can_view_notification(array $notification, ?int $userId = null): bool
{
    $userId = $userId ?? (int) (current_user()['id'] ?? 0);
    if ($userId <= 0) {
        return false;
    }

    if ($notification['notification_type'] === 'announcement') {
        return true;
    }

    return (int) ($notification['recipient_id'] ?? 0) === $userId;
}

function mark_notification_read(int $notificationId, ?int $userId = null): array
{
    $userId = $userId ?? (int) (current_user()['id'] ?? 0);
    if ($userId <= 0) {
        return ['success' => false, 'error' => 'Необходима авторизация.'];
    }

    $stmt = db()->prepare('SELECT * FROM notifications WHERE id = ? LIMIT 1');
    $stmt->execute([$notificationId]);
    $notification = $stmt->fetch();
    if (!$notification || !user_can_view_notification($notification, $userId)) {
        return ['success' => false, 'error' => 'Уведомление не найдено.'];
    }

    $ins = db()->prepare(
        'INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)'
    );
    $ins->execute([$notificationId, $userId]);

    return ['success' => true];
}

function mark_all_notifications_read(?int $userId = null): void
{
    $userId = $userId ?? (int) (current_user()['id'] ?? 0);
    if ($userId <= 0) {
        return;
    }

    $stmt = db()->prepare(
        "INSERT IGNORE INTO notification_reads (notification_id, user_id)
         SELECT n.id, ?
         FROM notifications n
         LEFT JOIN notification_reads nr
            ON nr.notification_id = n.id AND nr.user_id = ?
         WHERE nr.id IS NULL
           AND (
                (n.notification_type = 'personal' AND n.recipient_id = ?)
                OR n.notification_type = 'announcement'
           )"
    );
    $stmt->execute([$userId, $userId, $userId]);
}

function notification_type_label(string $type): string
{
    return $type === 'announcement' ? 'Общее оповещение' : 'Личное сообщение';
}
