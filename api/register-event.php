<?php
// api/register-event.php — Public event registration
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_err('Method not allowed.', 405);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rate_limit('reg_' . $ip, 10, 3600)) {
    json_err('Too many requests. Please try again later.');
}

$event_id  = (int)($_POST['event_id'] ?? 0);
$name      = trim($_POST['name']      ?? '');
$email     = trim($_POST['email']     ?? '');
$phone     = trim($_POST['phone']     ?? '');
$age_group = trim($_POST['age_group'] ?? '');
$notes     = trim($_POST['notes']     ?? '');

if (!$event_id) json_err('Invalid event.');
if (empty($name)) json_err('Name is required.');

$event = db_fetch('SELECT * FROM events WHERE id = ? AND status = ?', [$event_id, 'upcoming']);
if (!$event) json_err('Event not found or no longer accepting registrations.');

if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_err('Please enter a valid email address.');
}

// Check duplicate
if ($email && db_count('event_registrations', 'event_id = ? AND email = ?', [$event_id, $email]) > 0) {
    json_err('This email is already registered for this event.');
}

$id = db_insert('event_registrations', [
    'event_id'  => $event_id,
    'name'      => $name,
    'email'     => $email     ?: null,
    'phone'     => $phone     ?: null,
    'age_group' => $age_group ?: null,
    'notes'     => $notes     ?: null,
]);

json_ok(['registration_id' => $id], 'You are registered for "' . $event['title'] . '"! See you there.');
