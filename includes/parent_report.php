<?php

declare(strict_types=1);

require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/attendance.php';
require_once __DIR__ . '/organization.php';
require_once __DIR__ . '/curriculum.php';

/**
 * Источник оценок для отчёта родителям.
 */
function parent_report_grade_source(?string $requested = null): string
{
    $requested = $requested === 'manual_brs' ? 'manual_brs' : 'journal';
    if ($requested === 'manual_brs' && is_manual_brs_gradebook_active()) {
        return 'manual_brs';
    }

    return 'journal';
}

function parent_report_source_label(string $source): string
{
    return $source === 'manual_brs'
        ? 'Ручное БРС'
        : 'Электронный журнал';
}

/**
 * @return array{source: string, org_name: string, subjects_rows: list, grades_map: array, attendance: array}
 */
function parent_report_group_context(
    int $groupId,
    string $academicYear,
    string $semester,
    string $source = 'journal'
): array {
    $source = parent_report_grade_source($source);
    $org = get_organization();

    return [
        'source' => $source,
        'org_name' => trim((string) ($org['name'] ?? '')),
        'subjects_rows' => get_group_curriculum_subjects($groupId, $academicYear, $semester),
        'grades_map' => get_gradebook_grades_by_source($groupId, $academicYear, $semester, $source),
        'attendance' => fetch_group_attendance_student_totals(
            $groupId,
            $academicYear,
            null,
            $semester
        ),
    ];
}

/**
 * @return array{
 *   student: array,
 *   group: array,
 *   period: array{academic_year: string, semester: string},
 *   source: string,
 *   org_name: string,
 *   subjects: list<array{name: string, grade: ?int}>,
 *   grades_filled: int,
 *   grades_expected: int,
 *   average: ?float,
 *   has_twos: bool,
 *   attendance: array{excused: int, unexcused: int, total: int},
 *   text: string
 * }
 */
function build_parent_student_report(
    array $student,
    array $group,
    string $academicYear,
    string $semester,
    string $source = 'journal',
    ?array $context = null
): array {
    $groupId = (int) $group['id'];
    $context = $context ?? parent_report_group_context($groupId, $academicYear, $semester, $source);
    $source = (string) $context['source'];
    $studentId = (int) $student['id'];
    $subjectsRows = $context['subjects_rows'];
    $studentGrades = $context['grades_map'][$studentId] ?? [];

    $subjects = [];
    $values = [];
    $hasTwos = false;
    foreach ($subjectsRows as $row) {
        $itemId = (int) $row['curriculum_item_id'];
        $grade = isset($studentGrades[$itemId]) ? (int) $studentGrades[$itemId] : null;
        if ($grade !== null) {
            $values[] = $grade;
            if ($grade === 2) {
                $hasTwos = true;
            }
        }
        $subjects[] = [
            'name' => (string) $row['subject_name'],
            'grade' => $grade,
        ];
    }

    $att = $context['attendance'][$studentId] ?? ['excused_lessons' => 0, 'unexcused_lessons' => 0];
    $excused = (int) ($att['excused_lessons'] ?? 0);
    $unexcused = (int) ($att['unexcused_lessons'] ?? 0);

    $average = $values !== []
        ? round(array_sum($values) / count($values), 2)
        : null;

    $report = [
        'student' => $student,
        'group' => $group,
        'period' => [
            'academic_year' => $academicYear,
            'semester' => $semester,
        ],
        'source' => $source,
        'org_name' => (string) $context['org_name'],
        'subjects' => $subjects,
        'grades_filled' => count($values),
        'grades_expected' => count($subjectsRows),
        'average' => $average,
        'has_twos' => $hasTwos,
        'attendance' => [
            'excused' => $excused,
            'unexcused' => $unexcused,
            'total' => $excused + $unexcused,
        ],
        'text' => '',
    ];
    $report['text'] = format_parent_student_report_text($report);

    return $report;
}

