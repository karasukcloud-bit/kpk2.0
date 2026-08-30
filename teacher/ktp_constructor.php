<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/profile.php';
require_once __DIR__ . '/../includes/ktp.php';
require_once __DIR__ . '/../includes/ktp/word_import.php';
require_once __DIR__ . '/../includes/curriculum.php';
require_once __DIR__ . '/../includes/gradebook.php';

require_teacher_panel();

$itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
$mode = (string) ($_GET['mode'] ?? '');
$error = null;
$period = get_active_gradebook_period();
$assignedSubjects = get_teacher_assigned_subjects((int) current_user()['id'], $period['academic_year']);

if ($itemId > 0) {
    if (!can_manage_item_ktp($itemId)) {
        flash_set('error', 'Нет доступа к КТП этого предмета.');
        header('Location: ktp_constructor.php');
        exit;
    }
}

$item = $itemId > 0 ? get_curriculum_item_by_id($itemId) : null;
if ($itemId > 0 && $item === null) {
    flash_set('error', 'Предмет не найден.');
    header('Location: ktp_constructor.php');
    exit;
}

if ($itemId > 0 && ($mode === 'manual' || ($mode !== 'rp' && $mode !== 'rows' && $mode !== 'word'))) {
    $mode = 'rows';
}

if ($itemId > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = $_POST['action'] ?? '';
        $redirect = 'ktp_constructor.php?item_id=' . $itemId . '&mode=' . urlencode($mode !== '' ? $mode : 'rows');

        if ($action === 'upload_work_program') {
            $result = save_ktp_work_program($itemId, $_FILES['work_program'] ?? []);
            if ($result['success']) {
                flash_set('success', 'Рабочая программа загружена.');
                header('Location: ' . $redirect);
                exit;
            }
            $error = $result['error'];
        } elseif ($action === 'delete_work_program') {
            $result = delete_ktp_work_program($itemId);
            if ($result['success']) {
                flash_set('success', 'Рабочая программа удалена.');
                header('Location: ' . $redirect);
                exit;
            }
            $error = $result['error'];
        } elseif ($action === 'upload_word_ktp' && $mode === 'word') {
            $result = upload_and_parse_ktp_word($itemId, $_FILES['word_program'] ?? []);
            if ($result['success']) {
                flash_set(
                    'success',
                    'Файл разобран. Найдено строк: ' . (int) ($result['row_count'] ?? 0) . '. Проверьте предпросмотр и импортируйте.'
                );
                header('Location: ' . $redirect);
                exit;
            }
            $error = $result['error'];
        } elseif ($action === 'import_word_ktp' && $mode === 'word') {
            $preview = ktp_word_import_get_preview($itemId);
            if ($preview === null || empty($preview['rows'])) {
                $error = 'Нет данных для импорта. Загрузите файл Word заново.';
            } else {
                $result = import_ktp_topics_from_rows($itemId, $preview['rows'], true);
                if ($result['success']) {
                    ktp_word_import_clear_preview($itemId);
                    flash_set('success', 'Импортировано строк: ' . (int) ($result['imported'] ?? 0) . '.');
                    header('Location: ktp_constructor.php?item_id=' . $itemId . '&mode=rows');
                    exit;
                }
                $error = $result['error'];
            }
        } elseif ($action === 'cancel_word_ktp' && $mode === 'word') {
            ktp_word_import_clear_preview($itemId);
            flash_set('success', 'Предпросмотр импорта отменён.');
            header('Location: ' . $redirect);
            exit;
        } elseif ($action === 'add_ktp_semester_marker' && $mode === 'rows') {
            $markerType = (string) ($_POST['marker_type'] ?? 'semester_2');
            $result = add_ktp_semester_marker($itemId, $markerType);
            if ($result['success']) {
                flash_set('success', 'Разделитель «' . ktp_semester_marker_title($markerType) . '» добавлен в КТП.');
                header('Location: ' . $redirect);
                exit;
            }
            $error = $result['error'];
        }
    }
}

$isTableMode = $itemId > 0 && $mode === 'rows';
if ($isTableMode) {
    ensure_ktp_has_starter_row($itemId);
}
$topics = $isTableMode ? get_ktp_topics_with_progress($itemId) : [];
$ktpSummary = $isTableMode ? build_ktp_plan_summary($topics) : [];
$workProgram = $itemId > 0 && $mode === 'rp' ? get_ktp_work_program($itemId) : null;
$wordImportPreview = $itemId > 0 && $mode === 'word' ? ktp_word_import_get_preview($itemId) : null;
$ktpColumnWidths = $itemId > 0 ? get_ktp_column_widths($itemId) : null;

