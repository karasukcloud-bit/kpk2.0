<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/organization.php';
require_once __DIR__ . '/curriculum.php';

function compose_person_full_name(string $lastName, string $firstName, string $middleName = ''): string
{
    $parts = array_values(array_filter([
        trim($lastName),
        trim($firstName),
        trim($middleName),
    ], static function (string $part): bool {
        return $part !== '';
    }));

    return implode(' ', $parts);
}

function split_person_full_name(string $fullName): array
{
    $parts = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($parts)) {
        $parts = [];
    }

    return [
        'last_name' => $parts[0] ?? '',
        'first_name' => $parts[1] ?? '',
        'middle_name' => isset($parts[2]) ? implode(' ', array_slice($parts, 2)) : '',
    ];
}

function person_last_first_name(string $fullName): string
{
    $parts = split_person_full_name($fullName);
    $name = trim($parts['last_name'] . ' ' . $parts['first_name']);

    return $name !== '' ? $name : trim($fullName);
}

function get_group_id_for_curator(int $userId): ?int
{
    $stmt = db()->prepare('SELECT id FROM study_groups WHERE curator_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    return $row ? (int) $row['id'] : null;
}

function get_curator_group(int $userId): ?array
{
    $groupId = get_group_id_for_curator($userId);
    if ($groupId === null) {
        return null;
    }

    return get_group_by_id($groupId);
}

function assign_curator_group(int $userId, ?int $groupId): array
{
    $pdo = db();
    $pdo->prepare('UPDATE study_groups SET curator_id = NULL WHERE curator_id = ?')
        ->execute([$userId]);

    if ($groupId === null || $groupId === 0) {
        return ['success' => true];
    }

    $group = get_group_by_id($groupId);
    if ($group === null) {
        return ['success' => false, 'error' => 'Группа не найдена.'];
    }

    $currentCurator = (int) ($group['curator_id'] ?? 0);
    if ($currentCurator > 0 && $currentCurator !== $userId) {
        return ['success' => false, 'error' => 'Эта группа уже назначена другому куратору.'];
    }

    $stmt = $pdo->prepare('UPDATE study_groups SET curator_id = ? WHERE id = ?');
    $stmt->execute([$userId, $groupId]);

    return ['success' => true];
}

function sync_curator_group(int $userId, array $staffRoles, ?int $groupId): array
{
    if (!in_array('curator', $staffRoles, true)) {
        return assign_curator_group($userId, null);
    }

    if ($groupId === null || $groupId === 0) {
        return assign_curator_group($userId, null);
    }

    return assign_curator_group($userId, $groupId);
}

function render_curator_group_options(?int $selectedGroupId = null, ?int $forCuratorUserId = null): string
{
    $html = '<option value="">— Не назначена —</option>';

    foreach (get_all_groups() as $group) {
        $id = (int) $group['id'];
        $curatorId = (int) ($group['curator_id'] ?? 0);
        $taken = $curatorId > 0 && $curatorId !== (int) $forCuratorUserId;
        $selected = $id === (int) $selectedGroupId ? ' selected' : '';
        $disabled = $taken ? ' disabled' : '';
        $label = $group['number'] . ' · ' . $group['specialty_name'];

        if ($taken && !empty($group['curator_name'])) {
            $label .= ' (куратор: ' . $group['curator_name'] . ')';
        }

        $html .= '<option value="' . $id . '"' . $selected . $disabled . '>'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    return $html;
}

function get_curators(): array
{
    $stmt = db()->query(
        "SELECT u.id, u.full_name, u.email
         FROM users u
         INNER JOIN user_roles ur ON ur.user_id = u.id AND ur.role = 'curator'
         WHERE u.is_active = 1
         ORDER BY u.full_name ASC"
    );

    return $stmt->fetchAll();
}

function render_curator_options(?int $selectedId = null): string
{
    $html = '<option value="">— Не назначен —</option>';

    foreach (get_curators() as $curator) {
        $id = (int) $curator['id'];
        $selected = $id === (int) $selectedId ? ' selected' : '';
        $html .= '<option value="' . $id . '"' . $selected . '>'
            . htmlspecialchars($curator['full_name'], ENT_QUOTES, 'UTF-8')
            . '</option>';
    }

    return $html;
}

function get_groups_for_curator(?int $userId = null): array
{
    if (is_admin()) {
        return get_all_groups();
    }

    $userId = $userId ?? (int) (current_user()['id'] ?? 0);
    $stmt = db()->prepare(
        'SELECT g.id, g.number, g.specialty_id, g.curator_id, g.created_at,
                s.name AS specialty_name, s.code AS specialty_code,
                u.full_name AS curator_name
         FROM study_groups g
         INNER JOIN specialties s ON s.id = g.specialty_id
         LEFT JOIN users u ON u.id = g.curator_id
         WHERE g.curator_id = ?
         ORDER BY g.number ASC'
    );
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function can_manage_group(int $groupId): bool
{
    $group = get_group_by_id($groupId);
    if ($group === null) {
        return false;
    }

    if (is_admin()) {
        return true;
    }

    $user = current_user();

    return $user !== null && (int) ($group['curator_id'] ?? 0) === (int) $user['id'];
}

function require_group_access(int $groupId): array
{
    $group = get_group_by_id($groupId);
    if ($group === null || !can_manage_group($groupId)) {
        flash_set('error', 'Группа не найдена или нет доступа.');
        header('Location: group.php');
        exit;
    }

    return $group;
}

function get_students_by_group(int $groupId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM students WHERE group_id = ? ORDER BY full_name ASC'
    );
    $stmt->execute([$groupId]);

    return $stmt->fetchAll();
}

function get_student_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function student_gender_label(?string $gender): string
{
    if ($gender === 'male') {
        return 'Мужской';
    }
    if ($gender === 'female') {
        return 'Женский';
    }

    return '—';
}

function normalize_student_gender($gender): ?string
{
    $gender = trim((string) $gender);

    return ($gender === 'male' || $gender === 'female') ? $gender : null;
}

function normalize_student_birth_date($value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return null;
    }

    $parts = explode('-', $value);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        return null;
    }

    return $value;
}