function format_parent_student_report_text(array $report): string
{
    $student = $report['student'];
    $group = $report['group'];
    $period = $report['period'];
    $lines = [];

    if ($report['org_name'] !== '') {
        $lines[] = (string) $report['org_name'];
    }
    $lines[] = 'Отчёт для родителей';
    $lines[] = 'Студент: ' . trim((string) ($student['full_name'] ?? ''));
    $lines[] = 'Группа: ' . (string) ($group['number'] ?? '');
    if (!empty($group['specialty_name'])) {
        $lines[] = 'Специальность: ' . (string) $group['specialty_name'];
    }
    $lines[] = 'Период: ' . semester_label((string) $period['semester'])
        . ' ' . (string) $period['academic_year'] . ' учебного года';
    $lines[] = 'Источник оценок: ' . parent_report_source_label((string) $report['source']);
    $lines[] = '';
    $lines[] = 'Успеваемость:';

    if ($report['subjects'] === []) {
        $lines[] = 'Предметы за период не найдены.';
    } else {
        foreach ($report['subjects'] as $item) {
            $grade = $item['grade'];
            $lines[] = '• ' . $item['name'] . ' — ' . ($grade !== null ? (string) $grade : 'нет оценки');
        }
        $lines[] = 'Выставлено оценок: ' . (int) $report['grades_filled']
            . ' из ' . (int) $report['grades_expected'];
        if ($report['average'] !== null) {
            $lines[] = 'Средний балл: ' . rtrim(rtrim(number_format((float) $report['average'], 2, '.', ''), '0'), '.');
        }
        $lines[] = 'Есть оценки «2»: ' . (!empty($report['has_twos']) ? 'да' : 'нет');
    }

    $att = $report['attendance'];
    $lines[] = '';
    $lines[] = 'Посещаемость за ' . semester_label((string) $period['semester']) . ':';
    $lines[] = '• Уважительные пропуски: ' . (int) $att['excused'];
    $lines[] = '• Неуважительные пропуски: ' . (int) $att['unexcused'];
    $lines[] = '• Всего пропусков: ' . (int) $att['total'];
    $lines[] = '';
    $lines[] = 'Сформировано: ' . date('d.m.Y H:i');

    return implode("\n", $lines);
}

function max_messenger_share_url(string $text): string
{
    return 'https://max.ru/:share?text=' . rawurlencode($text);
}

function parent_report_link_secret(): string
{
    $secret = get_app_setting('parent_report_link_secret');
    if ($secret === null || strlen($secret) < 32) {
        $secret = bin2hex(random_bytes(32));
        set_app_setting('parent_report_link_secret', $secret);
    }

    return $secret;
}

function parent_report_base64url_encode(string $raw): string
{
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}

function parent_report_base64url_decode(string $raw): ?string
{
    $decoded = base64_decode(strtr($raw, '-_', '+/'), true);

    return $decoded === false ? null : $decoded;
}

function parent_report_create_token(
    int $studentId,
    string $academicYear,
    string $semester,
    string $source = 'journal',
    int $ttlDays = 180
): string {
    $payload = json_encode([
        's' => $studentId,
        'y' => $academicYear,
        'm' => $semester === '2' ? '2' : '1',
        'src' => parent_report_grade_source($source),
        'e' => time() + max(1, $ttlDays) * 86400,
    ], JSON_UNESCAPED_UNICODE);
    $body = parent_report_base64url_encode((string) $payload);
    $sig = hash_hmac('sha256', $body, parent_report_link_secret());

    return $body . '.' . $sig;
}

/**
 * @return array{student_id: int, academic_year: string, semester: string, source: string}|null
 */
function parent_report_parse_token(string $token): ?array
{
    $token = trim($token);
    if ($token === '' || !str_contains($token, '.')) {
        return null;
    }

    [$body, $sig] = explode('.', $token, 2);
    if ($body === '' || $sig === '') {
        return null;
    }

    $expected = hash_hmac('sha256', $body, parent_report_link_secret());
    if (!hash_equals($expected, $sig)) {
        return null;
    }

    $json = parent_report_base64url_decode($body);
    if ($json === null) {
        return null;
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return null;
    }

    $exp = (int) ($data['e'] ?? 0);
    if ($exp > 0 && time() > $exp) {
        return null;
    }

    $studentId = (int) ($data['s'] ?? 0);
    $year = normalize_academic_year((string) ($data['y'] ?? '')) ?? '';
    $semester = ((string) ($data['m'] ?? '1')) === '2' ? '2' : '1';
    $source = parent_report_grade_source((string) ($data['src'] ?? 'journal'));

    if ($studentId <= 0 || $year === '') {
        return null;
    }

    return [
        'student_id' => $studentId,
        'academic_year' => $year,
        'semester' => $semester,
        'source' => $source,
    ];
}

function parent_report_absolute_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

    return ($https ? 'https' : 'http') . '://' . $host . app_base_path();
}

function parent_report_public_url(string $token): string
{
    return parent_report_absolute_base_url() . 'parent_report.php?t=' . rawurlencode($token);
}

function format_parent_student_report_link_text(array $report, string $url): string
{
    $student = $report['student'];
    $group = $report['group'];
    $period = $report['period'];
    $lines = [];

    if ($report['org_name'] !== '') {
        $lines[] = (string) $report['org_name'];
    }
    $lines[] = 'Отчёт об успеваемости и посещаемости';
    $lines[] = 'Студент: ' . trim((string) ($student['full_name'] ?? ''));
    $lines[] = 'Группа: ' . (string) ($group['number'] ?? '');
    $lines[] = 'Период: ' . semester_label((string) $period['semester'])
        . ' ' . (string) $period['academic_year'];
    $lines[] = '';
    $lines[] = 'Открыть отчёт:';
    $lines[] = $url;

    return implode("\n", $lines);
}
