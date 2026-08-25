<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/record_book.php';
require_once __DIR__ . '/../includes/courseworks.php';
require_once __DIR__ . '/../includes/practices.php';
require_once __DIR__ . '/../includes/gia.php';
require_once __DIR__ . '/../includes/curriculum.php';

require_login();
if (!can_use_deputy_panel()) {
    http_response_code(403);
    exit('Доступ запрещён. Требуются права завуча или администратора.');
}

$studentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$student = $studentId > 0 ? get_student_by_id($studentId) : null;

if ($student === null) {
    flash_set('error', 'Студент не найден.');
    header('Location: record_books.php');
    exit;
}

if ($groupId <= 0) {
    $groupId = (int) $student['group_id'];
}

$extraError = null;
$extraSuccess = flash_get('success');
$extraPeriod = 'courseworks';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $extraError = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'add_coursework' || $action === 'update_coursework') {
            $extraPeriod = 'courseworks';
            $id = $action === 'update_coursework' ? (int) ($_POST['coursework_id'] ?? 0) : 0;
            $result = save_student_coursework($studentId, $_POST, $id);
            if ($result['success']) {
                flash_set('success', $id > 0 ? 'Курсовая работа обновлена.' : 'Курсовая работа добавлена.');
                header('Location: record_book.php?' . http_build_query([
                    'id' => $studentId,
                    'group_id' => $groupId,
                    'period' => 'courseworks',
                ]));
                exit;
            }
            $extraError = $result['error'];
        } elseif ($action === 'delete_coursework') {
            $extraPeriod = 'courseworks';
            $result = delete_student_coursework($studentId, (int) ($_POST['coursework_id'] ?? 0));
            if ($result['success']) {
                flash_set('success', 'Запись курсовой работы удалена.');
                header('Location: record_book.php?' . http_build_query([
                    'id' => $studentId,
                    'group_id' => $groupId,
                    'period' => 'courseworks',
                ]));
                exit;
            }
            $extraError = $result['error'];
        } elseif ($action === 'add_practice' || $action === 'update_practice') {
            $extraPeriod = 'practices';
            $id = $action === 'update_practice' ? (int) ($_POST['practice_id'] ?? 0) : 0;
            $result = save_student_practice($studentId, $_POST, $id);
            if ($result['success']) {
                flash_set('success', $id > 0 ? 'Практика обновлена.' : 'Практика добавлена.');
                header('Location: record_book.php?' . http_build_query([
                    'id' => $studentId,
                    'group_id' => $groupId,
                    'period' => 'practices',
                ]));
                exit;
            }
            $extraError = $result['error'];
        } elseif ($action === 'delete_practice') {
            $extraPeriod = 'practices';
            $result = delete_student_practice($studentId, (int) ($_POST['practice_id'] ?? 0));
            if ($result['success']) {
                flash_set('success', 'Запись о практике удалена.');
                header('Location: record_book.php?' . http_build_query([
                    'id' => $studentId,
                    'group_id' => $groupId,
                    'period' => 'practices',
                ]));
                exit;
            }
            $extraError = $result['error'];
        } elseif ($action === 'add_gia' || $action === 'update_gia') {
            $extraPeriod = 'gia';
            $id = $action === 'update_gia' ? (int) ($_POST['gia_id'] ?? 0) : 0;
            $result = save_student_gia($studentId, $_POST, $id);
            if ($result['success']) {
                flash_set('success', $id > 0 ? 'Запись ГИА обновлена.' : 'Запись ГИА добавлена.');
                header('Location: record_book.php?' . http_build_query([
                    'id' => $studentId,
                    'group_id' => $groupId,
                    'period' => 'gia',
                ]));
                exit;
            }
            $extraError = $result['error'];
        } elseif ($action === 'delete_gia') {
            $extraPeriod = 'gia';
            $result = delete_student_gia($studentId, (int) ($_POST['gia_id'] ?? 0));
            if ($result['success']) {
                flash_set('success', 'Запись ГИА удалена.');
                header('Location: record_book.php?' . http_build_query([
                    'id' => $studentId,
                    'group_id' => $groupId,
                    'period' => 'gia',
                ]));
                exit;
            }
            $extraError = $result['error'];
        }
    }
}

$group = get_group_by_id((int) $student['group_id']);
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

if ($extraError !== null) {
    $isSpecialSection = true;
    $isCourseworks = $extraPeriod === 'courseworks';
    $isPractices = $extraPeriod === 'practices';
    $isGia = $extraPeriod === 'gia';
    $selectedKey = $extraPeriod;
}

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
        $isSpecialSection = true;
        $selectedKey = 'courseworks';
    }
}

