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

    <?php if (($report['total'] ?? 0) === 0): ?>
        <p class="text-muted">В группе нет студентов — справка пуста.</p>
    <?php else: ?>
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
