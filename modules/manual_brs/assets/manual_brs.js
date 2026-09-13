(function () {
    const config = window.MANUAL_BRS_CONFIG || { lessonsTotal: 0, brs: {} };
    const brs = config.brs || {};
    const modal = document.querySelector('[data-manual-brs-modal]');
    if (!modal) {
        return;
    }

    const form = modal.querySelector('[data-manual-brs-form]');
    const studentIdInput = modal.querySelector('[data-manual-brs-student-id]');
    const studentLabel = modal.querySelector('[data-manual-brs-student-label]');
    const currentInput = modal.querySelector('[data-manual-brs-current]');
    const controlInput = modal.querySelector('[data-manual-brs-control]');
    const absentInput = modal.querySelector('[data-manual-brs-absent]');
    const lateInput = modal.querySelector('[data-manual-brs-late]');
    const activityInput = modal.querySelector('[data-manual-brs-activity]');
    const paModalSelect = modal.querySelector('[data-manual-brs-pa-modal]');
    const currentAvgNode = modal.querySelector('[data-manual-brs-current-avg]');
    const controlAvgNode = modal.querySelector('[data-manual-brs-control-avg]');
    const totalNode = modal.querySelector('[data-manual-brs-total]');
    const statusNode = modal.querySelector('[data-manual-brs-form-status]');
    const paForm = document.querySelector('[data-manual-brs-pa-form]');
    const paStudentInput = paForm ? paForm.querySelector('[data-manual-brs-pa-student]') : null;
    const paValueInput = paForm ? paForm.querySelector('[data-manual-brs-pa-value]') : null;
    let activeRow = null;

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

    function formatNum(value, decimals) {
        if (value === null || value === undefined || Number.isNaN(value)) {
            return '—';
        }
        const fixed = Number(value).toFixed(decimals);
        return fixed.replace(/\.?0+$/, '');
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

    function calculate() {
        const currentMarks = parseMarks(currentInput.value);
        const controlMarks = parseMarks(controlInput.value);
        const currentAvg = avg(currentMarks);
        const controlAvg = avg(controlMarks);
        const lessonsTotal = Math.max(0, Number(config.lessonsTotal) || 0);
        let absent = Math.max(0, parseInt(absentInput.value, 10) || 0);
        let late = Math.max(0, parseInt(lateInput.value, 10) || 0);
        let activity = Math.max(0, parseInt(activityInput.value, 10) || 0);

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

        currentAvgNode.textContent = currentAvg === null ? '—' : formatNum(currentAvg, 2);
        controlAvgNode.textContent = controlAvg === null ? '—' : formatNum(controlAvg, 2);

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
        const p1 = row.querySelector('[data-manual-brs-p1-cell]');
        const p2 = row.querySelector('[data-manual-brs-p2-cell]');
        const semesterCell = row.querySelector('[data-manual-brs-semester-cell]');
        if (p1 && data.period_1_display !== undefined) {
            p1.textContent = data.period_1_display || '—';
        }
        if (p2 && data.period_2_display !== undefined) {
            p2.textContent = data.period_2_display || '—';
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

    function openModal(row) {
        activeRow = row;
        studentIdInput.value = row.getAttribute('data-student-id') || '';
        studentLabel.textContent = row.getAttribute('data-student-name') || '';
        currentInput.value = row.getAttribute('data-current-marks') || '';
        controlInput.value = row.getAttribute('data-control-marks') || '';
        absentInput.value = row.getAttribute('data-absent') || '0';
        lateInput.value = row.getAttribute('data-late') || '0';
        activityInput.value = row.getAttribute('data-activity') || '0';
        if (paModalSelect) {
            paModalSelect.value = row.getAttribute('data-pa-grade') || '';
        }
        if (statusNode) {
            statusNode.textContent = '';
        }
        calculate();
        modal.removeAttribute('hidden');
        document.body.classList.add('modal-open');
        currentInput.focus();
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

    [currentInput, controlInput].forEach((input) => {
        input.addEventListener('input', () => {
            filterMarksInput(input);
            calculate();
        });
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('pattern', '[2-5]*');
        input.setAttribute('autocomplete', 'off');
    });

    [absentInput, lateInput, activityInput].forEach((input) => {
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
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Ошибка сохранения');
            }
            const calc = data.calc || {};
            if (activeRow) {
                activeRow.setAttribute('data-current-marks', currentInput.value.trim());
                activeRow.setAttribute('data-control-marks', controlInput.value.trim());
                activeRow.setAttribute('data-absent', String(absentInput.value || '0'));
                activeRow.setAttribute('data-late', String(lateInput.value || '0'));
                activeRow.setAttribute('data-activity', String(activityInput.value || '0'));
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
