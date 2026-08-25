<?php

declare(strict_types=1);

/** @var array $selectedRoles */
$selectedRoles = $selectedRoles ?? [];
$forCuratorUserId = $forCuratorUserId ?? null;
$selectedCuratorGroupId = $selectedCuratorGroupId ?? null;
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
    <p class="form__hint">Группа, уже назначенная другому куратору, будет недоступна для выбора.</p>
    <select id="curator_group_id" name="curator_group_id">
        <?= render_curator_group_options($selectedCuratorGroupId, $forCuratorUserId) ?>
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCuratorGroupToggle);
    } else {
        initCuratorGroupToggle();
    }
})();
</script>
