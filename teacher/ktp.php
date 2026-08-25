<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/profile.php';
require_once __DIR__ . '/../includes/ktp.php';
require_once __DIR__ . '/../includes/curriculum.php';

require_teacher_panel();

$itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
$error = null;

if ($itemId <= 0 || !can_manage_item_ktp($itemId)) {
    flash_set('error', 'Нет доступа к КТП этого предмета.');
    header('Location: subjects.php');
    exit;
}

$item = get_curriculum_item_by_id($itemId);
if ($item === null) {
    flash_set('error', 'Предмет не найден.');
    header('Location: subjects.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_ktp_topic') {
            $result = add_ktp_topic(
                $itemId,
                $_POST['ktp_title'] ?? '',
                $_POST['ktp_lesson_type'] ?? 'lecture',
                $_POST['ktp_hours'] ?? 2,
                true
            );
        } elseif ($action === 'add_ktp_attestation') {
            $result = add_ktp_attestation(
                $itemId,
                $_POST['attestation_type'] ?? '',
                $_POST['attestation_hours'] ?? 1
            );
        } elseif ($action === 'update_ktp_topic') {
            $topicId = (int) ($_POST['topic_id'] ?? 0);
            $topic = get_ktp_topic_by_id($topicId);
            if ($topic === null || (int) $topic['curriculum_item_id'] !== $itemId) {
                $result = ['success' => false, 'error' => 'Тема не найдена.'];
            } else {
                $lessonType = (string) ($_POST['ktp_lesson_type'] ?? 'lecture');
                $result = update_ktp_topic(
                    $topicId,
                    $_POST['ktp_title'] ?? '',
                    $lessonType,
                    $_POST['ktp_hours'] ?? 1,
                    $lessonType !== 'exam'
                );
            }
        } elseif ($action === 'delete_ktp_topic') {
            $topicId = (int) ($_POST['topic_id'] ?? 0);
            $topic = get_ktp_topic_by_id($topicId);
            if ($topic === null || (int) $topic['curriculum_item_id'] !== $itemId) {
                $result = ['success' => false, 'error' => 'Тема не найдена.'];
            } else {
                $result = delete_ktp_topic($topicId);
            }
        } else {
            $result = ['success' => false, 'error' => 'Неизвестное действие.'];
        }

        if ($result['success']) {
            if ($action === 'add_ktp_topic') {
                $count = (int) ($result['count'] ?? 1);
                $msg = $count > 1
                    ? ('Добавлено строк темы: ' . $count . ' (по 1 часу).')
                    : 'Тема добавлена в КТП.';
            } elseif ($action === 'add_ktp_attestation') {
                $count = (int) ($result['count'] ?? 1);
                $msg = $count > 1
                    ? ('Добавлена промежуточная аттестация: ' . $count . ' стр.')
                    : 'Промежуточная аттестация добавлена.';
            } elseif ($action === 'update_ktp_topic') {
                $added = (int) ($result['added'] ?? 0);
                $msg = $added > 0
                    ? ('Тема обновлена, добавлено строк: ' . $added . ' (по 1 часу).')
                    : 'Тема обновлена.';
            } else {
                $msg = 'Строка КТП удалена.';
            }
            flash_set('success', $msg);
            header('Location: ktp.php?item_id=' . $itemId);
            exit;
        }

        $error = $result['error'];
    }
}

