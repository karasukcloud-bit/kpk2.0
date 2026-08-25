<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $tableGroups */
/** @var array<string, mixed> $organization */
/** @var array<int, array<string, mixed>> $teachers */
$glazEditable = $glazEditable ?? false;
$glazHighlightGroupNumbers = $glazHighlightGroupNumbers ?? [];
$glazHighlightTeacherId = $glazHighlightTeacherId ?? null;
$glazShowLegend = $glazShowLegend ?? false;
?>
<section class="panel glaz-print-block">
    <div class="glaz-print-header">
        <h2>График ликвидации академической задолженности</h2>
        <?php if ($organization['name'] !== ''): ?>
            <p><?= e($organization['name']) ?></p>
        <?php endif; ?>
        <p class="text-muted">Только архивированные ведомости. Незавершённые семестры в график не включаются.</p>
        <?php if ($glazShowLegend): ?>
            <p class="text-muted glaz-legend glaz-no-print">
                <span class="glaz-legend__sample glaz-legend__sample--group"></span> — ваша группа (куратор)
                <span class="glaz-legend__sample glaz-legend__sample--name"></span> — ваша фамилия в составе комиссии
            </p>
        <?php endif; ?>
    </div>

    <?php if ($tableGroups === []): ?>
        <p class="text-muted">Академическая задолженность не найдена.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table glaz-table">
                <thead>
                    <tr>
                        <th>Группа</th>
                        <th>Студент</th>
                        <th>Предмет</th>
                        <th>Период задолженности</th>
                        <th>Дата и время ликвидации</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableGroups as $groupData): ?>
                        <?php
                        $isMyGroup = in_array($groupData['group_number'], $glazHighlightGroupNumbers, true);
                        $groupRendered = false;
                        foreach ($groupData['students'] as $studentData):
                            $studentRendered = false;
                            foreach ($studentData['items'] as $itemIndex => $item):
                                $schedule = $item['schedule'];
                                $displayText = format_glaz_schedule_text($schedule);
                                $displayHtml = format_glaz_schedule_display_html($schedule, $glazHighlightTeacherId);
                        ?>
                        <tr class="glaz-row<?= $itemIndex > 0 ? ' glaz-row--split' : '' ?><?= $isMyGroup ? ' glaz-row--group-mine' : '' ?>">
                            <?php if (!$groupRendered): ?>
                            <td rowspan="<?= (int) $groupData['rowspan'] ?>" class="glaz-cell-group<?= $isMyGroup ? ' glaz-cell-group--mine' : '' ?>">
                                <?= e($groupData['group_number']) ?>
                            </td>
                            <?php $groupRendered = true; endif; ?>

                            <?php if (!$studentRendered): ?>
                            <td rowspan="<?= (int) $studentData['rowspan'] ?>" class="glaz-cell-student">
                                <?php if (!empty($studentData['has_expired_debt'])): ?>
                                <span
                                    class="glaz-warning"
                                    title="<?= e(glaz_expiry_warning_title()) ?>"
                                    aria-label="<?= e(glaz_expiry_warning_title()) ?>"
                                >!</span>
                                <?php endif; ?>
                                <?= e($studentData['student_name']) ?>
                            </td>
                            <?php $studentRendered = true; endif; ?>

                            <td class="glaz-cell-subject"><?= e($item['subject_name']) ?></td>
                            <td>
                                <?php if (!empty($item['is_expired'])): ?>
                                <span
                                    class="glaz-warning glaz-warning--period"
                                    title="<?= e(glaz_expiry_warning_title()) ?>"
                                    aria-label="<?= e(glaz_expiry_warning_title()) ?>"
                                >!</span>
                                <?php endif; ?>
                                <?= e($item['period_label']) ?>
                            </td>
                            <td class="glaz-cell-schedule">
                                <div
                                    class="glaz-schedule-display"
                                    data-glaz-display="<?= e($item['debt_key']) ?>"
                                >
                                    <?php if ($glazEditable): ?>
                                        <?= $displayText !== '' ? nl2br(e($displayText)) : '<span class="text-muted">—</span>' ?>
                                    <?php elseif ($displayHtml !== ''): ?>
                                        <?= $displayHtml ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($glazEditable): ?>
                                <form
                                    class="glaz-schedule-form glaz-no-print"
                                    data-glaz-form
                                    data-save-url="glaz_save.php"
                                    data-debt-key="<?= e($item['debt_key']) ?>"
                                >
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="student_id" value="<?= (int) $item['student_id'] ?>">
                                    <input type="hidden" name="curriculum_item_id" value="<?= (int) $item['curriculum_item_id'] ?>">
                                    <input type="hidden" name="academic_year" value="<?= e($item['academic_year']) ?>">
                                    <input type="hidden" name="semester" value="<?= e($item['semester']) ?>">

                                    <div class="glaz-schedule-form__row">
                                        <label>
                                            <span class="glaz-schedule-form__label">Дата</span>
                                            <input
                                                type="date"
                                                name="liquidation_date"
                                                value="<?= e((string) ($schedule['liquidation_date'] ?? '')) ?>"
                                            >
                                        </label>
                                        <label>
                                            <span class="glaz-schedule-form__label">Время</span>
                                            <input
                                                type="time"
                                                name="liquidation_time"
                                                value="<?= e((string) ($schedule['liquidation_time'] ?? '')) ?>"
                                            >
                                        </label>
                                    </div>

                                    <div class="glaz-commission" data-glaz-commission data-max="3">
                                        <span class="glaz-schedule-form__label">Состав комиссии</span>
                                        <div class="glaz-commission__list" data-glaz-commission-list>
                                            <?php
                                            $commissionIds = glaz_commission_teacher_ids($schedule);
                                            foreach ($commissionIds as $index => $teacherId):
                                                $selectedId = (int) $teacherId;
                                                $withRemove = $index > 0;
                                                require __DIR__ . '/../glaz_commission_row.php';
                                            endforeach;
                                            ?>
                                        </div>
                                        <button
                                            type="button"
                                            class="glaz-commission__add"
                                            data-glaz-commission-add
                                            title="Добавить преподавателя"
                                            aria-label="Добавить преподавателя"
                                        >+</button>
                                    </div>

                                    <div class="glaz-schedule-form__actions">
                                        <button type="submit" class="btn btn--primary btn--sm">Сохранить</button>
                                        <span class="glaz-schedule-form__status text-muted" data-glaz-status></span>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                        endforeach;
                        ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
