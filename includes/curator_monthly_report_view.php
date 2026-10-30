<?php

declare(strict_types=1);

/**
 * Только чтение отчёта куратора.
 * Ожидает: $payload, $studentCount, $studentNameMap, $scored, $itemMeta
 */

$payload = is_array($payload ?? null) ? $payload : curator_monthly_report_default_payload();
$studentCount = (int) ($studentCount ?? 0);
$studentNameMap = is_array($studentNameMap ?? null) ? $studentNameMap : [];
$scored = is_array($scored ?? null)
    ? $scored
    : curator_monthly_report_score_payload($payload, $studentCount);
$itemMeta = is_array($itemMeta ?? null) ? $itemMeta : curator_monthly_report_item_meta();
?>
<div class="curator-monthly-report-readonly">
    <?php foreach ($itemMeta as $item): ?>
        <?php
        $key = $item['key'];
        $points = (int) ($scored['item_points'][$key] ?? 0);
        $percent = (float) ($scored['percents'][$key] ?? 0);
        ?>
        <div class="curator-monthly-report-item">
            <div class="curator-monthly-report-item__head">
                <h3><?= e($item['label']) ?></h3>
                <div class="curator-monthly-report-item__score">
                    <?= e((string) $percent) ?>% ·
                    <strong><?= $points ?> б.</strong>
                </div>
            </div>

            <?php if ($item['type'] === 'count'): ?>
                <p class="curator-monthly-report-item__auto">
                    <strong><?= (int) ($payload['key_events_count'] ?? 0) ?></strong>
                    <span class="text-muted"> из <?= (int) $studentCount ?></span>
                </p>
            <?php elseif ($item['type'] === 'events'): ?>
                <?php $events = is_array($payload['group_events'] ?? null) ? $payload['group_events'] : []; ?>
                <?php if ($events === []): ?>
                    <p class="text-muted">Мероприятия не указаны.</p>
                <?php else: ?>
                    <ul class="curator-monthly-report-readonly__list">
                        <?php foreach ($events as $title): ?>
                            <li><?= e((string) $title) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="text-muted">Всего: <?= count($events) ?></p>
                <?php endif; ?>
            <?php elseif ($item['type'] === 'auto'): ?>
                <?php
                $autoValue = match ($key) {
                    'clubs' => (int) ($payload['clubs_count'] ?? 0),
                    'debts' => (int) ($payload['debts_count'] ?? 0),
                    'unexcused' => (int) ($payload['unexcused_count'] ?? 0),
                    default => 0,
                };
                ?>
                <p class="curator-monthly-report-item__auto">
                    <strong><?= $autoValue ?></strong>
                    <span class="text-muted"> из <?= (int) $studentCount ?></span>
                </p>
            <?php elseif ($item['type'] === 'students'): ?>
                <?php
                $ids = is_array($payload[$key] ?? null) ? $payload[$key] : [];
                $names = curator_monthly_report_student_names(
                    array_map('intval', $ids),
                    $studentNameMap
                );
                ?>
                <?php if ($names === []): ?>
                    <p class="text-muted">Студенты не указаны.</p>
                <?php else: ?>
                    <ul class="curator-monthly-report-readonly__list">
                        <?php foreach ($names as $name): ?>
                            <li><?= e($name) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="text-muted">Всего: <?= count($names) ?></p>
                <?php endif; ?>
            <?php elseif ($item['type'] === 'characteristics'): ?>
                <p class="curator-monthly-report-item__auto">
                    <strong>
                        <?= (($payload['characteristics'] ?? '') === 'submitted')
                            ? 'Характеристики сданы'
                            : 'Характеристики не сданы' ?>
                    </strong>
                </p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="curator-monthly-report-total">
        <div>
            <div class="curator-monthly-report-total__label">Итого баллов</div>
            <div class="curator-monthly-report-total__value"><?= (int) ($scored['total_points'] ?? 0) ?></div>
        </div>
    </div>
</div>
