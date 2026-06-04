<?php
// api/submit-contact.php — Public contact form submission
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_err('Method not allowed.', 405);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rate_limit('contact_' . $ip, 3, 3600)) {
    json_err('Too many requests. Please try again in an hour.');
}

$name      = trim($_POST['name']      ?? '');
$email     = trim($_POST['email']     ?? '');
$age_group = trim($_POST['age_group'] ?? '');
$subject   = trim($_POST['subject']   ?? '');
$message   = trim($_POST['message']   ?? '');

if (empty($name))    json_err('Name is required.');
if (empty($message)) json_err('Message cannot be empty.');
if (strlen($message) > 5000) json_err('Message too long.');

if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_err('Please enter a valid email address.');
}

db_insert('contact_messages', [
    'name'      => $name,
    'email'     => $email     ?: null,
    'age_group' => $age_group ?: null,
    'subject'   => $subject   ?: null,
    'message'   => $message,
    'is_read'   => 0,
]);

json_ok([], 'Thank you for reaching out! We will get back to you soon.');
