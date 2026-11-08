<?php

declare(strict_types=1);

/**
 * Общая страница импорта студентов из Word / текста.
 * Ожидает: $importGroup, $importGroupId, $importBackUrl, $importPanel ('curator'|'admin')
 */

require_once __DIR__ . '/student_word_import.php';

$importGroupId = (int) ($importGroupId ?? 0);
$importGroup = $importGroup ?? null;
$importBackUrl = (string) ($importBackUrl ?? 'students.php');
$importPanel = (string) ($importPanel ?? 'curator');
$error = null;
$preview = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'parse') {
            $source = (string) ($_POST['source'] ?? 'file');
            if ($source === 'text') {
                $parsed = parse_students_from_text_list((string) ($_POST['list_text'] ?? ''));
            } else {
                $parsed = parse_students_from_uploaded_word($_FILES['word_file'] ?? []);
            }
            if (!$parsed['success']) {
                $error = $parsed['error'] ?? 'Не удалось разобрать список.';
            } else {
                $preview = $parsed['students'] ?? [];
            }
        } elseif ($action === 'import') {
            $lastNames = $_POST['last_name'] ?? [];
            $firstNames = $_POST['first_name'] ?? [];
            $middleNames = $_POST['middle_name'] ?? [];
            if (!is_array($lastNames)) {
                $lastNames = [];
            }
            $rows = [];
            foreach ($lastNames as $i => $last) {
                $rows[] = [
                    'last_name' => (string) $last,
                    'first_name' => (string) ($firstNames[$i] ?? ''),
                    'middle_name' => (string) ($middleNames[$i] ?? ''),
                ];
            }
            $result = import_students_name_rows($importGroupId, $rows);
            if ($result['success']) {
                $created = (int) ($result['created'] ?? 0);
                $msg = 'Добавлено студентов: ' . $created . '.';
                $errList = $result['errors'] ?? [];
                if ($errList !== []) {
                    $msg .= ' С ошибками: ' . count($errList) . '.';
                }
                flash_set('success', $msg);
                header('Location: ' . $importBackUrl);
                exit;
            }
            $error = $result['error'] ?? 'Не удалось импортировать студентов.';
            $preview = [];
            foreach ($rows as $row) {
                if (trim(($row['last_name'] ?? '') . ($row['first_name'] ?? '')) === '') {
                    continue;
                }
                $preview[] = [
                    'last_name' => (string) ($row['last_name'] ?? ''),
                    'first_name' => (string) ($row['first_name'] ?? ''),
                    'middle_name' => (string) ($row['middle_name'] ?? ''),
                    'full_name' => compose_person_full_name(
                        (string) ($row['last_name'] ?? ''),
                        (string) ($row['first_name'] ?? ''),
                        (string) ($row['middle_name'] ?? '')
                    ),
                ];
            }
        }
    }
}

$groupNumber = is_array($importGroup) ? (string) ($importGroup['number'] ?? '') : '';
$pageTitle = 'Импорт студентов — группа ' . $groupNumber;
$showHeader = true;
require __DIR__ . '/header.php';
?>

<div class="dashboard dashboard--wide<?= $importPanel === 'curator' ? ' curator-page' : '' ?>">
    <?php if ($importPanel === 'curator'): ?>
        <?php
        $curatorPageTitle = 'Импорт студентов';
        $curatorPageSubtitle = 'Группа ' . $groupNumber;
        $curatorPageIcon = 'student';
        $curatorPageShowGroup = false;
        ob_start();
        ?>
        <a href="<?= e($importBackUrl) ?>" class="btn btn--ghost">← К списку группы</a>
        <?php
        $curatorPageActionsHtml = ob_get_clean();
        require __DIR__ . '/curator_page_hero.php';
        ?>
    <?php else: ?>
        <section class="panel">
            <div class="panel__header">
                <div>
                    <h1>Импорт студентов</h1>
                    <p class="text-muted">Группа <?= e($groupNumber) ?></p>
                </div>
                <div class="panel__header-actions">
                    <a href="<?= e($importBackUrl) ?>" class="btn btn--ghost">← Назад</a>
                </div>
            </div>
            <?php require __DIR__ . '/admin_nav.php'; ?>
        </section>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($preview === []): ?>
    <section class="panel">
        <h2>Загрузка списка</h2>
        <p class="text-muted">
            Поддерживается Word (.docx) с нумерованным списком, например:
            «1. Иванов Иван Иванович». Фамилия, имя и отчество будут разделены автоматически.
        </p>

        <form method="post" enctype="multipart/form-data" class="form form--medium" action="?group_id=<?= $importGroupId ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="parse">
            <input type="hidden" name="source" value="file">
            <input type="hidden" name="group_id" value="<?= $importGroupId ?>">
            <div class="form__group">
                <label for="word_file">Файл Word (.docx)</label>
                <input type="file" id="word_file" name="word_file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Распознать список</button>
            </div>
        </form>

        <hr class="panel-divider">

        <h3 class="subsection-title">Или вставьте список текстом</h3>
        <form method="post" class="form form--medium" action="?group_id=<?= $importGroupId ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="parse">
            <input type="hidden" name="source" value="text">
            <input type="hidden" name="group_id" value="<?= $importGroupId ?>">
            <div class="form__group">
                <label for="list_text">Список ФИО</label>
                <textarea id="list_text" name="list_text" rows="8" placeholder="1. Иванов Иван Иванович&#10;2. Петров Пётр Петрович"></textarea>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Распознать текст</button>
            </div>
        </form>
    </section>
    <?php else: ?>
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2>Проверьте список</h2>
                <p class="text-muted">Найдено: <?= count($preview) ?>. При необходимости поправьте ФИО перед добавлением.</p>
            </div>
            <div class="panel__header-actions">
                <a href="?group_id=<?= $importGroupId ?>" class="btn btn--ghost">Загрузить заново</a>
            </div>
        </div>

        <form method="post" class="form" action="?group_id=<?= $importGroupId ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import">
            <input type="hidden" name="group_id" value="<?= $importGroupId ?>">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Фамилия</th>
                            <th>Имя</th>
                            <th>Отчество</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview as $index => $row): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <input type="text" name="last_name[]" value="<?= e($row['last_name']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="first_name[]" value="<?= e($row['first_name']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="middle_name[]" value="<?= e($row['middle_name']) ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary" data-page-loader>Добавить в группу</button>
                <a href="?group_id=<?= $importGroupId ?>" class="btn btn--ghost">Отмена</a>
            </div>
        </form>
    </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
