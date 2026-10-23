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

    <h3>Успеваемость</h3>
    <?php if ($report['subjects'] === []): ?>
        <p class="text-muted">Предметы за период не найдены.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
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
        <dl class="profile-list parent-report__summary">
            <dt>Выставлено оценок</dt>
            <dd><?= (int) $report['grades_filled'] ?> из <?= (int) $report['grades_expected'] ?></dd>
            <dt>Средний балл</dt>
            <dd><?= $report['average'] !== null ? e((string) $report['average']) : '—' ?></dd>
            <dt>Есть оценки «2»</dt>
            <dd><?= !empty($report['has_twos']) ? 'да' : 'нет' ?></dd>
        </dl>
    <?php endif; ?>

    <h3>Посещаемость</h3>
    <p class="text-muted">За <?= e(semester_label((string) $period['semester'])) ?> текущего учебного года.</p>
    <dl class="profile-list parent-report__summary">
        <dt>Уважительные пропуски</dt>
        <dd><?= (int) $report['attendance']['excused'] ?></dd>
        <dt>Неуважительные пропуски</dt>
        <dd><?= (int) $report['attendance']['unexcused'] ?></dd>
        <dt>Всего пропусков</dt>
        <dd><?= (int) $report['attendance']['total'] ?></dd>
    </dl>
</article>
