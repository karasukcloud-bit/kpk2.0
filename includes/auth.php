<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/roles.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

require_once __DIR__ . '/avatars.php';

function user_first_middle_name(string $fullName): string
{
    $parts = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($parts) || $parts === []) {
        return '';
    }

    if (count($parts) === 1) {
        return $parts[0];
    }

    $first = $parts[1];
    $middle = isset($parts[2]) ? implode(' ', array_slice($parts, 2)) : '';

    return trim($first . ($middle !== '' ? ' ' . $middle : ''));
}

function normalize_avatar_value(string $avatar): string
{
    $avatar = trim($avatar);
    if ($avatar === '') {
        return 'icon:person';
    }

    if (strpos($avatar, 'icon:') === 0) {
        $key = resolve_avatar_icon_key(substr($avatar, 5));

        return 'icon:' . $key;
    }

    if (strpos($avatar, 'file:') === 0) {
        $file = basename(substr($avatar, 5));
        if ($file !== '' && is_file(avatar_upload_dir() . '/' . $file)) {
            return 'file:' . $file;
        }

        return 'icon:person';
    }

    return 'icon:person';
}

function avatar_upload_dir(): string
{
    return dirname(__DIR__) . '/uploads/avatars';
}

function user_avatar_src(string $avatar, string $basePath = ''): ?string
{
    $avatar = normalize_avatar_value($avatar);
    if (strpos($avatar, 'file:') !== 0) {
        return null;
    }

    $prefix = $basePath !== '' ? rtrim(str_replace('\\', '/', $basePath), '/') . '/' : '';

    return $prefix . 'uploads/avatars/' . rawurlencode(substr($avatar, 5));
}

function user_avatar_icon_key(string $avatar): string
{
    $avatar = normalize_avatar_value($avatar);
    if (strpos($avatar, 'icon:') === 0) {
        return resolve_avatar_icon_key(substr($avatar, 5));
    }

    return 'person';
}

function render_user_avatar(string $avatar, string $extraClass = '', string $basePath = ''): string
{
    $avatar = normalize_avatar_value($avatar);
    $class = trim('user-avatar ' . $extraClass);
    $src = user_avatar_src($avatar, $basePath);

    if ($src !== null) {
        return '<span class="' . e($class) . ' user-avatar--photo">'
            . '<img src="' . e($src) . '" alt="" width="40" height="40">'
            . '</span>';
    }

    $icon = user_avatar_icon_key($avatar);

    return '<span class="' . e($class) . ' user-avatar--icon user-avatar--' . e($icon) . '" aria-hidden="true">'
        . avatar_icon_svg($icon)
        . '</span>';
}

function app_base_path(): string
{
    $dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');

    foreach (['/admin', '/deputy', '/curator', '/teacher', '/educator', '/student', '/curriculum'] as $sub) {
        if (str_ends_with($dir, $sub)) {
            $dir = dirname($dir);
            break;
        }
    }

    return rtrim(str_replace('\\', '/', $dir), '/') . '/';
}

/** Текущая панель по URL: admin|curator|teacher|deputy|educator|cabinet */
function current_header_panel(): ?string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    if (preg_match('#/(admin|curator|teacher|deputy|educator|student)(/|$)#', $script, $matches)) {
        return $matches[1];
    }

    if (basename($script) === 'cabinet.php') {
        return 'cabinet';
    }

    return null;
}

function redirect_to(string $page): void
{
    header('Location: ' . app_base_path() . ltrim($page, '/'));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    if (!array_key_exists('_current_user_cache', $GLOBALS)) {
        $stmt = db()->prepare(
            'SELECT id, email, full_name, role, is_active,
                    phone, position, education, additional_info, avatar
             FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int) $_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;

        if ($user === null || !(int) $user['is_active']) {
            logout_user();
            return null;
        }

        $user['phone'] = (string) ($user['phone'] ?? '');
        $user['position'] = (string) ($user['position'] ?? '');
        $user['education'] = (string) ($user['education'] ?? '');
        $user['additional_info'] = (string) ($user['additional_info'] ?? '');
        $user['avatar'] = (string) ($user['avatar'] ?? 'icon:person');
        if ($user['avatar'] === '') {
            $user['avatar'] = 'icon:person';
        }

        if ($user['role'] === 'admin') {
            $user['staff_roles'] = [];
        } elseif ($user['role'] === 'student') {
            $user['staff_roles'] = [];
        } else {
            $user['staff_roles'] = get_user_staff_roles((int) $user['id']);
        }

        $GLOBALS['_current_user_cache'] = $user;
    }

    return $GLOBALS['_current_user_cache'];
}

