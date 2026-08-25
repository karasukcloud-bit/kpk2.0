<?php

declare(strict_types=1);

/** @var array $list */
/** @var string $viewBase */
/** @var bool $showRestored */
/** @var string|null $success */
/** @var string|null $error */

$dash = static fn ($v): string => ($v === null || $v === '') ? '—' : (string) $v;
?>
<?php if (!empty($success)): ?>
    <div class="alert alert--success"><?= e($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php endif; ?>

<section class="panel">
    <div class="panel__header">
        <h2>Список отчисленных</h2>
        <div class="form__inline-actions">
            <?php if ($showRestored): ?>
                <a href="?active_only=1" class="btn btn--ghost btn--sm">Только невосстановленные</a>
            <?php else: ?>
                <a href="?" class="btn btn--ghost btn--sm">Показать всех</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($list === []): ?>
        <p class="text-muted">Отчисленных студентов пока нет.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>ФИО</th>
                        <th>Группа</th>
                        <th>Приказ</th>
                        <th>Дата отчисления</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $index => $row): ?>
                    <tr class="table__row--clickable"
                        onclick="location.href='<?= e($viewBase) ?>?id=<?= (int) $row['id'] ?>'">
                        <td><?= $index + 1 ?></td>
                        <td><?= e($row['full_name']) ?></td>
                        <td><?= e($dash($row['group_number'] ?? '')) ?></td>
                        <td><?= e($dash($row['expulsion_order'] ?? '')) ?></td>
                        <td><?= e(date('d.m.Y', strtotime((string) $row['expulsion_date']))) ?></td>
                        <td>
                            <?php if ((int) $row['is_restored'] === 1): ?>
                                <span class="badge badge--role badge--educator">Восстановлен</span>
                            <?php else: ?>
                                <span class="badge badge--role badge--deputy">Отчислен</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted table-hint">Нажмите на строку, чтобы открыть карточку.</p>
    <?php endif; ?>
</section>
