<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/curriculum.php';

function curriculum_item_merge_label(array $item): string
{
    $name = trim((string) ($item['subject_name'] ?? ''));
    $type = (string) ($item['item_type'] ?? 'subject');
    $id = (int) ($item['id'] ?? $item['curriculum_item_id'] ?? 0);
    $parentId = (int) ($item['parent_item_id'] ?? 0);

    $suffix = match ($type) {
        'mdk' => 'МДК',
        'composite' => 'ПМ/комплекс',
        'practice' => 'практика',
        default => 'предмет',
    };
    if ($parentId > 0) {
        $suffix .= ', внутри ПМ';
    }

    return $name . ' · ' . $suffix . ' (id ' . $id . ')';
}

/** @return list<array<string, mixed>> */
function get_curriculum_plan_items_for_merge(int $planId): array
{
    require_once __DIR__ . '/curriculum_composites.php';
    $typeCol = db()->query("SHOW COLUMNS FROM curriculum_items LIKE 'item_type'")->fetch();
    $parentCol = curriculum_items_has_parent_column();

    $sql = 'SELECT ci.id, ci.curriculum_plan_id, ci.subject_id, ci.semester, ci.sort_order,
                   ' . ($typeCol ? 'ci.item_type,' : "'subject' AS item_type,") . '
                   ' . ($parentCol ? 'ci.parent_item_id,' : '0 AS parent_item_id,') . '
                   sub.name AS subject_name
            FROM curriculum_items ci
            INNER JOIN subjects sub ON sub.id = ci.subject_id
            WHERE ci.curriculum_plan_id = ?
            ORDER BY sub.name ASC, ci.id ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute([$planId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['curriculum_item_id'] = (int) $row['id'];
        $row['parent_item_id'] = (int) ($row['parent_item_id'] ?? 0);
        $row['label'] = curriculum_item_merge_label($row);
    }
    unset($row);

    return $rows;
}

function curriculum_item_table_exists(string $table): bool
{
    return (bool) db()->query("SHOW TABLES LIKE " . db()->quote($table))->fetch();
}

function curriculum_item_count_rows(string $table, string $column, int $itemId): int
{
    if (!curriculum_item_table_exists($table)) {
        return 0;
    }
    $stmt = db()->prepare("SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?");
    $stmt->execute([$itemId]);

    return (int) $stmt->fetchColumn();
}

/**
 * @return array{
 *   ok: bool,
 *   error?: string,
 *   from?: array,
 *   to?: array,
 *   counts?: array<string, int>,
 *   target_lessons?: int
 * }
 */
function preview_curriculum_item_merge(int $fromId, int $toId): array
{
    if ($fromId <= 0 || $toId <= 0) {
        return ['ok' => false, 'error' => 'Укажите оба предмета.'];
    }
    if ($fromId === $toId) {
        return ['ok' => false, 'error' => 'Источник и назначение должны отличаться.'];
    }

    $from = get_curriculum_item_by_id($fromId);
    $to = get_curriculum_item_by_id($toId);
    if ($from === null || $to === null) {
        return ['ok' => false, 'error' => 'Предмет не найден.'];
    }
    if ((int) $from['curriculum_plan_id'] !== (int) $to['curriculum_plan_id']) {
        return ['ok' => false, 'error' => 'Оба предмета должны быть в одном учебном плане.'];
    }

    $targetLessons = curriculum_item_count_rows('journal_lessons', 'curriculum_item_id', $toId);
    $counts = [
        'journal_lessons' => curriculum_item_count_rows('journal_lessons', 'curriculum_item_id', $fromId),
        'journal_grades' => 0,
        'grade_entries' => curriculum_item_count_rows('grade_entries', 'curriculum_item_id', $fromId),
        'ktp_topics' => curriculum_item_count_rows('ktp_topics', 'curriculum_item_id', $fromId),
        'ktp_work_programs' => curriculum_item_count_rows('ktp_work_programs', 'curriculum_item_id', $fromId),
        'ktp_item_settings' => curriculum_item_count_rows('ktp_item_settings', 'curriculum_item_id', $fromId),
        'student_record_book' => curriculum_item_count_rows('student_record_book', 'curriculum_item_id', $fromId),
        'glaz_schedules' => curriculum_item_count_rows('glaz_schedules', 'curriculum_item_id', $fromId),
        'manual_brs_sheets' => curriculum_item_count_rows('manual_brs_sheets', 'curriculum_item_id', $fromId),
        'manual_brs_attestations' => curriculum_item_count_rows('manual_brs_attestations', 'curriculum_item_id', $fromId),
    ];

    if ($counts['journal_lessons'] > 0 && curriculum_item_table_exists('journal_grades')) {
        $stmt = db()->prepare(
            'SELECT COUNT(*)
             FROM journal_grades jg
             INNER JOIN journal_lessons jl ON jl.id = jg.lesson_id
             WHERE jl.curriculum_item_id = ?'
        );
        $stmt->execute([$fromId]);
        $counts['journal_grades'] = (int) $stmt->fetchColumn();
    }

    return [
        'ok' => true,
        'from' => $from + ['label' => curriculum_item_merge_label($from)],
        'to' => $to + ['label' => curriculum_item_merge_label($to)],
        'counts' => $counts,
        'target_lessons' => $targetLessons,
    ];
}

/**
 * Перенос данных журнала и связанных записей с from → to.
 * Целевой журнал должен быть без уроков.
 *
 * @return array{success: bool, error?: string, moved?: array<string, int>, deleted_source?: bool}
 */
function merge_curriculum_item_data(int $fromId, int $toId, bool $deleteSource = false): array
{
    if (!can_manage_curriculum()) {
        return ['success' => false, 'error' => 'Недостаточно прав.'];
    }

    $preview = preview_curriculum_item_merge($fromId, $toId);
    if (empty($preview['ok'])) {
        return ['success' => false, 'error' => (string) ($preview['error'] ?? 'Ошибка проверки.')];
    }
    if ((int) ($preview['target_lessons'] ?? 0) > 0) {
        return [
            'success' => false,
            'error' => 'У целевого предмета уже есть даты в журнале. Перенос возможен только на пустой журнал.',
        ];
    }

    $pdo = db();
    $pdo->beginTransaction();
    $moved = [];

    try {
        $upd = static function (string $table) use ($pdo, $fromId, $toId, &$moved): void {
            if (!curriculum_item_table_exists($table)) {
                $moved[$table] = 0;
                return;
            }
            $stmt = $pdo->prepare(
                "UPDATE `{$table}` SET curriculum_item_id = ? WHERE curriculum_item_id = ?"
            );
            $stmt->execute([$toId, $fromId]);
            $moved[$table] = $stmt->rowCount();
        };

        // journal_lessons — основной перенос (оценки/Н остаются на lesson_id)
        $upd('journal_lessons');

        // grade_entries: конфликты UNIQUE (student, item)
        if (curriculum_item_table_exists('grade_entries')) {
            $pdo->prepare(
                'DELETE ge_from
                 FROM grade_entries ge_from
                 INNER JOIN grade_entries ge_to
                    ON ge_to.student_id = ge_from.student_id
                   AND ge_to.curriculum_item_id = ?
                 WHERE ge_from.curriculum_item_id = ?'
            )->execute([$toId, $fromId]);
            $stmt = $pdo->prepare(
                'UPDATE grade_entries SET curriculum_item_id = ? WHERE curriculum_item_id = ?'
            );
            $stmt->execute([$toId, $fromId]);
            $moved['grade_entries'] = $stmt->rowCount();
        }

        $upd('ktp_topics');

        if (curriculum_item_table_exists('ktp_work_programs')) {
            $toWp = curriculum_item_count_rows('ktp_work_programs', 'curriculum_item_id', $toId);
            if ($toWp > 0) {
                $pdo->prepare('DELETE FROM ktp_work_programs WHERE curriculum_item_id = ?')->execute([$fromId]);
                $moved['ktp_work_programs'] = 0;
            } else {
                $upd('ktp_work_programs');
            }
        }

        if (curriculum_item_table_exists('ktp_item_settings')) {
            $toSet = curriculum_item_count_rows('ktp_item_settings', 'curriculum_item_id', $toId);
            if ($toSet > 0) {
                $pdo->prepare('DELETE FROM ktp_item_settings WHERE curriculum_item_id = ?')->execute([$fromId]);
                $moved['ktp_item_settings'] = 0;
            } else {
                $upd('ktp_item_settings');
            }
        }

        if (curriculum_item_table_exists('student_record_book')) {
            $pdo->prepare(
                'DELETE rb_from
                 FROM student_record_book rb_from
                 INNER JOIN student_record_book rb_to
                    ON rb_to.student_id = rb_from.student_id
                   AND rb_to.academic_year = rb_from.academic_year
                   AND rb_to.semester = rb_from.semester
                   AND rb_to.curriculum_item_id = ?
                 WHERE rb_from.curriculum_item_id = ?'
            )->execute([$toId, $fromId]);
            $stmt = $pdo->prepare(
                'UPDATE student_record_book SET curriculum_item_id = ? WHERE curriculum_item_id = ?'
            );
            $stmt->execute([$toId, $fromId]);
            $moved['student_record_book'] = $stmt->rowCount();
        }

        if (curriculum_item_table_exists('glaz_schedules')) {
            $pdo->prepare(
                'DELETE gs_from
                 FROM glaz_schedules gs_from
                 INNER JOIN glaz_schedules gs_to
                    ON gs_to.student_id = gs_from.student_id
                   AND gs_to.curriculum_item_id = ?
                   AND gs_to.academic_year = gs_from.academic_year
                   AND gs_to.semester = gs_from.semester
                   AND gs_to.source = gs_from.source
                 WHERE gs_from.curriculum_item_id = ?'
            )->execute([$toId, $fromId]);
            $stmt = $pdo->prepare(
                'UPDATE glaz_schedules SET curriculum_item_id = ? WHERE curriculum_item_id = ?'
            );
            $stmt->execute([$toId, $fromId]);
            $moved['glaz_schedules'] = $stmt->rowCount();
        }

        if (curriculum_item_table_exists('manual_brs_sheets')) {
            $pdo->prepare(
                'DELETE s_from
                 FROM manual_brs_sheets s_from
                 INNER JOIN manual_brs_sheets s_to
                    ON s_to.academic_year = s_from.academic_year
                   AND s_to.period = s_from.period
                   AND s_to.curriculum_item_id = ?
                 WHERE s_from.curriculum_item_id = ?'
            )->execute([$toId, $fromId]);
            $stmt = $pdo->prepare(
                'UPDATE manual_brs_sheets SET curriculum_item_id = ? WHERE curriculum_item_id = ?'
            );
            $stmt->execute([$toId, $fromId]);
            $moved['manual_brs_sheets'] = $stmt->rowCount();
        }

        if (curriculum_item_table_exists('manual_brs_attestations')) {
            $pdo->prepare(
                'DELETE a_from
                 FROM manual_brs_attestations a_from
                 INNER JOIN manual_brs_attestations a_to
                    ON a_to.academic_year = a_from.academic_year
                   AND a_to.semester = a_from.semester
                   AND a_to.student_id = a_from.student_id
                   AND a_to.curriculum_item_id = ?
                 WHERE a_from.curriculum_item_id = ?'
            )->execute([$toId, $fromId]);
            $stmt = $pdo->prepare(
                'UPDATE manual_brs_attestations SET curriculum_item_id = ? WHERE curriculum_item_id = ?'
            );
            $stmt->execute([$toId, $fromId]);
            $moved['manual_brs_attestations'] = $stmt->rowCount();
        }

        $deletedSource = false;
        if ($deleteSource) {
            $del = delete_curriculum_item($fromId);
            if (empty($del['success'])) {
                throw new RuntimeException((string) ($del['error'] ?? 'Не удалось удалить исходный предмет.'));
            }
            $deletedSource = true;
        }

        $pdo->commit();

        return [
            'success' => true,
            'moved' => $moved,
            'deleted_source' => $deletedSource,
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();

        return ['success' => false, 'error' => 'Перенос не выполнен: ' . $e->getMessage()];
    }
}
