<?php

declare(strict_types=1);

/** @var array $giaEntries */
/** @var bool $canEditGia */
/** @var array|null $editGia */
/** @var string|null $giaError */
/** @var string|null $giaSuccess */

$canEditGia = !empty($canEditGia);
$editGia = $editGia ?? null;
$giaError = $giaError ?? null;
$giaSuccess = $giaSuccess ?? null;
$giaEntries = $giaEntries ?? [];
$sections = split_gia_entries($giaEntries);

$form = $editGia ?? [
    'id' => 0,
    'form_type' => 'demo_exam',
    'module_name' => '',
    'code' => '',
    'points' => '',
    'topic' => '',
    'defense_date' => '',
    'grade' => '',
];
$isEdit = (int) ($form['id'] ?? 0) > 0;
$currentType = normalize_gia_form_type((string) ($form['form_type'] ?? 'demo_exam')) ?? 'demo_exam';

$queryBase = array_filter([
    'id' => $_GET['id'] ?? null,
    'group_id' => $_GET['group_id'] ?? null,
    'period' => 'gia',
], static fn ($v) => $v !== null && $v !== '');
?>
<div class="record-book__sheet-head">
    <div>
        <h2>Государственная итоговая аттестация</h2>
        <p class="text-muted">Демонстрационный экзамен и выпускная квалификационная работа</p>
    </div>
    <div class="record-book__stats">
        <div class="record-book__stat">
            <span class="record-book__stat-value"><?= count($giaEntries) ?></span>
            <span class="record-book__stat-label">записей</span>
        </div>
    </div>
</div>

<?php if ($giaSuccess): ?>
    <div class="alert alert--success"><?= e($giaSuccess) ?></div>
<?php endif; ?>
<?php if ($giaError): ?>
    <div class="alert alert--error"><?= e($giaError) ?></div>
<?php endif; ?>

<?php if ($canEditGia): ?>
<form method="post" class="form record-book-extra-form" data-gia-form>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $isEdit ? 'update_gia' : 'add_gia' ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="gia_id" value="<?= (int) $form['id'] ?>">
    <?php endif; ?>
    <h3 class="subsection-title"><?= $isEdit ? 'Редактирование записи' : 'Добавить запись ГИА' ?></h3>
    <div class="form__group">
        <label for="gia_form_type">Форма итоговой аттестации</label>
        <select id="gia_form_type" name="form_type" required data-gia-type>
            <?php foreach (gia_form_types() as $value => $label): ?>
            <option value="<?= e($value) ?>"<?= $currentType === $value ? ' selected' : '' ?>>
                <?= e($label) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="gia-fields" data-gia-fields="demo_exam"<?= $currentType === 'demo_exam' ? '' : ' hidden' ?>>
        <div class="form__group">
            <label for="gia_module_name">Наименование ПМ</label>
            <input type="text" id="gia_module_name" name="module_name" maxlength="255"
                   value="<?= e((string) ($form['module_name'] ?? '')) ?>">
        </div>
        <div class="form__row">
            <div class="form__group">
                <label for="gia_code">КОД</label>
                <input type="text" id="gia_code" name="code" maxlength="100"
                       value="<?= e((string) ($form['code'] ?? '')) ?>">
            </div>
            <div class="form__group">
                <label for="gia_points">Количество баллов</label>
                <input type="number" id="gia_points" name="points" min="0" max="99999" step="0.01"
                       value="<?= e($form['points'] !== null && $form['points'] !== '' ? (string) $form['points'] : '') ?>">
            </div>
        </div>
    </div>

    <div class="gia-fields" data-gia-fields="vkr"<?= $currentType === 'vkr' ? '' : ' hidden' ?>>
        <div class="form__group">
            <label for="gia_topic">Тема</label>
            <input type="text" id="gia_topic" name="topic" maxlength="500"
                   value="<?= e((string) ($form['topic'] ?? '')) ?>">
        </div>
        <div class="form__group">
            <label for="gia_defense_date">Дата защиты</label>
            <input type="date" id="gia_defense_date" name="defense_date"
                   value="<?= e((string) ($form['defense_date'] ?? '')) ?>">
        </div>
    </div>

    <div class="form__group">
        <label for="gia_grade">Оценка</label>
        <select id="gia_grade" name="grade">
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
        <a href="?<?= e(http_build_query($queryBase)) ?>" class="btn btn--ghost">Отмена</a>
        <?php endif; ?>
    </div>
