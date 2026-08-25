<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_stats.php';
require_once __DIR__ . '/../includes/curriculum.php';

require_admin();

$stats = build_admin_app_statistics();
$period = $stats['period'];

$pageTitle = 'Статистика — Администрирование';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'stats';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель администратора</h1>
                <p class="text-muted">Сводная статистика по приложению</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/admin_nav.php'; ?>
    </section>

    <section class="panel">
        <h2>Текущий период</h2>
        <p class="text-muted">
            Учебный год <?= e($period['academic_year']) ?>
            · <?= e(semester_label($period['semester'])) ?>
        </p>
        <div class="admin-stats-grid">
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) $stats['users']['total'] ?></div>
                <div class="admin-stat-card__label">Пользователей</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) $stats['organization']['students'] ?></div>
                <div class="admin-stat-card__label">Студентов</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) $stats['organization']['groups'] ?></div>
                <div class="admin-stat-card__label">Групп</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) $stats['organization']['subjects'] ?></div>
                <div class="admin-stat-card__label">Предметов</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) $stats['organization']['specialties'] ?></div>
                <div class="admin-stat-card__label">Специальностей</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__value"><?= (int) $stats['curriculum']['items_active_year'] ?></div>
                <div class="admin-stat-card__label">Позиций УП (год)</div>
            </div>
        </div>
    </section>

    <section class="panel">
        <h2>Пользователи</h2>
        <div class="admin-stats-columns">
            <dl class="profile-list">
                <dt>Всего учётных записей</dt>
                <dd><?= (int) $stats['users']['total'] ?></dd>
                <dt>Активных</dt>
                <dd><?= (int) $stats['users']['active'] ?></dd>
                <dt>Заблокированных</dt>
                <dd><?= (int) $stats['users']['inactive'] ?></dd>
            </dl>
            <dl class="profile-list">
                <dt>Администраторы</dt>
                <dd><?= (int) $stats['users']['by_account_role']['admin'] ?></dd>
                <dt>Сотрудники (teacher)</dt>
                <dd><?= (int) $stats['users']['by_account_role']['teacher'] ?></dd>
                <dt>Студенты (учётки)</dt>
                <dd><?= (int) $stats['users']['by_account_role']['student'] ?></dd>
            </dl>
            <dl class="profile-list">
                <?php foreach (staff_role_labels() as $role => $label): ?>
                <dt><?= e($label) ?></dt>
                <dd><?= (int) ($stats['users']['by_staff_role'][$role] ?? 0) ?></dd>
                <?php endforeach; ?>
            </dl>
        </div>
        <p class="text-muted">
            Роли сотрудников считаются по назначениям: у одного пользователя может быть несколько ролей.
        </p>
    </section>

    <section class="panel">
        <h2>Организация и контингент</h2>
        <div class="admin-stats-columns">
            <dl class="profile-list">
                <dt>Специальности</dt>
                <dd><?= (int) $stats['organization']['specialties'] ?></dd>
                <dt>Группы</dt>
                <dd><?= (int) $stats['organization']['groups'] ?></dd>
                <dt>Группы с куратором</dt>
                <dd><?= (int) $stats['organization']['groups_with_curator'] ?></dd>
            </dl>
            <dl class="profile-list">
                <dt>Студенты в группах</dt>
                <dd><?= (int) $stats['organization']['students'] ?></dd>
                <dt>Студенты с учётной записью</dt>
                <dd><?= (int) $stats['organization']['students_with_account'] ?></dd>
                <dt>Без учётной записи</dt>
                <dd><?= max(0, (int) $stats['organization']['students'] - (int) $stats['organization']['students_with_account']) ?></dd>
            </dl>
            <dl class="profile-list">
                <dt>Предметы (справочник)</dt>
                <dd><?= (int) $stats['organization']['subjects'] ?></dd>
                <dt>Учебные планы</dt>
                <dd><?= (int) $stats['curriculum']['plans'] ?></dd>
                <dt>Позиции учебных планов</dt>
                <dd><?= (int) $stats['curriculum']['items'] ?></dd>
                <dt>Позиции за текущий год</dt>
                <dd><?= (int) $stats['curriculum']['items_active_year'] ?></dd>
                <dt>Темы КТП</dt>
                <dd><?= (int) $stats['curriculum']['ktp_topics'] ?></dd>
            </dl>
        </div>
    </section>

    <section class="panel">
        <h2>Успеваемость и посещаемость</h2>
        <div class="admin-stats-columns">
            <dl class="profile-list">
                <dt>Оценки в ведомостях</dt>
                <dd><?= (int) $stats['learning']['grade_entries'] ?></dd>
                <dt>Уроки в журналах</dt>
                <dd><?= (int) $stats['learning']['journal_lessons'] ?></dd>
                <dt>Отметки в журналах</dt>
                <dd><?= (int) $stats['learning']['journal_grades'] ?></dd>
            </dl>
            <dl class="profile-list">
                <dt>Дни учёта пропусков</dt>
                <dd><?= (int) $stats['learning']['attendance_days'] ?></dd>
                <dt>Дни за текущий учебный год</dt>
                <dd><?= (int) $stats['learning']['attendance_days_year'] ?></dd>
                <dt>Записи пропусков</dt>
                <dd><?= (int) $stats['learning']['attendance_entries'] ?></dd>
            </dl>
        </div>
    </section>

    <section class="panel">
        <h2>Архив, уведомления и ГЛАЗ</h2>
        <div class="admin-stats-columns">
            <dl class="profile-list">
                <dt>Периодов в архиве</dt>
                <dd><?= (int) $stats['archive']['periods'] ?></dd>
                <dt>Архивы ведомостей</dt>
                <dd><?= (int) $stats['archive']['gradebooks'] ?></dd>
                <dt>Архивы журналов</dt>
                <dd><?= (int) $stats['archive']['journals'] ?></dd>
            </dl>
            <dl class="profile-list">
                <dt>Уведомлений</dt>
                <dd><?= (int) $stats['other']['notifications'] ?></dd>
                <dt>Непрочитанных личных</dt>
                <dd><?= (int) $stats['other']['notifications_unread_personal'] ?></dd>
            </dl>
            <dl class="profile-list">
                <dt>Записей ГЛАЗ</dt>
                <dd><?= (int) $stats['other']['glaz_schedules'] ?></dd>
                <dt>С назначенной датой</dt>
                <dd><?= (int) $stats['other']['glaz_scheduled'] ?></dd>
                <dt>С комиссией</dt>
                <dd><?= (int) $stats['other']['glaz_with_commission'] ?></dd>
            </dl>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
