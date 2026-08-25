<?php

declare(strict_types=1);

$archivePanel = $archivePanel ?? 'admin';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../archive.php';
require_once __DIR__ . '/../teachers.php';

require_archive_manager();

$archiveId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
$archive = $archiveId > 0 ? get_archive_period_by_id($archiveId) : null;

if ($archive === null || $archive['archive_type'] !== 'journal') {
    flash_set('error', 'Архив журналов не найден.');
    header('Location: archive.php');
    exit;
}

$items = get_archive_journal_items($archiveId, $groupId);
$selected = null;
foreach ($items as $row) {
    if ($itemId > 0 && (int) $row['id'] === $itemId) {
        $selected = $row;
        break;
    }
}
if ($selected === null && $items !== [] && $itemId === 0) {
    $selected = null;
}

$sheet = $selected ? get_archive_journal_sheet((int) $selected['id']) : null;
$tabParam = (string) ($_GET['tab'] ?? 'journal');
if ($tabParam === 'material') {
    $activeTab = 'material';
} elseif ($tabParam === 'ktp') {
    $activeTab = 'ktp';
} else {
    $activeTab = 'journal';
}
$archiveJournalUrl = static function (array $params) use ($archiveId, $groupId): string {
    $query = array_merge(
        ['id' => $archiveId, 'group_id' => $groupId],
        $params
    );

    return 'archive_journal.php?' . http_build_query($query);
};
$backUrl = 'archive.php?year=' . urlencode((string) $archive['academic_year'])
    . '&semester=' . urlencode((string) $archive['semester']);