function format_student_birth_date(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return $value;
    }

    return date('d.m.Y', $ts);
}

function student_age_years(?string $birthDate, ?DateTimeInterface $onDate = null): ?int
{
    $birthDate = normalize_student_birth_date($birthDate);
    if ($birthDate === null) {
        return null;
    }

    try {
        $birth = new DateTimeImmutable($birthDate);
        $on = $onDate instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($onDate)
            : new DateTimeImmutable('today');
    } catch (Throwable $e) {
        return null;
    }

    if ($birth > $on) {
        return null;
    }

    return (int) $birth->diff($on)->y;
}

function student_family_type_label(?string $type): string
{
    if ($type === 'complete') {
        return 'Полная семья';
    }
    if ($type === 'no_father') {
        return 'Неполная (без отца)';
    }
    if ($type === 'no_mother') {
        return 'Неполная (без матери)';
    }

    return '—';
}

function normalize_student_family_type($value): ?string
{
    $value = trim((string) $value);

    return in_array($value, ['complete', 'no_father', 'no_mother'], true) ? $value : null;
}

function student_residence_type_label(?string $type): string
{
    if ($type === 'family') {
        return 'С родителями / родственниками';
    }
    if ($type === 'dormitory') {
        return 'Общежитие';
    }
    if ($type === 'apartment') {
        return 'Квартира (наём)';
    }

    return '—';
}

function normalize_student_residence_type($value): ?string
{
    $value = trim((string) $value);

    return in_array($value, ['family', 'dormitory', 'apartment'], true) ? $value : null;
}

