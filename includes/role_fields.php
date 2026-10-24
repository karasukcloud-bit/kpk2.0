<?php

declare(strict_types=1);

/** @var array $selectedRoles */
$selectedRoles = $selectedRoles ?? [];
$forCuratorUserId = $forCuratorUserId ?? null;
$selectedCuratorGroupId = $selectedCuratorGroupId ?? null;
$selectedCuratorGroupId2 = $selectedCuratorGroupId2 ?? null;
$selectedCuratorGroupId3 = $selectedCuratorGroupId3 ?? null;
$selectedSpecialtyHeadId = $selectedSpecialtyHeadId ?? null;
$showCuratorGroup = in_array('curator', $selectedRoles, true);
$showSpecialtyHead = in_array('specialty_head', $selectedRoles, true);
?>
<div class="form__group">
    <span class="form__label">Роли в системе</span>
    <p class="form__hint">Можно выбрать несколько ролей. От ролей зависит доступный функционал.</p>
    <div class="role-checkboxes">
        <?php foreach (staff_role_labels() as $roleKey => $roleName): ?>
        <label class="checkbox-label role-checkboxes__item">
            <input
                type="checkbox"
                name="staff_roles[]"
                value="<?= e($roleKey) ?>"
                data-role-checkbox="<?= e($roleKey) ?>"
                <?= in_array($roleKey, $selectedRoles, true) ? 'checked' : '' ?>
            >
            <?= e($roleName) ?>
        </label>
        <?php endforeach; ?>
    </div>
</div>

<div class="form__group curator-group-field<?= $showCuratorGroup ? '' : ' curator-group-field--hidden' ?>" id="curator_group_field">
    <label for="curator_group_id">Курируемые группы</label>
    <p class="form__hint">Можно назначить до 3 групп. Группа, уже назначенная другому куратору, будет недоступна.</p>
    <div class="form__row">
        <div class="form__group">
            <label for="curator_group_id" class="text-muted">Группа 1</label>
            <select id="curator_group_id" name="curator_group_id" data-curator-group-select>
                <?= render_curator_group_options($selectedCuratorGroupId, $forCuratorUserId) ?>
            </select>
        </div>
        <div class="form__group">
            <label for="curator_group_id_2" class="text-muted">Группа 2</label>
            <select id="curator_group_id_2" name="curator_group_id_2" data-curator-group-select>
                <?= render_curator_group_options($selectedCuratorGroupId2, $forCuratorUserId) ?>
            </select>
        </div>
        <div class="form__group">
            <label for="curator_group_id_3" class="text-muted">Группа 3</label>
            <select id="curator_group_id_3" name="curator_group_id_3" data-curator-group-select>
                <?= render_curator_group_options($selectedCuratorGroupId3, $forCuratorUserId) ?>
            </select>
        </div>
    </div>
</div>

<div class="form__group specialty-head-field<?= $showSpecialtyHead ? '' : ' specialty-head-field--hidden' ?>" id="specialty_head_field">
    <label for="specialty_head_id">Специальность</label>
    <p class="form__hint">Укажите специальность, которой руководит сотрудник. Одна специальность может быть назначена только одному руководителю.</p>
    <select id="specialty_head_id" name="specialty_head_id">
        <?= render_specialty_head_options($selectedSpecialtyHeadId, $forCuratorUserId) ?>
    </select>
</div>

<script>
(function () {
    function initCuratorGroupToggle() {
        var checkbox = document.querySelector('[data-role-checkbox="curator"]');
        var field = document.getElementById('curator_group_field');
        if (!checkbox || !field) {
            return;
        }

        function updateVisibility() {
            field.classList.toggle('curator-group-field--hidden', !checkbox.checked);
        }

        checkbox.addEventListener('change', updateVisibility);
        updateVisibility();
    }

    function initCuratorGroupSelects() {
        var selects = Array.prototype.slice.call(
            document.querySelectorAll('[data-curator-group-select]')
        );
        if (selects.length < 2) {
            return;
        }

        function syncSelects() {
            var taken = {};
            selects.forEach(function (select) {
                if (select.value !== '') {
                    taken[select.value] = true;
                }
            });

            selects.forEach(function (select) {
                Array.prototype.forEach.call(select.options, function (option) {
                    if (option.value === '') {
                        return;
                    }
                    var hide = taken[option.value] === true && select.value !== option.value;
                    option.hidden = hide;
                    option.disabled = hide;
                    if (hide && select.value === option.value) {
                        select.value = '';
                    }
                });
            });
        }

        selects.forEach(function (select) {
            select.addEventListener('change', syncSelects);
        });
        syncSelects();
    }

    function initCuratorRoleFields() {
        initCuratorGroupToggle();
        initCuratorGroupSelects();
    }

    function initSpecialtyHeadToggle() {
        var checkbox = document.querySelector('[data-role-checkbox="specialty_head"]');
        var field = document.getElementById('specialty_head_field');
        if (!checkbox || !field) {
            return;
        }

        function updateVisibility() {
            field.classList.toggle('specialty-head-field--hidden', !checkbox.checked);
        }

        checkbox.addEventListener('change', updateVisibility);
        updateVisibility();
    }

    function initRoleFields() {
        initCuratorRoleFields();
        initSpecialtyHeadToggle();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRoleFields);
    } else {
        initRoleFields();
    }
})();
</script>
