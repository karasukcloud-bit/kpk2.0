<?php

declare(strict_types=1);

/** @var array $topics */
/** @var array $item */
/** @var array|null $ktpSummary */
/** @var array|null $ktpColumnWidths */

$topics = $topics ?? [];
$item = $item ?? [];
$ktpSummary = $ktpSummary ?? null;
$ktpColumnWidths = $ktpColumnWidths ?? null;
$isProfessionality = curriculum_item_is_professionality($item);
?>
<?php if ($topics === []): ?>
    <p class="text-muted">КТП по этому предмету пока не заполнен.</p>
<?php else: ?>
    <div class="covered-summary ktp-plan-summary ktp-view-print-area">
        <h3>Сводка КТП</h3>
        <?php require __DIR__ . '/summary_table.php'; render_ktp_plan_summary_table($item, $topics); ?>
    </div>

    <div class="table-wrap ktp-view-print-area">
        <table class="table ktp-view-table ktp-rows-table">
            <thead>
                <tr>
                    <th class="ktp-col-num-head"<?= ktp_column_width_attr($ktpColumnWidths, 0) ?>>№</th>
                    <th<?= ktp_column_width_attr($ktpColumnWidths, 1) ?>>Тема</th>
                    <th<?= ktp_column_width_attr($ktpColumnWidths, 2) ?>>Тип</th>
                    <th class="ktp-col-hours-head"<?= ktp_column_width_attr($ktpColumnWidths, 3) ?>>Часы</th>
                    <th<?= ktp_column_width_attr($ktpColumnWidths, 4) ?>>Сроки</th>
                    <th<?= ktp_column_width_attr($ktpColumnWidths, 5) ?>>ОК / ПК</th>
                    <th<?= ktp_column_width_attr($ktpColumnWidths, 6) ?>>Форма контроля</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topics as $index => $topic): ?>
                <?php
                $isSemesterMarker = ktp_is_semester_marker_type((string) ($topic['lesson_type'] ?? ''));
                ?>
                <tr class="<?= $isSemesterMarker ? 'ktp-row--semester-marker' : '' ?>">
                    <td class="ktp-col-num"><?php
                        $topicNum = ktp_topic_display_number($topics, $index);
                        echo $topicNum !== null ? (int) $topicNum : '';
                    ?></td>
                    <?php if ($isSemesterMarker): ?>
                    <td colspan="6"><strong><?= e(ktp_semester_marker_title()) ?></strong></td>
                    <?php else: ?>
                    <td class="ktp-rows-title-cell"><?= e($topic['title']) ?></td>
                    <td><?= e(ktp_lesson_type_label((string) $topic['lesson_type'])) ?></td>
                    <td><?= e(format_ktp_topic_hours($topic, $isProfessionality)) ?></td>
                    <td><?= e(format_ktp_deadline_date($topic['deadline_date'] ?? null)) ?></td>
                    <td class="ktp-competency-cell"><?php render_ktp_competency_cell($topic['ok_codes'] ?? null, $topic['pk_codes'] ?? null); ?></td>
                    <td><?= e(ktp_control_form_label($topic['control_form'] ?? null)) ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
