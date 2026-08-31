<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/students.php';

function get_all_teachers(): array
{
    $stmt = db()->query(
        "SELECT id, email, full_name, phone, is_active, auth_credentials_sent, created_at
         FROM users
         WHERE role = 'teacher'
         ORDER BY full_name COLLATE utf8mb4_unicode_ci ASC"
    );
    $teachers = $stmt->fetchAll();

    foreach ($teachers as &$teacher) {
        $teacher['phone'] = (string) ($teacher['phone'] ?? '');
        $teacher['auth_credentials_sent'] = (int) ($teacher['auth_credentials_sent'] ?? 0);
        $teacher['staff_roles'] = get_user_staff_roles((int) $teacher['id']);
        $curatorGroups = in_array('curator', $teacher['staff_roles'], true)
            ? get_groups_for_curator((int) $teacher['id'])
            : [];
        $teacher['curator_group_id'] = isset($curatorGroups[0]) ? (int) $curatorGroups[0]['id'] : null;
        $teacher['curator_group_id_2'] = isset($curatorGroups[1]) ? (int) $curatorGroups[1]['id'] : null;
        $teacher['curator_group_number'] = implode(', ', array_map(
            static fn (array $group): string => (string) $group['number'],
            $curatorGroups
        ));
        if ($teacher['curator_group_number'] === '') {
            $teacher['curator_group_number'] = null;
        }
        $teacher['specialty_head_id'] = in_array('specialty_head', $teacher['staff_roles'], true)
            ? get_user_specialty_head_id((int) $teacher['id'])
            : null;
        $teacher['specialty_head_label'] = format_specialty_head_label($teacher['specialty_head_id']);
    }
    unset($teacher);

    return $teachers;
}

function get_teacher_by_id(int $id): ?array
{
    $stmt = db()->prepare(
        "SELECT id, email, full_name, phone, password_plain, is_active, created_at
         FROM users
         WHERE id = ? AND role = 'teacher'
         LIMIT 1"
    );
    $stmt->execute([$id]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        return null;
    }

    $teacher['phone'] = (string) ($teacher['phone'] ?? '');
    $teacher['staff_roles'] = get_user_staff_roles($id);
    $curatorGroups = in_array('curator', $teacher['staff_roles'], true)
        ? get_groups_for_curator($id)
        : [];
    $teacher['curator_group_id'] = isset($curatorGroups[0]) ? (int) $curatorGroups[0]['id'] : null;
    $teacher['curator_group_id_2'] = isset($curatorGroups[1]) ? (int) $curatorGroups[1]['id'] : null;
    $teacher['curator_group_number'] = implode(', ', array_map(
        static fn (array $group): string => (string) $group['number'],
        $curatorGroups
    ));
    if ($teacher['curator_group_number'] === '') {
        $teacher['curator_group_number'] = null;
    }
    $teacher['specialty_head_id'] = in_array('specialty_head', $teacher['staff_roles'], true)
        ? get_user_specialty_head_id($id)
        : null;
    $teacher['specialty_head_label'] = format_specialty_head_label($teacher['specialty_head_id']);

    return $teacher;
}

function create_teacher(
    string $email,
    string $password,
    string $fullName,
    array $staffRoles,
    ?int $curatorGroupId = null,
    string $phone = '',
    ?int $curatorGroupId2 = null,
    ?int $specialtyHeadId = null
): array {
    $rolesCheck = validate_staff_roles($staffRoles);
    if (!$rolesCheck['success']) {
        return $rolesCheck;
    }
    $staffRoles = $rolesCheck['roles'];

    $specialtyCheck = validate_specialty_head_roles($staffRoles, $specialtyHeadId);
    if (!$specialtyCheck['success']) {
        return $specialtyCheck;
    }

    $result = register_user($email, $password, $fullName, 'teacher', $staffRoles, $phone);
    if (!$result['success']) {
        return $result;
    }

    $userId = (int) $result['user_id'];

    $groupResult = sync_curator_groups($userId, $staffRoles, curator_group_ids_from_values($curatorGroupId, $curatorGroupId2));
    if (!$groupResult['success']) {
        $stmt = db()->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$userId]);
        return $groupResult;
    }

    $specialtyResult = sync_specialty_head($userId, $staffRoles, $specialtyHeadId);
    if (!$specialtyResult['success']) {
        assign_curator_group($userId, null);
        $stmt = db()->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$userId]);
        return $specialtyResult;
    }

    return $result;
}

