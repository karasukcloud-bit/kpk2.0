<?php

declare(strict_types=1);

/**
 * Блок просмотра/редактирования отчёта.
 * Ожидает: $mode, $month, $hasSaved, $saved, $scored, $payload, $itemMeta,
 * $students, $studentCount, $studentNameMap, $monthQuery
 */
?>
<div class="panel__header panel__header--compact">
    <div>
        <h2>
            <?php if ($mode === 'view'): ?>
                Просмотр: <?= e(format_attendance_month($month)) ?>
            <?php elseif ($hasSaved): ?>
                Редактирование: <?= e(format_attendance_month($month)) ?>
            <?php else: ?>
                Новый отчёт: <?= e(format_attendance_month($month)) ?>
            <?php endif; ?>
        </h2>
        <p class="text-muted">
            <?php if ($hasSaved): ?>
                Сохранённые баллы: <?= (int) ($saved['total_points'] ?? $scored['total_points']) ?>.
            <?php else: ?>
                Отчёт за этот месяц ещё не сохранён.
            <?php endif; ?>
        </p>
    </div>
    <div class="panel__actions">
        <?php if ($mode === 'view' && $hasSaved): ?>
            <a
                class="btn btn--primary btn--sm"
                href="<?= e($monthQuery($month, 'edit')) ?>"
                data-report-nav
                data-month="<?= e($month) ?>"
                data-mode="edit"
            >Изменить</a>
        <?php elseif ($mode === 'edit' && $hasSaved): ?>
            <a
                class="btn btn--ghost btn--sm"
                href="<?= e($monthQuery($month, 'view')) ?>"
                data-report-nav
                data-month="<?= e($month) ?>"
                data-mode="view"
            >К просмотру</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($mode === 'view'): ?>
    <?php require __DIR__ . '/curator_monthly_report_view.php'; ?>
<?php else: ?>
<p class="text-muted curator-monthly-report__hint">
    Баллы: 0% — 0, 1–25% — 1, 26–50% — 2, 51–75% — 3, 76–100% — 4.
    Для взысканий, академической задолженности, вредных привычек и внешнего вида
    шкала обратная (чем меньше доля, тем выше балл).
</p>

<form
    method="post"
    class="form curator-monthly-report-form"
    data-monthly-report
    data-student-count="<?= (int) $studentCount ?>"
    data-auto-clubs="<?= (int) $payload['clubs_count'] ?>"
    data-auto-debts="<?= (int) $payload['debts_count'] ?>"
    data-auto-unexcused="<?= (int) $payload['unexcused_count'] ?>"
