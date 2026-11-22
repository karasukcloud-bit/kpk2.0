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
.parent-report__average { margin: 0.65rem 0 0; }
.parent-report__summary { max-width: 28rem; margin-top: 0.75rem; }
.parent-report__attendance-note { margin: 0 0 0.5rem; }
.parent-report-actions { margin-bottom: 1rem; }
@media print {
    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    .header,
    .footer,
    .parent-report-actions,
    .parent-report__title {
        display: none !important;
    }

    .page,
    .dashboard,
    .panel {
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }

    .parent-report {
        font-size: 10.5pt;
        line-height: 1.25;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .parent-report__org {
        margin: 0 0 0.35rem;
        font-size: 11.5pt;
    }

    .parent-report__meta {
        margin: 0 0 0.45rem;
        font-size: 9.5pt;
        line-height: 1.3;
    }

    .parent-report__body {
        display: grid;
        grid-template-columns: 1.45fr 1fr;
        gap: 0.75rem 1rem;
        align-items: start;
    }

    .parent-report__block h3 {
        margin: 0 0 0.3rem;
        font-size: 11pt;
    }

    .parent-report__grades-wrap {
        overflow: visible !important;
    }

    .parent-report__grades-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9pt;
    }

    .parent-report__grades-table th,
    .parent-report__grades-table td {
        padding: 0.12rem 0.3rem !important;
        border: 1px solid #333 !important;
        line-height: 1.2;
    }

    .parent-report__average {
        margin: 0.35rem 0 0;
        font-size: 10pt;
    }

    .parent-report__attendance-note {
        margin: 0 0 0.3rem;
        font-size: 9pt;
    }

    .parent-report__summary {
        margin: 0;
        max-width: none;
    }

    .parent-report__summary dt,
    .parent-report__summary dd {
        margin: 0;
        padding: 0.15rem 0;
        font-size: 9.5pt;
        line-height: 1.25;
    }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
