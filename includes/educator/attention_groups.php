<?php

declare(strict_types=1);

/** @var array{academic_year: string, semester1: list, semester2: list} $attentionGroups */
/** @var string $year */

$attentionGroups = $attentionGroups ?? [
    'academic_year' => $year ?? '',
    'semester1' => [],
    'semester2' => [],
];

$renderAttentionGroupsTable = static function (array $rows, string $emptyText): void {
    if ($rows === []) {
        echo '<p class="text-muted">' . e($emptyText) . '</p>';

        return;
    }
    ?>
    <div class="table-wrap">
        <table class="table table--compact educator-attention-table">
            <thead>
                <tr>
                    <th style="width:3rem">№</th>
                    <th>Группа</th>
                    <th>Неуважительные</th>
                    <th>Уважительные</th>
                    <th>Всего</th>
                    <th>На студента</th>
                    <th>Студентов с Н</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><strong><?= e($row['group_number']) ?></strong></td>
                    <td><strong class="educator-attention-table__unexcused"><?= (int) $row['unexcused'] ?></strong></td>
                    <td><?= (int) $row['excused'] ?></td>
                    <td><?= (int) $row['total'] ?></td>
                    <td><?= e((string) $row['per_student_unexcused']) ?></td>
                    <td><?= (int) $row['students_with_unexcused'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
};
?>
<section class="panel educator-attention-block">
    <div class="panel__header">
        <div>
            <h2>Группы, требующие внимания</h2>
            <p class="text-muted">
                Топ-5 групп с наибольшим числом пропусков по неуважительным причинам
                за учебный год <?= e($attentionGroups['academic_year'] ?: ($year ?? '')) ?>.
            </p>
        </div>
    </div>

    <div class="educator-attention-grid">
        <div class="educator-attention-col educator-attention-col--sem1">
            <h3 class="subsection-title">1 семестр</h3>
            <?php $renderAttentionGroupsTable(
                $attentionGroups['semester1'],
                'В 1 семестре групп с неуважительными пропусками нет.'
            ); ?>
        </div>
        <div class="educator-attention-col educator-attention-col--sem2">
            <h3 class="subsection-title">2 семестр</h3>
            <?php $renderAttentionGroupsTable(
                $attentionGroups['semester2'],
                'Во 2 семестре групп с неуважительными пропусками нет.'
            ); ?>
        </div>
    </div>
</section>
