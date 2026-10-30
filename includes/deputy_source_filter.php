<?php

declare(strict_types=1);

/**
 * Переключатель источника оценок (журнал / ручное БРС).
 * Ожидает: $sourceFilterUrl (callable|string), $sourceFilterCurrent (journal|manual_brs)
 * Опционально: $sourceFilterQuery (array) если $sourceFilterUrl — строка страницы.
 */

$sourceFilterCurrent = ($sourceFilterCurrent ?? 'journal') === 'manual_brs' ? 'manual_brs' : 'journal';
if (!is_manual_brs_gradebook_active()) {
    return;
}

$buildSourceHref = static function (string $source) use ($sourceFilterUrl, $sourceFilterQuery): string {
    if (is_callable($sourceFilterUrl)) {
        return (string) $sourceFilterUrl(['source' => $source]);
    }
    $page = (string) $sourceFilterUrl;
    $params = array_merge($sourceFilterQuery ?? [], ['source' => $source]);
    $qs = http_build_query($params);

    return $page . ($qs !== '' ? '?' . $qs : '');
};
?>
<div class="summary-report-filters__group">
    <span class="summary-report-filters__label">Источник оценок</span>
    <div class="summary-report-filters__tabs" role="tablist">
        <a href="<?= e($buildSourceHref('journal')) ?>"
           class="summary-report-filters__tab<?= $sourceFilterCurrent === 'journal' ? ' is-active' : '' ?>">
            Электронный журнал
        </a>
        <a href="<?= e($buildSourceHref('manual_brs')) ?>"
           class="summary-report-filters__tab<?= $sourceFilterCurrent === 'manual_brs' ? ' is-active' : '' ?>">
            Ручное БРС
        </a>
    </div>
</div>
