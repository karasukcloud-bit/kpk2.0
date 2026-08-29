<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/curriculum.php';

function ktp_lesson_type_label(string $type): string
{
    $labels = [
        'lecture' => 'Лекция',
        'practice' => 'Практика',
        'independent' => 'Самостоятельная работа',
        'diff_credit' => 'Дифференцированный зачёт',
        'credit' => 'Зачёт',
        'exam' => 'Экзамен',
        'control' => 'Контрольная работа',
        'semester_2' => '2 семестр (полугодие)',
    ];

    return $labels[$type] ?? 'Лекция';
}

function ktp_attestation_types(): array
{
    return ['diff_credit', 'credit', 'exam', 'control'];
}

function ktp_is_attestation_type(string $type): bool
{
    return in_array($type, ktp_attestation_types(), true);
}

/** Темы, которые преподаватель отрабатывает и может вносить в журнал. */
function ktp_is_journal_selectable_type(string $type): bool
{
    $type = normalize_ktp_lesson_type($type);

    return $type !== 'independent' && $type !== 'semester_2';
}

function ktp_is_semester_marker_type(string $type): bool
{
    return normalize_ktp_lesson_type($type) === 'semester_2';
}

function ktp_semester_marker_title(): string
{
    return '2 семестр (полугодие)';
}

function ktp_topic_display_number(array $topics, int $index): ?int
{
    if (!isset($topics[$index])) {
        return null;
    }

    if (ktp_is_semester_marker_type((string) ($topics[$index]['lesson_type'] ?? ''))) {
        return null;
    }

    $num = 0;
    for ($i = 0; $i <= $index; $i++) {
        if (!ktp_is_semester_marker_type((string) ($topics[$i]['lesson_type'] ?? ''))) {
            $num++;
        }
    }

    return $num;
}

function curriculum_item_spans_two_semesters(?array $item): bool
{
    return ($item['semester'] ?? '') === 'both';
}

function curriculum_item_ends_after_attestation(?array $item): bool
{
    return ($item['semester'] ?? '') === '1';
}

function has_ktp_semester_marker(int $curriculumItemId): bool
{
    $stmt = db()->prepare(
        "SELECT id FROM ktp_topics
         WHERE curriculum_item_id = ? AND lesson_type = 'semester_2'
         LIMIT 1"
    );
    $stmt->execute([$curriculumItemId]);

    return (bool) $stmt->fetch();
}

