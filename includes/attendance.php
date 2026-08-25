<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/students.php';
require_once __DIR__ . '/curriculum.php';

function get_attendance_reasons(bool $activeOnly = true): array
{
    $sql = 'SELECT id, name, sort_order, is_active FROM attendance_reasons';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order, name';

    return db()->query($sql)->fetchAll();
}

function create_attendance_reason(string $name): array
{
    $name = trim($name);
    if ($name === '') {
        return ['success' => false, 'error' => 'Укажите название причины.'];
    }

    $stmt = db()->prepare('SELECT id FROM attendance_reasons WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Такая причина уже существует.'];
    }

    $sortOrder = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM attendance_reasons')->fetchColumn();
    $stmt = db()->prepare(
        'INSERT INTO attendance_reasons (name, sort_order, is_active) VALUES (?, ?, 1)'
    );
    $stmt->execute([$name, $sortOrder]);

    return ['success' => true];
}

function update_attendance_reason(int $id, string $name, bool $isActive): array
{
    $name = trim($name);
    if ($name === '') {
        return ['success' => false, 'error' => 'Укажите название причины.'];
    }

    $stmt = db()->prepare('SELECT id FROM attendance_reasons WHERE name = ? AND id <> ? LIMIT 1');
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Такая причина уже существует.'];
    }

    $stmt = db()->prepare(
        'UPDATE attendance_reasons SET name = ?, is_active = ? WHERE id = ?'
    );
    $stmt->execute([$name, $isActive ? 1 : 0, $id]);

    return ['success' => true];
}

function delete_attendance_reason(int $id): array
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM attendance_entries WHERE reason_id = ?'
    );
    $stmt->execute([$id]);
    if ((int) $stmt->fetchColumn() > 0) {
        $stmt = db()->prepare('UPDATE attendance_reasons SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);

        return ['success' => true, 'deactivated' => true];
    }

    $stmt = db()->prepare('DELETE FROM attendance_reasons WHERE id = ?');
    $stmt->execute([$id]);

    return ['success' => true, 'deactivated' => false];
}

function get_attendance_journal(int $groupId, string $academicYear, ?string $month = null): array
{
    $sql = 'SELECT id, attendance_date
            FROM attendance_days
            WHERE group_id = ? AND academic_year = ?';
    $params = [$groupId, $academicYear];

    if ($month !== null) {
        $sql .= ' AND DATE_FORMAT(attendance_date, "%Y-%m") = ?';
        $params[] = $month;
    }

    $sql .= ' ORDER BY attendance_date';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $days = $stmt->fetchAll();

    if ($days === []) {
        return ['days' => [], 'entries' => []];
    }

    $dayIds = array_map(static fn (array $day): int => (int) $day['id'], $days);
    $placeholders = implode(',', array_fill(0, count($dayIds), '?'));

    $stmt = db()->prepare(
        "SELECT ae.attendance_day_id, ae.student_id, ae.excused_lessons, ae.unexcused_lessons,
                ae.reason_id, ar.name AS reason_name
         FROM attendance_entries ae
         LEFT JOIN attendance_reasons ar ON ar.id = ae.reason_id
         WHERE ae.attendance_day_id IN ($placeholders)"
    );
    $stmt->execute($dayIds);

    $entries = [];
    foreach ($stmt->fetchAll() as $row) {
        $dayId = (int) $row['attendance_day_id'];
        $studentId = (int) $row['student_id'];
        $entries[$dayId][$studentId] = [
            'excused_lessons' => (int) $row['excused_lessons'],
            'unexcused_lessons' => (int) $row['unexcused_lessons'],
            'reason_id' => $row['reason_id'] !== null ? (int) $row['reason_id'] : null,
            'reason_name' => (string) ($row['reason_name'] ?? ''),
        ];
    }

    return ['days' => $days, 'entries' => $entries];
}

