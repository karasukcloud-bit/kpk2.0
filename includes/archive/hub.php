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

    <section class="panel" data-archive-json-import data-import-url="archive_import_json.php" data-csrf="<?= e(csrf_token()) ?>">
        <h2>Импорт ведомостей из JSON</h2>
        <p class="text-muted">
            Выберите один или несколько файлов ведомостей (.json). Сначала рекомендуется
            «Проверить» (без записи), затем «Импортировать». Год и семестр берутся из файла.
            Для прошлых учебных лет карточки не попадают в текущий состав группы (выпускники).
            Большие наборы файлов отправляются пакетами автоматически.
        </p>
        <div class="form form--medium">
            <div class="form__group">
                <label for="archive_json_files">Файлы JSON</label>
                <input
                    type="file"
                    id="archive_json_files"
                    name="files[]"
                    accept=".json,application/json"
                    multiple
                    data-archive-json-files
                >
            </div>
            <div class="form__group form__group--checkbox">
                <label class="checkbox-label">
                    <input type="checkbox" data-archive-json-allow-create value="1">
                    Создавать отсутствующих студентов (на проде только если dry-run показал MISSING)
                </label>
            </div>
            <div class="form__actions">
                <button type="button" class="btn btn--ghost" data-archive-json-dry>Проверить</button>
                <button type="button" class="btn btn--primary" data-archive-json-run>Импортировать</button>
            </div>
            <p class="text-muted" data-archive-json-status hidden></p>
            <div class="table-wrap" data-archive-json-result hidden>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Файл / группа</th>
                            <th>Статус</th>
                            <th>Оценок</th>
                            <th>Замечания</th>
                        </tr>
                    </thead>
                    <tbody data-archive-json-tbody></tbody>
                </table>
            </div>
        </div>
    </section>

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

<script>
(function () {
    const root = document.querySelector('[data-archive-json-import]');
    if (!root) return;

    const filesInput = root.querySelector('[data-archive-json-files]');
    const allowCreate = root.querySelector('[data-archive-json-allow-create]');
    const statusEl = root.querySelector('[data-archive-json-status]');
    const resultWrap = root.querySelector('[data-archive-json-result]');
    const tbody = root.querySelector('[data-archive-json-tbody]');
    const dryBtn = root.querySelector('[data-archive-json-dry]');
    const runBtn = root.querySelector('[data-archive-json-run]');
    const importUrl = root.dataset.importUrl || 'archive_import_json.php';
    const csrf = root.dataset.csrf || '';
    const BATCH_SIZE = 15; // PHP max_file_uploads обычно 20

    function setBusy(busy) {
        dryBtn.disabled = busy;
        runBtn.disabled = busy;
        filesInput.disabled = busy;
    }

    function showStatus(text, isError) {
        statusEl.hidden = false;
        statusEl.textContent = text;
        statusEl.classList.toggle('alert', true);
        statusEl.classList.toggle('alert--error', !!isError);
        statusEl.classList.toggle('alert--success', !isError);
        statusEl.classList.toggle('text-muted', false);
    }

    function escapeHtml(value) {
        return String(value).replace(/</g, '&lt;').replace(/&/g, '&amp;');
    }

    function appendResultRows(rows, dryRun) {
        rows.forEach(function (row) {
            const tr = document.createElement('tr');
            const title = row.label ? (row.file + ' → ' + row.label) : row.file;
            let note = '';
            if (!row.ok) {
                note = row.error || 'Ошибка';
            } else {
                const missing = row.students
                    ? row.students.filter(function (s) {
                        return String(s.flag).indexOf('MISSING') === 0;
                    }).map(function (s) { return s.name; })
                    : [];
                const parts = [];
                if (row.historical) parts.push('исторический период');
                if (row.missing > 0) {
                    parts.push(
                        'нет в системе: ' + missing.slice(0, 5).join(', ')
                        + (missing.length > 5 ? '…' : '')
                    );
                }
                if (parts.length === 0) parts.push('ок');
                note = parts.join('; ');
            }
            tr.innerHTML =
                '<td>' + escapeHtml(title) + '</td>' +
                '<td>' + (row.ok ? (dryRun ? 'Проверка' : 'Импорт') : 'Ошибка') + '</td>' +
                '<td>' + (row.ok ? String(row.grades != null ? row.grades : '—') : '—') + '</td>' +
                '<td>' + escapeHtml(note) + '</td>';
            if (!row.ok || (row.missing > 0)) tr.classList.add('table__row--warn');
            tbody.appendChild(tr);
        });
    }

    async function postBatch(fileBatch, dryRun) {
        const formData = new FormData();
        formData.append('csrf_token', csrf);
        formData.append('dry_run', dryRun ? '1' : '0');
        if (allowCreate.checked) formData.append('allow_create', '1');
        fileBatch.forEach(function (file) { formData.append('files[]', file); });

        const response = await fetch(importUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            throw new Error(
                'Сервер вернул не JSON (HTTP ' + response.status + '). '
                + 'Частая причина: слишком много файлов за раз или лимит PHP. '
                + raw.trim().slice(0, 180)
            );
        }
        if (!response.ok && !data.results) {
            throw new Error(data.error || ('Ошибка запроса (HTTP ' + response.status + ')'));
        }
        return data;
    }

    async function runImport(dryRun) {
        if (!filesInput.files || filesInput.files.length === 0) {
            showStatus('Выберите хотя бы один JSON-файл.', true);
            return;
        }
        const allFiles = Array.from(filesInput.files);
        if (!dryRun) {
            const ok = window.confirm(
                'Импортировать ' + allFiles.length + ' файл(ов) в архив ведомостей?'
            );
            if (!ok) return;
        }

        setBusy(true);
        resultWrap.hidden = true;
        tbody.innerHTML = '';

        const batches = [];
        for (let i = 0; i < allFiles.length; i += BATCH_SIZE) {
            batches.push(allFiles.slice(i, i + BATCH_SIZE));
        }

        let failed = 0;
        let processed = 0;
        try {
            for (let b = 0; b < batches.length; b++) {
                showStatus(
                    (dryRun ? 'Проверка' : 'Импорт')
                    + '… пакет ' + (b + 1) + '/' + batches.length
                    + ' (' + processed + '/' + allFiles.length + ' файлов)',
                    false
                );
                const data = await postBatch(batches[b], dryRun);
                const rows = data.results || [];
                appendResultRows(rows, !!data.dry_run);
                failed += Number(data.failed || 0);
                processed += batches[b].length;
                resultWrap.hidden = tbody.children.length === 0;
            }

            if (failed === 0) {
                showStatus(
                    dryRun
                        ? 'Проверка ок: все ' + allFiles.length + ' файл(ов), ФИО найдены. Можно импортировать.'
                        : 'Импорт завершён: ' + allFiles.length + ' файл(ов).',
                    false
                );
                if (!dryRun) {
                    window.setTimeout(function () { window.location.reload(); }, 1200);
                }
            } else {
                showStatus(
                    dryRun
                        ? 'Проверка: есть отсутствующие ФИО или ошибки (' + failed + '). '
                            + 'Поправьте JSON или включите «Создавать…» осознанно.'
                        : 'Импорт завершился с ошибками (' + failed + ').',
                    true
                );
            }
        } catch (e) {
            showStatus(e && e.message ? e.message : 'Не удалось выполнить запрос.', true);
        } finally {
            setBusy(false);
        }
    }

    dryBtn.addEventListener('click', function () { runImport(true); });
    runBtn.addEventListener('click', function () { runImport(false); });
})();
</script>

<?php require __DIR__ . '/../footer.php'; ?>