>
    <?= csrf_field() ?>
    <input type="hidden" name="month" value="<?= e($month) ?>">

    <?php foreach ($itemMeta as $item): ?>
        <?php
        $key = $item['key'];
        $points = (int) ($scored['item_points'][$key] ?? 0);
        $percent = (float) ($scored['percents'][$key] ?? 0);
        ?>
        <div
            class="curator-monthly-report-item"
            data-report-item="<?= e($key) ?>"
            data-item-type="<?= e($item['type']) ?>"
            data-inverted="<?= !empty($item['inverted']) ? '1' : '0' ?>"
        >
            <div class="curator-monthly-report-item__head">
                <h3><?= e($item['label']) ?></h3>
                <div class="curator-monthly-report-item__score">
                    <span data-item-percent><?= e((string) $percent) ?></span>% ·
                    <strong><span data-item-points><?= $points ?></span> б.</strong>
                </div>
            </div>

            <?php if ($item['type'] === 'count'): ?>
                <div class="form__group">
                    <label for="key_events_count">Количество студентов</label>
                    <input
                        type="number"
                        id="key_events_count"
                        name="key_events_count"
                        min="0"
                        max="<?= (int) $studentCount ?>"
                        value="<?= (int) $payload['key_events_count'] ?>"
                        data-count-input
                    >
                </div>
            <?php elseif ($item['type'] === 'events'): ?>
                <div class="curator-monthly-report-events" data-events-root>
                    <?php
                    $events = $payload['group_events'] !== [] ? $payload['group_events'] : [''];
                    foreach ($events as $eventTitle):
                    ?>
                    <div class="curator-monthly-report-events__row" data-event-row>
                        <input
                            type="text"
                            name="group_events[]"
                            maxlength="255"
                            placeholder="Название мероприятия"
                            value="<?= e((string) $eventTitle) ?>"
                            data-event-title
                        >
                        <button type="button" class="btn btn--ghost btn--sm" data-event-remove title="Удалить">×</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn--ghost btn--sm" data-event-add>+ Добавить мероприятие</button>
                <p class="text-muted">Учитывается количество названий: <strong data-events-count><?= count($payload['group_events']) ?></strong></p>
            <?php elseif ($item['type'] === 'auto'): ?>
                <?php
                $autoValue = match ($key) {
                    'clubs' => (int) $payload['clubs_count'],
                    'debts' => (int) $payload['debts_count'],
                    'unexcused' => (int) $payload['unexcused_count'],
                    default => 0,
                };
                $autoNote = match ($key) {
                    'clubs' => 'из занятости студентов группы',
                    'debts' => 'из академических задолженностей (ведомости / ГЛАЗ)',
                    'unexcused' => 'из пропусков группы за выбранный месяц',
                    default => 'из системы',
                };
                ?>
                <p class="curator-monthly-report-item__auto">
                    <strong data-auto-count="<?= e($key) ?>"><?= $autoValue ?></strong>
                    <span class="text-muted"> — <?= e($autoNote) ?></span>
                </p>
            <?php elseif ($item['type'] === 'students'): ?>
                <div class="curator-monthly-report-students" data-students-root data-students-key="<?= e($key) ?>">
                    <?php
                    $selected = is_array($payload[$key] ?? null) ? $payload[$key] : [];
                    if ($selected === []) {
                        $selected = [0];
                    }
                    foreach ($selected as $selectedId):
                    ?>
                    <div class="curator-monthly-report-students__row" data-student-row>
                        <select name="<?= e($key) ?>[]" data-student-select>
                            <option value="">— Выберите студента —</option>
                            <?php foreach ($students as $student): ?>
                                <option
                                    value="<?= (int) $student['id'] ?>"
                                    <?= (int) $selectedId === (int) $student['id'] ? ' selected' : '' ?>
                                ><?= e((string) $student['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn--ghost btn--sm" data-student-remove title="Удалить">×</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn--ghost btn--sm" data-student-add>+ Добавить студента</button>
                <p class="text-muted">Выбрано: <strong data-students-count><?= count(array_filter(
                    is_array($payload[$key] ?? null) ? $payload[$key] : [],
                    static fn ($id) => (int) $id > 0
                )) ?></strong></p>
            <?php elseif ($item['type'] === 'characteristics'): ?>
                <div class="summary-report-filters__tabs curator-monthly-report-chars">
                    <label class="summary-report-filters__tab<?= $payload['characteristics'] === 'submitted' ? ' is-active' : '' ?>">
                        <input type="radio" name="characteristics" value="submitted"<?= $payload['characteristics'] === 'submitted' ? ' checked' : '' ?> data-char-input>
                        Характеристики сданы
                    </label>
                    <label class="summary-report-filters__tab<?= $payload['characteristics'] === 'not_submitted' ? ' is-active' : '' ?>">
                        <input type="radio" name="characteristics" value="not_submitted"<?= $payload['characteristics'] !== 'submitted' ? ' checked' : '' ?> data-char-input>
                        Характеристики не сданы
                    </label>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="curator-monthly-report-total">
        <div>
            <div class="curator-monthly-report-total__label">Итого баллов</div>
            <div class="curator-monthly-report-total__value"><span data-total-points><?= (int) $scored['total_points'] ?></span></div>
        </div>
        <button type="submit" class="btn btn--primary">Сохранить отчёт</button>
    </div>
</form>
<?php endif; ?>
