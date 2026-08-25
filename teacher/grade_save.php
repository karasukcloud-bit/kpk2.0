<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';
require_once __DIR__ . '/../includes/journal.php';

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

$lessonId = (int) ($_POST['lesson_id'] ?? 0);
$studentId = (int) ($_POST['student_id'] ?? 0);
$mark = (string) ($_POST['mark'] ?? '');
$activity = isset($_POST['activity']) && (string) $_POST['activity'] === '1';
$late = isset($_POST['late']) && (string) $_POST['late'] === '1';

$result = save_journal_grade($lessonId, $studentId, $mark, $activity, $late);

if (!$result['success']) {
    http_response_code(400);
    echo json_encode($result);
    exit;
}

$lesson = get_journal_lesson_by_id($lessonId);
$itemId = (int) $lesson['curriculum_item_id'];
$students = get_students_by_group((int) $lesson['group_id']);
$lessons = get_journal_lessons($itemId);
$grades = get_journal_grades($itemId);
$totals = build_journal_totals($students, $lessons, $grades);

echo json_encode([
    'success' => true,
    'entry' => $result['entry'],
    'totals' => $totals,
]);
