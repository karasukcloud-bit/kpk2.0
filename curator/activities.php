<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/student_activities.php';

require_curator_panel();

$ctx = resolve_curator_group_context(isset($_GET['group_id']) ? (int) $_GET['group_id'] : null);
$groups = $ctx['groups'];
$groupId = $ctx['group_id'];
$group = $ctx['group'];
$students = $group ? get_students_by_group($groupId) : [];
$error = flash_get('error') ?: $ctx['error'];
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $group !== null) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Ошибка безопасности. Обновите страницу и попробуйте снова.');
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'save_student_activities') {
            $studentId = (int) ($_POST['student_id'] ?? 0);
            $allowed = false;
            foreach ($students as $student) {
                if ((int) $student['id'] === $studentId) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                flash_set('error', 'Студент не принадлежит выбранной группе.');
            } else {
                $rawItems = $_POST['activities'] ?? [];
                if (!is_array($rawItems)) {
                    $rawItems = [];
                }
                $result = save_student_activities($studentId, $rawItems);
                if ($result['success']) {
                    flash_set('success', 'Занятость студента сохранена.');
                } else {
                    flash_set('error', $result['error'] ?? 'Не удалось сохранить.');
                }
            }
        } else {
            flash_set('error', 'Неизвестное действие.');
        }
    }

    header('Location: activities.php?group_id=' . $groupId);
    exit;
}

$activitiesReport = $students !== [] ? build_group_activities_report($students) : null;
$activitiesMap = $activitiesReport['map'] ?? [];

$pageTitle = 'Занятость студентов — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'activities';
$curatorGroupId = $groupId;
$curatorGroups = $groups;
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide curator-activities-page">
    <section class="panel no-print">
        <div class="panel__header">
            <div>
                <h1>Панель куратора</h1>
                <p class="text-muted">Занятость студентов во внеурочное время (кружки и секции)</p>
            </div>
            <?php if ($group && $students !== []): ?>
            <div class="panel__header-actions">
                <button type="button" class="btn btn--ghost" data-print-activities>Печать</button>
            </div>
            <?php endif; ?>
        </div>
        <?php require __DIR__ . '/../includes/curator_nav.php'; ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success no-print"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error no-print"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($groups === []): ?>
        <section class="panel">
            <p class="text-muted">Вам ещё не назначена группа. Обратитесь к администратору.</p>
        </section>
    <?php elseif ($group === null): ?>
        <section class="panel">
            <p class="text-muted">Выберите группу, чтобы заполнить занятость студентов.</p>
        </section>
    <?php else: ?>
        <section class="panel" data-activities-print-root>
            <div class="curator-activities-print-header" hidden>
                <h2>Занятость студентов во внеурочное время</h2>
                <p>
                    Группа <strong><?= e($group['number']) ?></strong>
                    · <?= e($group['specialty_name']) ?> (<?= e($group['specialty_code']) ?>)
                    · <?= e(date('d.m.Y')) ?>
                </p>
            </div>

            <p class="text-muted no-print">
                Группа <strong><?= e($group['number']) ?></strong>
                · студентов: <?= count($students) ?>
                <?php if ($activitiesReport): ?>
                    · с занятостью: <?= (int) $activitiesReport['with_activities'] ?>
                    · без занятости: <?= (int) $activitiesReport['without_activities'] ?>
                <?php endif; ?>
            </p>

            <?php if ($students === []): ?>
                <p class="text-muted">В группе пока нет студентов.</p>
            <?php else: ?>
                <?php if ($activitiesReport): ?>
                <div class="admin-stats-grid group-report__stats no-print">
                    <div class="admin-stat-card">
                        <div class="admin-stat-card__value"><?= (int) $activitiesReport['with_activities'] ?></div>
                        <div class="admin-stat-card__label">Заняты</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-card__value"><?= (int) $activitiesReport['without_activities'] ?></div>
                        <div class="admin-stat-card__label">Не заняты</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-card__value"><?= (int) $activitiesReport['club_count'] ?></div>
                        <div class="admin-stat-card__label">Кружков</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-card__value"><?= (int) $activitiesReport['section_count'] ?></div>
                        <div class="admin-stat-card__label">Секций</div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="table-wrap">
                    <table class="table curator-activities-table">
                        <thead>
                            <tr>
                                <th style="width:3rem">№</th>
                                <th>ФИО</th>
                                <th>Кружки и секции</th>
                                <th class="no-print" style="width:8rem">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                            <?php
                            $studentId = (int) $student['id'];
                            $list = $activitiesMap[$studentId] ?? [];
                            $summary = format_student_activities_summary($list);
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($student['full_name']) ?></td>
                                <td>
                                    <?php if ($list === []): ?>
                                        <span class="text-muted">Не указано</span>
                                    <?php else: ?>
                                        <ul class="curator-activities-list">
                                            <?php foreach ($list as $activity): ?>
                                            <li>
                                                <span class="curator-activity-badge curator-activity-badge--<?= e($activity['activity_type']) ?>">
                                                    <?= e(student_activity_type_label((string) $activity['activity_type'])) ?>
                                                </span>
                                                <?= e($activity['title']) ?>
                                                <?php if (trim((string) ($activity['place'] ?? '')) !== ''): ?>
                                                    <span class="text-muted">— <?= e($activity['place']) ?></span>
                                                <?php endif; ?>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                                <td class="no-print">
                                    <button
                                        type="button"
                                        class="btn btn--ghost btn--sm"
                                        data-activity-edit
                                        data-student-id="<?= $studentId ?>"
                                        data-student-name="<?= e($student['full_name']) ?>"
                                        data-activities="<?= e(json_encode($list, JSON_UNESCAPED_UNICODE)) ?>"
                                    >Изменить</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted table-hint no-print">
                    У одного студента можно указать несколько кружков и секций.
                </p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php if ($group && $students !== []): ?>
