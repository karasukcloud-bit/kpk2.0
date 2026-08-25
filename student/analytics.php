<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/student_accounts.php';
require_once __DIR__ . '/../includes/student_analytics.php';

require_student();

$student = current_student();
if ($student === null) {
    flash_set('error', 'Карточка студента не найдена. Обратитесь к куратору.');
    header('Location: cabinet.php');
    exit;
}

$analytics = build_student_analytics($student);
$period = $analytics['period'];
$overview = $analytics['overview'];
$subjects = $analytics['subjects'];
$attendance = $analytics['attendance'];
$journal = $analytics['journal_activity'];
$recommendations = $analytics['recommendations'];
$narratives = $analytics['narratives'];

$pageTitle = 'Аналитика';
$showHeader = true;
$basePath = '../';
$currentStudentTab = 'analytics';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Аналитика обучения</h1>
                <p class="text-muted">
                    <?= e($student['full_name']) ?>
                    · группа <?= e((string) $student['group_number']) ?>
                    · <?= e($period['academic_year']) ?>
                    · <?= e(semester_label($period['semester'])) ?>
                </p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/student_nav.php'; ?>
    </section>

    <section class="panel sa-intro">
        <div class="sa-intro__icon" aria-hidden="true"><?= student_analytics_icon('user') ?></div>
        <div>
            <h2 class="sa-intro__title">Педагогический обзор</h2>
            <p class="sa-intro__text"><?= e($narratives['overview']) ?></p>
        </div>
    </section>

    <section class="sa-kpis">
        <article class="sa-kpi sa-kpi--<?= e((string) $overview['level']) ?>">
            <div class="sa-kpi__icon"><?= student_analytics_icon('chart') ?></div>
            <div class="sa-kpi__body">
                <span class="sa-kpi__label">Средний балл (журнал)</span>
                <strong class="sa-kpi__value">
                    <?= $overview['average'] !== null ? e(student_analytics_fmt((float) $overview['average'])) : '—' ?>
                </strong>
                <span class="sa-kpi__hint"><?= e((string) $overview['level_label']) ?></span>
            </div>
        </article>
        <article class="sa-kpi">
            <div class="sa-kpi__icon"><?= student_analytics_icon('book') ?></div>
            <div class="sa-kpi__body">
                <span class="sa-kpi__label">Оценок / дисциплин</span>
                <strong class="sa-kpi__value">
                    <?= (int) $overview['marks_count'] ?> / <?= (int) $overview['subjects_with_marks'] ?>
                </strong>
                <span class="sa-kpi__hint">из <?= (int) $overview['subjects_count'] ?> в семестре</span>
            </div>
        </article>
        <article class="sa-kpi">
            <div class="sa-kpi__icon"><?= student_analytics_icon('spark') ?></div>
            <div class="sa-kpi__body">
                <span class="sa-kpi__label">Активность на занятиях</span>
                <strong class="sa-kpi__value"><?= (int) $journal['activity_count'] ?></strong>
                <span class="sa-kpi__hint">
                    <?php if ($journal['activity_rate'] !== null): ?>
                        <?= e(student_analytics_fmt((float) $journal['activity_rate'])) ?>% посещённых занятий
                    <?php else: ?>
                        пока нет базы для расчёта
                    <?php endif; ?>
                </span>
            </div>
        </article>
        <article class="sa-kpi">
            <div class="sa-kpi__icon"><?= student_analytics_icon('clock') ?></div>
            <div class="sa-kpi__body">
                <span class="sa-kpi__label">Опоздания</span>
                <strong class="sa-kpi__value"><?= (int) $journal['late_count'] ?></strong>
                <span class="sa-kpi__hint">
                    <?php if ($journal['late_rate'] !== null): ?>
                        <?= e(student_analytics_fmt((float) $journal['late_rate'])) ?>% посещённых занятий
                    <?php else: ?>
                        данных пока недостаточно
                    <?php endif; ?>
                </span>
            </div>
        </article>
        <article class="sa-kpi">
            <div class="sa-kpi__icon"><?= student_analytics_icon('calendar') ?></div>
            <div class="sa-kpi__body">
                <span class="sa-kpi__label">Пропуски (посещаемость)</span>
                <strong class="sa-kpi__value"><?= (int) $attendance['total'] ?> ч</strong>
                <span class="sa-kpi__hint">
                    уваж. <?= (int) $attendance['excused'] ?> · неуваж. <?= (int) $attendance['unexcused'] ?>
                </span>
            </div>
        </article>
        <article class="sa-kpi">
            <div class="sa-kpi__icon"><?= student_analytics_icon('target') ?></div>
            <div class="sa-kpi__body">
                <span class="sa-kpi__label">Зачётка (итоговые)</span>
                <strong class="sa-kpi__value">
                    <?= $overview['record_book_average'] !== null
                        ? e(student_analytics_fmt((float) $overview['record_book_average']))
                        : '—' ?>
                </strong>
                <span class="sa-kpi__hint"><?= (int) $overview['record_book_graded'] ?> итоговых оценок</span>
            </div>
        </article>
    </section>

    <section class="panel">
        <div class="sa-section-head">
            <div class="sa-section-head__icon"><?= student_analytics_icon('book') ?></div>
            <div>
                <h2>Ситуация по дисциплинам</h2>
                <p class="text-muted"><?= e($narratives['subjects']) ?></p>
            </div>
        </div>

        <?php if ($subjects === []): ?>
            <p class="text-muted">В текущем семестре нет предметов для анализа.</p>
        <?php else: ?>
            <div class="sa-subjects">
                <?php foreach ($subjects as $subject): ?>
                <article class="sa-subject sa-subject--<?= e((string) $subject['status']) ?>">
                    <div class="sa-subject__top">
                        <div>
                            <h3 class="sa-subject__title"><?= e((string) $subject['subject_name']) ?></h3>
                            <?php if ($subject['teacher_name'] !== ''): ?>
                            <p class="sa-subject__teacher">Преподаватель: <?= e((string) $subject['teacher_name']) ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="sa-subject__badge"><?= e((string) $subject['status_label']) ?></span>
                    </div>
                    <div class="sa-subject__stats">
                        <span>Средний балл: <strong><?= $subject['average'] !== null ? e(student_analytics_fmt((float) $subject['average'])) : '—' ?></strong></span>
                        <span>Оценок: <strong><?= (int) $subject['marks_count'] ?></strong></span>
                        <span>Пропуски «Н»: <strong><?= (int) $subject['absent'] ?></strong></span>
                        <span>Активность: <strong><?= (int) $subject['activity'] ?></strong></span>
                        <span>Опоздания: <strong><?= (int) $subject['late'] ?></strong></span>
                        <?php if ($subject['final_display'] !== ''): ?>
                        <span>Итог: <strong><?= e((string) $subject['final_display']) ?></strong></span>
                        <?php endif; ?>
                    </div>
                    <p class="sa-subject__comment"><?= e((string) $subject['comment']) ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="sa-two-col">
        <section class="panel">
            <div class="sa-section-head">
                <div class="sa-section-head__icon"><?= student_analytics_icon('spark') ?></div>
                <div>
                    <h2>Активность и дисциплина на занятиях</h2>
                    <p class="text-muted"><?= e($narratives['activity']) ?></p>
                </div>
            </div>
            <ul class="sa-facts">
                <li>
                    <span class="sa-facts__icon"><?= student_analytics_icon('check') ?></span>
                    <div>
                        <strong>Присутствие в журнале</strong>
                        <p><?= (int) $journal['lessons_visited'] ?> занятий с отметкой присутствия
                            <?php if ($journal['attendance_rate'] !== null): ?>
                                (<?= e(student_analytics_fmt((float) $journal['attendance_rate'])) ?>%)
                            <?php endif; ?>
                        </p>
                    </div>
                </li>
                <li>
                    <span class="sa-facts__icon"><?= student_analytics_icon('alert') ?></span>
                    <div>
                        <strong>Пропуски в журнале («Н»)</strong>
                        <p><?= (int) $journal['lessons_absent'] ?> занятий</p>
                    </div>
                </li>
                <li>
                    <span class="sa-facts__icon"><?= student_analytics_icon('spark') ?></span>
                    <div>
                        <strong>Активность</strong>
                        <p><?= (int) $journal['activity_count'] ?> отметок за активность на уроке</p>
                    </div>
                </li>
                <li>
                    <span class="sa-facts__icon"><?= student_analytics_icon('clock') ?></span>
                    <div>
                        <strong>Опоздания</strong>
                        <p><?= (int) $journal['late_count'] ?> зафиксированных опозданий</p>
                    </div>
                </li>
            </ul>
        </section>

        <section class="panel">
            <div class="sa-section-head">
                <div class="sa-section-head__icon"><?= student_analytics_icon('calendar') ?></div>
                <div>
                    <h2>Посещаемость по причинам</h2>
                    <p class="text-muted"><?= e($narratives['attendance']) ?></p>
                </div>
            </div>

            <?php if ($attendance['by_reason'] === []): ?>
                <p class="text-muted">Записей о пропусках в журнале посещаемости нет.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table sa-reasons-table">
                        <thead>
                            <tr>
                                <th>Причина</th>
                                <th>Уважит.</th>
                                <th>Неуважит.</th>
                                <th>Всего</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance['by_reason'] as $reason): ?>
                            <tr>
                                <td><?= e((string) $reason['reason_name']) ?></td>
                                <td><?= (int) $reason['excused'] ?></td>
                                <td><?= (int) $reason['unexcused'] ?></td>
                                <td><strong><?= (int) $reason['total'] ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <section class="panel">
        <div class="sa-section-head">
            <div class="sa-section-head__icon"><?= student_analytics_icon('target') ?></div>
            <div>
                <h2>Рекомендации по улучшению</h2>
                <p class="text-muted"><?= e($narratives['closing']) ?></p>
            </div>
        </div>
        <div class="sa-recs">
            <?php foreach ($recommendations as $rec): ?>
            <article class="sa-rec">
                <div class="sa-rec__icon"><?= student_analytics_icon((string) $rec['icon']) ?></div>
                <div>
                    <h3 class="sa-rec__title"><?= e((string) $rec['title']) ?></h3>
                    <p class="sa-rec__text"><?= e((string) $rec['text']) ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
