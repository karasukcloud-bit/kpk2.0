<?php

declare(strict_types=1);

$archivePanel = $archivePanel ?? 'admin';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../archive.php';
require_once __DIR__ . '/../curriculum.php';
require_once __DIR__ . '/../teachers.php';

require_archive_manager();

$activePeriod = get_active_gradebook_period();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $replace = isset($_POST['replace']);
        $year = (string) $activePeriod['academic_year'];
        $semester = (string) $activePeriod['semester'];

        if ($action === 'delete_gradebooks' || $action === 'delete_journals') {
            $year = (string) ($_POST['year'] ?? '');
            $semester = (string) ($_POST['semester'] ?? '');
            $result = delete_archive_period_by_admin(
                $action === 'delete_journals' ? 'journal' : 'gradebook',
                $year,
                $semester,
                (string) ($_POST['confirm_text'] ?? ''),
                isset($_POST['confirm_ack'])
            );
        } elseif ($action === 'archive_gradebooks') {
            $result = archive_gradebooks_for_period($year, $semester, $replace);
        } elseif ($action === 'archive_journals') {
            $result = archive_journals_for_period($year, $semester, $replace);
        } else {
            $result = ['success' => false, 'error' => 'Неизвестное действие.'];
        }

        if ($result['success']) {
            if ($action === 'delete_journals') {
                $message = 'Архив журналов удалён.';
            } elseif ($action === 'delete_gradebooks') {
                $message = 'Архив ведомостей удалён.';
            } elseif ($action === 'archive_journals') {
                $message = 'Журналы за период архивированы.';
            } else {
                $message = 'Ведомости за период архивированы.';
            }
            flash_set('success', $message);
            header('Location: archive.php?year=' . urlencode($year) . '&semester=' . urlencode($semester));
            exit;
        }

        $error = $result['error'] === 'already_exists'
            ? 'Архив за этот период уже есть. Подтвердите замену.'
            : (string) $result['error'];
        $pendingReplace = $result['error'] === 'already_exists' ? $action : null;
    }
}

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

$savedPeriods = list_saved_archive_periods();
$periodValid = false;
foreach ($savedPeriods as $period) {
    if ((string) $period['academic_year'] === $viewYear && (string) $period['semester'] === $viewSemester) {
        $periodValid = true;
        break;
    }
}
if (!$periodValid && $savedPeriods !== []) {
    $viewYear = (string) $savedPeriods[0]['academic_year'];
    $viewSemester = (string) $savedPeriods[0]['semester'];
    $periodValid = true;
}

$gradebookArchive = $periodValid ? get_archive_period('gradebook', $viewYear, $viewSemester) : null;
$journalArchive = $periodValid ? get_archive_period('journal', $viewYear, $viewSemester) : null;
$gradebookGroups = $gradebookArchive ? get_archive_gradebook_groups((int) $gradebookArchive['id']) : [];
$journalGroups = $journalArchive ? get_archive_journal_groups((int) $journalArchive['id']) : [];

$success = flash_get('success');
$pendingReplace = $pendingReplace ?? null;
$canDeleteArchive = can_delete_archives() && $archivePanel === 'admin';
$pageTitle = 'Архив — ' . ($archivePanel === 'admin' ? 'Администрирование' : 'Панель завуча');
$showHeader = true;
$basePath = '../';

if ($archivePanel === 'admin') {
    $currentAdminTab = 'archive';
} else {
    $currentDeputyTab = 'archive';
}

