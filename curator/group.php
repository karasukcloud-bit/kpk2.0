<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/curriculum.php';
require_once __DIR__ . '/../includes/group_report.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/attendance.php';

require_curator_panel();

$ctx = resolve_curator_group_context(isset($_GET['group_id']) ? (int) $_GET['group_id'] : null);
$groups = $ctx['groups'];
$groupId = $ctx['group_id'];
$group = $ctx['group'];
$students = $group ? get_students_by_group($groupId) : [];
$error = flash_get('error') ?: $ctx['error'];
$success = flash_get('success');

$studentsTotal = count($students);
$avgGrade = null;
$attendancePercent = null;
$debtorsCount = 0;
$academicYear = get_default_academic_year();

if ($group !== null && $students !== []) {
    $report = build_group_report($students, $groupId);
    $debtorsCount = (int) ($report['debtors_count'] ?? 0);

    try {
        $period = get_active_gradebook_period();
        $year = (string) ($period['academic_year'] ?? '');
        $semester = (string) ($period['semester'] ?? '1');
        if ($year !== '') {
            $academicYear = $year;
        }

        if ($year !== '' && is_manual_brs_gradebook_active()) {
            $overview = get_group_gradebook_overview($groupId, $year, $semester, 'manual_brs');
            $summary = $overview['summary'] ?? [];
            if ((int) ($summary['assessed_students'] ?? 0) > 0) {
                $avgGrade = round((float) ($summary['average_grade'] ?? 0), 1);
            }
        }

        if ($year !== '') {
            $attendancePercent = compute_group_average_attendance_percent($groupId, $year);
        }
    } catch (Throwable $e) {
        // оставляем «—»
    }
}

