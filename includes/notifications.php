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
    return is_admin() || can_use_deputy_panel();
}

/** Допустимые аудитории группового оповещения. */
function notification_audience_options(): array
{
    return [
        'curators' => 'Кураторы',
        'teachers' => 'Преподаватели',
        'students' => 'Студенты',
    ];
}

/**
 * @param list<string>|mixed $raw
 * @return list<string>
 */
function normalize_notification_audience($raw): array
{
    if (!is_array($raw)) {
        return [];
    }

    $allowed = array_keys(notification_audience_options());
    $result = [];
    foreach ($raw as $value) {
        $key = is_string($value) ? trim($value) : '';
        if ($key !== '' && in_array($key, $allowed, true) && !in_array($key, $result, true)) {
            $result[] = $key;
        }
    }

    return $result;
}

function encode_notification_audience(array $audience): ?string
{
    $audience = normalize_notification_audience($audience);

    return $audience === [] ? null : implode(',', $audience);
}

/** @return list<string> */
function decode_notification_audience(?string $audience): array
{
    if ($audience === null || trim($audience) === '') {
        return [];
    }

    return normalize_notification_audience(explode(',', $audience));
}

function notification_audience_label(?string $audience): string
{
    $keys = decode_notification_audience($audience);
    if ($keys === []) {
        return 'Все пользователи';
    }

    $labels = notification_audience_options();
    $parts = [];
    foreach ($keys as $key) {
        $parts[] = $labels[$key] ?? $key;
    }

    return implode(', ', $parts);
}

function user_matches_notification_audience(?array $user, ?string $audience): bool
{
    $targets = decode_notification_audience($audience);
    if ($targets === []) {
        return true;
    }
    if ($user === null) {
        return false;
    }

    $role = (string) ($user['role'] ?? '');
    $staffRoles = $user['staff_roles'] ?? null;
    if (!is_array($staffRoles)) {
        $userId = (int) ($user['id'] ?? 0);
        $staffRoles = $userId > 0 ? get_user_staff_roles($userId) : [];
    }

    if (in_array('students', $targets, true) && $role === 'student') {
        return true;
    }
    if (in_array('teachers', $targets, true) && $role === 'teacher') {
        return true;
    }
    if (in_array('curators', $targets, true) && in_array('curator', $staffRoles, true)) {
        return true;
    }

    return false;
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

function send_announcement(string $title, string $body, array $audience = []): array
{
    if (!can_send_announcements()) {
        return ['success' => false, 'error' => 'Недостаточно прав для отправки оповещений.'];
    }

    $title = trim($title);
    $body = trim($body);
    $audience = normalize_notification_audience($audience);
    if ($title === '') {
        return ['success' => false, 'error' => 'Укажите тему оповещения.'];
    }
    if ($body === '') {
        return ['success' => false, 'error' => 'Укажите текст оповещения.'];
    }
    if ($audience === []) {
        return ['success' => false, 'error' => 'Выберите, кому отправить уведомление.'];
    }

    $sender = current_user();
    $stmt = db()->prepare(
        'INSERT INTO notifications (sender_id, notification_type, title, body, recipient_id, audience)
         VALUES (?, \'announcement\', ?, ?, NULL, ?)'
    );
    $stmt->execute([
        $sender ? (int) $sender['id'] : null,
        $title,
        $body,
        encode_notification_audience($audience),
    ]);

    return ['success' => true];
}

function get_unread_notifications_count(?int $userId = null): int
{
    $count = 0;
    foreach (get_user_notifications($userId) as $notification) {
        if (!(int) ($notification['is_read'] ?? 0)) {
            $count++;
        }
    }

    return $count;
}

function get_user_notifications(?int $userId = null): array
{
    $userId = $userId ?? (int) (current_user()['id'] ?? 0);
    if ($userId <= 0) {
        return [];
    }

    $user = current_user();
    if ($user === null || (int) ($user['id'] ?? 0) !== $userId) {
        $stmtUser = db()->prepare('SELECT id, role FROM users WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch() ?: null;
        if ($user !== null) {
            $user['staff_roles'] = get_user_staff_roles($userId);
        }
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

    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        if (!user_can_view_notification($row, $userId, $user)) {
            continue;
        }
        $items[] = $row;
    }

    return $items;
}

function user_can_view_notification(array $notification, ?int $userId = null, ?array $user = null): bool
{
    $userId = $userId ?? (int) (current_user()['id'] ?? 0);
    if ($userId <= 0) {
        return false;
    }

    if ($notification['notification_type'] === 'announcement') {
        if ($user === null) {
            $user = current_user();
            if ($user === null || (int) ($user['id'] ?? 0) !== $userId) {
                $stmt = db()->prepare('SELECT id, role FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $user = $stmt->fetch() ?: null;
                if ($user !== null) {
                    $user['staff_roles'] = get_user_staff_roles($userId);
                }
            }
        }

        return user_matches_notification_audience($user, $notification['audience'] ?? null);
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

    $ids = [];
    foreach (get_user_notifications($userId) as $notification) {
        if (!(int) ($notification['is_read'] ?? 0)) {
            $ids[] = (int) $notification['id'];
        }
    }
    if ($ids === []) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)'
    );
    foreach ($ids as $notificationId) {
        $stmt->execute([$notificationId, $userId]);
    }
}

function notification_type_label(string $type): string
{
    return $type === 'announcement' ? 'Общее оповещение' : 'Личное сообщение';
}
