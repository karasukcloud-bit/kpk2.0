<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/journal.php';
require_once __DIR__ . '/grading.php';
require_once __DIR__ . '/attendance.php';
require_once __DIR__ . '/record_book.php';
require_once __DIR__ . '/gradebook.php';
require_once __DIR__ . '/curriculum.php';

/**
 * @return array{
 *   period: array,
 *   overview: array,
 *   subjects: list<array>,
 *   attendance: array,
 *   journal_activity: array,
 *   recommendations: list<array>,
 *   narratives: array
 * }
 */
function build_student_analytics(array $student): array
{
    $studentId = (int) $student['id'];
    $groupId = (int) $student['group_id'];
    $period = get_active_gradebook_period();
    $year = (string) $period['academic_year'];
    $semester = (string) $period['semester'];
    $gradingConfig = get_grading_config();

    $subjectsRaw = get_student_journal_subjects($groupId, $year, $semester);
    $subjects = [];
    $allMarks = [];
    $lessonsVisited = 0;
    $lessonsAbsent = 0;
    $activityCount = 0;
    $lateCount = 0;
    $marksCount = 0;
    $twos = 0;
    $fives = 0;

    foreach ($subjectsRaw as $subject) {
        $itemId = (int) $subject['curriculum_item_id'];
        $lessons = get_journal_lessons($itemId);
        $grades = get_journal_grades_for_student($itemId, $studentId);
        $totals = build_journal_totals(
            [['id' => $studentId]],
            $lessons,
            [$studentId => $grades],
            $gradingConfig
        );
        $total = $totals[$studentId] ?? ['display' => '', 'average' => null];

        $subjectMarks = [];
        $subjectAbsent = 0;
        $subjectActivity = 0;
        $subjectLate = 0;
        $subjectVisited = 0;

        foreach ($lessons as $lesson) {
            $lessonId = (int) $lesson['id'];
            $entry = $grades[$lessonId] ?? null;
            if ($entry === null) {
                continue;
            }
            $mark = (string) ($entry['mark'] ?? '');
            if ($mark === '') {
                if (!empty($entry['activity']) || !empty($entry['late'])) {
                    $subjectVisited++;
                    $lessonsVisited++;
                    if (!empty($entry['activity'])) {
                        $subjectActivity++;
                        $activityCount++;
                    }
                    if (!empty($entry['late'])) {
                        $subjectLate++;
                        $lateCount++;
                    }
                }
                continue;
            }

            if ($mark === 'Н') {
                $subjectAbsent++;
                $lessonsAbsent++;
                continue;
            }

            $subjectVisited++;
            $lessonsVisited++;
            if (in_array($mark, ['2', '3', '4', '5'], true)) {
                $value = (int) $mark;
                $subjectMarks[] = $value;
                $allMarks[] = $value;
                $marksCount++;
                if ($value === 2) {
                    $twos++;
                }
                if ($value === 5) {
                    $fives++;
                }
            }
            if (!empty($entry['activity'])) {
                $subjectActivity++;
                $activityCount++;
            }
            if (!empty($entry['late'])) {
                $subjectLate++;
                $lateCount++;
            }
        }

        $avg = $subjectMarks !== []
            ? round(array_sum($subjectMarks) / count($subjectMarks), 2)
            : null;
        $status = student_analytics_subject_status($avg, $subjectAbsent, count($lessons), $subjectLate);

        $subjects[] = [
            'curriculum_item_id' => $itemId,
            'subject_name' => (string) $subject['subject_name'],
            'teacher_name' => (string) ($subject['teacher_name'] ?? ''),
            'lessons_total' => count($lessons),
            'marks_count' => count($subjectMarks),
            'average' => $avg,
            'final_display' => (string) ($total['display'] ?? ''),
            'absent' => $subjectAbsent,
            'activity' => $subjectActivity,
            'late' => $subjectLate,
            'visited' => $subjectVisited,
            'status' => $status,
            'status_label' => student_analytics_status_label($status),
            'comment' => student_analytics_subject_comment(
                (string) $subject['subject_name'],
                $avg,
                $subjectAbsent,
                $subjectActivity,
                $subjectLate,
                count($lessons)
            ),
        ];
    }

    usort($subjects, static function (array $a, array $b): int {
        $order = ['risk' => 0, 'attention' => 1, 'stable' => 2, 'strong' => 3, 'empty' => 4];
        $oa = $order[$a['status']] ?? 9;
        $ob = $order[$b['status']] ?? 9;
        if ($oa !== $ob) {
            return $oa <=> $ob;
        }
        $aa = $a['average'] ?? 99;
        $ba = $b['average'] ?? 99;

        return $aa <=> $ba;
    });

    $overviewAvg = $allMarks !== [] ? round(array_sum($allMarks) / count($allMarks), 2) : null;
    $attendance = student_analytics_attendance($studentId, $groupId, $year);
    $recordBook = get_student_record_book($studentId);
    $rbSummary = student_analytics_record_book_summary($recordBook);

    $lessonsRecorded = $lessonsVisited + $lessonsAbsent;
    $activityRate = $lessonsVisited > 0 ? round($activityCount / $lessonsVisited * 100, 1) : null;
    $lateRate = $lessonsVisited > 0 ? round($lateCount / $lessonsVisited * 100, 1) : null;
    $attendanceRate = $lessonsRecorded > 0
        ? round($lessonsVisited / $lessonsRecorded * 100, 1)
        : null;

    $overview = [
        'average' => $overviewAvg,
        'marks_count' => $marksCount,
        'subjects_count' => count($subjects),
        'subjects_with_marks' => count(array_filter($subjects, static fn (array $s): bool => $s['average'] !== null)),
        'fives' => $fives,
        'twos' => $twos,
        'level' => student_analytics_performance_level($overviewAvg, $twos),
        'level_label' => student_analytics_performance_level_label(
            student_analytics_performance_level($overviewAvg, $twos)
        ),
        'record_book_average' => $rbSummary['average'],
        'record_book_graded' => $rbSummary['graded'],
        'record_book_periods' => $rbSummary['periods'],
    ];

    $journalActivity = [
        'lessons_visited' => $lessonsVisited,
        'lessons_absent' => $lessonsAbsent,
        'lessons_recorded' => $lessonsRecorded,
        'activity_count' => $activityCount,
        'late_count' => $lateCount,
        'activity_rate' => $activityRate,
        'late_rate' => $lateRate,
        'attendance_rate' => $attendanceRate,
    ];

    $recommendations = student_analytics_recommendations($overview, $subjects, $attendance, $journalActivity);
    $narratives = student_analytics_narratives(
        (string) $student['full_name'],
        $year,
        $semester,
        $overview,
        $subjects,
        $attendance,
        $journalActivity,
        $recommendations
    );

    return [
        'period' => [
            'academic_year' => $year,
            'semester' => $semester,
        ],
        'overview' => $overview,
        'subjects' => $subjects,
        'attendance' => $attendance,
        'journal_activity' => $journalActivity,
        'recommendations' => $recommendations,
        'narratives' => $narratives,
    ];
}

