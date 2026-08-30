<?php

declare(strict_types=1);

/** @var array $selectedRoles */
$selectedRoles = $selectedRoles ?? [];
$forCuratorUserId = $forCuratorUserId ?? null;
$selectedCuratorGroupId = $selectedCuratorGroupId ?? null;
$selectedCuratorGroupId2 = $selectedCuratorGroupId2 ?? null;
$showCuratorGroup = in_array('curator', $selectedRoles, true);
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
    <label for="curator_group_id">Курируемая группа</label>
    <p class="form__hint">Можно назначить до 2 групп. Группа, уже назначенная другому куратору, будет недоступна.</p>
    <div class="form__row">
        <div class="form__group">
            <label for="curator_group_id" class="text-muted">Группа 1</label>
            <select id="curator_group_id" name="curator_group_id" data-curator-group-primary>
                <?= render_curator_group_options($selectedCuratorGroupId, $forCuratorUserId) ?>
            </select>
        </div>
        <div class="form__group">
            <label for="curator_group_id_2" class="text-muted">Группа 2</label>
            <select id="curator_group_id_2" name="curator_group_id_2" data-curator-group-secondary>
                <?= render_curator_group_options($selectedCuratorGroupId2, $forCuratorUserId, (int) $selectedCuratorGroupId) ?>
            </select>
        </div>
    </div>
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

    function initCuratorGroupPair() {
        var primary = document.querySelector('[data-curator-group-primary]');
        var secondary = document.querySelector('[data-curator-group-secondary]');
        if (!primary || !secondary) {
            return;
        }

        function syncSecondary() {
            var primaryValue = primary.value;
            Array.prototype.forEach.call(secondary.options, function (option) {
                if (option.value === '') {
                    return;
                }
                var hide = option.value === primaryValue && primaryValue !== '';
                option.hidden = hide;
                option.disabled = hide;
                if (hide && secondary.value === option.value) {
                    secondary.value = '';
                }
            });
        }

        primary.addEventListener('change', syncSecondary);
        syncSecondary();
    }

    function initCuratorRoleFields() {
        initCuratorGroupToggle();
        initCuratorGroupPair();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCuratorRoleFields);
    } else {
        initCuratorRoleFields();
    }
})();
</script>
