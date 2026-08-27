<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/journal.php';

require_teacher_panel();

$user = current_user();
$period = get_active_gradebook_period();
$year = $period['academic_year'];
$semester = $period['semester'];
$assignments = get_journal_assignments_for_user(null, $year, $semester);
$groups = get_journal_groups($year, $semester);

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
$tabParam = (string) ($_GET['tab'] ?? 'journal');
if ($tabParam === 'material') {
    $activeTab = 'material';
} elseif ($tabParam === 'ktp') {
    $activeTab = 'ktp';
} else {
    $activeTab = 'journal';
}
$error = null;

$selectedGroup = null;
foreach ($groups as $group) {
    if ((int) $group['group_id'] === $groupId) {
        $selectedGroup = $group;
        break;
    }
}

if ($groupId > 0 && $selectedGroup === null) {
    $error = 'Группа не найдена или недоступна.';
    $groupId = 0;
    $itemId = 0;
}

$assignment = null;
if ($selectedGroup !== null && $itemId > 0) {
    foreach ($selectedGroup['subjects'] as $row) {
        if ((int) $row['curriculum_item_id'] === $itemId) {
            $assignment = $row;
            break;
        }
    }

    if ($assignment === null) {
        $error = 'Предмет не найден или недоступен в текущем семестре.';
        $itemId = 0;
    }
}

if ($itemId > 0 && $groupId === 0 && $assignment === null) {
    foreach ($assignments as $row) {
        if ((int) $row['curriculum_item_id'] === $itemId) {
            $assignment = $row;
            $groupId = (int) $row['group_id'];
            foreach ($groups as $group) {
                if ((int) $group['group_id'] === $groupId) {
                    $selectedGroup = $group;
                    break;
                }
            }
            break;
        }
    }
}

$journalUrl = static function (array $params = []): string {
    $query = array_filter($params, static function ($value) {
        return $value !== null && $value !== '' && $value !== 0;
    });

    return 'journal.php' . ($query !== [] ? '?' . http_build_query($query) : '');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $assignment !== null) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_lesson') {
            $result = add_journal_lesson(
                $itemId,
                (string) ($_POST['lesson_date'] ?? ''),
                (int) ($_POST['ktp_topic_id'] ?? 0) ?: null,
                (string) ($_POST['grade_type'] ?? 'current')
            );
        } elseif ($action === 'update_lesson') {
            $result = update_journal_lesson(
                (int) ($_POST['lesson_id'] ?? 0),
                (string) ($_POST['lesson_date'] ?? ''),
                (int) ($_POST['ktp_topic_id'] ?? 0) ?: null,
                (string) ($_POST['grade_type'] ?? 'current')
            );
        } elseif ($action === 'delete_lesson') {
            $result = delete_journal_lesson((int) ($_POST['lesson_id'] ?? 0));
        } else {
            $result = ['success' => false, 'error' => 'Неизвестное действие.'];
        }

        if ($result['success']) {
            if ($action === 'add_lesson') {
                $successMessage = 'Дата добавлена в журнал.';
            } elseif ($action === 'update_lesson') {
                $successMessage = 'Столбец обновлён.';
            } elseif ($action === 'delete_lesson') {
                $successMessage = 'Столбец удалён.';
            } else {
                $successMessage = 'Изменения сохранены.';
            }
            flash_set('success', $successMessage);
            header('Location: ' . $journalUrl([
                'group_id' => $groupId,
                'item_id' => $itemId,
                'tab' => $activeTab,
            ]));
            exit;
        }

        $error = $result['error'];
    }
}

$students = $assignment ? get_students_by_group((int) $assignment['group_id']) : [];
$lessons = $assignment ? get_journal_lessons($itemId) : [];
$grades = $assignment ? get_journal_grades($itemId) : [];
$totals = $assignment ? build_journal_totals($students, $lessons, $grades) : [];
$ktpProgress = $assignment ? get_ktp_topics_with_progress($itemId) : [];
$defaultTopicId = $assignment ? get_next_ktp_topic_id($itemId, $lessons) : null;
$coveredSummary = $assignment ? build_covered_material_summary($lessons) : null;
$gradingConfig = get_grading_config();
$isBrs = $gradingConfig['system'] === 'brs';
$success = flash_get('success');

