<?php

declare(strict_types=1);

/** Доступные роли сотрудников (можно назначать несколько одновременно). */
const STAFF_ROLES = ['teacher', 'curator', 'deputy', 'educator', 'specialty_head'];

function staff_role_labels(): array
{
    return [
        'teacher'        => 'Преподаватель',
        'curator'        => 'Куратор',
        'deputy'         => 'Завуч',
        'educator'       => 'Воспитатель',
        'specialty_head' => 'Руководитель специальности',
    ];
}

function staff_role_label(string $role): string
{
    return staff_role_labels()[$role] ?? $role;
}

function normalize_staff_roles(array $roles): array
{
    $normalized = [];

    foreach ($roles as $role) {
        if (!is_string($role)) {
            continue;
        }

        $role = trim($role);
        if (in_array($role, STAFF_ROLES, true) && !in_array($role, $normalized, true)) {
            $normalized[] = $role;
        }
    }

    sort($normalized);

    return $normalized;
}

function validate_staff_roles(array $roles): array
{
    $roles = normalize_staff_roles($roles);

    if ($roles === []) {
        return ['success' => false, 'error' => 'Выберите хотя бы одну роль.'];
    }

    return ['success' => true, 'roles' => $roles];
}

function get_user_staff_roles(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT role FROM user_roles WHERE user_id = ? ORDER BY role ASC'
    );
    $stmt->execute([$userId]);

    return array_column($stmt->fetchAll(), 'role');
}

function set_user_staff_roles(int $userId, array $roles): void
{
    $roles = normalize_staff_roles($roles);
    $pdo = db();

    $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$userId]);

    $stmt = $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?, ?)');

    foreach ($roles as $role) {
        $stmt->execute([$userId, $role]);
    }
}

function user_has_staff_role(int $userId, string $role): bool
{
    return in_array($role, get_user_staff_roles($userId), true);
}

function format_staff_roles_list(array $roles): string
{
    if ($roles === []) {
        return '—';
    }

    return implode(', ', array_map('staff_role_label', $roles));
}

function render_staff_roles_badges(array $roles): string
{
    if ($roles === []) {
        return '<span class="text-muted">—</span>';
    }

    $html = '';
    foreach ($roles as $role) {
        $html .= '<span class="badge badge--role badge--' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars(staff_role_label($role), ENT_QUOTES, 'UTF-8')
            . '</span> ';
    }

    return trim($html);
}