function build_attendance_month_totals(array $students, array $journal): array
{
    $totals = [];

    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $totals[$studentId] = [
            'excused_lessons' => 0,
            'unexcused_lessons' => 0,
        ];
    }

    foreach ($journal['entries'] as $dayEntries) {
        foreach ($dayEntries as $studentId => $entry) {
            if (!isset($totals[$studentId])) {
                continue;
            }

            $totals[$studentId]['excused_lessons'] += (int) $entry['excused_lessons'];
            $totals[$studentId]['unexcused_lessons'] += (int) $entry['unexcused_lessons'];
        }
    }

    return $totals;
}

function build_attendance_month_summary(array $students, array $monthTotals): array
{
    $excused = 0;
    $unexcused = 0;

    foreach ($monthTotals as $total) {
        $excused += (int) ($total['excused_lessons'] ?? 0);
        $unexcused += (int) ($total['unexcused_lessons'] ?? 0);
    }

    $all = $excused + $unexcused;
    $studentCount = count($students);

    return [
        'total' => $all,
        'excused' => $excused,
        'unexcused' => $unexcused,
        'per_student_total' => $studentCount > 0 ? round($all / $studentCount, 1) : 0.0,
        'per_student_excused' => $studentCount > 0 ? round($excused / $studentCount, 1) : 0.0,
        'per_student_unexcused' => $studentCount > 0 ? round($unexcused / $studentCount, 1) : 0.0,
        'student_count' => $studentCount,
    ];
}

function normalize_attendance_month(string $month): ?string
{
    $month = trim($month);
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        return null;
    }

    [$year, $mon] = array_map('intval', explode('-', $month));
    if ($mon < 1 || $mon > 12) {
        return null;
    }

    return sprintf('%04d-%02d', $year, $mon);
}

function get_academic_year_months(string $academicYear): array
{
    $academicYear = normalize_academic_year($academicYear);
    if ($academicYear === null) {
        return [];
    }

    [$startYear] = array_map('intval', explode('-', $academicYear));
    $months = [];

    for ($i = 0; $i < 12; $i++) {
        $monthIndex = 9 + $i;
        $year = $startYear;
        if ($monthIndex > 12) {
            $monthIndex -= 12;
            $year = $startYear + 1;
        }

        $value = sprintf('%04d-%02d', $year, $monthIndex);
        $months[] = [
            'value' => $value,
            'label' => format_attendance_month($value),
        ];
    }

    return $months;
}

function get_default_attendance_month(string $academicYear): string
{
    $months = get_academic_year_months($academicYear);
    $current = date('Y-m');

    foreach ($months as $month) {
        if ($month['value'] === $current) {
            return $current;
        }
    }

    return $months[0]['value'] ?? $current;
}

function resolve_attendance_month(string $academicYear, ?string $requested): string
{
    $months = get_academic_year_months($academicYear);
    $allowed = array_column($months, 'value');
    $normalized = $requested !== null ? normalize_attendance_month($requested) : null;

    if ($normalized !== null && in_array($normalized, $allowed, true)) {
        return $normalized;
    }

    return get_default_attendance_month($academicYear);
}

function format_attendance_month(string $month): string
{
    static $names = [
        1 => 'Январь',
        2 => 'Февраль',
        3 => 'Март',
        4 => 'Апрель',
        5 => 'Май',
        6 => 'Июнь',
        7 => 'Июль',
        8 => 'Август',
        9 => 'Сентябрь',
        10 => 'Октябрь',
        11 => 'Ноябрь',
        12 => 'Декабрь',
    ];

    $normalized = normalize_attendance_month($month);
    if ($normalized === null) {
        return $month;
    }

    [$year, $mon] = array_map('intval', explode('-', $normalized));

    return ($names[$mon] ?? $normalized) . ' ' . $year;
}

function attendance_month_date_bounds(string $month): array
{
    $normalized = normalize_attendance_month($month);
    if ($normalized === null) {
        return [date('Y-m-01'), date('Y-m-t')];
    }

    $start = $normalized . '-01';
    $end = date('Y-m-t', strtotime($start));

    return [$start, $end];
}

function get_attendance_day(int $dayId, int $groupId): ?array
{
    $stmt = db()->prepare(
        'SELECT id, group_id, attendance_date, academic_year
         FROM attendance_days
         WHERE id = ? AND group_id = ?
         LIMIT 1'
    );
    $stmt->execute([$dayId, $groupId]);
    $day = $stmt->fetch();

    return $day === false ? null : $day;
}