function student_analytics_performance_level(?float $average, int $twos): string
{
    if ($average === null) {
        return 'insufficient';
    }
    if ($twos > 0 || $average < 3.0) {
        return 'risk';
    }
    if ($average >= 4.6) {
        return 'excellent';
    }
    if ($average >= 4.0) {
        return 'good';
    }
    if ($average >= 3.5) {
        return 'satisfactory';
    }

    return 'weak';
}

function student_analytics_performance_level_label(string $level): string
{
    return match ($level) {
        'excellent' => 'Высокий уровень',
        'good' => 'Хороший уровень',
        'satisfactory' => 'Устойчивый средний уровень',
        'weak' => 'Требует усиления',
        'risk' => 'Зона академического риска',
        default => 'Недостаточно данных',
    };
}

function student_analytics_subject_status(
    ?float $average,
    int $absent,
    int $lessonsTotal,
    int $late
): string {
    if ($lessonsTotal === 0 && $average === null) {
        return 'empty';
    }
    if (($average !== null && $average < 3.0) || $absent >= 3) {
        return 'risk';
    }
    if (($average !== null && $average < 3.7) || $absent >= 2 || $late >= 3) {
        return 'attention';
    }
    if ($average !== null && $average >= 4.5 && $absent === 0) {
        return 'strong';
    }
    if ($average !== null || $lessonsTotal > 0) {
        return 'stable';
    }

    return 'empty';
}

