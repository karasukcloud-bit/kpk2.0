<?php

declare(strict_types=1);

/** @var array|null $group */
/** @var array $students */

$studentInfoColumns = [
    ['key' => 'full_name', 'label' => 'ФИО'],
    ['key' => 'phone', 'label' => 'Телефон'],
    ['key' => 'birth_date', 'label' => 'Дата рождения'],
    ['key' => 'gender', 'label' => 'Пол'],
    ['key' => 'mother_name', 'label' => 'ФИО мамы'],
    ['key' => 'mother_phone', 'label' => 'Телефон мамы'],
    ['key' => 'mother_workplace', 'label' => 'Место работы мамы'],
    ['key' => 'father_name', 'label' => 'ФИО папы'],
    ['key' => 'father_phone', 'label' => 'Телефон папы'],
    ['key' => 'father_workplace', 'label' => 'Место работы папы'],
    ['key' => 'address_registered', 'label' => 'Адрес по прописке'],
    ['key' => 'address_region', 'label' => 'Область / край'],
    ['key' => 'address_district', 'label' => 'Район / округ'],
    ['key' => 'address_locality', 'label' => 'Населённый пункт'],
    ['key' => 'address_street', 'label' => 'Улица'],
    ['key' => 'address_house', 'label' => 'Дом'],
    ['key' => 'address_actual', 'label' => 'Фактический адрес'],
    ['key' => 'family_type', 'label' => 'Состав семьи'],
    ['key' => 'siblings_under_18', 'label' => 'Братьев/сестёр <18'],
    ['key' => 'residence_type', 'label' => 'Проживание'],
    ['key' => 'is_nonresident', 'label' => 'Иногородний'],
    ['key' => 'is_low_income', 'label' => 'Малообеспеченная'],
    ['key' => 'without_parental_care', 'label' => 'Без попечительства'],
];

$renderStudentInfoCell = static function (array $student, string $key): string {
    switch ($key) {
        case 'full_name':
            return (string) $student['full_name'];
        case 'phone':
            return (string) (($student['phone'] ?? '') !== '' ? $student['phone'] : '—');
        case 'birth_date':
            return format_student_birth_date(isset($student['birth_date']) ? (string) $student['birth_date'] : null);
        case 'gender':
            return student_gender_label(isset($student['gender']) ? (string) $student['gender'] : null);
        case 'mother_name':
            return (string) (($student['mother_name'] ?? '') !== '' ? $student['mother_name'] : '—');
        case 'mother_phone':
            return (string) (($student['mother_phone'] ?? '') !== '' ? $student['mother_phone'] : '—');
        case 'mother_workplace':
            return (string) (($student['mother_workplace'] ?? '') !== '' ? $student['mother_workplace'] : '—');
        case 'father_name':
            return (string) (($student['father_name'] ?? '') !== '' ? $student['father_name'] : '—');
        case 'father_phone':
            return (string) (($student['father_phone'] ?? '') !== '' ? $student['father_phone'] : '—');
        case 'father_workplace':
            return (string) (($student['father_workplace'] ?? '') !== '' ? $student['father_workplace'] : '—');
        case 'address_registered':
            return format_student_registered_address($student);
        case 'address_region':
        case 'address_district':
        case 'address_locality':
        case 'address_street':
        case 'address_house':
            return (string) (($student[$key] ?? '') !== '' ? $student[$key] : '—');
        case 'address_actual':
            return (string) (($student['address_actual'] ?? '') !== '' ? $student['address_actual'] : '—');
        case 'family_type':
            return student_family_type_label(isset($student['family_type']) ? (string) $student['family_type'] : null);
        case 'siblings_under_18':
            return (string) (int) ($student['siblings_under_18'] ?? 0);
        case 'residence_type':
            return student_residence_type_label(isset($student['residence_type']) ? (string) $student['residence_type'] : null);
        case 'is_nonresident':
            return !empty($student['is_nonresident']) ? 'Да' : 'Нет';
        case 'is_low_income':
            return !empty($student['is_low_income']) ? 'Да' : 'Нет';
        case 'without_parental_care':
            return !empty($student['without_parental_care']) ? 'Да' : 'Нет';
        default:
            return '—';
    }
};
?>
<p class="text-muted educator-no-print">
    Группа <strong><?= e($group['number']) ?></strong>
    · <?= e($group['specialty_name']) ?> (<?= e($group['specialty_code']) ?>)
    · студентов: <?= count($students) ?>
</p>
<?php if ($students === []): ?>
    <p class="text-muted">В группе пока нет студентов.</p>
<?php else: ?>
    <div class="educator-students-print-area">
        <div class="educator-students-print-header educator-print-only">
            <h2>Информация по студентам</h2>
            <p>
                Группа <?= e($group['number']) ?>
                · <?= e($group['specialty_name']) ?> (<?= e($group['specialty_code']) ?>)
                · студентов: <?= count($students) ?>
            </p>
        </div>

        <div class="student-info-toolbar educator-no-print" id="educator-students-col-controls">
            <div class="student-info-toolbar__columns">
                <span class="student-info-toolbar__title">Столбцы:</span>
                <?php foreach ($studentInfoColumns as $column): ?>
                <label class="student-info-col__toggle">
                    <input
                        type="checkbox"
                        class="student-info-col__check"
                        data-col="<?= e($column['key']) ?>"
                        checked
                    >
                    <span><?= e($column['label']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn--secondary" id="educator-students-print-btn">Печать</button>
        </div>

        <div class="table-wrap">
            <table class="table table--student-info" id="educator-students-table">
                <thead>
                    <tr>
                        <th class="student-info-col student-info-col--num">№</th>
                        <?php foreach ($studentInfoColumns as $column): ?>
                        <th class="student-info-col" data-col="<?= e($column['key']) ?>">
                            <?= e($column['label']) ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $student): ?>
                    <tr>
                        <td class="student-info-col student-info-col--num"><?= $index + 1 ?></td>
                        <?php foreach ($studentInfoColumns as $column): ?>
                        <td class="student-info-col" data-col="<?= e($column['key']) ?>">
                            <?= e($renderStudentInfoCell($student, $column['key'])) ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