$pageTitle = 'Список группы — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'group';
$curatorGroupId = $groupId;
$curatorGroups = $groups;
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide curator-group-page">
    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
        <section class="panel">
            <p class="text-muted">
                Вам ещё не назначена группа. Обратитесь к администратору:
                в разделе «Информация» у группы нужно указать куратора.
            </p>
        </section>
    <?php else: ?>
        <section class="curator-group-hero">
            <div class="curator-group-hero__top">
                <div class="curator-group-hero__title-block">
                    <span class="curator-group-hero__title-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </span>
                    <div>
                        <h1 class="curator-group-hero__title">
                            <span class="curator-group-hero__title-desktop">Список студентов</span>
                            <span class="curator-group-hero__title-mobile">Панель куратора</span>
                        </h1>
                        <p class="curator-group-hero__subtitle">
                            <?php if ($group): ?>
                                Группа <?= e($group['number']) ?>
                            <?php else: ?>
                                Выберите группу
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="curator-group-hero__controls">
                    <?php if (count($groups) > 1): ?>
                    <form method="get" class="curator-group-pill-form curator-group-pill-form--desktop">
                        <label class="curator-group-pill curator-group-pill--select" title="Группа">
                            <span class="curator-group-pill__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <path d="M9 22V12h6v10"/>
                                </svg>
                            </span>
                            <select name="group_id" class="curator-group-pill__select" onchange="this.form.submit()" aria-label="Группа">
                                <?php foreach ($groups as $item): ?>
                                <option value="<?= (int) $item['id'] ?>"<?= (int) $item['id'] === $groupId ? ' selected' : '' ?>>
                                    <?= e($item['number']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </form>
                    <button
                        type="button"
                        class="curator-group-pill curator-group-pill--sheet-trigger"
                        data-group-sheet-open
                        aria-haspopup="dialog"
                        title="Выбрать группу"
                    >
                        <span class="curator-group-pill__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <path d="M9 22V12h6v10"/>
                            </svg>
                        </span>
                        <span class="curator-group-pill__text"><?= e($group['number'] ?? 'Группа') ?></span>
                        <span class="curator-group-pill__chevron" aria-hidden="true">▾</span>
                    </button>
                    <?php elseif ($group): ?>
                    <div class="curator-group-pill" title="Группа">
                        <span class="curator-group-pill__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <path d="M9 22V12h6v10"/>
                            </svg>
                        </span>
                        <span class="curator-group-pill__text"><?= e($group['number']) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="curator-group-pill" title="Учебный год">
                        <span class="curator-group-pill__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                <path d="M16 2v4M8 2v4M3 10h18"/>
                            </svg>
                        </span>
                        <span class="curator-group-pill__text"><?= e($academicYear) ?></span>
                    </div>

                    <?php if ($group && $students !== []): ?>
                    <label class="curator-group-search">
                        <span class="curator-group-search__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="7"/>
                                <path d="m20 20-3-3"/>
                            </svg>
                        </span>
                        <input
                            type="search"
                            class="curator-group-search__input"
                            placeholder="Поиск по ФИО..."
                            data-curator-group-search
                            autocomplete="off"
                        >
                    </label>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($group): ?>
            <div class="curator-group-stats" aria-label="Сводка по группе">
                <article class="curator-group-stat curator-group-stat--total">
                    <div class="curator-group-stat__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="curator-group-stat__body">
                        <div class="curator-group-stat__value"><?= $studentsTotal ?></div>
                        <div class="curator-group-stat__label">Всего студентов</div>
                    </div>
                </article>

                <article class="curator-group-stat curator-group-stat--grades">
                    <div class="curator-group-stat__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 15 9 22 9 17 14 19 21 12 17 5 21 7 14 2 9 9 9 12 2"/>
                        </svg>
                    </div>
                    <div class="curator-group-stat__body">
                        <div class="curator-group-stat__value-row">
                            <span class="curator-group-stat__value">
                                <?= $avgGrade !== null ? e(number_format($avgGrade, 1, '.', '')) : '—' ?>
                            </span>
                        </div>
                        <div class="curator-group-stat__label">Средняя успеваемость</div>
                    </div>
                </article>

                <article class="curator-group-stat curator-group-stat--attendance">
                    <div class="curator-group-stat__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="m8 12 3 3 5-6"/>
                        </svg>
                    </div>
                    <div class="curator-group-stat__body">
                        <div class="curator-group-stat__value-row">
                            <span class="curator-group-stat__value">
                                <?= $attendancePercent !== null ? e((string) $attendancePercent) . '%' : '—' ?>
                            </span>
                        </div>
                        <div class="curator-group-stat__label">Средняя посещаемость</div>
                    </div>
                </article>

                <a
                    href="report.php?group_id=<?= $groupId ?>"
                    class="curator-group-stat curator-group-stat--debts<?= $debtorsCount > 0 ? ' is-alert' : '' ?>"
                    title="Открыть справку по группе"
                >
                    <div class="curator-group-stat__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 8v4M12 16h.01"/>
                        </svg>
                    </div>
                    <div class="curator-group-stat__body">
                        <div class="curator-group-stat__value"><?= $debtorsCount ?></div>
                        <div class="curator-group-stat__label">Студенты с задолженностями</div>
                    </div>
                    <span class="curator-group-stat__chevron" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </span>
                </a>
            </div>

            <div class="curator-mobile-quick" aria-label="Быстрые действия">
                <a class="curator-mobile-quick__item" href="attendance.php?group_id=<?= $groupId ?>">
                    <span class="curator-mobile-quick__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
                    </span>
                    <span>Посещаемость</span>
                </a>
                <a class="curator-mobile-quick__item" href="grades.php?group_id=<?= $groupId ?>">
                    <span class="curator-mobile-quick__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
                    </span>
                    <span>Ведомость</span>
                </a>
                <a class="curator-mobile-quick__item" href="report.php?group_id=<?= $groupId ?>">
                    <span class="curator-mobile-quick__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 18V14M12 18v-6M16 18v-3"/></svg>
                    </span>
                    <span>О группе</span>
                </a>
                <a class="curator-mobile-quick__item" href="monthly_report.php?group_id=<?= $groupId ?>">
                    <span class="curator-mobile-quick__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/></svg>
                    </span>
                    <span>Отчёты</span>
                </a>
            </div>
            <?php endif; ?>
        </section>

        <?php if ($group === null): ?>
            <section class="panel">
                <p class="text-muted">Выберите группу, чтобы увидеть список студентов.</p>
            </section>
        <?php else: ?>
            <section class="panel curator-group-page__table-panel" id="students">
                <div class="curator-group-page__table-head">
                    <div>
                        <h2 class="curator-group-page__table-title">Студенты группы</h2>
                        <p class="text-muted curator-group-page__table-meta">
                            <?= e($group['specialty_name']) ?> (<?= e($group['specialty_code']) ?>)
                        </p>
                    </div>
                    <div class="panel__header-actions">
                        <?php if (!empty($students)): ?>
                        <a href="group_export_word.php?group_id=<?= $groupId ?>" class="btn btn--ghost">Экспорт в Word</a>
                        <?php endif; ?>
                        <a href="students_import.php?group_id=<?= $groupId ?>" class="btn btn--ghost">Импорт из Word</a>
                        <a href="student_create.php?group_id=<?= $groupId ?>" class="btn btn--primary">+ Добавить студента</a>
                    </div>
                </div>

                <?php if (empty($students)): ?>
                    <p class="text-muted">В группе пока нет студентов.</p>
                <?php else: ?>
                    <div class="table-wrap curator-group-table-wrap curator-group-table-wrap--desktop">
                        <table class="table curator-group-table" data-curator-group-table>
                            <thead>
                                <tr>
                                    <th class="curator-group-table__num">№</th>
                                    <th>ФИО студента</th>
                                    <th>Телефон</th>
                                    <th>Дата рождения</th>
                                    <th>Данные</th>
                                    <th class="curator-group-table__actions">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $index => $student): ?>
                                <?php $editUrl = 'student_edit.php?id=' . (int) $student['id']; ?>
                                <tr
                                    class="table__row--clickable"
                                    data-student-name="<?= e(mb_strtolower((string) $student['full_name'])) ?>"
                                    onclick="location.href='<?= e($editUrl) ?>'"
                                >
                                    <td class="curator-group-table__num"><?= $index + 1 ?></td>
                                    <td>
                                        <span class="curator-group-table__name"><?= e($student['full_name']) ?></span>
                                    </td>
                                    <td><?= e($student['phone'] ?: '—') ?></td>
                                    <td><?= e(format_student_birth_date($student['birth_date'] ?? null)) ?></td>
                                    <td><?= student_profile_completeness_badge($student) ?></td>
                                    <td class="curator-group-table__actions" onclick="event.stopPropagation()">
                                        <a
                                            href="<?= e($editUrl) ?>"
                                            class="curator-group-table__action"
                                            title="Открыть карточку"
                                            aria-label="Открыть карточку студента"
                                        >
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="12" cy="12" r="1"/>
                                                <circle cx="12" cy="5" r="1"/>
                                                <circle cx="12" cy="19" r="1"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="curator-student-cards" data-curator-group-cards>
                        <?php foreach ($students as $student): ?>
                        <?php
                        $editUrl = 'student_edit.php?id=' . (int) $student['id'];
                        $parts = preg_split('/\s+/u', trim((string) $student['full_name'])) ?: [];
                        $initials = mb_strtoupper(mb_substr((string) ($parts[0] ?? '?'), 0, 1) . mb_substr((string) ($parts[1] ?? ''), 0, 1));
                        $complete = is_student_profile_complete($student);
                        ?>
                        <a
                            href="<?= e($editUrl) ?>"
                            class="curator-student-card"
                            data-student-name="<?= e(mb_strtolower((string) $student['full_name'])) ?>"
                        >
                            <span class="curator-student-card__avatar" aria-hidden="true"><?= e($initials) ?></span>
                            <span class="curator-student-card__body">
                                <span class="curator-student-card__name"><?= e($student['full_name']) ?></span>
                                <span class="curator-student-card__meta"><?= e($student['phone'] ?: 'Телефон не указан') ?></span>
                            </span>
                            <span class="curator-student-card__badge<?= $complete ? ' is-ok' : ' is-warn' ?>">
                                <?= $complete ? 'Заполнено' : 'Не заполнено' ?>
                            </span>
                            <span class="curator-student-card__chevron" aria-hidden="true">›</span>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <p class="text-muted table-hint curator-group-page__hint" data-curator-group-empty hidden>
                        По запросу ничего не найдено.
                    </p>
                    <p class="text-muted table-hint curator-group-page__hint curator-group-page__hint--desktop">
                        Нажмите на строку, чтобы открыть карточку студента.
                        «Заполнено» — указаны все необходимые данные с учётом состава семьи;
                        флаги «иногородний», «малообеспеченная семья» и подобные не обязательны.
                    </p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (count($groups) > 1): ?>
