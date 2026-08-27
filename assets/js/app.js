(function () {
    const generatePasswordValue = (length = 8) => {
        const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        let password = '';
        for (let i = 0; i < length; i += 1) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return password;
    };

    const resolvePasswordFields = (button) => {
        const form = button.closest('form');
        const passwordId = button.getAttribute('data-password-target') || 'password';
        const confirmId = button.getAttribute('data-password-confirm-target') || 'password_confirm';

        let passwordInput = document.getElementById(passwordId);
        let confirmInput = document.getElementById(confirmId);

        if (!passwordInput && form) {
            passwordInput = form.querySelector('input[name="' + passwordId + '"]');
        }
        if (!confirmInput && form) {
            confirmInput = form.querySelector('input[name="' + confirmId + '"]');
        }

        return { passwordInput, confirmInput };
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-generate-password]');
        if (!button) {
            return;
        }

        event.preventDefault();
        const { passwordInput, confirmInput } = resolvePasswordFields(button);
        if (!passwordInput) {
            return;
        }

        const password = generatePasswordValue();
        passwordInput.value = password;
        if (confirmInput) {
            confirmInput.value = password;
        }
        passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
        passwordInput.focus();
        if (typeof passwordInput.select === 'function') {
            passwordInput.select();
        }
    });
})();