function student_analytics_status_label(string $status): string
{
    return match ($status) {
        'strong' => 'Сильная сторона',
        'stable' => 'Стабильно',
        'attention' => 'Требует внимания',
        'risk' => 'Риск',
        default => 'Пока без данных',
    };
}

function student_analytics_subject_comment(
    string $subjectName,
    ?float $average,
    int $absent,
    int $activity,
    int $late,
    int $lessonsTotal
): string {
    if ($lessonsTotal === 0) {
        return 'По дисциплине «' . $subjectName . '» занятия в журнале ещё не отражены — '
            . 'оценить динамику пока рано.';
    }

    if ($average === null && $absent === 0 && $activity === 0 && $late === 0) {
        return 'По дисциплине «' . $subjectName . '» отметки пока единичны или отсутствуют. '
            . 'Имеет смысл регулярно готовиться к занятиям и активно включаться в работу на уроке.';
    }

    $parts = [];
    if ($average !== null) {
        if ($average >= 4.5) {
            $parts[] = 'результаты по «' . $subjectName . '» выглядят уверенно (средний балл '
                . student_analytics_fmt($average) . ')';
        } elseif ($average >= 3.7) {
            $parts[] = 'по «' . $subjectName . '» сохраняется приемлемый уровень (средний балл '
                . student_analytics_fmt($average) . '), но запас прочности невелик';
        } elseif ($average >= 3.0) {
            $parts[] = 'по «' . $subjectName . '» средний балл '
                . student_analytics_fmt($average)
                . ' — материал усваивается неравномерно, нужна системная работа';
        } else {
            $parts[] = 'по «' . $subjectName . '» средний балл '
                . student_analytics_fmt($average)
                . ' сигнализирует о серьёзных пробелах';
        }
    }

    if ($absent > 0) {
        $parts[] = 'отмечено пропусков по журналу: ' . $absent
            . student_analytics_plural($absent, ' занятие', ' занятия', ' занятий');
    }
    if ($late > 0) {
        $parts[] = 'опозданий: ' . $late;
    }
    if ($activity > 0) {
        $parts[] = 'отметок активности: ' . $activity;
    } elseif ($average !== null) {
        $parts[] = 'активность на занятиях фиксируется редко — стоит чаще проявлять инициативу';
    }

    $text = implode('; ', $parts);
    if ($text === '') {
        return 'По дисциплине «' . $subjectName . '» данные ещё формируются.';
    }

    return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1) . '.';
}

