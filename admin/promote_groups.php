<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/organization.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/gradebook.php';

require_admin();

$period = get_active_gradebook_period();
$defaultYear = $period['academic_year'];
$error = null;
$success = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $year = (string) ($_POST['academic_year'] ?? $defaultYear);

        if ($action === 'promote_one') {
            $result = promote_group(
                (int) ($_POST['group_id'] ?? 0),
                (string) ($_POST['new_number'] ?? ''),
                $year
            );
            if ($result['success']) {
                flash_set(
                    'success',
                    'Группа ' . $result['from_number'] . ' → ' . $result['to_number']
                    . ' (студентов: ' . (int) $result['students'] . '). Данные сохранены.'
                );
                header('Location: promote_groups.php');
                exit;
            }
            $error = $result['error'];
        } elseif ($action === 'promote_selected') {
            $year = (string) ($_POST['academic_year'] ?? $defaultYear);
            $items = $_POST['promote'] ?? [];
            if (!is_array($items) || $items === []) {
                $error = 'Отметьте хотя бы одну группу для перевода.';
            } else {
                $ok = [];
                $fail = [];
                foreach ($items as $groupId => $row) {
                    if (!is_array($row) || empty($row['checked'])) {
                        continue;
                    }
                    $result = promote_group(
                        (int) $groupId,
                        (string) ($row['new_number'] ?? ''),
                        $year
                    );
                    if ($result['success']) {
                        $ok[] = $result['from_number'] . ' → ' . $result['to_number'];
                    } else {
                        $fail[] = 'ID ' . (int) $groupId . ': ' . $result['error'];
                    }
                }
                if ($ok === [] && $fail === []) {
                    $error = 'Отметьте хотя бы одну группу для перевода.';
                } elseif ($fail === []) {
                    flash_set('success', 'Переведены группы: ' . implode(', ', $ok) . '.');
                    header('Location: promote_groups.php');
                    exit;
                } else {
                    $error = ($ok !== [] ? ('Переведены: ' . implode(', ', $ok) . '. ') : '')
                        . 'Ошибки: ' . implode('; ', $fail);
                }
            }
        }
    }
}

$preview = build_group_promotion_preview();
$pageTitle = 'Перевод групп на курс — Администрирование';
$showHeader = true;
$basePath = '../';
$currentAdminTab = 'promote_groups';
$panelTitle = 'Панель администратора';
$navFile = __DIR__ . '/../includes/admin_nav.php';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/promote_groups_page.php';
require __DIR__ . '/../includes/footer.php';
