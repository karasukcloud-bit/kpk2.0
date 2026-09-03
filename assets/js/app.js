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
    const attendanceCancel = document.querySelector('[data-attendance-cancel]');

    const hideAttendanceForm = () => {
        if (!attendanceForm) {
            return;
        }
        attendanceForm.classList.add('attendance-form--hidden');
        if (attendanceToggle) {
            attendanceToggle.textContent = 'Добавить дату';
        }
    };

    if (attendanceToggle && attendanceForm) {
        attendanceToggle.addEventListener('click', () => {
            const hidden = attendanceForm.classList.toggle('attendance-form--hidden');
            attendanceToggle.textContent = hidden ? 'Добавить дату' : 'Скрыть форму';
        });
    }

    if (attendanceCancel) {
        attendanceCancel.addEventListener('click', hideAttendanceForm);
    }

    attendanceForm?.addEventListener('blur', (event) => {
        const input = event.target.closest('.attendance-input-num');
        if (!input || input.value.trim() !== '') {
            return;
        }
        input.value = '0';
    }, true);

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

    document.querySelectorAll('[data-ktp-sortable]').forEach((ktpSortable) => {
        const statusSelector = ktpSortable.dataset.reorderStatus || '[data-ktp-reorder-status]';
        const statusNode = document.querySelector(statusSelector);
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
            statusNode.classList.toggle('alert', isError);
            statusNode.classList.toggle('alert--error', isError);
            if (text && !isError) {
                window.clearTimeout(saveTimer);
                saveTimer = window.setTimeout(() => {
                    statusNode.hidden = true;
                    statusNode.textContent = '';
                    statusNode.classList.remove('ktp-reorder-status--ok');
                }, 1800);
            }
        };

        const refreshNumbers = () => {
            if (ktpSortable.querySelector('[data-ktp-row]')) {
                let num = 0;
                ktpSortable.querySelectorAll('[data-ktp-row]').forEach((row) => {
                    const numCell = row.querySelector('[data-ktp-num], .ktp-col-num');
                    if (!numCell) {
                        return;
                    }
                    if (row.classList.contains('ktp-row--semester-marker')) {
                        numCell.textContent = '';
                        return;
                    }
                    num += 1;
                    numCell.textContent = String(num);
                });
                return;
            }

            let num = 0;
            Array.from(ktpSortable.querySelectorAll('[data-ktp-num]')).forEach((cell) => {
                num += 1;
                cell.textContent = String(num);
            });
        };

        const collectOrder = () => Array.from(ktpSortable.querySelectorAll(':scope > tr[data-topic-id]'))
            .map((row) => row.dataset.topicId)
            .filter((id) => id && id !== '0');

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
                    // После drag DOM уже в нужном порядке — всегда пересчитываем сводку из него,
                    // чтобы ответ сервера не затирал корректное распределение на мгновение.
                    document.dispatchEvent(new CustomEvent('ktp-rows-changed'));
                    setStatus('Порядок сохранён');
                })
                .catch((error) => {
                    setStatus(error.message || 'Ошибка сохранения порядка.', true);
                });
        };

        const bindSortableRow = (row) => {
            if (row.dataset.sortableBound === '1') {
                return;
            }
            row.dataset.sortableBound = '1';

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
                // Сразу пересчитываем сводку по текущему DOM.
                document.dispatchEvent(new CustomEvent('ktp-rows-changed'));
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
        };

        ktpSortable.querySelectorAll('.ktp-sortable-row').forEach(bindSortableRow);

        ktpSortable.addEventListener('ktp-sortable-bind', (event) => {
            const row = event.detail?.row;
            if (row instanceof HTMLElement) {
                bindSortableRow(row);
            }
        });
    });

    document.querySelectorAll('[data-ktp-competency-block]').forEach((block) => {
        const updateCompetencySelected = () => {
            const okNode = block.querySelector('[data-ktp-selected-ok]');
            const pkNode = block.querySelector('[data-ktp-selected-pk]');
            const collectLabels = (selector) => Array.from(block.querySelectorAll(selector))
                .filter((input) => input.checked)
                .map((input) => input.dataset.competencyLabel || input.value);
            const sortLabels = (labels) => labels.sort((left, right) => {
                const leftNum = parseInt(String(left).replace(/\D/g, ''), 10) || 0;
                const rightNum = parseInt(String(right).replace(/\D/g, ''), 10) || 0;
                return leftNum - rightNum;
            });

            if (okNode) {
                const okLabels = sortLabels(collectLabels('input[name="ok_codes[]"]'));
                okNode.textContent = okLabels.length > 0 ? okLabels.join(', ') : '—';
            }
            if (pkNode) {
                const pkLabels = sortLabels(collectLabels('input[name="pk_codes[]"]'));
                pkNode.textContent = pkLabels.length > 0 ? pkLabels.join(', ') : '—';
            }
        };

        block.addEventListener('change', (event) => {
            if (event.target.matches('input[name="ok_codes[]"], input[name="pk_codes[]"]')) {
                updateCompetencySelected();
            }
        });
        updateCompetencySelected();
    });

    const ktpTopicForm = document.querySelector('[data-ktp-topic-form]');
    const supportsKtpOrientation = (type) => type === 'lecture' || type === 'practice';

    const syncKtpOrientationFields = (root) => {
        if (!root || root.dataset.ktpProfessionality !== '1') {
            return;
        }
        const typeSelect = root.querySelector('[data-ktp-lesson-type], [data-ktp-edit-type]');
        const type = typeSelect ? typeSelect.value : 'lecture';
        root.querySelectorAll('[data-ktp-orientation-field]').forEach((field) => {
            field.hidden = !supportsKtpOrientation(type);
        });
    };

    if (ktpTopicForm) {
        const topicTypeSelect = ktpTopicForm.querySelector('[data-ktp-lesson-type]');
        syncKtpOrientationFields(ktpTopicForm);
        if (topicTypeSelect) {
            topicTypeSelect.addEventListener('change', () => syncKtpOrientationFields(ktpTopicForm));
        }
    }

    const ktpEditModal = document.querySelector('[data-ktp-edit-modal]');
    if (ktpEditModal) {
        const titleInput = ktpEditModal.querySelector('[data-ktp-edit-title]');
        const typeSelect = ktpEditModal.querySelector('[data-ktp-edit-type]');
        const hoursInput = ktpEditModal.querySelector('[data-ktp-edit-hours]');
        const orientationInput = ktpEditModal.querySelector('[data-ktp-edit-orientation]');
        const idInput = ktpEditModal.querySelector('[data-ktp-edit-id]');
        const deadlineInput = ktpEditModal.querySelector('[data-ktp-edit-deadline]');
        const controlSelect = ktpEditModal.querySelector('[data-ktp-edit-control]');
        const competencyBlock = ktpEditModal.querySelector('[data-ktp-competency-block]');

        const setCompetencyCheckboxes = (rawValue, selector) => {
            if (!competencyBlock) {
                return;
            }
            const values = String(rawValue || '')
                .split(',')
                .map((item) => item.trim())
                .filter(Boolean);
            competencyBlock.querySelectorAll(selector).forEach((input) => {
                input.checked = values.includes(input.value);
            });
            const trigger = competencyBlock.querySelector('input[name="ok_codes[]"], input[name="pk_codes[]"]');
            if (trigger) {
                trigger.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

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
            if (orientationInput) {
                orientationInput.value = button.dataset.orientationHours || '0';
            }
            if (deadlineInput) {
                deadlineInput.value = button.dataset.deadline || '';
            }
            setCompetencyCheckboxes(button.dataset.okCodes || '', 'input[name="ok_codes[]"]');
            setCompetencyCheckboxes(button.dataset.pkCodes || '', 'input[name="pk_codes[]"]');
            if (controlSelect) {
                controlSelect.value = button.dataset.controlForm || '';
            }
            syncAttestationTitle();
            syncKtpOrientationFields(ktpEditModal);
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
            typeSelect.addEventListener('change', () => {
                syncAttestationTitle();
                syncKtpOrientationFields(ktpEditModal);
            });
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

    const ktpRowsEditor = document.querySelector('[data-ktp-rows-editor]');
    if (ktpRowsEditor) {
        const body = ktpRowsEditor.querySelector('[data-ktp-rows-body]');
        const statusNode = document.querySelector('[data-ktp-rows-status]');
        const template = document.getElementById('ktp-rows-row-template');
        const actionUrl = ktpRowsEditor.dataset.actionUrl;
        const csrfToken = ktpRowsEditor.dataset.csrf;
        const itemId = ktpRowsEditor.dataset.itemId;
        const attestationTitles = {
            diff_credit: 'Промежуточная аттестация. Дифференцированный зачёт',
            credit: 'Промежуточная аттестация. Зачёт',
            exam: 'Промежуточная аттестация. Экзамен',
            control: 'Промежуточная аттестация. Контрольная работа',
        };
        let activeRow = body ? body.querySelector('[data-ktp-row]:last-child') : null;
        let statusTimer = null;
        const saveTimers = new WeakMap();

        const setStatus = (text, isError) => {
            if (!statusNode) {
                return;
            }
            statusNode.hidden = !text;
            statusNode.textContent = text || '';
            statusNode.classList.toggle('alert', !!isError);
            statusNode.classList.toggle('alert--error', !!isError);
            window.clearTimeout(statusTimer);
            if (text && !isError) {
                statusTimer = window.setTimeout(() => {
                    statusNode.hidden = true;
                    statusNode.textContent = '';
                }, 1800);
            }
        };

        const isAttestationType = (type) => Object.prototype.hasOwnProperty.call(attestationTitles, type);

        const formatSummaryNumber = (value) => {
            const num = Number(value);
            if (!Number.isFinite(num)) {
                return '0';
            }
            return String(num).replace(/\.0$/, '');
        };

        const setWorkloadCell = (workloadTable, rowKey, columnType, semesterIndex, text) => {
            let selector;
            if (columnType === 'course') {
                selector = '[data-ktp-workload-course="' + rowKey + '"]';
            } else {
                selector = '[data-ktp-workload-row="' + rowKey + '"][data-ktp-workload-sem="' + semesterIndex + '"]';
            }
            const cell = workloadTable.querySelector(selector);
            if (cell) {
                cell.textContent = text;
            }
        };

        const applyWorkloadPayload = (payload) => {
            const workloadTable = document.querySelector('[data-ktp-workload-table]');
            if (!workloadTable || !payload || !payload.rows) {
                return false;
            }

            const slots = Array.isArray(payload.semester_slots) ? payload.semester_slots : [];
            workloadTable.dataset.semesterSlots = slots.join(',');
            workloadTable.dataset.splitSemesters = payload.split_semesters ? '1' : '0';
            if (typeof payload.professionality === 'boolean') {
                workloadTable.dataset.professionality = payload.professionality ? '1' : '0';
            }

            Object.keys(payload.rows).forEach((rowKey) => {
                const rowData = payload.rows[rowKey] || {};
                setWorkloadCell(workloadTable, rowKey, 'course', 0, rowData.course || '—');
                const semesters = rowData.semesters || {};
                for (let semester = 1; semester <= 8; semester += 1) {
                    const value = semesters[String(semester)] || semesters[semester] || '—';
                    setWorkloadCell(workloadTable, rowKey, 'sem', semester, value);
                }
            });

            return true;
        };
        window.applyKtpWorkloadPayload = applyWorkloadPayload;

        const updateSummaryTable = () => {
            const workloadTable = document.querySelector('[data-ktp-workload-table]');
            if (!body || !workloadTable) {
                return;
            }

            const isProfessionality = workloadTable.dataset.professionality === '1';
            let semesterSlots = (workloadTable.dataset.semesterSlots || '')
                .split(',')
                .map((value) => Number(value))
                .filter((value) => Number.isFinite(value) && value > 0);

            const hasSemester2Marker = Array.from(
                body.querySelectorAll('.ktp-row--semester-marker')
            ).some((row) => {
                const markerType = row.getAttribute('data-lesson-type') || row.dataset.lessonType || '';
                if (markerType === 'semester_2') {
                    return true;
                }
                const text = (row.textContent || '').toLowerCase().replace(/\s+/g, ' ');
                return text.indexOf('2 семестр') !== -1;
            });
            let splitSemesters = workloadTable.dataset.splitSemesters === '1' || hasSemester2Marker;

            if (splitSemesters && semesterSlots.length < 2 && semesterSlots.length === 1) {
                const firstSlot = semesterSlots[0];
                const pairSlot = firstSlot % 2 === 1 ? firstSlot + 1 : firstSlot - 1;
                if (pairSlot >= 1 && pairSlot <= 8) {
                    semesterSlots = firstSlot % 2 === 1
                        ? [firstSlot, pairSlot]
                        : [pairSlot, firstSlot];
                    workloadTable.dataset.semesterSlots = semesterSlots.join(',');
                    workloadTable.dataset.splitSemesters = '1';
                }
            }

            const createBucket = () => ({
                lecture: { hours: 0, orient: 0 },
                practice: { hours: 0, orient: 0 },
                independent: { hours: 0, orient: 0 },
                attestation: { hours: 0, orient: 0 },
                attestationForms: [],
            });

            const firstBucket = createBucket();
            const secondBucket = createBucket();
            const attestationFormShort = {
                exam: 'Э',
                credit: 'З',
                diff_credit: 'ДЗ',
                control: 'КР',
            };

            const addToBucket = (bucket, lessonType, hours, orient) => {
                if (lessonType === 'lecture') {
                    bucket.lecture.hours += hours;
                    bucket.lecture.orient += orient;
                } else if (lessonType === 'practice') {
                    bucket.practice.hours += hours;
                    bucket.practice.orient += orient;
                } else if (lessonType === 'independent') {
                    bucket.independent.hours += hours;
                    bucket.independent.orient += orient;
                } else if (isAttestationType(lessonType)) {
                    bucket.attestation.hours += hours;
                    bucket.attestation.orient += orient;
                    const short = attestationFormShort[lessonType] || '';
                    if (short && hours > 0) {
                        bucket.attestationForms.push(short + ' (' + formatSummaryNumber(hours) + ' ч.)');
                    }
                }
            };

            let currentHalf = 1;
            body.querySelectorAll('[data-ktp-row]').forEach((row) => {
                if (row.classList.contains('ktp-row--semester-marker')) {
                    let markerType = row.getAttribute('data-lesson-type') || row.dataset.lessonType || '';
                    if (!markerType) {
                        const text = (row.textContent || '').toLowerCase().replace(/\s+/g, ' ');
                        if (text.indexOf('1 семестр') !== -1) {
                            markerType = 'semester_1';
                        } else if (text.indexOf('2 семестр') !== -1) {
                            markerType = 'semester_2';
                        }
                    }
                    if (markerType === 'semester_1') {
                        currentHalf = 1;
                        row.setAttribute('data-lesson-type', 'semester_1');
                    } else if (markerType === 'semester_2') {
                        currentHalf = 2;
                        row.setAttribute('data-lesson-type', 'semester_2');
                    }
                    return;
                }

                const typeSelect = row.querySelector('[data-ktp-field="lesson_type"]');
                const hoursInput = row.querySelector('[data-ktp-field="hours"]');
                const orientationInput = row.querySelector('[data-ktp-field="orientation_hours"]');
                const lessonType = typeSelect ? typeSelect.value : 'lecture';
                const hours = Number(hoursInput ? hoursInput.value : 0) || 0;
                const orient = Number(orientationInput ? orientationInput.value : 0) || 0;
                const bucket = splitSemesters && currentHalf === 2 ? secondBucket : firstBucket;

                addToBucket(bucket, lessonType, hours, orient);
            });

            const mergeBuckets = (...buckets) => {
                const merged = createBucket();
                buckets.forEach((bucket) => {
                    ['lecture', 'practice', 'independent', 'attestation'].forEach((key) => {
                        merged[key].hours += bucket[key].hours;
                        merged[key].orient += bucket[key].orient;
                    });
                    merged.attestationForms = merged.attestationForms.concat(bucket.attestationForms);
                });
                return merged;
            };

            const semesterBuckets = {};
            if (splitSemesters && semesterSlots.length >= 2) {
                semesterBuckets[semesterSlots[0]] = firstBucket;
                semesterBuckets[semesterSlots[1]] = secondBucket;
            } else if (semesterSlots.length > 0) {
                semesterBuckets[semesterSlots[0]] = mergeBuckets(firstBucket, secondBucket);
            }

            const totalBucket = mergeBuckets(firstBucket, secondBucket);

            const teacherMetrics = (bucket) => ({
                hours: bucket.lecture.hours + bucket.practice.hours + bucket.attestation.hours,
                orient: bucket.lecture.orient + bucket.practice.orient + bucket.attestation.orient,
            });

            const opHours = (bucket) => (
                bucket.lecture.hours
                + bucket.practice.hours
                + bucket.independent.hours
                + bucket.attestation.hours
            );

            const formatTotalOnly = (hours) => (hours > 0 ? formatSummaryNumber(hours) : '—');
            const formatPair = (hours, orient) => {
                if (hours <= 0 && orient <= 0) {
                    return '—';
                }
                const total = formatSummaryNumber(hours);
                if (!isProfessionality) {
                    return total;
                }
                return total + '/' + formatSummaryNumber(orient);
            };

            const buildSemesterDisplays = (getMetrics, formatValue) => {
                const displays = {};
                Object.keys(semesterBuckets).forEach((semesterKey) => {
                    const semesterIndex = Number(semesterKey);
                    const bucket = semesterBuckets[semesterIndex];
                    displays[semesterIndex] = formatValue(getMetrics(bucket));
                });
                return displays;
            };

            const updateWorkloadRow = (rowKey, totalDisplay, getMetrics, formatValue) => {
                const semesterDisplays = buildSemesterDisplays(getMetrics, formatValue);
                setWorkloadCell(workloadTable, rowKey, 'course', 0, totalDisplay);
                for (let semester = 1; semester <= 8; semester += 1) {
                    const value = Object.prototype.hasOwnProperty.call(semesterDisplays, semester)
                        ? semesterDisplays[semester]
                        : '—';
                    setWorkloadCell(workloadTable, rowKey, 'sem', semester, value);
                }
            };

            updateWorkloadRow(
                'op_volume',
                formatTotalOnly(opHours(totalBucket)),
                opHours,
                formatTotalOnly
            );
            updateWorkloadRow(
                'with_teacher',
                formatPair(teacherMetrics(totalBucket).hours, teacherMetrics(totalBucket).orient),
                teacherMetrics,
                (metrics) => formatPair(metrics.hours, metrics.orient)
            );
            updateWorkloadRow(
                'lectures',
                formatPair(totalBucket.lecture.hours, totalBucket.lecture.orient),
                (bucket) => bucket.lecture,
                (metrics) => formatPair(metrics.hours, metrics.orient)
            );
            updateWorkloadRow(
                'practice',
                formatPair(totalBucket.practice.hours, totalBucket.practice.orient),
                (bucket) => bucket.practice,
                (metrics) => formatPair(metrics.hours, metrics.orient)
            );
            updateWorkloadRow('consultations', '—', () => ({ hours: 0, orient: 0 }), () => '—');
            updateWorkloadRow(
                'independent',
                formatPair(totalBucket.independent.hours, totalBucket.independent.orient),
                (bucket) => bucket.independent,
                (metrics) => formatPair(metrics.hours, metrics.orient)
            );
            updateWorkloadRow(
                'attestation',
                formatPair(totalBucket.attestation.hours, totalBucket.attestation.orient),
                (bucket) => bucket.attestation,
                (metrics) => formatPair(metrics.hours, metrics.orient)
            );

            const totalForms = totalBucket.attestationForms.length > 0
                ? totalBucket.attestationForms.join(', ')
                : '—';
            const formDisplays = buildSemesterDisplays(
                (bucket) => bucket.attestationForms,
                (forms) => (forms.length > 0 ? forms.join(', ') : '—')
            );
            setWorkloadCell(workloadTable, 'attestation_forms', 'course', 0, totalForms);
            for (let semester = 1; semester <= 8; semester += 1) {
                const value = Object.prototype.hasOwnProperty.call(formDisplays, semester)
                    ? formDisplays[semester]
                    : '—';
                setWorkloadCell(workloadTable, 'attestation_forms', 'sem', semester, value);
            }
        };

        const renumberRows = () => {
            if (!body) {
                return;
            }
            let num = 0;
            body.querySelectorAll('[data-ktp-row]').forEach((row) => {
                const typeSelect = row.querySelector('[data-ktp-field="lesson_type"]');
                const numCell = row.querySelector('[data-ktp-num], .ktp-col-num');
                if (!numCell) {
                    return;
                }
                if (row.classList.contains('ktp-row--semester-marker')) {
                    numCell.textContent = '';
                    return;
                }
                num += 1;
                numCell.textContent = String(num);
            });
            updateSummaryTable();
        };

        const updateCompSummary = (row) => {
            const summary = row.querySelector('[data-ktp-comp-summary]');
            if (!summary) {
                return;
            }
            const ok = Array.from(row.querySelectorAll('[data-ktp-field="ok"]:checked'))
                .map((input) => {
                    const label = input.closest('label');
                    const text = label ? label.querySelector('span') : null;
                    return text ? text.textContent.trim() : input.value;
                });
            const pk = Array.from(row.querySelectorAll('[data-ktp-field="pk"]:checked'))
                .map((input) => {
                    const label = input.closest('label');
                    const text = label ? label.querySelector('span') : null;
                    return text ? text.textContent.trim() : input.value;
                });
            const parts = [];
            if (ok.length) {
                parts.push('ОК: ' + ok.join(', '));
            }
            if (pk.length) {
                parts.push('ПК: ' + pk.join(', '));
            }
            summary.textContent = parts.length ? parts.join('; ') : '—';
        };

        let ktpCompClipboard = null;

        const getCompCodesFromRow = (row) => ({
            ok: Array.from(row.querySelectorAll('[data-ktp-field="ok"]:checked')).map((input) => input.value),
            pk: Array.from(row.querySelectorAll('[data-ktp-field="pk"]:checked')).map((input) => input.value),
        });

        const updateCompPasteButtons = () => {
            const hasClipboard = ktpCompClipboard !== null;
            ktpRowsEditor.querySelectorAll('[data-ktp-comp-paste], [data-ktp-comp-paste-down]').forEach((button) => {
                button.disabled = !hasClipboard;
            });
        };

        const applyCompCodesToRow = (row, clipboard) => {
            if (!clipboard || row.classList.contains('ktp-row--semester-marker')) {
                return false;
            }
            const okSet = new Set(clipboard.ok);
            const pkSet = new Set(clipboard.pk);
            row.querySelectorAll('[data-ktp-field="ok"]').forEach((input) => {
                input.checked = okSet.has(input.value);
            });
            row.querySelectorAll('[data-ktp-field="pk"]').forEach((input) => {
                input.checked = pkSet.has(input.value);
            });
            updateCompSummary(row);
            saveRow(row, true);
            return true;
        };

        const copyCompFromRow = (row) => {
            if (row.classList.contains('ktp-row--semester-marker')) {
                return;
            }
            ktpCompClipboard = getCompCodesFromRow(row);
            updateCompPasteButtons();
            setStatus('ОК/ПК скопированы');
        };

        const pasteCompToRowsBelow = (row) => {
            if (!ktpCompClipboard) {
                return 0;
            }
            let count = 0;
            let belowCurrent = false;
            body.querySelectorAll('[data-ktp-row]').forEach((targetRow) => {
                if (targetRow === row) {
                    belowCurrent = true;
                    return;
                }
                if (belowCurrent && applyCompCodesToRow(targetRow, ktpCompClipboard)) {
                    count += 1;
                }
            });
            return count;
        };

        ktpRowsEditor.addEventListener('click', (event) => {
            const copyBtn = event.target.closest('[data-ktp-comp-copy]');
            const pasteBtn = event.target.closest('[data-ktp-comp-paste]');
            const pasteDownBtn = event.target.closest('[data-ktp-comp-paste-down]');
            if (!copyBtn && !pasteBtn && !pasteDownBtn) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const row = event.target.closest('[data-ktp-row]');
            if (!row) {
                return;
            }

            if (copyBtn) {
                copyCompFromRow(row);
                return;
            }

            if (!ktpCompClipboard) {
                return;
            }

            if (pasteBtn) {
                if (applyCompCodesToRow(row, ktpCompClipboard)) {
                    setStatus('ОК/ПК вставлены');
                }
                return;
            }

            if (pasteDownBtn) {
                const count = pasteCompToRowsBelow(row);
                if (count > 0) {
                    setStatus('ОК/ПК вставлены в ' + count + ' ' + (count === 1 ? 'строку' : (count < 5 ? 'строки' : 'строк')) + ' ниже');
                } else {
                    setStatus('Нет строк ниже для вставки', true);
                }
            }
        });

        ktpRowsEditor.addEventListener('keydown', (event) => {
            if (!(event.ctrlKey || event.metaKey) || !event.shiftKey) {
                return;
            }
            const row = event.target.closest('[data-ktp-row]');
            if (!row || !ktpRowsEditor.contains(row)) {
                return;
            }
            const key = event.key.toLowerCase();
            if (key === 'c') {
                event.preventDefault();
                copyCompFromRow(row);
            } else if (key === 'v' && ktpCompClipboard) {
                event.preventDefault();
                if (event.altKey) {
                    const count = pasteCompToRowsBelow(row);
                    if (count > 0) {
                        setStatus('ОК/ПК вставлены в ' + count + ' строк ниже');
                    }
                } else if (applyCompCodesToRow(row, ktpCompClipboard)) {
                    setStatus('ОК/ПК вставлены');
                }
            }
        });

        ktpRowsEditor.addEventListener('click', (event) => {
            if (event.target.closest('[data-ktp-comp-copy], [data-ktp-comp-paste], [data-ktp-comp-paste-down]')) {
                return;
            }
            ktpRowsEditor.querySelectorAll('details[data-ktp-comp-picker][open]').forEach((picker) => {
                if (!picker.contains(event.target)) {
                    picker.open = false;
                }
            });
        });

        const getTitleText = (cell) => (cell ? (cell.textContent || '').replace(/\u00a0/g, ' ').trim() : '');

        const setTitleText = (cell, text) => {
            if (!cell) {
                return;
            }
            cell.textContent = text || '';
        };

        const syncRowTypeUi = (row) => {
            const typeSelect = row.querySelector('[data-ktp-field="lesson_type"]');
            const titleCell = row.querySelector('[data-ktp-field="title"]');
            const orientationInput = row.querySelector('[data-ktp-orientation-input]');
            const orientationSep = row.querySelector('[data-ktp-orientation-sep]');
            if (!typeSelect) {
                return;
            }
            const type = typeSelect.value;
            if (titleCell) {
                if (isAttestationType(type)) {
                    setTitleText(titleCell, attestationTitles[type] || getTitleText(titleCell));
                    titleCell.contentEditable = 'false';
                } else {
                    titleCell.contentEditable = 'true';
                }
            }
            if (orientationInput) {
                const show = type === 'lecture' || type === 'practice';
                orientationInput.hidden = !show;
                if (orientationSep) {
                    orientationSep.hidden = !show;
                }
                if (!show) {
                    orientationInput.value = '0';
                }
            }
        };

        const collectRowPayload = (row) => {
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('item_id', itemId);
            formData.append('action', 'save');
            formData.append('topic_id', row.dataset.topicId || '0');
            formData.append('ktp_title', getTitleText(row.querySelector('[data-ktp-field="title"]')));
            formData.append('ktp_lesson_type', (row.querySelector('[data-ktp-field="lesson_type"]') || {}).value || 'lecture');
            formData.append('ktp_hours', (row.querySelector('[data-ktp-field="hours"]') || {}).value || '1');
            const orientation = row.querySelector('[data-ktp-field="orientation_hours"]');
            formData.append('ktp_orientation_hours', orientation ? orientation.value : '0');
            formData.append('ktp_deadline', (row.querySelector('[data-ktp-field="deadline"]') || {}).value || '');
            formData.append('ktp_control_form', (row.querySelector('[data-ktp-field="control_form"]') || {}).value || '');
            row.querySelectorAll('[data-ktp-field="ok"]:checked').forEach((input) => {
                formData.append('ok_codes[]', input.value);
            });
            row.querySelectorAll('[data-ktp-field="pk"]:checked').forEach((input) => {
                formData.append('pk_codes[]', input.value);
            });
            return formData;
        };

        const fillRowFromTopic = (row, topic) => {
            row.dataset.topicId = String(topic.id || 0);
            const titleCell = row.querySelector('[data-ktp-field="title"]');
            const typeSelect = row.querySelector('[data-ktp-field="lesson_type"]');
            const hoursInput = row.querySelector('[data-ktp-field="hours"]');
            const orientationInput = row.querySelector('[data-ktp-field="orientation_hours"]');
            const deadlineInput = row.querySelector('[data-ktp-field="deadline"]');
            const controlSelect = row.querySelector('[data-ktp-field="control_form"]');

            if (titleCell) {
                setTitleText(titleCell, topic.title || '');
            }
            if (typeSelect) {
                typeSelect.value = topic.lesson_type || 'lecture';
            }
            if (hoursInput) {
                hoursInput.value = String(topic.hours || 1).replace(/\.0$/, '');
            }
            if (orientationInput) {
                orientationInput.value = String(topic.orientation_hours || 0).replace(/\.0$/, '');
            }
            if (deadlineInput) {
                deadlineInput.value = topic.deadline_date || '';
            }
            if (controlSelect) {
                controlSelect.value = topic.control_form || '';
            }

            const okSet = new Set(String(topic.ok_codes || '').split(',').map((v) => v.trim()).filter(Boolean));
            const pkSet = new Set(String(topic.pk_codes || '').split(',').map((v) => v.trim()).filter(Boolean));
            row.querySelectorAll('[data-ktp-field="ok"]').forEach((input) => {
                input.checked = okSet.has(input.value);
            });
            row.querySelectorAll('[data-ktp-field="pk"]').forEach((input) => {
                input.checked = pkSet.has(input.value);
            });

            syncRowTypeUi(row);
            updateCompSummary(row);
        };

        const createRowFromTopic = (topic) => {
            if (!template || !template.content) {
                return null;
            }
            if (topic.is_semester_marker) {
                const tr = document.createElement('tr');
                tr.className = 'ktp-rows-row ktp-sortable-row ktp-row--semester-marker';
                tr.dataset.topicId = String(topic.id || 0);
                tr.dataset.lessonType = topic.lesson_type || 'semester_2';
                const title = topic.type_label || topic.title || 'Семестр';
                tr.innerHTML = ''
                    + '<td class="ktp-col-handle"><span class="ktp-drag-handle" title="Перетащить" aria-hidden="true">⋮⋮</span></td>'
                    + '<td class="ktp-col-num"></td>'
                    + '<td colspan="6"><strong>' + title.replace(/</g, '&lt;') + '</strong></td>'
                    + '<td class="table__actions">'
                    + '<button type="button" class="journal-icon-btn journal-icon-btn--danger" title="Удалить" data-ktp-row-delete>×</button>'
                    + '</td>';
                return tr;
            }
            const node = template.content.firstElementChild.cloneNode(true);
            fillRowFromTopic(node, topic);
            return node;
        };

        const postAction = (formData) => fetch(actionUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        }).then((response) => response.json());

        const saveRow = (row, immediate) => {
            if (row.classList.contains('ktp-row--semester-marker')) {
                return;
            }
            const topicId = row.dataset.topicId;
            if (!topicId || topicId === '0') {
                return;
            }

            const run = () => {
                setStatus('Сохранение…');
                postAction(collectRowPayload(row))
                    .then((data) => {
                        if (!data.success) {
                            throw new Error(data.error || 'Не удалось сохранить строку.');
                        }
                        if (!applyWorkloadPayload(data.workload)) {
                            updateSummaryTable();
                        }
                        setStatus('Сохранено');
                    })
                    .catch((error) => {
                        setStatus(error.message || 'Ошибка сохранения.', true);
                    });
            };

            const existing = saveTimers.get(row);
            if (existing) {
                window.clearTimeout(existing);
            }
            if (immediate) {
                run();
                return;
            }
            saveTimers.set(row, window.setTimeout(run, 400));
        };

        const setActiveRow = (row) => {
            if (!row || !body.contains(row)) {
                return;
            }
            if (activeRow) {
                activeRow.classList.remove('ktp-rows-row--active');
            }
            activeRow = row;
            activeRow.classList.add('ktp-rows-row--active');
        };

        const bindRow = (row) => {
            row.addEventListener('focusin', () => setActiveRow(row));
            row.addEventListener('click', () => setActiveRow(row));

            row.querySelectorAll('input, select').forEach((field) => {
                const eventName = field.type === 'checkbox' || field.tagName === 'SELECT' || field.type === 'date'
                    ? 'change'
                    : 'input';
                field.addEventListener(eventName, () => {
                    if (field.dataset.ktpField === 'lesson_type') {
                        syncRowTypeUi(row);
                    }
                    if (field.dataset.ktpField === 'ok' || field.dataset.ktpField === 'pk') {
                        updateCompSummary(row);
                    }
                    updateSummaryTable();
                    saveRow(row, field.type === 'checkbox' || field.tagName === 'SELECT' || field.type === 'date');
                });
                if (eventName === 'input') {
                    field.addEventListener('change', () => saveRow(row, true));
                }
            });

            const titleCell = row.querySelector('[data-ktp-field="title"]');
            if (titleCell) {
                titleCell.addEventListener('input', () => saveRow(row, false));
                titleCell.addEventListener('blur', () => saveRow(row, true));
                titleCell.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        document.execCommand('insertLineBreak');
                    }
                });
            }

            const deleteBtn = row.querySelector('[data-ktp-row-delete]');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', () => {
                    if (!window.confirm('Удалить строку КТП?')) {
                        return;
                    }
                    const formData = new FormData();
                    formData.append('csrf_token', csrfToken);
                    formData.append('item_id', itemId);
                    formData.append('action', 'delete');
                    formData.append('topic_id', row.dataset.topicId || '0');
                    setStatus('Удаление…');
                    postAction(formData)
                        .then((data) => {
                            if (!data.success) {
                                throw new Error(data.error || 'Не удалось удалить строку.');
                            }
                            window.location.reload();
                        })
                        .catch((error) => {
                            setStatus(error.message || 'Ошибка удаления.', true);
                        });
                });
            }

            syncRowTypeUi(row);
            updateCompSummary(row);
        };

        body.querySelectorAll('[data-ktp-row]').forEach((row) => bindRow(row));
        renumberRows();

        document.addEventListener('ktp-rows-changed', updateSummaryTable);
        document.addEventListener('ktp-workload-updated', (event) => {
            const payload = event.detail && event.detail.workload;
            if (!applyWorkloadPayload(payload)) {
                updateSummaryTable();
            }
        });

        const insertBtn = document.querySelector('[data-ktp-row-insert]');
        const copyBtn = document.querySelector('[data-ktp-row-copy]');

        if (insertBtn) {
            insertBtn.addEventListener('click', () => {
                const afterId = activeRow ? (activeRow.dataset.topicId || '0') : '0';
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('item_id', itemId);
                formData.append('action', 'insert');
                formData.append('after_topic_id', afterId);
                setStatus('Добавление строки…');
                postAction(formData)
                    .then((data) => {
                        if (!data.success || !data.topic) {
                            throw new Error(data.error || 'Не удалось вставить строку.');
                        }
                        const row = createRowFromTopic(data.topic);
                        if (!row) {
                            throw new Error('Не удалось создать строку.');
                        }
                        if (activeRow && activeRow.nextSibling) {
                            body.insertBefore(row, activeRow.nextSibling);
                        } else {
                            body.appendChild(row);
                        }
                        bindRow(row);
                        updateCompPasteButtons();
                        body.dispatchEvent(new CustomEvent('ktp-sortable-bind', { detail: { row } }));
                        setActiveRow(row);
                        renumberRows();
                        const titleCell = row.querySelector('[data-ktp-field="title"]');
                        if (titleCell && titleCell.isContentEditable) {
                            titleCell.focus();
                        }
                        setStatus('Строка добавлена');
                        if (!applyWorkloadPayload(data.workload)) {
                            updateSummaryTable();
                        }
                    })
                    .catch((error) => {
                        setStatus(error.message || 'Ошибка добавления.', true);
                    });
            });
        }

        document.querySelectorAll('[data-ktp-row-insert-marker]').forEach((markerBtn) => {
            markerBtn.addEventListener('click', () => {
                const semester = markerBtn.dataset.semester === '1' ? '1' : '2';
                const markerType = 'semester_' + semester;
                const afterId = activeRow ? (activeRow.dataset.topicId || '0') : '0';
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('item_id', itemId);
                formData.append('action', 'insert_marker');
                formData.append('marker_type', markerType);
                formData.append('after_topic_id', afterId);
                setStatus('Добавление разделителя…');
                postAction(formData)
                    .then((data) => {
                        if (!data.success || !data.topic) {
                            throw new Error(data.error || 'Не удалось вставить разделитель.');
                        }
                        const row = createRowFromTopic(data.topic);
                        if (!row) {
                            throw new Error('Не удалось создать строку разделителя.');
                        }
                        if (activeRow && activeRow.nextSibling) {
                            body.insertBefore(row, activeRow.nextSibling);
                        } else {
                            body.appendChild(row);
                        }
                        bindRow(row);
                        body.dispatchEvent(new CustomEvent('ktp-sortable-bind', { detail: { row } }));
                        setActiveRow(row);
                        renumberRows();
                        if (!applyWorkloadPayload(data.workload)) {
                            updateSummaryTable();
                        }
                        setStatus('Разделитель «' + semester + ' семестр» добавлен, часы пересчитаны');
                    })
                    .catch((error) => {
                        setStatus(error.message || 'Ошибка добавления разделителя.', true);
                    });
            });
        });

        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                const source = activeRow || body.querySelector('[data-ktp-row]:last-child');
                if (!source || source.classList.contains('ktp-row--semester-marker')) {
                    setStatus('Нет строки для копирования.', true);
                    return;
                }
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('item_id', itemId);
                formData.append('action', 'copy');
                formData.append('topic_id', source.dataset.topicId || '0');
                setStatus('Копирование…');
                postAction(formData)
                    .then((data) => {
                        if (!data.success || !data.topic) {
                            throw new Error(data.error || 'Не удалось скопировать строку.');
                        }
                        const row = createRowFromTopic(data.topic);
                        if (!row) {
                            throw new Error('Не удалось создать строку.');
                        }
                        if (source.nextSibling) {
                            body.insertBefore(row, source.nextSibling);
                        } else {
                            body.appendChild(row);
                        }
                        bindRow(row);
                        updateCompPasteButtons();
                        body.dispatchEvent(new CustomEvent('ktp-sortable-bind', { detail: { row } }));
                        setActiveRow(row);
                        renumberRows();
                        if (!applyWorkloadPayload(data.workload)) {
                            updateSummaryTable();
                        }
                        setStatus('Строка скопирована');
                    })
                    .catch((error) => {
                        setStatus(error.message || 'Ошибка копирования.', true);
                    });
            });
        }
    }

    document.querySelectorAll('table[data-col-resize]').forEach((table) => {
        const headCells = Array.from(table.querySelectorAll('thead th'));
        if (headCells.length < 2) {
            return;
        }

        const minWidth = 36;
        const persistOffset = Number(table.dataset.colResizePersistOffset || 0);
        const persistCols = Number(table.dataset.colResizePersistCols || 0) || headCells.length;
        const saveUrl = table.dataset.colResizeSaveUrl || '';
        const itemId = table.dataset.itemId || '';
        const csrfToken = table.dataset.csrf || '';
        const storageKey = [
            'col-resize',
            table.dataset.colResizeKey || 'table',
            itemId,
            String(headCells.length),
        ].join(':');
        let widthsReady = false;
        let saveServerTimer = null;

        const applyWidths = (widthsPx) => {
            const total = widthsPx.reduce((sum, value) => sum + value, 0);
            if (total <= 0) {
                return;
            }
            table.style.width = '100%';
            table.style.tableLayout = 'fixed';
            headCells.forEach((th, index) => {
                th.style.width = ((widthsPx[index] / total) * 100).toFixed(4) + '%';
            });
            widthsReady = true;
        };

        const applySavedPercentages = (saved) => {
            if (!Array.isArray(saved) || saved.length !== persistCols) {
                return false;
            }
            const numeric = saved.map((value) => Number(value));
            if (numeric.some((value) => !Number.isFinite(value) || value <= 0)) {
                return false;
            }
            const total = numeric.reduce((sum, value) => sum + value, 0);
            if (total <= 0) {
                return false;
            }
            const normalized = numeric.map((value) => (value / total) * 100);

            table.style.width = '100%';
            table.style.tableLayout = 'fixed';

            if (headCells.length > persistOffset + persistCols) {
                const tableWidth = table.getBoundingClientRect().width || 1;
                const actionCol = headCells[persistOffset + persistCols];
                const actionWidth = actionCol.getBoundingClientRect().width;
                const actionPct = (actionWidth / tableWidth) * 100;
                const contentPct = 100 - actionPct;
                headCells.forEach((th, index) => {
                    if (index >= persistOffset && index < persistOffset + persistCols) {
                        const savedIndex = index - persistOffset;
                        th.style.width = ((normalized[savedIndex] / 100) * contentPct).toFixed(4) + '%';
                    } else if (index === persistOffset + persistCols) {
                        th.style.width = actionPct.toFixed(4) + '%';
                    }
                });
            } else {
                headCells.forEach((th, index) => {
                    if (index < normalized.length) {
                        th.style.width = normalized[index].toFixed(4) + '%';
                    }
                });
            }

            widthsReady = true;
            return true;
        };

        const collectWidthsPx = () => headCells.map((th) => th.getBoundingClientRect().width);

        const getPersistCells = () => headCells.slice(persistOffset, persistOffset + persistCols);

        const getPersistPercentages = () => {
            const widths = getPersistCells().map((th) => th.getBoundingClientRect().width);
            const total = widths.reduce((sum, value) => sum + value, 0);
            if (total <= 0) {
                return null;
            }
            return widths.map((value) => (value / total) * 100);
        };

        const saveWidthsToServer = () => {
            if (!saveUrl || !itemId) {
                return;
            }
            const percentages = getPersistPercentages();
            if (!percentages) {
                return;
            }
            const formData = new FormData();
            formData.append('action', 'save_column_widths');
            formData.append('item_id', itemId);
            formData.append('column_widths', JSON.stringify(percentages));
            formData.append('csrf_token', csrfToken);
            fetch(saveUrl, { method: 'POST', body: formData }).catch(() => {});
        };

        const saveWidths = () => {
            try {
                const widths = collectWidthsPx();
                if (widths.some((value) => value <= 0)) {
                    return;
                }
                window.localStorage.setItem(storageKey, JSON.stringify(widths));
            } catch (error) {
                // ignore quota / private mode
            }
            if (saveUrl && itemId) {
                clearTimeout(saveServerTimer);
                saveServerTimer = setTimeout(saveWidthsToServer, 400);
            }
        };

        const restoreFromServer = () => {
            const raw = table.dataset.columnWidths;
            if (!raw) {
                return false;
            }
            try {
                return applySavedPercentages(JSON.parse(raw));
            } catch (error) {
                return false;
            }
        };

        const restoreFromLocalStorage = () => {
            try {
                const raw = window.localStorage.getItem(storageKey);
                if (!raw) {
                    return false;
                }
                const widths = JSON.parse(raw);
                if (!Array.isArray(widths) || widths.length !== headCells.length) {
                    return false;
                }
                const numeric = widths.map((value) => Number(value));
                if (numeric.some((value) => !Number.isFinite(value) || value <= 0)) {
                    return false;
                }
                applyWidths(numeric);
                return true;
            } catch (error) {
                return false;
            }
        };

        const lockCurrentWidths = () => {
            if (widthsReady) {
                return;
            }
            const widths = collectWidthsPx();
            if (widths.some((value) => value <= 0)) {
                return;
            }
            applyWidths(widths);
        };

        if (!restoreFromServer()) {
            if (restoreFromLocalStorage() && saveUrl && itemId) {
                saveWidthsToServer();
            }
        }

        headCells.forEach((th, index) => {
            if (index >= headCells.length - 1) {
                return;
            }

            const handle = document.createElement('span');
            handle.className = 'col-resize-handle';
            handle.title = 'Изменить ширину столбца';
            th.appendChild(handle);

            handle.addEventListener('mousedown', (event) => {
                event.preventDefault();
                event.stopPropagation();
                lockCurrentWidths();

                const leftTh = th;
                const rightTh = headCells[index + 1];
                const startX = event.clientX;
                const leftStart = leftTh.getBoundingClientRect().width;
                const rightStart = rightTh.getBoundingClientRect().width;
                const pairTotal = leftStart + rightStart;
                const tableWidth = table.getBoundingClientRect().width || 1;

                document.body.classList.add('is-col-resizing');

                const onMove = (moveEvent) => {
                    let leftWidth = leftStart + (moveEvent.clientX - startX);
                    leftWidth = Math.max(minWidth, Math.min(leftWidth, pairTotal - minWidth));
                    const rightWidth = pairTotal - leftWidth;
                    leftTh.style.width = ((leftWidth / tableWidth) * 100).toFixed(4) + '%';
                    rightTh.style.width = ((rightWidth / tableWidth) * 100).toFixed(4) + '%';
                };

                const onUp = () => {
                    document.body.classList.remove('is-col-resizing');
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                    saveWidths();
                };

                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });
        });
    });

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

    const familyTypeSelect = document.querySelector('[data-family-type]');
    if (familyTypeSelect) {
        const syncParentFields = () => {
            const type = familyTypeSelect.value;
            document.querySelectorAll('[data-parent-field]').forEach((block) => {
                const parent = block.dataset.parentField;
                const hide = (type === 'no_father' && parent === 'father')
                    || (type === 'no_mother' && parent === 'mother');
                block.hidden = hide;
            });
        };

        familyTypeSelect.addEventListener('change', syncParentFields);
        syncParentFields();
    }

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

    const normalizePhoneDigits = (value) => String(value).replace(/\D/g, '').slice(0, 11);

    const formatLoginPhone = (value) => {
        const digits = normalizePhoneDigits(value);
        if (digits === '') {
            return '';
        }

        if (digits.length === 11 && digits[0] === '8') {
            return '+7' + digits.slice(1);
        }

        if (digits.length === 11 && digits[0] === '7') {
            return '+' + digits;
        }

        if (digits.length === 10) {
            return digits[0] === '7' ? '+' + digits : '+7' + digits;
        }

        if (digits[0] === '8' && digits.length > 1) {
            return '+7' + digits.slice(1);
        }

        if (digits[0] === '7') {
            return '+' + digits;
        }

        return '+' + digits;
    };

    document.querySelectorAll('input[data-phone-login]').forEach((input) => {
        const applyPhoneMask = () => {
            input.value = formatLoginPhone(input.value);
        };

        input.addEventListener('input', applyPhoneMask);

        input.addEventListener('blur', applyPhoneMask);

        const form = input.closest('form');
        if (form) {
            form.addEventListener('submit', applyPhoneMask);
        }

        if (input.value.trim() !== '') {
            applyPhoneMask();
        }
    });

    const loginForm = document.querySelector('[data-login-form]');
    if (loginForm) {
        const phoneGroup = loginForm.querySelector('[data-login-phone-group]');
        const emailGroup = loginForm.querySelector('[data-login-email-group]');
        const phoneInput = loginForm.querySelector('[data-phone-login]');
        const emailInput = loginForm.querySelector('[data-login-email]');

        const syncLoginType = () => {
            const type = loginForm.querySelector('[data-login-type]:checked')?.value || 'phone';
            const isPhone = type === 'phone';

            if (phoneGroup) {
                phoneGroup.hidden = !isPhone;
            }
            if (emailGroup) {
                emailGroup.hidden = isPhone;
            }
            if (phoneInput) {
                phoneInput.required = isPhone;
            }
            if (emailInput) {
                emailInput.required = !isPhone;
            }
        };

        loginForm.querySelectorAll('[data-login-type]').forEach((input) => {
            input.addEventListener('change', syncLoginType);
        });

        syncLoginType();
    }

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

        const credentialsUrl = teachersTable.dataset.teachersCredentialsUrl;
        const csrfToken = teachersTable.dataset.csrf;
        if (credentialsUrl && csrfToken) {
            teachersTable.querySelectorAll('[data-teacher-credentials-sent]').forEach((checkbox) => {
                checkbox.addEventListener('click', (event) => {
                    event.stopPropagation();
                });

                checkbox.addEventListener('change', () => {
                    const teacherId = checkbox.dataset.teacherId;
                    if (!teacherId) {
                        return;
                    }

                    const formData = new FormData();
                    formData.append('csrf_token', csrfToken);
                    formData.append('teacher_id', teacherId);
                    formData.append('sent', checkbox.checked ? '1' : '0');

                    checkbox.disabled = true;

                    fetch(credentialsUrl, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            if (!data.success) {
                                throw new Error(data.error || 'Не удалось сохранить отметку.');
                            }
                        })
                        .catch((error) => {
                            checkbox.checked = !checkbox.checked;
                            window.alert(error.message || 'Не удалось сохранить отметку.');
                        })
                        .finally(() => {
                            checkbox.disabled = false;
                        });
                });
            });
        }
    }

    document.querySelectorAll('[data-table-search]').forEach((input) => {
        const key = input.dataset.tableSearch;
        const table = document.querySelector(`[data-table-search-target="${key}"]`);
        if (!table) {
            return;
        }

        const rows = Array.from(table.querySelectorAll('[data-search-row]'));
        const emptyNode = document.querySelector(`[data-table-search-empty="${key}"]`);

        const filterRows = () => {
            const query = input.value.trim().toLowerCase();
            let visible = 0;

            rows.forEach((row) => {
                const text = row.dataset.searchText || '';
                const match = query === '' || text.indexOf(query) !== -1;
                row.hidden = !match;
                if (match) {
                    visible += 1;
                    const numCell = row.querySelector('[data-search-num]');
                    if (numCell) {
                        numCell.textContent = String(visible);
                    }
                }
            });

            if (emptyNode) {
                emptyNode.hidden = visible > 0;
            }
            table.hidden = visible === 0;
        };

        input.addEventListener('input', filterRows);
    });

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

    document.querySelectorAll('[data-ktp-print]').forEach((button) => {
        button.addEventListener('click', () => {
            window.print();
        });
    });

    const header = document.querySelector('.header');
    const menuBtn = header?.querySelector('.header__menu-btn');
    const menuCloseBtn = header?.querySelector('.header__nav-close');
    const headerNav = header?.querySelector('.header__nav');
    const headerOverlay = header?.querySelector('.header__overlay');

    if (header && menuBtn && headerNav) {
        const closeHeaderMenu = () => {
            header.classList.remove('header--menu-open');
            menuBtn.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('body--nav-open');
        };

        const openHeaderMenu = () => {
            header.classList.add('header--menu-open');
            menuBtn.setAttribute('aria-expanded', 'true');
            document.body.classList.add('body--nav-open');
        };

        menuBtn.addEventListener('click', () => {
            if (!header.classList.contains('header--menu-open')) {
                openHeaderMenu();
            }
        });

        menuCloseBtn?.addEventListener('click', closeHeaderMenu);
        headerOverlay?.addEventListener('click', closeHeaderMenu);
        headerNav.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', closeHeaderMenu);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeHeaderMenu();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 900) {
                closeHeaderMenu();
            }
        });
    }
});
