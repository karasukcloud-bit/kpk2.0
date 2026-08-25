<?php

declare(strict_types=1);

/** @var array $entries */

$sections = split_record_book_entries($entries);
$sectionBlocks = [
    [
        'title' => 'Зачёты',
        'hint' => 'Зачёт, дифференцированный зачёт',
        'items' => $sections['credits'],
    ],
    [
        'title' => 'Экзамены',
        'hint' => 'Экзамен',
        'items' => $sections['exams'],
    ],
];
?>
<div class="record-book__grades">
    <?php foreach ($sectionBlocks as $block): ?>
        <?php if ($block['items'] === []) {
            continue;
        } ?>
        <div class="record-book__section">
            <div class="record-book__section-head">
                <h3 class="record-book__section-title"><?= e($block['title']) ?></h3>
                <span class="record-book__section-hint"><?= e($block['hint']) ?></span>
            </div>
            <?php foreach ($block['items'] as $entry): ?>
                <?php $grade = $entry['grade']; ?>
                <div class="record-book__row">
                    <div class="record-book__subject">
                        <?= e($entry['subject_name']) ?>
                        <?php
                        $formLabel = record_book_attestation_label((string) ($entry['attestation_form'] ?? ''));
                        $metaParts = [];
                        if ($formLabel !== '') {
                            $metaParts[] = $formLabel;
                        }
                        if (!empty($entry['teacher_name'])) {
                            $metaParts[] = (string) $entry['teacher_name'];
                        }
                        ?>
                        <?php if ($metaParts !== []): ?>
                        <span class="record-book__teacher"><?= e(implode(' · ', $metaParts)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="record-book__grade<?= $grade !== null && $grade !== '' ? ' record-book__grade--' . (int) $grade : '' ?>">
                        <?= $grade !== null && $grade !== '' ? e((string) (int) $grade) : '—' ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