<div class="modal" data-activity-modal hidden>
    <div class="modal__backdrop" data-activity-modal-close></div>
    <div class="modal__dialog modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="activity-modal-title">
        <div class="modal__header">
            <h2 id="activity-modal-title">Занятость студента</h2>
            <button type="button" class="modal__close" data-activity-modal-close aria-label="Закрыть">&times;</button>
        </div>
        <form method="post" class="form" data-activity-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_student_activities">
            <input type="hidden" name="student_id" value="" data-activity-student-id>
            <p class="text-muted" data-activity-student-label></p>
            <div class="curator-activity-rows" data-activity-rows></div>
            <div class="form__actions" style="justify-content:space-between;margin-top:1rem">
                <button type="button" class="btn btn--ghost" data-activity-add-row>+ Добавить</button>
                <div style="display:flex;gap:0.5rem">
                    <button type="submit" class="btn btn--primary">Сохранить</button>
                    <button type="button" class="btn btn--ghost" data-activity-modal-close>Отмена</button>
                </div>
            </div>
        </form>
    </div>
</div>

<template id="activity-row-template">
    <div class="curator-activity-row" data-activity-row>
        <div class="form__group">
            <label>Тип</label>
            <select name="activities[__INDEX__][activity_type]">
                <option value="club">Кружок</option>
                <option value="section">Секция</option>
            </select>
        </div>
        <div class="form__group">
            <label>Название</label>
            <input type="text" name="activities[__INDEX__][title]" maxlength="255" placeholder="Например: Волейбол">
        </div>
        <div class="form__group">
            <label>Место / организация</label>
            <input type="text" name="activities[__INDEX__][place]" maxlength="255" placeholder="Необязательно">
        </div>
        <button type="button" class="btn btn--ghost btn--sm curator-activity-row__remove" data-activity-remove title="Удалить">×</button>
    </div>
</template>

<script>
(() => {
    const modal = document.querySelector('[data-activity-modal]');
    const rowsRoot = document.querySelector('[data-activity-rows]');
    const template = document.getElementById('activity-row-template');
    const studentIdInput = document.querySelector('[data-activity-student-id]');
    const studentLabel = document.querySelector('[data-activity-student-label]');
    const printBtn = document.querySelector('[data-print-activities]');
    if (!modal || !rowsRoot || !template) {
        return;
    }

    let rowIndex = 0;

    const addRow = (data = null) => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(rowIndex++));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        const row = wrap.firstElementChild;
        if (!row) {
            return;
        }
        if (data) {
            const typeSelect = row.querySelector('select');
            const titleInput = row.querySelector('input[name*="[title]"]');
            const placeInput = row.querySelector('input[name*="[place]"]');
            if (typeSelect) {
                typeSelect.value = data.activity_type === 'section' ? 'section' : 'club';
            }
            if (titleInput) {
                titleInput.value = data.title || '';
            }
            if (placeInput) {
                placeInput.value = data.place || '';
            }
        }
        rowsRoot.appendChild(row);
    };

    const openModal = (button) => {
        const studentId = button.dataset.studentId || '';
        const name = button.dataset.studentName || '';
        let activities = [];
        try {
            activities = JSON.parse(button.dataset.activities || '[]');
        } catch (e) {
            activities = [];
        }
        if (!Array.isArray(activities)) {
            activities = [];
        }

        rowIndex = 0;
        rowsRoot.innerHTML = '';
        if (studentIdInput) {
            studentIdInput.value = studentId;
        }
        if (studentLabel) {
            studentLabel.textContent = name;
        }
        if (activities.length === 0) {
            addRow();
        } else {
            activities.forEach((item) => addRow(item));
        }
        modal.hidden = false;
        document.body.classList.add('modal-open');
    };

    const closeModal = () => {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
    };

    document.querySelectorAll('[data-activity-edit]').forEach((button) => {
        button.addEventListener('click', () => openModal(button));
    });
    modal.querySelectorAll('[data-activity-modal-close]').forEach((node) => {
        node.addEventListener('click', closeModal);
    });
    document.querySelector('[data-activity-add-row]')?.addEventListener('click', () => addRow());
    rowsRoot.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-activity-remove]');
        if (!btn) {
            return;
        }
        const row = btn.closest('[data-activity-row]');
        if (row) {
            row.remove();
        }
        if (!rowsRoot.querySelector('[data-activity-row]')) {
            addRow();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    if (printBtn) {
        printBtn.addEventListener('click', () => {
            document.body.classList.add('curator-activities-printing');
            window.print();
            window.setTimeout(() => {
                document.body.classList.remove('curator-activities-printing');
            }, 300);
        });
    }
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
