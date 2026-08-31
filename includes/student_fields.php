<?php

declare(strict_types=1);

/** @var array $data */
$data = $data ?? [];
$nameParts = [
    'last_name' => (string) ($data['last_name'] ?? ''),
    'first_name' => (string) ($data['first_name'] ?? ''),
    'middle_name' => (string) ($data['middle_name'] ?? ''),
];
if ($nameParts['last_name'] === '' && $nameParts['first_name'] === '') {
    $nameParts = split_person_full_name((string) ($data['full_name'] ?? ''));
}
?>
<div class="student-fields">
<div class="form__group">
    <label for="last_name">Фамилия</label>
    <input
        type="text"
        id="last_name"
        name="last_name"
        class="form-input--name"
        required
        value="<?= e($nameParts['last_name']) ?>"
        placeholder="Иванов"
        autocomplete="family-name"
    >
</div>

<div class="form__row">
    <div class="form__group">
        <label for="first_name">Имя</label>
        <input
            type="text"
            id="first_name"
            name="first_name"
            class="form-input--name"
            required
            value="<?= e($nameParts['first_name']) ?>"
            placeholder="Иван"
            autocomplete="given-name"
        >
    </div>
    <div class="form__group">
        <label for="middle_name">Отчество</label>
        <input
            type="text"
            id="middle_name"
            name="middle_name"
            class="form-input--name"
            value="<?= e($nameParts['middle_name']) ?>"
            placeholder="Иванович"
            autocomplete="additional-name"
        >
    </div>
</div>

<div class="form__group">
    <label for="phone">Телефон студента</label>
    <input type="text" id="phone" name="phone"
           class="form-input--phone"
           value="<?= e($data['phone'] ?? '') ?>"
           placeholder="+7 (___) ___-__-__">
</div>

<div class="form__group">
    <label for="snils">СНИЛС</label>
    <input
        type="text"
        id="snils"
        name="snils"
        class="form-input--snils"
        value="<?= e((string) ($data['snils'] ?? '')) ?>"
        placeholder="___-___-___ __"
        maxlength="14"
        inputmode="numeric"
        autocomplete="off"
        data-snils-mask
    >
</div>

<div class="form__row">
    <div class="form__group">
        <label for="birth_date">Дата рождения</label>
        <input
            type="date"
            id="birth_date"
            name="birth_date"
            class="form-input--date"
            value="<?= e((string) ($data['birth_date'] ?? '')) ?>"
        >
    </div>
    <div class="form__group">
        <label for="gender">Пол</label>
        <select id="gender" name="gender" class="form-input--select-sm">
            <option value="">— Не указан —</option>
            <option value="male"<?= (($data['gender'] ?? '') === 'male') ? ' selected' : '' ?>>Мужской</option>
            <option value="female"<?= (($data['gender'] ?? '') === 'female') ? ' selected' : '' ?>>Женский</option>
        </select>
    </div>
</div>

<div class="form__row">
    <div class="form__group">
        <label for="family_type">Состав семьи</label>
        <select id="family_type" name="family_type" class="form-input--select-md" data-family-type>
            <option value="">— Не указан —</option>
            <option value="complete"<?= (($data['family_type'] ?? '') === 'complete') ? ' selected' : '' ?>>Полная семья</option>
            <option value="no_father"<?= (($data['family_type'] ?? '') === 'no_father') ? ' selected' : '' ?>>Неполная (без отца)</option>
            <option value="no_mother"<?= (($data['family_type'] ?? '') === 'no_mother') ? ' selected' : '' ?>>Неполная (без матери)</option>
        </select>
    </div>
    <div class="form__group">
        <label for="siblings_under_18">Братьев/сестёр младше 18 лет</label>
        <input
            type="number"
            id="siblings_under_18"
            name="siblings_under_18"
            class="form-input--short"
            min="0"
            max="20"
            value="<?= e((string) (int) ($data['siblings_under_18'] ?? 0)) ?>"
        >
    </div>
</div>

<div class="form__row" data-parent-field="mother">
    <div class="form__group">
        <label for="mother_name"><?= e(student_parent_field_label('mother', 'name')) ?></label>
        <input type="text" id="mother_name" name="mother_name"
               class="form-input--fio"
               value="<?= e($data['mother_name'] ?? '') ?>">
    </div>
    <div class="form__group">
        <label for="mother_phone"><?= e(student_parent_field_label('mother', 'phone')) ?></label>
        <input type="text" id="mother_phone" name="mother_phone"
               class="form-input--phone"
               value="<?= e($data['mother_phone'] ?? '') ?>">
    </div>
</div>

<div class="form__group" data-parent-field="mother">
    <label for="mother_workplace"><?= e(student_parent_field_label('mother', 'workplace')) ?></label>
    <input type="text" id="mother_workplace" name="mother_workplace"
           class="form-input--wide"
           value="<?= e($data['mother_workplace'] ?? '') ?>">
</div>

<div class="form__group" data-parent-field="mother">
    <label for="mother_education"><?= e(student_parent_field_label('mother', 'education')) ?></label>
    <?php render_student_education_select('mother_education', $data['mother_education'] ?? '', 'mother_education'); ?>
</div>

