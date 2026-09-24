<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/characteristic.php';

require_curator_panel();

$ctx = resolve_curator_group_context(isset($_GET['group_id']) ? (int) $_GET['group_id'] : null);
$groups = $ctx['groups'];
$groupId = $ctx['group_id'];
$group = $ctx['group'];
$students = $group ? get_students_by_group($groupId) : [];
$error = flash_get('error') ?: $ctx['error'];
$success = flash_get('success');
$user = current_user();

$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int) ($_POST['student_id'] ?? $studentId);
}

$student = null;
foreach ($students as $row) {
    if ((int) $row['id'] === $studentId) {
        $student = $row;
        break;
    }
}
if ($student === null) {
    $studentId = 0;
}

$data = null;
$preview = '';
$gender = null;

if ($student !== null && $group !== null) {
    $defaults = characteristic_default_data($student, $group, $user ?: null);
    $gender = (string) ($student['gender'] ?? '');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
            $data = $defaults;
        } else {
            $data = characteristic_merge_post($defaults, $_POST);
            $data['student_id'] = (string) $studentId;
            $data['gender'] = $gender;
            $action = (string) ($_POST['action'] ?? 'preview');
            if ($action === 'download') {
                try {
                    download_characteristic_docx($data);
                } catch (Throwable $e) {
                    $error = 'Не удалось сформировать файл Word.';
                }
            }
            $preview = build_characteristic_text($data);
        }
    } else {
        $data = $defaults;
        $preview = build_characteristic_text($data);
    }
}

$pageTitle = 'Характеристики — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'characteristics';
$curatorGroupId = $groupId;
$curatorGroups = $groups;
require __DIR__ . '/../includes/header.php';

$fieldSelect = static function (
    string $name,
    string $label,
    array $options,
    array $data,
    bool $allowCustom = true
): void {
    $value = (string) ($data[$name] ?? '');
    $known = in_array($value, $options, true);
    ?>
    <div class="form__group">
        <label for="<?= e($name) ?>"><?= e($label) ?></label>
        <select id="<?= e($name) ?>" name="<?= e($name) ?>">
            <?php foreach ($options as $option): ?>
            <option value="<?= e($option) ?>"<?= $value === $option ? ' selected' : '' ?>>
                <?= e($option) ?>
            </option>
            <?php endforeach; ?>
            <?php if ($allowCustom && $value !== '' && !$known): ?>
            <option value="<?= e($value) ?>" selected><?= e($value) ?> (своё)</option>
            <?php endif; ?>
        </select>
        <?php if ($allowCustom): ?>
        <input type="text" name="<?= e($name) ?>_custom" class="characteristic-custom-input"
               placeholder="Или введите свой вариант" data-target="<?= e($name) ?>"
               value="<?= !$known && $value !== '' ? e($value) : '' ?>">
        <?php endif; ?>
    </div>
    <?php
};

$fieldText = static function (string $name, string $label, array $data, string $hint = ''): void {
    ?>
    <div class="form__group">
        <label for="<?= e($name) ?>"><?= e($label) ?></label>
        <input type="text" id="<?= e($name) ?>" name="<?= e($name) ?>"
               value="<?= e((string) ($data[$name] ?? '')) ?>">
        <?php if ($hint !== ''): ?>
        <p class="form__hint text-muted"><?= e($hint) ?></p>
        <?php endif; ?>
    </div>
    <?php
};

$fieldArea = static function (string $name, string $label, array $data, int $rows = 2): void {
    ?>
    <div class="form__group">
        <label for="<?= e($name) ?>"><?= e($label) ?></label>
        <textarea id="<?= e($name) ?>" name="<?= e($name) ?>" rows="<?= $rows ?>"><?= e((string) ($data[$name] ?? '')) ?></textarea>
    </div>
    <?php
};
?>