function student_analytics_attendance(int $studentId, int $groupId, string $academicYear): array
{
    if (!db()->query("SHOW TABLES LIKE 'attendance_entries'")->fetch()) {
        return [
            'excused' => 0,
            'unexcused' => 0,
            'total' => 0,
            'by_reason' => [],
            'days_with_absence' => 0,
        ];
    }

    $stmt = db()->prepare(
        'SELECT COALESCE(SUM(ae.excused_lessons), 0) AS excused,
                COALESCE(SUM(ae.unexcused_lessons), 0) AS unexcused,
                COUNT(*) AS days_count
         FROM attendance_entries ae
         INNER JOIN attendance_days ad ON ad.id = ae.attendance_day_id
         WHERE ae.student_id = ? AND ad.group_id = ? AND ad.academic_year = ?
           AND (ae.excused_lessons > 0 OR ae.unexcused_lessons > 0)'
    );
    $stmt->execute([$studentId, $groupId, $academicYear]);
    $row = $stmt->fetch() ?: ['excused' => 0, 'unexcused' => 0, 'days_count' => 0];

    $reasonsStmt = db()->prepare(
        'SELECT COALESCE(ar.name, \'Причина не указана\') AS reason_name,
                SUM(ae.excused_lessons) AS excused,
                SUM(ae.unexcused_lessons) AS unexcused,
                SUM(ae.excused_lessons + ae.unexcused_lessons) AS total
         FROM attendance_entries ae
         INNER JOIN attendance_days ad ON ad.id = ae.attendance_day_id
         LEFT JOIN attendance_reasons ar ON ar.id = ae.reason_id
         WHERE ae.student_id = ? AND ad.group_id = ? AND ad.academic_year = ?
           AND (ae.excused_lessons > 0 OR ae.unexcused_lessons > 0)
         GROUP BY reason_name
         ORDER BY total DESC, reason_name ASC'
    );
    $reasonsStmt->execute([$studentId, $groupId, $academicYear]);
    $byReason = [];
    foreach ($reasonsStmt->fetchAll() as $reasonRow) {
        $byReason[] = [
            'reason_name' => (string) $reasonRow['reason_name'],
            'excused' => (int) $reasonRow['excused'],
            'unexcused' => (int) $reasonRow['unexcused'],
            'total' => (int) $reasonRow['total'],
        ];
    }

    $excused = (int) $row['excused'];
    $unexcused = (int) $row['unexcused'];

    return [
        'excused' => $excused,
        'unexcused' => $unexcused,
        'total' => $excused + $unexcused,
        'by_reason' => $byReason,
        'days_with_absence' => (int) $row['days_count'],
    ];
}

function student_analytics_record_book_summary(array $periods): array
{
    $grades = [];
    foreach ($periods as $period) {
        foreach ($period['entries'] ?? [] as $entry) {
            if ($entry['grade'] !== null && $entry['grade'] !== '') {
                $grades[] = (int) $entry['grade'];
            }
        }
    }

    $count = count($grades);

    return [
        'periods' => count($periods),
        'graded' => $count,
        'average' => $count > 0 ? round(array_sum($grades) / $count, 2) : null,
    ];
}