$pageTitle = 'Электронный журнал — Панель преподавателя';
$showHeader = true;
$basePath = '../';
$currentTeacherTab = 'journal';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель преподавателя</h1>
                <p class="text-muted">Электронный журнал по всем предметам учебного плана (в том числе при замещении)</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/teacher_nav.php'; ?>
    </section>

    <section class="panel">
        <?php if ($success): ?>
            <div class="alert alert--success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <p class="text-muted">
            Учебный год <?= e($year) ?> · <?= e(semester_label($semester)) ?>
        </p>

        <?php if ($groups === []): ?>
            <p class="text-muted">
                В системе пока нет групп.
                Добавьте их в админ-панели во вкладке «Информация».
            </p>
        <?php elseif ($assignment !== null): ?>
            <div class="journal-step">
                <div class="panel__header panel__header--compact">
                    <h2><?= e($assignment['subject_name']) ?></h2>
                    <div class="journal-back-actions">
                        <a
                            href="<?= e($journalUrl(['group_id' => $groupId])) ?>"
                            class="btn btn--ghost btn--sm"
                        >← К предметам</a>
                        <a href="<?= e($journalUrl()) ?>" class="btn btn--ghost btn--sm">← К группам</a>
                    </div>
                </div>

                <div class="journal-meta">
                    <div class="journal-meta__main">
                        <span class="journal-meta__group"><?= e($assignment['group_number']) ?></span>
                        <span class="journal-meta__subject"><?= e($assignment['subject_name']) ?></span>
                    </div>
                    <div class="journal-meta__side">
                        <span><?= e(semester_label($assignment['semester'])) ?></span>
                        <span><?= count($students) ?> студ.</span>
                        <span><?= count($lessons) ?> зан.</span>
                    </div>
                </div>

                <nav class="journal-subtabs">
                    <a
                        href="<?= e($journalUrl(['group_id' => $groupId, 'item_id' => $itemId, 'tab' => 'journal'])) ?>"
                        class="journal-subtabs__item<?= $activeTab === 'journal' ? ' journal-subtabs__item--active' : '' ?>"
                    >Журнал</a>
                    <a
                        href="<?= e($journalUrl(['group_id' => $groupId, 'item_id' => $itemId, 'tab' => 'material'])) ?>"
                        class="journal-subtabs__item<?= $activeTab === 'material' ? ' journal-subtabs__item--active' : '' ?>"
                    >Пройденный материал</a>
                    <a
                        href="<?= e($journalUrl(['group_id' => $groupId, 'item_id' => $itemId, 'tab' => 'ktp'])) ?>"
                        class="journal-subtabs__item<?= $activeTab === 'ktp' ? ' journal-subtabs__item--active' : '' ?>"
                    >КТП</a>
                </nav>

                <?php if ($activeTab === 'ktp'): ?>
                    <?php if ($ktpProgress === []): ?>
                        <p class="text-muted">
                            КТП по этому предмету пока не заполнен.
                            Его составляет преподаватель, закреплённый в учебном плане, в личном кабинете.
                        </p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table ktp-view-table">
                                <thead>
                                    <tr>
                                        <th>№</th>
                                        <th>Тема</th>
                                        <th>Тип урока</th>
                                        <th>Часы</th>
                                        <th>Статус</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ktpProgress as $index => $topic): ?>
                                    <?php $isIndependent = !ktp_is_journal_selectable_type((string) ($topic['lesson_type'] ?? 'lecture')); ?>
                                    <tr class="<?= !empty($topic['completed']) ? 'ktp-row--done' : '' ?><?= $isIndependent ? ' ktp-row--independent' : '' ?>">
                                        <td><?= $index + 1 ?></td>
                                        <td><?= e($topic['title']) ?></td>
                                        <td><?= e(ktp_lesson_type_label((string) $topic['lesson_type'])) ?></td>
                                        <td><?= e(rtrim(rtrim(number_format((float) $topic['hours'], 1, '.', ''), '0'), '.')) ?></td>
                                        <td>
                                            <?php if ($isIndependent): ?>
                                                <span class="badge badge--inactive">Не для журнала</span>
                                            <?php elseif (!empty($topic['completed'])): ?>
                                                <span class="badge badge--success">Пройдена</span>
                                                <?php if (!empty($topic['first_lesson_date'])): ?>
                                                    <span class="text-muted">
                                                        <?= e(date('d.m.Y', (int) strtotime($topic['first_lesson_date']))) ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Не пройдена</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php elseif ($activeTab === 'material'): ?>
                    <?php if ($lessons === []): ?>
                        <p class="text-muted">Пройденных занятий пока нет. Добавьте даты во вкладке «Журнал».</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="table covered-table">
                                <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Тема</th>
                                        <th>Тип оценки</th>
                                        <th>Тип урока</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lessons as $lesson): ?>
                                    <tr>
                                        <td><?= e(date('d.m.Y', (int) strtotime($lesson['lesson_date']))) ?></td>
                                        <td><?= e($lesson['topic_title'] ?: '—') ?></td>
                                        <td>
                                            <span class="journal-lesson-type journal-lesson-type--<?= e($lesson['grade_type'] ?? 'current') ?>">
                                                <?= e(journal_grade_type_label((string) ($lesson['grade_type'] ?? 'current'))) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($lesson['topic_lesson_type'])): ?>
                                                <?= e(ktp_lesson_type_label((string) $lesson['topic_lesson_type'])) ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($coveredSummary !== null): ?>
                        <div class="covered-summary">
                            <h3>Сводка</h3>
                            <dl class="profile-list">
                                <dt>Уроков всего</dt>
                                <dd><?= (int) $coveredSummary['total_lessons'] ?></dd>
                                <dt>Часов лекций</dt>
                                <dd><?= e((string) $coveredSummary['lecture_hours']) ?></dd>
                                <dt>Часов практических</dt>
                                <dd><?= e((string) $coveredSummary['practice_hours']) ?></dd>
                                <dt>Часов всего</dt>
                                <dd><?= e((string) $coveredSummary['total_hours']) ?></dd>
                            </dl>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php elseif (empty($students)): ?>
                    <p class="text-muted">В группе пока нет студентов.</p>
                <?php else: ?>
                    <div class="journal-toolbar">
                        <button
                            type="button"
                            class="btn btn--primary"
                            data-lesson-modal-open
                            data-mode="add"
                            data-date="<?= e(date('Y-m-d')) ?>"
                            data-topic-id="<?= (int) ($defaultTopicId ?? 0) ?>"
                            data-grade-type="current"
                        >Добавить дату</button>
                    </div>

                    <div
                        class="journal-form"
                        data-journal
                        data-grading-system="<?= e($gradingConfig['system']) ?>"
                        data-save-url="grade_save.php"
                        data-csrf="<?= e(csrf_token()) ?>"
                    >
                        <div class="table-wrap journal-table-wrap">
                            <table class="table journal-table">
                                <thead>
                                    <tr>
                                        <th class="journal-table__student-col">Студенты</th>
                                        <?php foreach ($lessons as $index => $lesson): ?>
                                        <?php $gradeType = (string) ($lesson['grade_type'] ?? 'current'); ?>
                                        <th class="journal-table__lesson-col journal-table__lesson-col--<?= e($gradeType) ?><?= $index % 2 === 1 ? ' journal-table__lesson-col--alt' : '' ?>">
                                            <div class="journal-lesson-head">
                                                <div class="journal-lesson-meta">
                                                    <span class="journal-lesson-date"><?= e(format_journal_date($lesson['lesson_date'])) ?></span>
                                                    <span
                                                        class="journal-lesson-type journal-lesson-type--<?= e($gradeType) ?>"
                                                        title="<?= e(journal_grade_type_label($gradeType)) ?>"
                                                    ><?= e(journal_grade_type_short($gradeType)) ?></span>
                                                </div>
                                                <?php if (!empty($lesson['topic_title'])): ?>
                                                <span class="journal-lesson-topic" title="<?= e($lesson['topic_title']) ?>">
                                                    <?= e($lesson['topic_title']) ?>
                                                </span>
                                                <?php endif; ?>
                                                <div class="journal-lesson-actions">
                                                    <button
                                                        type="button"
                                                        class="journal-icon-btn"
                                                        title="Редактировать"
                                                        data-lesson-modal-open
                                                        data-mode="edit"
                                                        data-lesson-id="<?= (int) $lesson['id'] ?>"
                                                        data-date="<?= e($lesson['lesson_date']) ?>"
                                                        data-topic-id="<?= (int) ($lesson['ktp_topic_id'] ?? 0) ?>"
                                                        data-grade-type="<?= e($gradeType) ?>"
                                                    >✎</button>
                                                    <form method="post" class="form-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="delete_lesson">
                                                        <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id'] ?>">
                                                        <button
                                                            type="submit"
                                                            class="journal-icon-btn journal-icon-btn--danger"
                                                            title="Удалить"
                                                            onclick="return confirm('Удалить столбец и все оценки за эту дату?')"
                                                        >×</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </th>
                                        <?php endforeach; ?>
                                        <th class="journal-table__total-col"><?= $isBrs ? 'Баллы' : 'Итого' ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $rowIndex => $student): ?>
                                    <?php $studentId = (int) $student['id']; ?>
                                    <tr class="<?= $rowIndex % 2 === 1 ? 'journal-row--alt' : '' ?>">
                                        <td class="journal-table__student-col">
                                            <span class="journal-student-name"><?= e($student['full_name']) ?></span>
                                        </td>
                                        <?php foreach ($lessons as $index => $lesson): ?>
                                        <?php
                                        $lessonId = (int) $lesson['id'];
                                        $gradeType = (string) ($lesson['grade_type'] ?? 'current');
                                        $entry = $grades[$studentId][$lessonId] ?? empty_journal_entry();
                                        $mark = (string) $entry['mark'];
                                        $present = $mark !== 'Н';
                                        $hasGrade = in_array($mark, ['2', '3', '4', '5'], true);
                                        $canActivity = $present && $hasGrade;
                                        $canLate = $present;
                                        $hasActivity = $canActivity && !empty($entry['activity']);
                                        $hasLate = $canLate && !empty($entry['late']);
                                        ?>
                                        <td
                                            class="journal-table__cell<?= $index % 2 === 1 ? ' journal-table__cell--alt' : '' ?><?= $gradeType === 'control' ? ' journal-table__cell--control' : '' ?>"
                                            data-journal-cell
                                            data-student-id="<?= $studentId ?>"
                                            data-lesson-id="<?= $lessonId ?>"
                                            data-mark="<?= e($mark) ?>"
                                            data-activity="<?= $hasActivity ? '1' : '0' ?>"
                                            data-late="<?= $hasLate ? '1' : '0' ?>"
                                        >
                                            <div class="journal-mark">
                                                <button
                                                    type="button"
                                                    class="journal-mark__trigger<?= $mark !== '' ? ' journal-mark__trigger--set' : '' ?><?= $mark === 'Н' ? ' journal-mark__trigger--absent' : '' ?>"
                                                    data-mark-trigger
                                                    aria-haspopup="listbox"
                                                    aria-expanded="false"
                                                ><?= e(render_journal_mark_label($mark)) ?></button>
                                                <div class="journal-mark__menu" data-mark-menu hidden>
                                                    <?php foreach (journal_mark_options() as $option): ?>
                                                    <button
                                                        type="button"
                                                        class="journal-mark__option<?= $option === $mark ? ' journal-mark__option--active' : '' ?>"
                                                        data-mark-option="<?= e($option) ?>"
                                                    ><?= e(render_journal_mark_label($option)) ?></button>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php if ($isBrs): ?>
                                            <div class="journal-flags">
                                                <button
                                                    type="button"
                                                    class="journal-flag journal-flag--activity<?= $hasActivity ? ' journal-flag--on' : '' ?><?= $canActivity ? '' : ' journal-flag--disabled' ?>"
                                                    data-flag="activity"
                                                    title="<?= $canActivity ? 'Активность' : 'Активность доступна при оценке 2–5' ?>"
                                                    aria-pressed="<?= $hasActivity ? 'true' : 'false' ?>"
                                                    <?= $canActivity ? '' : 'disabled' ?>
                                                >А</button>
                                                <button
                                                    type="button"
                                                    class="journal-flag journal-flag--late<?= $hasLate ? ' journal-flag--on' : '' ?><?= $canLate ? '' : ' journal-flag--disabled' ?>"
                                                    data-flag="late"
                                                    title="<?= $canLate ? 'Опоздание' : 'Опоздание недоступно при пропуске (Н)' ?>"
                                                    aria-pressed="<?= $hasLate ? 'true' : 'false' ?>"
                                                    <?= $canLate ? '' : 'disabled' ?>
                                                >О</button>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <?php endforeach; ?>
                                        <td
                                            class="journal-table__total-col"
                                            data-journal-total="<?= $studentId ?>"
                                        ><?php
                                            $total = $totals[$studentId] ?? null;
                                            echo is_array($total) ? ($total['html'] ?? '') : e((string) $total);
                                        ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($lessons === []): ?>
                        <p class="text-muted">Добавьте дату занятия, чтобы появились столбцы для отметок.</p>
                        <?php endif; ?>

                        <p class="journal-status text-muted" data-journal-status>
                            <?php if ($isBrs): ?>
                                БРС: оценки 2–5 или Н. Итого — сумма баллов (макс. 100) и оценка по шкале.
                                О — при присутствии; А — при оценке 2–5. Пересчёт сразу после изменения.
                            <?php else: ?>
                                Традиционная система: оценки 2–5, итог — средний балл.
                                Пропуски (Н) в среднем не учитываются.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($selectedGroup !== null): ?>
            <div class="journal-step">
                <div class="panel__header panel__header--compact">
                    <h2>Предметы группы <?= e($selectedGroup['group_number']) ?></h2>
                    <a href="<?= e($journalUrl()) ?>" class="btn btn--ghost btn--sm">← К группам</a>
                </div>
                <?php if ($selectedGroup['subjects'] === []): ?>
                    <p class="text-muted">
                        В текущем семестре для этой группы нет предметов.
                        Администратор или завуч должен заполнить учебный план.
                    </p>
                <?php else: ?>
                    <div class="journal-choice-grid">
                        <?php foreach ($selectedGroup['subjects'] as $subject): ?>
                        <a
                            href="<?= e($journalUrl([
                                'group_id' => $groupId,
                                'item_id' => (int) $subject['curriculum_item_id'],
                            ])) ?>"
                            class="journal-choice"
                        >
                            <?= render_journal_choice_icon('journal') ?>
                            <strong><?= e($subject['subject_name']) ?></strong>
                            <span class="text-muted"><?= e(semester_label($subject['semester'])) ?></span>
                            <?php if (!empty($subject['teacher_name'])): ?>
                            <span class="journal-choice__meta">Преп.: <?= e($subject['teacher_name']) ?></span>
                            <?php else: ?>
                            <span class="journal-choice__meta">Преподаватель не назначен</span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="journal-step">
                <h2>Выберите группу</h2>
                <div class="journal-choice-grid">
                    <?php foreach ($groups as $group): ?>
                    <a
                        href="<?= e($journalUrl(['group_id' => (int) $group['group_id']])) ?>"
                        class="journal-choice"
                    >
                        <?= render_journal_choice_icon('group') ?>
                        <strong><?= e($group['group_number']) ?></strong>
                        <span class="text-muted"><?= e($group['specialty_name']) ?></span>
                        <span class="journal-choice__meta"><?= count($group['subjects']) ?> предм.</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php if ($assignment !== null && !empty($students)): ?>
