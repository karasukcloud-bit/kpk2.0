<?php

declare(strict_types=1);

/** @var array $list */
/** @var array $filter */
/** @var array $yearOptions */
/** @var string $viewBase */
/** @var bool $showRestored */
/** @var string $listQuery */
/** @var string|null $success */
/** @var string|null $error */

$dash = static fn ($v): string => ($v === null || $v === '') ? '—' : (string) $v;
$showAllPeriods = $filter['show_all_periods'];
$filterYear = $filter['academic_year'];
$filterSemester = $filter['semester'];
$restoredParams = $showRestored ? ['active_only' => '1'] : [];
if (!$showAllPeriods) {
    $restoredParams['academic_year'] = $filterYear;
    $restoredParams['semester'] = $filterSemester;
}
$restoredToggleUrl = 'expelled.php' . ($restoredParams !== [] ? ('?' . http_build_query($restoredParams)) : '');
?>
<?php if (!empty($success)): ?>
    <div class="alert alert--success"><?= e($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php endif; ?>

<section class="panel">
    <div class="panel__header">
        <h2>Список отчисленных</h2>
        <div class="form__inline-actions">
            <?php if ($showRestored): ?>
                <a href="<?= e($restoredToggleUrl) ?>" class="btn btn--ghost btn--sm">Только невосстановленные</a>
            <?php else: ?>
                <a href="<?= e($restoredToggleUrl) ?>" class="btn btn--ghost btn--sm">Показать всех</a>
            <?php endif; ?>
        </div>
    </div>

    <form method="get" class="form form--filter" data-expelled-period-filter>
        <?php if (!$showRestored): ?>
            <input type="hidden" name="active_only" value="1">
        <?php endif; ?>
        <div class="form__row form__row--filter">
            <div class="form__group">
                <label for="expelled_academic_year">Учебный год отчисления</label>
                <select id="expelled_academic_year" name="academic_year" data-expelled-year>
                    <option value=""<?= $showAllPeriods ? ' selected' : '' ?>>Все</option>
                    <?php foreach ($yearOptions as $year): ?>
                    <option value="<?= e($year) ?>"<?= !$showAllPeriods && $year === $filterYear ? ' selected' : '' ?>>
                        <?= e($year) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form__group">
                <label for="expelled_semester">Семестр</label>
                <select
                    id="expelled_semester"
                    name="semester"
                    data-expelled-semester
                    <?= $showAllPeriods ? 'disabled' : '' ?>
                >
                    <option value="1"<?= !$showAllPeriods && $filterSemester === '1' ? ' selected' : '' ?>>1 семестр</option>
                    <option value="2"<?= !$showAllPeriods && $filterSemester === '2' ? ' selected' : '' ?>>2 семестр</option>
                </select>
            </div>
            <div class="form__group form__group--align-end">
                <button type="submit" class="btn btn--primary">Показать</button>
            </div>
        </div>
    </form>

    <?php if ($list === []): ?>
        <p class="text-muted">Отчисленных студентов по выбранным условиям нет.</p>
    <?php else: ?>
        <p class="text-muted">
            Найдено: <strong><?= count($list) ?></strong>
            <?php if (!$showAllPeriods): ?>
                · <?= e($filterYear) ?>, <?= e($filterSemester) ?> семестр
            <?php endif; ?>
        </p>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>ФИО</th>
                        <th>Группа</th>
                        <th>Приказ</th>
                        <th>Дата отчисления</th>
                        <?php if ($showAllPeriods): ?>
                        <th>Период</th>
                        <?php endif; ?>
                        <th>Статус</th>
                        <th class="table__actions-col"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $index => $row): ?>
                    <tr class="table__row--clickable"
                        onclick="location.href='<?= e($viewBase) ?>?id=<?= (int) $row['id'] ?>'">
                        <td><?= $index + 1 ?></td>
                        <td><?= e($row['full_name']) ?></td>
                        <td><?= e($dash($row['group_number'] ?? '')) ?></td>
                        <td><?= e($dash($row['expulsion_order'] ?? '')) ?></td>
                        <td><?= e(date('d.m.Y', strtotime((string) $row['expulsion_date']))) ?></td>
                        <?php if ($showAllPeriods): ?>
                        <td><?= e(expelled_period_label($row)) ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ((int) $row['is_restored'] === 1): ?>
                                <span class="badge badge--role badge--educator">Восстановлен</span>
                            <?php else: ?>
                                <span class="badge badge--role badge--deputy">Отчислен</span>
                            <?php endif; ?>
                        </td>
                        <td class="table__actions" onclick="event.stopPropagation()">
                            <form
                                method="post"
                                class="form-inline"
                                onsubmit="return confirm('Удалить отчисленного студента и все связанные данные из системы?');"
                            >
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="expelled_id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="btn btn--danger btn--sm">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted table-hint">Нажмите на строку, чтобы открыть карточку.</p>
    <?php endif; ?>
</section>
<script>
(() => {
    const form = document.querySelector('[data-expelled-period-filter]');
    if (!form) return;
    const yearSelect = form.querySelector('[data-expelled-year]');
    const semesterSelect = form.querySelector('[data-expelled-semester]');
    if (!yearSelect || !semesterSelect) return;

    const syncSemesterState = () => {
        const allYears = yearSelect.value === '';
        semesterSelect.disabled = allYears;
        if (allYears) {
            semesterSelect.removeAttribute('name');
        } else {
            semesterSelect.setAttribute('name', 'semester');
        }
    };

    yearSelect.addEventListener('change', () => {
        syncSemesterState();
        form.submit();
    });

    syncSemesterState();
})();
</script>