function student_analytics_recommendations(
    array $overview,
    array $subjects,
    array $attendance,
    array $journalActivity
): array {
    $items = [];

    $riskSubjects = array_values(array_filter(
        $subjects,
        static fn (array $s): bool => in_array($s['status'], ['risk', 'attention'], true)
    ));
    if ($riskSubjects !== []) {
        $names = array_map(static fn (array $s): string => '«' . $s['subject_name'] . '»', array_slice($riskSubjects, 0, 3));
        $items[] = [
            'icon' => 'book',
            'title' => 'Учебные приоритеты',
            'text' => 'Сосредоточьте внимание на дисциплинах '
                . implode(', ', $names)
                . '. Составьте короткий план повторения: 2–3 темы в неделю, '
                . 'консультация с преподавателем при устойчивых затруднениях.',
        ];
    }

    if ((int) ($overview['twos'] ?? 0) > 0) {
        $items[] = [
            'icon' => 'alert',
            'title' => 'Ликвидация неудовлетворительных отметок',
            'text' => 'В журнале есть оценки «2». Необходимо своевременно подойти к преподавателю, '
                . 'уточнить перечень тем к пересдаче и закрыть пробелы до завершения семестра.',
        ];
    }

    if ((int) ($attendance['unexcused'] ?? 0) > 0) {
        $items[] = [
            'icon' => 'calendar',
            'title' => 'Посещаемость',
            'text' => 'Зафиксированы пропуски без уважительной причины ('
                . (int) $attendance['unexcused']
                . ' ч.). Рекомендуется снизить число таких пропусков: '
                . 'они напрямую влияют на усвоение материала и итоговую аттестацию.',
        ];
    } elseif ((int) ($attendance['excused'] ?? 0) > 8) {
        $items[] = [
            'icon' => 'calendar',
            'title' => 'Восполнение пропущенного материала',
            'text' => 'Пропуски по уважительным причинам накоплены заметно. '
                . 'После возвращения к учёбе целесообразно запросить у преподавателей перечень тем '
                . 'и самостоятельно отработать материал.',
        ];
    }

    if ((int) ($journalActivity['late_count'] ?? 0) >= 3) {
        $items[] = [
            'icon' => 'clock',
            'title' => 'Дисциплина прихода на занятия',
            'text' => 'Опоздания отмечаются неоднократно. Планируйте маршрут и время выхода так, '
                . 'чтобы быть в аудитории к началу пары: первые минуты занятия часто задают логику темы.',
        ];
    }

    $activityRate = $journalActivity['activity_rate'] ?? null;
    if ($activityRate !== null && $activityRate < 20 && (int) ($journalActivity['lessons_visited'] ?? 0) >= 5) {
        $items[] = [
            'icon' => 'spark',
            'title' => 'Активность на занятиях',
            'text' => 'Доля отметок за активность невелика. Чаще отвечайте устно, участвуйте в разборе '
                . 'заданий и практических работах — это укрепляет понимание и улучшает текущие результаты.',
        ];
    }

    if ($items === [] && ($overview['average'] ?? null) !== null) {
        $items[] = [
            'icon' => 'check',
            'title' => 'Поддержание результата',
            'text' => 'Текущая картина в целом благоприятная. Сохраняйте ритм подготовки, '
                . 'не откладывайте выполнение практических заданий и следите за посещаемостью.',
        ];
    }

    if ($items === []) {
        $items[] = [
            'icon' => 'info',
            'title' => 'Накопление данных',
            'text' => 'Пока в журнале и журнале посещаемости недостаточно записей для развёрнутых рекомендаций. '
                . 'После появления отметок и сведений о посещении анализ станет точнее.',
        ];
    }

    return $items;
}

