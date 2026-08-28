<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/grading.php';
require_once __DIR__ . '/../includes/attendance.php';

require_admin();

$error = null;
$editSection = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_organization') {
            $result = save_organization($_POST);
            $editSection = 'organization';
        } elseif ($action === 'save_gradebook_period') {
            $result = save_active_gradebook_period(
                $_POST['active_academic_year'] ?? '',
                $_POST['active_semester'] ?? '1'
            );
            $editSection = 'period';
        } elseif ($action === 'extend_academic_years') {
            $result = extend_academic_year_horizon(10);
            $editSection = 'period';
        } elseif ($action === 'save_grading_config') {
            $result = save_grading_config($_POST);
            $editSection = 'grading';
        } elseif ($action === 'add_specialty') {
            $result = create_specialty($_POST['name'] ?? '', $_POST['code'] ?? '');
        } elseif ($action === 'add_group') {
            $labels = group_labels_from_input($_POST);
            $result = create_group(
                $_POST['number'] ?? '',
                (int) ($_POST['specialty_id'] ?? 0),
                null,
                $labels['is_professionality'],
                $labels['is_general_education']
            );
        } elseif ($action === 'add_attendance_reason') {
            $result = create_attendance_reason($_POST['reason_name'] ?? '');
        } elseif ($action === 'update_attendance_reason') {
            $result = update_attendance_reason(
                (int) ($_POST['reason_id'] ?? 0),
                $_POST['reason_name'] ?? '',
                isset($_POST['is_active'])
            );
        } elseif ($action === 'delete_attendance_reason') {
            $result = delete_attendance_reason((int) ($_POST['reason_id'] ?? 0));
        } else {
            $result = ['success' => false, 'error' => 'Неизвестное действие.'];
        }

        if ($result['success']) {
            if ($action === 'save_organization') {
                $successMessage = 'Информация об организации сохранена.';
            } elseif ($action === 'save_gradebook_period') {
                $successMessage = 'Период ведомости сохранён.';
            } elseif ($action === 'extend_academic_years') {
                $maxLabel = (string) ($result['max_year_label'] ?? '');
                $successMessage = $maxLabel !== ''
                    ? 'Список учебных годов расширен до ' . $maxLabel . '.'
                    : 'Список учебных годов расширен на 10 лет.';
            } elseif ($action === 'save_grading_config') {
                $successMessage = 'Настройки системы оценивания сохранены. Итоги в журналах пересчитаются автоматически.';
            } elseif ($action === 'add_specialty') {
                $successMessage = 'Специальность добавлена.';
            } elseif ($action === 'add_group') {
                $successMessage = 'Группа добавлена.';
            } elseif ($action === 'add_attendance_reason') {
                $successMessage = 'Причина пропуска добавлена.';
            } elseif ($action === 'update_attendance_reason') {
                $successMessage = 'Причина пропуска обновлена.';
            } elseif ($action === 'delete_attendance_reason') {
                $successMessage = isset($result['deactivated']) && $result['deactivated']
                    ? 'Причина отключена, так как используется в журнале.'
                    : 'Причина пропуска удалена.';
            } else {
                $successMessage = 'Изменения сохранены.';
            }
            flash_set('success', $successMessage);
            $redirect = 'info.php';
            if (in_array($action, ['add_attendance_reason', 'update_attendance_reason', 'delete_attendance_reason'], true)) {
                $redirect .= '#attendance-reasons';
            } elseif ($action === 'add_group') {
                $redirect .= '#groups';
            } elseif ($action === 'add_specialty') {
                $redirect .= '#specialties';
            } elseif (in_array($action, ['save_gradebook_period', 'extend_academic_years'], true)) {
                $redirect .= '#period';
            }
            header('Location: ' . $redirect);
            exit;
        }

        $error = $result['error'];
    }
}

