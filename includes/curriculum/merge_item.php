<?php

declare(strict_types=1);

$curriculumPanel = $curriculumPanel ?? 'admin';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../curriculum.php';
require_once __DIR__ . '/../curriculum_item_merge.php';
require_once __DIR__ . '/../organization.php';

require_curriculum_manager();

$academicYear = normalize_academic_year($_GET['year'] ?? $_POST['year'] ?? get_default_academic_year())
    ?? get_default_academic_year();
$groupId = (int) ($_GET['group_id'] ?? $_POST['group_id'] ?? 0);
$fromId = (int) ($_GET['from_id'] ?? $_POST['from_id'] ?? 0);
$toId = (int) ($_GET['to_id'] ?? $_POST['to_id'] ?? 0);
$deleteSource = !empty($_POST['delete_source']);

$error = null;
$success = null;
$preview = null;

$plan = $groupId > 0 ? get_curriculum_plan($groupId, $academicYear) : null;
if ($groupId > 0 && $plan === null) {
    $error = 'Учебный план группы на этот год не найден.';
}

$items = $plan ? get_curriculum_plan_items_for_merge((int) $plan['id']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу.';
    } elseif (($_POST['action'] ?? '') === 'preview') {
        $preview = preview_curriculum_item_merge($fromId, $toId);
        if (empty($preview['ok'])) {
            $error = (string) ($preview['error'] ?? 'Ошибка предпросмотра.');
            $preview = null;
        }
    } elseif (($_POST['action'] ?? '') === 'merge') {
        $preview = preview_curriculum_item_merge($fromId, $toId);
        if (empty($preview['ok'])) {
            $error = (string) ($preview['error'] ?? 'Ошибка проверки.');
            $preview = null;
        } else {
            $result = merge_curriculum_item_data($fromId, $toId, $deleteSource);
            if (!empty($result['success'])) {
                $lessons = (int) ($result['moved']['journal_lessons'] ?? 0);
                $msg = 'Перенесено уроков журнала: ' . $lessons . '.';
                if (!empty($result['deleted_source'])) {
                    $msg .= ' Исходный предмет удалён из плана.';
                }
                flash_set('success', $msg);
                $redirect = 'curriculum_merge_item.php?year=' . urlencode($academicYear)
                    . '&group_id=' . $groupId
                    . '&to_id=' . $toId;
                header('Location: ' . $redirect);
                exit;
            }
            $error = $result['error'] ?? 'Не удалось выполнить перенос.';
        }
    }
}

if ($preview === null && $fromId > 0 && $toId > 0 && $error === null) {
    $check = preview_curriculum_item_merge($fromId, $toId);
    if (!empty($check['ok'])) {
        $preview = $check;
    }
}

$success = $success ?? flash_get('success');
$groups = get_all_groups();
$yearOptions = get_academic_year_options($academicYear);

$pageTitle = 'Перенос журнала предмета';
$showHeader = true;
$basePath = '../';
if ($curriculumPanel === 'admin') {
    $currentAdminTab = 'curriculum';
} else {
    $currentDeputyTab = 'curriculum';
}

require __DIR__ . '/../header.php';
?>

