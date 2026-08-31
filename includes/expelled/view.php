<?php

declare(strict_types=1);

/** @var array $expelled */
/** @var array $periods */
/** @var array $debts */
/** @var array|null $restoration */
/** @var array $groups */
/** @var string $tab */
/** @var string $viewUrl */
/** @var string|null $success */
/** @var string|null $error */

$dash = static fn ($v): string => ($v === null || trim((string) $v) === '') ? '—' : (string) $v;
$courseworks = $courseworks ?? [];
$practices = $practices ?? [];
$giaEntries = $giaEntries ?? [];
$selectedKey = (string) ($_GET['period'] ?? '');
$isCourseworks = $selectedKey === 'courseworks';
$isPractices = $selectedKey === 'practices';
$isGia = $selectedKey === 'gia';
$isSpecialSection = $isCourseworks || $isPractices || $isGia;
$selectedPeriod = null;

if (!$isSpecialSection) {
    foreach ($periods as $period) {
        $key = $period['academic_year'] . '|' . $period['semester'];
        if ($selectedKey === $key) {
            $selectedPeriod = $period;
            break;
        }
    }
    if ($selectedPeriod === null && $periods !== []) {
        $selectedPeriod = $periods[0];
        $selectedKey = $selectedPeriod['academic_year'] . '|' . $selectedPeriod['semester'];
    } elseif ($selectedPeriod === null && $periods === []) {
        $isCourseworks = true;
        $isSpecialSection = true;
        $selectedKey = 'courseworks';
    }
}

$rbSummary = (!$isSpecialSection && $selectedPeriod)
    ? summarize_record_book_period($selectedPeriod['entries'])
    : null;