$pageTitle = 'Архив журнала';
$showHeader = true;
$basePath = '../';
if ($archivePanel === 'admin') {
    $currentAdminTab = 'archive';
} else {
    $currentDeputyTab = 'archive';
}
require __DIR__ . '/../header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Архив журналов</h1>
                <p class="text-muted">
                    <?= e($archive['academic_year']) ?> · <?= e(semester_label((string) $archive['semester'])) ?>
                    <?php if ($items !== []): ?>
                        · группа <?= e((string) $items[0]['group_number']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <a href="<?= e($backUrl) ?>" class="btn btn--ghost">← Назад</a>
        </div>
        <?php
        if ($archivePanel === 'admin') {
            require __DIR__ . '/../admin_nav.php';
        } else {
            require __DIR__ . '/../deputy_nav.php';
        }
        ?>
    </section>

    <section class="panel">
        <?php if ($items === []): ?>
            <p class="text-muted">В архиве нет журналов этой группы.</p>
        <?php elseif ($selected === null): ?>
            <div class="panel__header panel__header--compact">
                <h2>Предметы</h2>
                <a
                    href="archive_journal_pdf.php?id=<?= $archiveId ?>&group_id=<?= $groupId ?>"
                    class="btn btn--secondary btn--sm"
                >Скачать PDF</a>
            </div>
            <div class="journal-choice-grid">
                <?php foreach ($items as $item): ?>
                <a
                    href="archive_journal.php?id=<?= $archiveId ?>&group_id=<?= $groupId ?>&item_id=<?= (int) $item['id'] ?>"
                    class="journal-choice"
                >
                    <strong><?= e($item['subject_name']) ?></strong>
                    <?php if (!empty($item['teacher_name'])): ?>
                    <span class="journal-choice__meta">Преп.: <?= e($item['teacher_name']) ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="panel__header panel__header--compact">
                <h2><?= e($selected['subject_name']) ?></h2>
                <a href="archive_journal.php?id=<?= $archiveId ?>&group_id=<?= $groupId ?>" class="btn btn--ghost btn--sm">← К предметам</a>
            </div>
            <?php
            $students = $sheet['students'];
            $lessons = $sheet['lessons'];
            $grades = $sheet['grades'];
            $totals = $sheet['totals'];
            $topics = $sheet['topics'] ?? [];
            $coveredSummary = $lessons !== [] ? build_covered_material_summary($lessons) : null;
            $ktpSummary = $topics !== [] ? build_ktp_plan_summary($topics) : null;
            $formatHours = static function ($hours): string {
                return rtrim(rtrim(number_format((float) $hours, 1, '.', ''), '0'), '.');
            };
            ?>
            <nav class="journal-subtabs">
                <a
                    href="<?= e($archiveJournalUrl(['item_id' => (int) $selected['id'], 'tab' => 'journal'])) ?>"
                    class="journal-subtabs__item<?= $activeTab === 'journal' ? ' journal-subtabs__item--active' : '' ?>"
                >Журнал</a>
                <a
                    href="<?= e($archiveJournalUrl(['item_id' => (int) $selected['id'], 'tab' => 'material'])) ?>"
                    class="journal-subtabs__item<?= $activeTab === 'material' ? ' journal-subtabs__item--active' : '' ?>"
                >Пройденный материал</a>
                <a
                    href="<?= e($archiveJournalUrl(['item_id' => (int) $selected['id'], 'tab' => 'ktp'])) ?>"
                    class="journal-subtabs__item<?= $activeTab === 'ktp' ? ' journal-subtabs__item--active' : '' ?>"
                >КТП</a>
            </nav>

            <?php if ($activeTab === 'ktp'): ?>
                <?php if ($topics === []): ?>
                    <p class="text-muted">В архиве нет КТП по этому предмету.</p>
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
                                <?php foreach ($topics as $index => $topic): ?>
                                <tr class="<?= !empty($topic['completed']) ? 'ktp-row--done' : '' ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td><?= e($topic['title']) ?></td>
                                    <td><?= e(ktp_lesson_type_label((string) ($topic['lesson_type'] ?? 'lecture'))) ?></td>
                                    <td><?= e($formatHours($topic['hours'] ?? 0)) ?></td>
                                    <td>
                                        <?php if (!empty($topic['completed'])): ?>
                                            <span class="badge badge--success">Пройдена</span>
                                            <?php if (!empty($topic['first_lesson_date'])): ?>
                                                <span class="text-muted">
                                                    <?= e(date('d.m.Y', (int) strtotime((string) $topic['first_lesson_date']))) ?>
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
                    <?php if ($ktpSummary !== null): ?>
                    <div class="covered-summary ktp-plan-summary">
                        <h3>Сводка КТП</h3>
                        <dl class="profile-list">
                            <dt>Уроков (лекция/практика)</dt>
                            <dd><?= (int) $ktpSummary['lessons'] ?></dd>
                            <dt>Часов лекций</dt>
                            <dd><?= e((string) $ktpSummary['lecture_hours']) ?></dd>
                            <dt>Часов практических</dt>
                            <dd><?= e((string) $ktpSummary['practice_hours']) ?></dd>
                            <dt>Часов промежуточной аттестации</dt>
                            <dd><?= e((string) $ktpSummary['attestation_hours']) ?></dd>
                            <dt>Часов самостоятельных работ</dt>
                            <dd><?= e((string) $ktpSummary['independent_hours']) ?></dd>
                            <dt>Часов всего</dt>
                            <dd><?= e((string) $ktpSummary['total_hours']) ?></dd>
                        </dl>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php elseif ($activeTab === 'material'): ?>
                <?php if ($lessons === []): ?>
                    <p class="text-muted">В архиве нет пройденных занятий.</p>
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
                                    <td><?= e(date('d.m.Y', (int) strtotime((string) $lesson['lesson_date']))) ?></td>
                                    <td><?= e(($lesson['topic_title'] ?? '') !== '' ? $lesson['topic_title'] : '—') ?></td>
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
            <?php elseif ($students === []): ?>
                <p class="text-muted">В архиве нет студентов.</p>
            <?php else: ?>
                <div class="table-wrap journal-table-wrap">
                    <table class="table journal-table">
                        <thead>
                            <tr>
                                <th class="journal-table__student-col">Студенты</th>
                                <?php foreach ($lessons as $lesson): ?>
                                <?php $gradeType = (string) ($lesson['grade_type'] ?? 'current'); ?>
                                <th class="journal-table__lesson-col journal-table__lesson-col--<?= e($gradeType) ?>">
                                    <div class="journal-lesson-head">
                                        <span class="journal-lesson-date"><?= e(format_journal_date((string) $lesson['lesson_date'])) ?></span>
                                        <span class="journal-lesson-type journal-lesson-type--<?= e($gradeType) ?>">
                                            <?= e(journal_grade_type_short($gradeType)) ?>
                                        </span>
                                        <?php if (!empty($lesson['topic_title'])): ?>
                                        <span class="journal-lesson-topic" title="<?= e($lesson['topic_title']) ?>">
                                            <?= e($lesson['topic_title']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </th>
                                <?php endforeach; ?>
                                <th class="journal-table__total-col">Итого</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <?php $studentId = (int) $student['student_id']; ?>
                            <tr>
                                <td class="journal-table__student-col">
                                    <?= e(person_last_first_name((string) $student['full_name'])) ?>
                                </td>
                                <?php foreach ($lessons as $lesson): ?>
                                <?php
                                $lessonId = (int) $lesson['id'];
                                $entry = $grades[$studentId][$lessonId] ?? empty_journal_entry();
                                $mark = (string) ($entry['mark'] ?? '');
                                ?>
                                <td class="journal-table__cell">
                                    <?= e(render_journal_mark_label($mark)) ?>
                                    <?php if (!empty($entry['activity'])): ?><span class="text-muted">акт</span><?php endif; ?>
                                    <?php if (!empty($entry['late'])): ?><span class="text-muted">оп</span><?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                                <td class="journal-table__total-col">
                                    <?php
                                    $total = $totals[$studentId] ?? [];
                                    $final = $total['final_grade'] ?? null;
                                    echo $final !== null && $final !== '' ? e((string) $final) : '—';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted table-hint">Текущие оценки сохранены как на момент архивации. Итог обновляется при правке архивной ведомости.</p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