function save_attendance_day(int $groupId, string $date, string $academicYear, array $entries, ?int $dayId = null): array
{
    if (!can_manage_group($groupId)) {
        return ['success' => false, 'error' => 'Нет доступа к группе.'];
    }

    $date = trim($date);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
        return ['success' => false, 'error' => 'Некорректная дата.'];
    }

    $dateMonth = substr($date, 0, 7);
    $allowedMonths = array_column(get_academic_year_months($academicYear), 'value');
    if (!in_array($dateMonth, $allowedMonths, true)) {
        return ['success' => false, 'error' => 'Дата должна относиться к выбранному учебному году.'];
    }

    $students = get_students_by_group($groupId);
    $studentIds = array_map(static fn (array $student): int => (int) $student['id'], $students);
    $reasons = get_attendance_reasons(true);
    $reasonIds = array_map(static fn (array $reason): int => (int) $reason['id'], $reasons);

    $normalized = [];
    foreach ($entries as $studentId => $entry) {
        $studentId = (int) $studentId;
        if (!in_array($studentId, $studentIds, true)) {
            continue;
        }

        $excused = max(0, (int) ($entry['excused_lessons'] ?? 0));
        $unexcused = max(0, (int) ($entry['unexcused_lessons'] ?? 0));
        $reasonId = isset($entry['reason_id']) && $entry['reason_id'] !== ''
            ? (int) $entry['reason_id']
            : null;

        if ($reasonId !== null && !in_array($reasonId, $reasonIds, true)) {
            return ['success' => false, 'error' => 'Выбрана недопустимая причина пропуска.'];
        }

        if ($excused === 0 && $unexcused === 0) {
            continue;
        }

        if ($excused > 0 && $reasonId === null) {
            return ['success' => false, 'error' => 'Укажите причину для уважительных пропусков.'];
        }

        $normalized[$studentId] = [
            'excused_lessons' => $excused,
            'unexcused_lessons' => $unexcused,
            'reason_id' => $reasonId,
        ];
    }

    if ($normalized === []) {
        return ['success' => false, 'error' => 'Укажите пропуски хотя бы для одного студента.'];
    }

    $pdo = db();

    if ($dayId !== null) {
        $day = get_attendance_day($dayId, $groupId);
        if ($day === null) {
            return ['success' => false, 'error' => 'Запись не найдена.'];
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM attendance_days
             WHERE group_id = ? AND academic_year = ? AND attendance_date = ? AND id <> ?
             LIMIT 1'
        );
        $stmt->execute([$groupId, $academicYear, $date, $dayId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'На эту дату уже есть запись.'];
        }

        $pdo->prepare('UPDATE attendance_days SET attendance_date = ? WHERE id = ?')
            ->execute([$date, $dayId]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT id FROM attendance_days
             WHERE group_id = ? AND academic_year = ? AND attendance_date = ?
             LIMIT 1'
        );
        $stmt->execute([$groupId, $academicYear, $date]);
        $existing = $stmt->fetch();
        if ($existing) {
            return ['success' => false, 'error' => 'На эту дату уже есть запись. Используйте «Изменить».'];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO attendance_days (group_id, attendance_date, academic_year)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([$groupId, $date, $academicYear]);
        $dayId = (int) $pdo->lastInsertId();
    }

    $pdo->prepare('DELETE FROM attendance_entries WHERE attendance_day_id = ?')->execute([$dayId]);

    $insert = $pdo->prepare(
        'INSERT INTO attendance_entries
         (attendance_day_id, student_id, excused_lessons, unexcused_lessons, reason_id)
         VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($normalized as $studentId => $entry) {
        $insert->execute([
            $dayId,
            $studentId,
            $entry['excused_lessons'],
            $entry['unexcused_lessons'],
            $entry['reason_id'],
        ]);
    }

    return ['success' => true, 'day_id' => $dayId, 'month' => $dateMonth];
}

function delete_attendance_day(int $dayId, int $groupId): array
{
    if (!can_manage_group($groupId)) {
        return ['success' => false, 'error' => 'Нет доступа к группе.'];
    }

    $day = get_attendance_day($dayId, $groupId);
    if ($day === null) {
        return ['success' => false, 'error' => 'Запись не найдена.'];
    }

    db()->prepare('DELETE FROM attendance_days WHERE id = ?')->execute([$dayId]);

    return ['success' => true];
}

function format_attendance_date(string $date): string
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    return date('d.m.Y', $timestamp);
}

