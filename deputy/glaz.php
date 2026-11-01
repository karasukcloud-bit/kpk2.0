<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/glaz.php';
require_once __DIR__ . '/../includes/teachers.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/expelled.php';

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён. Требуются права завуча или администратора.');
}

$manualBrsAvailable = is_manual_brs_gradebook_active();
$source = (string) ($_GET['source'] ?? 'journal');
if ($source === 'manual_brs' && !$manualBrsAvailable) {
    $source = 'journal';
}
$source = $source === 'manual_brs' ? 'manual_brs' : 'journal';
$glazListUrl = 'glaz.php?source=' . urlencode($source);

if ($source === 'manual_brs') {
    require_once __DIR__ . '/../modules/manual_brs/bootstrap.php';
    require_once __DIR__ . '/../modules/manual_brs/includes/glaz.php';
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } elseif (($_POST['action'] ?? '') === 'expel_from_glaz') {
        $result = expel_student(
            (int) ($_POST['student_id'] ?? 0),
            (string) ($_POST['expulsion_order'] ?? ''),
            (string) ($_POST['expulsion_date'] ?? ''),
            (string) ($_POST['expulsion_reason'] ?? '')
        );
        if (!empty($result['success'])) {
            flash_set('success', 'Студент отчислен и убран из ГЛАЗ. Архивные оценки сохранены.');
            header('Location: ' . $glazListUrl);
            exit;
        }
        $error = $result['error'] ?? 'Не удалось отчислить студента.';
    } elseif (($_POST['action'] ?? '') === 'send_glaz') {
        $result = send_glaz_to_teachers($source);
        if (!empty($result['success'])) {
            $msg = $source === 'manual_brs'
                ? 'График ГЛАЗ (Ручное БРС) отправлен преподавателям: '
                : 'График ГЛАЗ (журнал) отправлен преподавателям: ';
            flash_set('success', $msg . (int) $result['sent'] . ' оповещений.');
            header('Location: ' . $glazListUrl);
            exit;
        }
        $error = $result['error'] ?? 'Не удалось отправить график.';
    }
}

if ($source === 'manual_brs') {
    $debts = get_all_manual_brs_academic_debts();
    $scheduleIndex = get_glaz_schedules_index('manual_brs');
    $glazSourceNote = 'Источник: модуль «Ручное БРС». Сейчас этот контур не для пересдач — список отдельно от журнала.';
    $sendConfirm = 'Отправить график ГЛАЗ (Ручное БРС) всем преподавателям?';
} else {
    $debts = get_all_academic_debts();
    $scheduleIndex = get_glaz_schedules_index('journal');
    $glazSourceNote = 'Источник: электронный журнал / архив ведомостей. Список неуспевающих для текущего графика ликвидации. Незавершённые семестры не включаются.';
    $sendConfirm = 'Отправить график ГЛАЗ (журнал) всем преподавателям?';
}

$glazSaveUrl = 'glaz_save.php';
$glazShowSourceColumn = false;
$tableGroups = build_glaz_table_groups($debts, $scheduleIndex['schedules']);
$teachers = get_all_teachers();
$organization = get_organization();
$glazEditable = true;
$glazCanExpel = $source === 'journal';
$glazHighlightGroupNumbers = [];
$glazHighlightTeacherId = null;
$glazShowLegend = false;
$success = flash_get('success');

$pageTitle = 'ГЛАЗ — Панель завуча';
$showHeader = true;
$basePath = '../';
$currentDeputyTab = 'glaz';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide glaz-page">
    <section class="panel glaz-no-print">
        <div class="panel__header">
            <div>
                <h1>Панель завуча</h1>
                <p class="text-muted">График ликвидации академической задолженности (ГЛАЗ)</p>
            </div>
            <div class="panel__actions">
                <?php if ($tableGroups !== []): ?>
                <form method="post" class="form-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="send_glaz">
                    <button
                        type="submit"
                        class="btn btn--primary btn--sm"
                        onclick="return confirm(<?= json_encode($sendConfirm, JSON_UNESCAPED_UNICODE) ?>);"
                    >Отправить преподавателям</button>
                </form>
                <button type="button" class="btn btn--ghost" data-glaz-print>Печать</button>
                <?php endif; ?>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/deputy_nav.php'; ?>
    </section>

    <?php if ($manualBrsAvailable): ?>
    <section class="panel glaz-no-print">
        <div class="summary-report-filters">
            <?php
            $sourceFilterCurrent = $source;
            $sourceFilterUrl = 'glaz.php';
            $sourceFilterQuery = [];
            require __DIR__ . '/../includes/deputy_source_filter.php';
            ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert--success glaz-no-print"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error glaz-no-print"><?= e($error) ?></div>
    <?php endif; ?>

        <?php require __DIR__ . '/../includes/glaz/table.php'; ?>
</div>

<?php if ($glazCanExpel): ?>
<div class="modal" id="glaz-expel-modal" hidden>
    <div class="modal__backdrop" data-glaz-expel-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="glaz-expel-title">
        <div class="modal__header">
            <h2 id="glaz-expel-title">Отчисление студента</h2>
            <button type="button" class="modal__close" data-glaz-expel-close aria-label="Закрыть">×</button>
        </div>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="expel_from_glaz">
            <input type="hidden" name="student_id" value="" data-glaz-expel-student-id>
            <p class="text-muted" data-glaz-expel-student-name></p>
            <p class="text-muted">
                Для импортированных / архивных студентов: оценки в архиве останутся,
                из ГЛАЗ студент исчезнет, карточка появится в «Отчисленные».
            </p>
            <div class="form__group">
                <label for="glaz_expulsion_order">Номер приказа</label>
                <input type="text" id="glaz_expulsion_order" name="expulsion_order" required maxlength="100">
            </div>
            <div class="form__group">
                <label for="glaz_expulsion_date">Дата отчисления</label>
                <input type="date" id="glaz_expulsion_date" name="expulsion_date" required value="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="form__group">
                <label for="glaz_expulsion_reason">Причина отчисления</label>
                <textarea id="glaz_expulsion_reason" name="expulsion_reason" rows="3" required></textarea>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--danger">Подтвердить отчисление</button>
                <button type="button" class="btn btn--ghost" data-glaz-expel-close>Отмена</button>
            </div>
        </form>
    </div>
</div>
<script>
(() => {
    const modal = document.getElementById('glaz-expel-modal');
    if (!modal) {
        return;
    }
    const idInput = modal.querySelector('[data-glaz-expel-student-id]');
    const nameEl = modal.querySelector('[data-glaz-expel-student-name]');
    const open = (btn) => {
        if (idInput) {
            idInput.value = btn.dataset.studentId || '';
        }
        if (nameEl) {
            nameEl.textContent = btn.dataset.studentName || '';
        }
        modal.hidden = false;
        document.body.classList.add('modal-open');
    };
    const close = () => {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
    };
    document.querySelectorAll('[data-glaz-expel-open]').forEach((btn) => {
        btn.addEventListener('click', () => open(btn));
    });
    modal.querySelectorAll('[data-glaz-expel-close]').forEach((btn) => {
        btn.addEventListener('click', close);
    });
})();
</script>
<?php endif; ?>

<template id="glaz-commission-row-template">
    <?php
    $selectedId = 0;
    $withRemove = true;
    require __DIR__ . '/../includes/glaz_commission_row.php';
    ?>
</template>

<?php require __DIR__ . '/../includes/footer.php'; ?>