<div class="app-sheet" data-group-sheet hidden>
    <div class="app-sheet__backdrop" data-group-sheet-close tabindex="-1"></div>
    <div class="app-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="groupSheetTitle">
        <div class="app-sheet__handle" aria-hidden="true"></div>
        <div class="app-sheet__head">
            <h2 class="app-sheet__title" id="groupSheetTitle">Выберите группу</h2>
            <button type="button" class="app-sheet__close" data-group-sheet-close aria-label="Закрыть">×</button>
        </div>
        <label class="app-sheet__search">
            <span class="app-sheet__search-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
            </span>
            <input type="search" class="app-sheet__search-input" placeholder="Поиск группы..." data-group-sheet-search autocomplete="off">
        </label>
        <form method="get" class="app-sheet__form" data-group-sheet-form>
            <div class="app-sheet__list" data-group-sheet-list>
                <?php foreach ($groups as $item): ?>
                <label class="app-sheet__option" data-group-label="<?= e(mb_strtolower((string) $item['number'])) ?>">
                    <input
                        type="radio"
                        name="group_id"
                        value="<?= (int) $item['id'] ?>"
                        <?= (int) $item['id'] === $groupId ? 'checked' : '' ?>
                        onchange="this.form.submit()"
                    >
                    <span class="app-sheet__option-text"><?= e($item['number']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