$topics = get_ktp_topics_with_progress($itemId);
$ktpSummary = build_ktp_plan_summary($topics);
$success = flash_get('success');
$pageTitle = 'КТП — ' . $item['subject_name'];
$showHeader = true;
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="layout">
    <section class="panel">
        <div class="panel__header panel__header--compact">
            <h1>КТП: <?= e($item['subject_name']) ?></h1>
            <a href="subjects.php" class="btn btn--ghost btn--sm">← К предметам</a>
        </div>
        <p class="text-muted">
            Группа <?= e($item['group_number']) ?> · <?= e(semester_label($item['semester'])) ?> ·
            <?= e($item['academic_year']) ?>.
            Темы подгружаются в электронный журнал для всех преподавателей.
            Порядок тем можно менять перетаскиванием.
        </p>

        <?php if ($success): ?>
            <div class="alert alert--success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>
        <p class="ktp-reorder-status text-muted" data-ktp-reorder-status hidden></p>

        <?php if ($topics === []): ?>
            <p class="text-muted">Темы пока не добавлены.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table ktp-edit-table">
                    <thead>
                        <tr>
                            <th class="ktp-col-handle"></th>
                            <th>№</th>
                            <th>Тема</th>
                            <th>Тип</th>
                            <th>Часы</th>
                            <th>Статус</th>
                            <th class="table__actions-col">Действия</th>
                        </tr>
                    </thead>
                    <tbody
                        data-ktp-sortable
                        data-item-id="<?= (int) $itemId ?>"
                        data-reorder-url="ktp_reorder.php"
                        data-csrf="<?= e(csrf_token()) ?>"
                    >
                        <?php foreach ($topics as $index => $topic): ?>
                        <tr
                            class="ktp-sortable-row<?= !empty($topic['completed']) ? ' ktp-row--done' : '' ?>"
                            data-topic-id="<?= (int) $topic['id'] ?>"
                        >
                            <td class="ktp-col-handle">
                                <span class="ktp-drag-handle" title="Перетащить" aria-hidden="true">⋮⋮</span>
                            </td>
                            <td class="ktp-col-num" data-ktp-num><?= $index + 1 ?></td>
                            <td><?= e($topic['title']) ?></td>
                            <td><?= e(ktp_lesson_type_label((string) $topic['lesson_type'])) ?></td>
                            <td><?= e(rtrim(rtrim(number_format((float) $topic['hours'], 1, '.', ''), '0'), '.')) ?></td>
                            <td>
                                <?php if (!empty($topic['completed'])): ?>
                                    <span class="badge badge--success">Пройдена</span>
                                    <?php if (!empty($topic['first_lesson_date'])): ?>
                                        <span class="text-muted"><?= e(date('d.m.Y', (int) strtotime($topic['first_lesson_date']))) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Не пройдена</span>
                                <?php endif; ?>
                            </td>
                            <td class="table__actions">
                                <button
                                    type="button"
                                    class="journal-icon-btn"
                                    title="Изменить"
                                    data-ktp-edit-open
                                    data-topic-id="<?= (int) $topic['id'] ?>"
                                    data-title="<?= e($topic['title']) ?>"
                                    data-lesson-type="<?= e((string) $topic['lesson_type']) ?>"
                                    data-hours="<?= e(rtrim(rtrim(number_format((float) $topic['hours'], 1, '.', ''), '0'), '.')) ?>"
                                >✎</button>
                                <form method="post" class="form-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_ktp_topic">
                                    <input type="hidden" name="topic_id" value="<?= (int) $topic['id'] ?>">
                                    <button
                                        type="submit"
                                        class="journal-icon-btn journal-icon-btn--danger"
                                        title="Удалить"
                                        onclick="return confirm('Удалить строку КТП?')"
                                    >×</button>
                                </form>
                            </td>
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
                    <dt>Часов промежуточной аттестации</dt>
                    <dd><?= e((string) $ktpSummary['attestation_hours']) ?></dd>
                    <dt>Часов самостоятельных работ</dt>
                    <dd><?= e((string) $ktpSummary['independent_hours']) ?></dd>
                    <dt>Часов всего</dt>
                    <dd><?= e((string) $ktpSummary['total_hours']) ?></dd>
                </dl>
            </div>
        <?php endif; ?>

        <h3 class="subsection-title">Новая тема</h3>
        <p class="text-muted form-hint">
            Число часов создаёт столько же строк (по 1 часу). Для самостоятельной работы выберите соответствующий тип.
        </p>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_ktp_topic">

            <div class="form__row form__row--3">
                <div class="form__group">
                    <label for="ktp_title">Тема</label>
                    <input type="text" id="ktp_title" name="ktp_title" required placeholder="Тема урока">
                </div>
                <div class="form__group">
                    <label for="ktp_lesson_type">Тип</label>
                    <select id="ktp_lesson_type" name="ktp_lesson_type">
                        <option value="lecture">Лекция</option>
                        <option value="practice">Практика</option>
                        <option value="independent">Самостоятельная работа</option>
                    </select>
                </div>
                <div class="form__group">
                    <label for="ktp_hours">Часы (строк)</label>
                    <input type="number" id="ktp_hours" name="ktp_hours" min="1" max="24" step="1" value="2" required>
                </div>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Добавить тему</button>
            </div>
        </form>

        <h3 class="subsection-title">Промежуточная аттестация</h3>
        <p class="text-muted form-hint">
            Экзамен — одна строка с указанным числом часов.
            Остальные виды — по одной строке на каждый час.
            В теме будет: «Промежуточная аттестация. …».
        </p>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_ktp_attestation">

            <div class="form__row">
                <div class="form__group">
                    <label for="attestation_type">Вид аттестации</label>
                    <select id="attestation_type" name="attestation_type" required>
                        <option value="diff_credit">Дифференцированный зачёт</option>
                        <option value="credit">Зачёт</option>
                        <option value="exam">Экзамен</option>
                        <option value="control">Контрольная работа</option>
                    </select>
                </div>
                <div class="form__group">
                    <label for="attestation_hours">Количество часов</label>
                    <input type="number" id="attestation_hours" name="attestation_hours" min="1" max="24" step="1" value="1" required>
                </div>
            </div>
            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Добавить аттестацию</button>
            </div>
        </form>
    </section>
</div>

<div class="modal" data-ktp-edit-modal hidden>
    <div class="modal__backdrop" data-ktp-edit-close></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ktp-edit-title">
        <div class="modal__header">
            <h2 id="ktp-edit-title">Редактировать тему</h2>
            <button type="button" class="modal__close" data-ktp-edit-close aria-label="Закрыть">&times;</button>
        </div>
        <form method="post" class="form" data-ktp-edit-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_ktp_topic">
            <input type="hidden" name="topic_id" value="" data-ktp-edit-id>

            <div class="form__group">
                <label for="modal_ktp_title">Тема</label>
                <input type="text" id="modal_ktp_title" name="ktp_title" required data-ktp-edit-title>
            </div>

            <div class="form__group">
                <label for="modal_ktp_lesson_type">Тип</label>
                <select id="modal_ktp_lesson_type" name="ktp_lesson_type" data-ktp-edit-type>
                    <option value="lecture">Лекция</option>
                    <option value="practice">Практика</option>
                    <option value="independent">Самостоятельная работа</option>
                    <option value="diff_credit">Дифференцированный зачёт</option>
                    <option value="credit">Зачёт</option>
                    <option value="exam">Экзамен</option>
                    <option value="control">Контрольная работа</option>
                </select>
            </div>

            <div class="form__group">
                <label for="modal_ktp_hours">Часы</label>
                <input
                    type="number"
                    id="modal_ktp_hours"
                    name="ktp_hours"
                    min="1"
                    max="24"
                    step="1"
                    value="1"
                    required
                    data-ktp-edit-hours
                >
                <p class="text-muted form-hint" data-ktp-edit-hours-hint>
                    Для экзамена — часы одной строки. Для остальных типов число больше 1 добавит строки под текущей.
                </p>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Сохранить</button>
                <button type="button" class="btn btn--ghost" data-ktp-edit-close>Отмена</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
