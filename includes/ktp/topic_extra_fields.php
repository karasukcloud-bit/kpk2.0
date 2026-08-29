<?php

declare(strict_types=1);

/** @var array $postData */
$postData = $postData ?? [];
$okSelected = $postData['ok_codes'] ?? [];
$pkSelected = $postData['pk_codes'] ?? [];
if (!is_array($okSelected)) {
    $okSelected = parse_ktp_competency_codes((string) $okSelected);
}
if (!is_array($pkSelected)) {
    $pkSelected = parse_ktp_competency_codes((string) $pkSelected);
}
$fieldPrefix = $fieldPrefix ?? '';
?>
<div class="form__row ktp-extra-row">
    <div class="form__group form__group--compact">
        <label for="<?= e($fieldPrefix) ?>ktp_deadline">Сроки</label>
        <input
            type="date"
            class="ktp-field-compact"
            id="<?= e($fieldPrefix) ?>ktp_deadline"
            name="ktp_deadline"
            value="<?= e((string) ($postData['ktp_deadline'] ?? '')) ?>"
        >
    </div>
    <div class="form__group form__group--compact">
        <label for="<?= e($fieldPrefix) ?>ktp_control_form">Форма контроля</label>
        <?php render_ktp_control_form_select(
            'ktp_control_form',
            normalize_ktp_control_form($postData['ktp_control_form'] ?? ''),
            $fieldPrefix . 'ktp_control_form'
        ); ?>
    </div>
</div>

<?php render_ktp_competency_fields($fieldPrefix, $okSelected, $pkSelected); ?>