function student_dormitory_address(): string
{
    $org = get_organization();
    $address = trim((string) ($org['address'] ?? ''));

    return $address !== '' ? ('Общежитие, ' . $address) : 'Общежитие';
}

function compose_student_registered_address(array $data): string
{
    $parts = [];
    foreach (['address_region', 'address_district', 'address_locality', 'address_street'] as $key) {
        $value = trim((string) ($data[$key] ?? ''));
        if ($value !== '') {
            $parts[] = $value;
        }
    }
    $house = trim((string) ($data['address_house'] ?? ''));
    if ($house !== '') {
        $parts[] = (mb_stripos($house, 'д') === 0) ? $house : ('д. ' . $house);
    }

    return implode(', ', $parts);
}

function format_student_registered_address(array $student): string
{
    $composed = compose_student_registered_address($student);
    if ($composed !== '') {
        return $composed;
    }

    $legacy = trim((string) ($student['address_registered'] ?? ''));

    return $legacy !== '' ? $legacy : '—';
}

function student_geography_label(array $student): string
{
    $district = trim((string) ($student['address_district'] ?? ''));
    $locality = trim((string) ($student['address_locality'] ?? ''));
    if ($district !== '' && $locality !== '') {
        return $district . ', ' . $locality;
    }
    if ($district !== '') {
        return $district;
    }
    if ($locality !== '') {
        return $locality;
    }

    return trim((string) ($student['district'] ?? ''));
}

/**
 * Если фактический адрес пуст — подставить по месту проживания.
 */
function apply_student_auto_address(array $data): array
{
    $registered = compose_student_registered_address($data);
    if ($registered !== '') {
        $data['address_registered'] = $registered;
    }

    $geoDistrict = trim((string) ($data['address_district'] ?? ''));
    $geoLocality = trim((string) ($data['address_locality'] ?? ''));
    if ($geoDistrict !== '' || $geoLocality !== '') {
        $data['district'] = $geoDistrict !== '' ? $geoDistrict : $geoLocality;
    }

    $actual = trim((string) ($data['address_actual'] ?? ''));
    if ($actual !== '') {
        return $data;
    }

    $residence = normalize_student_residence_type($data['residence_type'] ?? null);
    if ($residence === 'family') {
        $data['address_actual'] = trim((string) ($data['address_registered'] ?? ''));
    } elseif ($residence === 'dormitory') {
        $data['address_actual'] = student_dormitory_address();
    }

    return $data;
}

function normalize_student_snils(?string $value): string
{
    $digits = preg_replace('/\D/u', '', trim((string) $value));
    if ($digits === null || $digits === '') {
        return '';
    }

    if (strlen((string) $digits) !== 11) {
        return '';
    }

    return substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6, 3) . ' ' . substr($digits, 9, 2);
}

function normalize_student_siblings_under_18($value): int
{
    $n = (int) $value;

    return max(0, min(20, $n));
}

