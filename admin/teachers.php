<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/teachers.php';

require_admin();

$teachers = get_all_teachers();
$success = flash_get('success');
$error = flash_get('error');

$pageTitle = 'Преподаватели — Администрирование';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'teachers';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель администратора</h1>
                <p class="text-muted">Управление данными системы</p>
            </div>
            <a href="teacher_create.php" class="btn btn--primary">+ Добавить преподавателя</a>
        </div>

        <?php require __DIR__ . '/../includes/admin_nav.php'; ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <h2>Список преподавателей</h2>

        <?php if (empty($teachers)): ?>
            <p class="text-muted">Преподаватели пока не зарегистрированы.</p>
        <?php else: ?>
            <div class="form__group teachers-search">
                <label for="teachers_search">Поиск по ФИО</label>
                <input
                    type="search"
                    id="teachers_search"
                    placeholder="Начните вводить ФИО…"
                    autocomplete="off"
                    data-teachers-search
                >
            </div>
            <div class="table-wrap">
                <table class="table" data-teachers-table>
                    <thead>
                        <tr>
                            <th class="table__num-col">№</th>
                            <th>ФИО</th>
                            <th>Телефон</th>
                            <th>Роли</th>
                            <th>Группа куратора</th>
                            <th class="table__status-col">Статус</th>
                            <th>Дата регистрации</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $index => $teacher): ?>
                        <tr
                            class="table__row--clickable"
                            onclick="location.href='teacher_edit.php?id=<?= (int) $teacher['id'] ?>'"
                            data-teacher-row
                            data-teacher-name="<?= e(mb_strtolower($teacher['full_name'])) ?>"
                        >
                            <td data-teacher-num><?= $index + 1 ?></td>
                            <td><?= e($teacher['full_name']) ?></td>
                            <td><?= e(($teacher['phone'] ?? '') !== '' ? $teacher['phone'] : '—') ?></td>
                            <td class="roles-cell"><?= render_staff_roles_badges($teacher['staff_roles'] ?? []) ?></td>
                            <td><?= e($teacher['curator_group_number'] ?? '—') ?></td>
                            <td class="table__status-col">
                                <?php if ((int) $teacher['is_active']): ?>
                                    <span class="status-icon status-icon--active" title="Активен" aria-label="Активен">
                                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                                            <circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.15"/>
                                            <path fill="currentColor" d="M10.2 15.6 6.8 12.2l1.4-1.4 2 2 5-5 1.4 1.4z"/>
                                        </svg>
                                    </span>
                                <?php else: ?>
                                    <span class="status-icon status-icon--inactive" title="Неактивен" aria-label="Неактивен">
                                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                                            <circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.15"/>
                                            <path fill="currentColor" d="M15.5 8.5 13 11l2.5 2.5-1.4 1.4L11.6 12.4 9.1 14.9 7.7 13.5 10.2 11 7.7 8.5 9.1 7.1l2.5 2.5 2.5-2.5z"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(format_date($teacher['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted teachers-search-empty" data-teachers-empty hidden>Ничего не найдено по запросу.</p>
            <p class="text-muted table-hint">Нажмите на строку, чтобы редактировать данные преподавателя.</p>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
