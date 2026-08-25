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
    return normalize_ktp_lesson_type($type) !== 'independent';
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

function build_ktp_plan_summary(array $topics): array
{
    $lessons = 0;
    $lectureHours = 0.0;
    $practiceHours = 0.0;
    $attestationHours = 0.0;
    $independentHours = 0.0;

    foreach ($topics as $topic) {
        $hours = (float) ($topic['hours'] ?? 0);
        $type = (string) ($topic['lesson_type'] ?? 'lecture');

        if ($type === 'lecture' || $type === 'practice') {
            $lessons++;
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
        'total_hours' => round(
            $lectureHours + $practiceHours + $attestationHours + $independentHours,
            1
        ),
    ];
}

function add_ktp_attestation(int $curriculumItemId, string $attestationType, $hours = 1): array
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
        $oneRowPerHour
    );
}

function get_ktp_topics(int $curriculumItemId): array
{
    $stmt = db()->prepare(
        'SELECT id, curriculum_item_id, title, lesson_type, hours, sort_order
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
    bool $oneRowPerHour = false
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
    if (ktp_is_attestation_type($lessonType)) {
        $title = ktp_attestation_title($lessonType);
    }

    $hours = normalize_ktp_hours($hours);

    // Экзамен — всегда одна строка с полным числом часов
    if ($lessonType === 'exam') {
        $oneRowPerHour = false;
    }

    $stmt = db()->prepare(
        'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ktp_topics WHERE curriculum_item_id = ?'
    );
    $stmt->execute([$curriculumItemId]);
    $sortOrder = (int) $stmt->fetchColumn();

    $stmt = db()->prepare(
        'INSERT INTO ktp_topics (curriculum_item_id, title, lesson_type, hours, sort_order)
         VALUES (?, ?, ?, ?, ?)'
    );

    if (!$oneRowPerHour) {
        $stmt->execute([$curriculumItemId, $title, $lessonType, $hours, $sortOrder]);

        return ['success' => true, 'id' => (int) db()->lastInsertId(), 'count' => 1];
    }

    $rowsCount = (int) round($hours);
    if ($rowsCount < 1) {
        $rowsCount = 1;
    }
    if ($rowsCount > 24) {
        $rowsCount = 24;
    }

    $ids = [];
    $pdo = db();
    $pdo->beginTransaction();

    try {
        for ($i = 0; $i < $rowsCount; $i++) {
            $stmt->execute([$curriculumItemId, $title, $lessonType, 1.0, $sortOrder + $i]);
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
        'SELECT kt.id, kt.curriculum_item_id, kt.title, kt.lesson_type, kt.hours, kt.sort_order,
                COUNT(jl.id) AS used_count,
                MIN(jl.lesson_date) AS first_lesson_date,
                MAX(jl.lesson_date) AS last_lesson_date
         FROM ktp_topics kt
         LEFT JOIN journal_lessons jl ON jl.ktp_topic_id = kt.id
         WHERE kt.curriculum_item_id = ?
         GROUP BY kt.id, kt.curriculum_item_id, kt.title, kt.lesson_type, kt.hours, kt.sort_order
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
    bool $oneRowPerHour = false
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
    if (ktp_is_attestation_type($lessonType)) {
        $title = ktp_attestation_title($lessonType);
    }

    $hours = normalize_ktp_hours($hours);
    $curriculumItemId = (int) $topic['curriculum_item_id'];
    $currentSort = (int) $topic['sort_order'];

    // Экзамен всегда одна строка с указанным числом часов
    if ($lessonType === 'exam') {
        $oneRowPerHour = false;
    }

    if (!$oneRowPerHour) {
        $stmt = db()->prepare(
            'UPDATE ktp_topics SET title = ?, lesson_type = ?, hours = ? WHERE id = ?'
        );
        $stmt->execute([$title, $lessonType, $hours, $topicId]);

        return ['success' => true, 'count' => 1, 'added' => 0];
    }

    $rowsCount = (int) round($hours);
    if ($rowsCount < 1) {
        $rowsCount = 1;
    }
    if ($rowsCount > 24) {
        $rowsCount = 24;
    }

    $extraRows = $rowsCount - 1;
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'UPDATE ktp_topics SET title = ?, lesson_type = ?, hours = 1 WHERE id = ?'
        );
        $stmt->execute([$title, $lessonType, $topicId]);

        if ($extraRows > 0) {
            $shift = $pdo->prepare(
                'UPDATE ktp_topics
                 SET sort_order = sort_order + ?
                 WHERE curriculum_item_id = ? AND sort_order > ?'
            );
            $shift->execute([$extraRows, $curriculumItemId, $currentSort]);

            $insert = $pdo->prepare(
                'INSERT INTO ktp_topics (curriculum_item_id, title, lesson_type, hours, sort_order)
                 VALUES (?, ?, ?, 1, ?)'
            );

            for ($i = 1; $i <= $extraRows; $i++) {
                $insert->execute([
                    $curriculumItemId,
                    $title,
                    $lessonType,
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
