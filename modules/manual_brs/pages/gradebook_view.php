<?php

declare(strict_types=1);

/**
 * Общая ведомость ручного БРС (только итоговые оценки).
 * Ожидает: $manualBrsPanel = curator|deputy|admin
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/organization.php';
require_once __DIR__ . '/../../../includes/students.php';
require_once __DIR__ . '/../../../includes/gradebook.php';
require_once __DIR__ . '/../../../includes/curriculum.php';
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/gradebook.php';

manual_brs_require_gradebook_viewer();
manual_brs_ensure_schema();

$manualBrsPanel = $manualBrsPanel ?? 'deputy';
if (!in_array($manualBrsPanel, ['curator', 'deputy', 'admin'], true)) {
    $manualBrsPanel = 'deputy';
}

$period = get_active_gradebook_period();
$year = $period['academic_year'];
$semester = $period['semester'];
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

$groups = [];
$group = null;
$students = [];
$subjects = [];
$grades = [];
$summary = null;
$studentLists = [
    'with_twos' => [],
    'only_good' => [],
    'excellent' => [],
];
$error = null;

if ($manualBrsPanel === 'curator') {
    require_curator_panel();
    $ctx = resolve_curator_group_context($groupId > 0 ? $groupId : null);
    $groups = $ctx['groups'];
    $groupId = (int) $ctx['group_id'];
    $group = $ctx['group'];
    $curatorGroupId = $groupId;
    $curatorGroups = $groups;
    $currentCuratorTab = 'manual_brs_gradebook';
} else {
    if ($manualBrsPanel === 'admin') {
        require_admin();
        $currentAdminTab = 'manual_brs_gradebook';
    } else {
        if (!can_use_deputy_panel()) {
            http_response_code(403);
            exit('Доступ запрещён.');
        }
        $currentDeputyTab = 'manual_brs_gradebook';
    }
    $groups = get_all_groups();
    if ($groupId > 0) {
        $group = get_group_by_id($groupId);
        if ($group === null) {
            $error = 'Группа не найдена.';
            $groupId = 0;
        }
    }
}

$isControlWeek = false;
$sheetTitle = '';
$orgName = '';

if ($group !== null) {
    $students = get_students_by_group($groupId);
    $subjects = get_group_curriculum_subjects($groupId, $year, $semester);
    $grades = manual_brs_get_gradebook_grades($groupId, $year, $semester);
    $summary = build_gradebook_summary($students, $subjects, $grades);
    $studentLists = build_gradebook_student_lists($students, $subjects, $grades);
    $sheetTitle = manual_brs_gradebook_title((string) $group['number'], $semester, $year);
    $isControlWeek = $subjects !== []
        && manual_brs_is_control_week($groupId, $year, $semester, $subjects);
    $org = get_organization();
    $orgName = trim((string) ($org['name'] ?? ''));
}

$panelTitles = [
    'curator' => 'Панель куратора',
    'deputy' => 'Панель завуча',
    'admin' => 'Админ панель',
];
$pageTitle = 'Ведомость (ручное БРС) — ' . ($panelTitles[$manualBrsPanel] ?? 'СПО');
$showHeader = true;
$basePath = '../';
require __DIR__ . '/../../../includes/header.php';
?>

<link rel="stylesheet" href="<?= e(manual_brs_asset_url('manual_brs.css', $basePath)) ?>?v=20260913e">

<div class="dashboard dashboard--wide">
    <section class="panel manual-brs-no-print">
        <div class="panel__header">
            <div>
                <h1><?= e($panelTitles[$manualBrsPanel] ?? 'Ведомость') ?></h1>
                <p class="text-muted">Ведомость ручного БРС (временный модуль)</p>
            </div>
            <?php if ($manualBrsPanel !== 'curator' && $group !== null): ?>
                <a href="<?= e(manual_brs_gradebook_url($manualBrsPanel)) ?>" class="btn btn--ghost">← К группам</a>
            <?php endif; ?>
        </div>
        <?php if ($manualBrsPanel === 'curator'): ?>
            <?php require __DIR__ . '/../../../includes/curator_nav.php'; ?>
        <?php elseif ($manualBrsPanel === 'admin'): ?>
            <?php require __DIR__ . '/../../../includes/admin_nav.php'; ?>
        <?php else: ?>
            <?php require __DIR__ . '/../../../includes/deputy_nav.php'; ?>
        <?php endif; ?>
    </section>

    <?php if ($error): ?>
        <div class="alert alert--error manual-brs-no-print"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <p class="text-muted manual-brs-no-print">
            Оценки — итог семестра из модуля «Ручное БРС» (периоды + ПА). Только просмотр.
        </p>

        <?php if ($manualBrsPanel === 'curator' && empty($groups)): ?>
            <p class="text-muted">Вам ещё не назначена группа.</p>
        <?php elseif ($group === null): ?>
            <?php if ($manualBrsPanel === 'curator'): ?>
                <p class="text-muted">Выберите группу, чтобы открыть ведомость ручного БРС.</p>
            <?php else: ?>
                <h2>Выберите группу</h2>
                <?php if ($groups === []): ?>
                    <p class="text-muted">Группы пока не добавлены.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>№</th>
                                    <th>Группа</th>
                                    <th>Курс</th>
                                    <th>Специальность</th>
                                    <th class="table__actions-col">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groups as $index => $gRow): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= e($gRow['number']) ?></strong></td>
                                    <td><?= (int) get_group_course($gRow) ?></td>
                                    <td><?= e($gRow['specialty_name'] ?? '') ?></td>
                                    <td class="table__actions">
                                        <a
                                            href="<?= e(manual_brs_gradebook_url($manualBrsPanel, [
                                                'group_id' => (int) $gRow['id'],
                                            ])) ?>"
                                            class="btn btn--primary btn--sm"
                                        >Открыть</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php elseif ($subjects === []): ?>
            <div class="manual-brs-gradebook-print-area">
                <h2 class="manual-brs-gradebook-title"><?= e($sheetTitle) ?></h2>
                <p class="text-muted">
                    Для этого периода нет предметов в учебном плане группы.
                </p>
            </div>
        <?php else: ?>
            <div class="panel__header panel__header--compact manual-brs-no-print">
                <div></div>
                <div class="panel__header-actions">
                    <a
                        href="<?= e(manual_brs_gradebook_pdf_url($manualBrsPanel, $groupId)) ?>"
                        class="btn btn--primary btn--sm"
                    >Экспорт в PDF</a>
                    <button type="button" class="btn btn--ghost btn--sm" data-manual-brs-print>Печать</button>
                </div>
            </div>

            <div class="manual-brs-gradebook-print-area">
                <header class="manual-brs-gradebook-header">
                    <?php if ($orgName !== ''): ?>
                        <p class="manual-brs-gradebook-header__meta"><?= e($orgName) ?></p>
                    <?php endif; ?>
                    <h2 class="manual-brs-gradebook-title"><?= e($sheetTitle) ?></h2>
                    <?php if ($isControlWeek): ?>
                        <p class="manual-brs-gradebook-subtitle">Контрольная неделя</p>
                    <?php endif; ?>
                    <?php if (!empty($group['specialty_name'])): ?>
                        <p class="manual-brs-gradebook-header__meta"><?= e((string) $group['specialty_name']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($group['curator_name'])): ?>
                        <p class="manual-brs-gradebook-header__meta">Куратор: <?= e((string) $group['curator_name']) ?></p>
                    <?php endif; ?>

                    <?php if (is_array($summary)): ?>
                    <table class="manual-brs-gradebook-stats">
                        <tr>
                            <td>
                                <span class="manual-brs-gradebook-stats__label">Выставлено оценок</span>
                                <span class="manual-brs-gradebook-stats__value">
                                    <?= (int) ($summary['filled_grades'] ?? 0) ?> из <?= (int) ($summary['expected_grades'] ?? 0) ?>
                                </span>
                            </td>
                            <td>
                                <span class="manual-brs-gradebook-stats__label">Абсолютная успеваемость</span>
                                <span class="manual-brs-gradebook-stats__value"><?= e((string) $summary['absolute_percent']) ?>%</span>
                                <span class="manual-brs-gradebook-stats__label">
                                    (<?= (int) ($summary['absolute_count'] ?? 0) ?> из <?= (int) $summary['assessed_students'] ?>)
                                </span>
                            </td>
                            <td>
                                <span class="manual-brs-gradebook-stats__label">Качественная успеваемость</span>
                                <span class="manual-brs-gradebook-stats__value"><?= e((string) $summary['quality_percent']) ?>%</span>
                                <span class="manual-brs-gradebook-stats__label">
                                    (<?= (int) ($summary['quality_count'] ?? 0) ?> из <?= (int) $summary['assessed_students'] ?>)
                                </span>
                            </td>
                        </tr>
                    </table>
                    <?php endif; ?>
                </header>

                <div class="table-wrap">
                    <table class="table gradebook-table gradebook-table--readonly">
                        <thead>
                            <tr>
                                <th class="gradebook-table__student-col">Студенты</th>
                                <?php foreach ($subjects as $subject): ?>
                                <th class="gradebook-table__subject-col">
                                    <span class="gradebook-subject-title"><?= e($subject['subject_name']) ?></span>
                                </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td class="gradebook-table__student-col">
                                    <?= e(person_last_first_name((string) $student['full_name'])) ?>
                                </td>
                                <?php foreach ($subjects as $subject): ?>
                                <?php
                                $studentId = (int) $student['id'];
                                $itemId = (int) $subject['curriculum_item_id'];
                                $value = $grades[$studentId][$itemId] ?? null;
                                ?>
                                <td class="gradebook-table__cell gradebook-table__grade-cell">
                                    <?= $value !== null ? e((string) $value) : '—' ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="text-muted table-hint manual-brs-no-print">
                    Источник: модуль «Ручное БРС». Обычная электронная ведомость и журнал не изменяются.
                </p>

                <div class="gradebook-lists">
                    <h2>Списки студентов</h2>
                    <div class="gradebook-lists__grid">
                        <div class="gradebook-lists__col">
                            <h3>Оценка «2»</h3>
                            <div>
                                <?php if ($studentLists['with_twos'] === []): ?>
                                    <p class="text-muted">Нет студентов</p>
                                <?php else: ?>
                                    <ul class="gradebook-lists__items">
                                        <?php foreach ($studentLists['with_twos'] as $item): ?>
                                        <li class="gradebook-lists__item">
                                            <strong><?= e(person_last_first_name((string) $item['full_name'])) ?></strong>
                                            <span class="gradebook-lists__subjects text-muted"><?= e(implode(', ', $item['subjects'])) ?></span>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="gradebook-lists__col">
                            <h3>Только оценки 4–5</h3>
                            <div>
                                <?php if ($studentLists['only_good'] === []): ?>
                                    <p class="text-muted">Нет студентов</p>
                                <?php else: ?>
                                    <ul class="gradebook-lists__items">
                                        <?php foreach ($studentLists['only_good'] as $item): ?>
                                        <li class="gradebook-lists__item">
                                            <strong><?= e(person_last_first_name((string) $item['full_name'])) ?></strong>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="gradebook-lists__col">
                            <h3>Отличники (все «5»)</h3>
                            <div>
                                <?php if ($studentLists['excellent'] === []): ?>
                                    <p class="text-muted">Нет студентов</p>
                                <?php else: ?>
                                    <ul class="gradebook-lists__items">
                                        <?php foreach ($studentLists['excellent'] as $item): ?>
                                        <li class="gradebook-lists__item">
                                            <strong><?= e(person_last_first_name((string) $item['full_name'])) ?></strong>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($group !== null && $subjects !== [] && is_array($summary)): ?>
    <section class="panel panel--info manual-brs-no-print">
        <h2>Сводная информация</h2>
        <dl class="profile-list">
            <dt>Выставлено оценок</dt>
            <dd><?= (int) ($summary['filled_grades'] ?? 0) ?> из <?= (int) ($summary['expected_grades'] ?? 0) ?></dd>
            <dt>Успеваемость абсолютная</dt>
            <dd><?= e((string) $summary['absolute_percent']) ?>%</dd>
            <dt>Успеваемость качественная</dt>
            <dd><?= e((string) $summary['quality_percent']) ?>%</dd>
        </dl>
        <p class="text-muted">
            Абсолютная успеваемость: доля студентов без оценок «2».
            Качественная успеваемость: доля студентов, у которых все выставленные оценки не ниже «4».
        </p>
    </section>
    <?php endif; ?>
</div>

<?php if ($group !== null && $subjects !== []): ?>
<script>
document.querySelectorAll('[data-manual-brs-print]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../../../includes/footer.php'; ?>
