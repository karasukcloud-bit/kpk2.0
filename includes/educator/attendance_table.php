<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $rows */
/** @var string $tableTitle */
?>
<h3 class="subsection-title"><?= e($tableTitle) ?></h3>
<?php if ($rows === []): ?>
    <p class="text-muted">Группы пока не добавлены.</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="table educator-attendance-table">
            <thead>
                <tr>
                    <th>Группа</th>
                    <th>Всего пропусков</th>
                    <th>Уважительных</th>
                    <th>Неуважительных</th>
                    <th>На студента</th>
                    <th>Уважит. на студ.</th>
                    <th>Неуважит. на студ.</th>
                    <th>Студенты с неуважит. пропусками</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <?php $summary = $row['summary']; ?>
                <tr>
                    <td><?= e($row['group_number']) ?></td>
                    <td><?= (int) $summary['total'] ?></td>
                    <td><?= (int) $summary['excused'] ?></td>
                    <td><?= (int) $summary['unexcused'] ?></td>
                    <td><?= e((string) $summary['per_student_total']) ?></td>
                    <td><?= e((string) $summary['per_student_excused']) ?></td>
                    <td><?= e((string) $summary['per_student_unexcused']) ?></td>
                    <td class="educator-attendance-table__students">
                        <?= e(format_educator_unexcused_students_list($row['unexcused_students'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
