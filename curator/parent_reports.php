<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';
require_once __DIR__ . '/../includes/parent_report.php';

require_curator_panel();

$ctx = resolve_curator_group_context(isset($_GET['group_id']) ? (int) $_GET['group_id'] : null);
$groups = $ctx['groups'];
$groupId = $ctx['group_id'];
$group = $ctx['group'];
$students = $group ? get_students_by_group($groupId) : [];
$error = $ctx['error'];

$period = get_active_gradebook_period();
$year = $period['academic_year'];
$semester = $period['semester'];

$defaultSource = is_manual_brs_gradebook_active() ? 'manual_brs' : 'journal';
$source = parent_report_grade_source($_GET['source'] ?? $defaultSource);

$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
$selectedStudent = null;
$report = null;
$reportContext = null;
$publicUrl = '';
$linkShareText = '';

if ($group !== null) {
    $reportContext = parent_report_group_context($groupId, $year, $semester, $source);
}

if ($group !== null && $studentId > 0) {
    foreach ($students as $row) {
        if ((int) $row['id'] === $studentId) {
            $selectedStudent = $row;
            break;
        }
    }
    if ($selectedStudent === null) {
        $error = $error ?: 'Студент не найден в этой группе.';
        $studentId = 0;
    } else {
        $report = build_parent_student_report(
            $selectedStudent,
            $group,
            $year,
            $semester,
            $source,
            $reportContext
        );
        $token = parent_report_create_token(
            (int) $selectedStudent['id'],
            $year,
            $semester,
            $source
        );
        $publicUrl = parent_report_public_url($token);
        $linkShareText = format_parent_student_report_link_text($report, $publicUrl);
    }
}

$pageTitle = 'Отчёты родителям — Панель куратора';
$showHeader = true;
$basePath = '../';
$currentCuratorTab = 'parent_reports';
$curatorGroupId = $groupId;
$curatorGroups = $groups;
require __DIR__ . '/../includes/header.php';

$listQuery = static function (array $extra = []) use ($groupId, $source): string {
    $params = array_merge(
        [
            'group_id' => $groupId > 0 ? $groupId : null,
            'source' => $source,
        ],
        $extra
    );
    $params = array_filter(
        $params,
        static fn ($v): bool => $v !== null && $v !== '' && $v !== 0
    );

    return 'parent_reports.php?' . http_build_query($params);
};
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель куратора</h1>
                <p class="text-muted">Отчёты по успеваемости и посещаемости для родителей</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/curator_nav.php'; ?>
    </section>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
        <section class="panel">
            <p class="text-muted">Вам ещё не назначена группа. Обратитесь к администратору.</p>
        </section>
    <?php elseif ($group === null): ?>
        <section class="panel">
            <p class="text-muted">Выберите группу, чтобы открыть отчёты.</p>
        </section>
    <?php else: ?>
        <section class="panel">
            <div class="panel__header panel__header--compact">
                <p class="text-muted" style="margin:0;">
                    Группа <strong><?= e((string) $group['number']) ?></strong>
                    · <?= e(semester_label($semester)) ?> <?= e($year) ?>
                    · источник оценок: <?= e(parent_report_source_label($source)) ?>
                </p>
                <?php if (is_manual_brs_gradebook_active()): ?>
                <div class="panel__header-actions">
                    <a
                        href="<?= e($listQuery(['source' => 'journal', 'student_id' => $studentId ?: null])) ?>"
                        class="btn btn--sm <?= $source === 'journal' ? 'btn--primary' : 'btn--ghost' ?>"
                    >Журнал</a>
                    <a
                        href="<?= e($listQuery(['source' => 'manual_brs', 'student_id' => $studentId ?: null])) ?>"
                        class="btn btn--sm <?= $source === 'manual_brs' ? 'btn--primary' : 'btn--ghost' ?>"
                    >Ручное БРС</a>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($report !== null): ?>
                <div class="panel__header panel__header--compact parent-report-actions">
                    <a href="<?= e($listQuery()) ?>" class="btn btn--ghost btn--sm">← К списку студентов</a>
                    <div class="panel__header-actions">
                        <button
                            type="button"
                            class="btn btn--primary btn--sm"
                            data-share-max
                            data-share-text="<?= e(rawurlencode($report['text'])) ?>"
                        >MAX: текст</button>
                        <button
                            type="button"
                            class="btn btn--primary btn--sm"
                            data-share-max
                            data-share-text="<?= e(rawurlencode($linkShareText)) ?>"
                        >MAX: ссылка</button>
                        <button type="button" class="btn btn--ghost btn--sm" data-parent-report-print>Печать</button>
                        <button
                            type="button"
                            class="btn btn--ghost btn--sm"
                            data-copy-text="<?= e($report['text']) ?>"
                        >Копировать текст</button>
                        <button
                            type="button"
                            class="btn btn--ghost btn--sm"
                            data-copy-text="<?= e($publicUrl) ?>"
                        >Копировать ссылку</button>
                    </div>
                </div>

                <div class="parent-report-link-box parent-report-actions">
                    <label for="parent_report_public_url">Ссылка для родителей (без авторизации)</label>
                    <div class="parent-report-link-box__row">
                        <input
                            type="text"
                            id="parent_report_public_url"
                            class="parent-report-link-box__input"
                            value="<?= e($publicUrl) ?>"
                            readonly
                        >
                        <a href="<?= e($publicUrl) ?>" class="btn btn--ghost btn--sm" target="_blank" rel="noopener">Открыть</a>
                    </div>
                    <p class="text-muted table-hint">Ссылка действует 180 дней. В MAX можно отправить полный текст или короткое сообщение со ссылкой.</p>
                </div>

                <?php require __DIR__ . '/../includes/parent_report_view.php'; ?>
            <?php elseif ($students === []): ?>
                <p class="text-muted">В группе пока нет студентов.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="table__num-col">№</th>
                                <th>ФИО</th>
                                <th class="table__actions-col">Отчёт</th>
                                <th class="table__share-col">MAX</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                            <?php
                            $rowReport = build_parent_student_report(
                                $student,
                                $group,
                                $year,
                                $semester,
                                $source,
                                $reportContext
                            );
                            $rowToken = parent_report_create_token(
                                (int) $student['id'],
                                $year,
                                $semester,
                                $source
                            );
                            $rowUrl = parent_report_public_url($rowToken);
                            $rowLinkText = format_parent_student_report_link_text($rowReport, $rowUrl);
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e((string) $student['full_name']) ?></td>
                                <td class="table__actions">
                                    <a
                                        href="<?= e($listQuery(['student_id' => (int) $student['id']])) ?>"
                                        class="btn btn--primary btn--sm"
                                    >Открыть</a>
                                </td>
                                <td class="table__share-col parent-report-max-cell">
                                    <button
                                        type="button"
                                        class="btn btn--ghost btn--sm"
                                        data-share-max
                                        data-share-text="<?= e(rawurlencode($rowReport['text'])) ?>"
                                        title="Отправить полный текст отчёта в MAX"
                                    >Текст</button>
                                    <button
                                        type="button"
                                        class="btn btn--ghost btn--sm"
                                        data-share-max
                                        data-share-text="<?= e(rawurlencode($rowLinkText)) ?>"
                                        title="Отправить ссылку на отчёт в MAX"
                                    >Ссылка</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted table-hint">
                    «Текст» — полный отчёт в сообщении MAX.
                    «Ссылка» — короткое сообщение со ссылкой, которую родитель откроет без входа в систему.
                </p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<style>
