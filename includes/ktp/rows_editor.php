<?php

declare(strict_types=1);

/** @var int $itemId */
/** @var array $item */
/** @var array $topics */
/** @var array $ktpSummary */
/** @var array|null $ktpColumnWidths */
/** @var string|null $success */
/** @var string|null $error */

$ktpColumnWidths = $ktpColumnWidths ?? get_ktp_column_widths($itemId);
$isProfessionality = curriculum_item_is_professionality($item);
$formatHours = static function ($hours): string {
    return rtrim(rtrim(number_format((float) $hours, 1, '.', ''), '0'), '.');
};

$lessonTypeOptions = [
    'lecture' => 'Лекция',
    'practice' => 'Практика',
    'independent' => 'Самостоятельная работа',
    'diff_credit' => 'Дифференцированный зачёт',
    'credit' => 'Зачёт',
    'exam' => 'Экзамен',
    'control' => 'Контрольная работа',
];

$okCodesAll = ktp_competency_codes('OK');
$pkCodesAll = ktp_competency_codes('PK');
$controlOptions = ktp_control_form_options();

$renderRow = static function (
    array $topic,
    int $index,
    array $topics,
    bool $isProfessionality,
    array $lessonTypeOptions,
    array $okCodesAll,
    array $pkCodesAll,
    array $controlOptions,
    callable $formatHours
): void {
    $isSemesterMarker = ktp_is_semester_marker_type((string) ($topic['lesson_type'] ?? ''));
    $topicId = (int) $topic['id'];
    $lessonType = (string) ($topic['lesson_type'] ?? 'lecture');
    $okSelected = parse_ktp_competency_codes($topic['ok_codes'] ?? null);
    $pkSelected = parse_ktp_competency_codes($topic['pk_codes'] ?? null);
    $okMap = array_flip($okSelected);
    $pkMap = array_flip($pkSelected);
    $rowClass = 'ktp-rows-row ktp-sortable-row';
    if ($isSemesterMarker) {
        $rowClass .= ' ktp-row--semester-marker';
    }
    ?>
    <tr class="<?= e($rowClass) ?>" data-ktp-row data-topic-id="<?= $topicId ?>"<?= $isSemesterMarker ? ' data-lesson-type="' . e($lessonType) . '"' : '' ?>>
        <td class="ktp-col-handle">
            <span class="ktp-drag-handle" title="Перетащить" aria-hidden="true">⋮⋮</span>
        </td>
        <td class="ktp-col-num"<?= $isSemesterMarker ? '' : ' data-ktp-num' ?>><?php
            $topicNum = ktp_topic_display_number($topics, $index);
            echo $topicNum !== null ? (int) $topicNum : '';
        ?></td>
        <?php if ($isSemesterMarker): ?>
        <td colspan="6">
            <strong><?= e(ktp_semester_marker_title($lessonType)) ?></strong>
        </td>
        <?php else: ?>
        <td
            class="ktp-rows-title-cell"
            data-ktp-field="title"
            contenteditable="<?= ktp_is_attestation_type($lessonType) ? 'false' : 'true' ?>"
            data-placeholder="Тема урока"
        ><?= e((string) $topic['title']) ?></td>
        <td>
            <select class="ktp-inline-select" name="ktp_lesson_type" data-ktp-field="lesson_type">
                <?php foreach ($lessonTypeOptions as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= $lessonType === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="ktp-rows-hours-cell">
            <div class="ktp-rows-hours-inner">
                <input
                    type="number"
                    class="ktp-inline-input ktp-inline-input--num"
                    name="ktp_hours"
                    data-ktp-field="hours"
                    min="1"
                    max="24"
                    step="1"
                    title="Часы на тему"
                    aria-label="Часы на тему"
                    value="<?= e($formatHours($topic['hours'] ?? 1)) ?>"
                >
                <?php if ($isProfessionality): ?>
                <span class="ktp-rows-hours-sep" data-ktp-orientation-sep<?= ktp_topic_supports_orientation_hours($lessonType) ? '' : ' hidden' ?>>/</span>
                <input
                    type="number"
                    class="ktp-inline-input ktp-inline-input--num"
                    name="ktp_orientation_hours"
                    data-ktp-field="orientation_hours"
                    data-ktp-orientation-input
                    min="0"
                    max="24"
                    step="1"
                    title="Профориентированные часы"
                    aria-label="Профориентированные часы"
                    value="<?= e($formatHours($topic['orientation_hours'] ?? 0)) ?>"
                    <?= ktp_topic_supports_orientation_hours($lessonType) ? '' : 'hidden' ?>
                >
                <?php endif; ?>
            </div>
        </td>
        <td>
            <input
                type="date"
                class="ktp-inline-input"
                name="ktp_deadline"
                data-ktp-field="deadline"
                value="<?= e((string) ($topic['deadline_date'] ?? '')) ?>"
            >
        </td>
        <td class="ktp-competency-cell">
            <details class="ktp-comp-picker" data-ktp-comp-picker>
                <summary class="ktp-comp-picker__summary">
                    <span data-ktp-comp-summary>
                        <?php
                        $okLabel = format_ktp_competency_codes_list($topic['ok_codes'] ?? null);
                        $pkLabel = format_ktp_competency_codes_list($topic['pk_codes'] ?? null);
                        $parts = [];
                        if ($okLabel !== '') {
                            $parts[] = 'ОК: ' . $okLabel;
                        }
                        if ($pkLabel !== '') {
                            $parts[] = 'ПК: ' . $pkLabel;
                        }
                        echo e($parts !== [] ? implode('; ', $parts) : '—');
                        ?>
                    </span>
                </summary>
                <div class="ktp-comp-picker__panel">
                    <div class="ktp-comp-picker__group">
                        <strong>ОК</strong>
                        <?php foreach ($okCodesAll as $code): ?>
                        <label class="ktp-competency-check">
                            <input type="checkbox" name="ok_codes[]" value="<?= e($code) ?>" data-ktp-field="ok"<?= isset($okMap[$code]) ? ' checked' : '' ?>>
                            <span><?= e(ktp_competency_label($code)) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="ktp-comp-picker__group">
                        <strong>ПК</strong>
                        <?php foreach ($pkCodesAll as $code): ?>
                        <label class="ktp-competency-check">
                            <input type="checkbox" name="pk_codes[]" value="<?= e($code) ?>" data-ktp-field="pk"<?= isset($pkMap[$code]) ? ' checked' : '' ?>>
                            <span><?= e(ktp_competency_label($code)) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
        </td>
        <td>
            <select class="ktp-inline-select" name="ktp_control_form" data-ktp-field="control_form">
                <option value="">— Не выбрано —</option>
                <?php foreach ($controlOptions as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= ($topic['control_form'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <?php endif; ?>
        <td class="table__actions">
            <button
                type="button"
                class="journal-icon-btn journal-icon-btn--danger"
                title="Удалить"
                data-ktp-row-delete
            >×</button>
        </td>
    </tr>
    <?php
};
?>
<p class="text-muted">
    Группа <?= e($item['group_number']) ?> · <?= e(semester_label($item['semester'])) ?> ·
    <?= e($item['academic_year']) ?>.<?php if ($isProfessionality): ?>
    <span class="badge badge--group-label">Профессионалитет</span><?php endif; ?>
    Редактируйте ячейки прямо в таблице. Изменения сохраняются автоматически. Порядок строк меняйте перетаскиванием за ⋮⋮.
</p>

<?php if ($success): ?>
    <div class="alert alert--success"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php endif; ?>
<p class="ktp-rows-status text-muted" data-ktp-rows-status hidden></p>

<?php if ($topics !== []): ?>
<div class="covered-summary ktp-plan-summary">
    <h3>Сводка КТП</h3>
    <?php require __DIR__ . '/summary_table.php'; render_ktp_plan_summary_table($item, $topics, true); ?>
</div>
<?php endif; ?>

<div class="table-wrap">
    <table
        class="table ktp-edit-table ktp-rows-table"
        data-ktp-rows-editor
        data-col-resize
        data-col-resize-key="ktp-rows"
        data-col-resize-persist-cols="<?= ktp_view_column_count() ?>"
        data-col-resize-persist-offset="1"
        data-col-resize-save-url="ktp_row_action.php"
        data-item-id="<?= (int) $itemId ?>"
        data-action-url="ktp_row_action.php"
        data-csrf="<?= e(csrf_token()) ?>"
        data-professionality="<?= $isProfessionality ? '1' : '0' ?>"
        <?php if ($ktpColumnWidths !== null): ?>
        data-column-widths="<?= e(json_encode($ktpColumnWidths, JSON_UNESCAPED_UNICODE)) ?>"
        <?php endif; ?>
    >
        <thead>
            <tr>
                <th class="ktp-col-handle"></th>
                <th class="ktp-col-num-head"<?= ktp_column_width_attr($ktpColumnWidths, 0) ?>>№</th>
                <th<?= ktp_column_width_attr($ktpColumnWidths, 1) ?>>Тема</th>
                <th<?= ktp_column_width_attr($ktpColumnWidths, 2) ?>>Тип</th>
                <th class="ktp-col-hours-head"<?= ktp_column_width_attr($ktpColumnWidths, 3) ?>>Часы</th>
                <th<?= ktp_column_width_attr($ktpColumnWidths, 4) ?>>Сроки</th>
                <th<?= ktp_column_width_attr($ktpColumnWidths, 5) ?>>ОК / ПК</th>
                <th<?= ktp_column_width_attr($ktpColumnWidths, 6) ?>>Форма контроля</th>
                <th class="table__actions-col"></th>
            </tr>
        </thead>
        <tbody
            data-ktp-rows-body
            data-ktp-sortable
            data-reorder-url="ktp_reorder.php"
            data-csrf="<?= e(csrf_token()) ?>"
            data-item-id="<?= (int) $itemId ?>"
            data-reorder-status="[data-ktp-rows-status]"
        >
            <?php foreach ($topics as $index => $topic): ?>
                <?php
                $renderRow(
                    $topic,
                    $index,
                    $topics,
                    $isProfessionality,
                    $lessonTypeOptions,
                    $okCodesAll,
                    $pkCodesAll,
                    $controlOptions,
                    $formatHours
                );
                ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<template id="ktp-rows-row-template">
    <?php
    $renderRow(
        [
            'id' => 0,
            'title' => '',
            'lesson_type' => 'lecture',
            'hours' => 1,
            'orientation_hours' => 0,
            'deadline_date' => '',
            'ok_codes' => '',
            'pk_codes' => '',
            'control_form' => '',
            'completed' => false,
        ],
        0,
        [['lesson_type' => 'lecture']],
        $isProfessionality,
        $lessonTypeOptions,
        $okCodesAll,
        $pkCodesAll,
        $controlOptions,
        $formatHours
    );
    ?>
</template>

<div class="ktp-rows-toolbar">
    <button type="button" class="btn btn--primary" data-ktp-row-insert>Вставить строку</button>
    <button type="button" class="btn btn--ghost" data-ktp-row-copy>Скопировать строку</button>
    <button type="button" class="btn btn--ghost" data-ktp-row-insert-marker data-semester="1">1 семестр</button>
    <button type="button" class="btn btn--ghost" data-ktp-row-insert-marker data-semester="2">2 семестр</button>
</div>

<?php if (curriculum_item_spans_two_semesters($item)): ?>
<hr class="divider">
<p class="text-muted">
    Для предметов на оба семестра вставьте разделители «1 семестр» и «2 семестр» в нужных местах таблицы —
    часы до/после разделителей попадут в соответствующие столбцы сводки.
</p>
<?php endif; ?>
