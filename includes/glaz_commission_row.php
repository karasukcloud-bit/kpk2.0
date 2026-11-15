<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $teachers */
/** @var int $selectedId */
$rowTeacherList = $teachersForRow ?? $teachers;
?>
<div class="glaz-commission__row">
    <select name="commission[]">
        <option value="">— Выберите —</option>
        <?php foreach ($rowTeacherList as $teacher): ?>
        <option
            value="<?= (int) $teacher['id'] ?>"
            <?= (int) $teacher['id'] === $selectedId ? 'selected' : '' ?>
        ><?= e((string) $teacher['full_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button
        type="button"
        class="glaz-commission__remove"
        data-glaz-commission-remove
        title="Убрать из комиссии"
    >Убрать</button>
</div>
