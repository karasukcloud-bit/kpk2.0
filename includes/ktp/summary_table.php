<?php

declare(strict_types=1);

function format_ktp_workload_pair(float $hours, float $orient, bool $isProfessionality): string
{
    if ($hours <= 0 && $orient <= 0) {
        return '—';
    }

    $total = format_ktp_summary_number($hours);
    if (!$isProfessionality) {
        return $total;
    }

    return $total . '/' . format_ktp_summary_number($orient);
}

function format_ktp_workload_display(array $row, bool $isProfessionality): string
{
    $kind = (string) ($row['kind'] ?? 'pair');

    if ($kind === 'dash') {
        return '—';
    }

    if ($kind === 'text') {
        $text = trim((string) ($row['text'] ?? ''));

        return $text !== '' ? $text : '—';
    }

    $hours = (float) ($row['hours'] ?? 0);
    $orient = (float) ($row['orient'] ?? 0);

    if ($kind === 'total_only') {
        return $hours > 0 ? format_ktp_summary_number($hours) : '—';
    }

    return format_ktp_workload_pair($hours, $orient, $isProfessionality);
}

function format_ktp_workload_semester_display(array $row, bool $isProfessionality, int $semesterIndex): string
{
    $kind = (string) ($row['kind'] ?? 'pair');

    if ($kind === 'dash') {
        return '—';
    }

    if ($kind === 'text') {
        $text = trim((string) (($row['semester_text'][$semesterIndex] ?? '')));

        return $text !== '' ? $text : '—';
    }

    $metrics = $row['semester_metrics'][$semesterIndex] ?? ['hours' => 0.0, 'orient' => 0.0];
    $hours = (float) ($metrics['hours'] ?? 0);
    $orient = (float) ($metrics['orient'] ?? 0);

    if ($kind === 'total_only') {
        return $hours > 0 ? format_ktp_summary_number($hours) : '—';
    }

    return format_ktp_workload_pair($hours, $orient, $isProfessionality);
}

function render_ktp_workload_value_cell(
    string $display,
    bool $liveUpdate,
    string $rowKey,
    string $columnType,
    int $semesterIndex = 0,
    string $tableClass = 'ktp-workload-table'
): void {
    $attrs = '';

    if ($liveUpdate) {
        if ($columnType === 'course') {
            $attrs = ' data-ktp-workload-course="' . e($rowKey) . '"';
        } else {
            $attrs = ' data-ktp-workload-row="' . e($rowKey) . '" data-ktp-workload-sem="' . $semesterIndex . '"';
        }
    }

    echo '<td class="' . e($tableClass) . '__value"' . $attrs . '>' . e($display) . '</td>';
}

function render_ktp_workload_corner_cell(string $tableClass, bool $forPdf): void
{
    if ($forPdf) {
        ?>
        <th class="<?= e($tableClass) ?>__corner" rowspan="5">
            <table class="<?= e($tableClass) ?>__corner-grid" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="<?= e($tableClass) ?>__corner-top">Количество часов</td>
                </tr>
                <tr>
                    <td class="<?= e($tableClass) ?>__corner-line"></td>
                </tr>
                <tr>
                    <td class="<?= e($tableClass) ?>__corner-bottom">Учебная нагрузка</td>
                </tr>
            </table>
        </th>
        <?php
        return;
    }
    ?>
    <th class="<?= e($tableClass) ?>__corner" rowspan="5">
        <span class="<?= e($tableClass) ?>__corner-top">Количество часов</span>
        <span class="<?= e($tableClass) ?>__corner-bottom">Учебная нагрузка</span>
    </th>
    <?php
}

function render_ktp_plan_summary_table(array $item, array $topics, bool $liveUpdate = false, bool $forPdf = false): void
{
    $data = build_ktp_workload_table_data($item, $topics);
    $isProfessionality = (bool) $data['is_professionality'];
    $semesterSlots = $data['semester_slots'];
    $courseYears = $data['course_years'];
    $slotsAttr = implode(',', array_map('strval', $semesterSlots));
    $splitSemesters = count($semesterSlots) > 1 ? '1' : '0';
    $tableClass = $forPdf ? 'pdf-workload-table' : 'ktp-workload-table';
    ?>
    <?php if (!$forPdf): ?><div class="ktp-workload-table-wrap"><?php endif; ?>
        <table
            class="<?= $forPdf ? e($tableClass) : 'table ' . e($tableClass) ?>"
            <?php if ($liveUpdate): ?>
            data-ktp-workload-table
            data-semester-slots="<?= e($slotsAttr) ?>"
            data-split-semesters="<?= e($splitSemesters) ?>"
            data-professionality="<?= $isProfessionality ? '1' : '0' ?>"
            <?php endif; ?>
        >
            <colgroup>
                <col style="width:24%">
                <col style="width:7%">
                <?php for ($semester = 1; $semester <= 8; $semester++): ?>
                <col style="width:8.625%">
                <?php endfor; ?>
            </colgroup>
            <thead>
                <tr>
                    <?php render_ktp_workload_corner_cell($tableClass, $forPdf); ?>
                    <th class="<?= e($tableClass) ?>__course-col" rowspan="5">Учебная нагрузка на курс обучения</th>
                    <th colspan="8">Распределение по курсам и семестрам</th>
                </tr>
                <tr>
                    <?php for ($course = 1; $course <= 4; $course++): ?>
                    <th colspan="2"><?= $course ?> курс</th>
                    <?php endfor; ?>
                </tr>
                <tr>
                    <?php foreach ($courseYears as $yearLabel): ?>
                    <th colspan="2"><?= e($yearLabel) ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th colspan="8" class="<?= e($tableClass) ?>__year-note">(Год обучения)</th>
                </tr>
                <tr>
                    <?php for ($semester = 1; $semester <= 8; $semester++): ?>
                    <th><?= $semester ?> сем</th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['rows'] as $row): ?>
                <?php
                $rowKey = (string) $row['key'];
                $courseDisplay = format_ktp_workload_display($row, $isProfessionality);
                ?>
                <tr>
                    <td class="<?= e($tableClass) ?>__label">
                        <?= e((string) $row['label']) ?>
                        <?php if (!empty($row['sub'])): ?>
                        <span class="<?= e($tableClass) ?>__label-sub"> / <?= e((string) $row['sub']) ?></span>
                        <?php endif; ?>
                    </td>
                    <?php render_ktp_workload_value_cell($courseDisplay, $liveUpdate, $rowKey, 'course', 0, $tableClass); ?>
                    <?php for ($semester = 1; $semester <= 8; $semester++): ?>
                    <?php
                    $semesterDisplay = in_array($semester, $semesterSlots, true)
                        ? format_ktp_workload_semester_display($row, $isProfessionality, $semester)
                        : '—';
                    render_ktp_workload_value_cell($semesterDisplay, $liveUpdate, $rowKey, 'sem', $semester, $tableClass);
                    ?>
                    <?php endfor; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php if (!$forPdf): ?></div><?php endif; ?>
    <?php
}