<div class="modal" data-lesson-modal hidden>
    <div class="modal__backdrop" data-lesson-modal-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="lesson-modal-title">
        <div class="modal__header">
            <h2 id="lesson-modal-title" data-lesson-modal-title>Добавить дату</h2>
            <button type="button" class="modal__close" data-lesson-modal-close aria-label="Закрыть">&times;</button>
        </div>
        <form method="post" class="form" data-lesson-modal-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_lesson" data-lesson-action>
            <input type="hidden" name="lesson_id" value="" data-lesson-id>

            <div class="form__group">
                <label for="modal_lesson_date">Дата</label>
                <input
                    type="date"
                    id="modal_lesson_date"
                    name="lesson_date"
                    required
                    value="<?= e(date('Y-m-d')) ?>"
                    data-lesson-date
                >
            </div>

            <div class="form__group">
                <label for="modal_ktp_topic">Тема урока (КТП)</label>
                <select id="modal_ktp_topic" name="ktp_topic_id" data-lesson-topic>
                    <option value="">— Не выбрана —</option>
                    <?php foreach ($ktpProgress as $index => $topic): ?>
                    <?php
                    $isCompleted = !empty($topic['completed']);
                    $isIndependent = !ktp_is_journal_selectable_type((string) ($topic['lesson_type'] ?? 'lecture'));
                    $isDisabled = $isCompleted || $isIndependent;
                    ?>
                    <option
                        value="<?= (int) $topic['id'] ?>"
                        data-completed="<?= $isCompleted ? '1' : '0' ?>"
                        data-independent="<?= $isIndependent ? '1' : '0' ?>"
                        <?= $isDisabled ? 'disabled' : '' ?>
                    >
                        <?= ($index + 1) ?>. <?= e($topic['title']) ?>
                        · <?= e(ktp_lesson_type_label((string) ($topic['lesson_type'] ?? 'lecture'))) ?>
                        · <?= e(rtrim(rtrim(number_format((float) ($topic['hours'] ?? 2), 1, '.', ''), '0'), '.')) ?> ч.<?php
                        if ($isIndependent): ?> · не для журнала<?php
                        elseif ($isCompleted): ?> · пройдена<?php
                        endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($ktpProgress === []): ?>
                <p class="text-muted form-hint">
                    Темы КТП пока не заполнены. Их добавляет преподаватель в личном кабинете.
                </p>
                <?php else: ?>
                <p class="text-muted form-hint">
                    Пройденные темы и самостоятельная работа недоступны для выбора.
                    В журнал вносятся только занятия, которые отрабатывает преподаватель.
                </p>
                <?php endif; ?>
            </div>

            <div class="form__group">
                <label for="modal_grade_type">Тип оценки</label>
                <select id="modal_grade_type" name="grade_type" data-lesson-grade-type>
                    <option value="current">Текущая</option>
                    <option value="control">Контрольная</option>
                </select>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary" data-lesson-submit>Добавить</button>
                <button type="button" class="btn btn--ghost" data-lesson-modal-close>Отмена</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