function add_ktp_semester_marker(int $curriculumItemId): array
{
    $item = get_curriculum_item_by_id($curriculumItemId);
    if ($item === null) {
        return ['success' => false, 'error' => 'Предмет учебного плана не найден.'];
    }

    if (!curriculum_item_spans_two_semesters($item)) {
        return [
            'success' => false,
            'error' => 'Разделитель 2 семестра доступен только для предметов на оба семестра.',
        ];
    }

    if (has_ktp_semester_marker($curriculumItemId)) {
        return ['success' => false, 'error' => 'Разделитель 2 семестра уже добавлен в КТП.'];
    }

    $stmt = db()->prepare(
        'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ktp_topics WHERE curriculum_item_id = ?'
    );
    $stmt->execute([$curriculumItemId]);
    $sortOrder = (int) $stmt->fetchColumn();

    $stmt = db()->prepare(
        'INSERT INTO ktp_topics
            (curriculum_item_id, title, lesson_type, hours, deadline_date, ok_codes, pk_codes, control_form, sort_order)
         VALUES (?, ?, \'semester_2\', 0, NULL, \'\', \'\', NULL, ?)'
    );
    $stmt->execute([$curriculumItemId, ktp_semester_marker_title(), $sortOrder]);

    return ['success' => true, 'id' => (int) db()->lastInsertId()];
}

function curriculum_item_is_professionality(?array $item): bool
{
    return !empty($item['is_professionality']);
}

function normalize_ktp_orientation_hours($hours): float
{
    $value = round((float) $hours, 1);
    if ($value < 0) {
        return 0.0;
    }

    return min(24.0, $value);
}

function ktp_topic_supports_orientation_hours(string $type): bool
{
    $type = normalize_ktp_lesson_type($type);

    return $type === 'lecture' || $type === 'practice';
}

function ktp_orientation_hours_for_topic(string $lessonType, float $hours, bool $isProfessionality): float
{
    if (!$isProfessionality || !ktp_topic_supports_orientation_hours($lessonType)) {
        return 0.0;
    }

    return normalize_ktp_orientation_hours($hours);
}

function ktp_split_orientation_hours(float $total, int $rowsCount): float
{
    if ($rowsCount < 1 || $total <= 0) {
        return 0.0;
    }

    return round($total / $rowsCount, 1);
}

function format_ktp_topic_hours(array $topic, bool $showOrientation = false): string
{
    $hours = rtrim(rtrim(number_format((float) ($topic['hours'] ?? 0), 1, '.', ''), '0'), '.');
    if (!$showOrientation) {
        return $hours;
    }

    $orientationHours = (float) ($topic['orientation_hours'] ?? 0);
    if ($orientationHours <= 0 || !ktp_topic_supports_orientation_hours((string) ($topic['lesson_type'] ?? 'lecture'))) {
        return $hours;
    }

    $orientation = rtrim(rtrim(number_format($orientationHours, 1, '.', ''), '0'), '.');

    return $hours . '/' . $orientation;
}

function ktp_attestation_title(string $type): string
{
    return 'Промежуточная аттестация. ' . ktp_lesson_type_label($type);
}

function normalize_ktp_lesson_type(string $type): string
{
    $allowed = [
        'lecture',
        'practice',
        'independent',
        'diff_credit',
        'credit',
        'exam',
        'control',
        'semester_2',
    ];

    return in_array($type, $allowed, true) ? $type : 'lecture';
}

function normalize_ktp_hours($hours): float
{
    $value = round((float) $hours, 1);
    if ($value <= 0) {
        return 2.0;
    }

    return min(24.0, $value);
}

function ktp_control_form_options(): array
{
    return [
        'oral' => 'Устный ответ',
        'written' => 'Письменный ответ',
        'test' => 'Тестовое задание',
        'practical' => 'Практическое задание',
    ];
}

function ktp_control_form_label(?string $form): string
{
    $form = trim((string) $form);
    if ($form === '') {
        return '—';
    }

    return ktp_control_form_options()[$form] ?? '—';
}

function normalize_ktp_control_form($form): ?string
{
    $form = trim((string) $form);

    return array_key_exists($form, ktp_control_form_options()) ? $form : null;
}

function ktp_competency_label(string $code): string
{
    if (preg_match('/^(OK|PK)(\d+)$/iu', $code, $matches)) {
        $prefix = strtoupper($matches[1]) === 'OK' ? 'ОК' : 'ПК';

        return $prefix . ' ' . $matches[2];
    }

    return $code;
}

function format_ktp_competency_codes_list(?string $stored): string
{
    $codes = parse_ktp_competency_codes($stored);
    if ($codes === []) {
        return '';
    }

    return implode(', ', array_map('ktp_competency_label', $codes));
}

function render_ktp_competency_cell(?string $okCodes, ?string $pkCodes): void
{
    $ok = format_ktp_competency_codes_list($okCodes);
    $pk = format_ktp_competency_codes_list($pkCodes);

    if ($ok === '' && $pk === '') {
        echo '—';

        return;
    }

    if ($ok !== '') {
        echo '<div><strong>ОК:</strong> ' . e($ok) . '</div>';
    }
    if ($pk !== '') {
        echo '<div><strong>ПК:</strong> ' . e($pk) . '</div>';
    }
}

function ktp_competency_codes(string $prefix, int $count = 20): array
{
    $codes = [];
    for ($i = 1; $i <= $count; $i++) {
        $codes[] = $prefix . $i;
    }

    return $codes;
}

function normalize_ktp_competency_codes($input, string $prefix): string
{
    $allowed = array_flip(ktp_competency_codes($prefix));
    $values = [];

    if (is_array($input)) {
        $values = $input;
    } elseif (is_string($input) && trim($input) !== '') {
        $values = preg_split('/\s*,\s*/', trim($input)) ?: [];
    }

    $normalized = [];
    foreach ($values as $value) {
        $value = strtoupper(trim((string) $value));
        if ($value !== '' && isset($allowed[$value])) {
            $normalized[$value] = true;
        }
    }

    $list = array_keys($normalized);
    usort($list, static function (string $a, string $b): int {
        return (int) substr($a, 2) <=> (int) substr($b, 2);
    });

    return implode(',', $list);
}

function parse_ktp_competency_codes(?string $stored): array
{
    $stored = trim((string) $stored);
    if ($stored === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $stored))));
}

