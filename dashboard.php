<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

require_login();

if (is_student_user()) {
    redirect_to('student/cabinet.php');
}

$user = current_user();

$pageTitle = 'Главная';
$showHeader = true;
require __DIR__ . '/includes/header.php';
?>

<div class="dashboard">
    <section class="panel">
        <h1>Добро пожаловать, <?= e($user['full_name']) ?>!</h1>
        <p class="text-muted">
            <?php if (is_admin()): ?>
            Вы вошли как <strong>Администратор</strong>.
            <?php else: ?>
            Ваши роли: <strong><?= e(display_user_roles($user)) ?></strong>.
            <?php endif; ?>
            Система контроля успеваемости и посещаемости студентов.
        </p>
    </section>

    <section class="cards">
        <article class="card">
            <h2>Успеваемость</h2>
            <p>Учёт оценок и академической успеваемости студентов.</p>
            <span class="badge badge--soon">Скоро</span>
        </article>

        <article class="card">
            <h2>Посещаемость</h2>
            <p>Контроль посещаемости занятий по группам.</p>
            <span class="badge badge--soon">Скоро</span>
        </article>

        <?php if (is_admin()): ?>
        <a href="admin/teachers.php" class="card card--admin card--link">
            <h2>Администрирование</h2>
            <p>Управление преподавателями, справочниками и учебными планами.</p>
            <span class="badge badge--admin">Открыть</span>
        </a>
        <?php endif; ?>

        <?php if (can_use_curator_panel()): ?>
        <a href="curator/group.php" class="card card--curator card--link">
            <h2>Панель куратора</h2>
            <p>Список группы, посещаемость и электронная ведомость.</p>
            <span class="badge badge--curator">Открыть</span>
        </a>
        <?php endif; ?>

        <?php if (can_use_teacher_panel()): ?>
        <a href="teacher/subjects.php" class="card card--link">
            <h2>Панель преподавателя</h2>
            <p>Мои предметы, КТП и электронный журнал (в том числе при замещении).</p>
            <span class="badge badge--role badge--teacher">Открыть</span>
        </a>
        <?php endif; ?>

        <?php if (can_use_deputy_panel()): ?>
        <a href="deputy/curriculum.php" class="card card--deputy card--link">
            <h2>Панель завуча</h2>
            <p>Формирование учебного плана групп на учебный год.</p>
            <span class="badge badge--deputy">Открыть</span>
        </a>
        <?php endif; ?>

        <?php if (can_use_educator_panel()): ?>
        <a href="educator/attendance.php" class="card card--link">
            <h2>Панель воспитателя</h2>
            <p>Раздел в разработке.</p>
            <span class="badge badge--role badge--educator">Открыть</span>
        </a>
        <?php endif; ?>

        <a href="cabinet.php" class="card card--link">
            <h2>Личный кабинет</h2>
            <p>Профиль и настройки аккаунта.</p>
            <span class="badge badge--role">Открыть</span>
        </a>
    </section>

    <section class="panel panel--info">
        <h2>Ваш профиль</h2>
        <dl class="profile-list">
            <dt>ФИО</dt>
            <dd><?= e($user['full_name']) ?></dd>
            <dt>Телефон (логин)</dt>
            <dd><?= e(($user['phone'] ?? '') !== '' ? $user['phone'] : '—') ?></dd>
            <?php
            $profileEmail = display_user_email((string) ($user['email'] ?? ''));
            if ($profileEmail !== ''):
            ?>
            <dt>Email</dt>
            <dd><?= e($profileEmail) ?></dd>
            <?php endif; ?>
            <?php if (!empty($user['position'])): ?>
            <dt>Должность</dt>
            <dd><?= e($user['position']) ?></dd>
            <?php endif; ?>
            <dt>Роли</dt>
            <dd>
                <?php if (is_admin()): ?>
                    <?= e(role_label('admin')) ?>
                <?php else: ?>
                    <?= render_staff_roles_badges($user['staff_roles'] ?? []) ?>
                <?php endif; ?>
            </dd>
        </dl>
        <p><a href="cabinet.php" class="btn btn--ghost btn--sm">Редактировать в кабинете</a></p>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
