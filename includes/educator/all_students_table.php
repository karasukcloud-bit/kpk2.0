<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $students */
/** @var array $filters */
/** @var array|null $selectedGroup */
/** @var string $listUrl */

$listUrl = $listUrl ?? 'all_students.php';

$studentListColumns = [
    ['key' => 'full_name', 'label' => 'Студент', 'default' => true],
    ['key' => 'group_number', 'label' => 'Группа', 'default' => true],
    ['key' => 'phone', 'label' => 'Телефон', 'default' => false],
    ['key' => 'birth_date', 'label' => 'Дата рождения', 'default' => false],
    ['key' => 'gender', 'label' => 'Пол', 'default' => false],
    ['key' => 'residence_type', 'label' => 'Проживание', 'default' => false],
    ['key' => 'is_nonresident', 'label' => 'Иногородний', 'default' => false],
    ['key' => 'is_low_income', 'label' => 'Малообеспеченная', 'default' => false],
    ['key' => 'without_parental_care', 'label' => 'Без попечительства', 'default' => false],
    ['key' => 'address_registered', 'label' => 'Адрес по прописке', 'default' => false],
    ['key' => 'address_actual', 'label' => 'Фактический адрес', 'default' => false],
    ['key' => 'family_type', 'label' => 'Состав семьи', 'default' => false],
];

$renderStudentListCell = static function (array $student, string $key): string {
    switch ($key) {
        case 'full_name':
            return (string) ($student['full_name'] ?? '');
        case 'group_number':
            return (string) (($student['group_number'] ?? '') !== '' ? $student['group_number'] : '—');
        case 'phone':
            return (string) (($student['phone'] ?? '') !== '' ? $student['phone'] : '—');
        case 'birth_date':
            return format_student_birth_date(isset($student['birth_date']) ? (string) $student['birth_date'] : null);
        case 'gender':
            return student_gender_label(isset($student['gender']) ? (string) $student['gender'] : null);
        case 'residence_type':
            return student_residence_type_label(isset($student['residence_type']) ? (string) $student['residence_type'] : null);
        case 'is_nonresident':
            return !empty($student['is_nonresident']) ? 'Да' : 'Нет';
        case 'is_low_income':
            return !empty($student['is_low_income']) ? 'Да' : 'Нет';
        case 'without_parental_care':
            return !empty($student['without_parental_care']) ? 'Да' : 'Нет';
        case 'address_registered':
            return format_student_registered_address($student);
        case 'address_actual':
            return (string) (($student['address_actual'] ?? '') !== '' ? $student['address_actual'] : '—');
        case 'family_type':
            return student_family_type_label(isset($student['family_type']) ? (string) $student['family_type'] : null);
        default:
            return '—';
    }
};

$filterHints = [];
if ($selectedGroup !== null) {
    $filterHints[] = 'группа ' . $selectedGroup['number'];
}
if (($filters['gender'] ?? '') === 'male') {
    $filterHints[] = 'мужской пол';
} elseif (($filters['gender'] ?? '') === 'female') {
    $filterHints[] = 'женский пол';
}
if (($filters['is_minor'] ?? '') === '1') {
    $filterHints[] = 'несовершеннолетние';
} elseif (($filters['is_minor'] ?? '') === '0') {
    $filterHints[] = 'совершеннолетние';
}
if (($filters['is_nonresident'] ?? '') === '1') {
    $filterHints[] = 'иногородние';
} elseif (($filters['is_nonresident'] ?? '') === '0') {
    $filterHints[] = 'местные';
}
if (($filters['is_low_income'] ?? '') === '1') {
    $filterHints[] = 'малообеспеченные';
}
if (($filters['without_parental_care'] ?? '') === '1') {
    $filterHints[] = 'без попечительства';
}
if (($filters['residence_type'] ?? '') !== '') {
    $filterHints[] = student_residence_type_label($filters['residence_type']);
}
if (($filters['q'] ?? '') !== '') {
    $filterHints[] = 'поиск «' . $filters['q'] . '»';
}
?>
<p class="text-muted educator-no-print">
    В списке: <?= count($students) ?>
    <?php if ($filterHints !== []): ?>
        · фильтр: <?= e(implode(', ', $filterHints)) ?>
    <?php endif; ?>
</p>

<?php if ($students === []): ?>
    <p class="text-muted">Студенты по выбранным условиям не найдены.</p>
<?php else: ?>
    <div class="educator-students-print-area">
        <div class="educator-students-print-header educator-print-only">
            <h2>Список студентов</h2>
            <p>
                Студентов: <?= count($students) ?>
                <?php if ($filterHints !== []): ?>
                    · <?= e(implode(', ', $filterHints)) ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="student-info-toolbar educator-no-print" id="educator-all-students-col-controls">
            <div class="student-info-toolbar__columns">
                <span class="student-info-toolbar__title">Столбцы для печати:</span>
                <?php foreach ($studentListColumns as $column): ?>
                <label class="student-info-col__toggle">
                    <input
                        type="checkbox"
                        class="student-info-col__check"
                        data-col="<?= e($column['key']) ?>"
                        <?= !empty($column['default']) ? 'checked' : '' ?>
                    >
                    <span><?= e($column['label']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn--secondary" id="educator-all-students-print-btn">Печать</button>
        </div>

        <div class="table-wrap">
            <table class="table table--student-info" id="educator-all-students-table">
                <thead>
                    <tr>
                        <th class="student-info-col student-info-col--num">№</th>
                        <?php foreach ($studentListColumns as $column): ?>
                        <th
                            class="student-info-col<?= empty($column['default']) ? ' is-col-hidden' : '' ?>"
                            data-col="<?= e($column['key']) ?>"
                        >
                            <?= e($column['label']) ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $student): ?>
                    <?php $studentCardUrl = 'student_view.php?id=' . (int) $student['id']; ?>
                    <tr class="educator-all-students-row" data-href="<?= e($studentCardUrl) ?>">
                        <td class="student-info-col student-info-col--num"><?= $index + 1 ?></td>
                        <?php foreach ($studentListColumns as $column): ?>
                        <td
                            class="student-info-col<?= empty($column['default']) ? ' is-col-hidden' : '' ?>"
                            data-col="<?= e($column['key']) ?>"
                        >
                            <?php if ($column['key'] === 'full_name'): ?>
                                <a href="<?= e($studentCardUrl) ?>" class="educator-student-link">
                                    <?= e($renderStudentListCell($student, $column['key'])) ?>
                                </a>
                            <?php else: ?>
                                <?= e($renderStudentListCell($student, $column['key'])) ?>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