<div class="dashboard">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Перенос журнала на другой предмет плана</h1>
                <p class="text-muted">
                    Для дубля МДК: перенести даты, оценки и «Н» со старого предмета на МДК внутри ПМ.
                    Целевой журнал должен быть пустым.
                </p>
            </div>
            <a href="curriculum.php?year=<?= e(urlencode($academicYear)) ?>" class="btn btn--ghost">← Учебный план</a>
        </div>
        <?php
        if ($curriculumPanel === 'admin') {
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
        <form method="get" class="form">
            <div class="form__row">
                <div class="form__group">
                    <label for="year">Учебный год</label>
                    <select id="year" name="year">
                        <?php foreach ($yearOptions as $year): ?>
                        <option value="<?= e($year) ?>"<?= $year === $academicYear ? ' selected' : '' ?>><?= e($year) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form__group">
                    <label for="group_id">Группа</label>
                    <select id="group_id" name="group_id" required>
                        <option value="">— Выберите —</option>
                        <?php foreach ($groups as $g): ?>
                        <option value="<?= (int) $g['id'] ?>"<?= (int) $g['id'] === $groupId ? ' selected' : '' ?>>
                            <?= e((string) $g['number']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form__group form__group--align-end">
                    <label class="form__label">&nbsp;</label>
                    <button type="submit" class="btn btn--ghost">Открыть план</button>
                </div>
            </div>
        </form>
    </section>

    <?php if ($plan && $items !== []): ?>
    <section class="panel">
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="year" value="<?= e($academicYear) ?>">
            <input type="hidden" name="group_id" value="<?= $groupId ?>">
            <input type="hidden" name="action" value="preview">

            <div class="form__group">
                <label for="from_id">Откуда (старый предмет)</label>
                <select id="from_id" name="from_id" required>
                    <option value="">— Выберите —</option>
                    <?php foreach ($items as $item): ?>
                    <option value="<?= (int) $item['id'] ?>"<?= (int) $item['id'] === $fromId ? ' selected' : '' ?>>
                        <?= e((string) $item['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form__group">
                <label for="to_id">Куда (МДК через ПМ)</label>
                <select id="to_id" name="to_id" required>
                    <option value="">— Выберите —</option>
                    <?php foreach ($items as $item): ?>
                    <option value="<?= (int) $item['id'] ?>"<?= (int) $item['id'] === $toId ? ' selected' : '' ?>>
                        <?= e((string) $item['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--ghost">Предпросмотр</button>
            </div>
        </form>
    </section>
    <?php elseif ($groupId > 0 && $plan): ?>
    <section class="panel">
        <p class="text-muted">В плане нет предметов.</p>
    </section>
    <?php endif; ?>

    <?php if ($preview): ?>
    <section class="panel">
        <h2>Предпросмотр</h2>
        <p>
            <strong>Из:</strong> <?= e((string) $preview['from']['label']) ?><br>
            <strong>В:</strong> <?= e((string) $preview['to']['label']) ?>
        </p>
        <ul class="text-muted">
            <li>Уроков журнала: <?= (int) $preview['counts']['journal_lessons'] ?></li>
            <li>Оценок / Н в журнале: <?= (int) $preview['counts']['journal_grades'] ?></li>
            <li>Итогов ведомости (grade_entries): <?= (int) $preview['counts']['grade_entries'] ?></li>
            <li>Тем КТП: <?= (int) $preview['counts']['ktp_topics'] ?></li>
            <?php if ((int) $preview['counts']['manual_brs_sheets'] > 0): ?>
            <li>Листов ручного БРС: <?= (int) $preview['counts']['manual_brs_sheets'] ?></li>
            <?php endif; ?>
            <li>Уроков на целевом предмете сейчас: <?= (int) $preview['target_lessons'] ?></li>
        </ul>

        <?php if ((int) $preview['target_lessons'] > 0): ?>
            <div class="alert alert--error">Целевой журнал не пуст — перенос заблокирован.</div>
        <?php else: ?>
        <form method="post" class="form" onsubmit="return confirm('Перенести журнал на целевой предмет?');">
            <?= csrf_field() ?>
            <input type="hidden" name="year" value="<?= e($academicYear) ?>">
            <input type="hidden" name="group_id" value="<?= $groupId ?>">
            <input type="hidden" name="from_id" value="<?= $fromId ?>">
            <input type="hidden" name="to_id" value="<?= $toId ?>">
            <input type="hidden" name="action" value="merge">
            <div class="form__group">
                <label>
                    <input type="checkbox" name="delete_source" value="1" checked>
                    Удалить старый предмет из учебного плана после переноса
                </label>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Выполнить перенос</button>
            </div>
        </form>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