$organization = get_organization();
$gradebookPeriod = get_active_gradebook_period();
$gradingConfig = get_grading_config();
$yearOptions = get_academic_year_options($gradebookPeriod['academic_year']);
$specialties = get_all_specialties();
$groups = get_all_groups();
$attendanceReasons = get_attendance_reasons(false);
$success = flash_get('success');
$flashError = flash_get('error');
$error = $error ?? $flashError;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_organization') {
    $organization = array_merge($organization, [
        'name'            => $_POST['name'] ?? '',
        'address'         => $_POST['address'] ?? '',
        'phone'           => $_POST['phone'] ?? '',
        'email'           => $_POST['email'] ?? '',
        'additional_info' => $_POST['additional_info'] ?? '',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_gradebook_period') {
    $gradebookPeriod = [
        'academic_year' => $_POST['active_academic_year'] ?? $gradebookPeriod['academic_year'],
        'semester' => $_POST['active_semester'] ?? $gradebookPeriod['semester'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_grading_config') {
    $gradingConfig = [
        'system' => (($_POST['system'] ?? '') === 'brs') ? 'brs' : 'traditional',
        'brs' => [
            'weight_current' => (float) ($_POST['weight_current'] ?? $gradingConfig['brs']['weight_current']),
            'weight_control' => (float) ($_POST['weight_control'] ?? $gradingConfig['brs']['weight_control']),
            'weight_attendance' => (float) ($_POST['weight_attendance'] ?? $gradingConfig['brs']['weight_attendance']),
            'weight_punctuality' => (float) ($_POST['weight_punctuality'] ?? $gradingConfig['brs']['weight_punctuality']),
            'weight_activity' => (float) ($_POST['weight_activity'] ?? $gradingConfig['brs']['weight_activity']),
            'scale_3' => (float) ($_POST['scale_3'] ?? $gradingConfig['brs']['scale_3']),
            'scale_4' => (float) ($_POST['scale_4'] ?? $gradingConfig['brs']['scale_4']),
            'scale_5' => (float) ($_POST['scale_5'] ?? $gradingConfig['brs']['scale_5']),
        ],
    ];
}

$pageTitle = 'Информация — Администрирование';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'info';
require __DIR__ . '/../includes/header.php';

$dash = static function (?string $value): string {
    $value = trim((string) $value);

    return $value !== '' ? $value : '—';
};

$gradingSystemLabel = $gradingConfig['system'] === 'brs'
    ? 'Балльно-рейтинговая (БРС, до 100 баллов)'
    : 'Традиционная 5-балльная (средний балл)';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель администратора</h1>
                <p class="text-muted">Справочная информация об образовательной организации</p>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin_nav.php'; ?>
    </section>

    <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel" data-settings-block="organization">
        <h2>Образовательная организация</h2>

        <div data-settings-view<?= $editSection === 'organization' ? ' hidden' : '' ?>>
            <dl class="profile-list">
                <dt>Полное название</dt>
                <dd><?= e($dash($organization['name'] ?? '')) ?></dd>
                <dt>Адрес</dt>
                <dd><?= e($dash($organization['address'] ?? '')) ?></dd>
                <dt>Телефон</dt>
                <dd><?= e($dash($organization['phone'] ?? '')) ?></dd>
                <dt>Email</dt>
                <dd><?= e($dash($organization['email'] ?? '')) ?></dd>
                <dt>Дополнительная информация</dt>
                <dd class="cabinet-profile-multiline"><?= e($dash($organization['additional_info'] ?? '')) ?></dd>
            </dl>
            <div class="form__actions">
                <button type="button" class="btn btn--primary" data-settings-edit-open>Редактировать</button>
            </div>
        </div>

        <div data-settings-edit<?= $editSection === 'organization' ? '' : ' hidden' ?>>
            <form method="post" class="form form--medium">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_organization">

                <div class="form__group">
                    <label for="org_name">Полное название</label>
                    <input type="text" id="org_name" name="name" required
                           value="<?= e($organization['name']) ?>"
                           placeholder="Государственное бюджетное профессиональное образовательное учреждение...">
                </div>

                <div class="form__group">
                    <label for="org_address">Адрес</label>
                    <input type="text" id="org_address" name="address"
                           value="<?= e($organization['address']) ?>"
                           placeholder="г. ..., ул. ..., д. ...">
                </div>

                <div class="form__row">
                    <div class="form__group">
                        <label for="org_phone">Телефон</label>
                        <input type="text" id="org_phone" name="phone"
                               value="<?= e($organization['phone']) ?>"
                               placeholder="+7 (___) ___-__-__">
                    </div>
                    <div class="form__group">
                        <label for="org_email">Email</label>
                        <input type="email" id="org_email" name="email"
                               value="<?= e($organization['email']) ?>"
                               placeholder="info@kpk.local">
                    </div>
                </div>

                <div class="form__group">
                    <label for="org_info">Дополнительная информация</label>
                    <textarea id="org_info" name="additional_info" rows="4"
                              placeholder="Реквизиты, режим работы, сайт и другие сведения"><?= e($organization['additional_info']) ?></textarea>
                </div>

                <div class="form__actions">
                    <button type="submit" class="btn btn--primary">Сохранить</button>
                    <button type="button" class="btn btn--ghost" data-settings-edit-cancel>Отмена</button>
                </div>
            </form>
        </div>
    </section>

    <section class="panel" data-settings-block="period" id="period">
        <h2>Период электронной ведомости</h2>
        <p class="text-muted">
            Выбранный учебный год и семестр используются в ведомостях группы.
            Предметы подгружаются только из учебного плана выбранного семестра.
            Список годов: с 2000–2001 до <?= e((string) get_academic_year_to_start_year()) ?>–<?= e((string) (get_academic_year_to_start_year() + 1)) ?>.
            При необходимости можно добавить ещё 10 лет вперёд.
        </p>

        <div data-settings-view<?= $editSection === 'period' ? ' hidden' : '' ?>>
            <dl class="profile-list">
                <dt>Учебный год</dt>
                <dd><?= e($dash($gradebookPeriod['academic_year'] ?? '')) ?></dd>
                <dt>Семестр</dt>
                <dd><?= e($gradebookPeriod['semester'] === '2' ? '2 семестр' : '1 семестр') ?></dd>
            </dl>
            <div class="form__actions">
                <button type="button" class="btn btn--primary" data-settings-edit-open>Редактировать</button>
            </div>
        </div>

        <div data-settings-edit<?= $editSection === 'period' ? '' : ' hidden' ?>>
            <form method="post" class="form form--medium">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_gradebook_period">

                <div class="form__row">
                    <div class="form__group">
                        <label for="active_academic_year">Учебный год</label>
                        <select id="active_academic_year" name="active_academic_year" required>
                            <?php foreach ($yearOptions as $year): ?>
                            <option value="<?= e($year) ?>"<?= $year === $gradebookPeriod['academic_year'] ? ' selected' : '' ?>>
                                <?= e($year) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form__group">
                        <label for="active_semester">Семестр</label>
                        <select id="active_semester" name="active_semester" required>
                            <option value="1"<?= $gradebookPeriod['semester'] === '1' ? ' selected' : '' ?>>1 семестр</option>
                            <option value="2"<?= $gradebookPeriod['semester'] === '2' ? ' selected' : '' ?>>2 семестр</option>
                        </select>
                    </div>
                </div>

                <div class="form__actions">
                    <button type="submit" class="btn btn--primary">Сохранить период</button>
                    <button type="button" class="btn btn--ghost" data-settings-edit-cancel>Отмена</button>
                </div>
            </form>
            <form method="post" class="form form-inline" style="margin-top: 0.75rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="extend_academic_years">
                <button type="submit" class="btn btn--secondary btn--sm">
                    Добавить ещё +10 лет
                </button>
                <span class="text-muted" style="margin-left: 0.5rem;">
                    Список до <?= e((string) get_academic_year_to_start_year()) ?>–<?= e((string) (get_academic_year_to_start_year() + 1)) ?>
                </span>
            </form>
        </div>
    </section>

    <section class="panel" data-settings-block="grading">
        <h2>Система оценивания</h2>
        <p class="text-muted">
            Система для электронного журнала. После сохранения итоги пересчитываются
            при каждом изменении оценки или отметок А/О.
        </p>

        <div data-settings-view<?= $editSection === 'grading' ? ' hidden' : '' ?>>
            <dl class="profile-list">
                <dt>Система</dt>
                <dd><?= e($gradingSystemLabel) ?></dd>
                <?php if ($gradingConfig['system'] === 'brs'): ?>
                <dt>Вес текущих</dt>
                <dd><?= e(format_grading_number((float) $gradingConfig['brs']['weight_current'], 1)) ?></dd>
                <dt>Вес контрольных</dt>
                <dd><?= e(format_grading_number((float) $gradingConfig['brs']['weight_control'], 1)) ?></dd>
                <dt>Вес посещаемости</dt>
                <dd><?= e(format_grading_number((float) $gradingConfig['brs']['weight_attendance'], 1)) ?></dd>
                <dt>Вес без опозданий</dt>
                <dd><?= e(format_grading_number((float) $gradingConfig['brs']['weight_punctuality'], 1)) ?></dd>
                <dt>Вес активности</dt>
                <dd><?= e(format_grading_number((float) $gradingConfig['brs']['weight_activity'], 1)) ?></dd>
                <dt>Шкала: от баллов → 3 / 4 / 5</dt>
                <dd>
                    <?= e(format_grading_number((float) $gradingConfig['brs']['scale_3'], 1)) ?>
                    /
                    <?= e(format_grading_number((float) $gradingConfig['brs']['scale_4'], 1)) ?>
                    /
                    <?= e(format_grading_number((float) $gradingConfig['brs']['scale_5'], 1)) ?>
                </dd>
                <?php endif; ?>
            </dl>
            <div class="form__actions">
                <button type="button" class="btn btn--primary" data-settings-edit-open>Редактировать</button>
            </div>
        </div>

        <div data-settings-edit<?= $editSection === 'grading' ? '' : ' hidden' ?>>
            <form method="post" class="form form--medium" data-grading-settings>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_grading_config">

                <div class="form__group">
                    <label for="grading_system">Система</label>
                    <select id="grading_system" name="system" data-grading-system>
                        <option value="traditional"<?= $gradingConfig['system'] === 'traditional' ? ' selected' : '' ?>>
                            Традиционная 5-балльная (средний балл)
                        </option>
                        <option value="brs"<?= $gradingConfig['system'] === 'brs' ? ' selected' : '' ?>>
                            Балльно-рейтинговая (БРС, до 100 баллов)
                        </option>
                    </select>
                </div>

                <div class="grading-brs-settings" data-grading-brs<?= $gradingConfig['system'] === 'brs' ? '' : ' hidden' ?>>
                    <h3 class="subsection-title">Формула БРС (веса блоков, сумма до 100)</h3>
                    <p class="text-muted form-hint">
                        Текущие: (средняя текущих / 5) × вес.
                        Контрольные: (средняя контрольных / 5) × вес.
                        Если в журнале ещё нет контрольных, блок контрольных считается по средней текущих;
                        после появления контрольной — по стандартной формуле.
                        Посещаемость: (вес / число уроков) × уроки без Н.
                        Без опозданий: (вес / уроки без Н) × уроки без О.
                        Активность: (вес / уроки без Н) × уроки с А.
                    </p>

                    <div class="form__row form__row--3">
                        <div class="form__group">
                            <label for="weight_current">Вес текущих</label>
                            <input type="number" id="weight_current" name="weight_current" min="0" max="100" step="0.5"
                                   value="<?= e(format_grading_number((float) $gradingConfig['brs']['weight_current'], 1)) ?>" required>
                        </div>
                        <div class="form__group">
                            <label for="weight_control">Вес контрольных</label>
                            <input type="number" id="weight_control" name="weight_control" min="0" max="100" step="0.5"
                                   value="<?= e(format_grading_number((float) $gradingConfig['brs']['weight_control'], 1)) ?>" required>
                        </div>
                        <div class="form__group">
                            <label for="weight_attendance">Вес посещаемости</label>
                            <input type="number" id="weight_attendance" name="weight_attendance" min="0" max="100" step="0.5"
                                   value="<?= e(format_grading_number((float) $gradingConfig['brs']['weight_attendance'], 1)) ?>" required>
                        </div>
                    </div>

                    <div class="form__row">
                        <div class="form__group">
                            <label for="weight_punctuality">Вес без опозданий</label>
                            <input type="number" id="weight_punctuality" name="weight_punctuality" min="0" max="100" step="0.5"
                                   value="<?= e(format_grading_number((float) $gradingConfig['brs']['weight_punctuality'], 1)) ?>" required>
                        </div>
                        <div class="form__group">
                            <label for="weight_activity">Вес активности</label>
                            <input type="number" id="weight_activity" name="weight_activity" min="0" max="100" step="0.5"
                                   value="<?= e(format_grading_number((float) $gradingConfig['brs']['weight_activity'], 1)) ?>" required>
                        </div>
                    </div>

                    <h3 class="subsection-title">Шкала перевода баллов в оценку</h3>
                    <p class="text-muted form-hint">
                        Ниже порога «3» — оценка 2. Далее: от порога «3» до порога «4» — 3,
                        от «4» до «5» — 4, от порога «5» и выше — 5.
                    </p>

                    <div class="form__row form__row--3">
                        <div class="form__group">
                            <label for="scale_3">От баллов → 3</label>
                            <input type="number" id="scale_3" name="scale_3" min="0" max="100" step="0.5"
                                   value="<?= e(format_grading_number((float) $gradingConfig['brs']['scale_3'], 1)) ?>" required>
                        </div>
                        <div class="form__group">
                            <label for="scale_4">От баллов → 4</label>
                            <input type="number" id="scale_4" name="scale_4" min="0" max="100" step="0.5"
                                   value="<?= e(format_grading_number((float) $gradingConfig['brs']['scale_4'], 1)) ?>" required>
                        </div>
                        <div class="form__group">
                            <label for="scale_5">От баллов → 5</label>
                            <input type="number" id="scale_5" name="scale_5" min="0" max="100" step="0.5"
                                   value="<?= e(format_grading_number((float) $gradingConfig['brs']['scale_5'], 1)) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form__actions">
                    <button type="submit" class="btn btn--primary">Сохранить систему оценивания</button>
                    <button type="button" class="btn btn--ghost" data-settings-edit-cancel>Отмена</button>
                </div>
            </form>
        </div>
    </section>

    <section class="panel" id="specialties">
        <div class="panel__header panel__header--compact">
            <h2>Специальности</h2>
        </div>

        <?php if (empty($specialties)): ?>
            <p class="text-muted">Специальности пока не добавлены.</p>
        <?php else: ?>
            <div class="form__group list-search">
                <label for="specialties_search">Поиск</label>
                <input
                    type="search"
                    id="specialties_search"
                    placeholder="Название или код…"
                    autocomplete="off"
                    data-table-search="specialties"
                >
            </div>
            <div class="table-wrap">
                <table class="table" data-table-search-target="specialties">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Название</th>
                            <th>Код</th>
                            <th class="table__actions-col">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($specialties as $index => $specialty): ?>
                        <tr
                            data-search-row
                            data-search-text="<?= e(mb_strtolower($specialty['name'] . ' ' . $specialty['code'])) ?>"
                        >
                            <td data-search-num><?= $index + 1 ?></td>
                            <td><?= e($specialty['name']) ?></td>
                            <td><code><?= e($specialty['code']) ?></code></td>
                            <td class="table__actions">
                                <a href="specialty_edit.php?id=<?= (int) $specialty['id'] ?>" class="btn btn--ghost btn--sm">Изменить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted list-search-empty" data-table-search-empty="specialties" hidden>Ничего не найдено по запросу.</p>
        <?php endif; ?>

        <h3 class="subsection-title">Добавить специальность</h3>
        <form method="post" class="form form--inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_specialty">

            <div class="form__row form__row--3">
                <div class="form__group">
                    <label for="specialty_name">Название</label>
                    <input type="text" id="specialty_name" name="name" required
                           value="<?= e(($_POST['action'] ?? '') === 'add_specialty' ? ($_POST['name'] ?? '') : '') ?>"
                           placeholder="Педагогика и методика начального образования">
                </div>
                <div class="form__group">
                    <label for="specialty_code">Код</label>
                    <input type="text" id="specialty_code" name="code" required
                           value="<?= e(($_POST['action'] ?? '') === 'add_specialty' ? ($_POST['code'] ?? '') : '') ?>"
                           placeholder="44.02.01">
                </div>
                <div class="form__group form__group--align-end">
                    <button type="submit" class="btn btn--primary btn--block">Добавить</button>
                </div>
            </div>
        </form>
    </section>

    <section class="panel" id="groups">
        <div class="panel__header panel__header--compact">
            <h2>Группы</h2>
        </div>

        <?php if (empty($specialties)): ?>
            <p class="text-muted">Сначала добавьте хотя бы одну специальность, затем создайте группы.</p>
        <?php elseif (empty($groups)): ?>
            <p class="text-muted">Группы пока не добавлены.</p>
        <?php else: ?>
            <div class="form__group list-search">
                <label for="groups_search">Поиск</label>
                <input
                    type="search"
                    id="groups_search"
                    placeholder="Номер, специальность, код или куратор…"
                    autocomplete="off"
                    data-table-search="groups"
                >
            </div>
            <div class="table-wrap">
                <table class="table" data-table-search-target="groups">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Номер группы</th>
                            <th>Специальность</th>
                            <th>Код</th>
                            <th>Куратор</th>
                            <th>Метки</th>
                            <th class="table__actions-col">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $index => $group): ?>
                        <?php
                        $groupSearchText = mb_strtolower(implode(' ', array_filter([
                            $group['number'] ?? '',
                            $group['specialty_name'] ?? '',
                            $group['specialty_code'] ?? '',
                            $group['curator_name'] ?? '',
                            group_labels_text($group),
                        ])));
                        ?>
                        <tr data-search-row data-search-text="<?= e($groupSearchText) ?>">
                            <td data-search-num><?= $index + 1 ?></td>
                            <td><strong><?= e($group['number']) ?></strong></td>
                            <td><?= e($group['specialty_name']) ?></td>
                            <td><code><?= e($group['specialty_code']) ?></code></td>
                            <td><?= e($group['curator_name'] ?? '—') ?></td>
                            <td class="group-labels-cell"><?= render_group_labels_badges($group) ?></td>
                            <td class="table__actions">
                                <a href="group_edit.php?id=<?= (int) $group['id'] ?>" class="btn btn--ghost btn--sm">Изменить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted list-search-empty" data-table-search-empty="groups" hidden>Ничего не найдено по запросу.</p>
        <?php endif; ?>

        <?php if (!empty($specialties)): ?>
        <h3 class="subsection-title">Добавить группу</h3>
        <form method="post" class="form form--inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_group">

            <div class="form__row form__row--3">
                <div class="form__group">
                    <label for="group_number">Номер группы</label>
                    <input type="text" id="group_number" name="number" required
                           value="<?= e(($_POST['action'] ?? '') === 'add_group' ? ($_POST['number'] ?? '') : '') ?>"
                           placeholder="П-21">
                </div>
                <div class="form__group">
                    <label for="group_specialty">Специальность</label>
                    <select id="group_specialty" name="specialty_id" required>
                        <?= render_specialty_options(
                            $specialties,
                            (int) (($_POST['action'] ?? '') === 'add_group' ? ($_POST['specialty_id'] ?? 0) : 0)
                        ) ?>
                    </select>
                </div>
            </div>
            <?php
            $addGroupLabels = ($_POST['action'] ?? '') === 'add_group'
                ? group_labels_from_input($_POST)
                : ['is_professionality' => false, 'is_general_education' => false];
            render_group_labels_fields($addGroupLabels);
            ?>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Добавить</button>
            </div>
        </form>
        <?php endif; ?>
    </section>

    <section class="panel" id="attendance-reasons">
        <div class="panel__header panel__header--compact">
            <h2>Причины пропусков</h2>
        </div>
        <p class="text-muted">
            Список причин для уважительных пропусков в журнале посещаемости куратора.
        </p>

        <?php if (empty($attendanceReasons)): ?>
            <p class="text-muted">Причины пока не добавлены.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Статус</th>
                            <th class="table__actions-col">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceReasons as $reason): ?>
                        <?php $formId = 'reason-form-' . (int) $reason['id']; ?>
                        <tr>
                            <td>
                                <input
                                    type="text"
                                    name="reason_name"
                                    form="<?= e($formId) ?>"
                                    value="<?= e($reason['name']) ?>"
                                    required
                                    class="attendance-reason-form__name"
                                >
                            </td>
                            <td>
                                <label class="checkbox-inline">
                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        form="<?= e($formId) ?>"
                                        value="1"
                                        <?= (int) $reason['is_active'] === 1 ? 'checked' : '' ?>
                                    >
                                    Активна
                                </label>
                            </td>
                            <td class="table__actions">
                                <form method="post" id="<?= e($formId) ?>" class="form-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_attendance_reason">
                                    <input type="hidden" name="reason_id" value="<?= (int) $reason['id'] ?>">
                                    <button type="submit" class="btn btn--ghost btn--sm">Сохранить</button>
                                </form>
                                <form method="post" class="form-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_attendance_reason">
                                    <input type="hidden" name="reason_id" value="<?= (int) $reason['id'] ?>">
                                    <button
                                        type="submit"
                                        class="btn btn--danger btn--sm"
                                        onclick="return confirm('Удалить причину пропуска?')"
                                    >Удалить</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h3 class="subsection-title">Добавить причину</h3>
        <form method="post" class="form form--inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_attendance_reason">

            <div class="form__row form__row--3">
                <div class="form__group">
                    <label for="new_reason_name">Название</label>
                    <input
                        type="text"
                        id="new_reason_name"
                        name="reason_name"
                        required
                        placeholder="Командировка"
                    >
                </div>
                <div class="form__group form__group--align-end">
                    <button type="submit" class="btn btn--primary btn--block">Добавить</button>
                </div>
            </div>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
