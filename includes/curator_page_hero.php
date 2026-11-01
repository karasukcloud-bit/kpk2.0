<?php

declare(strict_types=1);

/** @var string $curatorPageTitle */
/** @var string $curatorPageSubtitle */
/** @var string $curatorPageIcon */
/** @var bool $curatorPageShowGroup */
/** @var array|null $curatorGroups */
/** @var int|null $curatorGroupId */
/** @var array<string, scalar|null> $curatorGroupPreserveParams */
/** @var array|null $group */
/** @var string|null $curatorPageActionsHtml */
/** @var string|null $curatorPageExtraControlsHtml */

$curatorPageTitle = (string) ($curatorPageTitle ?? '');
$curatorPageSubtitle = (string) ($curatorPageSubtitle ?? '');
$curatorPageIcon = (string) ($curatorPageIcon ?? 'group');
$curatorPageShowGroup = $curatorPageShowGroup ?? true;
$curatorGroups = $curatorGroups ?? [];
$curatorGroupId = (int) ($curatorGroupId ?? 0);
$curatorGroupPreserveParams = $curatorGroupPreserveParams ?? [];
$curatorPageActionsHtml = (string) ($curatorPageActionsHtml ?? '');
$curatorPageExtraControlsHtml = (string) ($curatorPageExtraControlsHtml ?? '');

$iconPaths = [
    'group' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'attendance' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>',
    'grades' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/>',
    'report' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 18V14M12 18v-6M16 18v-3"/>',
    'archive' => '<path d="M21 8v13H3V8"/><path d="M23 3H1v5h22V3z"/><path d="M10 12h4"/>',
    'activities' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><path d="M19 8v4M17 10h4"/>',
    'characteristics' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>',
    'parent_reports' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><path d="M12 11V7M10 9h4"/>',
    'monthly_report' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/>',
    'student' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
];
$iconBody = $iconPaths[$curatorPageIcon] ?? $iconPaths['group'];

$currentGroup = null;
if (isset($group) && is_array($group)) {
    $currentGroup = $group;
} elseif ($curatorGroupId > 0) {
    foreach ($curatorGroups as $item) {
        if ((int) ($item['id'] ?? 0) === $curatorGroupId) {
            $currentGroup = $item;
            break;
        }
    }
}
?>
<section class="curator-page-hero curator-no-print no-print">
    <div class="curator-page-hero__top">
        <div class="curator-page-hero__title-block">
            <span class="curator-page-hero__title-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <?= $iconBody ?>
                </svg>
            </span>
            <div>
                <h1 class="curator-page-hero__title"><?= e($curatorPageTitle) ?></h1>
                <?php if ($curatorPageSubtitle !== ''): ?>
                <p class="curator-page-hero__subtitle"><?= e($curatorPageSubtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="curator-page-hero__controls">
            <?php if ($curatorPageShowGroup && $curatorGroups !== []): ?>
                <?php if (count($curatorGroups) > 1): ?>
                <form method="get" class="curator-group-pill-form">
                    <label class="curator-group-pill curator-group-pill--select" title="Группа">
                        <span class="curator-group-pill__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <path d="M9 22V12h6v10"/>
                            </svg>
                        </span>
                        <select name="group_id" class="curator-group-pill__select" onchange="this.form.submit()" aria-label="Группа">
                            <?php foreach ($curatorGroups as $item): ?>
                            <option value="<?= (int) $item['id'] ?>"<?= (int) $item['id'] === $curatorGroupId ? ' selected' : '' ?>>
                                <?= e((string) $item['number']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <?php foreach ($curatorGroupPreserveParams as $paramName => $paramValue): ?>
                        <?php if ($paramName === 'group_id' || $paramValue === null || $paramValue === '') {
                            continue;
                        } ?>
                    <input type="hidden" name="<?= e((string) $paramName) ?>" value="<?= e((string) $paramValue) ?>">
                    <?php endforeach; ?>
                </form>
                <?php elseif ($currentGroup !== null): ?>
                <div class="curator-group-pill" title="Группа">
                    <span class="curator-group-pill__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <path d="M9 22V12h6v10"/>
                        </svg>
                    </span>
                    <span class="curator-group-pill__text"><?= e((string) ($currentGroup['number'] ?? '')) ?></span>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <?= $curatorPageExtraControlsHtml ?>
            <?= $curatorPageActionsHtml ?>
        </div>
    </div>
</section>
<?php
$curatorPageActionsHtml = '';
$curatorPageExtraControlsHtml = '';
$curatorPageShowGroup = true;
?>
