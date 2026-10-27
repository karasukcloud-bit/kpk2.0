<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/educator/all_students_filters.php';

require_educator_panel();

if (isset($_GET['clear'])) {
    educator_all_students_clear_filters();
    header('Location: all_students.php');
    exit;
}

$groups = get_all_groups();

if (educator_all_students_has_filter_request()) {
    $filters = educator_all_students_normalize_filters($_GET);
    educator_all_students_save_filters($filters);
} else {
    $filters = educator_all_students_saved_filters();
    $query = educator_all_students_filter_query($filters);
    if ($query !== '') {
        header('Location: all_students.php?' . $query);
        exit;
    }
}

$listUrl = educator_all_students_list_url($filters);

$students = get_students_list(
    $filters['group_id'] > 0 ? $filters['group_id'] : null
);

usort(
    $students,
    static fn (array $a, array $b): int => strcasecmp(
        (string) ($a['full_name'] ?? ''),
        (string) ($b['full_name'] ?? '')
    )
);

$students = array_values(array_filter(
    $students,
    static function (array $student) use ($filters): bool {
        if ($filters['q'] !== '') {
            $haystack = mb_strtolower(
                trim(
                    (string) ($student['full_name'] ?? '') . ' '
                    . (string) ($student['group_number'] ?? '') . ' '
                    . (string) ($student['phone'] ?? '')
                )
            );
            if (mb_strpos($haystack, mb_strtolower($filters['q'])) === false) {
                return false;
            }
        }
        if ($filters['gender'] !== '' && (string) ($student['gender'] ?? '') !== $filters['gender']) {
            return false;
        }
        if ($filters['is_minor'] === '1' || $filters['is_minor'] === '0') {
            $age = student_age_years(isset($student['birth_date']) ? (string) $student['birth_date'] : null);
            if ($age === null) {
                return false;
            }
            $isMinor = $age < 18;
            if ($filters['is_minor'] === '1' && !$isMinor) {
                return false;
            }
            if ($filters['is_minor'] === '0' && $isMinor) {
                return false;
            }
        }
        if ($filters['is_nonresident'] === '1' && empty($student['is_nonresident'])) {
            return false;
        }
        if ($filters['is_nonresident'] === '0' && !empty($student['is_nonresident'])) {
            return false;
        }
        if ($filters['is_low_income'] === '1' && empty($student['is_low_income'])) {
            return false;
        }
        if ($filters['without_parental_care'] === '1' && empty($student['without_parental_care'])) {
            return false;
        }
        if ($filters['residence_type'] !== '') {
            if ((string) ($student['residence_type'] ?? '') !== $filters['residence_type']) {
                return false;
            }
        }

        return true;
    }
));

$selectedGroup = null;
if ($filters['group_id'] > 0) {
    foreach ($groups as $group) {
        if ((int) $group['id'] === $filters['group_id']) {
            $selectedGroup = $group;
            break;
        }
    }
}

