<?php

declare(strict_types=1);

/** @var int $selectedId */
/** @var string $teacherName */
$selectedId = (int) ($selectedId ?? 0);
$teacherName = (string) ($teacherName ?? '');
if ($selectedId <= 0 || $teacherName === '') {
    return;
}
?>
<div class="glaz-commission__chip" data-teacher-id="<?= $selectedId ?>">
    <input type="hidden" name="commission[]" value="<?= $selectedId ?>">
    <span class="glaz-commission__chip-name"><?= e($teacherName) ?></span>
    <button
        type="button"
        class="glaz-commission__chip-remove"
        data-glaz-commission-remove
        title="Убрать из комиссии"
        aria-label="Убрать <?= e($teacherName) ?> из комиссии"
    >×</button>
</div>