require __DIR__ . '/../header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1><?= $archivePanel === 'admin' ? 'Панель администратора' : 'Панель завуча' ?></h1>
                <p class="text-muted">Архив ведомостей и журналов</p>
            </div>
        </div>
        <?php
        if ($archivePanel === 'admin') {
            require __DIR__ . '/../admin_nav.php';
        } else {
            require __DIR__ . '/../deputy_nav.php';
        }
        ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <h2>Архивация текущего периода</h2>
        <p class="text-muted">
            Текущий период: <?= e($activePeriod['academic_year']) ?> ·
            <?= e(semester_label($activePeriod['semester'])) ?>.
            Архивируйте после выставления всех итоговых оценок.
        </p>
        <div class="form__actions">
            <form method="post" class="form-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="archive_gradebooks">
                <?php if ($pendingReplace === 'archive_gradebooks'): ?>
                    <input type="hidden" name="replace" value="1">
                <?php endif; ?>
                <button
                    type="submit"
                    class="btn btn--primary"
                    onclick="return confirm('<?= $pendingReplace === 'archive_gradebooks'
                        ? 'Заменить архив ведомостей за текущий период?'
                        : 'Архивировать все ведомости за текущий период?' ?>')"
                ><?= $pendingReplace === 'archive_gradebooks' ? 'Заменить архив ведомостей' : 'Архивировать ведомости' ?></button>
            </form>
            <form method="post" class="form-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="archive_journals">
                <?php if ($pendingReplace === 'archive_journals'): ?>
                    <input type="hidden" name="replace" value="1">
                <?php endif; ?>
                <button
                    type="submit"
                    class="btn btn--primary"
                    onclick="return confirm('<?= $pendingReplace === 'archive_journals'
                        ? 'Заменить архив журналов за текущий период?'
                        : 'Архивировать все журналы за текущий период?' ?>')"
                ><?= $pendingReplace === 'archive_journals' ? 'Заменить архив журналов' : 'Архивировать журналы' ?></button>
            </form>
        </div>
    </section>

    <section class="panel">
        <h2>Просмотр архива</h2>
        <?php if ($savedPeriods === []): ?>
            <p class="text-muted">В архиве пока нет сохранённых ведомостей и журналов.</p>
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

            <h3 class="subsection-title">Ведомости</h3>
            <?php if ($gradebookArchive === null): ?>
                <p class="text-muted">Ведомости за этот период не архивированы.</p>
            <?php else: ?>
                <div class="panel__header panel__header--compact">
                    <p class="text-muted">
                        Архивировано <?= e(format_date($gradebookArchive['archived_at'])) ?>
                        <?php if (!empty($gradebookArchive['archived_by_name'])): ?>
                            · <?= e($gradebookArchive['archived_by_name']) ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($canDeleteArchive): ?>
                    <button
                        type="button"
                        class="btn btn--danger btn--sm"
                        data-archive-delete-open
                        data-type="gradebook"
                        data-year="<?= e($viewYear) ?>"
                        data-semester="<?= e($viewSemester) ?>"
                        data-label="ведомости <?= e($viewYear) ?> · <?= e(semester_label($viewSemester)) ?>"
                    >Удалить ведомости</button>
                    <?php endif; ?>
                </div>
                <?php if ($gradebookGroups === []): ?>
                    <p class="text-muted">В архиве нет групп.</p>
                <?php else: ?>
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

            <h3 class="subsection-title">Журналы</h3>
            <?php if ($journalArchive === null): ?>
                <p class="text-muted">Журналы за этот период не архивированы.</p>
            <?php else: ?>
                <div class="panel__header panel__header--compact">
                    <p class="text-muted">
                        Архивировано <?= e(format_date($journalArchive['archived_at'])) ?>
                        <?php if (!empty($journalArchive['archived_by_name'])): ?>
                            · <?= e($journalArchive['archived_by_name']) ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($canDeleteArchive): ?>
                    <button
                        type="button"
                        class="btn btn--danger btn--sm"
                        data-archive-delete-open
                        data-type="journal"
                        data-year="<?= e($viewYear) ?>"
                        data-semester="<?= e($viewSemester) ?>"
                        data-label="журналы <?= e($viewYear) ?> · <?= e(semester_label($viewSemester)) ?>"
                    >Удалить журналы</button>
                    <?php endif; ?>
                </div>
                <?php if ($journalGroups === []): ?>
                    <p class="text-muted">В архиве нет журналов.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Группа</th>
                                    <th>Предметов</th>
                                    <th class="table__actions-col">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($journalGroups as $group): ?>
                                <tr>
                                    <td><?= e($group['group_number']) ?></td>
                                    <td><?= (int) $group['subjects_count'] ?></td>
                                    <td class="table__actions">
                                        <a
                                            href="archive_journal.php?id=<?= (int) $journalArchive['id'] ?>&group_id=<?= (int) $group['group_id'] ?>"
                                            class="btn btn--primary btn--sm"
                                        >Открыть</a>
                                        <a
                                            href="archive_journal_pdf.php?id=<?= (int) $journalArchive['id'] ?>&group_id=<?= (int) $group['group_id'] ?>"
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
        <?php endif; ?>
    </section>
</div>

<?php if ($canDeleteArchive): ?>
<div class="modal" data-archive-delete-modal hidden>
    <div class="modal__backdrop" data-archive-delete-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="archive-delete-title">
        <div class="modal__header">
            <h2 id="archive-delete-title">Удалить архив</h2>
            <button type="button" class="modal__close" data-archive-delete-close aria-label="Закрыть">&times;</button>
        </div>
        <form method="post" class="form" data-archive-delete-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="" data-archive-delete-action>
            <input type="hidden" name="year" value="" data-archive-delete-year>
            <input type="hidden" name="semester" value="" data-archive-delete-semester>
            <p class="text-muted" data-archive-delete-meta></p>
            <p>Действие необратимо. Чтобы подтвердить удаление, отметьте согласие и введите слово <strong>УДАЛИТЬ</strong>.</p>
            <div class="form__group form__group--checkbox">
                <label class="checkbox-label">
                    <input type="checkbox" name="confirm_ack" value="1" required data-archive-delete-ack>
                    Я понимаю, что архив будет удалён безвозвратно
                </label>
            </div>
            <div class="form__group">
                <label for="archive_confirm_text">Подтверждение</label>
                <input
                    type="text"
                    id="archive_confirm_text"
                    name="confirm_text"
                    required
                    autocomplete="off"
                    placeholder="УДАЛИТЬ"
                    data-archive-delete-text
                >
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--danger">Удалить</button>
                <button type="button" class="btn btn--ghost" data-archive-delete-close>Отмена</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../footer.php'; ?>