<div class="form__row" data-parent-field="father">
    <div class="form__group">
        <label for="father_name"><?= e(student_parent_field_label('father', 'name')) ?></label>
        <input type="text" id="father_name" name="father_name"
               class="form-input--fio"
               value="<?= e($data['father_name'] ?? '') ?>">
    </div>
    <div class="form__group">
        <label for="father_phone"><?= e(student_parent_field_label('father', 'phone')) ?></label>
        <input type="text" id="father_phone" name="father_phone"
               class="form-input--phone"
               value="<?= e($data['father_phone'] ?? '') ?>">
    </div>
</div>

<div class="form__group" data-parent-field="father">
    <label for="father_workplace"><?= e(student_parent_field_label('father', 'workplace')) ?></label>
    <input type="text" id="father_workplace" name="father_workplace"
           class="form-input--wide"
           value="<?= e($data['father_workplace'] ?? '') ?>">
</div>

<div class="form__group" data-parent-field="father">
    <label for="father_education"><?= e(student_parent_field_label('father', 'education')) ?></label>
    <?php render_student_education_select('father_education', $data['father_education'] ?? '', 'father_education'); ?>
</div>

<div class="form__group">
    <span class="form__label">Адрес по прописке</span>
    <div class="form__row">
        <div class="form__group">
            <label for="address_region">Область / край</label>
            <input
                type="text"
                id="address_region"
                name="address_region"
                class="form-input--address"
                value="<?= e($data['address_region'] ?? '') ?>"
                data-student-address-part
                placeholder="Брянская область"
            >
        </div>
        <div class="form__group">
            <label for="address_district">Район / округ</label>
            <input
                type="text"
                id="address_district"
                name="address_district"
                class="form-input--address"
                value="<?= e($data['address_district'] ?? '') ?>"
                data-student-address-part
                placeholder="Клинцовский район"
            >
        </div>
    </div>
    <div class="form__row">
        <div class="form__group">
            <label for="address_locality">Населённый пункт</label>
            <input
                type="text"
                id="address_locality"
                name="address_locality"
                class="form-input--address"
                value="<?= e($data['address_locality'] ?? '') ?>"
                data-student-address-part
                placeholder="г. Клинцы"
            >
        </div>
        <div class="form__group">
            <label for="address_street">Улица</label>
            <input
                type="text"
                id="address_street"
                name="address_street"
                class="form-input--address"
                value="<?= e($data['address_street'] ?? '') ?>"
                data-student-address-part
                placeholder="ул. Ленина"
            >
        </div>
        <div class="form__group">
            <label for="address_house">Дом</label>
            <input
                type="text"
                id="address_house"
                name="address_house"
                class="form-input--house"
                value="<?= e($data['address_house'] ?? '') ?>"
                data-student-address-part
                placeholder="12"
            >
        </div>
    </div>
    <input type="hidden" name="address_registered" id="address_registered"
           value="<?= e(compose_student_registered_address($data) !== ''
               ? compose_student_registered_address($data)
               : (string) ($data['address_registered'] ?? '')) ?>"
           data-student-address-registered>
</div>

<?php
$dormitoryAddress = function_exists('student_dormitory_address')
    ? student_dormitory_address()
    : 'Общежитие';
?>
<div class="form__row">
    <div class="form__group">
        <label for="residence_type">Место проживания</label>
        <select
            id="residence_type"
            name="residence_type"
            class="form-input--select-md"
            data-student-residence
            data-dormitory-address="<?= e($dormitoryAddress) ?>"
        >
            <option value="">— Не указано —</option>
            <option value="family"<?= (($data['residence_type'] ?? '') === 'family') ? ' selected' : '' ?>>С родителями / родственниками</option>
            <option value="dormitory"<?= (($data['residence_type'] ?? '') === 'dormitory') ? ' selected' : '' ?>>Общежитие</option>
            <option value="apartment"<?= (($data['residence_type'] ?? '') === 'apartment') ? ' selected' : '' ?>>Квартира (наём)</option>
        </select>
    </div>
    <div class="form__group form__group--checkboxes">
        <label class="checkbox-label">
            <input type="checkbox" name="is_nonresident" value="1"
                <?= !empty($data['is_nonresident']) ? 'checked' : '' ?>>
            Иногородний студент
        </label>
        <label class="checkbox-label">
            <input type="checkbox" name="is_low_income" value="1"
                <?= !empty($data['is_low_income']) ? 'checked' : '' ?>>
            Малообеспеченная семья
        </label>
        <label class="checkbox-label">
            <input type="checkbox" name="without_parental_care" value="1"
                <?= !empty($data['without_parental_care']) ? 'checked' : '' ?>>
            Оставшийся без попечительства родителей
        </label>
    </div>
</div>

<div class="form__group">
    <label for="address_actual">Фактический адрес</label>
    <input type="text" id="address_actual" name="address_actual"
           class="form-input--wide"
           value="<?= e($data['address_actual'] ?? '') ?>"
           data-student-address-actual
           placeholder="Заполняется автоматически при выборе места проживания">
    <p class="text-muted form-hint">
        При выборе «с родителями» подставляется адрес по прописке,
        при «общежитие» — адрес организации. Можно изменить вручную.
    </p>
</div>
</div>
