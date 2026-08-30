<?php

declare(strict_types=1);

require_once __DIR__ . '/../organization.php';

function ktp_document_signatory_name(string $roleKey): string
{
    // Роли umr_deputy и specialty_leader будут добавлены позже.
    return '';
}

function build_ktp_document_header_context(array $item): array
{
    $org = get_organization();
    $specialtyCode = trim((string) ($item['specialty_code'] ?? ''));
    $specialtyName = trim((string) ($item['specialty_name'] ?? ''));
    $specialty = trim($specialtyCode . ($specialtyCode !== '' && $specialtyName !== '' ? ' ' : '') . $specialtyName);

    $teacherName = trim((string) ($item['teacher_name'] ?? ''));
    $umrDeputyName = trim(ktp_document_signatory_name('umr_deputy'));
    $specialtyLeaderName = trim(ktp_document_signatory_name('specialty_leader'));

    return [
        'organization_name' => trim((string) ($org['name'] ?? '')),
        'academic_year' => trim((string) ($item['academic_year'] ?? '')),
        'group_number' => trim((string) ($item['group_number'] ?? '')),
        'subject_name' => trim((string) ($item['subject_name'] ?? '')),
        'specialty' => $specialty !== '' ? $specialty : '—',
        'teacher_name' => $teacherName !== '' ? $teacherName : 'ххххххх',
        'umr_deputy_name' => $umrDeputyName !== '' ? $umrDeputyName : 'ххххххх',
        'specialty_leader_name' => $specialtyLeaderName !== '' ? $specialtyLeaderName : 'хххххххх',
    ];
}

function render_ktp_document_header(array $header, bool $forPdf = false): void
{
    $orgName = (string) ($header['organization_name'] ?? '');
    $year = (string) ($header['academic_year'] ?? '');
    $titleYear = $year !== '' ? ' на ' . $year . ' год обучения' : '';
    $e = static fn (string $value): string => $forPdf
        ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
        : e($value);
    ?>
    <header class="ktp-doc-header">
        <div class="ktp-doc-header__center">
            <?php if ($orgName !== ''): ?>
            <div class="ktp-doc-header__org"><?= $forPdf ? nl2br(htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8')) : nl2br(e($orgName)) ?></div>
            <?php endif; ?>
            <h2 class="ktp-doc-header__title">КАЛЕНДАРНО-ТЕМАТИЧЕСКИЙ ПЛАН<?= $e($titleYear) ?></h2>
        </div>

        <div class="ktp-doc-header__approve">
            <div class="ktp-doc-header__approve-title">УТВЕРЖДАЮ</div>
            <div class="ktp-doc-header__approve-role">
                Заместитель директора<br>
                по учебно-методической работе
            </div>
            <div class="ktp-doc-header__approve-sign">
                <span class="ktp-doc-header__signature">________________________</span>
                <span class="ktp-doc-header__sign-name"><?= $e((string) $header['umr_deputy_name']) ?></span>
            </div>
        </div>

        <div class="ktp-doc-header__meta">
            <p class="ktp-doc-header__meta-line">Учебная дисциплина: <?= $e((string) $header['subject_name']) ?></p>
            <p class="ktp-doc-header__meta-line">Преподаватель: <?= $e((string) $header['teacher_name']) ?></p>
            <p class="ktp-doc-header__meta-line">Специальность: <?= $e((string) $header['specialty']) ?></p>
            <p class="ktp-doc-header__meta-line">Руководитель специальности: <?= $e((string) $header['specialty_leader_name']) ?></p>
            <?php if ((string) ($header['group_number'] ?? '') !== ''): ?>
            <p class="ktp-doc-header__meta-line">Группа: <?= $e((string) $header['group_number']) ?></p>
            <?php endif; ?>
        </div>
    </header>
    <?php
}
