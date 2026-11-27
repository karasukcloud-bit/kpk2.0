<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/student_sanctions.php';

require_educator_panel();
ensure_student_sanctions_schema();

$groups = get_all_groups();
$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$query = trim((string) ($_GET['q'] ?? ''));
$showAll = (string) ($_GET['all'] ?? '') === '1';

if ($groupId > 0) {
    $exists = false;
    foreach ($groups as $g) {
        if ((int) $g['id'] === $groupId) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $groupId = 0;
    }
}

$withSanctions = search_students_with_sanctions($groupId > 0 ? $groupId : null, $query);

if ($showAll && ($groupId > 0 || $query !== '')) {
    $students = get_students_list($groupId > 0 ? $groupId : null);
    $withMap = [];
    foreach ($withSanctions as $row) {
        $withMap[(int) $row['student_id']] = $row;
    }
    $browseRows = [];
    foreach ($students as $student) {
        $sid = (int) ($student['id'] ?? 0);
        if ($sid < 1) {
            continue;
        }
        if ($query !== '') {
            $hay = mb_strtolower((string) ($student['full_name'] ?? ''));
            if (mb_strpos($hay, mb_strtolower($query)) === false) {
                continue;
            }
        }
        if (isset($withMap[$sid])) {
            $browseRows[] = $withMap[$sid];
            continue;
        }
        $browseRows[] = [
            'student_id' => $sid,
            'full_name' => (string) ($student['full_name'] ?? ''),
            'group_id' => (int) ($student['group_id'] ?? 0),
            'group_number' => (string) ($student['group_number'] ?? ''),
            'sanctions_count' => 0,
            'last_issued_on' => null,
            'sanctions' => [],
        ];
    }
    usort(
        $browseRows,
        static fn (array $a, array $b): int => ((int) $b['sanctions_count'] <=> (int) $a['sanctions_count'])
            ?: strcasecmp((string) $a['full_name'], (string) $b['full_name'])
    );
} else {
    $browseRows = $withSanctions;
}

$pageTitle = 'Взыскания — Панель воспитателя';
$showHeader = true;
$basePath = '../';
$currentEducatorTab = 'sanctions';
require __DIR__ . '/../includes/header.php';

$openUrl = static function (int $studentId) use ($groupId, $query, $showAll): string {
    $params = ['id' => $studentId];
    if ($groupId > 0) {
        $params['group_id'] = $groupId;
    }
    if ($query !== '') {
        $params['q'] = $query;
    }
    if ($showAll) {
        $params['all'] = 1;
    }

    return 'student_sanctions.php?' . http_build_query($params);
};
?>

<div class="dashboard dashboard--wide educator-sanctions-page">
    <section class="panel">
        <div class="panel__header">
            <div>
                <h1>Панель воспитателя</h1>
                <p class="text-muted">Взыскания студентов (данные вносят кураторы групп)</p>
            </div>
        </div>
        <?php require __DIR__ . '/../includes/educator_nav.php'; ?>
    </section>

    <section class="panel">
        <h2>Поиск</h2>
        <form method="get" class="form form--filters">
            <div class="form__grid form__grid--3">
                <div class="form__group">
                    <label for="sanctions_group">Группа</label>
                    <select id="sanctions_group" name="group_id">
                        <option value="0">Все группы</option>
                        <?php foreach ($groups as $g): ?>
                            <option value="<?= (int) $g['id'] ?>"<?= $groupId === (int) $g['id'] ? ' selected' : '' ?>>
                                <?= e((string) $g['number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form__group">
                    <label for="sanctions_q">ФИО</label>
                    <input
                        type="search"
                        id="sanctions_q"
                        name="q"
                        value="<?= e($query) ?>"
                        placeholder="Фамилия или ФИО"
                        autocomplete="off"
                    >
                </div>
                <div class="form__group">
                    <label for="sanctions_scope">Показывать</label>
                    <select id="sanctions_scope" name="all">
                        <option value="0"<?= !$showAll ? ' selected' : '' ?>>Только со взысканиями</option>
                        <option value="1"<?= $showAll ? ' selected' : '' ?>>Все найденные студенты</option>
                    </select>
                </div>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Найти</button>
                <a class="btn btn--ghost" href="sanctions.php">Сбросить</a>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Результаты</h2>
        <?php if ($browseRows === []): ?>
            <p class="text-muted">
                <?= $groupId > 0 || $query !== ''
                    ? 'По заданным условиям ничего не найдено.'
                    : 'В системе пока нет записей о взысканиях.' ?>
            </p>
        <?php else: ?>
            <p class="text-muted">Найдено: <?= count($browseRows) ?></p>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Студент</th>
                            <th>Группа</th>
                            <th>Взысканий</th>
                            <th>Последнее</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($browseRows as $row): ?>
                            <?php $count = (int) $row['sanctions_count']; ?>
                            <tr>
                                <td>
                                    <a href="<?= e($openUrl((int) $row['student_id'])) ?>">
                                        <strong><?= e((string) $row['full_name']) ?></strong>
                                    </a>
                                </td>
                                <td><?= e((string) $row['group_number']) ?></td>
                                <td>
                                    <?php if ($count > 0): ?>
                                        <strong><?= $count ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">нет</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $row['last_issued_on']
                                        ? e(format_sanction_date((string) $row['last_issued_on']))
                                        : '—' ?>
                                </td>
                                <td>
                                    <a class="btn btn--ghost btn--sm" href="<?= e($openUrl((int) $row['student_id'])) ?>">
                                        Открыть
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
