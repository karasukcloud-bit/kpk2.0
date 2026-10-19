<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/teachers.php';

require_admin();

$teachers = get_all_teachers();
$success = flash_get('success');
$error = flash_get('error');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
$loginUrl = ($https ? 'https' : 'http') . '://'
    . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . app_base_path() . 'login.php';

$pageTitle = 'Преподаватели — Администрирование';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'teachers';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide teachers-page">
    <section class="panel teachers-no-print">
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
        <div class="alert alert--success teachers-no-print"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert--error teachers-no-print"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel__header teachers-no-print">
            <div>
                <h2>Список преподавателей</h2>
            </div>
            <?php if (!empty($teachers)): ?>
                <button type="button" class="btn btn--ghost" data-teachers-print>
                    Печать с данными доступа
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($teachers)): ?>
            <p class="text-muted teachers-no-print">Преподаватели пока не зарегистрированы.</p>
        <?php else: ?>
            <div class="form__group teachers-search teachers-no-print">
                <label for="teachers_search">Поиск по ФИО</label>
                <input
                    type="search"
                    id="teachers_search"
                    placeholder="Начните вводить ФИО…"
                    autocomplete="off"
                    data-teachers-search
                >
            </div>
            <div class="table-wrap teachers-no-print">
                <table
                    class="table"
                    data-teachers-table
                    data-teachers-credentials-url="teacher_credentials_sent.php"
                    data-csrf="<?= e(csrf_token()) ?>"
                >
                    <thead>
                        <tr>
                            <th class="table__num-col">№</th>
                            <th>ФИО</th>
                            <th>Телефон</th>
                            <th>Роли</th>
                            <th>Курируемые группы</th>
                            <th>Специальность</th>
                            <th class="table__check-col" title="Отметка об отправке логина и пароля">Данные отправлены</th>
                            <th class="table__share-col">Поделиться</th>
                            <th class="table__status-col">Статус</th>
                            <th>Дата регистрации</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $index => $teacher): ?>
                        <?php
                        $shareText = teacher_credentials_share_text($teacher, $loginUrl);
                        ?>
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
                            <td><?= e($teacher['specialty_head_label'] ?? '—') ?></td>
                            <td class="table__check-col" onclick="event.stopPropagation()">
                                <label class="checkbox-label checkbox-label--table" title="Регистрационные данные отправлены">
                                    <input
                                        type="checkbox"
                                        data-teacher-credentials-sent
                                        data-teacher-id="<?= (int) $teacher['id'] ?>"
                                        <?= (int) ($teacher['auth_credentials_sent'] ?? 0) ? 'checked' : '' ?>
                                        aria-label="Данные для авторизации отправлены: <?= e($teacher['full_name']) ?>"
                                    >
                                </label>
                            </td>
                            <td class="table__share-col" onclick="event.stopPropagation()">
                                <button
                                    type="button"
                                    class="btn btn--ghost btn--small"
                                    data-teacher-share-max
                                    data-share-text="<?= e(rawurlencode($shareText)) ?>"
                                    title="Отправить данные в MAX (временно)"
                                >
                                    MAX
                                </button>
                            </td>
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
            <p class="text-muted teachers-search-empty teachers-no-print" data-teachers-empty hidden>Ничего не найдено по запросу.</p>
            <p class="text-muted table-hint teachers-no-print">
                Нажмите на строку, чтобы редактировать. «MAX» — временно: отправка логина/пароля в мессенджер.
                Печать — список с регистрационными данными.
            </p>

            <div class="teachers-print-only" hidden>
                <div class="teachers-print-header">
                    <h2>СПО-ПРОГРЕСС — регистрационные данные преподавателей</h2>
                    <p><?= e($loginUrl) ?></p>
                    <p>Дата печати: <?= e(date('d.m.Y H:i')) ?></p>
                </div>
                <table class="table teachers-print-table">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>ФИО</th>
                            <th>Телефон</th>
                            <th>Логин</th>
                            <th>Пароль</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $index => $teacher): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($teacher['full_name']) ?></td>
                            <td><?= e(($teacher['phone'] ?? '') !== '' ? $teacher['phone'] : '—') ?></td>
                            <td><?php
                                $printLogin = teacher_display_login($teacher);
                                echo e($printLogin !== '' ? $printLogin : '—');
                            ?></td>
                            <td><?= e(($teacher['password_plain'] ?? '') !== '' ? $teacher['password_plain'] : '—') ?></td>
                            <td><?= (int) $teacher['is_active'] ? 'Активен' : 'Неактивен' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
