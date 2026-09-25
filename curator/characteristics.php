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
    bool $allowCustom = true,
    array $labels = []
): void {
    $value = (string) ($data[$name] ?? '');
    $known = array_key_exists($value, $options) || in_array($value, $options, true);
    $isAssoc = $options !== [] && array_keys($options) !== range(0, count($options) - 1);
    ?>
    <div class="form__group">
        <label for="<?= e($name) ?>"><?= e($label) ?></label>
        <select id="<?= e($name) ?>" name="<?= e($name) ?>">
            <?php if ($isAssoc): ?>
                <?php foreach ($options as $optValue => $optLabel): ?>
                <option value="<?= e((string) $optValue) ?>"<?= $value === (string) $optValue ? ' selected' : '' ?>>
                    <?= e((string) $optLabel) ?>
                </option>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($options as $option): ?>
                <?php
                $optValue = (string) $option;
                $optLabel = $labels[$optValue] ?? ($optValue !== '' ? $optValue : '— не указывать —');
                ?>
                <option value="<?= e($optValue) ?>"<?= $value === $optValue ? ' selected' : '' ?>>
                    <?= e($optLabel) ?>
                </option>
                <?php endforeach; ?>
            <?php endif; ?>
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

$fieldArea = static function (string $name, string $label, array $data, int $rows = 2, string $hint = ''): void {
    ?>
    <div class="form__group">
        <label for="<?= e($name) ?>"><?= e($label) ?></label>
        <textarea id="<?= e($name) ?>" name="<?= e($name) ?>" rows="<?= $rows ?>"><?= e((string) ($data[$name] ?? '')) ?></textarea>
        <?php if ($hint !== ''): ?>
        <p class="form__hint text-muted"><?= e($hint) ?></p>
        <?php endif; ?>
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
                <h2>Шапка</h2>
                <div class="form__grid form__grid--2">
                    <?php $fieldSelect(
                        'status',
                        'Статус',
                        ['student' => 'Студент курса', 'graduate' => 'Выпускник'],
                        $data,
                        false
                    ); ?>
                    <?php $fieldText('full_name', 'ФИО (именительный)', $data); ?>
                    <?php $fieldText('full_name_genitive', 'ФИО в родительном падеже', $data, 'Для заголовка: «Тахтамира Александра Александровича»'); ?>
                    <?php $fieldText('first_name', 'Имя в тексте', $data); ?>
                    <?php $fieldText('first_name_genitive', 'Имя в родительном падеже', $data, 'Для фразы «к достоинствам Александра»'); ?>
                    <?php $fieldText('birth_date', 'Дата рождения', $data); ?>
                    <?php $fieldText('course', 'Курс', $data); ?>
                    <?php $fieldText('org_name', 'Организация (полное название)', $data); ?>
                    <?php $fieldText('college_in', 'Фраза «В … колледже»', $data); ?>
                    <?php $fieldText('specialty', 'Специальность', $data, 'Например: «Физическая культура»'); ?>
                </div>
            </section>

            <section class="panel">
                <h2>Адрес и семья</h2>
                <div class="form__grid form__grid--2">
                    <?php $fieldArea('address', 'Адрес проживания', $data, 2); ?>
                    <?php $fieldSelect('family_kind', 'Состав семьи (в тексте)', characteristic_option_list('family_kind'), $data); ?>
                    <?php $fieldArea('family_sentence', 'Предложение о семье', $data, 3, 'Полная фраза: «Александр воспитывается в неполной семье, мать: …»'); ?>
                </div>
            </section>

            <section class="panel">
                <h2>Учёба и качества</h2>
                <div class="form__grid form__grid--2">
                    <?php $fieldSelect('dominant_grade', 'Преобладающая отметка', characteristic_option_list('dominant_grade'), $data); ?>
                    <div class="form__group form__group--full">
                        <label for="favorite_subjects">Интерес к предметам</label>
                        <?php
                        $curriculumSubjects = characteristic_group_subject_names((int) $groupId);
                        $favoriteValue = (string) ($data['favorite_subjects'] ?? '');
                        $favoriteParts = array_values(array_filter(array_map(
                            static fn (string $part): string => mb_strtolower(trim($part)),
                            preg_split('/\s*,\s*/u', $favoriteValue) ?: []
                        )));
                        ?>
                        <?php if ($curriculumSubjects !== []): ?>
                        <div class="characteristic-subjects" data-favorite-subjects-picker>
                            <?php foreach ($curriculumSubjects as $subjectName): ?>
                            <?php
                            $subjectLower = mb_strtolower($subjectName);
                            $checked = in_array($subjectLower, $favoriteParts, true);
                            ?>
                            <label class="checkbox-label characteristic-subjects__item">
                                <input
                                    type="checkbox"
                                    value="<?= e($subjectName) ?>"
                                    data-favorite-subject
                                    <?= $checked ? 'checked' : '' ?>
                                >
                                <?= e($subjectName) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="form__hint text-muted">Отметьте предметы из учебного плана группы — они подставятся в поле ниже.</p>
                        <?php else: ?>
                        <p class="form__hint text-muted">В учебном плане группы предметы пока не найдены — введите вручную.</p>
                        <?php endif; ?>
                        <textarea
                            id="favorite_subjects"
                            name="favorite_subjects"
                            rows="2"
                            data-favorite-subjects-input
                        ><?= e($favoriteValue) ?></textarea>
                    </div>
                    <?php $fieldSelect('self_esteem', 'Самооценка', characteristic_option_list('self_esteem'), $data); ?>
                    <?php $fieldSelect('motivation', 'Мотив учения', characteristic_option_list('motivation'), $data); ?>
                    <?php $fieldText('sports_section', 'Спортивная секция', $data, 'Из занятости студента. Если пусто — фраза не добавляется.'); ?>
                    <?php $fieldText('club', 'Кружок', $data, 'Из занятости студента. Если пусто — фраза не добавляется.'); ?>
                    <?php $fieldArea('achievements', 'Достижения / конкурсы', $data, 3, 'Заполняется вручную. Если пусто — абзац в текст не попадёт.'); ?>
                    <?php $fieldSelect('merits', 'Основные достоинства', characteristic_option_list('merits'), $data); ?>
                </div>
            </section>

            <section class="panel">
                <h2>Дисциплина и подписи</h2>
                <div class="form__grid form__grid--2">
                    <?php $fieldSelect('discipline', 'Правила распорядка', characteristic_option_list('discipline'), $data); ?>
                    <?php $fieldSelect('penalties', 'Взыскания', characteristic_option_list('penalties'), $data); ?>
                    <?php $fieldSelect(
                        'bad_habits',
                        'Вредные привычки',
                        characteristic_option_list('bad_habits'),
                        $data,
                        true,
                        ['' => '— не указывать —']
                    ); ?>
                    <?php $fieldText('acquainted_line', 'Строка «Ознакомлен …»', $data, 'Оставьте пустым, если не нужна'); ?>
                    <?php $fieldText('director_name', 'Директор (Фамилия И. О. или полная строка)', $data, 'Необязательно'); ?>
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

    const picker = form.querySelector('[data-favorite-subjects-picker]');
    const favoriteInput = form.querySelector('[data-favorite-subjects-input]');
    if (picker && favoriteInput) {
        const syncFromChecks = () => {
            const selected = Array.from(picker.querySelectorAll('[data-favorite-subject]:checked'))
                .map((input) => String(input.value || '').trim().toLowerCase())
                .filter(Boolean);
            favoriteInput.value = selected.join(', ');
        };
        picker.addEventListener('change', (event) => {
            if (event.target && event.target.matches('[data-favorite-subject]')) {
                syncFromChecks();
            }
        });
    }
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
