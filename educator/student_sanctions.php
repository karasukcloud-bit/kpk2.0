<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/student_sanctions.php';

require_educator_panel();
ensure_student_sanctions_schema();

$studentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$student = $studentId > 0 ? get_student_by_id($studentId) : null;

$backParams = [];
if (isset($_GET['group_id']) && (int) $_GET['group_id'] > 0) {
    $backParams['group_id'] = (int) $_GET['group_id'];
}
if (isset($_GET['q']) && trim((string) $_GET['q']) !== '') {
    $backParams['q'] = trim((string) $_GET['q']);
}
if ((string) ($_GET['all'] ?? '') === '1') {
    $backParams['all'] = 1;
}
$backUrl = 'sanctions.php' . ($backParams !== [] ? ('?' . http_build_query($backParams)) : '');

if ($student === null || is_student_removed($student)) {
    flash_set('error', 'Студент не найден.');
    header('Location: ' . $backUrl);
    exit;
}

$group = get_group_by_id((int) $student['group_id']);
$sanctions = get_student_sanctions($studentId);
$byYear = group_sanctions_by_academic_year($sanctions);

$pageTitle = 'Взыскания студента — Панель воспитателя';
$showHeader = true;
$basePath = '../';
$currentEducatorTab = 'sanctions';
require __DIR__ . '/../includes/header.php';
?>

<div class="dashboard dashboard--wide educator-sanctions-page">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1><?= e((string) $student['full_name']) ?></h1>
                <p class="text-muted">
                    Группа <?= e((string) ($group['number'] ?? '—')) ?>
                    <?php if (($group['specialty_name'] ?? '') !== ''): ?>
                        · <?= e((string) $group['specialty_name']) ?>
                    <?php endif; ?>
                    · взыскания
                </p>
            </div>
            <a href="<?= e($backUrl) ?>" class="btn btn--ghost">← Назад к поиску</a>
        </div>
        <?php require __DIR__ . '/../includes/educator_nav.php'; ?>
    </section>

    <section class="panel">
        <h2>Сведения о студенте</h2>
        <dl class="profile-list">
            <dt>ФИО</dt>
            <dd><?= e((string) $student['full_name']) ?></dd>
            <dt>Группа</dt>
            <dd><?= e((string) ($group['number'] ?? '—')) ?></dd>
            <dt>Телефон</dt>
            <dd><?= e(trim((string) ($student['phone'] ?? '')) !== '' ? (string) $student['phone'] : '—') ?></dd>
            <dt>Всего взысканий</dt>
            <dd><strong><?= count($sanctions) ?></strong></dd>
        </dl>
    </section>

    <section class="panel">
        <h2>Взыскания</h2>
        <?php if ($sanctions === []): ?>
            <p class="text-muted">У студента нет дисциплинарных взысканий.</p>
        <?php else: ?>
            <?php foreach ($byYear as $yearBlock): ?>
                <h3 class="subsection-title">Учебный год <?= e((string) $yearBlock['academic_year']) ?></h3>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Тип</th>
                                <th>Дата</th>
                                <th>Учебный год</th>
                                <th>Номер приказа</th>
                                <th>Описание</th>
                                <th>Скан приказа</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($yearBlock['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= e(student_sanction_type_label((string) ($item['sanction_type'] ?? 'remark'))) ?></strong>
                                    </td>
                                    <td><?= e(format_sanction_date((string) ($item['issued_on'] ?? ''))) ?></td>
                                    <td><?= e((string) ($item['academic_year'] ?? '')) ?></td>
                                    <td>№ <?= e((string) ($item['order_number'] ?? '')) ?></td>
                                    <td><?= e(trim((string) ($item['description'] ?? '')) !== '' ? (string) $item['description'] : '—') ?></td>
                                    <td>
                                        <?php if (!empty($item['scan_stored_name'])): ?>
                                            <a href="sanction_file.php?id=<?= (int) $item['id'] ?>" target="_blank" rel="noopener">
                                                <?= e((string) ($item['scan_original_name'] ?: 'Открыть скан')) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">не прикреплён</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