function clear_current_user_cache(): void
{
    unset($GLOBALS['_current_user_cache']);
}

function current_user_staff_roles(): array
{
    $user = current_user();

    return $user ? ($user['staff_roles'] ?? []) : [];
}

function has_role(string $role): bool
{
    if (is_admin()) {
        return true;
    }

    return in_array($role, current_user_staff_roles(), true);
}

function has_any_role(array $roles): bool
{
    if (is_admin()) {
        return true;
    }

    foreach ($roles as $role) {
        if (has_role($role)) {
            return true;
        }
    }

    return false;
}

function require_role(string $role): void
{
    require_login();

    if (!has_role($role)) {
        http_response_code(403);
        exit('Доступ запрещён. Недостаточно прав.');
    }
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

function is_student_user(): bool
{
    $user = current_user();

    return $user !== null && ($user['role'] ?? '') === 'student';
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect_to('login.php');
    }
}

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        exit('Доступ запрещён. Требуются права администратора.');
    }
}

function can_manage_curriculum(): bool
{
    return is_admin() || has_role('deputy');
}

function require_curriculum_manager(): void
{
    require_login();

    if (!can_manage_curriculum()) {
        http_response_code(403);
        exit('Доступ запрещён. Требуются права завуча или администратора.');
    }
}

function is_staff_deputy(): bool
{
    return !is_admin() && in_array('deputy', current_user_staff_roles(), true);
}

function is_staff_curator(): bool
{
    return !is_admin() && in_array('curator', current_user_staff_roles(), true);
}

function can_use_curator_panel(): bool
{
    return !is_student_user() && (is_admin() || has_role('curator'));
}

function require_curator_panel(): void
{
    require_login();

    if (!can_use_curator_panel()) {
        http_response_code(403);
        exit('Доступ запрещён. Требуются права куратора или администратора.');
    }
}

function is_staff_teacher(): bool
{
    return !is_admin() && in_array('teacher', current_user_staff_roles(), true);
}

function can_use_teacher_panel(): bool
{
    return !is_student_user() && (is_admin() || in_array('teacher', current_user_staff_roles(), true));
}

function require_teacher_panel(): void
{
    require_login();

    if (!can_use_teacher_panel()) {
        http_response_code(403);
        exit('Доступ запрещён. Требуются права преподавателя.');
    }
}

function can_use_deputy_panel(): bool
{
    return !is_student_user() && (is_admin() || in_array('deputy', current_user_staff_roles(), true));
}

function can_manage_archives(): bool
{
    return is_admin() || can_use_deputy_panel();
}

function can_delete_archives(): bool
{
    return is_admin();
}

function require_archive_manager(): void
{
    require_login();

    if (!can_manage_archives()) {
        http_response_code(403);
        exit('Доступ запрещён. Требуются права завуча или администратора.');
    }
}

function can_use_educator_panel(): bool
{
    return !is_student_user() && (is_admin() || in_array('educator', current_user_staff_roles(), true));
}

function require_educator_panel(): void
{
    require_login();

    if (!can_use_educator_panel()) {
        http_response_code(403);
        exit('Доступ запрещён. Требуются права воспитателя или администратора.');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_role'] = $user['role'];
}

function logout_user(): void
{
    clear_current_user_cache();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function normalize_phone_digits(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) === 11 && $digits[0] === '8') {
        $digits = '7' . substr($digits, 1);
    }

    return $digits;
}

function format_login_phone(string $phone): string
{
    $digits = normalize_phone_digits($phone);
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) === 10) {
        $digits = '7' . $digits;
    }

    return '+' . $digits;
}

function is_valid_login_phone(string $phone): bool
{
    $digits = normalize_phone_digits($phone);

    return strlen($digits) >= 10 && strlen($digits) <= 15;
}

function is_phone_placeholder_email(string $email): bool
{
    return (bool) preg_match('/@phone\.local$/i', $email);
}

