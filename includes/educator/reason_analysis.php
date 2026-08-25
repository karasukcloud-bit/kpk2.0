<?php

declare(strict_types=1);

/** @var array<string, mixed> $reasonAnalysis */
/** @var string $year */
?>
<section class="panel attendance-analysis">
    <div class="attendance-analysis__head">
        <h2>Анализ причин пропусков</h2>
        <p class="text-muted">Автоматическая сводка по учебному году <?= e($year) ?> на основе данных журнала посещаемости.</p>
    </div>

    <?php if ((int) ($reasonAnalysis['total'] ?? 0) === 0): ?>
        <p class="text-muted">Данных для анализа пока недостаточно.</p>
    <?php else: ?>
        <div class="attendance-analysis__grid">
            <div class="attendance-analysis__stats">
                <h3 class="subsection-title">Распределение уважительных пропусков</h3>
                <?php if ($reasonAnalysis['reasons'] === []): ?>
                    <p class="text-muted">Уважительные пропуски без указания причин не найдены.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table table--compact">
                            <thead>
                                <tr>
                                    <th>Причина</th>
                                    <th>Занятий</th>
                                    <th>Студентов</th>
                                    <th>% уважит.</th>
                                    <th>% от всех</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reasonAnalysis['reasons'] as $reason): ?>
                                <tr>
                                    <td><?= e($reason['reason_name']) ?></td>
                                    <td><?= (int) $reason['lessons'] ?></td>
                                    <td><?= (int) $reason['students'] ?></td>
                                    <td><?= e((string) $reason['share_excused']) ?>%</td>
                                    <td><?= e((string) $reason['share_total']) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="attendance-analysis__chart">
                <div class="educator-attendance-chart-wrap educator-attendance-chart-wrap--compact">
                    <canvas id="educator-reasons-chart" aria-label="Доля причин уважительных пропусков"></canvas>
                </div>
            </div>
        </div>

        <div class="attendance-analysis__report">
            <h3 class="subsection-title">Аналитическая справка</h3>
            <?php foreach ($reasonAnalysis['insights'] as $section): ?>
            <article class="attendance-analysis__block">
                <h4><?= e($section['title']) ?></h4>
                <p><?= nl2br(e($section['text'])) ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