function student_payload_from_post(array $post): array
{
    $lastName = trim((string) ($post['last_name'] ?? ''));
    $firstName = trim((string) ($post['first_name'] ?? ''));
    $middleName = trim((string) ($post['middle_name'] ?? ''));
    $birthRaw = trim((string) ($post['birth_date'] ?? ''));
    $snilsRaw = trim((string) ($post['snils'] ?? ''));
    $familyType = normalize_student_family_type($post['family_type'] ?? '');

    $data = [
        'last_name'          => $lastName,
        'first_name'         => $firstName,
        'middle_name'        => $middleName,
        'full_name'          => compose_person_full_name($lastName, $firstName, $middleName),
        'phone'              => trim((string) ($post['phone'] ?? '')),
        'snils'              => $snilsRaw,
        'birth_date'         => $birthRaw,
        'gender'             => trim((string) ($post['gender'] ?? '')),
        'mother_name'        => trim((string) ($post['mother_name'] ?? '')),
        'mother_phone'       => trim((string) ($post['mother_phone'] ?? '')),
        'mother_workplace'   => trim((string) ($post['mother_workplace'] ?? '')),
        'father_name'        => trim((string) ($post['father_name'] ?? '')),
        'father_phone'       => trim((string) ($post['father_phone'] ?? '')),
        'father_workplace'   => trim((string) ($post['father_workplace'] ?? '')),
        'address_region'     => trim((string) ($post['address_region'] ?? '')),
        'address_district'   => trim((string) ($post['address_district'] ?? '')),
        'address_locality'   => trim((string) ($post['address_locality'] ?? '')),
        'address_street'     => trim((string) ($post['address_street'] ?? '')),
        'address_house'      => trim((string) ($post['address_house'] ?? '')),
        'address_registered' => '',
        'address_actual'     => trim((string) ($post['address_actual'] ?? '')),
        'district'           => '',
        'is_low_income'      => !empty($post['is_low_income']),
        'without_parental_care' => !empty($post['without_parental_care']),
        'family_type'        => $familyType ?? '',
        'siblings_under_18'  => normalize_student_siblings_under_18($post['siblings_under_18'] ?? 0),
        'residence_type'     => trim((string) ($post['residence_type'] ?? '')),
        'is_nonresident'     => !empty($post['is_nonresident']),
    ];

    if ($familyType === 'no_father') {
        $data['father_name'] = '';
        $data['father_phone'] = '';
        $data['father_workplace'] = '';
    } elseif ($familyType === 'no_mother') {
        $data['mother_name'] = '';
        $data['mother_phone'] = '';
        $data['mother_workplace'] = '';
    }

    return $data;
}

function validate_student_data(array $data): array
{
    if (trim((string) ($data['last_name'] ?? '')) === '' || trim((string) ($data['first_name'] ?? '')) === '') {
        return ['success' => false, 'error' => 'Укажите фамилию и имя студента.'];
    }

    if (mb_strlen((string) ($data['full_name'] ?? '')) < 2) {
        return ['success' => false, 'error' => 'Укажите ФИО студента.'];
    }

    $birthRaw = trim((string) ($data['birth_date'] ?? ''));
    if ($birthRaw !== '' && normalize_student_birth_date($birthRaw) === null) {
        return ['success' => false, 'error' => 'Некорректная дата рождения.'];
    }

    $genderRaw = trim((string) ($data['gender'] ?? ''));
    if ($genderRaw !== '' && normalize_student_gender($genderRaw) === null) {
        return ['success' => false, 'error' => 'Укажите пол: мужской или женский.'];
    }

    $familyRaw = trim((string) ($data['family_type'] ?? ''));
    if ($familyRaw !== '' && normalize_student_family_type($familyRaw) === null) {
        return ['success' => false, 'error' => 'Некорректный тип семьи.'];
    }

    $residenceRaw = trim((string) ($data['residence_type'] ?? ''));
    if ($residenceRaw !== '' && normalize_student_residence_type($residenceRaw) === null) {
        return ['success' => false, 'error' => 'Некорректный тип проживания.'];
    }

    $snilsRaw = trim((string) ($data['snils'] ?? ''));
    if ($snilsRaw !== '') {
        $digits = preg_replace('/\D/u', '', $snilsRaw);
        if ($digits === null || strlen((string) $digits) !== 11) {
            return ['success' => false, 'error' => 'Некорректный СНИЛС.'];
        }
    }

    return ['success' => true];
}

