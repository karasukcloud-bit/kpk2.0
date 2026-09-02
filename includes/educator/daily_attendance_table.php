<?php

declare(strict_types=1);

/** @var array<string, mixed> $dailyReport */
$reasons = $dailyReport['reasons'] ?? [];
$rows = $dailyReport['rows'] ?? [];

$formatCell = static function (int $value): string {
    if ($value <= 0) {
        return '<span class="eda-cell-empty">—</span>';
    }

    return '<span class="eda-cell-value">' . $value . '</span>';
};
?>
<?php if ($rows === []): ?>
    <p class="text-muted">Группы пока не добавлены.</p>
<?php else: ?>
    <div class="eda-legend educator-no-print">
        <span class="eda-legend__item eda-legend__item--marked">
            <span class="eda-legend__swatch" aria-hidden="true"></span>
            Пропуски за день отмечены куратором
        </span>
        <span class="eda-legend__item">
            <span class="eda-legend__dot eda-legend__dot--unexcused" aria-hidden="true"></span>
            Неуважительные
        </span>
    </div>

    <div class="table-wrap eda-table-wrap">
        <table class="table educator-daily-attendance-table">
            <thead>
                <tr>
                    <th class="eda-col-group">Группа</th>
                    <th class="eda-col-curator">Куратор</th>
                    <?php foreach ($reasons as $reason): ?>
                    <th class="eda-col-reason" title="<?= e((string) $reason['name']) ?>">
                        <span class="eda-th-label"><?= e((string) $reason['name']) ?></span>
                    </th>
                    <?php endforeach; ?>
                    <th class="eda-col-unexcused">Неуважительные</th>
                    <th class="eda-col-students">Студенты с неуважительными пропусками</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <?php
                $groupHasAbsences = !empty($row['has_absences']);
                $unexcused = (int) ($row['unexcused'] ?? 0);
                $students = $row['unexcused_students'] ?? [];
                $curatorName = trim((string) ($row['curator_name'] ?? ''));
                $curatorDisplay = $curatorName !== '' ? person_last_first_name($curatorName) : '—';
                ?>
                <tr class="<?= $groupHasAbsences ? 'educator-daily-attendance-table__row--marked' : 'educator-daily-attendance-table__row--empty' ?>">
                    <td class="educator-daily-attendance-table__group<?= $groupHasAbsences ? ' educator-daily-attendance-table__group--marked' : '' ?>">
                        <div class="eda-group-cell">
                            <span class="eda-group-badge"><?= e($row['group_number']) ?></span>
                            <?php if ($groupHasAbsences): ?>
                            <span class="eda-group-status educator-no-print" title="Дата отмечена куратором">✓</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="eda-col-curator<?= $groupHasAbsences ? ' eda-col-curator--marked' : '' ?>">
                        <?php if ($curatorName !== ''): ?>
                        <span class="eda-curator-name" title="<?= e($curatorName) ?>"><?= e($curatorDisplay) ?></span>
                        <?php else: ?>
                        <span class="eda-cell-empty">—</span>
                        <?php endif; ?>
                    </td>
                    <?php foreach ($reasons as $reason): ?>
                    <?php
                    $reasonId = (int) $reason['id'];
                    $reasonCount = (int) ($row['reason_totals'][$reasonId] ?? 0);
                    ?>
                    <td class="eda-col-reason<?= $reasonCount > 0 ? ' eda-col-reason--filled' : '' ?>">
                        <?= $formatCell($reasonCount) ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="eda-col-unexcused<?= $unexcused > 0 ? ' eda-col-unexcused--filled' : '' ?>">
                        <?php if ($unexcused > 0): ?>
                        <span class="eda-unexcused-badge"><?= $unexcused ?></span>
                        <?php else: ?>
                        <span class="eda-cell-empty">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="educator-attendance-table__students">
                        <?php if ($students === []): ?>
                            <span class="eda-cell-empty">—</span>
                        <?php else: ?>
                            <div class="eda-students">
                                <?php foreach ($students as $student): ?>
                                <span class="eda-student-chip">
                                    <span class="eda-student-chip__name"><?= e((string) $student['full_name']) ?></span>
                                    <span class="eda-student-chip__count"><?= (int) $student['count'] ?></span>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <span class="eda-students-print educator-print-only">
                                <?= e(format_educator_unexcused_students_list($students)) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