function render_attendance_reason_options(array $reasons, ?int $selectedId = null): string
{
    $html = '<option value="">—</option>';
    foreach ($reasons as $reason) {
        $id = (int) $reason['id'];
        $selected = $id === $selectedId ? ' selected' : '';
        $html .= '<option value="' . $id . '"' . $selected . '>' . e($reason['name']) . '</option>';
    }

    return $html;
}

function get_attendance_semester_bounds(string $academicYear, string $semester): ?array
{
    $academicYear = normalize_academic_year($academicYear);
    if ($academicYear === null) {
        return null;
    }

    [$year1, $year2] = array_map('intval', explode('-', $academicYear));
    $semester = in_array($semester, ['1', '2'], true) ? $semester : '1';

    if ($semester === '1') {
        return [sprintf('%d-09-01', $year1), sprintf('%d-01-31', $year2)];
    }

    return [sprintf('%d-02-01', $year2), sprintf('%d-06-30', $year2)];
}

function fetch_group_attendance_student_totals(
    int $groupId,
    string $academicYear,
    ?string $month = null,
    ?string $semester = null
): array {
    $sql = 'SELECT ae.student_id,
                   SUM(ae.excused_lessons) AS excused_lessons,
                   SUM(ae.unexcused_lessons) AS unexcused_lessons
            FROM attendance_entries ae
            INNER JOIN attendance_days ad ON ad.id = ae.attendance_day_id
            WHERE ad.group_id = ? AND ad.academic_year = ?';
    $params = [$groupId, $academicYear];

    if ($month !== null) {
        $normalized = normalize_attendance_month($month);
        if ($normalized === null) {
            return [];
        }
        $sql .= ' AND DATE_FORMAT(ad.attendance_date, "%Y-%m") = ?';
        $params[] = $normalized;
    } elseif ($semester !== null) {
        $bounds = get_attendance_semester_bounds($academicYear, $semester);
        if ($bounds === null) {
            return [];
        }
        $sql .= ' AND ad.attendance_date BETWEEN ? AND ?';
        $params[] = $bounds[0];
        $params[] = $bounds[1];
    }

    $sql .= ' GROUP BY ae.student_id';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int) $row['student_id']] = [
            'excused_lessons' => (int) $row['excused_lessons'],
            'unexcused_lessons' => (int) $row['unexcused_lessons'],
        ];
    }

    return $result;
}

function build_educator_group_attendance_row(
    int $groupId,
    string $groupNumber,
    array $students,
    string $academicYear,
    ?string $month = null,
    ?string $semester = null
): array {
    $totalsByStudent = fetch_group_attendance_student_totals($groupId, $academicYear, $month, $semester);
    $monthTotals = [];

    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $monthTotals[$studentId] = $totalsByStudent[$studentId] ?? [
            'excused_lessons' => 0,
            'unexcused_lessons' => 0,
        ];
    }

    $unexcusedStudents = [];
    foreach ($students as $student) {
        $studentId = (int) $student['id'];
        $unexcused = (int) ($monthTotals[$studentId]['unexcused_lessons'] ?? 0);
        if ($unexcused <= 0) {
            continue;
        }

        $unexcusedStudents[] = [
            'full_name' => person_last_first_name((string) $student['full_name']),
            'count' => $unexcused,
        ];
    }

    return [
        'group_id' => $groupId,
        'group_number' => $groupNumber,
        'summary' => build_attendance_month_summary($students, $monthTotals),
        'unexcused_students' => $unexcusedStudents,
    ];
}

