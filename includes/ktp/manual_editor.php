<?php

declare(strict_types=1);

/** @var int $itemId */
/** @var array $item */
/** @var array $topics */
/** @var array $ktpSummary */
/** @var string|null $success */
/** @var string|null $error */
/** @var string $backUrl */

/** @var array $ktpTopicFormDraft */
/** @var array $ktpAttestationFormDraft */

$isProfessionality = curriculum_item_is_professionality($item);
$formatHours = static function ($hours): string {
    return rtrim(rtrim(number_format((float) $hours, 1, '.', ''), '0'), '.');
};
?>
<p class="text-muted">
    Группа <?= e($item['group_number']) ?> · <?= e(semester_label($item['semester'])) ?> ·
    <?= e($item['academic_year']) ?>.<?php if ($isProfessionality): ?>
    <span class="badge badge--group-label">Профессионалитет</span><?php endif; ?>
    Темы подгружаются в электронный журнал для всех преподавателей.
    Порядок тем можно менять перетаскиванием.
</p>

<?php if ($success): ?>
    <div class="alert alert--success"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php endif; ?>
<p class="ktp-reorder-status text-muted" data-ktp-reorder-status hidden></p>

<?php if ($topics === []): ?>
    <p class="text-muted">Темы пока не добавлены.</p>
<?php else: ?>
    <div class="covered-summary ktp-plan-summary">
        <h3>Сводка КТП</h3>
        <?php require __DIR__ . '/summary_table.php'; render_ktp_plan_summary_table($item, $topics); ?>
    </div>

    <div class="table-wrap">
        <table class="table ktp-edit-table">
            <thead>
                <tr>
                    <th class="ktp-col-handle"></th>
                    <th>№</th>
                    <th>Тема</th>
                    <th>Тип</th>
                    <th>Часы</th>
                    <th>Сроки</th>
                    <th>ОК / ПК</th>
                    <th>Форма контроля</th>
                    <th class="table__actions-col">Действия</th>
                </tr>
            </thead>
            <tbody
                data-ktp-sortable
                data-item-id="<?= (int) $itemId ?>"
                data-reorder-url="ktp_reorder.php"
                data-csrf="<?= e(csrf_token()) ?>"
            >
                <?php foreach ($topics as $index => $topic): ?>
                <?php
                $isSemesterMarker = ktp_is_semester_marker_type((string) ($topic['lesson_type'] ?? ''));
                $rowClass = 'ktp-sortable-row';
                if ($isSemesterMarker) {
                    $rowClass .= ' ktp-row--semester-marker';
                }
                ?>
                <tr
                    class="<?= e($rowClass) ?>"
                    data-topic-id="<?= (int) $topic['id'] ?>"
                >
                    <td class="ktp-col-handle">
                        <span class="ktp-drag-handle" title="Перетащить" aria-hidden="true">⋮⋮</span>
                    </td>
                    <td class="ktp-col-num"<?= $isSemesterMarker ? '' : ' data-ktp-num' ?>><?php
                        $topicNum = ktp_topic_display_number($topics, $index);
                        echo $topicNum !== null ? (int) $topicNum : '';
                    ?></td>
                    <td<?= $isSemesterMarker ? ' colspan="6"' : '' ?>>
                        <?php if ($isSemesterMarker): ?>
                            <strong><?= e(ktp_semester_marker_title((string) ($topic['lesson_type'] ?? 'semester_2'))) ?></strong>
                        <?php else: ?>
                            <?= e($topic['title']) ?>
                        <?php endif; ?>
                    </td>
                    <?php if (!$isSemesterMarker): ?>
                    <td><?= e(ktp_lesson_type_label((string) $topic['lesson_type'])) ?></td>
                    <td><?= e(format_ktp_topic_hours($topic, $isProfessionality)) ?></td>
                    <td><?= e(format_ktp_deadline_date($topic['deadline_date'] ?? null)) ?></td>
                    <td class="ktp-competency-cell"><?php render_ktp_competency_cell($topic['ok_codes'] ?? null, $topic['pk_codes'] ?? null); ?></td>
                    <td><?= e(ktp_control_form_label($topic['control_form'] ?? null)) ?></td>
                    <?php endif; ?>
                    <td class="table__actions">
                        <?php if (!$isSemesterMarker): ?>
                        <button
                            type="button"
                            class="journal-icon-btn"
                            title="Изменить"
                            data-ktp-edit-open
                            data-topic-id="<?= (int) $topic['id'] ?>"
                            data-title="<?= e($topic['title']) ?>"
                            data-lesson-type="<?= e((string) $topic['lesson_type']) ?>"
                            data-hours="<?= e($formatHours($topic['hours'])) ?>"
                            data-orientation-hours="<?= e($formatHours($topic['orientation_hours'] ?? 0)) ?>"
                            data-deadline="<?= e((string) ($topic['deadline_date'] ?? '')) ?>"
                            data-ok-codes="<?= e((string) ($topic['ok_codes'] ?? '')) ?>"
                            data-pk-codes="<?= e((string) ($topic['pk_codes'] ?? '')) ?>"
                            data-control-form="<?= e((string) ($topic['control_form'] ?? '')) ?>"
                        >✎</button>
                        <?php endif; ?>
                        <form method="post" class="form-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_ktp_topic">
                            <input type="hidden" name="topic_id" value="<?= (int) $topic['id'] ?>">
                            <button
                                type="submit"
                                class="journal-icon-btn journal-icon-btn--danger"
                                title="Удалить"
                                onclick="return confirm('Удалить строку КТП?')"
                            >×</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<hr class="divider">

