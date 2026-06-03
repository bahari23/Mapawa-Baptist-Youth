<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

session_start_secure();
admin_logout();
header('Location: ' . SITE_URL . '/admin/login.php');
exit;
