<?php

declare(strict_types=1);

/** @var array $group */
/** @var array $students */
/** @var string $year */
/** @var string $month */
/** @var array $journal */
/** @var array $monthTotals */
/** @var array|null $monthSummary */
/** @var bool $attendanceReadOnly */
/** @var callable|null $attendanceUrl */

$attendanceReadOnly = $attendanceReadOnly ?? false;
$attendanceShowIntro = $attendanceShowIntro ?? true;
$groupId = (int) $group['id'];
?>
<?php if ($attendanceShowIntro): ?>
<div class="attendance-journal-intro">
<p class="attendance-journal-meta">
    Группа <strong><?= e($group['number']) ?></strong>
    · учебный год <?= e($year) ?>
    · <?= e(format_attendance_month($month)) ?>
    · студентов: <?= count($students) ?>
</p>
<?php if (!$attendanceReadOnly): ?>
<p class="text-muted">
    Все пропуски сохраняются по датам и учебному году — их можно будет вывести в сводной таблице.
</p>
<?php else: ?>
<p class="text-muted">Только просмотр. Редактирование доступно куратору группы.</p>
<?php endif; ?>
</div>
<?php endif; ?>

<?php if (empty($journal['days'])): ?>
    <p class="text-muted">
        За <?= e(format_attendance_month($month)) ?> записей нет.
        <?php if (!$attendanceReadOnly): ?>
        Нажмите «Добавить дату», чтобы внести пропуски.
        <?php endif; ?>
    </p>
