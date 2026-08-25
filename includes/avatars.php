<?php

declare(strict_types=1);

/**
 * Пресеты аватаров: люди и животные.
 * @return array<string, array{label: string, color: string}>
 */
function avatar_preset_meta(): array
{
    return [
        'person' => ['label' => 'Человек', 'color' => '#64748b'],
        'woman' => ['label' => 'Женщина', 'color' => '#db2777'],
        'man' => ['label' => 'Мужчина', 'color' => '#2563eb'],
        'student' => ['label' => 'Студент', 'color' => '#7c3aed'],
        'teacher' => ['label' => 'Преподаватель', 'color' => '#0f766e'],
        'cat' => ['label' => 'Кот', 'color' => '#ea580c'],
        'dog' => ['label' => 'Собака', 'color' => '#ca8a04'],
        'fox' => ['label' => 'Лиса', 'color' => '#c2410c'],
        'owl' => ['label' => 'Сова', 'color' => '#4f46e5'],
        'rabbit' => ['label' => 'Кролик', 'color' => '#e11d48'],
        'bear' => ['label' => 'Медведь', 'color' => '#92400e'],
        'panda' => ['label' => 'Панда', 'color' => '#334155'],
    ];
}

function avatar_presets(): array
{
    $presets = [];
    foreach (avatar_preset_meta() as $key => $meta) {
        $presets[$key] = $meta['label'];
    }

    return $presets;
}

/** Старые цветовые ключи → новые иконки. */
function avatar_legacy_icon_map(): array
{
    return [
        'default' => 'person',
        'blue' => 'man',
        'green' => 'cat',
        'amber' => 'dog',
        'rose' => 'rabbit',
        'teal' => 'teacher',
        'indigo' => 'owl',
        'slate' => 'panda',
    ];
}

function resolve_avatar_icon_key(string $key): string
{
    $presets = avatar_presets();
    if (isset($presets[$key])) {
        return $key;
    }

    $legacy = avatar_legacy_icon_map();
    if (isset($legacy[$key])) {
        return $legacy[$key];
    }

    return 'person';
}

function avatar_icon_svg(string $key): string
{
    $key = resolve_avatar_icon_key($key);

    // Простые силуэты: люди и животные (viewBox 0 0 64 64)
    $icons = [
        'person' => <<<'SVG'
<circle cx="32" cy="20" r="12"/>
<path d="M10 58c3-14 12-20 22-20s19 6 22 20"/>
SVG,
        'woman' => <<<'SVG'
<path d="M22 22c0-8 4.5-14 10-14s10 6 10 14c0 2-.5 4-1.5 5.5L42 58H22l1.5-16.5C22.5 26 22 24 22 22z"/>
<circle cx="32" cy="18" r="10"/>
<path d="M20 10c2-6 6-9 12-9s10 3 12 9" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
SVG,
        'man' => <<<'SVG'
<rect x="20" y="2" width="24" height="7" rx="2"/>
<circle cx="32" cy="20" r="11"/>
<path d="M10 58c3-13 11-19 22-19s19 6 22 19"/>
SVG,
        'student' => <<<'SVG'
<path d="M6 28 L32 14 l26 14-26 13z"/>
<path d="M14 32v14c0 5 8 10 18 10s18-5 18-10V32"/>
<path d="M54 30v16" stroke="currentColor" stroke-width="3" stroke-linecap="round" fill="none"/>
<circle cx="32" cy="20" r="2.5"/>
SVG,
        'teacher' => <<<'SVG'
<circle cx="32" cy="17" r="10"/>
<path d="M14 14h36" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" fill="none"/>
<path d="M10 58c3-13 11-19 22-19s19 6 22 19"/>
<path d="M22 30h20v5H22z"/>
SVG,
        'cat' => <<<'SVG'
<path d="M16 12l6 12h20l6-12-8 6-8-4-8 4z"/>
<path d="M18 28c0-2 6-6 14-6s14 4 14 6v14c0 10-6 16-14 16s-14-6-14-16z"/>
<circle cx="26" cy="36" r="2.8"/>
<circle cx="38" cy="36" r="2.8"/>
<path d="M32 40v5M28 44h8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" fill="none"/>
SVG,
        'dog' => <<<'SVG'
<path d="M40 28c8 0 14 6 14 14 0 10-8 18-18 18H26c-8 0-14-7-14-16 0-8 6-14 14-14h4z"/>
<path d="M12 22c0-7 5-12 11-12 5 0 9 3 10 8l1 6H20c-4 0-8-1-8-2z"/>
<path d="M8 18c-2 4-1 10 3 12"/>
<circle cx="18" cy="20" r="2.2"/>
<path d="M48 48c6 3 10 8 8 12-5 1-11-2-14-7"/>
SVG,
        'fox' => <<<'SVG'
<path d="M12 46c0-14 8-26 20-26s20 12 20 26c0 9-8 14-20 14S12 55 12 46z"/>
<path d="M14 16l11 14M50 16L39 30"/>
<circle cx="26" cy="42" r="2.6"/>
<circle cx="38" cy="42" r="2.6"/>
<path d="M32 46l-3.5 6h7z"/>
SVG,
        'owl' => <<<'SVG'
<path d="M18 12l7 10M46 12l-7 10"/>
<ellipse cx="32" cy="36" rx="18" ry="20"/>
<circle cx="24" cy="32" r="7"/>
<circle cx="40" cy="32" r="7"/>
<circle cx="24" cy="32" r="2.8"/>
<circle cx="40" cy="32" r="2.8"/>
<path d="M32 38l-3 6h6z"/>
SVG,
        'rabbit' => <<<'SVG'
<ellipse cx="22" cy="16" rx="5" ry="15"/>
<ellipse cx="42" cy="16" rx="5" ry="15"/>
<ellipse cx="32" cy="40" rx="15" ry="16"/>
<circle cx="26" cy="38" r="2.5"/>
<circle cx="38" cy="38" r="2.5"/>
<ellipse cx="32" cy="45" rx="3.5" ry="2.5"/>
SVG,
        'bear' => <<<'SVG'
<circle cx="16" cy="18" r="9"/>
<circle cx="48" cy="18" r="9"/>
<circle cx="32" cy="34" r="20"/>
<circle cx="24" cy="32" r="3"/>
<circle cx="40" cy="32" r="3"/>
<ellipse cx="32" cy="42" rx="6" ry="5"/>
SVG,
        'panda' => <<<'SVG'
<ellipse cx="16" cy="18" rx="8" ry="9"/>
<ellipse cx="48" cy="18" rx="8" ry="9"/>
<circle cx="32" cy="34" r="20"/>
<ellipse cx="23" cy="32" rx="6" ry="7"/>
<ellipse cx="41" cy="32" rx="6" ry="7"/>
<circle cx="23" cy="32" r="2.2"/>
<circle cx="41" cy="32" r="2.2"/>
<ellipse cx="32" cy="44" rx="5" ry="3.5"/>
SVG,
    ];

    $body = $icons[$key] ?? $icons['person'];

    return '<svg viewBox="0 0 64 64" width="22" height="22" fill="currentColor" focusable="false" aria-hidden="true">'
        . $body
        . '</svg>';
}