function create_student(int $groupId, array $data): array
{
    if (get_group_by_id($groupId) === null) {
        return ['success' => false, 'error' => 'Группа не найдена.'];
    }

    $check = validate_student_data($data);
    if (!$check['success']) {
        return $check;
    }

    $data = apply_student_auto_address($data);

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO students (
                group_id, full_name, phone, birth_date, gender, mother_name, mother_phone, mother_workplace,
                father_name, father_phone, father_workplace,
                address_region, address_district, address_locality, address_street, address_house,
                address_registered, address_actual,
                district, is_low_income, family_type, siblings_under_18, residence_type, is_nonresident,
                without_parental_care
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $groupId,
            $data['full_name'],
            $data['phone'],
            normalize_student_birth_date($data['birth_date'] ?? null),
            normalize_student_gender($data['gender'] ?? null),
            $data['mother_name'],
            $data['mother_phone'],
            (string) ($data['mother_workplace'] ?? ''),
            $data['father_name'],
            $data['father_phone'],
            (string) ($data['father_workplace'] ?? ''),
            (string) ($data['address_region'] ?? ''),
            (string) ($data['address_district'] ?? ''),
            (string) ($data['address_locality'] ?? ''),
            (string) ($data['address_street'] ?? ''),
            (string) ($data['address_house'] ?? ''),
            $data['address_registered'],
            $data['address_actual'],
            (string) ($data['district'] ?? ''),
            !empty($data['is_low_income']) ? 1 : 0,
            normalize_student_family_type($data['family_type'] ?? null),
            normalize_student_siblings_under_18($data['siblings_under_18'] ?? 0),
            normalize_student_residence_type($data['residence_type'] ?? null),
            !empty($data['is_nonresident']) ? 1 : 0,
            !empty($data['without_parental_care']) ? 1 : 0,
        ]);
        $studentId = (int) $pdo->lastInsertId();

        $snils = normalize_student_snils($data['snils'] ?? null);
        if ($snils !== '') {
            $pdo->prepare('UPDATE students SET snils = ? WHERE id = ?')->execute([$snils, $studentId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Не удалось добавить студента.'];
    }

    require_once __DIR__ . '/student_accounts.php';
    $account = create_student_user_account(
        $studentId,
        (string) $data['full_name'],
        (string) ($data['phone'] ?? '')
    );
    log_student_created($studentId, $groupId, (string) $data['full_name']);
    if (!$account['success']) {
        return [
            'success' => true,
            'id' => $studentId,
            'account_error' => $account['error'],
        ];
    }

    return [
        'success' => true,
        'id' => $studentId,
        'login' => $account['login'],
        'password' => $account['password'],
    ];
}

function log_student_created(int $studentId, int $groupId, string $fullName): void
{
    require_once __DIR__ . '/activity_log.php';
    $group = get_group_by_id($groupId);
    log_activity(
        'student.create',
        'student',
        $studentId,
        $groupId,
        [
            'student_name' => $fullName,
            'group_number' => $group ? (string) $group['number'] : (string) $groupId,
        ]
    );
}

function update_student(int $id, array $data): array
{
    $student = get_student_by_id($id);
    if ($student === null) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    $check = validate_student_data($data);
    if (!$check['success']) {
        return $check;
    }

    $data = apply_student_auto_address($data);

    $stmt = db()->prepare(
        'UPDATE students SET
            full_name = ?, phone = ?, snils = ?, birth_date = ?, gender = ?,
            mother_name = ?, mother_phone = ?, mother_workplace = ?,
            father_name = ?, father_phone = ?, father_workplace = ?,
            address_region = ?, address_district = ?, address_locality = ?,
            address_street = ?, address_house = ?,
            address_registered = ?, address_actual = ?,
            district = ?, is_low_income = ?, family_type = ?, siblings_under_18 = ?,
            residence_type = ?, is_nonresident = ?, without_parental_care = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $data['full_name'],
        $data['phone'],
        normalize_student_snils($data['snils'] ?? null),
        normalize_student_birth_date($data['birth_date'] ?? null),
        normalize_student_gender($data['gender'] ?? null),
        $data['mother_name'],
        $data['mother_phone'],
        (string) ($data['mother_workplace'] ?? ''),
        $data['father_name'],
        $data['father_phone'],
        (string) ($data['father_workplace'] ?? ''),
        (string) ($data['address_region'] ?? ''),
        (string) ($data['address_district'] ?? ''),
        (string) ($data['address_locality'] ?? ''),
        (string) ($data['address_street'] ?? ''),
        (string) ($data['address_house'] ?? ''),
        $data['address_registered'],
        $data['address_actual'],
        (string) ($data['district'] ?? ''),
        !empty($data['is_low_income']) ? 1 : 0,
        normalize_student_family_type($data['family_type'] ?? null),
        normalize_student_siblings_under_18($data['siblings_under_18'] ?? 0),
        normalize_student_residence_type($data['residence_type'] ?? null),
        !empty($data['is_nonresident']) ? 1 : 0,
        !empty($data['without_parental_care']) ? 1 : 0,
        $id,
    ]);

    if (!empty($student['user_id'])) {
        db()->prepare(
            'UPDATE users SET full_name = ?, phone = ? WHERE id = ? AND role = \'student\''
        )->execute([
            $data['full_name'],
            $data['phone'],
            (int) $student['user_id'],
        ]);
    }

    return ['success' => true];
}

