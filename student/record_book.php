<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/student_accounts.php';
require_once __DIR__ . '/../includes/record_book.php';
require_once __DIR__ . '/../includes/courseworks.php';
require_once __DIR__ . '/../includes/practices.php';
require_once __DIR__ . '/../includes/gia.php';
require_once __DIR__ . '/../includes/curriculum.php';

require_student();

$student = current_student();
if ($student === null) {
    flash_set('error', 'Карточка студента не найдена. Обратитесь к куратору.');
    header('Location: cabinet.php');
    exit;
}

$studentId = (int) $student['id'];
$periods = get_student_record_book($studentId);
$courseworks = get_student_courseworks($studentId);
$practices = get_student_practices($studentId);
$giaEntries = get_student_gia($studentId);
$selectedKey = (string) ($_GET['period'] ?? '');
$isCourseworks = $selectedKey === 'courseworks';
$isPractices = $selectedKey === 'practices';
$isGia = $selectedKey === 'gia';
$isSpecialSection = $isCourseworks || $isPractices || $isGia;
$selected = null;

if (!$isSpecialSection) {
    foreach ($periods as $period) {
        $key = $period['academic_year'] . '|' . $period['semester'];
        if ($selectedKey === $key) {
            $selected = $period;
            break;
        }
    }
    if ($selected === null && $periods !== []) {
        $selected = $periods[0];
        $selectedKey = $selected['academic_year'] . '|' . $selected['semester'];
    } elseif ($selected === null && $periods === []) {
        $isCourseworks = true;
        $selectedKey = 'courseworks';
    }
}

$summary = (!$isSpecialSection && $selected) ? summarize_record_book_period($selected['entries']) : null;
$periodUrl = static function (string $key): string {
    return 'record_book.php?period=' . rawurlencode($key);
};

$pageTitle = 'Электронная зачётная книжка';
$showHeader = true;
$basePath = '../';
$currentStudentTab = 'record_book';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Электронная зачётная книжка</h1>
                <p class="text-muted">
                    <?= e($student['full_name']) ?>
                    · группа <?= e($student['group_number']) ?>
                </p>
            </div>
            <a href="record_book_pdf.php" class="btn btn--primary" target="_blank" rel="noopener">Скачать PDF</a>
        </div>
        <?php require __DIR__ . '/../includes/student_nav.php'; ?>
    </section>

    <section class="panel">
        <div class="record-book">
            <aside class="record-book__periods">
                <h2 class="record-book__aside-title">Разделы</h2>
                <div class="record-book__period-list">
                    <?php foreach ($periods as $period): ?>
                    <?php
                    $key = $period['academic_year'] . '|' . $period['semester'];
                    $periodSummary = summarize_record_book_period($period['entries']);
                    $isActive = !$isSpecialSection && $key === $selectedKey;
                    ?>
                    <a
                        href="<?= e($periodUrl($key)) ?>"
                        class="record-book__period-card<?= $isActive ? ' record-book__period-card--active' : '' ?>"
                    >
                        <span class="record-book__period-year"><?= e($period['academic_year']) ?></span>
                        <span class="record-book__period-sem"><?= e(semester_label($period['semester'])) ?></span>
                        <span class="record-book__period-meta">
                            <?= (int) $periodSummary['graded'] ?> / <?= (int) $periodSummary['total'] ?> оценок
                            <?php if ($periodSummary['average'] !== null): ?>
                                · ср. <?= e((string) $periodSummary['average']) ?>
                            <?php endif; ?>
                        </span>
                    </a>
                    <?php endforeach; ?>

                    <a
                        href="<?= e($periodUrl('courseworks')) ?>"
                        class="record-book__period-card<?= $isCourseworks ? ' record-book__period-card--active' : '' ?>"
                    >
                        <span class="record-book__period-year">Курсовые работы</span>
                        <span class="record-book__period-meta"><?= count($courseworks) ?> записей</span>
                    </a>
                    <a
                        href="<?= e($periodUrl('practices')) ?>"
                        class="record-book__period-card<?= $isPractices ? ' record-book__period-card--active' : '' ?>"
                    >
                        <span class="record-book__period-year">Практики</span>
                        <span class="record-book__period-meta"><?= count($practices) ?> записей</span>
                    </a>
                    <a
                        href="<?= e($periodUrl('gia')) ?>"
                        class="record-book__period-card<?= $isGia ? ' record-book__period-card--active' : '' ?>"
                    >
                        <span class="record-book__period-year">Государственная итоговая аттестация</span>
                        <span class="record-book__period-meta"><?= count($giaEntries) ?> записей</span>
                    </a>
                </div>
            </aside>

            <div class="record-book__sheet">
                <?php if ($isCourseworks): ?>
                    <?php
                    $canEditCourseworks = false;
                    require __DIR__ . '/../includes/record_book_courseworks.php';
                    ?>
                <?php elseif ($isPractices): ?>
                    <?php
                    $canEditPractices = false;
                    require __DIR__ . '/../includes/record_book_practices.php';
                    ?>
                <?php elseif ($isGia): ?>
                    <?php
                    $canEditGia = false;
                    require __DIR__ . '/../includes/record_book_gia.php';
                    ?>
                <?php elseif ($selected === null): ?>
                    <div class="record-book-empty">
                        <h2>Пока нет оценок</h2>
                        <p class="text-muted">
                            Оценки появятся в зачётке после архивации ведомостей за семестр.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="record-book__sheet-head">
                        <div>
                            <h2><?= e($selected['academic_year']) ?></h2>
                            <p class="text-muted"><?= e(semester_label($selected['semester'])) ?></p>
                        </div>
                        <?php if ($summary !== null): ?>
                        <div class="record-book__stats">
                            <div class="record-book__stat">
                                <span class="record-book__stat-value"><?= (int) $summary['graded'] ?></span>
                                <span class="record-book__stat-label">оценок</span>
                            </div>
                            <div class="record-book__stat">
                                <span class="record-book__stat-value">
                                    <?= $summary['average'] !== null ? e((string) $summary['average']) : '—' ?>
                                </span>
                                <span class="record-book__stat-label">средний балл</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php
                    $entries = $selected['entries'];
                    require __DIR__ . '/../includes/record_book_grades.php';
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