$summary = (!$isSpecialSection && $selected) ? summarize_record_book_period($selected['entries']) : null;
$backUrl = 'record_books.php?group_id=' . $groupId;
$periodUrl = static function (string $key) use ($studentId, $groupId): string {
    return 'record_book.php?' . http_build_query([
        'id' => $studentId,
        'group_id' => $groupId,
        'period' => $key,
    ]);
};

$courseworkError = ($extraError !== null && $extraPeriod === 'courseworks') ? $extraError : null;
$courseworkSuccess = $isCourseworks ? $extraSuccess : null;
$practiceError = ($extraError !== null && $extraPeriod === 'practices') ? $extraError : null;
$practiceSuccess = $isPractices ? $extraSuccess : null;
$giaError = ($extraError !== null && $extraPeriod === 'gia') ? $extraError : null;
$giaSuccess = $isGia ? $extraSuccess : null;

$editCoursework = null;
if ($isCourseworks && isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $found = get_student_coursework($editId);
    if ($found && (int) $found['student_id'] === $studentId) {
        $editCoursework = $found;
    }
}
if ($courseworkError !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $editCoursework = [
        'id' => (int) ($_POST['coursework_id'] ?? 0),
        'subject_name' => (string) ($_POST['subject_name'] ?? ''),
        'topic' => (string) ($_POST['topic'] ?? ''),
        'defense_date' => (string) ($_POST['defense_date'] ?? ''),
        'teacher_name' => (string) ($_POST['teacher_name'] ?? ''),
        'grade' => $_POST['grade'] ?? '',
    ];
}

$editPractice = null;
if ($isPractices && isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $found = get_student_practice($editId);
    if ($found && (int) $found['student_id'] === $studentId) {
        $editPractice = $found;
    }
}
if ($practiceError !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $editPractice = [
        'id' => (int) ($_POST['practice_id'] ?? 0),
        'module_name' => (string) ($_POST['module_name'] ?? ''),
        'org_supervisor_name' => (string) ($_POST['org_supervisor_name'] ?? ''),
        'college_supervisor_name' => (string) ($_POST['college_supervisor_name'] ?? ''),
        'grade' => $_POST['grade'] ?? '',
    ];
}

$editGia = null;
if ($isGia && isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $found = get_student_gia_entry($editId);
    if ($found && (int) $found['student_id'] === $studentId) {
        $editGia = $found;
    }
}
if ($giaError !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $editGia = [
        'id' => (int) ($_POST['gia_id'] ?? 0),
        'form_type' => (string) ($_POST['form_type'] ?? 'demo_exam'),
        'module_name' => (string) ($_POST['module_name'] ?? ''),
        'code' => (string) ($_POST['code'] ?? ''),
        'points' => $_POST['points'] ?? '',
        'topic' => (string) ($_POST['topic'] ?? ''),
        'defense_date' => (string) ($_POST['defense_date'] ?? ''),
        'grade' => $_POST['grade'] ?? '',
    ];
}

$pageTitle = 'Зачётная книжка — ' . $student['full_name'];
$showHeader = true;
$basePath = '../';
$currentDeputyTab = 'record_books';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Зачётная книжка</h1>
                <p class="text-muted">
                    <?= e($student['full_name']) ?>
                    <?php if ($group): ?>
                        · группа <?= e($group['number']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="panel__header-actions" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                <a href="record_book_pdf.php?id=<?= (int) $studentId ?>&group_id=<?= (int) $groupId ?>"
                   class="btn btn--primary" target="_blank" rel="noopener">Скачать PDF</a>
                <a href="<?= e($backUrl) ?>" class="btn btn--ghost">← К списку группы</a>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/deputy_nav.php'; ?>
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
                    $canEditCourseworks = true;
                    require __DIR__ . '/../includes/record_book_courseworks.php';
                    ?>
                <?php elseif ($isPractices): ?>
                    <?php
                    $canEditPractices = true;
                    require __DIR__ . '/../includes/record_book_practices.php';
                    ?>
                <?php elseif ($isGia): ?>
                    <?php
                    $canEditGia = true;
                    require __DIR__ . '/../includes/record_book_gia.php';
                    ?>
                <?php elseif ($selected === null): ?>
                    <div class="record-book-empty">
                        <h2>Пока нет оценок</h2>
                        <p class="text-muted">
                            Оценки появятся в зачётной книжке после архивации ведомостей за семестр.
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