function normalize_ktp_deadline_date($value): ?string
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

function format_ktp_deadline_date(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '—';
    }

    $ts = strtotime($value);

    return $ts !== false ? date('d.m.Y', $ts) : '—';
}

function ktp_topic_extra_from_post(array $post, bool $isProfessionality = false): array
{
    $orientationHours = 0.0;
    if ($isProfessionality) {
        $orientationHours = normalize_ktp_orientation_hours($post['ktp_orientation_hours'] ?? 0);
    }

    return [
        'deadline_date' => normalize_ktp_deadline_date($post['ktp_deadline'] ?? ''),
        'ok_codes' => normalize_ktp_competency_codes($post['ok_codes'] ?? [], 'OK'),
        'pk_codes' => normalize_ktp_competency_codes($post['pk_codes'] ?? [], 'PK'),
        'control_form' => normalize_ktp_control_form($post['ktp_control_form'] ?? ''),
        'orientation_hours' => $orientationHours,
    ];
}

function ktp_store_add_form_draft(int $itemId, string $form, array $post): void
{
    if ($form === 'topic') {
        $_SESSION['ktp_form_draft'][$itemId]['topic'] = [
            'ktp_lesson_type' => (string) ($post['ktp_lesson_type'] ?? 'lecture'),
            'ktp_hours' => (string) ($post['ktp_hours'] ?? '2'),
            'ktp_orientation_hours' => (string) ($post['ktp_orientation_hours'] ?? '0'),
            'ktp_deadline' => (string) ($post['ktp_deadline'] ?? ''),
            'ok_codes' => $post['ok_codes'] ?? [],
            'pk_codes' => $post['pk_codes'] ?? [],
            'ktp_control_form' => (string) ($post['ktp_control_form'] ?? ''),
        ];

        return;
    }

    if ($form === 'attestation') {
        $_SESSION['ktp_form_draft'][$itemId]['attestation'] = [
            'attestation_type' => (string) ($post['attestation_type'] ?? 'diff_credit'),
            'attestation_hours' => (string) ($post['attestation_hours'] ?? '1'),
        ];
    }
}

function ktp_get_add_form_draft(int $itemId, string $form): array
{
    return $_SESSION['ktp_form_draft'][$itemId][$form] ?? [];
}

