<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../archive.php';
require_once __DIR__ . '/../curriculum.php';
require_once __DIR__ . '/../teachers.php';
require_once __DIR__ . '/../students.php';

require_curator_panel();

$viewYear = '';
$viewSemester = '';
if (isset($_GET['period']) && is_string($_GET['period']) && strpos($_GET['period'], '|') !== false) {
    $parts = explode('|', $_GET['period'], 2);
    $viewYear = normalize_academic_year($parts[0] ?? '') ?? '';
    $viewSemester = normalize_gradebook_semester((string) ($parts[1] ?? ''));
} else {
    $viewYear = normalize_academic_year($_GET['year'] ?? '') ?? '';
    $viewSemester = isset($_GET['semester'])
        ? normalize_gradebook_semester((string) $_GET['semester'])
        : '';
}

$savedPeriods = list_saved_archive_gradebook_periods();
$periodValid = false;
$archiveId = 0;
foreach ($savedPeriods as $period) {
    if ((string) $period['academic_year'] === $viewYear && (string) $period['semester'] === $viewSemester) {
        $periodValid = true;
        $archiveId = (int) $period['archive_id'];
        break;
    }
}
if (!$periodValid && $savedPeriods !== []) {
    $viewYear = (string) $savedPeriods[0]['academic_year'];
    $viewSemester = (string) $savedPeriods[0]['semester'];
    $archiveId = (int) $savedPeriods[0]['archive_id'];
    $periodValid = true;
}

$gradebookArchive = $periodValid ? get_archive_period_by_id($archiveId) : null;
$gradebookGroups = $gradebookArchive
    ? get_archive_gradebook_groups((int) $gradebookArchive['id'])
    : [];

$pageTitle = 'Архив ведомостей — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'archive';
$curatorGroupId = 0;
require __DIR__ . '/../header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель куратора</h1>
                <p class="text-muted">Архив ведомостей</p>
            </div>
        </div>
        <?php require __DIR__ . '/../curator_nav.php'; ?>
    </section>

    <section class="panel">
        <h2>Просмотр архива</h2>
        <?php if ($savedPeriods === []): ?>
            <p class="text-muted">В архиве пока нет сохранённых ведомостей.</p>
        <?php else: ?>
            <form method="get" class="form form--filter">
                <div class="form__group">
                    <label for="archive_period">Период</label>
                    <select id="archive_period" name="period" onchange="this.form.submit()">
                        <?php foreach ($savedPeriods as $period): ?>
                        <?php
                        $periodYear = (string) $period['academic_year'];
                        $periodSemester = (string) $period['semester'];
                        $periodValue = $periodYear . '|' . $periodSemester;
                        $isSelected = $periodYear === $viewYear && $periodSemester === $viewSemester;
                        ?>
                        <option value="<?= e($periodValue) ?>"<?= $isSelected ? ' selected' : '' ?>>
                            <?= e($periodYear) ?> · <?= e(semester_label($periodSemester)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if ($gradebookArchive === null || $gradebookGroups === []): ?>
                <p class="text-muted">Ведомости за этот период не найдены.</p>
            <?php else: ?>
                <p class="text-muted">
                    Архивировано <?= e(format_date($gradebookArchive['archived_at'])) ?>
                    <?php if (!empty($gradebookArchive['archived_by_name'])): ?>
                        · <?= e($gradebookArchive['archived_by_name']) ?>
                    <?php endif; ?>
                </p>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Группа</th>
                                <th>Специальность</th>
                                <th>Куратор</th>
                                <th class="table__actions-col">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gradebookGroups as $group): ?>
                            <tr>
                                <td><?= e($group['group_number']) ?></td>
                                <td><?= e($group['specialty_name']) ?></td>
                                <td><?= e(($group['curator_name'] ?? '') !== '' ? $group['curator_name'] : '—') ?></td>
                                <td class="table__actions">
                                    <a
                                        href="archive_gradebook.php?id=<?= (int) $gradebookArchive['id'] ?>&group_id=<?= (int) $group['group_id'] ?>"
                                        class="btn btn--primary btn--sm"
                                    >Открыть</a>
                                    <a
                                        href="archive_gradebook_pdf.php?id=<?= (int) $gradebookArchive['id'] ?>&group_id=<?= (int) $group['group_id'] ?>"
                                        class="btn btn--secondary btn--sm"
                                    >PDF</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
