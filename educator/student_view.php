<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/educator/all_students_filters.php';

require_educator_panel();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$student = get_student_by_id($id);
$listUrl = educator_all_students_list_url();

if ($student === null || is_student_removed($student)) {
    flash_set('error', 'Студент не найден.');
    header('Location: ' . $listUrl);
    exit;
}

$group = get_group_by_id((int) $student['group_id']);
if ($group === null) {
    flash_set('error', 'Группа студента не найдена.');
    header('Location: ' . $listUrl);
    exit;
}

$displayOrDash = static function (?string $value): string {
    $value = trim((string) $value);

    return $value !== '' ? $value : '—';
};

$pageTitle = 'Карточка студента — Панель воспитателя';
$showHeader = true;
$basePath = '../';
$currentEducatorTab = 'all_students';
require __DIR__ . '/../includes/header.php';
$error = flash_get('error');
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1><?= e($student['full_name']) ?></h1>
                <p class="text-muted">Группа <?= e($group['number']) ?></p>
            </div>
            <a href="<?= e($listUrl) ?>" class="btn btn--ghost">← К списку студентов</a>
        </div>
        <?php require __DIR__ . '/../includes/educator_nav.php'; ?>
    </section>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <div class="student-card-view">
            <dl class="profile-list">
                <dt>ФИО студента</dt>
                <dd><?= e($displayOrDash($student['full_name'] ?? '')) ?></dd>
                <dt>Группа</dt>
                <dd><?= e($group['number']) ?><?php if (($group['specialty_name'] ?? '') !== ''): ?> · <?= e($group['specialty_name']) ?><?php endif; ?></dd>
                <dt>Телефон студента</dt>
                <dd><?= e($displayOrDash($student['phone'] ?? '')) ?></dd>
                <dt>СНИЛС</dt>
                <dd><?= e($displayOrDash($student['snils'] ?? '')) ?></dd>
                <dt>Дата рождения</dt>
                <dd><?= e(format_student_birth_date(isset($student['birth_date']) ? (string) $student['birth_date'] : null)) ?></dd>
                <dt>Пол</dt>
                <dd><?= e(student_gender_label(isset($student['gender']) ? (string) $student['gender'] : null)) ?></dd>
                <dt><?= e(student_parent_field_label('mother', 'name')) ?></dt>
                <dd><?= e($displayOrDash($student['mother_name'] ?? '')) ?></dd>
                <dt><?= e(student_parent_field_label('mother', 'phone')) ?></dt>
                <dd><?= e($displayOrDash($student['mother_phone'] ?? '')) ?></dd>
                <dt><?= e(student_parent_field_label('mother', 'workplace')) ?></dt>
                <dd><?= e($displayOrDash($student['mother_workplace'] ?? '')) ?></dd>
                <dt><?= e(student_parent_field_label('mother', 'education')) ?></dt>
                <dd><?= e(student_education_label($student['mother_education'] ?? null)) ?></dd>
                <dt><?= e(student_parent_field_label('father', 'name')) ?></dt>
                <dd><?= e($displayOrDash($student['father_name'] ?? '')) ?></dd>
                <dt><?= e(student_parent_field_label('father', 'phone')) ?></dt>
                <dd><?= e($displayOrDash($student['father_phone'] ?? '')) ?></dd>
                <dt><?= e(student_parent_field_label('father', 'workplace')) ?></dt>
                <dd><?= e($displayOrDash($student['father_workplace'] ?? '')) ?></dd>
                <dt><?= e(student_parent_field_label('father', 'education')) ?></dt>
                <dd><?= e(student_education_label($student['father_education'] ?? null)) ?></dd>
                <dt>Адрес по прописке</dt>
                <dd><?= e(format_student_registered_address($student)) ?></dd>
                <dt>Область / край</dt>
                <dd><?= e($displayOrDash($student['address_region'] ?? '')) ?></dd>
                <dt>Район / округ</dt>
                <dd><?= e($displayOrDash($student['address_district'] ?? '')) ?></dd>
                <dt>Населённый пункт</dt>
                <dd><?= e($displayOrDash($student['address_locality'] ?? '')) ?></dd>
                <dt>Улица</dt>
                <dd><?= e($displayOrDash($student['address_street'] ?? '')) ?></dd>
                <dt>Дом</dt>
                <dd><?= e($displayOrDash($student['address_house'] ?? '')) ?></dd>
                <dt>Фактический адрес</dt>
                <dd><?= e($displayOrDash($student['address_actual'] ?? '')) ?></dd>
                <dt>Состав семьи</dt>
                <dd><?= e(student_family_type_label(isset($student['family_type']) ? (string) $student['family_type'] : null)) ?></dd>
                <dt>Братьев/сестёр младше 18</dt>
                <dd><?= e((string) (int) ($student['siblings_under_18'] ?? 0)) ?></dd>
                <dt>Место проживания</dt>
                <dd><?= e(student_residence_type_label(isset($student['residence_type']) ? (string) $student['residence_type'] : null)) ?></dd>
                <dt>Иногородний</dt>
                <dd><?= !empty($student['is_nonresident']) ? 'Да' : 'Нет' ?></dd>
                <dt>Малообеспеченная семья</dt>
                <dd><?= !empty($student['is_low_income']) ? 'Да' : 'Нет' ?></dd>
                <dt>Без попечительства родителей</dt>
                <dd><?= !empty($student['without_parental_care']) ? 'Да' : 'Нет' ?></dd>
            </dl>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