function render_ktp_competency_fields(string $fieldPrefix, array $okSelected = [], array $pkSelected = []): void
{
    $fieldPrefix = preg_replace('/[^a-z0-9_]/i', '', $fieldPrefix);
    $okMap = array_flip(array_map('strtoupper', $okSelected));
    $pkMap = array_flip(array_map('strtoupper', $pkSelected));
    ?>
    <div class="ktp-competency-layout" data-ktp-competency-block>
        <div class="ktp-competency-pickers">
            <div class="ktp-competency-group">
                <span class="form__label">Общие компетенции (ОК)</span>
                <div class="ktp-competency-checkboxes">
                    <?php foreach (ktp_competency_codes('OK') as $code): ?>
                    <label class="ktp-competency-check">
                        <input
                            type="checkbox"
                            name="ok_codes[]"
                            value="<?= e($code) ?>"
                            data-competency-label="<?= e(ktp_competency_label($code)) ?>"
                            <?= isset($okMap[$code]) ? 'checked' : '' ?>
                        >
                        <span><?= e(ktp_competency_label($code)) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="ktp-competency-group">
                <span class="form__label">Профессиональные компетенции (ПК)</span>
                <div class="ktp-competency-checkboxes">
                    <?php foreach (ktp_competency_codes('PK') as $code): ?>
                    <label class="ktp-competency-check">
                        <input
                            type="checkbox"
                            name="pk_codes[]"
                            value="<?= e($code) ?>"
                            data-competency-label="<?= e(ktp_competency_label($code)) ?>"
                            <?= isset($pkMap[$code]) ? 'checked' : '' ?>
                        >
                        <span><?= e(ktp_competency_label($code)) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="ktp-competency-selected">
            <span class="form__label">Выбрано</span>
            <div class="ktp-selected-block">
                <div class="ktp-selected-line">
                    <strong>ОК:</strong>
                    <span data-ktp-selected-ok class="ktp-selected-list">—</span>
                </div>
                <div class="ktp-selected-line">
                    <strong>ПК:</strong>
                    <span data-ktp-selected-pk class="ktp-selected-list">—</span>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function render_ktp_control_form_select(string $name, ?string $selected = null, string $id = ''): void
{
    $idAttr = $id !== '' ? ' id="' . e($id) . '"' : '';
    ?>
    <select name="<?= e($name) ?>" class="ktp-field-compact"<?= $idAttr ?>>
        <option value="">— Не выбрано —</option>
        <?php foreach (ktp_control_form_options() as $value => $label): ?>
        <option value="<?= e($value) ?>"<?= $selected === $value ? ' selected' : '' ?>>
            <?= e($label) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php
}

function ktp_work_program_dir(): string
{
    return dirname(__DIR__) . '/uploads/ktp_work_programs';
}

function get_ktp_work_program(int $curriculumItemId): ?array
{
    if (!db()->query("SHOW TABLES LIKE 'ktp_work_programs'")->fetch()) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT wp.*, u.full_name AS uploaded_by_name
         FROM ktp_work_programs wp
         LEFT JOIN users u ON u.id = wp.uploaded_by
         WHERE wp.curriculum_item_id = ?
         LIMIT 1'
    );
    $stmt->execute([$curriculumItemId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function save_ktp_work_program(int $curriculumItemId, array $file): array
{
    $item = get_curriculum_item_by_id($curriculumItemId);
    if ($item === null) {
        return ['success' => false, 'error' => 'Предмет учебного плана не найден.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'Выберите файл рабочей программы.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Не удалось загрузить файл.'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 10 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Размер файла не должен превышать 10 МБ.'];
    }

    $originalName = basename((string) ($file['name'] ?? 'work_program'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'rtf', 'odt'];
    if (!in_array($extension, $allowed, true)) {
        return ['success' => false, 'error' => 'Допустимы файлы PDF, DOC, DOCX, RTF, ODT.'];
    }

    $dir = ktp_work_program_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['success' => false, 'error' => 'Не удалось создать папку для загрузок.'];
    }

    $storedName = 'item' . $curriculumItemId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $dest = $dir . '/' . $storedName;
    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $dest)) {
        return ['success' => false, 'error' => 'Не удалось сохранить файл.'];
    }

    $existing = get_ktp_work_program($curriculumItemId);
    if ($existing !== null) {
        $oldPath = ktp_work_program_dir() . '/' . basename((string) $existing['stored_path']);
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
        db()->prepare(
            'UPDATE ktp_work_programs
             SET original_name = ?, stored_path = ?, uploaded_by = ?, uploaded_at = NOW()
             WHERE curriculum_item_id = ?'
        )->execute([
            $originalName,
            $storedName,
            current_user() ? (int) current_user()['id'] : null,
            $curriculumItemId,
        ]);
    } else {
        db()->prepare(
            'INSERT INTO ktp_work_programs (curriculum_item_id, original_name, stored_path, uploaded_by)
             VALUES (?, ?, ?, ?)'
        )->execute([
            $curriculumItemId,
            $originalName,
            $storedName,
            current_user() ? (int) current_user()['id'] : null,
        ]);
    }

    return ['success' => true];
}

function delete_ktp_work_program(int $curriculumItemId): array
{
    $existing = get_ktp_work_program($curriculumItemId);
    if ($existing === null) {
        return ['success' => false, 'error' => 'Рабочая программа не загружена.'];
    }

    $path = ktp_work_program_dir() . '/' . basename((string) $existing['stored_path']);
    if (is_file($path)) {
        @unlink($path);
    }

    db()->prepare('DELETE FROM ktp_work_programs WHERE curriculum_item_id = ?')->execute([$curriculumItemId]);

    return ['success' => true];
}

function build_ktp_plan_summary(array $topics): array
{
    $lessons = 0;
    $lectureHours = 0.0;
    $practiceHours = 0.0;
    $attestationHours = 0.0;
    $independentHours = 0.0;
    $orientationHours = 0.0;

    foreach ($topics as $topic) {
        $hours = (float) ($topic['hours'] ?? 0);
        $type = (string) ($topic['lesson_type'] ?? 'lecture');

        if ($type === 'lecture' || $type === 'practice') {
            $lessons++;
            $orientationHours += (float) ($topic['orientation_hours'] ?? 0);
        }

        if (ktp_is_semester_marker_type($type)) {
            continue;
        }

        if ($type === 'lecture') {
            $lectureHours += $hours;
        } elseif ($type === 'practice') {
            $practiceHours += $hours;
        } elseif ($type === 'independent') {
            $independentHours += $hours;
        } elseif (ktp_is_attestation_type($type)) {
            $attestationHours += $hours;
        }
    }

    return [
        'lessons' => $lessons,
        'lecture_hours' => round($lectureHours, 1),
        'practice_hours' => round($practiceHours, 1),
        'attestation_hours' => round($attestationHours, 1),
        'independent_hours' => round($independentHours, 1),
        'orientation_hours' => round($orientationHours, 1),
        'total_hours' => round(
            $lectureHours + $practiceHours + $attestationHours + $independentHours,
            1
        ),
    ];
}

function add_ktp_attestation(int $curriculumItemId, string $attestationType, $hours = 1, array $extra = []): array
{
    $attestationType = normalize_ktp_lesson_type($attestationType);
    if (!ktp_is_attestation_type($attestationType)) {
        return ['success' => false, 'error' => 'Некорректный вид промежуточной аттестации.'];
    }

    $title = ktp_attestation_title($attestationType);
    $oneRowPerHour = ($attestationType !== 'exam');

    return add_ktp_topic(
        $curriculumItemId,
        $title,
        $attestationType,
        $hours,
        $oneRowPerHour,
        $extra
    );
}

function get_ktp_topics(int $curriculumItemId): array
{
    $stmt = db()->prepare(
        'SELECT id, curriculum_item_id, title, lesson_type, hours, orientation_hours, deadline_date,
                ok_codes, pk_codes, control_form, sort_order
         FROM ktp_topics
         WHERE curriculum_item_id = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([$curriculumItemId]);

    return $stmt->fetchAll();
}

function get_ktp_topic_by_id(int $topicId): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM ktp_topics WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$topicId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function add_ktp_topic(
    int $curriculumItemId,
    string $title,
    string $lessonType = 'lecture',
    $hours = 2,
    bool $oneRowPerHour = false,
    array $extra = []
): array {
    $item = get_curriculum_item_by_id($curriculumItemId);
    if ($item === null) {
        return ['success' => false, 'error' => 'Предмет учебного плана не найден.'];
    }

    $title = trim($title);
    if ($title === '') {
        return ['success' => false, 'error' => 'Укажите тему урока.'];
    }

    $lessonType = normalize_ktp_lesson_type($lessonType);
    if (ktp_is_semester_marker_type($lessonType)) {
        return ['success' => false, 'error' => 'Некорректный тип темы.'];
    }
    if (ktp_is_attestation_type($lessonType)) {
        $title = ktp_attestation_title($lessonType);
    }

    $hours = normalize_ktp_hours($hours);
    $isProfessionality = curriculum_item_is_professionality($item);
    $meta = ktp_topic_extra_from_post($extra, $isProfessionality);
    $orientationTotal = ktp_orientation_hours_for_topic(
        $lessonType,
        (float) $meta['orientation_hours'],
        $isProfessionality
    );

    if ($lessonType === 'exam') {
        $oneRowPerHour = false;
    }

    $stmt = db()->prepare(
        'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ktp_topics WHERE curriculum_item_id = ?'
    );
    $stmt->execute([$curriculumItemId]);
    $sortOrder = (int) $stmt->fetchColumn();

    $stmt = db()->prepare(
        'INSERT INTO ktp_topics
            (curriculum_item_id, title, lesson_type, hours, orientation_hours, deadline_date, ok_codes, pk_codes, control_form, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$oneRowPerHour) {
        $stmt->execute([
            $curriculumItemId,
            $title,
            $lessonType,
            $hours,
            $orientationTotal,
            $meta['deadline_date'],
            $meta['ok_codes'],
            $meta['pk_codes'],
            $meta['control_form'],
            $sortOrder,
        ]);

        return ['success' => true, 'id' => (int) db()->lastInsertId(), 'count' => 1];
    }

    $rowsCount = (int) round($hours);
    if ($rowsCount < 1) {
        $rowsCount = 1;
    }
    if ($rowsCount > 24) {
        $rowsCount = 24;
    }
    $orientationPerRow = ktp_split_orientation_hours($orientationTotal, $rowsCount);

    $ids = [];
    $pdo = db();
    $pdo->beginTransaction();

    try {
        for ($i = 0; $i < $rowsCount; $i++) {
            $stmt->execute([
                $curriculumItemId,
                $title,
                $lessonType,
                1.0,
                $orientationPerRow,
                $meta['deadline_date'],
                $meta['ok_codes'],
                $meta['pk_codes'],
                $meta['control_form'],
                $sortOrder + $i,
            ]);
            $ids[] = (int) $pdo->lastInsertId();
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();

        return ['success' => false, 'error' => 'Не удалось добавить темы в КТП.'];
    }

    return [
        'success' => true,
        'id' => $ids[0] ?? 0,
        'ids' => $ids,
        'count' => count($ids),
    ];
}

function delete_ktp_topic(int $topicId): array
{
    if (get_ktp_topic_by_id($topicId) === null) {
        return ['success' => false, 'error' => 'Тема не найдена.'];
    }

    db()->prepare('UPDATE journal_lessons SET ktp_topic_id = NULL WHERE ktp_topic_id = ?')
        ->execute([$topicId]);
    db()->prepare('DELETE FROM ktp_topics WHERE id = ?')->execute([$topicId]);

    return ['success' => true];
}

function get_ktp_topics_with_progress(int $curriculumItemId): array
{
    $stmt = db()->prepare(
        'SELECT kt.id, kt.curriculum_item_id, kt.title, kt.lesson_type, kt.hours, kt.orientation_hours,
                kt.deadline_date, kt.ok_codes, kt.pk_codes, kt.control_form, kt.sort_order,
                COUNT(jl.id) AS used_count,
                MIN(jl.lesson_date) AS first_lesson_date,
                MAX(jl.lesson_date) AS last_lesson_date
         FROM ktp_topics kt
         LEFT JOIN journal_lessons jl ON jl.ktp_topic_id = kt.id
         WHERE kt.curriculum_item_id = ?
         GROUP BY kt.id, kt.curriculum_item_id, kt.title, kt.lesson_type, kt.hours, kt.orientation_hours,
                  kt.deadline_date, kt.ok_codes, kt.pk_codes, kt.control_form, kt.sort_order
         ORDER BY kt.sort_order ASC, kt.id ASC'
    );
    $stmt->execute([$curriculumItemId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['completed'] = (int) ($row['used_count'] ?? 0) > 0;
    }
    unset($row);

    return $rows;
}

function update_ktp_topic(
    int $topicId,
    string $title,
    string $lessonType = 'lecture',
    $hours = 2,
    bool $oneRowPerHour = false,
    array $extra = []
): array {
    $topic = get_ktp_topic_by_id($topicId);
    if ($topic === null) {
        return ['success' => false, 'error' => 'Тема не найдена.'];
    }

    $title = trim($title);
    if ($title === '') {
        return ['success' => false, 'error' => 'Укажите тему урока.'];
    }

    $lessonType = normalize_ktp_lesson_type($lessonType);
    if (ktp_is_semester_marker_type($lessonType)) {
        return ['success' => false, 'error' => 'Разделитель семестра нельзя редактировать.'];
    }
    if (ktp_is_attestation_type($lessonType)) {
        $title = ktp_attestation_title($lessonType);
    }

    $hours = normalize_ktp_hours($hours);
    $item = get_curriculum_item_by_id((int) $topic['curriculum_item_id']);
    $isProfessionality = curriculum_item_is_professionality($item);
    $meta = ktp_topic_extra_from_post($extra, $isProfessionality);
    $orientationTotal = ktp_orientation_hours_for_topic(
        $lessonType,
        (float) $meta['orientation_hours'],
        $isProfessionality
    );
    $curriculumItemId = (int) $topic['curriculum_item_id'];
    $currentSort = (int) $topic['sort_order'];

    if ($lessonType === 'exam') {
        $oneRowPerHour = false;
    }

    if (!$oneRowPerHour) {
        $stmt = db()->prepare(
            'UPDATE ktp_topics
             SET title = ?, lesson_type = ?, hours = ?, orientation_hours = ?, deadline_date = ?, ok_codes = ?, pk_codes = ?, control_form = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $title,
            $lessonType,
            $hours,
            $orientationTotal,
            $meta['deadline_date'],
            $meta['ok_codes'],
            $meta['pk_codes'],
            $meta['control_form'],
            $topicId,
        ]);

        return ['success' => true, 'count' => 1, 'added' => 0];
    }

    $rowsCount = (int) round($hours);
    if ($rowsCount < 1) {
        $rowsCount = 1;
    }
    if ($rowsCount > 24) {
        $rowsCount = 24;
    }
    $orientationPerRow = ktp_split_orientation_hours($orientationTotal, $rowsCount);

    $extraRows = $rowsCount - 1;
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'UPDATE ktp_topics
             SET title = ?, lesson_type = ?, hours = 1, orientation_hours = ?, deadline_date = ?, ok_codes = ?, pk_codes = ?, control_form = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $title,
            $lessonType,
            $orientationPerRow,
            $meta['deadline_date'],
            $meta['ok_codes'],
            $meta['pk_codes'],
            $meta['control_form'],
            $topicId,
        ]);

        if ($extraRows > 0) {
            $shift = $pdo->prepare(
                'UPDATE ktp_topics
                 SET sort_order = sort_order + ?
                 WHERE curriculum_item_id = ? AND sort_order > ?'
            );
            $shift->execute([$extraRows, $curriculumItemId, $currentSort]);

            $insert = $pdo->prepare(
                'INSERT INTO ktp_topics
                    (curriculum_item_id, title, lesson_type, hours, orientation_hours, deadline_date, ok_codes, pk_codes, control_form, sort_order)
                 VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?)'
            );

            for ($i = 1; $i <= $extraRows; $i++) {
                $insert->execute([
                    $curriculumItemId,
                    $title,
                    $lessonType,
                    $orientationPerRow,
                    $meta['deadline_date'],
                    $meta['ok_codes'],
                    $meta['pk_codes'],
                    $meta['control_form'],
                    $currentSort + $i,
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();

        return ['success' => false, 'error' => 'Не удалось обновить тему КТП.'];
    }

    return [
        'success' => true,
        'count' => $rowsCount,
        'added' => $extraRows,
    ];
}

function reorder_ktp_topics(int $curriculumItemId, array $orderedIds): array
{
    $item = get_curriculum_item_by_id($curriculumItemId);
    if ($item === null) {
        return ['success' => false, 'error' => 'Предмет учебного плана не найден.'];
    }

    $orderedIds = array_values(array_unique(array_map('intval', $orderedIds)));
    if ($orderedIds === []) {
        return ['success' => false, 'error' => 'Не передан порядок тем.'];
    }

    $existing = get_ktp_topics($curriculumItemId);
    $existingIds = array_map(static function (array $topic): int {
        return (int) $topic['id'];
    }, $existing);

    sort($existingIds);
    $check = $orderedIds;
    sort($check);

    if ($existingIds !== $check) {
        return ['success' => false, 'error' => 'Список тем не совпадает с КТП предмета.'];
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'UPDATE ktp_topics SET sort_order = ? WHERE id = ? AND curriculum_item_id = ?'
        );

        foreach ($orderedIds as $index => $topicId) {
            $stmt->execute([$index + 1, $topicId, $curriculumItemId]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();

        return ['success' => false, 'error' => 'Не удалось сохранить порядок тем.'];
    }

    return ['success' => true];
}

function get_used_ktp_topic_ids(int $curriculumItemId, ?int $exceptLessonId = null): array
{
    $sql = 'SELECT DISTINCT ktp_topic_id
            FROM journal_lessons
            WHERE curriculum_item_id = ?
              AND ktp_topic_id IS NOT NULL';
    $params = [$curriculumItemId];

    if ($exceptLessonId !== null && $exceptLessonId > 0) {
        $sql .= ' AND id != ?';
        $params[] = $exceptLessonId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function get_next_ktp_topic_id(int $curriculumItemId, array $lessons = []): ?int
{
    $topics = get_ktp_topics($curriculumItemId);
    if ($topics === []) {
        return null;
    }

    $usedIds = get_used_ktp_topic_ids($curriculumItemId);

    foreach ($topics as $topic) {
        $topicId = (int) $topic['id'];
        if (!ktp_is_journal_selectable_type((string) ($topic['lesson_type'] ?? 'lecture'))) {
            continue;
        }
        if (!in_array($topicId, $usedIds, true)) {
            return $topicId;
        }
    }

    return null;
}

function build_covered_material_summary(array $lessons): array
{
    $totalLessons = count($lessons);
    $lectureHours = 0.0;
    $practiceHours = 0.0;

    foreach ($lessons as $lesson) {
        $hours = (float) ($lesson['topic_hours'] ?? 0);
        $type = (string) ($lesson['topic_lesson_type'] ?? 'lecture');

        if ($hours <= 0) {
            continue;
        }

        if ($type === 'practice') {
            $practiceHours += $hours;
        } elseif ($type === 'lecture') {
            $lectureHours += $hours;
        }
    }

    return [
        'total_lessons' => $totalLessons,
        'lecture_hours' => round($lectureHours, 1),
        'practice_hours' => round($practiceHours, 1),
        'total_hours' => round($lectureHours + $practiceHours, 1),
    ];
}
