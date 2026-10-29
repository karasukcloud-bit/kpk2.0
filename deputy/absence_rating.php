<?php

declare(strict_types=1);

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '../educator/absence_rating.php' . ($query !== '' ? '?' . $query : '');
header('Location: ' . $target);
exit;