function student_analytics_narratives(
    string $fullName,
    string $year,
    string $semester,
    array $overview,
    array $subjects,
    array $attendance,
    array $journalActivity,
    array $recommendations
): array {
    $shortName = person_last_first_name($fullName);
    $avg = $overview['average'] ?? null;
    $level = (string) ($overview['level'] ?? 'insufficient');

    if ($avg === null) {
        $overviewText = 'На текущий момент по ' . semester_label($semester)
            . ' ' . $year . ' учебного года в журнале ещё недостаточно выставленных отметок, '
            . 'чтобы уверенно охарактеризовать общую успеваемость ' . $shortName . '. '
            . 'Рекомендуется регулярно посещать занятия и выполнять текущие задания — '
            . 'тогда картина успеваемости станет информативной уже в ближайшие недели.';
    } else {
        $overviewText = 'По итогам текущего периода (' . semester_label($semester)
            . ', ' . $year . ') средний балл текущих отметок составляет '
            . student_analytics_fmt((float) $avg) . '. '
            . 'Это соответствует оценке «' . student_analytics_performance_level_label($level) . '». ';
        if ((int) $overview['fives'] > 0) {
            $overviewText .= 'Отметок «отлично»: ' . (int) $overview['fives'] . '. ';
        }
        if ((int) $overview['twos'] > 0) {
            $overviewText .= 'Вместе с тем присутствуют неудовлетворительные результаты, '
                . 'которые требуют первоочередного внимания. ';
        } elseif ($avg >= 4.0) {
            $overviewText .= 'В целом учебная динамика производит положительное впечатление: '
                . 'материал осваивается уверенно, без выраженных провалов. ';
        } else {
            $overviewText .= 'Результаты свидетельствуют о необходимости более системной подготовки '
                . 'и своевременного обращения за разъяснениями к преподавателям. ';
        }
        if ($overview['record_book_average'] !== null) {
            $overviewText .= 'По данным электронной зачётной книжки средний балл итоговых оценок — '
                . student_analytics_fmt((float) $overview['record_book_average']) . '.';
        }
    }

    $strong = array_values(array_filter($subjects, static fn (array $s): bool => $s['status'] === 'strong'));
    $risk = array_values(array_filter($subjects, static fn (array $s): bool => $s['status'] === 'risk'));
    $attention = array_values(array_filter($subjects, static fn (array $s): bool => $s['status'] === 'attention'));

    if ($subjects === []) {
        $subjectsText = 'В учебном плане текущего семестра предметы для журнала пока не назначены, '
            . 'либо данные ещё не внесены преподавателями.';
    } else {
        $subjectsText = 'Анализ по дисциплинам показывает следующее. ';
        if ($strong !== []) {
            $subjectsText .= 'Уверенно выглядят: '
                . implode(', ', array_map(static fn (array $s): string => '«' . $s['subject_name'] . '»', array_slice($strong, 0, 3)))
                . '. ';
        }
        if ($risk !== []) {
            $subjectsText .= 'В зоне риска: '
                . implode(', ', array_map(static fn (array $s): string => '«' . $s['subject_name'] . '»', array_slice($risk, 0, 3)))
                . ' — здесь целесообразна дополнительная самостоятельная работа и консультации. ';
        } elseif ($attention !== []) {
            $subjectsText .= 'Под особым контролем стоит держать: '
                . implode(', ', array_map(static fn (array $s): string => '«' . $s['subject_name'] . '»', array_slice($attention, 0, 3)))
                . '. ';
        } else {
            $subjectsText .= 'Критических просадок по отдельным дисциплинам не выявлено, '
                . 'однако равномерность подготовки сохраняет значение на протяжении всего семестра.';
        }
    }

    $visited = (int) ($journalActivity['lessons_visited'] ?? 0);
    $absent = (int) ($journalActivity['lessons_absent'] ?? 0);
    $late = (int) ($journalActivity['late_count'] ?? 0);
    $activity = (int) ($journalActivity['activity_count'] ?? 0);

    if ($visited + $absent === 0) {
        $activityText = 'Сведений об отметках присутствия, активности и опозданиях в журнале пока нет. '
            . 'После наполнения журнала занятиями здесь появится более точная характеристика учебной дисциплины.';
    } else {
        $activityText = 'По данным электронного журнала учтено посещённых занятий: ' . $visited
            . ', пропусков («Н»): ' . $absent . '. ';
        if ($journalActivity['attendance_rate'] !== null) {
            $activityText .= 'Доля присутствия среди отмеченных занятий — '
                . student_analytics_fmt((float) $journalActivity['attendance_rate']) . '%. ';
        }
        if ($activity > 0) {
            $activityText .= 'Активность на занятиях отмечена ' . $activity
                . student_analytics_plural($activity, ' раз', ' раза', ' раз') . '. ';
        } else {
            $activityText .= 'Отметки за активность почти не встречаются — '
                . 'полезно чаще включаться в устную и практическую работу. ';
        }
        if ($late > 0) {
            $activityText .= 'Опозданий зафиксировано: ' . $late . '. '
                . 'Регулярные опоздания снижают качество восприятия материала в начале занятия.';
        } else {
            $activityText .= 'Опозданий практически не отмечено — это положительная черта учебной дисциплины.';
        }
    }

    $excused = (int) ($attendance['excused'] ?? 0);
    $unexcused = (int) ($attendance['unexcused'] ?? 0);
    $totalAbs = (int) ($attendance['total'] ?? 0);
    if ($totalAbs === 0) {
        $attendanceText = 'В журнале посещаемости за учебный год пропуски по дням не зафиксированы '
            . '(либо сведения ещё не внесены куратором). Это благоприятный фон для устойчивой учёбы.';
    } else {
        $attendanceText = 'По журналу посещаемости за ' . $year . ' учебный год учтено '
            . $totalAbs . student_analytics_plural($totalAbs, ' час', ' часа', ' часов')
            . ' пропусков: из них по уважительной причине — ' . $excused
            . ', без уважительной — ' . $unexcused . '. ';
        if ($unexcused > 0) {
            $attendanceText .= 'Пропуски без уважительной причины особенно чувствительны для итоговой аттестации '
                . 'и требуют осознанного отношения к режиму учёбы.';
        } else {
            $attendanceText .= 'Отсутствие «неуважительных» пропусков — положительный показатель; '
                . 'важно лишь своевременно восполнять материал после вынужденных отсутствий.';
        }
        if (($attendance['by_reason'][0]['reason_name'] ?? '') !== '') {
            $top = $attendance['by_reason'][0];
            $attendanceText .= ' Наиболее частая причина: «' . $top['reason_name'] . '» ('
                . (int) $top['total'] . ' ч.).';
        }
    }

    $recLead = $recommendations[0]['text'] ?? '';
    $closing = 'Итоговая рекомендация педагога: ' . $recLead;

    return [
        'overview' => trim($overviewText),
        'subjects' => trim($subjectsText),
        'activity' => trim($activityText),
        'attendance' => trim($attendanceText),
        'closing' => trim($closing),
    ];
}

