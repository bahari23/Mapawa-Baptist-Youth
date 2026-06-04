<?php
// ================================================================
// includes/config.php — Site-wide configuration
// ================================================================

// ── Database ─────────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_NAME',     'mapawa_youth');
define('DB_USER',     'root');          // Change to your MySQL user
define('DB_PASS',     '');              // Change to your MySQL password
define('DB_CHARSET',  'utf8mb4');

// ── Site ──────────────────────────────────────────────────────────
define('SITE_NAME',   'Mapawa Baptist Youth Ministry');
define('SITE_URL',    'http://localhost/mapawa-baptist-youth'); // No trailing slash
define('ADMIN_EMAIL', 'admin@mapawabaptist.org');

// ── Admin Credentials ─────────────────────────────────────────────
// Default: username = admin | password = MBC@Admin2025!
// Change these after first login via the Admin → Settings panel.
define('ADMIN_DEFAULT_USER', 'admin');
define('ADMIN_DEFAULT_PASS', 'MBC@Admin2025!');

// ── Uploads ───────────────────────────────────────────────────────
define('UPLOAD_DIR',   __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL',   SITE_URL . '/assets/uploads/');
define('MAX_UPLOAD_MB', 100);           // Max file size in MB

define('ALLOWED_AUDIO', ['mp3','wav','ogg','m4a','aac']);
define('ALLOWED_VIDEO', ['mp4','mov','webm','mkv']);
define('ALLOWED_IMAGE', ['jpg','jpeg','png','webp','gif']);
define('ALLOWED_PDF',   ['pdf']);

// ── Session ───────────────────────────────────────────────────────
define('SESSION_NAME',    'mbc_admin_sess');
define('SESSION_TIMEOUT', 3600 * 4);   // 4 hours

// ── Security ─────────────────────────────────────────────────────
define('CSRF_TOKEN_NAME', 'mbc_csrf');
define('APP_ENV',         'development'); // 'production' hides error details

// ── Timezone ─────────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila');

// ── Error reporting ──────────────────────────────────────────────
if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
