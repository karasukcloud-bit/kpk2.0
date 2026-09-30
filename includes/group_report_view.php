<?php

declare(strict_types=1);

/** @var array $group */
/** @var array $students */
/** @var array|null $report */
/** @var bool $showGroupReportTitle */

$showGroupReportTitle = $showGroupReportTitle ?? true;
$report = $report ?? build_group_report(
    $students ?? [],
    isset($group['id']) ? (int) $group['id'] : null
);

$activitiesBlock = $report['activities'] ?? [
    'with_activities' => 0,
    'without_activities' => 0,
    'club_count' => 0,
    'section_count' => 0,
    'pct_with' => 0,
    'pct_without' => 0,
    'rows' => [],
];
$totalStudents = (int) ($report['total'] ?? 0);
$malePct = $totalStudents > 0 ? round(((int) $report['male'] / $totalStudents) * 100) : 0;
$femalePct = $totalStudents > 0 ? round(((int) $report['female'] / $totalStudents) * 100) : 0;
$groupCourse = function_exists('get_group_course') ? get_group_course($group) : (int) ($group['course'] ?? 0);
?>
<div class="group-report">
    <?php if ($showGroupReportTitle): ?>
        <h2 class="subsection-title">Аналитическая справка по группе</h2>
        <p class="text-muted">
            Группа <strong><?= e($group['number']) ?></strong>
            · <?= e($group['specialty_name']) ?> (<?= e($group['specialty_code']) ?>).
            Данные пересчитываются по актуальным карточкам студентов.
        </p>
    <?php endif; ?>

    <?php if ($totalStudents === 0): ?>
        <p class="text-muted">В группе нет студентов — справка пуста.</p>
    <?php else: ?>
        <div class="group-report-dash">
            <div class="group-report-dash__hero">
                <div class="group-report-dash__hero-main">
                    <div class="group-report-dash__eyebrow">Сводка по группе</div>
                    <div class="group-report-dash__title">
                        <span class="group-report-dash__group"><?= e((string) $group['number']) ?></span>
                        <?php if ($groupCourse > 0): ?>
                        <span class="group-report-dash__course"><?= $groupCourse ?> курс</span>
                        <?php endif; ?>
                    </div>
                    <div class="group-report-dash__specialty">
                        <?= e((string) ($group['specialty_name'] ?? '')) ?>
                        <?php if (!empty($group['specialty_code'])): ?>
                        <span class="text-muted">(<?= e((string) $group['specialty_code']) ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="group-report-dash__hero-side">
                    <div class="group-report-dash__kpi group-report-dash__kpi--accent">
                        <div class="group-report-dash__kpi-value"><?= $totalStudents ?></div>
                        <div class="group-report-dash__kpi-label">студентов</div>
                    </div>
                </div>
            </div>

            <div class="group-report-dash__grid">
                <div class="group-report-dash__card">
                    <div class="group-report-dash__card-label">Состав</div>
                    <div class="group-report-dash__split">
                        <div>
                            <div class="group-report-dash__split-value"><?= (int) $report['male'] ?></div>
                            <div class="group-report-dash__split-label">юношей</div>
                        </div>
                        <div>
                            <div class="group-report-dash__split-value"><?= (int) $report['female'] ?></div>
                            <div class="group-report-dash__split-label">девушек</div>
                        </div>
                    </div>
                    <div class="group-report-dash__bar" aria-hidden="true">
                        <span class="group-report-dash__bar-male" style="width: <?= $malePct ?>%"></span>
                        <span class="group-report-dash__bar-female" style="width: <?= $femalePct ?>%"></span>
                    </div>
                </div>

                <div class="group-report-dash__card">
                    <div class="group-report-dash__card-label">Возраст</div>
                    <div class="group-report-dash__big"><?= e(format_group_report_number($report['avg_age'])) ?></div>
                    <div class="group-report-dash__meta">
                        <?= (int) $report['minor'] ?> несовершеннолетних · <?= (int) $report['adult'] ?> совершеннолетних
                    </div>
                </div>

                <div class="group-report-dash__card">
                    <div class="group-report-dash__card-label">Успеваемость</div>
                    <div class="group-report-dash__big">
                        <?= e($report['absolute_percent'] !== null
                            ? format_group_report_number((float) $report['absolute_percent']) . '%'
                            : '—') ?>
                    </div>
                    <div class="group-report-dash__meta">
                        отличников <?= (int) ($report['excellent_count'] ?? 0) ?>
                        · хорошистов <?= (int) ($report['only_good_count'] ?? 0) ?>
                        · неуспевающих <?= (int) ($report['with_twos_count'] ?? 0) ?>
                    </div>
                </div>

                <div class="group-report-dash__card group-report-dash__card--activities">
                    <div class="group-report-dash__card-label">Занятость во внеурочное время</div>
                    <div class="group-report-dash__split">
                        <div>
                            <div class="group-report-dash__split-value"><?= (int) ($activitiesBlock['with_activities'] ?? 0) ?></div>
                            <div class="group-report-dash__split-label">заняты</div>
                        </div>
                        <div>
                            <div class="group-report-dash__split-value"><?= (int) ($activitiesBlock['without_activities'] ?? 0) ?></div>
                            <div class="group-report-dash__split-label">не заняты</div>
                        </div>
                    </div>
                    <div class="group-report-dash__bar" aria-hidden="true">
                        <span class="group-report-dash__bar-busy" style="width: <?= (float) ($activitiesBlock['pct_with'] ?? 0) ?>%"></span>
                        <span class="group-report-dash__bar-free" style="width: <?= (float) ($activitiesBlock['pct_without'] ?? 0) ?>%"></span>
                    </div>
                    <div class="group-report-dash__meta">
                        кружков <?= (int) ($activitiesBlock['club_count'] ?? 0) ?>
                        · секций <?= (int) ($activitiesBlock['section_count'] ?? 0) ?>
                        · охват <?= e(format_group_report_number((float) ($activitiesBlock['pct_with'] ?? 0))) ?>%
                    </div>
                </div>

                <div class="group-report-dash__card">
                    <div class="group-report-dash__card-label">Семья и проживание</div>
                    <div class="group-report-dash__chips">
                        <span class="group-report-dash__chip">Полные: <?= (int) $report['family_complete'] ?></span>
                        <span class="group-report-dash__chip">Неполные: <?= (int) $report['family_incomplete'] ?></span>
                        <span class="group-report-dash__chip">Малообеспеч.: <?= (int) $report['low_income'] ?></span>
                        <span class="group-report-dash__chip">Общежитие: <?= (int) $report['dormitory'] ?></span>
                    </div>
                </div>

                <div class="group-report-dash__card">
                    <div class="group-report-dash__card-label">Академ. задолженность</div>
                    <div class="group-report-dash__big"><?= (int) ($report['debtors_count'] ?? 0) ?></div>
                    <div class="group-report-dash__meta">
                        студентов · задолженностей <?= (int) ($report['debts_count'] ?? 0) ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $attentionStudents = $report['attention_students'] ?? [];
        ?>
        <div class="group-report-dash__attention">
            <div class="group-report-dash__attention-head">
                <div>
                    <div class="group-report-dash__card-label">Студенты, требующие внимания</div>
                    <p class="group-report-dash__attention-hint">
                        Топ-3 по задолженностям, пропускам без уважительной причины и взысканиям
                    </p>
                </div>
            </div>
            <?php if ($attentionStudents === []): ?>
                <p class="group-report-dash__attention-empty">
                    Сейчас нет студентов с выраженными рисками по выбранным критериям.
                </p>
            <?php else: ?>
                <ol class="group-report-dash__attention-list">
                    <?php foreach ($attentionStudents as $index => $item): ?>
                    <li class="group-report-dash__attention-item">
                        <div class="group-report-dash__attention-rank"><?= $index + 1 ?></div>
                        <div class="group-report-dash__attention-body">
                            <div class="group-report-dash__attention-name"><?= e($item['full_name']) ?></div>
                            <div class="group-report-dash__attention-tags">
                                <?php if ((int) ($item['debts'] ?? 0) > 0): ?>
                                <span class="group-report-dash__chip group-report-dash__chip--warn">
                                    задолженности: <?= (int) $item['debts'] ?>
                                </span>
                                <?php endif; ?>
                                <?php if ((int) ($item['unexcused'] ?? 0) > 0): ?>
                                <span class="group-report-dash__chip group-report-dash__chip--danger">
                                    пропуски: <?= (int) $item['unexcused'] ?>
                                </span>
                                <?php endif; ?>
                                <?php if ((int) ($item['sanctions'] ?? 0) > 0): ?>
                                <span class="group-report-dash__chip group-report-dash__chip--alert">
                                    взыскания: <?= (int) $item['sanctions'] ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>

        <hr class="divider group-report__divider">
        <h3 class="subsection-title">Подробная справка</h3>

        <div class="admin-stats-grid group-report__stats">
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) $report['total'] ?></div>
                <div class="admin-stat-card__label">Студентов</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) $report['male'] ?></div>
                <div class="admin-stat-card__label">Юношей</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) $report['female'] ?></div>
                <div class="admin-stat-card__label">Девушек</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= e(format_group_report_number($report['avg_age'])) ?></div>
                <div class="admin-stat-card__label">Средний возраст</div>
            </div>
        </div>

        <div class="admin-stats-columns group-report__columns">
            <div>
                <h3 class="subsection-title">Возраст</h3>
                <dl class="profile-list">
                    <dt>Младший студент</dt>
                    <dd><?= e(format_group_report_number($report['min_age'])) ?></dd>
                    <dt>Старший студент</dt>
                    <dd><?= e(format_group_report_number($report['max_age'])) ?></dd>
                    <dt>Несовершеннолетние</dt>
                    <dd><?= (int) $report['minor'] ?></dd>
                    <dt>Совершеннолетние</dt>
                    <dd><?= (int) $report['adult'] ?></dd>
                    <?php if ((int) $report['age_unknown'] > 0): ?>
                        <dt>Дата рождения не указана</dt>
                        <dd><?= (int) $report['age_unknown'] ?></dd>
                    <?php endif; ?>
                </dl>
            </div>

            <div>
                <h3 class="subsection-title">Семья</h3>
                <dl class="profile-list">
                    <dt>Полные семьи</dt>
                    <dd><?= (int) $report['family_complete'] ?></dd>
                    <dt>Неполные семьи</dt>
                    <dd><?= (int) $report['family_incomplete'] ?></dd>
                    <dt>из них без отца</dt>
                    <dd><?= (int) $report['family_no_father'] ?></dd>
                    <dt>из них без матери</dt>
                    <dd><?= (int) $report['family_no_mother'] ?></dd>
                    <dt>Многодетные семьи*</dt>
                    <dd><?= (int) $report['large_family'] ?></dd>
                    <dt>Малообеспеченные семьи</dt>
                    <dd><?= (int) $report['low_income'] ?></dd>
                    <dt>Без попечительства родителей</dt>
                    <dd><?= (int) $report['without_parental_care'] ?></dd>
                    <?php if ((int) $report['family_unknown'] > 0): ?>
                        <dt>Состав семьи не указан</dt>
                        <dd><?= (int) $report['family_unknown'] ?></dd>
                    <?php endif; ?>
                </dl>
                <p class="text-muted table-hint">* 2 и более брата/сестры младше 18 лет</p>
            </div>

            <div>
                <h3 class="subsection-title">Проживание</h3>
                <dl class="profile-list">
                    <dt>В общежитии</dt>
                    <dd><?= (int) $report['dormitory'] ?></dd>
                    <dt>Иногородние на квартирах</dt>
                    <dd><?= (int) $report['nonresident_apartment'] ?></dd>
                </dl>
            </div>
        </div>

        <h3 class="subsection-title">География студентов</h3>
        <?php if ($report['districts'] === []): ?>
            <p class="text-muted">Районы / населённые пункты пока не указаны в карточках.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Район / населённый пункт</th>
                            <th>Студентов</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['districts'] as $row): ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <td><?= (int) $row['count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ((int) $report['district_unknown'] > 0): ?>
                <p class="text-muted table-hint">
                    Без указания района: <?= (int) $report['district_unknown'] ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <h3 class="subsection-title">Успеваемость</h3>
        <?php if (!empty($report['academic_period_label'])): ?>
            <p class="text-muted">
                Период ведомости: <?= e($report['academic_period_label']) ?>.
            </p>
        <?php endif; ?>

        <div class="admin-stats-grid group-report__stats">
            <div class="admin-stat-card">
                <div class="admin-stat-card__value">
                    <?= e($report['absolute_percent'] !== null
                        ? format_group_report_number((float) $report['absolute_percent']) . '%'
                        : '—') ?>
                </div>
                <div class="admin-stat-card__label">Успеваемость</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) ($report['with_twos_count'] ?? 0) ?></div>
                <div class="admin-stat-card__label">Неуспевающих</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) ($report['only_good_count'] ?? 0) ?></div>
                <div class="admin-stat-card__label">Хорошистов</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) ($report['excellent_count'] ?? 0) ?></div>
                <div class="admin-stat-card__label">Отличников</div>
            </div>
        </div>

        <?php if (empty($report['academic_available']) && (int) ($report['assessed_students'] ?? 0) === 0): ?>
            <p class="text-muted">
                По текущему периоду ведомости оценок пока нет — категории успеваемости появятся после выставления итогов.
            </p>
        <?php else: ?>
            <div class="admin-stats-columns group-report__columns">
                <div>
                    <h3 class="subsection-title">Неуспевающие</h3>
                    <?php if (($report['with_twos'] ?? []) === []): ?>
                        <p class="text-muted">Нет</p>
                    <?php else: ?>
                        <ul class="group-report__list">
                            <?php foreach ($report['with_twos'] as $item): ?>
                            <li>
                                <?= e($item['full_name']) ?>
                                <?php if (!empty($item['subjects'])): ?>
                                    <span class="text-muted">
                                        — <?= e(implode(', ', $item['subjects'])) ?>
                                    </span>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div>
                    <h3 class="subsection-title">Хорошисты</h3>
                    <?php if (($report['only_good'] ?? []) === []): ?>
                        <p class="text-muted">Нет</p>
                    <?php else: ?>
                        <ul class="group-report__list">
                            <?php foreach ($report['only_good'] as $item): ?>
                            <li><?= e($item['full_name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div>
                    <h3 class="subsection-title">Отличники</h3>
                    <?php if (($report['excellent'] ?? []) === []): ?>
                        <p class="text-muted">Нет</p>
                    <?php else: ?>
                        <ul class="group-report__list">
                            <?php foreach ($report['excellent'] as $item): ?>
                            <li><?= e($item['full_name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <p class="text-muted table-hint">
                Успеваемость — доля студентов без оценок «2».
                Неуспевающие — есть «2»; хорошисты — все оценки не ниже «4» (но не все «5»);
                отличники — все оценки «5».
            </p>
        <?php endif; ?>

        <h3 class="subsection-title">Академическая задолженность</h3>
        <p class="text-muted">
            По итогам прошлых семестров (архивные ведомости):
            студентов с задолженностью — <?= (int) ($report['debtors_count'] ?? 0) ?>,
            задолженностей — <?= (int) ($report['debts_count'] ?? 0) ?>.
        </p>
        <?php if (($report['debts'] ?? []) === []): ?>
            <p class="text-muted">Академических задолженностей нет.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Студент</th>
                            <th>Предмет</th>
                            <th>Период</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['debts'] as $debt): ?>
                        <tr>
                            <td><?= e($debt['student_name']) ?></td>
                            <td><?= e($debt['subject_name']) ?></td>
                            <td><?= e($debt['period_label']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h3 class="subsection-title">Занятость во внеурочное время</h3>
        <?php if ($totalStudents === 0): ?>
            <p class="text-muted">Данных о занятости пока нет.</p>
        <?php else: ?>
            <div class="admin-stats-grid group-report__stats">
                <div class="admin-stat-card">
                    <div class="admin-stat-card__value"><?= (int) $activitiesBlock['with_activities'] ?></div>
                    <div class="admin-stat-card__label">Заняты</div>
                </div>
                <div class="admin-stat-card">
                    <div class="admin-stat-card__value"><?= (int) $activitiesBlock['without_activities'] ?></div>
                    <div class="admin-stat-card__label">Не заняты</div>
                </div>
                <div class="admin-stat-card">
                    <div class="admin-stat-card__value"><?= (int) $activitiesBlock['club_count'] ?></div>
                    <div class="admin-stat-card__label">Кружков</div>
                </div>
                <div class="admin-stat-card">
                    <div class="admin-stat-card__value"><?= (int) $activitiesBlock['section_count'] ?></div>
                    <div class="admin-stat-card__label">Секций</div>
                </div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Студент</th>
                            <th>Кружки и секции</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($activitiesBlock['rows'] ?? []) as $index => $row): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($row['full_name']) ?></td>
                            <td>
                                <?php if (($row['summary'] ?? '') === ''): ?>
                                    <span class="text-muted">Не указано</span>
                                <?php else: ?>
                                    <?= e($row['summary']) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted table-hint">
                Заполняется куратором во вкладке «Занятость студентов».
            </p>
        <?php endif; ?>

        <h3 class="subsection-title">Взыскания</h3>
        <?php if ($report['sanctions'] === []): ?>
            <p class="text-muted">
                Данных о взысканиях пока нет. Раздел будет заполняться воспитателем.
            </p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Студент</th>
                            <th>Взыскание</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['sanctions'] as $item): ?>
                        <tr>
                            <td><?= e($item['student_name'] ?? '') ?></td>
                            <td><?= e($item['label'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
