<?php

declare(strict_types=1);

/**
 * Заголовок столбца ведомости с статусом составной дисциплины.
 *
 * @param array<string,mixed> $subject
 * @param array<int, array<string,mixed>> $compositeMap
 */
function render_gradebook_subject_header(array $subject, array $compositeMap = []): void
{
    $itemId = (int) ($subject['curriculum_item_id'] ?? 0);
    $name = (string) ($subject['subject_name'] ?? '');
    $composite = $compositeMap[$itemId] ?? null;

    if ($composite === null) {
        echo '<th class="gradebook-table__subject-col">';
        echo '<span class="gradebook-subject-title">' . e($name) . '</span>';
        echo '</th>';

        return;
    }

    $hasGaps = !empty($composite['has_gaps']);
    $statusClass = $hasGaps
        ? ' gradebook-composite-status--gaps'
        : ' gradebook-composite-status--ok';
    ?>
    <th class="gradebook-table__subject-col gradebook-table__subject-col--composite">
        <button
            type="button"
            class="gradebook-composite-head"
            data-composite-open="<?= $itemId ?>"
            title="Открыть расшифровку по разделам"
        >
            <span class="gradebook-subject-title"><?= e($name) ?></span>
            <span class="gradebook-composite-status<?= $statusClass ?>">
                <?= e((string) $composite['status_label']) ?>
            </span>
        </button>
    </th>
    <?php
}

/**
 * Модалка расшифровки составных дисциплин + скрипт.
 *
 * @param array<int, array<string,mixed>> $compositeMap
 */
function render_gradebook_composite_modals(array $compositeMap): void
{
    if ($compositeMap === []) {
        return;
    }
    ?>
    <div class="modal" id="gradebook-composite-modal" hidden>
        <div class="modal__backdrop" data-composite-close></div>
        <div class="modal__dialog modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="gradebook-composite-title">
            <div class="modal__header">
                <h2 id="gradebook-composite-title">Разделы дисциплины</h2>
                <button type="button" class="modal__close" data-composite-close aria-label="Закрыть">×</button>
            </div>
            <p class="text-muted" id="gradebook-composite-meta"></p>
            <div class="table-wrap gradebook-composite-table-wrap" id="gradebook-composite-table-wrap"></div>
            <div class="form__actions">
                <button type="button" class="btn btn--ghost" data-composite-close>Закрыть</button>
            </div>
        </div>
    </div>
    <script>
    (() => {
        const map = <?= json_encode(
            array_values($compositeMap),
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
        ) ?>;
        const byId = {};
        map.forEach((item) => {
            byId[String(item.curriculum_item_id)] = item;
        });

        const modal = document.getElementById('gradebook-composite-modal');
        const title = document.getElementById('gradebook-composite-title');
        const meta = document.getElementById('gradebook-composite-meta');
        const wrap = document.getElementById('gradebook-composite-table-wrap');
        if (!modal || !title || !meta || !wrap) {
            return;
        }

        const esc = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        const gradeCell = (grade) => {
            if (grade === null || grade === undefined || grade === '') {
                return '<td class="gradebook-composite-cell gradebook-composite-cell--empty">—</td>';
            }
            const g = Number(grade);
            const cls = g === 2 ? ' gradebook-composite-cell--two' : '';
            return '<td class="gradebook-composite-cell' + cls + '">' + esc(g) + '</td>';
        };

        let dragActive = false;
        let dragMoved = false;
        let startX = 0;
        let startY = 0;
        let startLeft = 0;
        let startTop = 0;

        const stopDrag = () => {
            if (!dragActive) {
                return;
            }
            dragActive = false;
            wrap.classList.remove('is-dragging');
        };

        wrap.addEventListener('mousedown', (event) => {
            if (event.button !== 0) {
                return;
            }
            dragActive = true;
            dragMoved = false;
            startX = event.clientX;
            startY = event.clientY;
            startLeft = wrap.scrollLeft;
            startTop = wrap.scrollTop;
            wrap.classList.add('is-dragging');
            event.preventDefault();
        });

        document.addEventListener('mousemove', (event) => {
            if (!dragActive) {
                return;
            }
            const dx = event.clientX - startX;
            const dy = event.clientY - startY;
            if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
                dragMoved = true;
            }
            wrap.scrollLeft = startLeft - dx;
            wrap.scrollTop = startTop - dy;
        });

        document.addEventListener('mouseup', stopDrag);
        wrap.addEventListener('mouseleave', () => {
            if (dragActive) {
                stopDrag();
            }
        });
        wrap.addEventListener('dragstart', (event) => event.preventDefault());

        const open = (itemId) => {
            const item = byId[String(itemId)];
            if (!item) {
                return;
            }
            title.textContent = item.subject_name || 'Дисциплина';
            meta.textContent = (item.status_label || '')
                + (item.has_gaps ? ' · есть незаполненные разделы или ПА' : ' · разделы и ПА заполнены');

            let html = '<table class="table gradebook-composite-detail-table"><thead><tr>';
            html += '<th>Студент</th>';
            (item.children || []).forEach((child) => {
                const filled = (item.section_filled_counts || {})[child.curriculum_item_id] || 0;
                const total = item.pa_total || 0;
                html += '<th>' + esc(child.subject_name)
                    + '<span class="gradebook-composite-col-count">'
                    + esc(filled) + '/' + esc(total)
                    + '</span></th>';
            });
            html += '<th>Разделы</th><th>ПА</th><th>Итог</th></tr></thead><tbody>';

            (item.rows || []).forEach((row) => {
                html += '<tr><td>' + esc(row.display_name || row.full_name || '—') + '</td>';
                (row.parts || []).forEach((part) => {
                    html += gradeCell(part.grade);
                });
                html += gradeCell(row.base_grade);
                html += gradeCell(row.pa_grade);
                html += gradeCell(row.final_grade);
                html += '</tr>';
            });
            html += '</tbody></table>';
            wrap.innerHTML = html;
            wrap.scrollLeft = 0;
            wrap.scrollTop = 0;
            modal.hidden = false;
            document.body.classList.add('modal-open');
        };

        const close = () => {
            stopDrag();
            modal.hidden = true;
            document.body.classList.remove('modal-open');
        };

        document.querySelectorAll('[data-composite-open]').forEach((btn) => {
            btn.addEventListener('click', () => open(btn.getAttribute('data-composite-open')));
        });
        document.querySelectorAll('[data-composite-close]').forEach((btn) => {
            btn.addEventListener('click', close);
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.hidden) {
                close();
            }
        });
    })();
    </script>
    <?php
}