function student_analytics_fmt(float $value): string
{
    $formatted = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

    return $formatted === '' ? '0' : $formatted;
}

function student_analytics_plural(int $n, string $one, string $few, string $many): string
{
    $nAbs = abs($n) % 100;
    $n1 = $nAbs % 10;
    if ($nAbs > 10 && $nAbs < 20) {
        return $many;
    }
    if ($n1 > 1 && $n1 < 5) {
        return $few;
    }
    if ($n1 === 1) {
        return $one;
    }

    return $many;
}

function student_analytics_icon(string $name): string
{
    $icons = [
        'chart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 19h16v2H2V3h2v16zm3-1V10h2v8H7zm4 0V6h2v12h-2zm4 0v-5h2v5h-2zm4 0V8h2v10h-2z"/></svg>',
        'book' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6 4h11a3 3 0 0 1 3 3v13H7a2 2 0 0 0-2 2h14v2H5a4 4 0 0 1-4-4V4a2 2 0 0 1 2-2h1v2zm2 2v12.17c.31-.11.65-.17 1-.17h11V7a1 1 0 0 0-1-1H8z"/></svg>',
        'clock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zm1 5h-2v6l5 3 1-1.7-4-2.3V7z"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 2h2v2h6V2h2v2h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h3V2zm13 8H4v10h16V10zM4 8h16V6H4v2z"/></svg>',
        'spark' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2l1.8 6.2L20 10l-6.2 1.8L12 18l-1.8-6.2L4 10l6.2-1.8L12 2zm7 11 1 3.4L23.4 17 20 18l-1 3.4L18 18l-3.4-1L18 16l1-3z"/></svg>',
        'alert' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2 1 21h22L12 2zm0 5.8 7.2 11.7H4.8L12 7.8zM11 10v5h2v-5h-2zm0 6v2h2v-2h-2z"/></svg>',
        'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9.5 16.2 5.3 12l1.4-1.4 2.8 2.8 7-7L18 7.8l-8.5 8.4z"/></svg>',
        'info' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zm-1 5h2v2h-2V7zm0 4h2v6h-2v-6z"/></svg>',
        'user' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12zm0 2c-4 0-8 2-8 5v1h16v-1c0-3-4-5-8-5z"/></svg>',
        'target' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10h-2a8 8 0 1 1-8-8V2zm0 4a6 6 0 1 0 6 6h-2a4 4 0 1 1-4-4V6zm1 3v3.6l3 1.8-.9 1.5L11 13.5V9h2z"/></svg>',
    ];

    return $icons[$name] ?? $icons['info'];
}
