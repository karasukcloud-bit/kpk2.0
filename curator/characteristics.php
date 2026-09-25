<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/characteristic.php';

require_curator_panel();
ensure_student_characteristics_schema();

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

$isPreviewAjax = $_SERVER['REQUEST_METHOD'] === 'POST'
    && (string) ($_POST['action'] ?? '') === 'preview_ajax';

$student = null;
foreach ($students as $row) {
    if ((int) $row['id'] === $studentId) {
        $student = $row;
        break;
    }
}
if ($student === null) {
    $studentId = 0;
    if ($isPreviewAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Студент не выбран.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$data = null;
$preview = '';
$previewHtml = '';
$gender = null;
$hasSavedCharacteristic = false;

if ($student !== null && $group !== null) {
    try {
        $defaults = characteristic_default_data($student, $group, $user ?: null);
        $savedPayload = get_student_characteristic_payload($studentId);
        $hasSavedCharacteristic = $savedPayload !== null;
        $defaults = characteristic_apply_saved_payload($defaults, $savedPayload);
        $gender = (string) ($student['gender'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? null)) {
                $action = (string) ($_POST['action'] ?? '');
                if ($action === 'preview_ajax') {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(403);
                    echo json_encode(['ok' => false, 'error' => 'Ошибка безопасности. Обновите страницу.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
                $data = $defaults;
                $previewHtml = build_characteristic_preview_html($data);
            } else {
                $data = characteristic_merge_post($defaults, $_POST);
                $data['student_id'] = (string) $studentId;
                $data['gender'] = $gender;
                $action = (string) ($_POST['action'] ?? 'preview');
                if ($action === 'preview_ajax') {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'ok' => true,
                        'preview' => build_characteristic_text($data),
                        'preview_html' => build_characteristic_preview_html($data),
                        'full_name_genitive' => (string) ($data['full_name_genitive'] ?? ''),
                        'first_name_genitive' => (string) ($data['first_name_genitive'] ?? ''),
                        'first_name' => (string) ($data['first_name'] ?? ''),
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if ($action === 'save') {
                    $saveResult = save_student_characteristic(
                        $studentId,
                        $data,
                        $user ? (int) ($user['id'] ?? 0) ?: null : null
                    );
                    if ($saveResult['success']) {
                        flash_set('success', 'Характеристика сохранена.');
                    } else {
                        flash_set('error', $saveResult['error'] ?? 'Не удалось сохранить характеристику.');
                    }
                    header('Location: characteristics.php?group_id=' . $groupId . '&student_id=' . $studentId);
                    exit;
                }
                if ($action === 'download') {
                    try {
                        download_characteristic_docx($data);
                    } catch (Throwable $e) {
                        $error = 'Не удалось сформировать файл Word.';
                    }
                }
                $preview = build_characteristic_text($data);
                $previewHtml = build_characteristic_preview_html($data);
            }
        } else {
            $data = $defaults;
            $preview = build_characteristic_text($data);
            $previewHtml = build_characteristic_preview_html($data);
        }
    } catch (Throwable $e) {
        if ($isPreviewAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Не удалось сформировать характеристику.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $error = 'Не удалось сформировать характеристику. Проверьте данные студента и попробуйте снова.';
        $data = null;
        $preview = '';
        $previewHtml = '';
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
    <section class="panel no-print">
        <div class="panel__header">
            <div>
                <h1>Панель куратора</h1>
                <p class="text-muted">Генератор характеристик студентов</p>
            </div>
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
        <section class="panel no-print">
            <p class="text-muted">Вам ещё не назначена группа. Обратитесь к администратору.</p>
        </section>
    <?php elseif ($group === null): ?>
        <section class="panel no-print">
            <p class="text-muted">Выберите группу, чтобы сформировать характеристику.</p>
        </section>
    <?php elseif ($students === []): ?>
        <section class="panel no-print">
            <p class="text-muted">В группе нет студентов.</p>
        </section>
    <?php else: ?>
        <section class="panel no-print">
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

            <section class="panel no-print">
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
                    <?php $fieldText('full_name_genitive', 'ФИО в родительном падеже', $data, 'Подставляется автоматически (можно поправить вручную), напр. «Иванова Ивана Ивановича»'); ?>
                    <?php $fieldText('first_name', 'Имя в тексте', $data); ?>
                    <?php $fieldText('first_name_genitive', 'Имя в родительном падеже', $data, 'Автосклонение, напр. «к достоинствам Ивана»'); ?>
                    <?php $fieldText('birth_date', 'Дата рождения', $data); ?>
                    <?php $fieldText('course', 'Курс', $data); ?>
                    <?php $fieldText('org_name', 'Организация (полное название)', $data); ?>
                    <?php $fieldText('college_in', 'Фраза «В … колледже»', $data); ?>
                    <?php $fieldText('specialty', 'Специальность', $data, 'Например: «Физическая культура»'); ?>
                </div>
            </section>

            <section class="panel no-print">
                <h2>Адрес и семья</h2>
                <div class="form__grid form__grid--2">
                    <?php $fieldArea('address', 'Адрес проживания', $data, 2); ?>
                    <?php $fieldSelect('family_kind', 'Состав семьи (в тексте)', characteristic_option_list('family_kind'), $data); ?>
                    <?php $fieldArea('family_sentence', 'Предложение о семье', $data, 3, 'Полная фраза: «Александр воспитывается в неполной семье, мать: …»'); ?>
                </div>
            </section>

            <section class="panel no-print">
                <h2>Учёба и качества</h2>
                <div class="form__grid form__grid--2">
                    <?php $fieldSelect('dominant_grade', 'Преобладающая отметка', characteristic_option_list('dominant_grade'), $data); ?>
                    <div class="form__group form__group--full">
                        <label for="favorite_subjects">Интерес к предметам</label>
                        <?php
                        $curriculumSubjects = [];
                        try {
                            $curriculumSubjects = characteristic_group_subject_names((int) $groupId);
                        } catch (Throwable $e) {
                            $curriculumSubjects = [];
                        }
                        $favoriteValue = (string) ($data['favorite_subjects'] ?? '');
                        $favoriteParts = [];
                        foreach (preg_split('/\s*,\s*/u', $favoriteValue) ?: [] as $part) {
                            $part = trim((string) $part);
                            if ($part === '') {
                                continue;
                            }
                            $favoriteParts[] = function_exists('mb_strtolower')
                                ? mb_strtolower($part, 'UTF-8')
                                : strtolower($part);
                        }
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
                    <?php $fieldArea('events', 'Мероприятия', $data, 3, 'Конкретные мероприятия, в которых участвовал студент. Если пусто — абзац не попадёт в текст.'); ?>
                    <?php $fieldArea('contests', 'Конкурсы', $data, 3, 'Конкурсы, олимпиады, соревнования. Если пусто — абзац не попадёт в текст.'); ?>
                    <?php $fieldArea('achievements', 'Достижения / результаты', $data, 2, 'Грамоты, призовые места и т.п. Если пусто — не добавляется.'); ?>
                    <?php $fieldArea('additional_info', 'Дополнительная информация', $data, 4, 'Любые сведения, которые куратор хочет отразить в характеристике.'); ?>
                    <?php $fieldSelect('merits', 'Основные достоинства', characteristic_option_list('merits'), $data); ?>
                </div>
            </section>

            <section class="panel no-print">
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
                    <button type="submit" name="action" value="save" class="btn btn--primary">Сохранить</button>
                    <button type="button" class="btn btn--ghost" data-print-characteristic>Печать</button>
                    <button type="submit" name="action" value="download" class="btn btn--ghost">Скачать Word</button>
                </div>
                <?php if ($hasSavedCharacteristic): ?>
                <p class="text-muted" style="margin-top:0.75rem">Загружена сохранённая характеристика студента.</p>
                <?php endif; ?>
            </section>

            <section class="panel characteristic-preview-panel">
                <h2 class="no-print">Предпросмотр</h2>
                <p class="text-muted characteristic-preview-hint no-print">Текст обновляется автоматически при изменении полей. Вид совпадает с документом Word.</p>
                <div class="characteristic-preview" data-characteristic-preview><?= $previewHtml ?></div>
            </section>
        </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
(function () {
    const form = document.getElementById('characteristic-form');
    if (!form) return;

    const previewEl = form.querySelector('[data-characteristic-preview]');
    let previewTimer = null;
    let previewSeq = 0;

    const refreshPreview = () => {
        if (!previewEl) return;
        const seq = ++previewSeq;
        const body = new FormData(form);
        body.set('action', 'preview_ajax');

        fetch(form.getAttribute('action') || window.location.href, {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then((response) => response.json())
            .then((payload) => {
                if (seq !== previewSeq || !payload || !payload.ok) return;
                if (typeof payload.preview_html === 'string') {
                    previewEl.innerHTML = payload.preview_html;
                } else if (typeof payload.preview === 'string') {
                    previewEl.textContent = payload.preview;
                }
                if (typeof payload.full_name_genitive === 'string') {
                    const genInput = form.querySelector('[name="full_name_genitive"]');
                    const genCustom = form.querySelector('[name="full_name_genitive_custom"]');
                    const genManual = genCustom && genCustom.value.trim() !== '';
                    if (genInput && !genManual && document.activeElement !== genInput) {
                        genInput.value = payload.full_name_genitive;
                    }
                }
                if (typeof payload.first_name_genitive === 'string') {
                    const firstGen = form.querySelector('[name="first_name_genitive"]');
                    const firstGenCustom = form.querySelector('[name="first_name_genitive_custom"]');
                    const firstManual = firstGenCustom && firstGenCustom.value.trim() !== '';
                    if (firstGen && !firstManual && document.activeElement !== firstGen) {
                        firstGen.value = payload.first_name_genitive;
                    }
                }
                if (typeof payload.first_name === 'string') {
                    const firstInput = form.querySelector('[name="first_name"]');
                    if (firstInput && document.activeElement !== firstInput && !firstInput.value.trim()) {
                        firstInput.value = payload.first_name;
                    }
                }
            })
            .catch(() => {});
    };

    const schedulePreview = () => {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(refreshPreview, 280);
    };

    form.querySelectorAll('.characteristic-custom-input').forEach((input) => {
        input.addEventListener('input', () => {
            const targetName = input.getAttribute('data-target');
            if (!targetName || !input.value.trim()) {
                schedulePreview();
                return;
            }
            const select = form.querySelector('select[name="' + targetName + '"]');
            if (!select) {
                schedulePreview();
                return;
            }
            let option = Array.from(select.options).find((o) => o.value === input.value.trim());
            if (!option) {
                option = document.createElement('option');
                option.value = input.value.trim();
                option.textContent = input.value.trim();
                select.appendChild(option);
            }
            option.selected = true;
            schedulePreview();
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
            schedulePreview();
        };
        picker.addEventListener('change', (event) => {
            if (event.target && event.target.matches('[data-favorite-subject]')) {
                syncFromChecks();
            }
        });
    }

    form.addEventListener('input', schedulePreview);
    form.addEventListener('change', schedulePreview);

    const printBtn = form.querySelector('[data-print-characteristic]');
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            document.body.classList.add('characteristic-printing');
            window.print();
            window.setTimeout(() => {
                document.body.classList.remove('characteristic-printing');
            }, 300);
        });
    }
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
