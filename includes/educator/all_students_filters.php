<?php

declare(strict_types=1);

const EDUCATOR_ALL_STUDENTS_FILTERS_KEY = 'educator_all_students_filters';

/** @return array<string, mixed> */
function educator_all_students_default_filters(): array
{
    return [
        'q' => '',
        'group_id' => 0,
        'gender' => '',
        'is_minor' => '',
        'is_nonresident' => '',
        'is_low_income' => '',
        'without_parental_care' => '',
        'residence_type' => '',
    ];
}

/** @return array<string, mixed> */
function educator_all_students_normalize_filters(array $raw): array
{
    $filters = educator_all_students_default_filters();
    $filters['q'] = trim((string) ($raw['q'] ?? ''));
    $filters['group_id'] = (int) ($raw['group_id'] ?? 0);
    $filters['gender'] = trim((string) ($raw['gender'] ?? ''));
    $filters['is_minor'] = (string) ($raw['is_minor'] ?? '');
    $filters['is_nonresident'] = (string) ($raw['is_nonresident'] ?? '');
    $filters['is_low_income'] = (string) ($raw['is_low_income'] ?? '');
    $filters['without_parental_care'] = (string) ($raw['without_parental_care'] ?? '');
    $filters['residence_type'] = trim((string) ($raw['residence_type'] ?? ''));

    if (!in_array($filters['gender'], ['male', 'female'], true)) {
        $filters['gender'] = '';
    }
    if (!in_array($filters['is_minor'], ['0', '1'], true)) {
        $filters['is_minor'] = '';
    }
    if (!in_array($filters['is_nonresident'], ['0', '1'], true)) {
        $filters['is_nonresident'] = '';
    }
    if ($filters['is_low_income'] !== '1') {
        $filters['is_low_income'] = '';
    }
    if ($filters['without_parental_care'] !== '1') {
        $filters['without_parental_care'] = '';
    }
    if (!in_array($filters['residence_type'], ['family', 'dormitory', 'apartment'], true)) {
        $filters['residence_type'] = '';
    }
    if ($filters['group_id'] < 0) {
        $filters['group_id'] = 0;
    }

    return $filters;
}

function educator_all_students_has_filter_request(): bool
{
    foreach (array_keys(educator_all_students_default_filters()) as $key) {
        if (array_key_exists($key, $_GET)) {
            return true;
        }
    }

    return false;
}

/** @param array<string, mixed> $filters */
function educator_all_students_save_filters(array $filters): void
{
    $_SESSION[EDUCATOR_ALL_STUDENTS_FILTERS_KEY] = educator_all_students_normalize_filters($filters);
}

function educator_all_students_clear_filters(): void
{
    unset($_SESSION[EDUCATOR_ALL_STUDENTS_FILTERS_KEY]);
}

/** @return array<string, mixed> */
function educator_all_students_saved_filters(): array
{
    $saved = $_SESSION[EDUCATOR_ALL_STUDENTS_FILTERS_KEY] ?? null;

    return is_array($saved)
        ? educator_all_students_normalize_filters($saved)
        : educator_all_students_default_filters();
}

/** @param array<string, mixed> $filters */
function educator_all_students_filter_query(array $filters): string
{
    $filters = educator_all_students_normalize_filters($filters);
    $params = [];
    if ($filters['q'] !== '') {
        $params['q'] = $filters['q'];
    }
    if ($filters['group_id'] > 0) {
        $params['group_id'] = $filters['group_id'];
    }
    if ($filters['gender'] !== '') {
        $params['gender'] = $filters['gender'];
    }
    if ($filters['is_minor'] !== '') {
        $params['is_minor'] = $filters['is_minor'];
    }
    if ($filters['is_nonresident'] !== '') {
        $params['is_nonresident'] = $filters['is_nonresident'];
    }
    if ($filters['is_low_income'] === '1') {
        $params['is_low_income'] = '1';
    }
    if ($filters['without_parental_care'] === '1') {
        $params['without_parental_care'] = '1';
    }
    if ($filters['residence_type'] !== '') {
        $params['residence_type'] = $filters['residence_type'];
    }

    return http_build_query($params);
}

/** @param array<string, mixed>|null $filters */
function educator_all_students_list_url(?array $filters = null): string
{
    $filters = $filters ?? educator_all_students_saved_filters();
    $query = educator_all_students_filter_query($filters);

    return $query !== '' ? ('all_students.php?' . $query) : 'all_students.php';
}
