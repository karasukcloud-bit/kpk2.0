<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/teachers.php';
require_once __DIR__ . '/curriculum.php';
require_once __DIR__ . '/gradebook.php';

function update_own_profile(array $data, array $files = []): array
{
    $user = current_user();
    if ($user === null) {
        return ['success' => false, 'error' => 'Необходима авторизация.'];
    }

    $fullName = compose_person_full_name(
        (string) ($data['last_name'] ?? ''),
        (string) ($data['first_name'] ?? ''),
        (string) ($data['middle_name'] ?? '')
    );
    $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
    $phone = trim((string) ($data['phone'] ?? ''));
    $position = trim((string) ($data['position'] ?? ''));
    $education = trim((string) ($data['education'] ?? ''));
    $additionalInfo = trim((string) ($data['additional_info'] ?? ''));

    if (trim((string) ($data['last_name'] ?? '')) === '' || trim((string) ($data['first_name'] ?? '')) === '') {
        return ['success' => false, 'error' => 'Укажите фамилию и имя.'];
    }

    if ($fullName === '') {
        return ['success' => false, 'error' => 'Укажите ФИО.'];
    }

    if (!is_valid_login_phone($phone)) {
        return ['success' => false, 'error' => 'Укажите корректный номер телефона (логин).'];
    }

    $emailResult = resolve_user_email($email, $phone);
    if (!$emailResult['success']) {
        return $emailResult;
    }
    $email = $emailResult['email'];

    if (find_user_by_phone($phone, (int) $user['id']) !== null) {
        return ['success' => false, 'error' => 'Этот телефон уже используется.'];
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
    $stmt->execute([$email, (int) $user['id']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Этот email уже используется.'];
    }

    $stmt = db()->prepare(
        'UPDATE users
         SET full_name = ?, email = ?, phone = ?, position = ?, education = ?, additional_info = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $fullName,
        $email,
        $phone,
        $position,
        $education,
        $additionalInfo,
        (int) $user['id'],
    ]);

    clear_current_user_cache();
    current_user();

    if (isset($data['avatar_mode'])) {
        $avatarResult = update_own_avatar($data, $files);
        if (!$avatarResult['success']) {
            return $avatarResult;
        }
    }

    return ['success' => true];
}

function delete_user_avatar_file(string $avatar): void
{
    if (strpos($avatar, 'file:') !== 0) {
        return;
    }

    $file = basename(substr($avatar, 5));
    if ($file === '') {
        return;
    }

    $path = avatar_upload_dir() . '/' . $file;
    if (is_file($path)) {
        @unlink($path);
    }
}

function update_own_avatar(array $post, array $files): array
{
    $user = current_user();
    if ($user === null) {
        return ['success' => false, 'error' => 'Необходима авторизация.'];
    }

    $userId = (int) $user['id'];
    $current = normalize_avatar_value((string) ($user['avatar'] ?? 'icon:person'));
    $mode = (string) ($post['avatar_mode'] ?? 'icon');

    if ($mode === 'upload') {
        $file = $files['avatar_file'] ?? null;
        $hasNewFile = is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if (!$hasNewFile) {
            if (strpos($current, 'file:') === 0) {
                return ['success' => true];
            }

            return ['success' => false, 'error' => 'Выберите файл изображения.'];
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Не удалось загрузить файл.'];
        }

        if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return ['success' => false, 'error' => 'Размер файла не должен превышать 2 МБ.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $info = @getimagesize($tmp);
        if ($info === false) {
            return ['success' => false, 'error' => 'Файл должен быть изображением (JPG, PNG, WEBP или GIF).'];
        }

        $mimeMap = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
        ];
        $type = (int) ($info[2] ?? 0);
        if (!isset($mimeMap[$type])) {
            return ['success' => false, 'error' => 'Допустимы только JPG, PNG, WEBP или GIF.'];
        }

        $dir = avatar_upload_dir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['success' => false, 'error' => 'Не удалось создать папку для загрузок.'];
        }

        $filename = 'u' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $mimeMap[$type];
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($tmp, $dest)) {
            return ['success' => false, 'error' => 'Не удалось сохранить файл.'];
        }

        $newAvatar = 'file:' . $filename;
        if ($current !== $newAvatar) {
            delete_user_avatar_file($current);
        }
    } else {
        $icon = (string) ($post['avatar_icon'] ?? 'person');
        if (!array_key_exists($icon, avatar_presets())) {
            return ['success' => false, 'error' => 'Выберите одну из предложенных иконок.'];
        }

        $newAvatar = 'icon:' . $icon;
        if (strpos($current, 'file:') === 0) {
            delete_user_avatar_file($current);
        }
    }

    $stmt = db()->prepare('UPDATE users SET avatar = ? WHERE id = ?');
    $stmt->execute([$newAvatar, $userId]);

    clear_current_user_cache();
    current_user();

    return ['success' => true];
}

function change_own_password(string $currentPassword, string $newPassword, string $confirmPassword): array
{
    $user = current_user();
    if ($user === null) {
        return ['success' => false, 'error' => 'Необходима авторизация.'];
    }

    if ($newPassword === '') {
        return ['success' => false, 'error' => 'Укажите новый пароль.'];
    }

    if (mb_strlen($newPassword) < 6) {
        return ['success' => false, 'error' => 'Пароль должен быть не короче 6 символов.'];
    }

    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'error' => 'Пароли не совпадают.'];
    }

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $user['id']]);
    $hash = $stmt->fetchColumn();
    if ($hash === false || !password_verify($currentPassword, (string) $hash)) {
        return ['success' => false, 'error' => 'Текущий пароль неверен.'];
    }

    $stmt = db()->prepare(
        'UPDATE users SET password_hash = ?, password_plain = IF(role = \'student\', ?, password_plain) WHERE id = ?'
    );
    $stmt->execute([
        password_hash($newPassword, PASSWORD_DEFAULT),
        $newPassword,
        (int) $user['id'],
    ]);

    return ['success' => true];
}

function get_teacher_assigned_subjects(int $teacherId, ?string $academicYear = null): array
{
    $academicYear = normalize_academic_year($academicYear ?? get_default_academic_year())
        ?? get_default_academic_year();

    $stmt = db()->prepare(
        'SELECT ci.id AS curriculum_item_id, ci.semester, ci.teacher_id,
                sub.name AS subject_name,
                g.id AS group_id, g.number AS group_number,
                sp.name AS specialty_name,
                cp.academic_year,
                (SELECT COUNT(*) FROM ktp_topics kt WHERE kt.curriculum_item_id = ci.id) AS ktp_count
         FROM curriculum_items ci
         INNER JOIN subjects sub ON sub.id = ci.subject_id
         INNER JOIN curriculum_plans cp ON cp.id = ci.curriculum_plan_id
         INNER JOIN study_groups g ON g.id = cp.group_id
         INNER JOIN specialties sp ON sp.id = g.specialty_id
         WHERE ci.teacher_id = ? AND cp.academic_year = ?
         ORDER BY g.number ASC, sub.name ASC, ci.semester ASC'
    );
    $stmt->execute([$teacherId, $academicYear]);

    return $stmt->fetchAll();
}

function can_manage_item_ktp(int $curriculumItemId): bool
{
    if (!is_logged_in()) {
        return false;
    }

    if (is_admin() || can_manage_curriculum()) {
        return true;
    }

    if (!can_use_teacher_panel()) {
        return false;
    }

    $item = get_curriculum_item_by_id($curriculumItemId);
    if ($item === null) {
        return false;
    }

    $user = current_user();

    return $user !== null && (int) ($item['teacher_id'] ?? 0) === (int) $user['id'];
}
