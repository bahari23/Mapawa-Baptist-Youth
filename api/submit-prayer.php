<?php
// api/submit-prayer.php — Public prayer request submission
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_err('Method not allowed.', 405);
}

// Rate limit: 5 prayers per hour per IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rate_limit('prayer_' . $ip, 5, 3600)) {
    json_err('Too many requests. Please try again later.');
}

$request    = trim($_POST['request'] ?? '');
$name       = trim($_POST['name']    ?? '');
$category   = trim($_POST['category'] ?? '');
$is_anon    = !empty($_POST['is_anonymous']) ? 1 : 0;
$is_public  = !empty($_POST['is_public'])    ? 1 : 0;

if (empty($request)) {
    json_err('Prayer request cannot be empty.');
}
if (strlen($request) > 5000) {
    json_err('Request is too long (max 5000 characters).');
}

$id = db_insert('prayer_requests', [
    'name'         => $name     ?: null,
    'category'     => $category ?: null,
    'request'      => $request,
    'is_anonymous' => $is_anon,
    'is_public'    => $is_public,
    'is_read'      => 0,
]);

json_ok(['id' => $id], 'Your prayer request has been received. We are praying with you.');
