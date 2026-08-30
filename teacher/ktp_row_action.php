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
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ошибка безопасности.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$itemId = (int) ($_POST['item_id'] ?? 0);
if ($itemId <= 0 || !can_manage_item_ktp($itemId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Нет доступа к КТП этого предмета.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = (string) ($_POST['action'] ?? '');
$result = ['success' => false, 'error' => 'Неизвестное действие.'];

$attachWorkload = static function (array $payload) use ($itemId): array {
    if (empty($payload['success'])) {
        return $payload;
    }

    $item = get_curriculum_item_by_id($itemId);
    if ($item === null) {
        return $payload;
    }

    $payload['workload'] = build_ktp_workload_client_payload($item, get_ktp_topics($itemId));

    return $payload;
};

if ($action === 'insert') {
    $afterId = (int) ($_POST['after_topic_id'] ?? 0);
    $result = $attachWorkload(insert_ktp_empty_row($itemId, $afterId > 0 ? $afterId : null));
} elseif ($action === 'copy') {
    $topicId = (int) ($_POST['topic_id'] ?? 0);
    $topic = get_ktp_topic_by_id($topicId);
    if ($topic === null || (int) $topic['curriculum_item_id'] !== $itemId) {
        $result = ['success' => false, 'error' => 'Строка не найдена.'];
    } else {
        $result = $attachWorkload(copy_ktp_row($topicId));
    }
} elseif ($action === 'save') {
    $topicId = (int) ($_POST['topic_id'] ?? 0);
    $topic = get_ktp_topic_by_id($topicId);
    if ($topic === null || (int) $topic['curriculum_item_id'] !== $itemId) {
        $result = ['success' => false, 'error' => 'Строка не найдена.'];
    } else {
        $result = $attachWorkload(save_ktp_row($topicId, $_POST));
    }
} elseif ($action === 'delete') {
    $topicId = (int) ($_POST['topic_id'] ?? 0);
    $topic = get_ktp_topic_by_id($topicId);
    if ($topic === null || (int) $topic['curriculum_item_id'] !== $itemId) {
        $result = ['success' => false, 'error' => 'Строка не найдена.'];
    } else {
        $result = delete_ktp_topic($topicId);
        if ($result['success']) {
            ensure_ktp_has_starter_row($itemId);
            $topics = get_ktp_topics_with_progress($itemId);
            $item = get_curriculum_item_by_id($itemId);
            $isProfessionality = curriculum_item_is_professionality($item);
            $result['topics'] = array_map(
                static fn (array $row): array => ktp_topic_payload_for_json($row, $isProfessionality),
                $topics
            );
            $result = $attachWorkload($result);
        }
    }
} elseif ($action === 'insert_marker') {
    $afterId = (int) ($_POST['after_topic_id'] ?? 0);
    $markerType = (string) ($_POST['marker_type'] ?? 'semester_2');
    $result = $attachWorkload(
        insert_ktp_semester_marker($itemId, $markerType, $afterId > 0 ? $afterId : null)
    );
} elseif ($action === 'save_column_widths') {
    $result = save_ktp_column_widths($itemId, $_POST['column_widths'] ?? null);
}

if (!$result['success']) {
    http_response_code(400);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