<div class="dashboard dashboard--wide curator-characteristics-page">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель куратора</h1>
                <p class="text-muted">Генератор характеристик студентов</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/curator_nav.php'; ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($groups === []): ?>
        <section class="panel">
            <p class="text-muted">Вам ещё не назначена группа. Обратитесь к администратору.</p>
        </section>
    <?php elseif ($group === null): ?>
        <section class="panel">
            <p class="text-muted">Выберите группу, чтобы сформировать характеристику.</p>
        </section>
    <?php elseif ($students === []): ?>
        <section class="panel">
            <p class="text-muted">В группе нет студентов.</p>
        </section>
    <?php else: ?>
        <section class="panel">
            <form method="get" class="form form--filter">
                <input type="hidden" name="group_id" value="<?= (int) $groupId ?>">
                <div class="form__row form__row--filter">
                    <div class="form__group">
                        <label for="student_id_select">Студент</label>
                        <select id="student_id_select" name="student_id" onchange="this.form.submit()">
                            <option value="">— Выберите студента —</option>
                            <?php foreach ($students as $item): ?>
                            <option value="<?= (int) $item['id'] ?>"<?= (int) $item['id'] === $studentId ? ' selected' : '' ?>>
                                <?= e($item['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </section>

        <?php if ($student !== null && $data !== null): ?>
        <form method="post" class="characteristic-form" id="characteristic-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="student_id" value="<?= (int) $studentId ?>">
            <input type="hidden" name="group_id" value="<?= (int) $groupId ?>">

            <section class="panel">
                <h2>1. Общие сведения</h2>
                <div class="form__grid form__grid--2">
                    <?php $fieldText('full_name', 'ФИО', $data); ?>
                    <?php $fieldText('birth_date', 'Дата рождения', $data); ?>
                    <?php $fieldText('course', 'Курс', $data); ?>
                    <?php $fieldText('group_number', 'Группа', $data); ?>
                    <?php $fieldText('org_name', 'Образовательная организация', $data); ?>
                    <?php $fieldText('specialty', 'Специальность / профессия', $data); ?>
                    <?php $fieldText('study_start', 'Обучается с (месяц, год)', $data); ?>
                    <?php $fieldSelect('study_form', 'Форма обучения', characteristic_option_list('study_form', $gender), $data); ?>
                    <?php $fieldSelect('funding', 'Основа обучения', characteristic_option_list('funding', $gender), $data); ?>
                    <?php $fieldSelect('general_trait', 'Проявил(а) себя как', characteristic_option_list('general_trait', $gender), $data); ?>
                </div>
            </section>

            <section class="panel">
                <h2>2. Учебная деятельность</h2>
                <div class="form__grid form__grid--2">
                    <?php $fieldText('average_grade', 'Средний балл', $data, 'Подставляется из электронной ведомости, если есть оценки'); ?>
                    <?php $fieldSelect('study_level', 'Учится на', characteristic_option_list('study_level', $gender), $data); ?>
                    <?php $fieldArea('favorite_subjects', 'Интерес к дисциплинам', $data); ?>
                    <?php $fieldSelect('attendance', 'Посещаемость', characteristic_option_list('attendance', $gender), $data); ?>
                    <?php $fieldSelect('assignments', 'Отношение к заданиям', characteristic_option_list('assignments', $gender), $data); ?>
                    <?php $fieldSelect('deadlines', 'Сроки сдачи работ', characteristic_option_list('deadlines', $gender), $data); ?>
                    <?php $fieldText('debts', 'Академические задолженности', $data); ?>
                </div>
            </section>

            <section class="panel">
                <h2>3. Практическая подготовка</h2>
                <div class="form__grid form__grid--2">
                    <?php $fieldText('practice_place', 'Место практики', $data); ?>
                    <?php $fieldText('practice_grade', 'Оценка за практику', $data); ?>
                    <?php $fieldArea('practice_review', 'Отзыв руководителя', $data); ?>
                    <?php $fieldArea('practice_skills', 'Профессиональные навыки', $data); ?>
                    <?php $fieldText('competitions', 'Олимпиады / конкурсы', $data); ?>
                    <?php $fieldText('competition_result', 'Результат участия', $data); ?>
                </div>
            </section>

            <section class="panel">
                <h2>4. Личностные качества</h2>
                <div class="form__grid form__grid--2">
                    <?php $fieldSelect('personal_traits', 'Характеризуется как', characteristic_option_list('personal_traits', $gender), $data); ?>
                    <?php $fieldSelect('skills', 'Умеет', characteristic_option_list('skills', $gender), $data); ?>
                    <?php $fieldSelect('relations', 'Отношения с окружающими', characteristic_option_list('relations', $gender), $data); ?>
                    <?php $fieldSelect('conflicts', 'В конфликтных ситуациях', characteristic_option_list('conflicts', $gender), $data); ?>
                </div>
            </section>

            <section class="panel">
                <h2>5–7. Внеучебная деятельность, дисциплина, заключение</h2>
                <div class="form__grid form__grid--2">
                    <?php $fieldArea('extracurricular', 'Участие во внеучебной деятельности', $data); ?>
                    <?php $fieldArea('achievements', 'Достижения', $data); ?>
                    <?php $fieldSelect('discipline_rules', 'Правила распорядка', characteristic_option_list('discipline_rules', $gender), $data); ?>
                    <?php $fieldSelect('penalties', 'Дисциплинарные взыскания', characteristic_option_list('penalties', $gender), $data); ?>
                    <?php $fieldText('violations', 'Нарушения', $data); ?>
                    <?php $fieldSelect('conclusion_side', 'Зарекомендовал(а) себя с … стороны', characteristic_option_list('conclusion_side', $gender), $data); ?>
                    <?php $fieldSelect('purpose', 'Характеристика выдана для', characteristic_option_list('purpose', $gender), $data); ?>
                    <?php $fieldText('purpose_extra', 'Уточнение места предоставления', $data, 'Например, название организации'); ?>
                    <?php $fieldText('director_name', 'Директор (Фамилия И. О.)', $data); ?>
                    <?php $fieldText('curator_name', 'Куратор (Фамилия И. О.)', $data); ?>
                </div>

                <div class="form__actions" style="margin-top:1rem">
                    <button type="submit" name="action" value="preview" class="btn btn--ghost">Обновить текст</button>
                    <button type="submit" name="action" value="download" class="btn btn--primary">Скачать Word</button>
                </div>
            </section>

            <section class="panel">
                <h2>Предпросмотр</h2>
                <pre class="characteristic-preview"><?= e($preview) ?></pre>
            </section>
        </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
(function () {
    const form = document.getElementById('characteristic-form');
    if (!form) return;
    form.querySelectorAll('.characteristic-custom-input').forEach((input) => {
        input.addEventListener('input', () => {
            const targetName = input.getAttribute('data-target');
            if (!targetName || !input.value.trim()) return;
            const select = form.querySelector('select[name="' + targetName + '"]');
            if (!select) return;
            let option = Array.from(select.options).find((o) => o.value === input.value.trim());
            if (!option) {
                option = document.createElement('option');
                option.value = input.value.trim();
                option.textContent = input.value.trim();
                select.appendChild(option);
            }
            option.selected = true;
        });
    });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
