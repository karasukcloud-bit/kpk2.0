<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/students.php';
require_once __DIR__ . '/includes/organization.php';
require_once __DIR__ . '/includes/parent_report.php';

$token = trim((string) ($_GET['t'] ?? ''));
$payload = $token !== '' ? parent_report_parse_token($token) : null;
$error = null;
$report = null;

if ($payload === null) {
    $error = 'Ссылка недействительна или срок её действия истёк.';
} else {
    $student = get_student_by_id((int) $payload['student_id']);
    if ($student === null || !empty($student['removed_at'])) {
        $error = 'Студент не найден.';
    } else {
        $group = get_group_by_id((int) $student['group_id']);
        if ($group === null) {
            $error = 'Группа студента не найдена.';
        } else {
            $report = build_parent_student_report(
                $student,
                $group,
                (string) $payload['academic_year'],
                (string) $payload['semester'],
                (string) $payload['source']
            );
        }
    }
}

$pageTitle = $report !== null
    ? 'Отчёт для родителей — ' . (string) ($report['student']['full_name'] ?? '')
    : 'Отчёт для родителей';
$showHeader = true;
$basePath = app_base_path();
// Публичная страница: без меню панелей (header сам скрывает nav без логина).
require __DIR__ . '/includes/header.php';
?>

<div class="dashboard dashboard--wide">
    <section class="panel">
        <?php if ($error !== null): ?>
            <h1>Отчёт недоступен</h1>
            <p class="text-muted"><?= e($error) ?></p>
        <?php else: ?>
            <div class="panel__header panel__header--compact parent-report-actions">
                <p class="text-muted" style="margin:0;">Просмотр без авторизации</p>
                <button type="button" class="btn btn--ghost btn--sm" onclick="window.print()">Печать</button>
            </div>
            <?php require __DIR__ . '/includes/parent_report_view.php'; ?>
        <?php endif; ?>
    </section>
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
@media print {
    .header, .footer, .parent-report-actions { display: none !important; }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
