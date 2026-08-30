<?php

declare(strict_types=1);

$curriculumPanel = $curriculumPanel ?? 'admin';

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../curriculum.php';
require_once __DIR__ . '/../ktp.php';

require_curriculum_manager();

$itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$academicYear = normalize_academic_year($_GET['year'] ?? '') ?? get_default_academic_year();

$item = $itemId > 0 ? get_curriculum_item_by_id($itemId) : null;
if ($item === null) {
    flash_set('error', 'Предмет не найден.');
    header('Location: curriculum.php?year=' . urlencode($academicYear));
    exit;
}

if ($groupId <= 0) {
    $groupId = (int) $item['group_id'];
}
if ((string) ($item['academic_year'] ?? '') !== '') {
    $academicYear = (string) $item['academic_year'];
}

$topics = get_ktp_topics($itemId);
$ktpSummary = build_ktp_plan_summary($topics);
$isProfessionality = curriculum_item_is_professionality($item);
$backUrl = 'curriculum_edit.php?group_id=' . $groupId . '&year=' . urlencode($academicYear);

$pageTitle = 'КТП — ' . ($item['subject_name'] ?? '');
$showHeader = true;
$basePath = '../';

if ($curriculumPanel === 'admin') {
    $currentAdminTab = 'curriculum';
} else {
    $currentDeputyTab = 'curriculum';
}

require __DIR__ . '/../header.php';

$displayOrDash = static function (?string $value): string {
    $value = trim((string) $value);

    return $value !== '' ? $value : '—';
};
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>КТП: <?= e($item['subject_name']) ?></h1>
                <p class="text-muted">
                    Группа <?= e((string) ($item['group_number'] ?? '')) ?> ·
                    учебный год <?= e($academicYear) ?> ·
                    <?= e(semester_label((string) ($item['semester'] ?? '1'))) ?>
                </p>
            </div>
            <a href="<?= e($backUrl) ?>" class="btn btn--ghost">← Назад</a>
        </div>

        <?php
        if ($curriculumPanel === 'admin') {
            require __DIR__ . '/../admin_nav.php';
        } else {
            require __DIR__ . '/../deputy_nav.php';
        }
        ?>
    </section>

    <section class="panel">
        <h2>Разработчик КТП</h2>
        <?php if (empty($item['teacher_id'])): ?>
            <p class="text-muted">Преподаватель для предмета не назначен.</p>
        <?php else: ?>
            <dl class="profile-list">
                <dt>ФИО</dt>
                <dd><?= e($displayOrDash($item['teacher_name'] ?? '')) ?></dd>
                <dt>Должность</dt>
                <dd><?= e($displayOrDash($item['teacher_position'] ?? '')) ?></dd>
                <dt>Email</dt>
                <dd><?= e($displayOrDash($item['teacher_email'] ?? '')) ?></dd>
                <dt>Телефон</dt>
                <dd><?= e($displayOrDash($item['teacher_phone'] ?? '')) ?></dd>
            </dl>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Темы КТП</h2>
        <?php if ($topics === []): ?>
            <p class="text-muted">КТП по этому предмету пока не заполнен.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Тема</th>
                            <th>Тип урока</th>
                            <th>Часы</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topics as $index => $topic): ?>
                        <?php $isSemesterMarker = ktp_is_semester_marker_type((string) ($topic['lesson_type'] ?? 'lecture')); ?>
                        <tr class="<?= $isSemesterMarker ? 'ktp-row--semester-marker' : '' ?>">
                            <td><?php
                                $topicNum = ktp_topic_display_number($topics, $index);
                                echo $topicNum !== null ? (int) $topicNum : '';
                            ?></td>
                            <td<?= $isSemesterMarker ? ' colspan="3"' : '' ?>>
                                <?php if ($isSemesterMarker): ?>
                                    <strong><?= e(ktp_semester_marker_title((string) ($topic['lesson_type'] ?? 'semester_2'))) ?></strong>
                                <?php else: ?>
                                    <?= e($topic['title']) ?>
                                <?php endif; ?>
                            </td>
                            <?php if (!$isSemesterMarker): ?>
                            <td><?= e(ktp_lesson_type_label((string) ($topic['lesson_type'] ?? 'lecture'))) ?></td>
                            <td><?= e(format_ktp_topic_hours($topic, $isProfessionality)) ?></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="covered-summary ktp-plan-summary">
                <h3>Сводка КТП</h3>
                <dl class="profile-list">
                    <dt>Уроков (лекция/практика)</dt>
                    <dd><?= (int) $ktpSummary['lessons'] ?></dd>
                    <dt>Часов лекций</dt>
                    <dd><?= e((string) $ktpSummary['lecture_hours']) ?></dd>
                    <dt>Часов практических</dt>
                    <dd><?= e((string) $ktpSummary['practice_hours']) ?></dd>
                    <?php if ($isProfessionality): ?>
                    <dt>Часов профориентированных</dt>
                    <dd><?= e((string) $ktpSummary['orientation_hours']) ?></dd>
                    <?php endif; ?>
                    <dt>Часов промежуточной аттестации</dt>
                    <dd><?= e((string) $ktpSummary['attestation_hours']) ?></dd>
                    <dt>Часов самостоятельных работ</dt>
                    <dd><?= e((string) $ktpSummary['independent_hours']) ?></dd>
                    <dt>Часов всего</dt>
                    <dd><?= e((string) $ktpSummary['total_hours']) ?></dd>
                </dl>
            </div>
        <?php endif; ?>

        <div class="form__actions">
            <a href="<?= e($backUrl) ?>" class="btn btn--ghost">← Назад к учебному плану</a>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