</form>
<script>
(function () {
    var form = document.querySelector('[data-gia-form]');
    if (!form) return;
    var typeSelect = form.querySelector('[data-gia-type]');
    var blocks = form.querySelectorAll('[data-gia-fields]');
    function sync() {
        var type = typeSelect.value;
        blocks.forEach(function (block) {
            block.hidden = block.getAttribute('data-gia-fields') !== type;
        });
    }
    typeSelect.addEventListener('change', sync);
    sync();
})();
</script>
<?php endif; ?>

<?php
$demoRows = $sections['demo_exam'];
$vkrRows = $sections['vkr'];
?>

<div class="record-book__section">
    <div class="record-book__section-head">
        <h3 class="record-book__section-title">Демонстрационный экзамен</h3>
    </div>
    <?php if ($demoRows === []): ?>
        <p class="text-muted">Записей нет.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table table--record-book-extra">
            <thead>
                <tr>
                    <th class="table__num-col">№ п/п</th>
                    <th>Наименование ПМ</th>
                    <th>КОД</th>
                    <th>Количество баллов</th>
                    <th>Оценка</th>
                    <?php if ($canEditGia): ?>
                    <th class="table__actions-col"></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($demoRows as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= e((string) $row['module_name']) ?></td>
                    <td><?= e(trim((string) ($row['code'] ?? '')) !== '' ? (string) $row['code'] : '—') ?></td>
                    <td><?= $row['points'] !== null && $row['points'] !== '' ? e(rtrim(rtrim(number_format((float) $row['points'], 2, '.', ''), '0'), '.')) : '—' ?></td>
                    <td>
                        <?php if ($row['grade'] !== null && $row['grade'] !== ''): ?>
                            <span class="record-book__grade record-book__grade--inline record-book__grade--<?= (int) $row['grade'] ?>">
                                <?= (int) $row['grade'] ?>
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <?php if ($canEditGia): ?>
                    <td class="table__actions">
                        <a class="btn btn--ghost btn--sm"
                           href="?<?= e(http_build_query($queryBase + ['edit' => (int) $row['id']])) ?>">Изменить</a>
                        <form method="post" class="inline-form" onsubmit="return confirm('Удалить запись ГИА?');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_gia">
                            <input type="hidden" name="gia_id" value="<?= (int) $row['id'] ?>">
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
</div>

<div class="record-book__section" style="margin-top: 1.5rem;">
    <div class="record-book__section-head">
        <h3 class="record-book__section-title">Выпускная квалификационная работа</h3>
    </div>
    <?php if ($vkrRows === []): ?>
        <p class="text-muted">Записей нет.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table table--record-book-extra">
            <thead>
                <tr>
                    <th class="table__num-col">№ п/п</th>
                    <th>Тема</th>
                    <th>Дата защиты</th>
                    <th>Оценка</th>
                    <?php if ($canEditGia): ?>
                    <th class="table__actions-col"></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vkrRows as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= e((string) $row['topic']) ?></td>
                    <td><?= e(format_gia_defense_date($row['defense_date'] ?? null)) ?></td>
                    <td>
                        <?php if ($row['grade'] !== null && $row['grade'] !== ''): ?>
                            <span class="record-book__grade record-book__grade--inline record-book__grade--<?= (int) $row['grade'] ?>">
                                <?= (int) $row['grade'] ?>
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <?php if ($canEditGia): ?>
                    <td class="table__actions">
                        <a class="btn btn--ghost btn--sm"
                           href="?<?= e(http_build_query($queryBase + ['edit' => (int) $row['id']])) ?>">Изменить</a>
                        <form method="post" class="inline-form" onsubmit="return confirm('Удалить запись ГИА?');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_gia">
                            <input type="hidden" name="gia_id" value="<?= (int) $row['id'] ?>">
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
</div>
