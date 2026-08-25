<?php

declare(strict_types=1);

/** @var array $practices */
/** @var bool $canEditPractices */
/** @var array|null $editPractice */
/** @var string|null $practiceError */
/** @var string|null $practiceSuccess */

$canEditPractices = !empty($canEditPractices);
$editPractice = $editPractice ?? null;
$practiceError = $practiceError ?? null;
$practiceSuccess = $practiceSuccess ?? null;
$form = $editPractice ?? [
    'id' => 0,
    'module_name' => '',
    'org_supervisor_name' => '',
    'college_supervisor_name' => '',
    'grade' => '',
];
$isEdit = (int) ($form['id'] ?? 0) > 0;
?>
<div class="record-book__sheet-head">
    <div>
        <h2>Практики</h2>
        <p class="text-muted">Учебная и производственная практика</p>
    </div>
    <div class="record-book__stats">
        <div class="record-book__stat">
            <span class="record-book__stat-value"><?= count($practices) ?></span>
            <span class="record-book__stat-label">записей</span>
        </div>
    </div>
</div>

<?php if ($practiceSuccess): ?>
    <div class="alert alert--success"><?= e($practiceSuccess) ?></div>
<?php endif; ?>
<?php if ($practiceError): ?>
    <div class="alert alert--error"><?= e($practiceError) ?></div>
<?php endif; ?>

<?php if ($canEditPractices): ?>
<form method="post" class="form record-book-extra-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $isEdit ? 'update_practice' : 'add_practice' ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="practice_id" value="<?= (int) $form['id'] ?>">
    <?php endif; ?>
    <h3 class="subsection-title"><?= $isEdit ? 'Редактирование записи' : 'Добавить практику' ?></h3>
    <div class="form__group">
        <label for="pr_module_name">Наименование профессионального модуля (ПМ)</label>
        <input type="text" id="pr_module_name" name="module_name" required maxlength="255"
               value="<?= e((string) ($form['module_name'] ?? '')) ?>">
    </div>
    <div class="form__row">
        <div class="form__group">
            <label for="pr_org_supervisor">Руководитель практики от организации</label>
            <input type="text" id="pr_org_supervisor" name="org_supervisor_name" maxlength="255"
                   value="<?= e((string) ($form['org_supervisor_name'] ?? '')) ?>">
        </div>
        <div class="form__group">
            <label for="pr_college_supervisor">Руководитель практики</label>
            <input type="text" id="pr_college_supervisor" name="college_supervisor_name" maxlength="255"
                   value="<?= e((string) ($form['college_supervisor_name'] ?? '')) ?>">
        </div>
    </div>
    <div class="form__group">
        <label for="pr_grade">Оценка</label>
        <select id="pr_grade" name="grade">
            <option value="">—</option>
            <?php foreach ([5, 4, 3, 2] as $g): ?>
            <option value="<?= $g ?>"<?= (string) ($form['grade'] ?? '') === (string) $g ? ' selected' : '' ?>>
                <?= $g ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form__actions">
        <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Сохранить' : 'Добавить' ?></button>
        <?php if ($isEdit): ?>
        <a href="?<?= e(http_build_query(array_filter([
            'id' => $_GET['id'] ?? null,
            'group_id' => $_GET['group_id'] ?? null,
            'period' => 'practices',
        ], static fn ($v) => $v !== null && $v !== ''))) ?>" class="btn btn--ghost">Отмена</a>
        <?php endif; ?>
    </div>
</form>
<?php endif; ?>

<?php if ($practices === []): ?>
    <p class="text-muted">Практики пока не добавлены.</p>
<?php else: ?>
<div class="table-wrap">
    <table class="table table--record-book-extra">
        <thead>
            <tr>
                <th class="table__num-col">№ п/п</th>
                <th>Наименование профессионального модуля (ПМ)</th>
                <th>Руководитель практики от организации</th>
                <th>Руководитель практики</th>
                <th>Оценка</th>
                <?php if ($canEditPractices): ?>
                <th class="table__actions-col"></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($practices as $index => $row): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= e((string) $row['module_name']) ?></td>
                <td><?= e(trim((string) ($row['org_supervisor_name'] ?? '')) !== '' ? (string) $row['org_supervisor_name'] : '—') ?></td>
                <td><?= e(trim((string) ($row['college_supervisor_name'] ?? '')) !== '' ? (string) $row['college_supervisor_name'] : '—') ?></td>
                <td>
                    <?php if ($row['grade'] !== null && $row['grade'] !== ''): ?>
                        <span class="record-book__grade record-book__grade--inline record-book__grade--<?= (int) $row['grade'] ?>">
                            <?= (int) $row['grade'] ?>
                        </span>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <?php if ($canEditPractices): ?>
                <td class="table__actions">
                    <a class="btn btn--ghost btn--sm"
                       href="?<?= e(http_build_query(array_filter([
                           'id' => $_GET['id'] ?? null,
                           'group_id' => $_GET['group_id'] ?? null,
                           'period' => 'practices',
                           'edit' => (int) $row['id'],
                       ], static fn ($v) => $v !== null && $v !== ''))) ?>">Изменить</a>
                    <form method="post" class="inline-form" onsubmit="return confirm('Удалить запись о практике?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete_practice">
                        <input type="hidden" name="practice_id" value="<?= (int) $row['id'] ?>">
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