$success = flash_get('success');
$pageTitle = $item
    ? ('Конструктор КТП — ' . $item['subject_name'])
    : 'Конструктор КТП — Панель преподавателя';
$showHeader = true;
$basePath = '../';
$currentTeacherTab = 'ktp_constructor';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель преподавателя</h1>
                <p class="text-muted">Конструктор календарно-тематического планирования</p>
            </div>
            <?php if ($item): ?>
            <a href="ktp.php?item_id=<?= $itemId ?>" class="btn btn--ghost btn--sm">← К просмотру КТП</a>
            <?php endif; ?>
        </div>
        <?php require __DIR__ . '/../includes/teacher_nav.php'; ?>
    </section>

    <?php if ($item === null): ?>
        <section class="panel">
            <p class="text-muted">
                Учебный год <?= e($period['academic_year']) ?> · <?= e(semester_label($period['semester'])) ?>.
                Выберите предмет для работы с КТП.
            </p>
            <?php if ($assignedSubjects === []): ?>
                <p class="text-muted">Нет закреплённых предметов в текущем учебном году.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Группа</th>
                                <th>Предмет</th>
                                <th>Семестр</th>
                                <th>Тем в КТП</th>
                                <th class="table__actions-col">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignedSubjects as $subject): ?>
                            <tr>
                                <td><?= e($subject['group_number']) ?></td>
                                <td><?= e($subject['subject_name']) ?></td>
                                <td><?= e(semester_label($subject['semester'])) ?></td>
                                <td><?= (int) $subject['ktp_count'] ?></td>
                                <td class="table__actions">
                                    <a
                                        href="ktp.php?item_id=<?= (int) $subject['curriculum_item_id'] ?>"
                                        class="btn btn--primary btn--sm"
                                    >Открыть</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="panel">
            <div class="panel__header panel__header--compact">
                <h2><?= e($item['subject_name']) ?></h2>
            </div>

            <nav class="journal-subtabs ktp-constructor-modes">
                <a
                    href="ktp_constructor.php?item_id=<?= $itemId ?>&mode=rows"
                    class="journal-subtabs__item<?= $mode === 'rows' ? ' journal-subtabs__item--active' : '' ?>"
                >Строки</a>
                <a
                    href="ktp_constructor.php?item_id=<?= $itemId ?>&mode=word"
                    class="journal-subtabs__item<?= $mode === 'word' ? ' journal-subtabs__item--active' : '' ?>"
                >Из Word</a>
                <a
                    href="ktp_constructor.php?item_id=<?= $itemId ?>&mode=rp"
                    class="journal-subtabs__item<?= $mode === 'rp' ? ' journal-subtabs__item--active' : '' ?>"
                >С загрузкой РП</a>
            </nav>

            <?php if ($mode === 'rows'): ?>
                <?php require __DIR__ . '/../includes/ktp/rows_editor.php'; ?>
            <?php elseif ($mode === 'word'): ?>
                <?php require __DIR__ . '/../includes/ktp/word_import_ui.php'; ?>
            <?php else: ?>
                <?php if ($success): ?>
                    <div class="alert alert--success"><?= e($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert--error"><?= e($error) ?></div>
                <?php endif; ?>

                <p class="text-muted">
                    Загрузите файл рабочей программы (РП). Поддерживаются PDF, DOC, DOCX, RTF, ODT до 10 МБ.
                    После загрузки файл сохраняется в системе для дальнейшей работы.
                </p>

                <?php if ($workProgram !== null): ?>
                    <dl class="profile-list">
                        <dt>Текущий файл</dt>
                        <dd><?= e($workProgram['original_name']) ?></dd>
                        <dt>Загружен</dt>
                        <dd>
                            <?= e(date('d.m.Y H:i', strtotime((string) $workProgram['uploaded_at']))) ?>
                            <?php if (!empty($workProgram['uploaded_by_name'])): ?>
                                · <?= e($workProgram['uploaded_by_name']) ?>
                            <?php endif; ?>
                        </dd>
                    </dl>
                    <form method="post" class="form form--inline" onsubmit="return confirm('Удалить загруженную рабочую программу?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_work_program">
                        <button type="submit" class="btn btn--danger btn--sm">Удалить файл</button>
                    </form>
                    <hr class="divider">
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="form form--medium">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload_work_program">
                    <div class="form__group">
                        <label for="work_program">Файл рабочей программы</label>
                        <input type="file" id="work_program" name="work_program" accept=".pdf,.doc,.docx,.rtf,.odt" required>
                    </div>
                    <div class="form__actions">
                        <button type="submit" class="btn btn--primary">
                            <?= $workProgram !== null ? 'Заменить файл' : 'Загрузить РП' ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