function build_educator_attendance_report(
    string $academicYear,
    ?string $month = null,
    ?string $semester = null
): array {
    require_once __DIR__ . '/organization.php';

    $rows = [];
    foreach (get_all_groups() as $group) {
        $groupId = (int) $group['id'];
        $students = get_students_by_group($groupId);
        $rows[] = build_educator_group_attendance_row(
            $groupId,
            (string) $group['number'],
            $students,
            $academicYear,
            $month,
            $semester
        );
    }

    return $rows;
}

function format_educator_unexcused_students_list(array $students): string
{
    if ($students === []) {
        return '—';
    }

    $parts = [];
    foreach ($students as $student) {
        $parts[] = (string) $student['full_name'] . ' (' . (int) $student['count'] . ')';
    }

    return implode(', ', $parts);
}

function build_attendance_year_chart_data(string $academicYear): array
{
    $months = get_academic_year_months($academicYear);
    $data = [];

    foreach ($months as $month) {
        $stmt = db()->prepare(
            'SELECT COALESCE(SUM(ae.excused_lessons), 0) AS excused,
                    COALESCE(SUM(ae.unexcused_lessons), 0) AS unexcused
             FROM attendance_entries ae
             INNER JOIN attendance_days ad ON ad.id = ae.attendance_day_id
             WHERE ad.academic_year = ? AND DATE_FORMAT(ad.attendance_date, "%Y-%m") = ?'
        );
        $stmt->execute([$academicYear, $month['value']]);
        $row = $stmt->fetch() ?: ['excused' => 0, 'unexcused' => 0];
        $excused = (int) $row['excused'];
        $unexcused = (int) $row['unexcused'];

        $data[] = [
            'value' => $month['value'],
            'label' => $month['label'],
            'total' => $excused + $unexcused,
            'excused' => $excused,
            'unexcused' => $unexcused,
        ];
    }

    return $data;
}

function get_college_students_count(): int
{
    require_once __DIR__ . '/organization.php';

    $count = 0;
    foreach (get_all_groups() as $group) {
        $count += count(get_students_by_group((int) $group['id']));
    }

    return $count;
}

function build_attendance_year_per_student_chart_data(string $academicYear, ?int $studentCount = null): array
{
    $studentCount = $studentCount ?? get_college_students_count();
    $data = [];

    foreach (build_attendance_year_chart_data($academicYear) as $row) {
        $excused = (int) $row['excused'];
        $unexcused = (int) $row['unexcused'];
        $total = (int) $row['total'];

        $data[] = array_merge($row, [
            'per_student_total' => $studentCount > 0 ? round($total / $studentCount, 1) : 0.0,
            'per_student_excused' => $studentCount > 0 ? round($excused / $studentCount, 1) : 0.0,
            'per_student_unexcused' => $studentCount > 0 ? round($unexcused / $studentCount, 1) : 0.0,
        ]);
    }

    return $data;
}

function build_attendance_three_years_comparison_data(string $baseYear, string $metric = 'total'): array
{
    $fieldMap = [
        'total' => 'per_student_total',
        'excused' => 'per_student_excused',
        'unexcused' => 'per_student_unexcused',
    ];

    if (!isset($fieldMap[$metric])) {
        $metric = 'total';
    }

    $field = $fieldMap[$metric];
    $years = get_last_academic_years($baseYear, 3);
    $labels = get_academic_year_short_month_labels($baseYear);
    $studentCount = get_college_students_count();
    $series = [];

    foreach ($years as $year) {
        $monthData = build_attendance_year_per_student_chart_data($year, $studentCount);
        $values = array_map(static fn (array $row): float => (float) $row[$field], $monthData);
        $yearRaw = build_attendance_year_chart_data($year);
        $yearExcused = (int) array_sum(array_column($yearRaw, 'excused'));
        $yearUnexcused = (int) array_sum(array_column($yearRaw, 'unexcused'));
        $yearTotal = $yearExcused + $yearUnexcused;

        if ($metric === 'excused') {
            $yearValue = $studentCount > 0 ? round($yearExcused / $studentCount, 1) : 0.0;
        } elseif ($metric === 'unexcused') {
            $yearValue = $studentCount > 0 ? round($yearUnexcused / $studentCount, 1) : 0.0;
        } else {
            $yearValue = $studentCount > 0 ? round($yearTotal / $studentCount, 1) : 0.0;
        }

        $series[] = [
            'year' => $year,
            'values' => $values,
            'year_value' => $yearValue,
        ];
    }

    return [
        'labels' => $labels,
        'series' => $series,
        'metric' => $metric,
    ];
}

