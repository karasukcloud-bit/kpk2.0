<?php

declare(strict_types=1);

/** Ожидает: $report (из build_parent_student_report). */
$report = $report ?? null;
if (!is_array($report)) {
    return;
}
$student = $report['student'];
$group = $report['group'];
$period = $report['period'];
?>
<article class="parent-report" data-parent-report-print-area>
    <?php if ($report['org_name'] !== ''): ?>
        <h1 class="parent-report__org"><?= e($report['org_name']) ?></h1>
    <?php endif; ?>
    <h2 class="parent-report__title">Отчёт для родителей</h2>
    <p class="parent-report__meta">
        Студент: <strong><?= e((string) ($student['full_name'] ?? '')) ?></strong><br>
        Группа: <?= e((string) ($group['number'] ?? '')) ?>
        <?php if (!empty($group['specialty_name'])): ?>
            · <?= e((string) $group['specialty_name']) ?>
        <?php endif; ?><br>
        Период: <?= e(semester_label((string) $period['semester'])) ?>
        <?= e((string) $period['academic_year']) ?> учебного года<br>
        Источник оценок: <?= e(parent_report_source_label((string) $report['source'])) ?>
    </p>

    <div class="parent-report__body">
        <section class="parent-report__block parent-report__block--grades">
            <h3>Успеваемость</h3>
            <?php if ($report['subjects'] === []): ?>
                <p class="text-muted">Предметы за период не найдены.</p>
            <?php else: ?>
                <div class="table-wrap parent-report__grades-wrap">
                    <table class="table parent-report__grades-table">
                        <thead>
                            <tr>
                                <th>Предмет</th>
                                <th class="parent-report__grade-col">Оценка</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['subjects'] as $item): ?>
                            <tr>
                                <td><?= e($item['name']) ?></td>
                                <td class="parent-report__grade-col<?= ((int) ($item['grade'] ?? 0) === 2) ? ' parent-report__grade--two' : '' ?>">
                                    <?= $item['grade'] !== null ? e((string) $item['grade']) : '—' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="parent-report__average">
                    Средний балл:
                    <strong><?= $report['average'] !== null ? e((string) $report['average']) : '—' ?></strong>
                </p>
            <?php endif; ?>
        </section>

        <section class="parent-report__block parent-report__block--attendance">
            <h3>Посещаемость</h3>
            <p class="parent-report__attendance-note text-muted">
                За <?= e(semester_label((string) $period['semester'])) ?> текущего учебного года.
            </p>
            <dl class="profile-list parent-report__summary parent-report__summary--attendance">
                <dt>Уважительные пропуски</dt>
                <dd><?= (int) $report['attendance']['excused'] ?></dd>
                <dt>Неуважительные пропуски</dt>
                <dd><?= (int) $report['attendance']['unexcused'] ?></dd>
                <dt>Всего пропусков</dt>
                <dd><?= (int) $report['attendance']['total'] ?></dd>
            </dl>
        </section>
    </div>
</article>