<h3 class="subsection-title">Новая тема</h3>
<p class="text-muted form-hint">
    Число часов создаёт столько же строк (по 1 часу). Для самостоятельной работы выберите соответствующий тип.<?php if ($isProfessionality): ?>
    Для лекций и практик укажите профориентированные часы — в таблице они отображаются через слэш (2/1).<?php endif; ?>
</p>
<form method="post" class="form" data-ktp-topic-form data-ktp-professionality="<?= $isProfessionality ? '1' : '0' ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_ktp_topic">

    <div class="form__row ktp-topic-row">
        <div class="form__group form__group--ktp-title">
            <label for="ktp_title">Тема</label>
            <input
                type="text"
                id="ktp_title"
                name="ktp_title"
                required
                placeholder="Тема урока"
                value="<?= e((string) ($ktpTopicFormDraft['ktp_title'] ?? '')) ?>"
            >
        </div>
        <div class="form__group form__group--compact">
            <label for="ktp_lesson_type">Тип</label>
            <select id="ktp_lesson_type" name="ktp_lesson_type" class="ktp-field-compact" data-ktp-lesson-type>
                <?php $topicLessonType = (string) ($ktpTopicFormDraft['ktp_lesson_type'] ?? 'lecture'); ?>
                <option value="lecture"<?= $topicLessonType === 'lecture' ? ' selected' : '' ?>>Лекция</option>
                <option value="practice"<?= $topicLessonType === 'practice' ? ' selected' : '' ?>>Практика</option>
                <option value="independent"<?= $topicLessonType === 'independent' ? ' selected' : '' ?>>Самостоятельная работа</option>
            </select>
        </div>
        <div class="form__group form__group--compact">
            <label for="ktp_hours">Часы (строк)</label>
            <input
                type="number"
                id="ktp_hours"
                name="ktp_hours"
                class="ktp-field-compact"
                min="1"
                max="24"
                step="1"
                value="<?= e((string) ($ktpTopicFormDraft['ktp_hours'] ?? '2')) ?>"
                required
            >
        </div>
        <?php if ($isProfessionality): ?>
        <div class="form__group form__group--compact" data-ktp-orientation-field hidden>
            <label for="ktp_orientation_hours">Профориентированные</label>
            <input
                type="number"
                id="ktp_orientation_hours"
                name="ktp_orientation_hours"
                class="ktp-field-compact"
                min="0"
                max="24"
                step="1"
                value="<?= e((string) ($ktpTopicFormDraft['ktp_orientation_hours'] ?? '0')) ?>"
            >
        </div>
        <?php endif; ?>
    </div>

    <?php
    $fieldPrefix = 'topic_';
    $postData = $ktpTopicFormDraft;
    require __DIR__ . '/topic_extra_fields.php';
    ?>

    <div class="form__actions">
        <button type="submit" class="btn btn--primary">Добавить тему</button>
    </div>
</form>

<hr class="divider">

<h3 class="subsection-title">Промежуточная аттестация</h3>
<p class="text-muted form-hint">
    Экзамен — одна строка с указанным числом часов.
    Остальные виды — по одной строке на каждый час.
</p>
<form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_ktp_attestation">

    <div class="form__row">
        <div class="form__group">
            <label for="attestation_type">Вид аттестации</label>
            <?php $attestationType = (string) ($ktpAttestationFormDraft['attestation_type'] ?? 'diff_credit'); ?>
            <select id="attestation_type" name="attestation_type" required>
                <option value="diff_credit"<?= $attestationType === 'diff_credit' ? ' selected' : '' ?>>Дифференцированный зачёт</option>
                <option value="credit"<?= $attestationType === 'credit' ? ' selected' : '' ?>>Зачёт</option>
                <option value="exam"<?= $attestationType === 'exam' ? ' selected' : '' ?>>Экзамен</option>
                <option value="control"<?= $attestationType === 'control' ? ' selected' : '' ?>>Контрольная работа</option>
            </select>
        </div>
        <div class="form__group">
            <label for="attestation_hours">Количество часов</label>
            <input
                type="number"
                id="attestation_hours"
                name="attestation_hours"
                min="1"
                max="24"
                step="1"
                value="<?= e((string) ($ktpAttestationFormDraft['attestation_hours'] ?? '1')) ?>"
                required
            >
        </div>
    </div>

    <div class="form__actions">
        <button type="submit" class="btn btn--primary">Добавить аттестацию</button>
    </div>
</form>

<?php if (curriculum_item_ends_after_attestation($item) || curriculum_item_spans_two_semesters($item)): ?>
<hr class="divider">
<?php endif; ?>