function build_attendance_three_years_comparison_set(string $baseYear): array
{
    return [
        'total' => build_attendance_three_years_comparison_data($baseYear, 'total'),
        'excused' => build_attendance_three_years_comparison_data($baseYear, 'excused'),
        'unexcused' => build_attendance_three_years_comparison_data($baseYear, 'unexcused'),
    ];
}

function shift_academic_year(string $academicYear, int $offset): ?string
{
    $academicYear = normalize_academic_year($academicYear);
    if ($academicYear === null) {
        return null;
    }

    [$year1, $year2] = array_map('intval', explode('-', $academicYear));

    return sprintf('%d-%d', $year1 + $offset, $year2 + $offset);
}

function get_last_academic_years(string $baseYear, int $count = 3): array
{
    $baseYear = normalize_academic_year($baseYear) ?? get_default_academic_year();
    $years = [];

    for ($i = $count - 1; $i >= 0; $i--) {
        $year = shift_academic_year($baseYear, -$i);
        if ($year !== null) {
            $years[] = $year;
        }
    }

    return $years;
}

function get_academic_year_short_month_labels(string $academicYear): array
{
    static $shortNames = [
        1 => 'Янв',
        2 => 'Фев',
        3 => 'Мар',
        4 => 'Апр',
        5 => 'Май',
        6 => 'Июн',
        7 => 'Июл',
        8 => 'Авг',
        9 => 'Сен',
        10 => 'Окт',
        11 => 'Ноя',
        12 => 'Дек',
    ];

    $labels = [];
    foreach (get_academic_year_months($academicYear) as $month) {
        [, $mon] = array_map('intval', explode('-', (string) $month['value']));
        $labels[] = $shortNames[$mon] ?? (string) $month['label'];
    }

    return $labels;
}

