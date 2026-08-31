<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function get_organization(): array
{
    $stmt = db()->query('SELECT * FROM organization WHERE id = 1 LIMIT 1');
    $row = $stmt->fetch();

    if ($row) {
        return $row;
    }

    return [
        'id'              => 1,
        'name'            => '',
        'address'         => '',
        'phone'           => '',
        'email'           => '',
        'additional_info' => '',
    ];
}

function save_organization(array $data): array
{
    $name = trim($data['name'] ?? '');
    $address = trim($data['address'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $additionalInfo = trim($data['additional_info'] ?? '');

    if ($name === '') {
        return ['success' => false, 'error' => 'Укажите название образовательной организации.'];
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Некорректный email организации.'];
    }

    $stmt = db()->prepare(
        'INSERT INTO organization (id, name, address, phone, email, additional_info)
         VALUES (1, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            address = VALUES(address),
            phone = VALUES(phone),
            email = VALUES(email),
            additional_info = VALUES(additional_info)'
    );
    $stmt->execute([$name, $address, $phone, $email, $additionalInfo]);

    return ['success' => true];
}

function get_all_specialties(): array
{
    $stmt = db()->query(
        'SELECT id, name, code, created_at FROM specialties ORDER BY name ASC'
    );

    return $stmt->fetchAll();
}

function get_specialty_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM specialties WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function create_specialty(string $name, string $code): array
{
    $name = trim($name);
    $code = trim($code);

    if ($name === '') {
        return ['success' => false, 'error' => 'Укажите название специальности.'];
    }

    if ($code === '') {
        return ['success' => false, 'error' => 'Укажите код специальности.'];
    }

    if (find_specialty_by_code($code)) {
        return ['success' => false, 'error' => 'Специальность с таким кодом уже существует.'];
    }

    $stmt = db()->prepare('INSERT INTO specialties (name, code) VALUES (?, ?)');
    $stmt->execute([$name, $code]);

    return ['success' => true, 'id' => (int) db()->lastInsertId()];
}

function update_specialty(int $id, string $name, string $code): array
{
    $specialty = get_specialty_by_id($id);
    if ($specialty === null) {
        return ['success' => false, 'error' => 'Специальность не найдена.'];
    }

    $name = trim($name);
    $code = trim($code);

    if ($name === '') {
        return ['success' => false, 'error' => 'Укажите название специальности.'];
    }

    if ($code === '') {
        return ['success' => false, 'error' => 'Укажите код специальности.'];
    }

    $existing = find_specialty_by_code($code);
    if ($existing !== null && (int) $existing['id'] !== $id) {
        return ['success' => false, 'error' => 'Специальность с таким кодом уже существует.'];
    }

    $stmt = db()->prepare('UPDATE specialties SET name = ?, code = ? WHERE id = ?');
    $stmt->execute([$name, $code, $id]);

    return ['success' => true];
}

function delete_specialty(int $id): array
{
    $specialty = get_specialty_by_id($id);
    if ($specialty === null) {
        return ['success' => false, 'error' => 'Специальность не найдена.'];
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM study_groups WHERE specialty_id = ?');
    $stmt->execute([$id]);
    if ((int) $stmt->fetchColumn() > 0) {
        return ['success' => false, 'error' => 'Нельзя удалить специальность: к ней привязаны группы.'];
    }

    $stmt = db()->prepare('DELETE FROM specialties WHERE id = ?');
    $stmt->execute([$id]);

    return ['success' => true];
}

function find_specialty_by_code(string $code): ?array
{
    $stmt = db()->prepare('SELECT * FROM specialties WHERE code = ? LIMIT 1');
    $stmt->execute([trim($code)]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function get_all_groups(): array
{
    $stmt = db()->query(
        'SELECT g.id, g.number, g.specialty_id, g.curator_id,
                g.is_professionality, g.is_general_education, g.created_at,
                s.name AS specialty_name, s.code AS specialty_code,
                u.full_name AS curator_name
         FROM study_groups g
         INNER JOIN specialties s ON s.id = g.specialty_id
         LEFT JOIN users u ON u.id = g.curator_id
         ORDER BY g.number ASC'
    );

    return $stmt->fetchAll();
}

function get_group_by_id(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT g.*, s.name AS specialty_name, s.code AS specialty_code,
                u.full_name AS curator_name
         FROM study_groups g
         INNER JOIN specialties s ON s.id = g.specialty_id
         LEFT JOIN users u ON u.id = g.curator_id
         WHERE g.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function create_group(
    string $number,
    int $specialtyId,
    ?int $curatorId = null,
    bool $isProfessionality = false,
    bool $isGeneralEducation = false
): array {
    $number = trim($number);

    if ($number === '') {
        return ['success' => false, 'error' => 'Укажите номер группы.'];
    }

    if (get_specialty_by_id($specialtyId) === null) {
        return ['success' => false, 'error' => 'Выберите специальность из списка.'];
    }

    if (find_group_by_number($number)) {
        return ['success' => false, 'error' => 'Группа с таким номером уже существует.'];
    }

    $stmt = db()->prepare(
        'INSERT INTO study_groups
         (number, specialty_id, curator_id, is_professionality, is_general_education)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $number,
        $specialtyId,
        $curatorId ?: null,
        $isProfessionality ? 1 : 0,
        $isGeneralEducation ? 1 : 0,
    ]);

    return ['success' => true, 'id' => (int) db()->lastInsertId()];
}

function update_group(
    int $id,
    string $number,
    int $specialtyId,
    ?int $curatorId = null,
    bool $isProfessionality = false,
    bool $isGeneralEducation = false
): array {
    $group = get_group_by_id($id);
    if ($group === null) {
        return ['success' => false, 'error' => 'Группа не найдена.'];
    }

    $number = trim($number);

    if ($number === '') {
        return ['success' => false, 'error' => 'Укажите номер группы.'];
    }

    if (get_specialty_by_id($specialtyId) === null) {
        return ['success' => false, 'error' => 'Выберите специальность из списка.'];
    }

    $existing = find_group_by_number($number);
    if ($existing !== null && (int) $existing['id'] !== $id) {
        return ['success' => false, 'error' => 'Группа с таким номером уже существует.'];
    }

    $stmt = db()->prepare(
        'UPDATE study_groups
         SET number = ?, specialty_id = ?, curator_id = ?,
             is_professionality = ?, is_general_education = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $number,
        $specialtyId,
        $curatorId ?: null,
        $isProfessionality ? 1 : 0,
        $isGeneralEducation ? 1 : 0,
        $id,
    ]);

    return ['success' => true];
}

function delete_group(int $id): array
{
    if (get_group_by_id($id) === null) {
        return ['success' => false, 'error' => 'Группа не найдена.'];
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM students WHERE group_id = ?');
    $stmt->execute([$id]);
    if ((int) $stmt->fetchColumn() > 0) {
        return ['success' => false, 'error' => 'Нельзя удалить группу: в ней есть студенты.'];
    }

    $stmt = db()->prepare('DELETE FROM study_groups WHERE id = ?');
    $stmt->execute([$id]);

    return ['success' => true];
}

function find_group_by_number(string $number): ?array
{
    $stmt = db()->prepare('SELECT * FROM study_groups WHERE number = ? LIMIT 1');
    $stmt->execute([trim($number)]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function group_labels_from_input(array $input): array
{
    return [
        'is_professionality' => !empty($input['label_professionality']),
        'is_general_education' => !empty($input['label_general_education']),
    ];
}

function group_labels_text(array $group): string
{
    $labels = [];

    if (!empty($group['is_professionality'])) {
        $labels[] = 'Профессионалитет';
    }

    if (!empty($group['is_general_education'])) {
        $labels[] = 'Общеобразовательный цикл';
    }

    return $labels !== [] ? implode(', ', $labels) : '—';
}

function render_group_labels_fields(array $data = []): void
{
    $isProfessionality = !empty($data['is_professionality']);
    $isGeneralEducation = !empty($data['is_general_education']);
    ?>
    <div class="form__group form__group--checkboxes">
        <span class="form__label">Метки группы</span>
        <label class="checkbox-label">
            <input type="checkbox" name="label_professionality" value="1"
                <?= $isProfessionality ? 'checked' : '' ?>>
            Профессионалитет
        </label>
        <label class="checkbox-label">
            <input type="checkbox" name="label_general_education" value="1"
                <?= $isGeneralEducation ? 'checked' : '' ?>>
            Общеобразовательный цикл
        </label>
    </div>
    <?php
}

function render_group_labels_badges(array $group): string
{
    $html = '';

    if (!empty($group['is_professionality'])) {
        $html .= '<span class="badge badge--group-label">Профессионалитет</span> ';
    }

    if (!empty($group['is_general_education'])) {
        $html .= '<span class="badge badge--group-label badge--group-label-alt">Общеобразовательный цикл</span>';
    }

    return trim($html) !== '' ? trim($html) : '—';
}

function render_specialty_options(array $specialties, int $selectedId = 0): string
{
    $html = '<option value="">— Выберите специальность —</option>';

    foreach ($specialties as $specialty) {
        $id = (int) $specialty['id'];
        $selected = $id === $selectedId ? ' selected' : '';
        $label = $specialty['name'] . ' (' . $specialty['code'] . ')';
        $html .= '<option value="' . $id . '"' . $selected . '>'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    return $html;
}

function get_user_specialty_head_id(int $userId): ?int
{
    $stmt = db()->prepare('SELECT specialty_id FROM user_specialty_heads WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    return $row ? (int) $row['specialty_id'] : null;
}

function get_user_specialty_head(?int $userId = null): ?array
{
    $userId = $userId ?? (int) (current_user()['id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    $specialtyId = get_user_specialty_head_id($userId);
    if ($specialtyId === null) {
        return null;
    }

    return get_specialty_by_id($specialtyId);
}

function get_specialty_head_user_for_specialty(int $specialtyId): ?array
{
    if ($specialtyId <= 0) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT u.id, u.full_name, u.email, u.phone, u.position
         FROM user_specialty_heads ush
         INNER JOIN users u ON u.id = ush.user_id
         WHERE ush.specialty_id = ?
         LIMIT 1'
    );
    $stmt->execute([$specialtyId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function resolve_specialty_id_for_item(array $item): int
{
    $specialtyId = (int) ($item['group_specialty_id'] ?? $item['specialty_id'] ?? 0);
    if ($specialtyId > 0) {
        return $specialtyId;
    }

    $groupId = (int) ($item['group_id'] ?? 0);
    if ($groupId > 0) {
        $group = get_group_by_id($groupId);
        if ($group !== null) {
            return (int) ($group['specialty_id'] ?? 0);
        }
    }

    $code = trim((string) ($item['specialty_code'] ?? ''));
    if ($code !== '') {
        $stmt = db()->prepare('SELECT id FROM specialties WHERE code = ? LIMIT 1');
        $stmt->execute([$code]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }
    }

    return 0;
}

function validate_specialty_head_roles(array $staffRoles, ?int $specialtyHeadId, ?int $exceptUserId = null): array
{
    if (!in_array('specialty_head', $staffRoles, true)) {
        return ['success' => true];
    }

    if ($specialtyHeadId === null || $specialtyHeadId <= 0) {
        return ['success' => false, 'error' => 'Укажите специальность для руководителя специальности.'];
    }

    if (get_specialty_by_id($specialtyHeadId) === null) {
        return ['success' => false, 'error' => 'Специальность не найдена.'];
    }

    $stmt = db()->prepare(
        'SELECT user_id FROM user_specialty_heads WHERE specialty_id = ? AND user_id <> ? LIMIT 1'
    );
    $stmt->execute([$specialtyHeadId, (int) ($exceptUserId ?? 0)]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Эта специальность уже назначена другому руководителю.'];
    }

    return ['success' => true];
}

function get_specialty_head_name_for_specialty(int $specialtyId): string
{
    $user = get_specialty_head_user_for_specialty($specialtyId);

    return $user !== null ? trim((string) ($user['full_name'] ?? '')) : '';
}

function assign_user_specialty_head(int $userId, ?int $specialtyId): array
{
    $pdo = db();
    $pdo->prepare('DELETE FROM user_specialty_heads WHERE user_id = ?')->execute([$userId]);

    if ($specialtyId === null || $specialtyId <= 0) {
        return ['success' => true];
    }

    if (get_specialty_by_id($specialtyId) === null) {
        return ['success' => false, 'error' => 'Специальность не найдена.'];
    }

    $stmt = $pdo->prepare(
        'SELECT user_id FROM user_specialty_heads WHERE specialty_id = ? AND user_id <> ? LIMIT 1'
    );
    $stmt->execute([$specialtyId, $userId]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Эта специальность уже назначена другому руководителю.'];
    }

    $pdo->prepare('INSERT INTO user_specialty_heads (user_id, specialty_id) VALUES (?, ?)')
        ->execute([$userId, $specialtyId]);

    return ['success' => true];
}

function sync_specialty_head(int $userId, array $staffRoles, ?int $specialtyId): array
{
    if (!in_array('specialty_head', $staffRoles, true)) {
        return assign_user_specialty_head($userId, null);
    }

    if ($specialtyId === null || $specialtyId <= 0) {
        return ['success' => false, 'error' => 'Укажите специальность для руководителя специальности.'];
    }

    return assign_user_specialty_head($userId, $specialtyId);
}

function render_specialty_head_options(?int $selectedId = null, ?int $forUserId = null): string
{
    $html = '<option value="">— Не назначена —</option>';
    $taken = [];

    $stmt = db()->query('SELECT specialty_id, user_id FROM user_specialty_heads');
    foreach ($stmt->fetchAll() as $row) {
        $taken[(int) $row['specialty_id']] = (int) $row['user_id'];
    }

    foreach (get_all_specialties() as $specialty) {
        $id = (int) $specialty['id'];
        $ownerId = $taken[$id] ?? 0;
        $takenByOther = $ownerId > 0 && $ownerId !== (int) $forUserId;
        $selected = $id === (int) $selectedId ? ' selected' : '';
        $disabled = $takenByOther ? ' disabled' : '';
        $label = $specialty['name'] . ' (' . $specialty['code'] . ')';

        if ($takenByOther) {
            $stmt = db()->prepare('SELECT full_name FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$ownerId]);
            $headName = trim((string) ($stmt->fetchColumn() ?: ''));
            if ($headName !== '') {
                $label .= ' (руководитель: ' . $headName . ')';
            }
        }

        $html .= '<option value="' . $id . '"' . $selected . $disabled . '>'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    return $html;
}

function format_specialty_head_label(?int $specialtyId): ?string
{
    if ($specialtyId === null || $specialtyId <= 0) {
        return null;
    }

    $specialty = get_specialty_by_id($specialtyId);
    if ($specialty === null) {
        return null;
    }

    return $specialty['name'] . ' (' . $specialty['code'] . ')';
}