function update_teacher(
    int $id,
    string $email,
    string $fullName,
    bool $isActive,
    array $staffRoles,
    ?string $password = null,
    ?int $curatorGroupId = null,
    string $phone = '',
    ?int $curatorGroupId2 = null,
    ?int $specialtyHeadId = null
): array {
    $teacher = get_teacher_by_id($id);

    if ($teacher === null) {
        return ['success' => false, 'error' => 'Преподаватель не найден.'];
    }

    $rolesCheck = validate_staff_roles($staffRoles);
    if (!$rolesCheck['success']) {
        return ['success' => false, 'error' => $rolesCheck['error']];
    }
    $staffRoles = $rolesCheck['roles'];

    $specialtyCheck = validate_specialty_head_roles($staffRoles, $specialtyHeadId);
    if (!$specialtyCheck['success']) {
        return $specialtyCheck;
    }

    $fullName = trim($fullName);
    $phone = format_login_phone($phone);

    if (!is_valid_login_phone($phone)) {
        return ['success' => false, 'error' => 'Укажите корректный номер телефона (логин).'];
    }

    $emailResult = resolve_user_email($email, $phone);
    if (!$emailResult['success']) {
        return $emailResult;
    }
    $email = $emailResult['email'];

    if (mb_strlen($fullName) < 2) {
        return ['success' => false, 'error' => 'ФИО должно содержать минимум 2 символа.'];
    }

    if (find_user_by_phone($phone, $id) !== null) {
        return ['success' => false, 'error' => 'Пользователь с таким телефоном уже существует.'];
    }

    $existing = find_user_by_email($email);
    if ($existing !== null && (int) $existing['id'] !== $id) {
        return ['success' => false, 'error' => 'Пользователь с таким email уже существует.'];
    }

    if ($password !== null && $password !== '') {
        if (mb_strlen($password) < 6) {
            return ['success' => false, 'error' => 'Пароль должен быть не короче 6 символов.'];
        }

        $stmt = db()->prepare(
            'UPDATE users SET email = ?, full_name = ?, phone = ?, is_active = ?, password_hash = ?, password_plain = ? WHERE id = ?'
        );
        $stmt->execute([
            $email,
            $fullName,
            $phone,
            $isActive ? 1 : 0,
            password_hash($password, PASSWORD_DEFAULT),
            $password,
            $id,
        ]);
    } else {
        $stmt = db()->prepare(
            'UPDATE users SET email = ?, full_name = ?, phone = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([$email, $fullName, $phone, $isActive ? 1 : 0, $id]);
    }

    set_user_staff_roles($id, $staffRoles);

    $groupResult = sync_curator_groups($id, $staffRoles, curator_group_ids_from_values($curatorGroupId, $curatorGroupId2));
    if (!$groupResult['success']) {
        return $groupResult;
    }

    $specialtyResult = sync_specialty_head($id, $staffRoles, $specialtyHeadId);
    if (!$specialtyResult['success']) {
        return $specialtyResult;
    }

    return ['success' => true];
}

function admin_set_teacher_password(int $id, string $password, string $passwordConfirm): array
{
    $teacher = get_teacher_by_id($id);
    if ($teacher === null) {
        return ['success' => false, 'error' => 'Преподаватель не найден.'];
    }

    if (mb_strlen($password) < 6) {
        return ['success' => false, 'error' => 'Пароль должен быть не короче 6 символов.'];
    }

    if ($password !== $passwordConfirm) {
        return ['success' => false, 'error' => 'Пароли не совпадают.'];
    }

    $stmt = db()->prepare(
        'UPDATE users SET password_hash = ?, password_plain = ? WHERE id = ? AND role = \'teacher\''
    );
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $password, $id]);

    return ['success' => true];
}

function regenerate_teacher_password(int $id): array
{
    $teacher = get_teacher_by_id($id);
    if ($teacher === null) {
        return ['success' => false, 'error' => 'Преподаватель не найден.'];
    }

    $password = generate_user_password();
    $stmt = db()->prepare(
        'UPDATE users SET password_hash = ?, password_plain = ? WHERE id = ? AND role = \'teacher\''
    );
    $stmt->execute([
        password_hash($password, PASSWORD_DEFAULT),
        $password,
        $id,
    ]);

    return [
        'success' => true,
        'login' => (string) ($teacher['phone'] ?? ''),
        'password' => $password,
    ];
}

function delete_teacher(int $id): array
{
    $teacher = get_teacher_by_id($id);

    if ($teacher === null) {
        return ['success' => false, 'error' => 'Преподаватель не найден.'];
    }

    assign_curator_group($id, null);
    assign_user_specialty_head($id, null);

    $stmt = db()->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
    $stmt->execute([$id]);

    return ['success' => true];
}

function set_teacher_auth_credentials_sent(int $id, bool $sent): array
{
    $teacher = get_teacher_by_id($id);
    if ($teacher === null) {
        return ['success' => false, 'error' => 'Преподаватель не найден.'];
    }

    $stmt = db()->prepare(
        "UPDATE users SET auth_credentials_sent = ? WHERE id = ? AND role = 'teacher'"
    );
    $stmt->execute([$sent ? 1 : 0, $id]);

    return ['success' => true, 'sent' => $sent];
}

function format_date(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '—';
    }

    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return $datetime;
    }

    return date('d.m.Y H:i', $timestamp);
}

function posted_staff_roles(): array
{
    $roles = $_POST['staff_roles'] ?? [];

    return is_array($roles) ? normalize_staff_roles($roles) : [];
}

function posted_curator_group_id(): ?int
{
    $value = (int) ($_POST['curator_group_id'] ?? 0);

    return $value > 0 ? $value : null;
}

function posted_curator_group_id_2(): ?int
{
    $value = (int) ($_POST['curator_group_id_2'] ?? 0);

    return $value > 0 ? $value : null;
}

function posted_curator_group_ids(): array
{
    return curator_group_ids_from_values(posted_curator_group_id(), posted_curator_group_id_2());
}

function posted_specialty_head_id(): ?int
{
    $value = (int) ($_POST['specialty_head_id'] ?? 0);

    return $value > 0 ? $value : null;
}

function curator_group_ids_from_values(?int $groupId, ?int $groupId2 = null): array
{
    $ids = [];
    if ($groupId !== null && $groupId > 0) {
        $ids[] = $groupId;
    }
    if ($groupId2 !== null && $groupId2 > 0 && $groupId2 !== ($ids[0] ?? null)) {
        $ids[] = $groupId2;
    }

    return $ids;
}