$canRestore = (int) $expelled['is_restored'] === 0;
?>
<?php if (!empty($success)): ?>
    <div class="alert alert--success"><?= e($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php endif; ?>

<section class="panel">
    <nav class="admin-tabs">
        <a href="<?= e($viewUrl) ?>&tab=info"
           class="admin-tabs__item<?= $tab === 'info' ? ' admin-tabs__item--active' : '' ?>">Информация</a>
        <a href="<?= e($viewUrl) ?>&tab=record_book"
           class="admin-tabs__item<?= $tab === 'record_book' ? ' admin-tabs__item--active' : '' ?>">Зачётная книжка</a>
        <a href="<?= e($viewUrl) ?>&tab=debts"
           class="admin-tabs__item<?= $tab === 'debts' ? ' admin-tabs__item--active' : '' ?>">Академическая задолженность</a>
    </nav>

    <?php if ($tab === 'info'): ?>
        <h2 class="subsection-title">Сведения о студенте</h2>
        <dl class="profile-list">
            <dt>ФИО</dt>
            <dd><?= e($expelled['full_name']) ?></dd>
            <dt>Телефон</dt>
            <dd><?= e($dash($expelled['phone'] ?? '')) ?></dd>
            <dt>Дата рождения</dt>
            <dd><?= e(format_student_birth_date(isset($expelled['birth_date']) ? (string) $expelled['birth_date'] : null)) ?></dd>
            <dt>Пол</dt>
            <dd><?= e(student_gender_label(isset($expelled['gender']) ? (string) $expelled['gender'] : null)) ?></dd>
            <dt>Группа на момент отчисления</dt>
            <dd><?= e($dash($expelled['group_number'] ?? '')) ?>
                <?php if (($expelled['specialty_name'] ?? '') !== ''): ?>
                    · <?= e($expelled['specialty_name']) ?>
                    <?php if (($expelled['specialty_code'] ?? '') !== ''): ?>
                        (<?= e($expelled['specialty_code']) ?>)
                    <?php endif; ?>
                <?php endif; ?>
            </dd>
            <dt><?= e(student_parent_field_label('mother', 'name')) ?></dt>
            <dd><?= e($dash($expelled['mother_name'] ?? '')) ?></dd>
            <dt><?= e(student_parent_field_label('mother', 'phone')) ?></dt>
            <dd><?= e($dash($expelled['mother_phone'] ?? '')) ?></dd>
            <dt><?= e(student_parent_field_label('mother', 'workplace')) ?></dt>
            <dd><?= e($dash($expelled['mother_workplace'] ?? '')) ?></dd>
            <dt><?= e(student_parent_field_label('mother', 'education')) ?></dt>
            <dd><?= e(student_education_label($expelled['mother_education'] ?? null)) ?></dd>
            <dt><?= e(student_parent_field_label('father', 'name')) ?></dt>
            <dd><?= e($dash($expelled['father_name'] ?? '')) ?></dd>
            <dt><?= e(student_parent_field_label('father', 'phone')) ?></dt>
            <dd><?= e($dash($expelled['father_phone'] ?? '')) ?></dd>
            <dt><?= e(student_parent_field_label('father', 'workplace')) ?></dt>
            <dd><?= e($dash($expelled['father_workplace'] ?? '')) ?></dd>
            <dt><?= e(student_parent_field_label('father', 'education')) ?></dt>
            <dd><?= e(student_education_label($expelled['father_education'] ?? null)) ?></dd>
            <dt>Адрес по прописке</dt>
            <dd><?= e(format_student_registered_address($expelled)) ?></dd>
            <dt>Область / край</dt>
            <dd><?= e($dash($expelled['address_region'] ?? '')) ?></dd>
            <dt>Район / округ</dt>
            <dd><?= e($dash($expelled['address_district'] ?? '')) ?></dd>
            <dt>Населённый пункт</dt>
            <dd><?= e($dash($expelled['address_locality'] ?? '')) ?></dd>
            <dt>Улица</dt>
            <dd><?= e($dash($expelled['address_street'] ?? '')) ?></dd>
            <dt>Дом</dt>
            <dd><?= e($dash($expelled['address_house'] ?? '')) ?></dd>
            <dt>Фактический адрес</dt>
            <dd><?= e($dash($expelled['address_actual'] ?? '')) ?></dd>
            <dt>Состав семьи</dt>
            <dd><?= e(student_family_type_label(isset($expelled['family_type']) ? (string) $expelled['family_type'] : null)) ?></dd>
            <dt>Братьев/сестёр младше 18</dt>
            <dd><?= e((string) (int) ($expelled['siblings_under_18'] ?? 0)) ?></dd>
            <dt>Место проживания</dt>
            <dd><?= e(student_residence_type_label(isset($expelled['residence_type']) ? (string) $expelled['residence_type'] : null)) ?></dd>
            <dt>Иногородний</dt>
            <dd><?= !empty($expelled['is_nonresident']) ? 'Да' : 'Нет' ?></dd>
            <dt>Малообеспеченная семья</dt>
            <dd><?= !empty($expelled['is_low_income']) ? 'Да' : 'Нет' ?></dd>
            <dt>Без попечительства родителей</dt>
            <dd><?= !empty($expelled['without_parental_care']) ? 'Да' : 'Нет' ?></dd>
        </dl>

        <h2 class="subsection-title">Отчисление</h2>
        <dl class="profile-list">
            <dt>Номер приказа</dt>
            <dd><?= e($expelled['expulsion_order']) ?></dd>
            <dt>Дата отчисления</dt>
            <dd><?= e(date('d.m.Y', strtotime((string) $expelled['expulsion_date']))) ?></dd>
            <dt>Причина</dt>
            <dd><?= e($expelled['expulsion_reason']) ?></dd>
            <dt>Отчислил</dt>
            <dd><?= e($dash($expelled['expelled_by_name'] ?? '')) ?></dd>
        </dl>

        <?php if ($restoration): ?>
        <h2 class="subsection-title">Восстановление</h2>
        <dl class="profile-list">
            <dt>Дата восстановления</dt>
            <dd><?= e(date('d.m.Y', strtotime((string) $restoration['restore_date']))) ?></dd>
            <dt>Группа</dt>
            <dd><?= e($restoration['group_number']) ?></dd>
            <dt>Дополнительно</dt>
            <dd><?= e($dash($restoration['additional_info'] ?? '')) ?></dd>
            <dt>Восстановил</dt>
            <dd><?= e($dash($restoration['restored_by_name'] ?? '')) ?></dd>
        </dl>
        <?php elseif ($canRestore): ?>
        <div class="form__actions">
            <button type="button" class="btn btn--primary" data-expelled-restore-open>Восстановить</button>
        </div>
        <?php endif; ?>

    <?php elseif ($tab === 'record_book'): ?>
        <h2 class="subsection-title">Электронная зачётная книжка</h2>
        <div class="record-book">
            <aside class="record-book__periods">
                <h3 class="record-book__aside-title">Разделы</h3>
                <div class="record-book__period-list">
                    <?php foreach ($periods as $period): ?>
                    <?php
                    $key = $period['academic_year'] . '|' . $period['semester'];
                    $isActive = !$isSpecialSection && $key === $selectedKey;
                    $periodSummary = summarize_record_book_period($period['entries']);
                    ?>
                    <a href="<?= e($viewUrl) ?>&tab=record_book&period=<?= e(urlencode($key)) ?>"
                       class="record-book__period-card<?= $isActive ? ' record-book__period-card--active' : '' ?>">
                        <span class="record-book__period-year"><?= e($period['academic_year']) ?></span>
                        <span class="record-book__period-sem"><?= e(semester_label($period['semester'])) ?></span>
                        <span class="record-book__period-meta"><?= (int) $periodSummary['graded'] ?> оценок</span>
                    </a>
                    <?php endforeach; ?>
                    <a href="<?= e($viewUrl) ?>&tab=record_book&period=courseworks"
                       class="record-book__period-card<?= $isCourseworks ? ' record-book__period-card--active' : '' ?>">
                        <span class="record-book__period-year">Курсовые работы</span>
                        <span class="record-book__period-meta"><?= count($courseworks) ?> записей</span>
                    </a>
                    <a href="<?= e($viewUrl) ?>&tab=record_book&period=practices"
                       class="record-book__period-card<?= $isPractices ? ' record-book__period-card--active' : '' ?>">
                        <span class="record-book__period-year">Практики</span>
                        <span class="record-book__period-meta"><?= count($practices) ?> записей</span>
                    </a>
                    <a href="<?= e($viewUrl) ?>&tab=record_book&period=gia"
                       class="record-book__period-card<?= $isGia ? ' record-book__period-card--active' : '' ?>">
                        <span class="record-book__period-year">Государственная итоговая аттестация</span>
                        <span class="record-book__period-meta"><?= count($giaEntries) ?> записей</span>
                    </a>
                </div>
            </aside>
            <div class="record-book__sheet">
                <?php if ($isCourseworks): ?>
                    <?php
                    $canEditCourseworks = false;
                    require __DIR__ . '/../record_book_courseworks.php';
                    ?>
                <?php elseif ($isPractices): ?>
                    <?php
                    $canEditPractices = false;
                    require __DIR__ . '/../record_book_practices.php';
                    ?>
                <?php elseif ($isGia): ?>
                    <?php
                    $canEditGia = false;
                    require __DIR__ . '/../record_book_gia.php';
                    ?>
                <?php elseif ($selectedPeriod && $rbSummary): ?>
                    <div class="record-book__sheet-head">
                        <h3><?= e($selectedPeriod['academic_year']) ?> · <?= e(semester_label($selectedPeriod['semester'])) ?></h3>
                        <div class="record-book__stats">
                            <div class="record-book__stat">
                                <span class="record-book__stat-value"><?= (int) $rbSummary['graded'] ?></span>
                                <span class="record-book__stat-label">оценок</span>
                            </div>
                            <div class="record-book__stat">
                                <span class="record-book__stat-value">
                                    <?= $rbSummary['average'] !== null ? e((string) $rbSummary['average']) : '—' ?>
                                </span>
                                <span class="record-book__stat-label">средний балл</span>
                            </div>
                        </div>
                    </div>
                    <?php
                    $entries = $selectedPeriod['entries'];
                    require __DIR__ . '/../record_book_grades.php';
                    ?>
                <?php else: ?>
                    <p class="text-muted">Данных зачётной книжки нет.</p>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <h2 class="subsection-title">Академическая задолженность</h2>
        <?php if ($debts === []): ?>
            <p class="text-muted">Академических задолженностей нет.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Предмет</th>
                            <th>Период</th>
                            <th>Группа</th>
                            <th>Дата ликвидации</th>
                            <th>Время</th>
                            <th>Комиссия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($debts as $debt): ?>
                        <tr>
                            <td><?= e($debt['subject_name']) ?></td>
                            <td><?= e($debt['period_label']) ?></td>
                            <td><?= e($dash($debt['group_number'])) ?></td>
                            <td><?= e($debt['liquidation_date'] !== '' ? date('d.m.Y', strtotime($debt['liquidation_date'])) : '—') ?></td>
                            <td><?= e($debt['liquidation_time'] !== '' ? $debt['liquidation_time'] : '—') ?></td>
                            <td><?= e($dash($debt['commission_label'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php if ($canRestore): ?>
<div class="modal" id="expelled-restore-modal" hidden>
    <div class="modal__backdrop" data-expelled-restore-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="expelled-restore-title">
        <div class="modal__header">
            <h2 id="expelled-restore-title">Восстановление студента</h2>
            <button type="button" class="modal__close" data-expelled-restore-close aria-label="Закрыть">×</button>
        </div>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="restore">
            <div class="form__group">
                <label for="restore_date">Дата восстановления</label>
                <input type="date" id="restore_date" name="restore_date" required value="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="form__group">
                <label for="restore_group_id">Группа</label>
                <select id="restore_group_id" name="group_id" required>
                    <option value="">— Выберите группу —</option>
                    <?php foreach ($groups as $group): ?>
                    <option value="<?= (int) $group['id'] ?>">
                        <?= e($group['number']) ?> · <?= e($group['specialty_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form__group">
                <label for="additional_info">Дополнительная информация</label>
                <textarea id="additional_info" name="additional_info" rows="3" placeholder="Необязательно"></textarea>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Восстановить</button>
                <button type="button" class="btn btn--ghost" data-expelled-restore-close>Отмена</button>
            </div>
        </form>
    </div>
</div>
<script>
(() => {
    const modal = document.getElementById('expelled-restore-modal');
    if (!modal) return;
    const open = () => { modal.hidden = false; document.body.classList.add('modal-open'); };
    const close = () => { modal.hidden = true; document.body.classList.remove('modal-open'); };
    document.querySelectorAll('[data-expelled-restore-open]').forEach((btn) => btn.addEventListener('click', open));
    document.querySelectorAll('[data-expelled-restore-close]').forEach((btn) => btn.addEventListener('click', close));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) close(); });
    <?php if ($error && ($_SERVER['REQUEST_METHOD'] === 'POST') && (($_POST['action'] ?? '') === 'restore')): ?>
    open();
    <?php endif; ?>
})();
</script>
<?php endif; ?>
