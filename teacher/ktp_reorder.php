<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/profile.php';
require_once __DIR__ . '/../includes/ktp.php';
require_once __DIR__ . '/../includes/ktp/summary_table.php';

require_teacher_panel();

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается.']);
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ошибка безопасности.']);
    exit;
}

$itemId = (int) ($_POST['item_id'] ?? 0);
if ($itemId <= 0 || !can_manage_item_ktp($itemId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Нет доступа к КТП этого предмета.']);
    exit;
}

$orderRaw = $_POST['order'] ?? [];
if (is_string($orderRaw)) {
    $decoded = json_decode($orderRaw, true);
    $orderRaw = is_array($decoded) ? $decoded : [];
}

if (!is_array($orderRaw)) {
    $orderRaw = [];
}

$orderedIds = array_values(array_unique(array_map('intval', $orderRaw)));
$result = reorder_ktp_topics($itemId, $orderedIds);
if ($result['success']) {
    $item = get_curriculum_item_by_id($itemId);
    if ($item !== null) {
        // Считаем по порядку из запроса — совпадает с DOM после перетаскивания.
        $result['workload'] = build_ktp_workload_client_payload(
            $item,
            get_ktp_topics_in_ids_order($itemId, $orderedIds)
        );
    }
}
if (!$result['success']) {
    http_response_code(400);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
