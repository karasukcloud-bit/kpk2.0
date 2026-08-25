<?php

declare(strict_types=1);

/** @var array $courseworks */
/** @var bool $canEditCourseworks */
/** @var array|null $editCoursework */
/** @var string|null $courseworkError */
/** @var string|null $courseworkSuccess */

$canEditCourseworks = !empty($canEditCourseworks);
$editCoursework = $editCoursework ?? null;
$courseworkError = $courseworkError ?? null;
$courseworkSuccess = $courseworkSuccess ?? null;
$form = $editCoursework ?? [
    'id' => 0,
    'subject_name' => '',
    'topic' => '',
    'defense_date' => '',
    'teacher_name' => '',
    'grade' => '',
];
$isEdit = (int) ($form['id'] ?? 0) > 0;
?>
<div class="record-book__sheet-head">
    <div>
        <h2>Курсовые работы</h2>
        <p class="text-muted">Курсовые проекты и курсовые работы</p>
    </div>
    <div class="record-book__stats">
        <div class="record-book__stat">
            <span class="record-book__stat-value"><?= count($courseworks) ?></span>
            <span class="record-book__stat-label">записей</span>
        </div>
    </div>
</div>

<?php if ($courseworkSuccess): ?>
    <div class="alert alert--success"><?= e($courseworkSuccess) ?></div>
<?php endif; ?>
<?php if ($courseworkError): ?>
    <div class="alert alert--error"><?= e($courseworkError) ?></div>
<?php endif; ?>

<?php if ($canEditCourseworks): ?>
<form method="post" class="form coursework-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $isEdit ? 'update_coursework' : 'add_coursework' ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="coursework_id" value="<?= (int) $form['id'] ?>">
    <?php endif; ?>
    <h3 class="subsection-title"><?= $isEdit ? 'Редактирование записи' : 'Добавить курсовую работу' ?></h3>
    <div class="form__row">
        <div class="form__group">
            <label for="cw_subject_name">Наименование дисциплины (модуля), МДК</label>
            <input type="text" id="cw_subject_name" name="subject_name" required maxlength="255"
                   value="<?= e((string) ($form['subject_name'] ?? '')) ?>">
        </div>
        <div class="form__group">
            <label for="cw_teacher_name">Фамилия преподавателя</label>
            <input type="text" id="cw_teacher_name" name="teacher_name" maxlength="255"
                   value="<?= e((string) ($form['teacher_name'] ?? '')) ?>">
        </div>
    </div>
    <div class="form__group">
        <label for="cw_topic">Тема курсового проекта (курсовой работы)</label>
        <input type="text" id="cw_topic" name="topic" required maxlength="500"
               value="<?= e((string) ($form['topic'] ?? '')) ?>">
    </div>
    <div class="form__row">
        <div class="form__group">
            <label for="cw_defense_date">Дата защиты</label>
            <input type="date" id="cw_defense_date" name="defense_date"
                   value="<?= e((string) ($form['defense_date'] ?? '')) ?>">
        </div>
        <div class="form__group">
            <label for="cw_grade">Оценка</label>
            <select id="cw_grade" name="grade">
                <option value="">—</option>
                <?php foreach ([5, 4, 3, 2] as $g): ?>
                <option value="<?= $g ?>"<?= (string) ($form['grade'] ?? '') === (string) $g ? ' selected' : '' ?>>
                    <?= $g ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form__actions">
        <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Сохранить' : 'Добавить' ?></button>
        <?php if ($isEdit): ?>
        <a href="?<?= e(http_build_query(array_filter([
            'id' => $_GET['id'] ?? null,
            'group_id' => $_GET['group_id'] ?? null,
            'period' => 'courseworks',
        ], static fn ($v) => $v !== null && $v !== ''))) ?>" class="btn btn--ghost">Отмена</a>
        <?php endif; ?>
    </div>
</form>
<?php endif; ?>

<?php if ($courseworks === []): ?>
    <p class="text-muted">Курсовые работы пока не добавлены.</p>
<?php else: ?>
<div class="table-wrap">
    <table class="table table--courseworks">
        <thead>
            <tr>
                <th class="table__num-col">№ п/п</th>
                <th>Наименование учебных дисциплин (модулей), МДК</th>
                <th>Тема курсового проекта (курсовой работы)</th>
                <th>Дата защиты</th>
                <th>Фамилия преподавателя</th>
                <th>Оценка</th>
                <?php if ($canEditCourseworks): ?>
                <th class="table__actions-col"></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($courseworks as $index => $row): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= e((string) $row['subject_name']) ?></td>
                <td><?= e((string) $row['topic']) ?></td>
                <td><?= e(format_coursework_defense_date($row['defense_date'] ?? null)) ?></td>
                <td><?= e(trim((string) ($row['teacher_name'] ?? '')) !== '' ? (string) $row['teacher_name'] : '—') ?></td>
                <td>
                    <?php if ($row['grade'] !== null && $row['grade'] !== ''): ?>
                        <span class="record-book__grade record-book__grade--inline record-book__grade--<?= (int) $row['grade'] ?>">
                            <?= (int) $row['grade'] ?>
                        </span>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <?php if ($canEditCourseworks): ?>
                <td class="table__actions">
                    <a class="btn btn--ghost btn--sm"
                       href="?<?= e(http_build_query(array_filter([
                           'id' => $_GET['id'] ?? null,
                           'group_id' => $_GET['group_id'] ?? null,
                           'period' => 'courseworks',
                           'edit' => (int) $row['id'],
                       ], static fn ($v) => $v !== null && $v !== ''))) ?>">Изменить</a>
                    <form method="post" class="inline-form" onsubmit="return confirm('Удалить запись курсовой работы?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete_coursework">
                        <input type="hidden" name="coursework_id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="btn btn--ghost btn--sm">Удалить</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
