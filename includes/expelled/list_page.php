<?php

declare(strict_types=1);

require_once __DIR__ . '/../expelled.php';

require_expelled_manager();

$showRestored = !isset($_GET['active_only']);
$filter = parse_expelled_list_filter($_GET);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $result = delete_expelled_student((int) ($_POST['expelled_id'] ?? 0));
        if ($result['success']) {
            flash_set('success', 'Отчисленный студент и связанные данные удалены.');
            header('Location: expelled.php' . build_expelled_list_query($filter, $showRestored));
            exit;
        }
        $error = $result['error'];
    }
}

$list = list_expelled_students(
    $showRestored,
    $filter['show_all_periods'] ? null : $filter['academic_year'],
    $filter['show_all_periods'] ? null : $filter['semester']
);
$yearOptions = get_expelled_academic_year_filter_options();
$success = flash_get('success');
$flashError = flash_get('error');
$error = $error ?? $flashError;
$viewBase = $viewBase ?? 'expelled_view.php';
$listQuery = build_expelled_list_query($filter, $showRestored);