<?php if (curriculum_item_ends_after_attestation($item)): ?>
<h3 class="subsection-title">После промежуточной аттестации</h3>
<p class="text-muted form-hint">
    Предмет ведётся только в 1 семестре. После промежуточной аттестации журнал по этому предмету не заполняется.
</p>
<?php elseif (curriculum_item_spans_two_semesters($item)): ?>
<h3 class="subsection-title">Разделители семестров</h3>
<p class="text-muted form-hint">
    Для предметов на оба семестра добавьте разделители начала 1 и 2 семестра,
    затем продолжайте заполнять темы КТП.
</p>
<div class="form__actions">
<?php if (!has_ktp_semester_marker($itemId, 'semester_1')): ?>
<form method="post" class="form form--inline">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_ktp_semester_marker">
    <input type="hidden" name="marker_type" value="semester_1">
    <button type="submit" class="btn btn--ghost">Добавить «1 семестр (полугодие)»</button>
</form>
<?php endif; ?>
<?php if (!has_ktp_semester_marker($itemId, 'semester_2')): ?>
<form method="post" class="form form--inline">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_ktp_semester_marker">
    <input type="hidden" name="marker_type" value="semester_2">
    <button type="submit" class="btn btn--primary">Добавить «2 семестр (полугодие)»</button>
</form>
<?php endif; ?>
<?php if (has_ktp_semester_marker($itemId, 'semester_1') && has_ktp_semester_marker($itemId, 'semester_2')): ?>
<p class="text-muted">Разделители 1 и 2 семестра уже добавлены в таблицу КТП.</p>
<?php endif; ?>
</div>
<?php endif; ?>

<div class="modal" data-ktp-edit-modal data-ktp-professionality="<?= $isProfessionality ? '1' : '0' ?>" hidden>
    <div class="modal__backdrop" data-ktp-edit-close></div>
    <div class="modal__dialog modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="ktp-edit-title">
        <div class="modal__header">
            <h2 id="ktp-edit-title">Редактировать тему</h2>
            <button type="button" class="modal__close" data-ktp-edit-close aria-label="Закрыть">&times;</button>
        </div>
        <form method="post" class="form" data-ktp-edit-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_ktp_topic">
            <input type="hidden" name="topic_id" value="" data-ktp-edit-id>

            <div class="form__group">
                <label for="modal_ktp_title">Тема</label>
                <input type="text" id="modal_ktp_title" name="ktp_title" required data-ktp-edit-title>
            </div>

            <div class="form__row ktp-topic-row">
                <div class="form__group form__group--compact">
                    <label for="modal_ktp_lesson_type">Тип</label>
                    <select id="modal_ktp_lesson_type" name="ktp_lesson_type" class="ktp-field-compact" data-ktp-edit-type>
                        <option value="lecture">Лекция</option>
                        <option value="practice">Практика</option>
                        <option value="independent">Самостоятельная работа</option>
                        <option value="diff_credit">Дифференцированный зачёт</option>
                        <option value="credit">Зачёт</option>
                        <option value="exam">Экзамен</option>
                        <option value="control">Контрольная работа</option>
                    </select>
                </div>
                <div class="form__group form__group--compact">
                    <label for="modal_ktp_hours">Часы</label>
                    <input
                        type="number"
                        id="modal_ktp_hours"
                        name="ktp_hours"
                        class="ktp-field-compact"
                        min="1"
                        max="24"
                        step="1"
                        value="1"
                        required
                        data-ktp-edit-hours
                    >
                </div>
                <?php if ($isProfessionality): ?>
                <div class="form__group form__group--compact" data-ktp-orientation-field hidden>
                    <label for="modal_ktp_orientation_hours">Профориентированные</label>
                    <input
                        type="number"
                        id="modal_ktp_orientation_hours"
                        name="ktp_orientation_hours"
                        class="ktp-field-compact"
                        min="0"
                        max="24"
                        step="1"
                        value="0"
                        data-ktp-edit-orientation
                    >
                </div>
                <?php endif; ?>
                <div class="form__group form__group--compact">
                    <label for="modal_ktp_deadline">Сроки</label>
                    <input type="date" id="modal_ktp_deadline" class="ktp-field-compact" name="ktp_deadline" data-ktp-edit-deadline>
                </div>
                <div class="form__group form__group--compact">
                    <label for="modal_ktp_control_form">Форма контроля</label>
                    <select id="modal_ktp_control_form" name="ktp_control_form" class="ktp-field-compact" data-ktp-edit-control>
                        <option value="">— Не выбрано —</option>
                        <?php foreach (ktp_control_form_options() as $value => $label): ?>
                        <option value="<?= e($value) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php render_ktp_competency_fields('modal_'); ?>

            <p class="text-muted form-hint" data-ktp-edit-hours-hint>
                Для экзамена — часы одной строки. Для остальных типов число больше 1 добавит строки под текущей.
            </p>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сохранить</button>
                <button type="button" class="btn btn--ghost" data-ktp-edit-close>Отмена</button>
            </div>
        </form>
    </div>
</div>