document.addEventListener('DOMContentLoaded', () => {
    const passwordForms = document.querySelectorAll('form');

    passwordForms.forEach((form) => {
        const confirm = form.querySelector('#password_confirm, #new_password_confirm');
        const password = form.querySelector('#password, #new_password');

        if (confirm && password) {
            form.addEventListener('submit', (event) => {
                if (password.value !== confirm.value) {
                    event.preventDefault();
                    alert('Пароли не совпадают.');
                    confirm.focus();
                }
            });
        }

        const roleCheckboxes = form.querySelectorAll('input[name="staff_roles[]"]');
        if (roleCheckboxes.length > 0) {
            form.addEventListener('submit', (event) => {
                const checked = Array.from(roleCheckboxes).some((item) => item.checked);
                if (!checked) {
                    event.preventDefault();
                    alert('Выберите хотя бы одну роль.');
                }
            });
        }
    });

    const gradebook = document.querySelector('[data-gradebook]');
    if (gradebook) {
        const saveUrl = gradebook.dataset.saveUrl;
        const csrfToken = gradebook.dataset.csrf;
        const statusNode = gradebook.querySelector('[data-gradebook-status]');
        const assessedNode = document.querySelector('[data-summary-assessed]');
        const absoluteNode = document.querySelector('[data-summary-absolute]');
        const qualityNode = document.querySelector('[data-summary-quality]');
        const listsRoot = document.querySelector('[data-gradebook-lists]');
        const cells = () => Array.from(gradebook.querySelectorAll('.gradebook-table__grade-cell'));

        const setStatus = (message, state = '') => {
            if (!statusNode) {
                return;
            }

            statusNode.textContent = message;
            statusNode.dataset.state = state;
        };

        const normalizeGradeText = (value) => value.replace(/\s+/g, '');

        const getValidGrade = (value) => {
            const trimmed = normalizeGradeText(value);

            if (trimmed === '') {
                return '';
            }

            return /^[2-5]$/.test(trimmed) ? trimmed : null;
        };

        const getCellPosition = (cell) => {
            const row = cell.parentElement;
            const body = row.parentElement;

            return {
                row: row.rowIndex - 1,
                col: cell.cellIndex - 1,
            };
        };

        const focusCellAt = (rowIndex, colIndex) => {
            const body = gradebook.querySelector('.gradebook-table tbody');
            if (!body || !body.rows[rowIndex]) {
                return null;
            }

            const cell = body.rows[rowIndex].cells[colIndex + 1];
            if (!cell || !cell.classList.contains('gradebook-table__grade-cell')) {
                return null;
            }

            cell.focus();
            const range = document.createRange();
            range.selectNodeContents(cell);
            range.collapse(false);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);

            return cell;
        };

        const moveFocus = (cell, rowDelta, colDelta) => {
            const { row, col } = getCellPosition(cell);
            return focusCellAt(row + rowDelta, col + colDelta);
        };

        const renderStudentList = (container, items, withSubjects = false) => {
            if (!container) {
                return;
            }

            container.replaceChildren();

            if (!items.length) {
                const empty = document.createElement('p');
                empty.className = 'text-muted';
                empty.textContent = 'Нет студентов';
                container.appendChild(empty);
                return;
            }

            const list = document.createElement('ul');
            list.className = 'gradebook-lists__items';

            items.forEach((item) => {
                const row = document.createElement('li');
                row.className = 'gradebook-lists__item';

                const name = document.createElement('strong');
                name.textContent = item.full_name;
                row.appendChild(name);

                if (withSubjects && item.subjects?.length) {
                    const subjects = document.createElement('span');
                    subjects.className = 'gradebook-lists__subjects text-muted';
                    subjects.textContent = item.subjects.join(', ');
                    row.appendChild(subjects);
                }

                list.appendChild(row);
            });

            container.appendChild(list);
        };

        const renderStudentLists = (lists) => {
            if (!listsRoot || !lists) {
                return;
            }

            renderStudentList(listsRoot.querySelector('[data-list-twos]'), lists.with_twos || [], true);
            renderStudentList(listsRoot.querySelector('[data-list-good]'), lists.only_good || []);
            renderStudentList(listsRoot.querySelector('[data-list-excellent]'), lists.excellent || []);
        };

        const saveCell = async (cell) => {
            let nextValue = normalizeGradeText(cell.textContent);
            let validValue = getValidGrade(nextValue);

            if (validValue === null) {
                cell.textContent = '';
                validValue = '';
                nextValue = '';
            }

            if (nextValue === (cell.dataset.original || '')) {
                return true;
            }

            cell.dataset.saving = '1';
            setStatus('Сохранение...', 'saving');

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('student_id', cell.dataset.studentId);
            formData.append('curriculum_item_id', cell.dataset.itemId);
            formData.append('grade', validValue);

            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    body: formData,
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Не удалось сохранить оценку.');
                }

                cell.dataset.original = data.grade;
                cell.textContent = data.grade;

                if (assessedNode) {
                    assessedNode.textContent = `${data.summary.assessed_students} из ${data.summary.total_students}`;
                }

                if (absoluteNode) {
                    absoluteNode.textContent = `${data.summary.absolute_percent}%`;
                }

                if (qualityNode) {
                    qualityNode.textContent = `${data.summary.quality_percent}%`;
                }

                renderStudentLists(data.lists);

                setStatus('Оценка сохранена.', 'success');
                return true;
            } catch (error) {
                cell.textContent = cell.dataset.original || '';
                setStatus(error.message || 'Ошибка сохранения.', 'error');
                return false;
            } finally {
                delete cell.dataset.saving;
            }
        };

        cells().forEach((cell) => {
            cell.addEventListener('input', () => {
                let value = normalizeGradeText(cell.textContent);

                if (value.length > 1) {
                    value = value.slice(-1);
                }

                if (value !== '' && !/^[2-5]$/.test(value)) {
                    cell.textContent = '';
                    return;
                }

                cell.textContent = value;
            });

            cell.addEventListener('keydown', async (event) => {
                const arrows = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];

                if (arrows.includes(event.key)) {
                    event.preventDefault();

                    if (cell.dataset.saving) {
                        return;
                    }

                    await saveCell(cell);

                    const moves = {
                        ArrowUp: [-1, 0],
                        ArrowDown: [1, 0],
                        ArrowLeft: [0, -1],
                        ArrowRight: [0, 1],
                    };
                    const [rowDelta, colDelta] = moves[event.key];
                    moveFocus(cell, rowDelta, colDelta);
                    return;
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                    cell.blur();
                }
            });

            cell.addEventListener('blur', () => {
                if (!cell.dataset.saving) {
                    saveCell(cell);
                }
            });

            cell.addEventListener('paste', (event) => {
                event.preventDefault();
                const text = normalizeGradeText(event.clipboardData.getData('text'));
                cell.textContent = /^[2-5]$/.test(text) ? text : '';
            });
        });
    }

    const attendanceToggle = document.querySelector('[data-attendance-add-toggle]');
    const attendanceForm = document.querySelector('[data-attendance-form]');

    if (attendanceToggle && attendanceForm) {
        attendanceToggle.addEventListener('click', () => {
            const hidden = attendanceForm.classList.toggle('attendance-form--hidden');
            attendanceToggle.textContent = hidden ? 'Добавить дату' : 'Скрыть форму';
        });
    }

    const initTableColHover = (table) => {
        let activeCol = -1;

        const clearColHover = () => {
            if (activeCol < 0) {
                return;
            }
            table.querySelectorAll('.is-col-hover').forEach((cell) => {
                cell.classList.remove('is-col-hover');
            });
            activeCol = -1;
        };

        const setColHover = (colIndex) => {
            if (
                colIndex === activeCol
                && colIndex >= 0
                && table.querySelector(`tr > :nth-child(${colIndex + 1}).is-col-hover`)
            ) {
                return;
            }
            clearColHover();
            if (colIndex < 0) {
                return;
            }
            activeCol = colIndex;
            table.querySelectorAll(`tr > :nth-child(${colIndex + 1})`).forEach((cell) => {
                cell.classList.add('is-col-hover');
            });
        };

        table.addEventListener('mouseover', (event) => {
            if (table.closest('.journal-table-wrap.is-dragging')) {
                return;
            }
            const cell = event.target instanceof Element
                ? event.target.closest('th, td')
                : null;
            if (!cell || !table.contains(cell)) {
                return;
            }
            setColHover(cell.cellIndex);
        });

        table.addEventListener('mouseleave', () => {
            clearColHover();
        });
    };

    document.querySelectorAll('.attendance-table, .journal-table').forEach(initTableColHover);

    const journal = document.querySelector('[data-journal]');
    if (journal) {
        const saveUrl = journal.dataset.saveUrl;
        const csrfToken = journal.dataset.csrf;
        const statusNode = journal.querySelector('[data-journal-status]');

        const setStatus = (message, state = '') => {
            if (!statusNode) {
                return;
            }

            statusNode.textContent = message;
            statusNode.dataset.state = state;
        };

        const updateTotals = (totals) => {
            if (!totals) {
                return;
            }

            Object.keys(totals).forEach((studentId) => {
                const node = journal.querySelector(`[data-journal-total="${studentId}"]`);
                if (!node) {
                    return;
                }

                const total = totals[studentId];
                if (total && typeof total === 'object' && typeof total.html === 'string') {
                    node.innerHTML = total.html;
                    return;
                }

                node.textContent = total || '';
            });
        };

        const closeAllMenus = (except = null) => {
            journal.querySelectorAll('[data-mark-menu]').forEach((menu) => {
                if (menu === except) {
                    return;
                }

                menu.hidden = true;
                const trigger = menu.parentElement.querySelector('[data-mark-trigger]');
                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                }
            });
        };

        const syncCellUi = (cell) => {
            const mark = cell.dataset.mark || '';
            const present = mark !== 'Н';
            const hasGrade = ['2', '3', '4', '5'].includes(mark);
            const canActivity = present && hasGrade;
            const canLate = present;
            const trigger = cell.querySelector('[data-mark-trigger]');
            const activityBtn = cell.querySelector('[data-flag="activity"]');
            const lateBtn = cell.querySelector('[data-flag="late"]');

            if (!canActivity) {
                cell.dataset.activity = '0';
            }
            if (!canLate) {
                cell.dataset.late = '0';
            }

            if (trigger) {
                trigger.textContent = mark === '' ? '—' : mark;
                trigger.classList.toggle('journal-mark__trigger--set', mark !== '');
                trigger.classList.toggle('journal-mark__trigger--absent', mark === 'Н');
            }

            cell.querySelectorAll('[data-mark-option]').forEach((option) => {
                option.classList.toggle(
                    'journal-mark__option--active',
                    (option.dataset.markOption || '') === mark
                );
            });

            if (activityBtn) {
                const on = cell.dataset.activity === '1';
                activityBtn.classList.toggle('journal-flag--on', on);
                activityBtn.classList.toggle('journal-flag--disabled', !canActivity);
                activityBtn.disabled = !canActivity;
                activityBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
                activityBtn.title = canActivity
                    ? 'Активность'
                    : 'Активность доступна при оценке 2–5';
            }

            if (lateBtn) {
                const on = cell.dataset.late === '1';
                lateBtn.classList.toggle('journal-flag--on', on);
                lateBtn.classList.toggle('journal-flag--disabled', !canLate);
                lateBtn.disabled = !canLate;
                lateBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
                lateBtn.title = canLate
                    ? 'Опоздание'
                    : 'Опоздание недоступно при пропуске (Н)';
            }
        };

        const saveCell = async (cell) => {
            if (cell.dataset.saving === '1') {
                return false;
            }

            cell.dataset.saving = '1';
            setStatus('Сохранение...', 'saving');

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('student_id', cell.dataset.studentId);
            formData.append('lesson_id', cell.dataset.lessonId);
            formData.append('mark', cell.dataset.mark || '');
            formData.append('activity', cell.dataset.activity === '1' ? '1' : '0');
            formData.append('late', cell.dataset.late === '1' ? '1' : '0');

            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    body: formData,
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Не удалось сохранить отметку.');
                }

                cell.dataset.mark = data.entry.mark || '';
                cell.dataset.activity = data.entry.activity ? '1' : '0';
                cell.dataset.late = data.entry.late ? '1' : '0';
                syncCellUi(cell);
                updateTotals(data.totals);
                setStatus('Отметка сохранена.', 'success');
                return true;
            } catch (error) {
                setStatus(error.message || 'Ошибка сохранения.', 'error');
                return false;
            } finally {
                delete cell.dataset.saving;
            }
        };

        journal.querySelectorAll('[data-journal-cell]').forEach((cell) => {
            const trigger = cell.querySelector('[data-mark-trigger]');
            const menu = cell.querySelector('[data-mark-menu]');

            if (trigger && menu) {
                trigger.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const willOpen = menu.hidden;
                    closeAllMenus(willOpen ? menu : null);
                    menu.hidden = !willOpen;
                    trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });
            }

            cell.querySelectorAll('[data-mark-option]').forEach((option) => {
                option.addEventListener('click', async (event) => {
                    event.stopPropagation();
                    cell.dataset.mark = option.dataset.markOption || '';
                    closeAllMenus();
                    syncCellUi(cell);
                    await saveCell(cell);
                });
            });

            cell.querySelectorAll('[data-flag]').forEach((button) => {
                button.addEventListener('click', async (event) => {
                    event.stopPropagation();
                    if (button.disabled) {
                        return;
                    }

                    const flag = button.dataset.flag;
                    const key = flag === 'activity' ? 'activity' : 'late';
                    cell.dataset[key] = cell.dataset[key] === '1' ? '0' : '1';
                    syncCellUi(cell);
                    await saveCell(cell);
                });
            });

            syncCellUi(cell);
        });

        document.addEventListener('click', () => closeAllMenus());
    }

    document.querySelectorAll('.journal-table-wrap').forEach((wrap) => {
        let isDragging = false;
        let startX = 0;
        let startScrollLeft = 0;
        let moved = false;

        const isInteractive = (target) => {
            if (!(target instanceof Element)) {
                return false;
            }

            return Boolean(
                target.closest(
                    'button, a, input, select, textarea, label, .journal-mark, .journal-flags, [data-mark-trigger], [data-flag], [data-mark-menu], [data-mark-option]'
                )
            );
        };

        wrap.addEventListener('mousedown', (event) => {
            if (event.button !== 0 || isInteractive(event.target)) {
                return;
            }

            isDragging = true;
            moved = false;
            startX = event.pageX;
            startScrollLeft = wrap.scrollLeft;
            wrap.classList.add('is-dragging');
            wrap.querySelectorAll('.is-col-hover').forEach((cell) => {
                cell.classList.remove('is-col-hover');
            });
            event.preventDefault();
        });

        window.addEventListener('mousemove', (event) => {
            if (!isDragging) {
                return;
            }

            const delta = event.pageX - startX;
            if (Math.abs(delta) > 3) {
                moved = true;
            }
            wrap.scrollLeft = startScrollLeft - delta;
        });

        const stopDragging = () => {
            if (!isDragging) {
                return;
            }

            isDragging = false;
            wrap.classList.remove('is-dragging');
        };

        window.addEventListener('mouseup', stopDragging);

        wrap.addEventListener('click', (event) => {
            if (!moved) {
                return;
            }
            if (isInteractive(event.target)) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            moved = false;
        }, true);
    });

    const lessonModal = document.querySelector('[data-lesson-modal]');
    if (lessonModal) {
        const form = lessonModal.querySelector('[data-lesson-modal-form]');
        const titleNode = lessonModal.querySelector('[data-lesson-modal-title]');
        const actionInput = lessonModal.querySelector('[data-lesson-action]');
        const lessonIdInput = lessonModal.querySelector('[data-lesson-id]');
        const dateInput = lessonModal.querySelector('[data-lesson-date]');
        const topicSelect = lessonModal.querySelector('[data-lesson-topic]');
        const gradeTypeSelect = lessonModal.querySelector('[data-lesson-grade-type]');
        const submitBtn = lessonModal.querySelector('[data-lesson-submit]');

        const openModal = (button) => {
            const mode = button.dataset.mode || 'add';
            const isEdit = mode === 'edit';

            if (titleNode) {
                titleNode.textContent = isEdit ? 'Редактировать дату' : 'Добавить дату';
            }
            if (actionInput) {
                actionInput.value = isEdit ? 'update_lesson' : 'add_lesson';
            }
            if (lessonIdInput) {
                lessonIdInput.value = button.dataset.lessonId || '';
            }
            if (dateInput) {
                dateInput.value = button.dataset.date || dateInput.value;
            }
            if (topicSelect) {
                const topicId = button.dataset.topicId || '';
                const selectedTopicId = topicId && topicId !== '0' ? topicId : '';

                Array.from(topicSelect.options).forEach((option) => {
                    if (!option.value) {
                        option.disabled = false;
                        return;
                    }

                    const completed = option.dataset.completed === '1';
                    const independent = option.dataset.independent === '1';
                    option.disabled = independent
                        || (completed && !(isEdit && option.value === selectedTopicId));
                });

                topicSelect.value = selectedTopicId;
                if (selectedTopicId && topicSelect.value !== selectedTopicId) {
                    topicSelect.value = '';
                }
            }
            if (gradeTypeSelect) {
                gradeTypeSelect.value = button.dataset.gradeType || 'current';
            }
            if (submitBtn) {
                submitBtn.textContent = isEdit ? 'Сохранить' : 'Добавить';
            }

            lessonModal.hidden = false;
            document.body.classList.add('modal-open');
        };

        const closeModal = () => {
            lessonModal.hidden = true;
            document.body.classList.remove('modal-open');
        };

        document.querySelectorAll('[data-lesson-modal-open]').forEach((button) => {
            button.addEventListener('click', () => openModal(button));
        });

        lessonModal.querySelectorAll('[data-lesson-modal-close]').forEach((node) => {
            node.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !lessonModal.hidden) {
                closeModal();
            }
        });
    }

    const gradingSettings = document.querySelector('[data-grading-settings]');
    if (gradingSettings) {
        const systemSelect = gradingSettings.querySelector('[data-grading-system]');
        const brsBlock = gradingSettings.querySelector('[data-grading-brs]');
        const syncBrsVisibility = () => {
            if (!systemSelect || !brsBlock) {
                return;
            }
            brsBlock.hidden = systemSelect.value !== 'brs';
        };
        if (systemSelect) {
            systemSelect.addEventListener('change', syncBrsVisibility);
            syncBrsVisibility();
        }
    }

    const ktpSortable = document.querySelector('[data-ktp-sortable]');
    if (ktpSortable) {
        const statusNode = document.querySelector('[data-ktp-reorder-status]');
        const reorderUrl = ktpSortable.dataset.reorderUrl;
        const csrfToken = ktpSortable.dataset.csrf;
        const itemId = ktpSortable.dataset.itemId;
        let dragRow = null;
        let saveTimer = null;

        const setStatus = (text, isError = false) => {
            if (!statusNode) {
                return;
            }
            statusNode.hidden = !text;
            statusNode.textContent = text || '';
            statusNode.classList.toggle('ktp-reorder-status--error', isError);
            statusNode.classList.toggle('ktp-reorder-status--ok', Boolean(text) && !isError);
        };

        const refreshNumbers = () => {
            Array.from(ktpSortable.querySelectorAll('[data-ktp-num]')).forEach((cell, index) => {
                cell.textContent = String(index + 1);
            });
        };

        const collectOrder = () => Array.from(ktpSortable.querySelectorAll('[data-topic-id]'))
            .map((row) => row.dataset.topicId);

        const saveOrder = () => {
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('item_id', itemId);
            formData.append('order', JSON.stringify(collectOrder()));

            setStatus('Сохранение порядка…');

            fetch(reorderUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        throw new Error(data.error || 'Не удалось сохранить порядок.');
                    }
                    setStatus('Порядок сохранён');
                    window.clearTimeout(saveTimer);
                    saveTimer = window.setTimeout(() => setStatus(''), 1800);
                })
                .catch((error) => {
                    setStatus(error.message || 'Ошибка сохранения порядка.', true);
                });
        };

        ktpSortable.querySelectorAll('.ktp-sortable-row').forEach((row) => {
            const handle = row.querySelector('.ktp-drag-handle');

            if (handle) {
                handle.addEventListener('mousedown', () => {
                    row.setAttribute('draggable', 'true');
                });
                handle.addEventListener('mouseup', () => {
                    row.setAttribute('draggable', 'false');
                });
            }

            row.setAttribute('draggable', 'false');

            row.addEventListener('dragstart', (event) => {
                if (row.getAttribute('draggable') !== 'true') {
                    event.preventDefault();
                    return;
                }

                dragRow = row;
                row.classList.add('ktp-sortable-row--dragging');
                ktpSortable.classList.add('ktp-sortable--active');
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', row.dataset.topicId || '');
                }
            });

            row.addEventListener('dragend', () => {
                row.classList.remove('ktp-sortable-row--dragging');
                ktpSortable.classList.remove('ktp-sortable--active');
                ktpSortable.querySelectorAll('.ktp-sortable-row--over').forEach((item) => {
                    item.classList.remove('ktp-sortable-row--over');
                });
                row.setAttribute('draggable', 'false');
                dragRow = null;
                refreshNumbers();
                saveOrder();
            });

            row.addEventListener('dragover', (event) => {
                event.preventDefault();
                if (!dragRow || dragRow === row) {
                    return;
                }

                const bounds = row.getBoundingClientRect();
                const before = event.clientY < bounds.top + bounds.height / 2;
                ktpSortable.querySelectorAll('.ktp-sortable-row--over').forEach((item) => {
                    item.classList.remove('ktp-sortable-row--over');
                });
                row.classList.add('ktp-sortable-row--over');

                if (before) {
                    ktpSortable.insertBefore(dragRow, row);
                } else {
                    ktpSortable.insertBefore(dragRow, row.nextSibling);
                }
            });

            row.addEventListener('drop', (event) => {
                event.preventDefault();
            });
        });
    }

    const ktpEditModal = document.querySelector('[data-ktp-edit-modal]');
    if (ktpEditModal) {
        const titleInput = ktpEditModal.querySelector('[data-ktp-edit-title]');
        const typeSelect = ktpEditModal.querySelector('[data-ktp-edit-type]');
        const hoursInput = ktpEditModal.querySelector('[data-ktp-edit-hours]');
        const idInput = ktpEditModal.querySelector('[data-ktp-edit-id]');

        const attestationLabels = {
            diff_credit: 'Промежуточная аттестация. Дифференцированный зачёт',
            credit: 'Промежуточная аттестация. Зачёт',
            exam: 'Промежуточная аттестация. Экзамен',
            control: 'Промежуточная аттестация. Контрольная работа',
        };

        const syncAttestationTitle = () => {
            if (!typeSelect || !titleInput) {
                return;
            }
            const value = typeSelect.value;
            if (attestationLabels[value]) {
                titleInput.value = attestationLabels[value];
                titleInput.readOnly = true;
            } else {
                titleInput.readOnly = false;
            }
        };

        const openKtpEdit = (button) => {
            if (idInput) {
                idInput.value = button.dataset.topicId || '';
            }
            if (titleInput) {
                titleInput.value = button.dataset.title || '';
            }
            if (typeSelect) {
                typeSelect.value = button.dataset.lessonType || 'lecture';
            }
            if (hoursInput) {
                hoursInput.value = button.dataset.hours || '1';
            }
            syncAttestationTitle();
            if (titleInput && attestationLabels[typeSelect.value]) {
                titleInput.value = attestationLabels[typeSelect.value];
            }
            ktpEditModal.hidden = false;
            document.body.classList.add('modal-open');
            if (titleInput && !titleInput.readOnly) {
                titleInput.focus();
            }
        };

        const closeKtpEdit = () => {
            ktpEditModal.hidden = true;
            document.body.classList.remove('modal-open');
        };

        if (typeSelect) {
            typeSelect.addEventListener('change', syncAttestationTitle);
        }

        document.querySelectorAll('[data-ktp-edit-open]').forEach((button) => {
            button.addEventListener('click', () => openKtpEdit(button));
        });

        ktpEditModal.querySelectorAll('[data-ktp-edit-close]').forEach((node) => {
            node.addEventListener('click', closeKtpEdit);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !ktpEditModal.hidden) {
                closeKtpEdit();
            }
        });
    }

    const profileView = document.querySelector('[data-profile-view]');
    const profileEdit = document.querySelector('[data-profile-edit]');
    if (profileView && profileEdit) {
        const openEdit = () => {
            profileView.hidden = true;
            profileEdit.hidden = false;
            const firstInput = profileEdit.querySelector('input, textarea, select');
            if (firstInput) {
                firstInput.focus();
            }
        };
        const closeEdit = () => {
            profileEdit.hidden = true;
            profileView.hidden = false;
        };

        document.querySelectorAll('[data-profile-edit-open]').forEach((button) => {
            button.addEventListener('click', openEdit);
        });
        document.querySelectorAll('[data-profile-edit-cancel]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                closeEdit();
            });
        });
    }

    const residenceSelect = document.querySelector('[data-student-residence]');
    const addressRegistered = document.querySelector('[data-student-address-registered]');
    const addressActual = document.querySelector('[data-student-address-actual]');
    const addressParts = document.querySelectorAll('[data-student-address-part]');

    const applySnilsMask = (value) => {
        const digits = String(value).replace(/\D/g, '').slice(0, 11);
        const part1 = digits.slice(0, 3);
        const part2 = digits.slice(3, 6);
        const part3 = digits.slice(6, 9);
        const part4 = digits.slice(9, 11);

        if (digits.length <= 3) return part1;
        if (digits.length <= 6) return part1 + '-' + part2;
        if (digits.length <= 9) return part1 + '-' + part2 + '-' + part3;
        return part1 + '-' + part2 + '-' + part3 + ' ' + part4;
    };

    document.querySelectorAll('input[data-snils-mask]').forEach((input) => {
        input.addEventListener('input', () => {
            const current = input.value || '';
            input.value = applySnilsMask(current);
        });
    });

    const normalizePhoneDigits = (value) => {
        let digits = String(value).replace(/\D/g, '');
        if (digits.length === 11 && digits[0] === '8') {
            digits = '7' + digits.slice(1);
        }
        return digits;
    };

    const formatLoginPhone = (value) => {
        let digits = normalizePhoneDigits(value);
        if (digits.length === 10) {
            digits = '7' + digits;
        }
        if (digits === '') {
            return '';
        }
        return '+' + digits.slice(0, 11);
    };

    document.querySelectorAll('input[data-phone-login]').forEach((input) => {
        const stripSpaces = () => {
            input.value = input.value.replace(/\s+/g, '');
        };

        input.addEventListener('input', stripSpaces);

        input.addEventListener('blur', () => {
            input.value = formatLoginPhone(input.value);
        });

        const form = input.closest('form');
        if (form) {
            form.addEventListener('submit', () => {
                input.value = formatLoginPhone(input.value);
            });
        }

        if (input.value.trim() !== '') {
            input.value = formatLoginPhone(input.value);
        }
    });

    const composeRegisteredAddress = () => {
        const region = document.getElementById('address_region');
        const district = document.getElementById('address_district');
        const locality = document.getElementById('address_locality');
        const street = document.getElementById('address_street');
        const house = document.getElementById('address_house');
        const parts = [];
        [region, district, locality, street].forEach((input) => {
            const value = input ? input.value.trim() : '';
            if (value !== '') {
                parts.push(value);
            }
        });
        const houseValue = house ? house.value.trim() : '';
        if (houseValue !== '') {
            parts.push(/^д\.?\s*/i.test(houseValue) ? houseValue : ('д. ' + houseValue));
        }
        return parts.join(', ');
    };

    const syncRegisteredAddress = () => {
        if (!addressRegistered) {
            return '';
        }
        const composed = composeRegisteredAddress();
        addressRegistered.value = composed;
        return composed;
    };

    if (residenceSelect && addressActual) {
        let lastAutoAddress = '';
        const dormitoryAddress = (residenceSelect.getAttribute('data-dormitory-address') || 'Общежитие').trim();

        const resolveAutoAddress = () => {
            const type = residenceSelect.value;
            if (type === 'family') {
                return syncRegisteredAddress();
            }
            if (type === 'dormitory') {
                return dormitoryAddress;
            }
            return null;
        };

        const applyAutoAddress = (force) => {
            const next = resolveAutoAddress();
            if (next === null) {
                return;
            }
            const current = addressActual.value.trim();
            if (force || current === '' || current === lastAutoAddress) {
                addressActual.value = next;
                lastAutoAddress = next;
            }
        };

        if (addressActual.value.trim() !== '') {
            const initial = addressActual.value.trim();
            const registeredVal = syncRegisteredAddress() || (addressRegistered ? addressRegistered.value.trim() : '');
            if (
                (residenceSelect.value === 'family' && initial === registeredVal)
                || (residenceSelect.value === 'dormitory' && initial === dormitoryAddress)
            ) {
                lastAutoAddress = initial;
            }
        }

        residenceSelect.addEventListener('change', () => applyAutoAddress(true));
        addressParts.forEach((input) => {
            input.addEventListener('input', () => {
                syncRegisteredAddress();
                if (residenceSelect.value === 'family') {
                    applyAutoAddress(false);
                }
            });
        });
    } else if (addressParts.length && addressRegistered) {
        addressParts.forEach((input) => {
            input.addEventListener('input', syncRegisteredAddress);
        });
    }

    const avatarForm = document.querySelector('[data-avatar-form]');
    if (avatarForm) {
        const iconsBlock = avatarForm.querySelector('[data-avatar-icons]');
        const uploadBlock = avatarForm.querySelector('[data-avatar-upload]');
        const modeInputs = avatarForm.querySelectorAll('[data-avatar-mode]');

        const syncAvatarMode = () => {
            const mode = avatarForm.querySelector('[data-avatar-mode]:checked');
            const isUpload = mode && mode.value === 'upload';
            if (iconsBlock) {
                iconsBlock.hidden = !!isUpload;
            }
            if (uploadBlock) {
                uploadBlock.hidden = !isUpload;
            }
            avatarForm.querySelectorAll('.avatar-mode-tabs__item').forEach((item) => {
                const input = item.querySelector('input');
                item.classList.toggle('is-active', !!(input && input.checked));
            });
        };

        modeInputs.forEach((input) => {
            input.addEventListener('change', syncAvatarMode);
        });
        syncAvatarMode();
    }

    document.querySelectorAll('[data-settings-block]').forEach((block) => {
        const view = block.querySelector('[data-settings-view]');
        const edit = block.querySelector('[data-settings-edit]');
        if (!view || !edit) {
            return;
        }

        const openEdit = () => {
            view.hidden = true;
            edit.hidden = false;
            const firstInput = edit.querySelector('input, textarea, select');
            if (firstInput) {
                firstInput.focus();
            }
        };
        const closeEdit = () => {
            edit.hidden = true;
            view.hidden = false;
        };

        block.querySelectorAll('[data-settings-edit-open]').forEach((button) => {
            button.addEventListener('click', openEdit);
        });
        block.querySelectorAll('[data-settings-edit-cancel]').forEach((button) => {
            button.addEventListener('click', closeEdit);
        });
    });

    const teachersSearch = document.querySelector('[data-teachers-search]');
    const teachersTable = document.querySelector('[data-teachers-table]');
    if (teachersSearch && teachersTable) {
        const rows = Array.from(teachersTable.querySelectorAll('[data-teacher-row]'));
        const emptyNode = document.querySelector('[data-teachers-empty]');

        const filterTeachers = () => {
            const query = teachersSearch.value.trim().toLowerCase();
            let visible = 0;

            rows.forEach((row) => {
                const name = row.dataset.teacherName || '';
                const match = query === '' || name.indexOf(query) !== -1;
                row.hidden = !match;
                if (match) {
                    visible += 1;
                    const numCell = row.querySelector('[data-teacher-num]');
                    if (numCell) {
                        numCell.textContent = String(visible);
                    }
                }
            });

            if (emptyNode) {
                emptyNode.hidden = visible > 0;
            }
            teachersTable.hidden = visible === 0;
        };

        teachersSearch.addEventListener('input', filterTeachers);
    }

    const curriculumEditModal = document.querySelector('[data-curriculum-edit-modal]');
    if (curriculumEditModal) {
        const idInput = curriculumEditModal.querySelector('[data-curriculum-edit-id]');
        const subjectInput = curriculumEditModal.querySelector('[data-curriculum-edit-subject]');
        const semesterSelect = curriculumEditModal.querySelector('[data-curriculum-edit-semester]');
        const teacherSelect = curriculumEditModal.querySelector('[data-curriculum-edit-teacher]');

        const openCurriculumEdit = (button) => {
            if (idInput) {
                idInput.value = button.dataset.itemId || '';
            }
            if (subjectInput) {
                subjectInput.value = button.dataset.subjectName || '';
            }
            if (semesterSelect) {
                semesterSelect.value = button.dataset.semester || '1';
            }
            if (teacherSelect) {
                const teacherId = button.dataset.teacherId || '';
                teacherSelect.value = teacherId === '0' ? '' : teacherId;
            }
            curriculumEditModal.hidden = false;
            document.body.classList.add('modal-open');
            if (subjectInput) {
                subjectInput.focus();
            }
        };

        const closeCurriculumEdit = () => {
            curriculumEditModal.hidden = true;
            document.body.classList.remove('modal-open');
        };

        document.querySelectorAll('[data-curriculum-edit-open]').forEach((button) => {
            button.addEventListener('click', () => openCurriculumEdit(button));
        });

        curriculumEditModal.querySelectorAll('[data-curriculum-edit-close]').forEach((node) => {
            node.addEventListener('click', closeCurriculumEdit);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !curriculumEditModal.hidden) {
                closeCurriculumEdit();
            }
        });
    }

    const archiveEditModal = document.querySelector('[data-archive-edit-modal]');
    if (archiveEditModal) {
        const studentInput = archiveEditModal.querySelector('[data-archive-edit-student]');
        const itemInput = archiveEditModal.querySelector('[data-archive-edit-item]');
        const gradeSelect = archiveEditModal.querySelector('[data-archive-edit-grade]');
        const metaNode = archiveEditModal.querySelector('[data-archive-edit-meta]');
        const reasonCode = archiveEditModal.querySelector('[data-archive-reason-code]');
        const reasonWrap = archiveEditModal.querySelector('[data-archive-reason-text-wrap]');
        const reasonText = archiveEditModal.querySelector('[data-archive-reason-text]');

        const toggleReasonText = () => {
            const showOther = reasonCode && reasonCode.value === 'other';
            if (reasonWrap) {
                reasonWrap.hidden = !showOther;
            }
            if (reasonText) {
                reasonText.required = Boolean(showOther);
            }
        };

        const openArchiveEdit = (button) => {
            if (studentInput) {
                studentInput.value = button.dataset.studentId || '';
            }
            if (itemInput) {
                itemInput.value = button.dataset.itemId || '';
            }
            if (gradeSelect) {
                gradeSelect.value = button.dataset.currentGrade || '5';
            }
            if (metaNode) {
                metaNode.textContent = (button.dataset.studentName || '')
                    + ' · '
                    + (button.dataset.subjectName || '')
                    + (button.dataset.currentGrade ? ' · сейчас: ' + button.dataset.currentGrade : '');
            }
            if (reasonText) {
                reasonText.value = '';
            }
            toggleReasonText();
            archiveEditModal.hidden = false;
            document.body.classList.add('modal-open');
        };

        const closeArchiveEdit = () => {
            archiveEditModal.hidden = true;
            document.body.classList.remove('modal-open');
        };

        document.querySelectorAll('[data-archive-edit-open]').forEach((button) => {
            button.addEventListener('click', () => openArchiveEdit(button));
        });
        archiveEditModal.querySelectorAll('[data-archive-edit-close]').forEach((node) => {
            node.addEventListener('click', closeArchiveEdit);
        });
        if (reasonCode) {
            reasonCode.addEventListener('change', toggleReasonText);
        }
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !archiveEditModal.hidden) {
                closeArchiveEdit();
            }
        });
    }

    const archiveHistoryModal = document.querySelector('[data-archive-history-modal]');
    if (archiveHistoryModal) {
        const body = archiveHistoryModal.querySelector('[data-archive-history-body]');

        const closeArchiveHistory = () => {
            archiveHistoryModal.hidden = true;
            document.body.classList.remove('modal-open');
        };

        document.querySelectorAll('[data-archive-history-open]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                let rows = [];
                try {
                    rows = JSON.parse(button.dataset.history || '[]');
                } catch (error) {
                    rows = [];
                }
                if (body) {
                    body.innerHTML = '';
                    rows.forEach((row) => {
                        const item = document.createElement('div');
                        item.className = 'archive-history-item';
                        const name = document.createElement('p');
                        const nameStrong = document.createElement('strong');
                        nameStrong.textContent = row.user || '—';
                        name.appendChild(nameStrong);
                        const at = document.createElement('p');
                        at.className = 'text-muted';
                        at.textContent = row.at || '';
                        const grades = document.createElement('p');
                        grades.textContent = (row.from || '—') + ' → ' + (row.to || '—');
                        const reason = document.createElement('p');
                        reason.textContent = row.reason || '';
                        item.appendChild(name);
                        item.appendChild(at);
                        item.appendChild(grades);
                        item.appendChild(reason);
                        body.appendChild(item);
                    });
                }
                archiveHistoryModal.hidden = false;
                document.body.classList.add('modal-open');
            });
        });

        archiveHistoryModal.querySelectorAll('[data-archive-history-close]').forEach((node) => {
            node.addEventListener('click', closeArchiveHistory);
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !archiveHistoryModal.hidden) {
                closeArchiveHistory();
            }
        });
    }

    const archiveDeleteModal = document.querySelector('[data-archive-delete-modal]');
    if (archiveDeleteModal) {
        const form = archiveDeleteModal.querySelector('[data-archive-delete-form]');
        const actionInput = archiveDeleteModal.querySelector('[data-archive-delete-action]');
        const yearInput = archiveDeleteModal.querySelector('[data-archive-delete-year]');
        const semesterInput = archiveDeleteModal.querySelector('[data-archive-delete-semester]');
        const metaNode = archiveDeleteModal.querySelector('[data-archive-delete-meta]');
        const ackInput = archiveDeleteModal.querySelector('[data-archive-delete-ack]');
        const textInput = archiveDeleteModal.querySelector('[data-archive-delete-text]');

        const closeArchiveDelete = () => {
            archiveDeleteModal.hidden = true;
            document.body.classList.remove('modal-open');
            if (ackInput) {
                ackInput.checked = false;
            }
            if (textInput) {
                textInput.value = '';
            }
        };

        document.querySelectorAll('[data-archive-delete-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const type = button.dataset.type === 'journal' ? 'journal' : 'gradebook';
                if (actionInput) {
                    actionInput.value = type === 'journal' ? 'delete_journals' : 'delete_gradebooks';
                }
                if (yearInput) {
                    yearInput.value = button.dataset.year || '';
                }
                if (semesterInput) {
                    semesterInput.value = button.dataset.semester || '';
                }
                if (metaNode) {
                    metaNode.textContent = 'Будут удалены ' + (button.dataset.label || '');
                }
                archiveDeleteModal.hidden = false;
                document.body.classList.add('modal-open');
                if (textInput) {
                    textInput.focus();
                }
            });
        });

        archiveDeleteModal.querySelectorAll('[data-archive-delete-close]').forEach((node) => {
            node.addEventListener('click', closeArchiveDelete);
        });

        if (form) {
            form.addEventListener('submit', (event) => {
                const typed = ((textInput && textInput.value) || '').trim().toUpperCase();
                if (!ackInput || !ackInput.checked || typed !== 'УДАЛИТЬ') {
                    event.preventDefault();
                    window.alert('Отметьте согласие и введите слово УДАЛИТЬ.');
                    return;
                }
                if (textInput) {
                    textInput.value = 'УДАЛИТЬ';
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !archiveDeleteModal.hidden) {
                closeArchiveDelete();
            }
        });
    }

    const glazPage = document.querySelector('.glaz-page');
    if (glazPage) {
        const printBtn = glazPage.querySelector('[data-glaz-print]');
        const commissionTemplate = document.getElementById('glaz-commission-row-template');

        const updateCommissionControls = (commissionBlock) => {
            const list = commissionBlock.querySelector('[data-glaz-commission-list]');
            const addBtn = commissionBlock.querySelector('[data-glaz-commission-add]');
            const max = parseInt(commissionBlock.dataset.max || '3', 10);
            const rows = list ? list.querySelectorAll('.glaz-commission__row') : [];

            if (addBtn) {
                addBtn.hidden = rows.length >= max;
            }

            rows.forEach((row, index) => {
                const removeBtn = row.querySelector('[data-glaz-commission-remove]');
                if (!removeBtn) {
                    return;
                }
                removeBtn.hidden = rows.length <= 1;
            });
        };

        glazPage.querySelectorAll('[data-glaz-commission]').forEach(updateCommissionControls);

        glazPage.addEventListener('click', (event) => {
            const addBtn = event.target.closest('[data-glaz-commission-add]');
            if (addBtn) {
                const commissionBlock = addBtn.closest('[data-glaz-commission]');
                const list = commissionBlock?.querySelector('[data-glaz-commission-list]');
                const max = parseInt(commissionBlock?.dataset.max || '3', 10);

                if (!list || !commissionTemplate || list.children.length >= max) {
                    return;
                }

                const row = commissionTemplate.content.firstElementChild.cloneNode(true);
                const select = row.querySelector('select');
                if (select) {
                    select.selectedIndex = 0;
                }
                list.appendChild(row);
                updateCommissionControls(commissionBlock);
                return;
            }

            const removeBtn = event.target.closest('[data-glaz-commission-remove]');
            if (removeBtn) {
                const commissionBlock = removeBtn.closest('[data-glaz-commission]');
                const row = removeBtn.closest('.glaz-commission__row');
                const list = commissionBlock?.querySelector('[data-glaz-commission-list]');

                if (!row || !list || list.children.length <= 1) {
                    return;
                }

                row.remove();
                updateCommissionControls(commissionBlock);
            }
        });

        if (printBtn) {
            printBtn.addEventListener('click', () => window.print());
        }

        glazPage.querySelectorAll('[data-glaz-form]').forEach((form) => {
            const statusNode = form.querySelector('[data-glaz-status]');
            const displayNode = glazPage.querySelector(
                `[data-glaz-display="${form.dataset.debtKey}"]`
            );

            const setStatus = (message, state = '') => {
                if (!statusNode) {
                    return;
                }
                statusNode.textContent = message;
                statusNode.dataset.state = state;
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                setStatus('Сохранение...', '');

                try {
                    const response = await fetch(form.dataset.saveUrl, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            Accept: 'application/json',
                        },
                    });
                    const data = await response.json();

                    if (!data.success) {
                        setStatus(data.error || 'Ошибка сохранения.', 'error');
                        return;
                    }

                    if (displayNode) {
                        const text = data.schedule?.display_text || '';
                        displayNode.innerHTML = text !== ''
                            ? text.replace(/\n/g, '<br>')
                            : '<span class="text-muted">—</span>';
                    }

                    setStatus('Сохранено', 'success');
                } catch (error) {
                    setStatus('Ошибка сети.', 'error');
                }
            });
        });
    }
});
