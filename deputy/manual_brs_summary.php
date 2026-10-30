<?php

declare(strict_types=1);

$query = $_SERVER['QUERY_STRING'] ?? '';
$params = [];
if ($query !== '') {
    parse_str($query, $params);
}
$params['source'] = 'manual_brs';
header('Location: summary.php?' . http_build_query($params));
exit;