<?php else: ?>
    <div class="table-wrap">
        <table class="table attendance-table">
            <thead>
                <tr>
                    <th class="attendance-table__date-col">Дата</th>
                    <?php foreach ($students as $student): ?>
                    <th class="attendance-table__student-col">
                        <span class="attendance-student-title"><?= e(person_last_first_name((string) $student['full_name'])) ?></span>
                    </th>
                    <?php endforeach; ?>
                    <?php if (!$attendanceReadOnly): ?>
                    <th class="attendance-table__actions-col">Действия</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($journal['days'] as $day): ?>
                <?php $dayId = (int) $day['id']; ?>
                <tr>
                    <td class="attendance-table__date-col">
                        <strong><?= e(format_attendance_date($day['attendance_date'])) ?></strong>
                    </td>
                    <?php foreach ($students as $student): ?>
                    <?php
                    $studentId = (int) $student['id'];
                    $entry = $journal['entries'][$dayId][$studentId] ?? null;
                    ?>
                    <td class="attendance-table__cell<?= $entry !== null ? (((int) $entry['excused_lessons'] > 0) ? ' attendance-cell--excused' : '') . (((int) $entry['unexcused_lessons'] > 0) ? ' attendance-cell--unexcused' : '') : '' ?>">
                        <?php if ($entry === null): ?>
                            <span class="text-muted">—</span>
                        <?php else: ?>
                            <span class="attendance-cell__counts">
                                <?= (int) $entry['excused_lessons'] ?> / <?= (int) $entry['unexcused_lessons'] ?>
                            </span>
                            <?php if ($entry['excused_lessons'] > 0 && $entry['reason_name'] !== ''): ?>
                            <span
                                class="attendance-cell__reason text-muted"
                                title="<?= e($entry['reason_name']) ?>"
                            ><?= e(format_attendance_reason_short($entry['reason_name'])) ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <?php if (!$attendanceReadOnly && is_callable($attendanceUrl)): ?>
                    <td class="attendance-table__actions-col">
                        <div class="table__actions">
                            <a
                                href="<?= e($attendanceUrl($groupId, $month, ['edit_day' => $dayId])) ?>"
                                class="journal-icon-btn"
                                title="Изменить"
                                aria-label="Изменить"
                            >✎</a>
                            <form method="post" class="form-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_day">
                                <input type="hidden" name="month" value="<?= e($month) ?>">
                                <input type="hidden" name="day_id" value="<?= $dayId ?>">
                                <button
                                    type="submit"
                                    class="journal-icon-btn journal-icon-btn--danger"
                                    title="Удалить"
                                    aria-label="Удалить"
                                    onclick="return confirm('Удалить запись за эту дату?')"
                                >×</button>
                            </form>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="attendance-table__totals">
                    <th class="attendance-table__date-col">Итого за месяц</th>
                    <?php foreach ($students as $student): ?>
                    <?php
                    $studentId = (int) $student['id'];
                    $total = $monthTotals[$studentId] ?? ['excused_lessons' => 0, 'unexcused_lessons' => 0];
                    $hasTotal = $total['excused_lessons'] > 0 || $total['unexcused_lessons'] > 0;
                    ?>
                    <td class="attendance-table__cell">
                        <?php if ($hasTotal): ?>
                            <span class="attendance-cell__counts">
                                <?= (int) $total['excused_lessons'] ?> / <?= (int) $total['unexcused_lessons'] ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <?php if (!$attendanceReadOnly): ?>
                    <td></td>
                    <?php endif; ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="text-muted attendance-legend">
        В ячейке: уважительные / неуважительные пропуски (уроки). Под числами — сокращение причины уважительного пропуска (наведите курсор, чтобы увидеть полное название).
        Внизу — сумма пропусков за выбранный месяц.
    </p>

    <?php
    $unexcusedStudents = [];
    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $unexcused = (int) ($monthTotals[$studentId]['unexcused_lessons'] ?? 0);
        if ($unexcused <= 0) {
            continue;
        }
        $unexcusedStudents[] = [
            'name' => person_last_first_name((string) $student['full_name']),
            'count' => $unexcused,
        ];
    }
    usort(
        $unexcusedStudents,
        static fn (array $a, array $b): int => $b['count'] <=> $a['count'] ?: strcmp($a['name'], $b['name'])
    );
    ?>
    <section class="attendance-unexcused-list">
        <h3>Неуважительные пропуски за <?= e(format_attendance_month($month)) ?></h3>
        <?php if ($unexcusedStudents === []): ?>
            <p class="text-muted">Студентов с неуважительными пропусками в этом месяце нет.</p>
        <?php else: ?>
            <ul class="attendance-unexcused-list__items">
                <?php foreach ($unexcusedStudents as $row): ?>
                <li>
                    <span class="attendance-unexcused-list__name"><?= e($row['name']) ?></span>
                    <span class="attendance-unexcused-list__sep">—</span>
                    <span class="attendance-unexcused-list__count"><?= (int) $row['count'] ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if ($monthSummary !== null): ?>
    <section class="attendance-summary">
        <h2>Сводка за <?= e(format_attendance_month($month)) ?></h2>
        <div class="attendance-summary__grid">
            <div class="attendance-summary__col">
                <h3>Все пропуски</h3>
                <dl class="profile-list">
                    <dt>Всего пропусков</dt>
                    <dd><?= (int) $monthSummary['total'] ?></dd>
                    <dt>Всего уважительных</dt>
                    <dd><?= (int) $monthSummary['excused'] ?></dd>
                    <dt>Всего неуважительных</dt>
                    <dd><?= (int) $monthSummary['unexcused'] ?></dd>
                </dl>
            </div>
            <div class="attendance-summary__col">
                <h3>На одного студента</h3>
                <dl class="profile-list">
                    <dt>Всего на одного студента</dt>
                    <dd><?= e((string) $monthSummary['per_student_total']) ?></dd>
                    <dt>Уважительных на одного студента</dt>
                    <dd><?= e((string) $monthSummary['per_student_excused']) ?></dd>
                    <dt>Неуважительных на одного студента</dt>
                    <dd><?= e((string) $monthSummary['per_student_unexcused']) ?></dd>
                </dl>
                <p class="text-muted">
                    Сумма за месяц ÷ число студентов (<?= (int) $monthSummary['student_count'] ?>).
                </p>
            </div>
        </div>
    </section>
    <?php endif; ?>
<?php endif; ?>
