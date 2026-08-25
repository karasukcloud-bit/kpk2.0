<?php

declare(strict_types=1);

/** @var array $preview */
/** @var string $defaultYear */
/** @var string $panelTitle */
/** @var string $navFile */
/** @var string|null $success */
/** @var string|null $error */

$yearValue = (string) ($_POST['academic_year'] ?? $defaultYear);
?>
<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1><?= e($panelTitle) ?></h1>
                <p class="text-muted">Перевод групп на следующий курс без потери данных</p>
            </div>
        </div>
        <?php require $navFile; ?>
    </section>

    <?php if (!empty($success)): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel panel--info">
        <h2>Как это работает</h2>
        <p class="text-muted">
            После учебного года меняется только <strong>номер</strong> группы (например, 201 → 301 → 401).
            Состав студентов и идентификатор группы не меняются: зачётки, журналы, ведомости, пропуски
            и архивы сохраняются. Учебные планы прошлых лет остаются привязанными к учебному году.
            Куратору группы приходит уведомление о переводе.
        </p>
    </section>

    <section class="panel">
        <h2>Перевод групп</h2>
        <?php if ($preview === []): ?>
            <p class="text-muted">Группы пока не добавлены.</p>
        <?php else: ?>
            <form method="post" class="form" id="promote-groups-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="promote-action" value="promote_selected">
                <input type="hidden" name="group_id" id="promote-group-id" value="">
                <input type="hidden" name="new_number" id="promote-new-number" value="">

                <div class="form__group">
                    <label for="academic_year">Завершённый учебный год</label>
                    <input
                        type="text"
                        id="academic_year"
                        name="academic_year"
                        required
                        value="<?= e($yearValue) ?>"
                        placeholder="2025-2026"
                    >
                </div>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="promote-check-all" title="Выбрать все доступные">
                                </th>
                                <th>Текущий номер</th>
                                <th>Специальность</th>
                                <th>Студентов</th>
                                <th>Куратор</th>
                                <th>Новый номер</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview as $row): ?>
                            <?php
                            $g = $row['group'];
                            $gid = (int) $g['id'];
                            $suggested = $row['suggested'];
                            $canPromote = $suggested !== null && $row['conflict'] === null;
                            $posted = $_POST['promote'][$gid] ?? null;
                            $checked = is_array($posted) && !empty($posted['checked']);
                            $newValue = is_array($posted) && isset($posted['new_number'])
                                ? (string) $posted['new_number']
                                : (string) ($suggested ?? '');
                            ?>
                            <tr>
                                <td>
                                    <?php if ($canPromote): ?>
                                    <input
                                        type="checkbox"
                                        class="promote-row-check"
                                        name="promote[<?= $gid ?>][checked]"
                                        value="1"
                                        <?= $checked ? ' checked' : '' ?>
                                    >
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= e($g['number']) ?></strong></td>
                                <td><?= e($g['specialty_name']) ?></td>
                                <td><?= (int) $row['students'] ?></td>
                                <td><?= e(($g['curator_name'] ?? '') !== '' ? $g['curator_name'] : '—') ?></td>
                                <td>
                                    <?php if ($canPromote): ?>
                                    <input
                                        type="text"
                                        name="promote[<?= $gid ?>][new_number]"
                                        id="promote-num-<?= $gid ?>"
                                        value="<?= e($newValue) ?>"
                                        required
                                        class="promote-new-number"
                                    >
                                    <?php elseif ($suggested === null): ?>
                                        <span class="text-muted">Нет предложения номера</span>
                                    <?php else: ?>
                                        <span class="text-muted">Номер <?= e((string) $row['conflict']) ?> занят</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($canPromote): ?>
                                    <button
                                        type="button"
                                        class="btn btn--ghost btn--sm"
                                        data-promote-one="<?= $gid ?>"
                                    >Перевести</button>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form__actions">
                    <button type="submit" class="btn btn--primary" data-promote-bulk>
                        Перевести отмеченные
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>
<script>
(() => {
    const form = document.getElementById('promote-groups-form');
    if (!form) return;

    const actionInput = document.getElementById('promote-action');
    const groupIdInput = document.getElementById('promote-group-id');
    const newNumberInput = document.getElementById('promote-new-number');
    const all = document.getElementById('promote-check-all');

    if (all) {
        all.addEventListener('change', () => {
            form.querySelectorAll('.promote-row-check').forEach((cb) => {
                cb.checked = all.checked;
            });
        });
    }

    form.querySelectorAll('[data-promote-one]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const gid = btn.getAttribute('data-promote-one');
            const num = document.getElementById('promote-num-' + gid);
            if (!num || !num.value.trim()) {
                alert('Укажите новый номер группы.');
                return;
            }
            if (!confirm('Перевести группу на номер ' + num.value.trim() + '? Состав и данные сохранятся.')) {
                return;
            }
            actionInput.value = 'promote_one';
            groupIdInput.value = gid;
            newNumberInput.value = num.value.trim();
            form.submit();
        });
    });

    const bulkBtn = form.querySelector('[data-promote-bulk]');
    if (bulkBtn) {
        bulkBtn.addEventListener('click', (event) => {
            actionInput.value = 'promote_selected';
            groupIdInput.value = '';
            newNumberInput.value = '';
            const checked = form.querySelectorAll('.promote-row-check:checked');
            if (!checked.length) {
                event.preventDefault();
                alert('Отметьте хотя бы одну группу.');
                return;
            }
            if (!confirm('Перевести отмеченные группы? Состав студентов и все данные сохранятся.')) {
                event.preventDefault();
            }
        });
    }
})();
</script>
