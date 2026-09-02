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
        <h1>Добро пожаловать, <?= e(user_first_middle_name((string) ($user['full_name'] ?? ''))) ?>!</h1>
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

        <?php if (is_admin()): ?>
        <a href="admin/teachers.php" class="card card--link card--admin">
            <h2>Администрирование</h2>
            <p>Управление преподавателями, справочниками и учебными планами.</p>
            <span class="badge badge--admin">Открыть</span>
        </a>
        <?php endif; ?>

        <?php if (can_use_curator_panel()): ?>
        <a href="curator/group.php" class="card card--link card--curator">
            <h2>Панель куратора</h2>
            <p>Список группы, посещаемость и электронная ведомость.</p>
            <span class="badge badge--curator">Открыть</span>
        </a>
        <?php endif; ?>

        <?php if (can_use_teacher_panel()): ?>
        <a href="teacher/subjects.php" class="card card--link card--teacher">
            <h2>Панель преподавателя</h2>
            <p>Мои предметы, КТП и электронный журнал (в том числе при замещении).</p>
            <span class="badge badge--teacher">Открыть</span>
        </a>
        <?php endif; ?>

        <?php if (can_use_deputy_panel()): ?>
        <a href="deputy/curriculum.php" class="card card--link card--deputy">
            <h2>Панель завуча</h2>
            <p>Формирование учебного плана групп на учебный год.</p>
            <span class="badge badge--deputy">Открыть</span>
        </a>
        <?php endif; ?>

        <?php if (can_use_educator_panel()): ?>
        <a href="educator/daily_attendance.php" class="card card--link card--educator">
            <h2>Панель воспитателя</h2>
            <p>Сводка пропусков, пропуски по дням и информация по студентам.</p>
            <span class="badge badge--educator">Открыть</span>
        </a>
        <?php endif; ?>

        <a href="cabinet.php" class="card card--link card--cabinet">
            <h2>Личный кабинет</h2>
            <p>Профиль и настройки аккаунта.</p>
            <span class="badge badge--cabinet">Открыть</span>
        </a>
    </section>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
