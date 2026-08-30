<?php

declare(strict_types=1);

/** @var int $itemId */
/** @var array $item */
/** @var string|null $success */
/** @var string|null $error */
/** @var array|null $wordImportPreview */

$wordImportPreview = $wordImportPreview ?? ktp_word_import_get_preview($itemId);
$isProfessionality = curriculum_item_is_professionality($item);
?>

<?php if ($success): ?>
    <div class="alert alert--success"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php endif; ?>

<p class="text-muted">
    Загрузите рабочую программу в формате Word (.docx). Из документа будет найдена таблица
    с заголовками «Наименование разделов и тем», «Содержание учебного материала…»,
    «Объем, ак. ч.» и «Коды компетенций…», после чего строки будут перенесены в КТП.
</p>

<form method="post" enctype="multipart/form-data" class="form form--medium">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="upload_word_ktp">
    <div class="form__group">
        <label for="word_program">Файл Word (.docx)</label>
        <input type="file" id="word_program" name="word_program" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
    </div>
    <div class="form__actions">
        <button type="submit" class="btn btn--primary">Загрузить и разобрать</button>
    </div>
</form>

<?php if ($wordImportPreview !== null && !empty($wordImportPreview['rows'])): ?>
    <hr class="divider">
    <h3>Предпросмотр импорта</h3>
    <p class="text-muted">
        Файл: <?= e((string) ($wordImportPreview['filename'] ?? '')) ?>
        · строк: <?= count($wordImportPreview['rows']) ?>
    </p>

    <div class="table-wrap">
        <table class="table ktp-rows-table">
            <thead>
                <tr>
                    <th class="ktp-col-num-head">№</th>
                    <th>Тема</th>
                    <th>Тип</th>
                    <th class="ktp-col-hours-head">Часы</th>
                    <th>ОК / ПК</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($wordImportPreview['rows'] as $index => $row): ?>
                <?php
                $lessonType = (string) ($row['lesson_type'] ?? 'lecture');
                $isSemesterMarker = ktp_is_semester_marker_type($lessonType);
                ?>
                <tr<?= $isSemesterMarker ? ' class="ktp-row--semester-marker"' : '' ?>>
                    <td class="ktp-col-num"><?= $isSemesterMarker ? '' : (int) ($index + 1) ?></td>
                    <td class="ktp-rows-title-cell"><?= e((string) ($row['title'] ?? '')) ?></td>
                    <td><?= e(ktp_lesson_type_label($lessonType)) ?></td>
                    <td><?= e(format_ktp_topic_hours($row, $isProfessionality)) ?></td>
                    <td class="ktp-competency-cell"><?php render_ktp_competency_cell($row['ok_codes'] ?? null, $row['pk_codes'] ?? null); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form method="post" class="form" onsubmit="return confirm('Импорт заменит текущие строки КТП. Продолжить?');">
        <?= csrf_field() ?>
        <div class="form__actions">
            <button type="submit" class="btn btn--primary" name="action" value="import_word_ktp">Импортировать в КТП</button>
        </div>
    </form>
    <form method="post" class="form form--inline">
        <?= csrf_field() ?>
        <div class="form__actions">
            <button type="submit" class="btn btn--ghost" name="action" value="cancel_word_ktp">Отменить предпросмотр</button>
        </div>
    </form>
<?php endif; ?>