.parent-report__org {
    margin: 0 0 0.75rem;
    text-align: center;
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.35;
}
.parent-report__title { margin: 0.25rem 0 0.75rem; font-size: 1.25rem; text-align: center; }
.parent-report__meta { margin: 0 0 1rem; color: var(--color-muted, #666); line-height: 1.45; }
.parent-report__grade-col { width: 5rem; text-align: center; }
.parent-report__grade--two { color: #b71c1c; font-weight: 700; }
.parent-report__summary { max-width: 28rem; margin-top: 0.75rem; }
.parent-report-actions { margin-bottom: 1rem; }
.parent-report-max-cell { white-space: nowrap; }
.parent-report-max-cell .btn { margin: 0.1rem; }
.parent-report-link-box { margin-bottom: 1.25rem; }
.parent-report-link-box label { display: block; margin-bottom: 0.35rem; font-weight: 600; }
.parent-report-link-box__row { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.parent-report-link-box__input {
    flex: 1 1 16rem;
    min-width: 0;
    padding: 0.45rem 0.6rem;
    border: 1px solid var(--color-border, #ccc);
    border-radius: 0.4rem;
    font-size: 0.875rem;
}
@media print {
    .header, .footer, .admin-tabs, .parent-report-actions,
    .panel__header--compact, .curator-group-selector, [data-share-max],
    .parent-report-link-box {
        display: none !important;
    }
    .parent-report { display: block !important; }
}
</style>

<script>
(() => {
    document.querySelectorAll('[data-share-max]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            let text = '';
            try {
                text = decodeURIComponent(button.dataset.shareText || '');
            } catch (e) {
                text = button.dataset.shareText || '';
            }
            if (!text) {
                window.alert('Нет данных для отправки.');
                return;
            }
            window.open(
                'https://max.ru/:share?text=' + encodeURIComponent(text),
                '_blank',
                'noopener,noreferrer'
            );
        });
    });

    document.querySelectorAll('[data-copy-text]').forEach((button) => {
        button.addEventListener('click', async () => {
            const text = button.dataset.copyText || '';
            if (!text) {
                return;
            }
            try {
                await navigator.clipboard.writeText(text);
                window.alert('Скопировано.');
            } catch (e) {
                window.alert('Не удалось скопировать.');
            }
        });
    });

    document.querySelectorAll('[data-parent-report-print]').forEach((button) => {
        button.addEventListener('click', () => window.print());
    });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
