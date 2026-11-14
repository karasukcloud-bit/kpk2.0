<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/individual_schedule.php';
require_once __DIR__ . '/../includes/curriculum.php';
require_once __DIR__ . '/../includes/attendance.php';

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён. Требуются права завуча или администратора.');
}

$activePeriod = get_active_gradebook_period();
$year = normalize_academic_year((string) ($_GET['year'] ?? '')) ?? $activePeriod['academic_year'];
$semester = normalize_gradebook_semester((string) ($_GET['sem'] ?? $activePeriod['semester']));
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$search = trim((string) ($_GET['q'] ?? ''));

$error = null;
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } elseif (($_POST['action'] ?? '') === 'disable_igo') {
        $result = set_student_individual_schedule(
            (int) ($_POST['student_id'] ?? 0),
            (string) ($_POST['academic_year'] ?? ''),
            (string) ($_POST['semester'] ?? ''),
            false
        );
        if (!empty($result['success'])) {
            flash_set('success', 'Отметка ИГО снята.');
            $params = [
                'year' => $year,
                'sem' => $semester,
            ];
            if ($groupId > 0) {
                $params['group_id'] = $groupId;
            }
            if ($search !== '') {
                $params['q'] = $search;
            }
            header('Location: igo.php?' . http_build_query($params));
            exit;
        }
        $error = $result['error'] ?? 'Не удалось снять ИГО.';
    }
}

$yearOptions = get_last_academic_years($activePeriod['academic_year'], 4);
$groups = get_all_groups();
$rows = list_individual_schedule_students($year, $semester, $groupId, $search);

$groupIds = [];
foreach ($rows as $row) {
    $groupIds[(int) $row['group_id']] = true;
}
$studentCount = count($rows);
$groupCount = count($groupIds);

$queryParams = static function (array $overrides = []) use ($year, $semester, $groupId, $search): string {
    $params = array_merge([
        'year' => $year,
        'sem' => $semester,
        'group_id' => $groupId > 0 ? $groupId : null,
        'q' => $search !== '' ? $search : null,
    ], $overrides);
    $params = array_filter(
        $params,
        static fn ($v) => $v !== null && $v !== '' && $v !== 0
    );

    return http_build_query($params);
};

$preferManual = is_manual_brs_gradebook_active();

$pageTitle = 'ИГО — Панель завуча';
$showHeader = true;
$basePath = '../';
$currentDeputyTab = 'igo';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель завуча</h1>
                <p class="text-muted">Студенты на индивидуальном графике обучения (ИГО)</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/deputy_nav.php'; ?>
    </section>

    <section class="panel">
        <h2>Индивидуальный график</h2>
        <p class="text-muted">
            Период: <?= e($year) ?> · <?= e(semester_label($semester)) ?>.
            Пустые оценки студентов на ИГО не учитываются в статусе «Не все оценки».
        </p>

        <?php if ($success): ?>
            <div class="alert alert--success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="get" class="summary-report-filters igo-filters">
            <div class="summary-report-filters__group">
                <span class="summary-report-filters__label">Учебный год</span>
                <div class="summary-report-filters__tabs">
                    <?php foreach ($yearOptions as $optYear): ?>
                        <a href="igo.php?<?= e($queryParams(['year' => $optYear])) ?>"
                           class="summary-report-filters__tab<?= $optYear === $year ? ' is-active' : '' ?>">
                            <?= e($optYear) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="summary-report-filters__group">
                <span class="summary-report-filters__label">Семестр</span>
                <div class="summary-report-filters__tabs">
                    <a href="igo.php?<?= e($queryParams(['sem' => '1'])) ?>"
                       class="summary-report-filters__tab<?= $semester === '1' ? ' is-active' : '' ?>">1 семестр</a>
                    <a href="igo.php?<?= e($queryParams(['sem' => '2'])) ?>"
                       class="summary-report-filters__tab<?= $semester === '2' ? ' is-active' : '' ?>">2 семестр</a>
                </div>
            </div>

            <div class="summary-report-filters__group">
                <label class="summary-report-filters__label" for="igo-group">Группа</label>
                <select id="igo-group" name="group_id" class="form__select" onchange="this.form.submit()">
                    <option value="0">Все группы</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?= (int) $group['id'] ?>"<?= $groupId === (int) $group['id'] ? ' selected' : '' ?>>
                            <?= e((string) $group['number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="year" value="<?= e($year) ?>">
                <input type="hidden" name="sem" value="<?= e($semester) ?>">
            </div>

            <div class="summary-report-filters__group">
                <label class="summary-report-filters__label" for="igo-q">Поиск</label>
                <div class="igo-filters__search">
                    <input
                        id="igo-q"
                        type="search"
                        name="q"
                        value="<?= e($search) ?>"
                        class="form__input"
                        placeholder="ФИО студента"
                    >
                    <button type="submit" class="btn btn--ghost btn--sm">Найти</button>
                </div>
            </div>
        </form>

        <p class="text-muted igo-summary">
            На ИГО: <strong><?= $studentCount ?></strong>
            <?= $studentCount === 1 ? 'студент' : ($studentCount > 1 && $studentCount < 5 ? 'студента' : 'студентов') ?>
            · <strong><?= $groupCount ?></strong>
            <?= $groupCount === 1 ? 'группа' : ($groupCount > 1 && $groupCount < 5 ? 'группы' : 'групп') ?>
        </p>

        <?php if ($rows === []): ?>
            <p class="text-muted">Нет студентов на ИГО за выбранный период.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Студент</th>
                            <th>Группа</th>
                            <th>Специальность</th>
                            <th>Включён</th>
                            <th>Кем</th>
                            <th>Оценки</th>
                            <th class="table__actions">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $sid = (int) $row['student_id'];
                            $gid = (int) $row['group_id'];
                            $gradebookHref = $preferManual
                                ? 'manual_brs_gradebook.php?group_id=' . $gid
                                : 'gradebook.php?group_id=' . $gid;
                            $createdAt = (string) ($row['created_at'] ?? '');
                            $createdLabel = $createdAt !== ''
                                ? date('d.m.Y H:i', strtotime($createdAt))
                                : '—';
                            $byName = trim((string) ($row['created_by_name'] ?? ''));
                            ?>
                            <tr>
                                <td>
                                    <?= e((string) $row['display_name']) ?>
                                    <span class="badge badge--igo" title="Индивидуальный график обучения">ИГО</span>
                                </td>
                                <td><?= e((string) $row['group_number']) ?></td>
                                <td><?= e((string) ($row['specialty_name'] ?? '')) ?></td>
                                <td><?= e($createdLabel) ?></td>
                                <td><?= e($byName !== '' ? person_last_first_name($byName) : '—') ?></td>
                                <td>
                                    <?= (int) $row['grades_filled'] ?>
                                    / <?= (int) $row['subjects_total'] ?>
                                </td>
                                <td class="table__actions">
                                    <a href="<?= e($gradebookHref) ?>" class="btn btn--ghost btn--sm">Ведомость</a>
                                    <form method="post" class="form-inline igo-disable-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="disable_igo">
                                        <input type="hidden" name="student_id" value="<?= $sid ?>">
                                        <input type="hidden" name="academic_year" value="<?= e((string) $row['academic_year']) ?>">
                                        <input type="hidden" name="semester" value="<?= e((string) $row['semester']) ?>">
                                        <button
                                            type="submit"
                                            class="btn btn--ghost btn--sm"
                                            onclick="return confirm('Снять отметку ИГО у студента?');"
                                        >Снять ИГО</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