function display_user_email(?string $email): string
{
    $email = trim((string) $email);
    if ($email === '' || is_phone_placeholder_email($email)) {
        return '';
    }

    return $email;
}

function email_from_phone(string $phone): string
{
    return normalize_phone_digits($phone) . '@phone.local';
}

function resolve_user_email(string $email, string $phone): array
{
    $email = mb_strtolower(trim($email));
    $phone = trim($phone);

    if ($email === '') {
        if (!is_valid_login_phone($phone)) {
            return ['success' => false, 'error' => 'Укажите корректный номер телефона.'];
        }

        return ['success' => true, 'email' => email_from_phone($phone)];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Некорректный email.'];
    }

    return ['success' => true, 'email' => $email];
}

function generate_user_password(int $length = 8): string
{
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $max = strlen($chars) - 1;
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }

    return $password;
}

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([mb_strtolower(trim($email))]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function find_user_by_phone(string $phone, ?int $excludeUserId = null): ?array
{
    $digits = normalize_phone_digits($phone);
    if ($digits === '' || strlen($digits) < 10) {
        return null;
    }

    $stmt = db()->query("SELECT * FROM users WHERE phone <> ''");
    foreach ($stmt->fetchAll() as $user) {
        if ($excludeUserId !== null && (int) $user['id'] === $excludeUserId) {
            continue;
        }
        if (normalize_phone_digits((string) $user['phone']) === $digits) {
            return $user;
        }
    }

    return null;
}

function register_user(
    string $email,
    string $password,
    string $fullName,
    string $role = 'teacher',
    array $staffRoles = ['teacher'],
    string $phone = ''
): array {
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

    if (mb_strlen($password) < 6) {
        return ['success' => false, 'error' => 'Пароль должен быть не короче 6 символов.'];
    }

    if (!in_array($role, ['admin', 'teacher'], true)) {
        return ['success' => false, 'error' => 'Недопустимая роль пользователя.'];
    }

    if ($role === 'teacher') {
        $rolesCheck = validate_staff_roles($staffRoles);
        if (!$rolesCheck['success']) {
            return ['success' => false, 'error' => $rolesCheck['error']];
        }
        $staffRoles = $rolesCheck['roles'];
    }

    if (find_user_by_phone($phone)) {
        return ['success' => false, 'error' => 'Пользователь с таким телефоном уже зарегистрирован.'];
    }

    if (find_user_by_email($email)) {
        return ['success' => false, 'error' => 'Пользователь с таким email уже зарегистрирован.'];
    }

    $stmt = db()->prepare(
        'INSERT INTO users (email, password_hash, password_plain, full_name, phone, role)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $password,
        $fullName,
        $phone,
        $role,
    ]);

    $userId = (int) db()->lastInsertId();

    if ($role === 'teacher') {
        set_user_staff_roles($userId, $staffRoles);
    }

    return ['success' => true, 'user_id' => $userId];
}

function authenticate_user(string $login, string $password): array
{
    $login = trim($login);
    $user = null;

    if (is_valid_login_phone($login) || normalize_phone_digits($login) !== '') {
        $user = find_user_by_phone($login);
    }

    if ($user === null && $login !== '') {
        $emailLogin = mb_strtolower($login);
        if (strpos($emailLogin, '@') === false) {
            $emailLogin .= '@student.local';
        }
        $user = find_user_by_email($emailLogin);
    }

    if ($user === null) {
        return ['success' => false, 'error' => 'Неверный телефон или пароль.'];
    }

    if (!(int) $user['is_active']) {
        return ['success' => false, 'error' => 'Учётная запись заблокирована. Обратитесь к администратору.'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Неверный телефон или пароль.'];
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }

    login_user($user);

    return ['success' => true];
}

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

function role_label(string $role): string
{
    if ($role === 'admin') {
        return 'Администратор';
    }

    return staff_role_label($role);
}

function display_user_roles(?array $user): string
{
    if ($user === null) {
        return '—';
    }

    if ($user['role'] === 'admin') {
        return role_label('admin');
    }

    if (($user['role'] ?? '') === 'student') {
        return 'Студент';
    }

    return format_staff_roles_list($user['staff_roles'] ?? []);
}
