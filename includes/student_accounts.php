<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/students.php';

function curator_show_student_auth_data(): bool
{
    return is_admin();
}

function transliterate_to_login(string $text): string
{
    $map = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
        'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];

    $text = mb_strtolower(trim($text), 'UTF-8');
    $out = '';
    $len = mb_strlen($text, 'UTF-8');
    for ($i = 0; $i < $len; $i++) {
        $ch = mb_substr($text, $i, 1, 'UTF-8');
        if (isset($map[$ch])) {
            $out .= $map[$ch];
        } elseif (preg_match('/[a-z0-9]/', $ch)) {
            $out .= $ch;
        } elseif ($ch === ' ' || $ch === '-' || $ch === '_') {
            $out .= '.';
        }
    }

    $out = preg_replace('/\.+/', '.', $out) ?? $out;
    $out = trim($out, '.');

    return $out !== '' ? $out : 'student';
}

function generate_student_password(int $length = 8): string
{
    return generate_user_password($length);
}

function generate_student_login(string $lastName, string $firstName, string $middleName = ''): string
{
    $chunks = [];
    foreach ([$lastName, $firstName, $middleName] as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $chunks[] = transliterate_to_login($part);
    }

    $base = $chunks !== [] ? implode('.', $chunks) : 'student';
    if (mb_strlen($base) > 40) {
        $base = rtrim(mb_substr($base, 0, 40), '.');
    }

    $login = $base;
    $n = 0;
    while (is_student_login_taken($login)) {
        $n++;
        $login = $base . $n;
        if ($n > 9999) {
            $login = $base . '.' . bin2hex(random_bytes(3));
            break;
        }
    }

    return mb_strtolower($login) . '@student.local';
}

function is_student_login_taken(string $loginLocal): bool
{
    $email = mb_strtolower(trim($loginLocal)) . '@student.local';
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);

    return (bool) $stmt->fetch();
}

function get_student_by_user_id(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT s.*, g.number AS group_number, sp.name AS specialty_name
         FROM students s
         INNER JOIN study_groups g ON g.id = s.group_id
         INNER JOIN specialties sp ON sp.id = g.specialty_id
         WHERE s.user_id = ?
         LIMIT 1'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function get_student_account(int $studentId): ?array
{
    $stmt = db()->prepare(
        'SELECT u.id, u.email, u.full_name, u.password_plain, u.is_active, u.phone, u.avatar
         FROM students s
         INNER JOIN users u ON u.id = s.user_id
         WHERE s.id = ?
         LIMIT 1'
    );
    $stmt->execute([$studentId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function create_student_user_account(int $studentId, string $fullName, string $phone = ''): array
{
    $student = get_student_by_id($studentId);
    if ($student === null) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    if (!empty($student['user_id'])) {
        return ['success' => false, 'error' => 'Учётная запись уже создана.'];
    }

    $parts = split_person_full_name($fullName);
    $password = generate_student_password();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo = db();
    $maxAttempts = 8;
    $login = '';
    $userId = 0;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $login = generate_student_login(
            $parts['last_name'],
            $parts['first_name'],
            $parts['middle_name']
        );

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (email, password_hash, password_plain, full_name, phone, role, is_active)
                 VALUES (?, ?, ?, ?, ?, \'student\', 1)'
            );
            $stmt->execute([
                $login,
                $passwordHash,
                $password,
                $fullName,
                $phone,
            ]);
            $userId = (int) $pdo->lastInsertId();

            $pdo->prepare('UPDATE students SET user_id = ? WHERE id = ?')
                ->execute([$userId, $studentId]);

            $pdo->commit();
            break;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $duplicate = $e instanceof PDOException
                && (string) $e->getCode() === '23000';
            if (!$duplicate || $attempt === $maxAttempts) {
                return ['success' => false, 'error' => 'Не удалось создать учётную запись студента.'];
            }
        }
    }

    if ($userId <= 0) {
        return ['success' => false, 'error' => 'Не удалось создать учётную запись студента.'];
    }

    return [
        'success' => true,
        'user_id' => $userId,
        'login' => $login,
        'password' => $password,
    ];
}

function regenerate_student_password(int $studentId): array
{
    $account = get_student_account($studentId);
    if ($account === null) {
        return ['success' => false, 'error' => 'Учётная запись студента не найдена.'];
    }

    $password = generate_student_password();
    $stmt = db()->prepare(
        'UPDATE users SET password_hash = ?, password_plain = ? WHERE id = ? AND role = \'student\''
    );
    $stmt->execute([
        password_hash($password, PASSWORD_DEFAULT),
        $password,
        (int) $account['id'],
    ]);

    return [
        'success' => true,
        'login' => (string) $account['email'],
        'password' => $password,
    ];
}

function ensure_student_account(int $studentId): array
{
    $student = get_student_by_id($studentId);
    if ($student === null) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    if (!empty($student['user_id'])) {
        $account = get_student_account($studentId);
        if ($account === null) {
            return ['success' => false, 'error' => 'Учётная запись не найдена.'];
        }

        return [
            'success' => true,
            'login' => (string) $account['email'],
            'password' => (string) ($account['password_plain'] ?? ''),
            'created' => false,
        ];
    }

    $result = create_student_user_account(
        $studentId,
        (string) $student['full_name'],
        (string) ($student['phone'] ?? '')
    );
    if (!$result['success']) {
        return $result;
    }

    $result['created'] = true;

    return $result;
}

function is_student(): bool
{
    return is_student_user();
}

function require_student(): void
{
    require_login();

    if (!is_student_user()) {
        http_response_code(403);
        exit('Доступ запрещён. Требуется учётная запись студента.');
    }
}

function current_student(): ?array
{
    $user = current_user();
    if ($user === null || ($user['role'] ?? '') !== 'student') {
        return null;
    }

    return get_student_by_user_id((int) $user['id']);
}
