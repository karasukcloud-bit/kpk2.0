(function () {
    const config = window.MANUAL_BRS_CONFIG || { lessonsTotal: 0, brs: {} };
    const brs = config.brs || {};

    function formatNum(value, decimals) {
        if (value === null || value === undefined || Number.isNaN(value)) {
            return '—';
        }
        const fixed = Number(value).toFixed(decimals);
        return fixed.replace(/\.?0+$/, '');
    }

    function syncPaSelectInRow(row, paGrade) {
        const select = row.querySelector('[data-manual-brs-pa]');
        if (select) {
            select.value = paGrade == null || paGrade === '' ? '' : String(paGrade);
        }
        row.setAttribute('data-pa-grade', paGrade == null || paGrade === '' ? '' : String(paGrade));
    }

    function renderSemesterHtml(grade, points) {
        if (grade === null || grade === undefined || grade === '') {
            return '<span class="text-muted">—</span>';
        }
        const gradeNum = Math.max(2, Math.min(5, parseInt(grade, 10) || 2));
        let html = '<div class="journal-total">'
            + '<span class="journal-total__grade journal-total__grade--' + gradeNum + '">'
            + gradeNum
            + '</span>';
        if (points !== null && points !== undefined && points !== '') {
            html += '<span class="journal-total__points">' + formatNum(Number(points), 1) + '</span>';
        }
        return html + '</div>';
    }

    function updateRowSemester(row, data) {
        const periodCell = row.querySelector('[data-manual-brs-period-cell]');
        const semesterCell = row.querySelector('[data-manual-brs-semester-cell]');
        if (periodCell) {
            const display = data.period_display
                || (data.calc && data.calc.display)
                || '';
            if (display !== undefined) {
                periodCell.textContent = display || '—';
            }
        }
        if (data.points !== undefined) {
            row.setAttribute('data-points', data.points != null ? String(data.points) : '');
        }
        if (data.grade !== undefined) {
            row.setAttribute('data-grade', data.grade != null ? String(data.grade) : '');
        }
        if (semesterCell) {
            if (data.semester_html) {
                semesterCell.innerHTML = data.semester_html;
            } else {
                semesterCell.innerHTML = renderSemesterHtml(data.semester_grade, data.semester_points);
            }
        }
        if (data.pa_grade !== undefined) {
            syncPaSelectInRow(row, data.pa_grade);
        }
    }

    function applyRowsPayload(rowsMap) {
        if (!rowsMap || typeof rowsMap !== 'object') {
            return;
        }
        document.querySelectorAll('[data-manual-brs-open]').forEach((row) => {
            const sid = row.getAttribute('data-student-id');
            if (!sid || !rowsMap[sid]) {
                return;
            }
            updateRowSemester(row, rowsMap[sid]);
        });
    }

    const lessonsForm = document.querySelector('[data-manual-brs-lessons-form]');
    const lessonsInput = document.querySelector('[data-manual-brs-lessons-input]');
    const lessonsStatus = document.querySelector('[data-manual-brs-lessons-status]');
    const lessonsLabel = document.querySelector('[data-manual-brs-lessons-label]');
    let lessonsTimer = null;
    let lessonsSaving = false;
    let lessonsLastSaved = lessonsInput
        ? String(parseInt(lessonsInput.value, 10) || 0)
        : '';

    function setLessonsStatus(text) {
        if (lessonsStatus) {
            lessonsStatus.textContent = text || '';
        }
    }

    function applyLessonsTotal(value) {
        const n = Math.max(0, Math.min(200, parseInt(value, 10) || 0));
        config.lessonsTotal = n;
        if (lessonsLabel) {
            lessonsLabel.textContent = String(n);
        }
        return n;
    }

    async function saveLessonsTotal() {
        if (!lessonsForm || !lessonsInput || lessonsSaving) {
            return;
        }
        const value = String(Math.max(0, Math.min(200, parseInt(lessonsInput.value, 10) || 0)));
        if (value === lessonsLastSaved) {
            setLessonsStatus('');
            return;
        }
        lessonsInput.value = value;
        lessonsSaving = true;
        setLessonsStatus('Сохранение…');
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(lessonsForm),
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Ошибка сохранения');
            }
            lessonsLastSaved = String(data.lessons_total != null ? data.lessons_total : value);
            applyLessonsTotal(lessonsLastSaved);
            applyRowsPayload(data.rows);
            setLessonsStatus('Сохранено');
            window.setTimeout(() => {
                if (lessonsStatus && lessonsStatus.textContent === 'Сохранено') {
                    setLessonsStatus('');
                }
            }, 1500);
        } catch (err) {
            setLessonsStatus(err.message || 'Не удалось сохранить');
        } finally {
            lessonsSaving = false;
            const current = String(Math.max(0, Math.min(200, parseInt(lessonsInput.value, 10) || 0)));
            if (current !== lessonsLastSaved) {
                scheduleLessonsSave();
            }
        }
    }

    function scheduleLessonsSave() {
        if (lessonsTimer) {
            window.clearTimeout(lessonsTimer);
        }
        lessonsTimer = window.setTimeout(saveLessonsTotal, 400);
    }

    if (lessonsForm && lessonsInput) {
        lessonsForm.addEventListener('submit', (event) => {
            event.preventDefault();
            saveLessonsTotal();
        });
        lessonsInput.addEventListener('change', saveLessonsTotal);
        lessonsInput.addEventListener('input', scheduleLessonsSave);
        lessonsInput.addEventListener('blur', saveLessonsTotal);
    }

    const modal = document.querySelector('[data-manual-brs-modal]');
    if (!modal) {
        return;
    }

    const form = modal.querySelector('[data-manual-brs-form]');
    const studentIdInput = modal.querySelector('[data-manual-brs-student-id]');
    const studentLabel = modal.querySelector('[data-manual-brs-student-label]');
    const paModalSelect = modal.querySelector('[data-manual-brs-pa-modal]');
    const currentAvgNode = modal.querySelector('[data-manual-brs-current-avg]');
    const controlAvgNode = modal.querySelector('[data-manual-brs-control-avg]');
    const totalNode = modal.querySelector('[data-manual-brs-total]');
    const statusNode = modal.querySelector('[data-manual-brs-form-status]');
    const paForm = document.querySelector('[data-manual-brs-pa-form]');
    const paStudentInput = paForm ? paForm.querySelector('[data-manual-brs-pa-student]') : null;
    const paValueInput = paForm ? paForm.querySelector('[data-manual-brs-pa-value]') : null;
    let activeRow = null;

    function stageField(stage, field) {
        return modal.querySelector(
            '[data-manual-brs-stage="' + stage + '"][data-manual-brs-field="' + field + '"]'
        );
    }

    function stageAvgNode(stage, field) {
        return modal.querySelector('[data-manual-brs-stage-avg="' + stage + '-' + field + '"]');
    }

    function parseMarks(raw) {
        return String(raw || '')
            .replace(/[^2-5]/g, '')
            .split('')
            .map((digit) => parseInt(digit, 10))
            .filter((n) => n >= 2 && n <= 5);
    }

    function filterMarksInput(input) {
        const cleaned = String(input.value || '').replace(/[^2-5]/g, '');
        if (input.value !== cleaned) {
            const pos = input.selectionStart;
            const removed = String(input.value || '').length - cleaned.length;
            input.value = cleaned;
            if (typeof pos === 'number') {
                const next = Math.max(0, pos - removed);
                input.setSelectionRange(next, next);
            }
        }
    }

    function avg(marks) {
        if (!marks.length) {
            return null;
        }
        return Math.round((marks.reduce((a, b) => a + b, 0) / marks.length) * 100) / 100;
    }

    function pointsToGrade(points) {
        if (points >= Number(brs.scale_5 || 75)) {
            return 5;
        }
        if (points >= Number(brs.scale_4 || 65)) {
            return 4;
        }
        if (points >= Number(brs.scale_3 || 50)) {
            return 3;
        }
        return 2;
    }

    function readStage(stage) {
        const current = stageField(stage, 'current');
        const control = stageField(stage, 'control');
        const absent = stageField(stage, 'absent');
        const late = stageField(stage, 'late');
        const activity = stageField(stage, 'activity');
        return {
            currentMarks: parseMarks(current ? current.value : ''),
            controlMarks: parseMarks(control ? control.value : ''),
            currentInput: current ? String(current.value || '').trim() : '',
            controlInput: control ? String(control.value || '').trim() : '',
            absent: Math.max(0, parseInt(absent ? absent.value : '0', 10) || 0),
            late: Math.max(0, parseInt(late ? late.value : '0', 10) || 0),
            activity: Math.max(0, parseInt(activity ? activity.value : '0', 10) || 0),
        };
    }

    function calculate() {
        const s1 = readStage(1);
        const s2 = readStage(2);
        const currentMarks = s1.currentMarks.concat(s2.currentMarks);
        const controlMarks = s1.controlMarks.concat(s2.controlMarks);
        const currentAvg = avg(currentMarks);
        const controlAvg = avg(controlMarks);
        const s1CurrentAvg = avg(s1.currentMarks);
        const s1ControlAvg = avg(s1.controlMarks);
        const s2CurrentAvg = avg(s2.currentMarks);
        const s2ControlAvg = avg(s2.controlMarks);
        const lessonsTotal = Math.max(0, Number(config.lessonsTotal) || 0);
        let absent = s1.absent + s2.absent;
        let late = s1.late + s2.late;
        let activity = s1.activity + s2.activity;

        if (lessonsTotal > 0 && absent > lessonsTotal) {
            absent = lessonsTotal;
        }
        const attended = Math.max(0, lessonsTotal - absent);
        if (late > attended) {
            late = attended;
        }
        if (activity > attended) {
            activity = attended;
        }
        const notLate = Math.max(0, attended - late);

        const setAvg = (node, value) => {
            if (node) {
                node.textContent = value === null ? '—' : formatNum(value, 2);
            }
        };
        setAvg(stageAvgNode(1, 'current'), s1CurrentAvg);
        setAvg(stageAvgNode(1, 'control'), s1ControlAvg);
        setAvg(stageAvgNode(2, 'current'), s2CurrentAvg);
        setAvg(stageAvgNode(2, 'control'), s2ControlAvg);
        setAvg(currentAvgNode, currentAvg);
        setAvg(controlAvgNode, controlAvg);

        const hasData = currentMarks.length || controlMarks.length || lessonsTotal > 0;
        if (!hasData) {
            totalNode.textContent = '—';
            return null;
        }

        let points = 0;
        if (currentAvg !== null) {
            points += (currentAvg / 5) * Number(brs.weight_current || 30);
        }
        if (controlAvg !== null) {
            points += (controlAvg / 5) * Number(brs.weight_control || 45);
        } else if (currentAvg !== null) {
            points += (currentAvg / 5) * Number(brs.weight_control || 45);
        }
        if (lessonsTotal > 0) {
            points += (Number(brs.weight_attendance || 10) / lessonsTotal) * attended;
            if (attended > 0) {
                points += (Number(brs.weight_punctuality || 5) / attended) * notLate;
                points += (Number(brs.weight_activity || 5) / attended) * activity;
            }
        }

        points = Math.round(Math.max(0, Math.min(100, points)) * 10) / 10;
        const grade = pointsToGrade(points);
        totalNode.textContent = formatNum(points, 1) + ' → ' + grade;
        return { points: points, grade: grade, display: formatNum(points, 1) + ' → ' + grade };
    }

    function openModal(row) {
        activeRow = row;
        studentIdInput.value = row.getAttribute('data-student-id') || '';
        studentLabel.textContent = row.getAttribute('data-student-name') || '';
        const map = {
            1: {
                current: 'data-s1-current',
                control: 'data-s1-control',
                absent: 'data-s1-absent',
                late: 'data-s1-late',
                activity: 'data-s1-activity',
            },
            2: {
                current: 'data-s2-current',
                control: 'data-s2-control',
                absent: 'data-s2-absent',
                late: 'data-s2-late',
                activity: 'data-s2-activity',
            },
        };
        [1, 2].forEach((stage) => {
            Object.keys(map[stage]).forEach((field) => {
                const input = stageField(stage, field);
                if (!input) {
                    return;
                }
                const raw = row.getAttribute(map[stage][field]);
                input.value = field === 'current' || field === 'control'
                    ? (raw || '')
                    : (raw || '0');
            });
        });
        if (paModalSelect) {
            paModalSelect.value = row.getAttribute('data-pa-grade') || '';
        }
        if (statusNode) {
            statusNode.textContent = '';
        }
        calculate();
        modal.removeAttribute('hidden');
        document.body.classList.add('modal-open');
        const first = stageField(1, 'current');
        if (first) {
            first.focus();
        }
    }

    function closeModal() {
        modal.setAttribute('hidden', '');
        document.body.classList.remove('modal-open');
        activeRow = null;
    }

    document.querySelectorAll('[data-manual-brs-open]').forEach((row) => {
        row.addEventListener('click', () => openModal(row));
    });

    modal.querySelectorAll('[data-manual-brs-close]').forEach((node) => {
        node.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hasAttribute('hidden')) {
            closeModal();
        }
    });

    modal.querySelectorAll('[data-manual-brs-field="current"], [data-manual-brs-field="control"]').forEach((input) => {
        input.addEventListener('input', () => {
            filterMarksInput(input);
            calculate();
        });
    });

    modal.querySelectorAll(
        '[data-manual-brs-field="absent"], [data-manual-brs-field="late"], [data-manual-brs-field="activity"]'
    ).forEach((input) => {
        input.addEventListener('input', calculate);
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (statusNode) {
            statusNode.textContent = 'Сохранение…';
        }
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });
            const raw = await response.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch (parseErr) {
                throw new Error('Сервер вернул не JSON. Обновите страницу и попробуйте снова.');
            }
            if (!data.success) {
                throw new Error(data.error || 'Ошибка сохранения');
            }
            const calc = data.calc || {};
            const s1 = readStage(1);
            const s2 = readStage(2);
            if (activeRow) {
                activeRow.setAttribute('data-s1-current', s1.currentInput);
                activeRow.setAttribute('data-s1-control', s1.controlInput);
                activeRow.setAttribute('data-s1-absent', String(s1.absent));
                activeRow.setAttribute('data-s1-late', String(s1.late));
                activeRow.setAttribute('data-s1-activity', String(s1.activity));
                activeRow.setAttribute('data-s2-current', s2.currentInput);
                activeRow.setAttribute('data-s2-control', s2.controlInput);
                activeRow.setAttribute('data-s2-absent', String(s2.absent));
                activeRow.setAttribute('data-s2-late', String(s2.late));
                activeRow.setAttribute('data-s2-activity', String(s2.activity));
                activeRow.setAttribute('data-points', calc.points != null ? String(calc.points) : '');
                activeRow.setAttribute('data-grade', calc.grade != null ? String(calc.grade) : '');
                updateRowSemester(activeRow, data);
            }
            if (statusNode) {
                statusNode.textContent = 'Сохранено';
            }
            closeModal();
        } catch (err) {
            if (statusNode) {
                statusNode.textContent = err.message || 'Не удалось сохранить';
            }
        }
    });

    document.querySelectorAll('[data-manual-brs-pa]').forEach((select) => {
        select.addEventListener('change', async () => {
            if (!paForm || !paStudentInput || !paValueInput) {
                return;
            }
            const row = select.closest('tr');
            const studentId = select.getAttribute('data-student-id') || '';
            paStudentInput.value = studentId;
            paValueInput.value = select.value;
            select.disabled = true;
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(paForm),
                });
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.error || 'Ошибка сохранения ПА');
                }
                if (row) {
                    updateRowSemester(row, data);
                }
            } catch (err) {
                alert(err.message || 'Не удалось сохранить ПА');
            } finally {
                select.disabled = false;
            }
        });
    });
})();