function delete_student(int $id): array
{
    $student = get_student_by_id($id);
    if ($student === null) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    $userId = (int) ($student['user_id'] ?? 0);
    require_once __DIR__ . '/activity_log.php';
    $group = get_group_by_id((int) $student['group_id']);
    log_activity(
        'student.delete',
        'student',
        $id,
        (int) $student['group_id'],
        [
            'student_name' => (string) $student['full_name'],
            'group_number' => $group ? (string) $group['number'] : '',
        ]
    );

    $stmt = db()->prepare('DELETE FROM students WHERE id = ?');
    $stmt->execute([$id]);

    if ($userId > 0) {
        db()->prepare('DELETE FROM users WHERE id = ? AND role = \'student\'')
            ->execute([$userId]);
    }

    return ['success' => true];
}

function transfer_student(int $studentId, int $toGroupId, string $additionalInfo = ''): array
{
    if (!is_admin()) {
        return ['success' => false, 'error' => 'Перевод студентов доступен только администратору.'];
    }

    $student = get_student_by_id($studentId);
    if ($student === null) {
        return ['success' => false, 'error' => 'Студент не найден.'];
    }

    $fromGroupId = (int) $student['group_id'];
    if ($toGroupId === $fromGroupId) {
        return ['success' => false, 'error' => 'Студент уже состоит в выбранной группе.'];
    }

    $fromGroup = get_group_by_id($fromGroupId);
    $toGroup = get_group_by_id($toGroupId);
    if ($fromGroup === null || $toGroup === null) {
        return ['success' => false, 'error' => 'Группа не найдена.'];
    }

    $additionalInfo = trim($additionalInfo);
    $actor = current_user();

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE students SET group_id = ? WHERE id = ?')
            ->execute([$toGroupId, $studentId]);

        if ($pdo->query("SHOW TABLES LIKE 'student_transfers'")->fetch()) {
            $pdo->prepare(
                'INSERT INTO student_transfers
                    (student_id, from_group_id, from_group_number, to_group_id, to_group_number,
                     additional_info, transferred_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $studentId,
                $fromGroupId,
                (string) $fromGroup['number'],
                $toGroupId,
                (string) $toGroup['number'],
                $additionalInfo,
                $actor ? (int) $actor['id'] : null,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Не удалось перевести студента.'];
    }

    require_once __DIR__ . '/notifications.php';
    $curatorId = (int) ($toGroup['curator_id'] ?? 0);
    if ($curatorId > 0) {
        $infoLine = $additionalInfo !== '' ? ("\nДополнительно: " . $additionalInfo) : '';
        notify_user_direct(
            $curatorId,
            'Перевод студента в группу ' . $toGroup['number'],
            'Студент ' . $student['full_name'] . ' переведён в вашу группу '
            . $toGroup['number'] . ' из группы ' . $fromGroup['number'] . '.'
            . $infoLine
        );
    }

    return [
        'success' => true,
        'from_group_id' => $fromGroupId,
        'to_group_id' => $toGroupId,
    ];
}

function suggest_next_group_number(string $number): ?string
{
    if (preg_match('/^(.*?)(\d)(\d{2})(.*)$/u', $number, $m)) {
        $course = (int) $m[2];
        if ($course >= 9) {
            return null;
        }

        return $m[1] . ($course + 1) . $m[3] . $m[4];
    }

    if (preg_match('/^(.*?)(\d)(.*)$/u', $number, $m)) {
        $course = (int) $m[2];
        if ($course >= 9) {
            return null;
        }

        return $m[1] . ($course + 1) . $m[3];
    }

    return null;
}

function get_group_promotions(int $groupId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM group_promotions WHERE group_id = ? ORDER BY promoted_at DESC'
    );
    $stmt->execute([$groupId]);

    return $stmt->fetchAll();
}

function promote_group(int $groupId, string $newNumber, string $academicYear): array
{
    $group = get_group_by_id($groupId);
    if ($group === null) {
        return ['success' => false, 'error' => 'Группа не найдена.'];
    }

    if (!is_admin() && !can_use_deputy_panel() && !can_manage_group($groupId)) {
        return ['success' => false, 'error' => 'Недостаточно прав для перевода группы.'];
    }

    $newNumber = trim($newNumber);
    $academicYear = normalize_academic_year($academicYear);
    $fromNumber = (string) $group['number'];

    if ($newNumber === '') {
        return ['success' => false, 'error' => 'Укажите новый номер группы.'];
    }

    if ($academicYear === null) {
        return ['success' => false, 'error' => 'Некорректный учебный год. Формат: 2025-2026.'];
    }

    if ($newNumber === $fromNumber) {
        return ['success' => false, 'error' => 'Новый номер совпадает с текущим.'];
    }

    $existing = find_group_by_number($newNumber);
    if ($existing !== null && (int) $existing['id'] !== $groupId) {
        return ['success' => false, 'error' => 'Группа с номером ' . $newNumber . ' уже существует.'];
    }

    $studentCount = count(get_students_by_group($groupId));

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO group_promotions (group_id, from_number, to_number, academic_year)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$groupId, $fromNumber, $newNumber, $academicYear]);

        $stmt = $pdo->prepare('UPDATE study_groups SET number = ? WHERE id = ?');
        $stmt->execute([$newNumber, $groupId]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Не удалось перевести группу.'];
    }

    require_once __DIR__ . '/notifications.php';
    $curatorId = (int) ($group['curator_id'] ?? 0);
    if ($curatorId > 0) {
        notify_user_direct(
            $curatorId,
            'Перевод группы на следующий курс',
            'Группа ' . $fromNumber . ' переведена на номер ' . $newNumber
            . ' (завершён учебный год ' . $academicYear . ').'
            . ' Состав студентов (' . $studentCount . ') и все данные сохранены.'
        );
    }

    return [
        'success' => true,
        'from_number' => $fromNumber,
        'to_number' => $newNumber,
        'students' => $studentCount,
    ];
}

function build_group_promotion_preview(): array
{
    $rows = [];
    foreach (get_all_groups() as $group) {
        $groupId = (int) $group['id'];
        $number = (string) $group['number'];
        $suggested = suggest_next_group_number($number);
        $conflict = null;
        if ($suggested !== null) {
            $existing = find_group_by_number($suggested);
            if ($existing !== null && (int) $existing['id'] !== $groupId) {
                $conflict = $suggested;
            }
        }

        $rows[] = [
            'group' => $group,
            'students' => count(get_students_by_group($groupId)),
            'suggested' => $suggested,
            'conflict' => $conflict,
            'promotions' => get_group_promotions($groupId),
        ];
    }

    return $rows;
}