function fetch_attendance_unexcused_total(string $academicYear, ?string $semester = null): int
{
    $sql = 'SELECT COALESCE(SUM(ae.unexcused_lessons), 0)
            FROM attendance_entries ae
            INNER JOIN attendance_days ad ON ad.id = ae.attendance_day_id
            WHERE ad.academic_year = ?';
    $params = [$academicYear];

    if ($semester !== null) {
        $bounds = get_attendance_semester_bounds($academicYear, $semester);
        if ($bounds === null) {
            return 0;
        }
        $sql .= ' AND ad.attendance_date BETWEEN ? AND ?';
        $params[] = $bounds[0];
        $params[] = $bounds[1];
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function fetch_attendance_reason_stats(string $academicYear, ?string $semester = null): array
{
    $sql = 'SELECT COALESCE(ar.id, 0) AS reason_id,
                   COALESCE(NULLIF(ar.name, \'\'), \'Без указанной причины\') AS reason_name,
                   SUM(ae.excused_lessons) AS lessons,
                   COUNT(DISTINCT ae.student_id) AS students
            FROM attendance_entries ae
            INNER JOIN attendance_days ad ON ad.id = ae.attendance_day_id
            LEFT JOIN attendance_reasons ar ON ar.id = ae.reason_id
            WHERE ad.academic_year = ? AND ae.excused_lessons > 0';
    $params = [$academicYear];

    if ($semester !== null) {
        $bounds = get_attendance_semester_bounds($academicYear, $semester);
        if ($bounds === null) {
            return [];
        }
        $sql .= ' AND ad.attendance_date BETWEEN ? AND ?';
        $params[] = $bounds[0];
        $params[] = $bounds[1];
    }

    $sql .= ' GROUP BY ar.id, ar.name ORDER BY lessons DESC, reason_name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[] = [
            'reason_id' => (int) $row['reason_id'],
            'reason_name' => (string) $row['reason_name'],
            'lessons' => (int) $row['lessons'],
            'students' => (int) $row['students'],
        ];
    }

    return $rows;
}

function attendance_reason_top(array $reasons): ?array
{
    return $reasons[0] ?? null;
}

function build_attendance_reason_insights(
    string $academicYear,
    array $reasons,
    int $excused,
    int $unexcused,
    array $sem1Reasons,
    array $sem2Reasons,
    array $yearReport
): array {
    $total = $excused + $unexcused;
    $sections = [];

    if ($total === 0) {
        return [
            [
                'title' => 'Недостаточно данных',
                'text' => 'За выбранный учебный год пропуски не зафиксированы. Анализ причин будет доступен после заполнения журнала посещаемости кураторами.',
            ],
        ];
    }

    $excusedShare = round($excused / $total * 100, 1);
    $unexcusedShare = round($unexcused / $total * 100, 1);

    $sections[] = [
        'title' => 'Общая картина',
        'text' => sprintf(
            'За учебный год %s в системе учтено %d пропусков занятий: %d уважительных (%.1f%%) и %d неуважительных (%.1f%%). ',
            $academicYear,
            $total,
            $excused,
            $excusedShare,
            $unexcused,
            $unexcusedShare
        ) . ($unexcusedShare > 25
            ? 'Доля неуважительных пропусков заметна — имеет смысл усилить профилактическую работу с группами.'
            : 'Большая часть пропусков оформлена как уважительная, что говорит о дисциплине документирования.'),
    ];

    if ($excused > 0 && $reasons !== []) {
        $top = attendance_reason_top($reasons);
        $reasonCount = count($reasons);
        $topShare = $top ? round($top['lessons'] / $excused * 100, 1) : 0.0;

        $reasonLines = [];
        foreach (array_slice($reasons, 0, 5) as $index => $reason) {
            $share = round($reason['lessons'] / $excused * 100, 1);
            $reasonLines[] = sprintf(
                '%d) «%s» — %d занятий (%.1f%% уважительных, %d студентов)',
                $index + 1,
                $reason['reason_name'],
                $reason['lessons'],
                $share,
                $reason['students']
            );
        }

        $sections[] = [
            'title' => 'Структура уважительных пропусков',
            'text' => sprintf(
                'Уважительные пропуски распределены по %d %s. Лидирует причина «%s»: %d занятий (%.1f%% всех уважительных). ',
                $reasonCount,
                $reasonCount === 1 ? 'причине' : 'причинам',
                $top['reason_name'] ?? '—',
                $top['lessons'] ?? 0,
                $topShare
            ) . 'Рейтинг причин:' . "\n" . implode("\n", $reasonLines),
        ];
    }

    $topSem1 = attendance_reason_top($sem1Reasons);
    $topSem2 = attendance_reason_top($sem2Reasons);
    if ($topSem1 || $topSem2) {
        $sem1Text = $topSem1
            ? sprintf('«%s» (%d зан.)', $topSem1['reason_name'], $topSem1['lessons'])
            : 'данные отсутствуют';
        $sem2Text = $topSem2
            ? sprintf('«%s» (%d зан.)', $topSem2['reason_name'], $topSem2['lessons'])
            : 'данные отсутствуют';

        $semCompare = 'Структура причин в семестрах сопоставима.';
        if ($topSem1 && $topSem2 && $topSem1['reason_name'] !== $topSem2['reason_name']) {
            $semCompare = 'Во втором семестре профиль причин смещается относительно первого — это может быть связано с сезонностью (болезни, мероприятия) или изменением состава групп.';
        } elseif ($topSem1 && $topSem2 && $topSem1['reason_name'] === $topSem2['reason_name']) {
            $semCompare = sprintf(
                'Доминирующая причина «%s» сохраняется в обоих семестрах — тенденция устойчива в течение года.',
                $topSem1['reason_name']
            );
        }

        $sections[] = [
            'title' => 'Семестры',
            'text' => sprintf(
                "В 1 семестре чаще всего указывают %s.\nВо 2 семестре — %s.\n%s",
                $sem1Text,
                $sem2Text,
                $semCompare
            ),
        ];
    }

    $riskGroups = [];
    foreach ($yearReport as $row) {
        $summary = $row['summary'];
        if ((int) ($summary['unexcused'] ?? 0) <= 0) {
            continue;
        }
        $share = (int) $summary['total'] > 0
            ? round((int) $summary['unexcused'] / (int) $summary['total'] * 100, 1)
            : 0.0;
        $riskGroups[] = [
            'number' => (string) $row['group_number'],
            'unexcused' => (int) $summary['unexcused'],
            'share' => $share,
        ];
    }
    usort($riskGroups, static fn (array $a, array $b): int => $b['unexcused'] <=> $a['unexcused']);
    $riskGroups = array_slice($riskGroups, 0, 3);

    if ($riskGroups !== []) {
        $groupLines = array_map(
            static fn (array $group): string => sprintf(
                'группа %s — %d неуважит. (%.1f%% от пропусков группы)',
                $group['number'],
                $group['unexcused'],
                $group['share']
            ),
            $riskGroups
        );
        $sections[] = [
            'title' => 'Группы, требующие внимания',
            'text' => "Наибольшая концентрация неуважительных пропусков:\n" . implode("\n", $groupLines)
                . ".\nРекомендуется провести беседы со студентами и согласовать меры с кураторами.",
        ];
    }

    $topReason = attendance_reason_top($reasons);

    $sections[] = [
        'title' => 'Выводы и рекомендации',
        'text' => build_attendance_reason_recommendations($reasons, $excused, $unexcused, $topReason),
    ];

    return $sections;
}

function build_attendance_reason_recommendations(
    array $reasons,
    int $excused,
    int $unexcused,
    ?array $topReason
): string {
    $parts = [];

    if ($topReason) {
        $name = mb_strtolower((string) $topReason['reason_name']);
        if (str_contains($name, 'болезн')) {
            $parts[] = 'Преобладание пропусков по болезни — типичная картина; при резком росте стоит напомнить о необходимости медицинских справок.';
        } elseif (str_contains($name, 'приказ') || str_contains($name, 'заявлен')) {
            $parts[] = 'Значимая доля организационных причин (приказы, заявления) — контролируйте своевременность оформления документов кураторами.';
        } else {
            $parts[] = sprintf('Основной поток уважительных пропусков связан с причиной «%s» — учитывайте это при планировании воспитательной работы.', $topReason['reason_name']);
        }
    }

    if ($unexcused > 0 && $excused + $unexcused > 0) {
        $ratio = round($unexcused / ($excused + $unexcused) * 100, 1);
        if ($ratio >= 30) {
            $parts[] = 'Высокая доля неуважительных пропусков: целесообразен точечный разбор с группами и индивидуальная работа со студентами из списков неуважительных.';
        } else {
            $parts[] = 'Неуважительные пропуски присутствуют, но не доминируют — поддерживайте текущий уровень контроля посещаемости.';
        }
    }

    if (count($reasons) > 3) {
        $parts[] = 'Используется много разных причин — проверьте единообразие заполнения журнала кураторами.';
    }

    if ($parts === []) {
        return 'Продолжайте мониторинг посещаемости; при накоплении данных анализ станет точнее.';
    }

    return implode(' ', $parts);
}

function build_attendance_reason_analysis(string $academicYear, array $yearReport): array
{
    $reasons = fetch_attendance_reason_stats($academicYear);
    $excused = (int) array_sum(array_column($reasons, 'lessons'));
    $unexcused = fetch_attendance_unexcused_total($academicYear);
    $total = $excused + $unexcused;

    $enriched = [];
    foreach ($reasons as $reason) {
        $enriched[] = $reason + [
            'share_excused' => $excused > 0 ? round($reason['lessons'] / $excused * 100, 1) : 0.0,
            'share_total' => $total > 0 ? round($reason['lessons'] / $total * 100, 1) : 0.0,
        ];
    }

    return [
        'reasons' => $enriched,
        'excused' => $excused,
        'unexcused' => $unexcused,
        'total' => $total,
        'insights' => build_attendance_reason_insights(
            $academicYear,
            $enriched,
            $excused,
            $unexcused,
            fetch_attendance_reason_stats($academicYear, '1'),
            fetch_attendance_reason_stats($academicYear, '2'),
            $yearReport
        ),
    ];
}
