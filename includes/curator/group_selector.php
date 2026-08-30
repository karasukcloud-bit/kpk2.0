<?php

declare(strict_types=1);

/** @var array $curatorGroups */
/** @var int $curatorGroupId */
/** @var array<string, scalar|null> $curatorGroupPreserveParams */

$curatorGroups = $curatorGroups ?? [];
$curatorGroupId = (int) ($curatorGroupId ?? 0);
$curatorGroupPreserveParams = $curatorGroupPreserveParams ?? [];

if (count($curatorGroups) <= 1) {
    return;
}
?>
<form method="get" class="form form--filter curator-group-filter">
    <div class="form__row form__row--filter">
        <div class="form__group">
            <label for="curator_group_id">Группа</label>
            <select id="curator_group_id" name="group_id" onchange="this.form.submit()">
                <option value="">— Выберите группу —</option>
                <?php foreach ($curatorGroups as $item): ?>
                <option value="<?= (int) $item['id'] ?>"<?= (int) $item['id'] === $curatorGroupId ? ' selected' : '' ?>>
                    <?= e($item['number']) ?> · <?= e($item['specialty_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php foreach ($curatorGroupPreserveParams as $paramName => $paramValue): ?>
        <?php if ($paramName === 'group_id' || $paramValue === null || $paramValue === '') {
            continue;
        } ?>
    <input type="hidden" name="<?= e((string) $paramName) ?>" value="<?= e((string) $paramValue) ?>">
    <?php endforeach; ?>
</form>
