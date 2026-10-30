<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/glaz.php';
require_once __DIR__ . '/student_activities.php';
require_once __DIR__ . '/attendance.php';

function ensure_curator_monthly_reports_schema(?PDO $pdo = null): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo = $pdo ?? db();
    if ($pdo->query("SHOW TABLES LIKE 'curator_monthly_reports'")->fetch()) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE curator_monthly_reports (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id INT UNSIGNED NOT NULL,
            report_month CHAR(7) NOT NULL,
            payload_json MEDIUMTEXT NOT NULL,
            total_points SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            updated_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_curator_monthly_group_month (group_id, report_month),
            KEY idx_curator_monthly_month (report_month),
            CONSTRAINT fk_curator_monthly_group
                FOREIGN KEY (group_id) REFERENCES study_groups(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_curator_monthly_user
                FOREIGN KEY (updated_by) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

/**
 * Баллы по доле: 0–25 → 1, 26–50 → 2, 51–75 → 3, 76–100 → 4.
 * При $inverted шкала обратная (меньше % — больше баллов).
 */
function curator_monthly_report_points_by_percent(float $percent, bool $inverted = false): int
{
    $percent = max(0.0, min(100.0, $percent));
    if (!$inverted && $percent <= 0) {
        return 0;
    }
    if ($percent > 75) {
        $band = 4;
    } elseif ($percent > 50) {
        $band = 3;
    } elseif ($percent > 25) {
        $band = 2;
    } else {
        $band = 1;
    }

    return $inverted ? (5 - $band) : $band;
}

function curator_monthly_report_percent(int $part, int $total): float
{
    if ($total <= 0) {
        return 0.0;
    }

    return round(($part / $total) * 100, 1);
}

/**
 * @return array{
 *   clubs: int,
 *   debts: int,
 *   unexcused: int
 * }
 */
function curator_monthly_report_auto_metrics(int $groupId, array $students, string $academicYear, string $month): array
{
    $studentCount = count($students);
    $clubs = 0;
    if ($studentCount > 0) {
        $clubs = (int) (build_group_activities_report($students)['with_activities'] ?? 0);
    }

    $debts = 0;
    if ($studentCount > 0) {
        $ids = [];
        foreach ($students as $student) {
            $ids[(int) $student['id']] = true;
        }
        $debtorIds = [];
        foreach (get_all_academic_debts() as $debt) {
            $sid = (int) ($debt['student_id'] ?? 0);
            if (isset($ids[$sid])) {
                $debtorIds[$sid] = true;
            }
        }
        $debts = count($debtorIds);
    }

    $unexcused = 0;
    if ($studentCount > 0 && $groupId > 0 && $academicYear !== '') {
        $journal = get_attendance_journal($groupId, $academicYear, $month);
        $totals = build_attendance_month_totals($students, $journal);
        foreach ($totals as $row) {
            if ((int) ($row['unexcused_lessons'] ?? 0) > 0) {
                $unexcused++;
            }
        }
    }

    return [
        'clubs' => $clubs,
        'debts' => $debts,
        'unexcused' => $unexcused,
    ];
}

/**
 * @param list<int> $ids
 * @param list<int> $allowed
 * @return list<int>
 */
function curator_monthly_report_normalize_student_ids(array $ids, array $allowed): array
{
    $allowedMap = [];
    foreach ($allowed as $id) {
        $allowedMap[(int) $id] = true;
    }
    $out = [];
    foreach ($ids as $id) {
        $id = (int) $id;
        if ($id > 0 && isset($allowedMap[$id]) && !isset($out[$id])) {
            $out[$id] = $id;
        }
    }

    return array_values($out);
}

/**
 * @param list<string> $titles
 * @return list<string>
 */
function curator_monthly_report_normalize_event_titles(array $titles): array
{
    $out = [];
    foreach ($titles as $title) {
        $title = trim((string) $title);
        if ($title === '') {
            continue;
        }
        if (function_exists('mb_substr')) {
            $title = mb_substr($title, 0, 255, 'UTF-8');
        } else {
            $title = substr($title, 0, 255);
        }
        $out[] = $title;
    }

    return $out;
}

/**
 * @return array<string, mixed>
 */
function curator_monthly_report_default_payload(): array
{
    return [
        'key_events_count' => 0,
        'group_events' => [],
        'clubs_count' => 0,
        'debts_count' => 0,
        'unexcused_count' => 0,
        'sanctions' => [],
        'bad_habits' => [],
        'appearance' => [],
        'volunteers' => [],
        'cafeteria' => [],
        'characteristics' => 'not_submitted',
        'item_points' => [],
        'total_points' => 0,
    ];
}

/**
 * @param array<string, mixed> $payload
 * @param list<int> $allowedStudentIds
 * @return array<string, mixed>
 */
function curator_monthly_report_normalize_payload(
    array $payload,
    int $studentCount,
    array $allowedStudentIds,
    array $autoMetrics
): array {
    $base = curator_monthly_report_default_payload();
    $keyEvents = max(0, min($studentCount, (int) ($payload['key_events_count'] ?? 0)));
    $groupEvents = curator_monthly_report_normalize_event_titles(
        is_array($payload['group_events'] ?? null) ? $payload['group_events'] : []
    );
    $characteristics = (($payload['characteristics'] ?? '') === 'submitted')
        ? 'submitted'
        : 'not_submitted';

    $lists = [
        'sanctions' => curator_monthly_report_normalize_student_ids(
            is_array($payload['sanctions'] ?? null) ? $payload['sanctions'] : [],
            $allowedStudentIds
        ),
        'bad_habits' => curator_monthly_report_normalize_student_ids(
            is_array($payload['bad_habits'] ?? null) ? $payload['bad_habits'] : [],
            $allowedStudentIds
        ),
        'appearance' => curator_monthly_report_normalize_student_ids(
            is_array($payload['appearance'] ?? null) ? $payload['appearance'] : [],
            $allowedStudentIds
        ),
        'volunteers' => curator_monthly_report_normalize_student_ids(
            is_array($payload['volunteers'] ?? null) ? $payload['volunteers'] : [],
            $allowedStudentIds
        ),
        'cafeteria' => curator_monthly_report_normalize_student_ids(
            is_array($payload['cafeteria'] ?? null) ? $payload['cafeteria'] : [],
            $allowedStudentIds
        ),
    ];

    $normalized = array_merge($base, [
        'key_events_count' => $keyEvents,
        'group_events' => $groupEvents,
        'clubs_count' => (int) ($autoMetrics['clubs'] ?? 0),
        'debts_count' => (int) ($autoMetrics['debts'] ?? 0),
        'unexcused_count' => (int) ($autoMetrics['unexcused'] ?? 0),
        'sanctions' => $lists['sanctions'],
        'bad_habits' => $lists['bad_habits'],
        'appearance' => $lists['appearance'],
        'volunteers' => $lists['volunteers'],
        'cafeteria' => $lists['cafeteria'],
        'characteristics' => $characteristics,
    ]);

    $scored = curator_monthly_report_score_payload($normalized, $studentCount);
    $normalized['item_points'] = $scored['item_points'];
    $normalized['total_points'] = $scored['total_points'];

    return $normalized;
}

/**
 * @param array<string, mixed> $payload
 * @return array{item_points: array<string, int>, total_points: int, percents: array<string, float>}
 */
function curator_monthly_report_score_payload(array $payload, int $studentCount): array
{
    $eventsCount = count(is_array($payload['group_events'] ?? null) ? $payload['group_events'] : []);
    // 4 мероприятия ≈ 100% шкалы баллов
    $eventsPercent = min(100.0, curator_monthly_report_percent($eventsCount, 4));

    $items = [
        'key_events' => [
            'count' => (int) ($payload['key_events_count'] ?? 0),
            'inverted' => false,
        ],
        'group_events' => [
            'percent' => $eventsPercent,
            'inverted' => false,
        ],
        'clubs' => [
            'count' => (int) ($payload['clubs_count'] ?? 0),
            'inverted' => false,
        ],
        'debts' => [
            'count' => (int) ($payload['debts_count'] ?? 0),
            'inverted' => true,
        ],
        'unexcused' => [
            'count' => (int) ($payload['unexcused_count'] ?? 0),
            'inverted' => false,
        ],
        'sanctions' => [
            'count' => count(is_array($payload['sanctions'] ?? null) ? $payload['sanctions'] : []),
            'inverted' => true,
        ],
        'bad_habits' => [
            'count' => count(is_array($payload['bad_habits'] ?? null) ? $payload['bad_habits'] : []),
            'inverted' => true,
        ],
        'appearance' => [
            'count' => count(is_array($payload['appearance'] ?? null) ? $payload['appearance'] : []),
            'inverted' => true,
        ],
        'volunteers' => [
            'count' => count(is_array($payload['volunteers'] ?? null) ? $payload['volunteers'] : []),
            'inverted' => false,
        ],
        'cafeteria' => [
            'count' => count(is_array($payload['cafeteria'] ?? null) ? $payload['cafeteria'] : []),
            'inverted' => false,
        ],
    ];

    $itemPoints = [];
    $percents = [];
    $total = 0;

    foreach ($items as $key => $meta) {
        if (isset($meta['percent'])) {
            $percent = (float) $meta['percent'];
        } else {
            $percent = curator_monthly_report_percent((int) $meta['count'], $studentCount);
        }
        $points = curator_monthly_report_points_by_percent($percent, (bool) $meta['inverted']);
        $itemPoints[$key] = $points;
        $percents[$key] = $percent;
        $total += $points;
    }

    $charPoints = (($payload['characteristics'] ?? '') === 'submitted') ? 4 : 0;
    $itemPoints['characteristics'] = $charPoints;
    $percents['characteristics'] = (($payload['characteristics'] ?? '') === 'submitted') ? 100.0 : 0.0;
    $total += $charPoints;

    return [
        'item_points' => $itemPoints,
        'total_points' => $total,
        'percents' => $percents,
    ];
}

/**
 * @return array<string, mixed>|null
 */
function get_curator_monthly_report(int $groupId, string $month): ?array
{
    ensure_curator_monthly_reports_schema();
    $month = normalize_attendance_month($month) ?? '';
    if ($groupId < 1 || $month === '') {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT * FROM curator_monthly_reports
         WHERE group_id = ? AND report_month = ?
         LIMIT 1'
    );
    $stmt->execute([$groupId, $month]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    $decoded = json_decode((string) ($row['payload_json'] ?? ''), true);
    if (!is_array($decoded)) {
        $decoded = curator_monthly_report_default_payload();
    }

    return [
        'id' => (int) $row['id'],
        'group_id' => (int) $row['group_id'],
        'report_month' => (string) $row['report_month'],
        'payload' => $decoded,
        'total_points' => (int) $row['total_points'],
        'updated_by' => $row['updated_by'] !== null ? (int) $row['updated_by'] : null,
        'updated_at' => (string) ($row['updated_at'] ?? ''),
    ];
}

/**
 * @param array<string, mixed> $payload
 * @return array{success: bool, error?: string, total_points?: int}
 */
function save_curator_monthly_report(
    int $groupId,
    string $month,
    array $payload,
    ?int $userId
): array {
    ensure_curator_monthly_reports_schema();
    $month = normalize_attendance_month($month) ?? '';
    if ($groupId < 1 || $month === '') {
        return ['success' => false, 'error' => 'Некорректный месяц или группа.'];
    }

    $totalPoints = (int) ($payload['total_points'] ?? 0);
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return ['success' => false, 'error' => 'Не удалось сохранить данные отчёта.'];
    }

    $pdo = db();
    $existing = get_curator_monthly_report($groupId, $month);
    if ($existing !== null) {
        $stmt = $pdo->prepare(
            'UPDATE curator_monthly_reports
             SET payload_json = ?, total_points = ?, updated_by = ?
             WHERE group_id = ? AND report_month = ?'
        );
        $stmt->execute([$json, $totalPoints, $userId, $groupId, $month]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO curator_monthly_reports
             (group_id, report_month, payload_json, total_points, updated_by)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$groupId, $month, $json, $totalPoints, $userId]);
    }

    return ['success' => true, 'total_points' => $totalPoints];
}

/**
 * Отчёты одной группы по месяцам учебного года.
 *
 * @param list<array{value: string, label: string}> $monthOptions
 * @return list<array{
 *   month: string,
 *   label: string,
 *   has_report: bool,
 *   total_points: int|null,
 *   updated_at: string
 * }>
 */
function list_curator_monthly_reports_for_group(int $groupId, array $monthOptions): array
{
    ensure_curator_monthly_reports_schema();
    $groupId = (int) $groupId;
    if ($groupId < 1 || $monthOptions === []) {
        return [];
    }

    $monthValues = [];
    foreach ($monthOptions as $opt) {
        $value = (string) ($opt['value'] ?? '');
        if ($value !== '') {
            $monthValues[] = $value;
        }
    }
    if ($monthValues === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($monthValues), '?'));
    $params = array_merge([$groupId], $monthValues);
    $stmt = db()->prepare(
        "SELECT report_month, total_points, updated_at
         FROM curator_monthly_reports
         WHERE group_id = ? AND report_month IN ($placeholders)"
    );
    $stmt->execute($params);
    $byMonth = [];
    foreach ($stmt->fetchAll() as $row) {
        $byMonth[(string) $row['report_month']] = [
            'total_points' => (int) $row['total_points'],
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    $rows = [];
    foreach ($monthOptions as $opt) {
        $value = (string) ($opt['value'] ?? '');
        if ($value === '') {
            continue;
        }
        $saved = $byMonth[$value] ?? null;
        $rows[] = [
            'month' => $value,
            'label' => (string) ($opt['label'] ?? $value),
            'has_report' => $saved !== null,
            'total_points' => $saved !== null ? (int) $saved['total_points'] : null,
            'updated_at' => $saved['updated_at'] ?? '',
        ];
    }

    return $rows;
}

/**
 * Баллы всех групп за месяц (включая группы без отчёта).
 *
 * @return list<array{
 *   group_id: int,
 *   group_number: string,
 *   curator_name: string,
 *   specialty_name: string,
 *   has_report: bool,
 *   total_points: int|null,
 *   updated_at: string
 * }>
 */
function build_curator_monthly_reports_overview(string $month): array
{
    ensure_curator_monthly_reports_schema();
    require_once __DIR__ . '/organization.php';

    $month = normalize_attendance_month($month) ?? '';
    if ($month === '') {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT group_id, total_points, updated_at
         FROM curator_monthly_reports
         WHERE report_month = ?'
    );
    $stmt->execute([$month]);
    $byGroup = [];
    foreach ($stmt->fetchAll() as $row) {
        $byGroup[(int) $row['group_id']] = [
            'total_points' => (int) $row['total_points'],
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    $rows = [];
    foreach (get_all_groups() as $group) {
        $groupId = (int) $group['id'];
        $report = $byGroup[$groupId] ?? null;
        $rows[] = [
            'group_id' => $groupId,
            'group_number' => (string) ($group['number'] ?? ''),
            'curator_name' => (string) ($group['curator_name'] ?? ''),
            'specialty_name' => (string) ($group['specialty_name'] ?? ''),
            'has_report' => $report !== null,
            'total_points' => $report !== null ? (int) $report['total_points'] : null,
            'updated_at' => $report['updated_at'] ?? '',
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        $aPoints = $a['has_report'] ? (int) $a['total_points'] : -1;
        $bPoints = $b['has_report'] ? (int) $b['total_points'] : -1;
        if ($aPoints !== $bPoints) {
            return $bPoints <=> $aPoints;
        }

        return strnatcasecmp((string) $a['group_number'], (string) $b['group_number']);
    });

    return $rows;
}

/**
 * Сводная матрица баллов: группы × месяцы учебного года.
 *
 * @param list<array{value: string, label: string}> $monthOptions
 * @return array{
 *   months: list<array{value: string, label: string, short: string}>,
 *   rows: list<array{
 *     group_id: int,
 *     group_number: string,
 *     curator_name: string,
 *     points_by_month: array<string, int|null>,
 *     total_points: int,
 *     submitted_months: int
 *   }>
 * }
 */
function build_curator_monthly_reports_matrix(string $academicYear, array $monthOptions = []): array
{
    ensure_curator_monthly_reports_schema();
    require_once __DIR__ . '/organization.php';
    require_once __DIR__ . '/attendance.php';

    if ($monthOptions === [] && $academicYear !== '') {
        $monthOptions = get_academic_year_months($academicYear);
    }

    $months = [];
    $monthValues = [];
    foreach ($monthOptions as $opt) {
        $value = (string) ($opt['value'] ?? '');
        if ($value === '') {
            continue;
        }
        $label = (string) ($opt['label'] ?? $value);
        $short = $label;
        if (preg_match('/^(\S+)/u', $label, $m)) {
            $short = $m[1];
        }
        $months[] = [
            'value' => $value,
            'label' => $label,
            'short' => $short,
        ];
        $monthValues[] = $value;
    }

    if ($monthValues === []) {
        return ['months' => [], 'rows' => []];
    }

    $placeholders = implode(',', array_fill(0, count($monthValues), '?'));
    $stmt = db()->prepare(
        "SELECT group_id, report_month, total_points
         FROM curator_monthly_reports
         WHERE report_month IN ($placeholders)"
    );
    $stmt->execute($monthValues);
    $pointsMap = [];
    foreach ($stmt->fetchAll() as $row) {
        $pointsMap[(int) $row['group_id']][(string) $row['report_month']] = (int) $row['total_points'];
    }

    $rows = [];
    foreach (get_all_groups() as $group) {
        $groupId = (int) $group['id'];
        $pointsByMonth = [];
        $total = 0;
        $submitted = 0;
        foreach ($monthValues as $monthValue) {
            if (isset($pointsMap[$groupId][$monthValue])) {
                $pts = (int) $pointsMap[$groupId][$monthValue];
                $pointsByMonth[$monthValue] = $pts;
                $total += $pts;
                $submitted++;
            } else {
                $pointsByMonth[$monthValue] = null;
            }
        }
        $rows[] = [
            'group_id' => $groupId,
            'group_number' => (string) ($group['number'] ?? ''),
            'curator_name' => (string) ($group['curator_name'] ?? ''),
            'points_by_month' => $pointsByMonth,
            'total_points' => $total,
            'submitted_months' => $submitted,
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        return strnatcasecmp((string) $a['group_number'], (string) $b['group_number']);
    });

    return [
        'months' => $months,
        'rows' => $rows,
    ];
}

/**
 * @param list<int> $studentIds
 * @param array<int, string> $nameMap
 * @return list<string>
 */
function curator_monthly_report_student_names(array $studentIds, array $nameMap): array
{
    $names = [];
    foreach ($studentIds as $id) {
        $id = (int) $id;
        if ($id > 0 && isset($nameMap[$id]) && $nameMap[$id] !== '') {
            $names[] = $nameMap[$id];
        }
    }

    return $names;
}

/**
 * @return list<array{key: string, label: string, type: string, inverted?: bool, auto?: bool}>
 */
function curator_monthly_report_item_meta(): array
{
    return [
        [
            'key' => 'key_events',
            'label' => 'Количество студентов, принимавших участие в ключевых делах колледжа',
            'type' => 'count',
            'inverted' => false,
        ],
        [
            'key' => 'group_events',
            'label' => 'Групповые мероприятия, направленные на решение воспитательных задач',
            'type' => 'events',
            'inverted' => false,
        ],
        [
            'key' => 'clubs',
            'label' => 'Количество студентов, посещающих кружки и секции',
            'type' => 'auto',
            'auto' => true,
            'inverted' => false,
        ],
        [
            'key' => 'debts',
            'label' => 'Количество студентов, имеющих академическую задолженность',
            'type' => 'auto',
            'auto' => true,
            'inverted' => true,
        ],
        [
            'key' => 'unexcused',
            'label' => 'Количество студентов, имеющих пропуски занятий без уважительной причины',
            'type' => 'auto',
            'auto' => true,
            'inverted' => false,
        ],
        [
            'key' => 'sanctions',
            'label' => 'Количество студентов, имеющих взыскания',
            'type' => 'students',
            'inverted' => true,
        ],
        [
            'key' => 'bad_habits',
            'label' => 'Количество студентов, имеющих вредные привычки',
            'type' => 'students',
            'inverted' => true,
        ],
        [
            'key' => 'appearance',
            'label' => 'Количество студентов, не выполняющих требования к внешнему виду',
            'type' => 'students',
            'inverted' => true,
        ],
        [
            'key' => 'volunteers',
            'label' => 'Количество студентов, участвующих в волонтёрской и добровольческой деятельности',
            'type' => 'students',
            'inverted' => false,
        ],
        [
            'key' => 'cafeteria',
            'label' => 'Количество студентов, регулярно питающихся в столовой',
            'type' => 'students',
            'inverted' => false,
        ],
        [
            'key' => 'characteristics',
            'label' => 'Наличие своевременно подготовленных характеристик',
            'type' => 'characteristics',
            'inverted' => false,
        ],
    ];
}