$pageTitle = 'Студенты — Панель воспитателя';
$showHeader = true;
$basePath = '../';
$currentEducatorTab = 'all_students';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide educator-students-page">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель воспитателя</h1>
                <p class="text-muted">Список всех студентов</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/educator_nav.php'; ?>
    </section>

    <section class="panel">
        <form method="get" class="form form--filter educator-no-print" id="educator-all-students-filter">
            <div class="form__row form__row--filter">
                <div class="form__group">
                    <label for="q">Поиск</label>
                    <input
                        type="search"
                        id="q"
                        name="q"
                        value="<?= e($filters['q']) ?>"
                        placeholder="ФИО, группа, телефон"
                    >
                </div>
                <div class="form__group">
                    <label for="group_id">Группа</label>
                    <select id="group_id" name="group_id">
                        <option value="">Все группы</option>
                        <?php foreach ($groups as $group): ?>
                        <option value="<?= (int) $group['id'] ?>"<?= $filters['group_id'] === (int) $group['id'] ? ' selected' : '' ?>>
                            <?= e($group['number']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form__group">
                    <label for="gender">Пол</label>
                    <select id="gender" name="gender">
                        <option value="">Все</option>
                        <option value="male"<?= $filters['gender'] === 'male' ? ' selected' : '' ?>>Мужской</option>
                        <option value="female"<?= $filters['gender'] === 'female' ? ' selected' : '' ?>>Женский</option>
                    </select>
                </div>
                <div class="form__group">
                    <label for="is_minor">Возраст</label>
                    <select id="is_minor" name="is_minor">
                        <option value="">Все</option>
                        <option value="1"<?= $filters['is_minor'] === '1' ? ' selected' : '' ?>>Несовершеннолетние (до 18)</option>
                        <option value="0"<?= $filters['is_minor'] === '0' ? ' selected' : '' ?>>Совершеннолетние (18+)</option>
                    </select>
                </div>
            </div>
            <div class="form__row form__row--filter">
                <div class="form__group">
                    <label for="is_nonresident">Иногородний</label>
                    <select id="is_nonresident" name="is_nonresident">
                        <option value="">Все</option>
                        <option value="1"<?= $filters['is_nonresident'] === '1' ? ' selected' : '' ?>>Только иногородние</option>
                        <option value="0"<?= $filters['is_nonresident'] === '0' ? ' selected' : '' ?>>Только местные</option>
                    </select>
                </div>
                <div class="form__group">
                    <label for="residence_type">Проживание</label>
                    <select id="residence_type" name="residence_type">
                        <option value="">Любое</option>
                        <option value="family"<?= $filters['residence_type'] === 'family' ? ' selected' : '' ?>>С родителями / родственниками</option>
                        <option value="dormitory"<?= $filters['residence_type'] === 'dormitory' ? ' selected' : '' ?>>Общежитие</option>
                        <option value="apartment"<?= $filters['residence_type'] === 'apartment' ? ' selected' : '' ?>>Квартира (наём)</option>
                    </select>
                </div>
                <div class="form__group form__group--checkbox">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_low_income" value="1"<?= $filters['is_low_income'] === '1' ? ' checked' : '' ?>>
                        Малообеспеченная семья
                    </label>
                </div>
                <div class="form__group form__group--checkbox">
                    <label class="checkbox-label">
                        <input type="checkbox" name="without_parental_care" value="1"<?= $filters['without_parental_care'] === '1' ? ' checked' : '' ?>>
                        Без попечительства
                    </label>
                </div>
                <div class="form__actions form__actions--inline">
                    <button type="submit" class="btn btn--primary">Применить</button>
                    <a href="all_students.php?clear=1" class="btn btn--ghost">Сбросить</a>
                </div>
            </div>
        </form>

        <?php require __DIR__ . '/../includes/educator/all_students_table.php'; ?>
    </section>
</div>

<script>
(() => {
    const table = document.getElementById('educator-all-students-table');
    if (!table) {
        return;
    }

    const controls = document.getElementById('educator-all-students-col-controls');
    const storageKey = 'educatorAllStudentsColumns';
    const checks = controls ? controls.querySelectorAll('.student-info-col__check') : [];

    const applyColumn = (col, visible) => {
        table.querySelectorAll('[data-col="' + col + '"]').forEach((node) => {
            node.classList.toggle('is-col-hidden', !visible);
        });
    };

    const saveState = () => {
        const state = {};
        checks.forEach((check) => {
            state[check.dataset.col] = check.checked;
        });
        localStorage.setItem(storageKey, JSON.stringify(state));
    };

    checks.forEach((check) => {
        const syncColumn = () => {
            applyColumn(check.dataset.col, check.checked);
            saveState();
        };
        check.addEventListener('change', syncColumn);
        check.addEventListener('input', syncColumn);
    });

    try {
        const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
        checks.forEach((check) => {
            if (Object.prototype.hasOwnProperty.call(saved, check.dataset.col)) {
                check.checked = !!saved[check.dataset.col];
            }
            applyColumn(check.dataset.col, check.checked);
        });
    } catch (error) {
        checks.forEach((check) => applyColumn(check.dataset.col, check.checked));
    }

    const printBtn = document.getElementById('educator-all-students-print-btn');
    if (printBtn) {
        printBtn.addEventListener('click', () => window.print());
    }

    table.querySelectorAll('.educator-all-students-row[data-href]').forEach((row) => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('a')) {
                return;
            }
            const href = row.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        });
    });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
