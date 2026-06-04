<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start_secure();

// Already logged in
if (is_admin_logged_in()) {
    redirect(SITE_URL . '/admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username');
    $password = post('password');
    $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Rate limit: 5 attempts per 5 minutes per IP
    if (!rate_limit('login_' . $ip, 5, 300)) {
        $error = 'Too many login attempts. Please wait 5 minutes.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter your username and password.';
    } elseif (!admin_login($username, $password)) {
        $error = 'Invalid username or password.';
    } else {
        flash('success', 'Welcome back, ' . clean($_SESSION['admin_username']) . '!');
        redirect(SITE_URL . '/admin/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/responsive.css" />
  <link rel="icon" type="image/svg+xml" href="../assets/images/logo.svg" />
  <style>
    body { background: #07111f; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .login-wrap { width: 100%; max-width: 420px; padding: 24px; }
    .login-box { background: rgba(255,255,255,0.04); border: 1px solid rgba(245,200,66,0.15); border-radius: 12px; padding: 44px 40px; }
    @media (max-width: 480px) {
      .login-wrap { padding: 16px; }
      .login-box  { padding: 32px 22px; border-radius: 8px; }
      .login-title { font-size: 0.65rem; }
    }
    @media (max-width: 360px) {
      .login-box { padding: 24px 16px; }
    }
    .login-logo { width: 72px; margin: 0 auto 20px; display: block; filter: drop-shadow(0 0 16px rgba(245,200,66,0.3)); }
    .login-title { font-family: var(--font-display); font-size: 0.72rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--gold-rich); text-align: center; margin-bottom: 4px; }
    .login-sub { font-family: var(--font-serif); font-size: 1rem; color: rgba(255,249,238,0.5); text-align: center; margin-bottom: 32px; }
    .error-box { background: rgba(124,29,29,0.3); border: 1px solid rgba(124,29,29,0.5); border-radius: 6px; padding: 11px 16px; font-family: var(--font-display); font-size: 0.65rem; letter-spacing: 0.1em; text-transform: uppercase; color: #f88; margin-bottom: 20px; }
    .login-label { display: block; font-family: var(--font-display); font-size: 0.6rem; letter-spacing: 0.16em; text-transform: uppercase; color: rgba(255,249,238,0.45); margin-bottom: 6px; }
    .login-input { width: 100%; padding: 12px 14px; background: rgba(255,255,255,0.06); border: 1px solid rgba(245,200,66,0.15); border-radius: 4px; color: var(--cream); font-family: var(--font-body); font-size: 1rem; outline: none; transition: 0.2s; margin-bottom: 18px; }
    .login-input:focus { border-color: var(--gold-rich); background: rgba(255,255,255,0.09); }
    .login-btn { width: 100%; padding: 13px; background: var(--gold-rich); color: var(--blue-deep); font-family: var(--font-display); font-size: 0.72rem; letter-spacing: 0.16em; text-transform: uppercase; font-weight: 700; border: none; border-radius: 3px; cursor: pointer; transition: 0.2s; margin-top: 4px; }
    .login-btn:hover { background: var(--gold-bright); }
    .back-link { display: block; text-align: center; margin-top: 20px; font-family: var(--font-display); font-size: 0.62rem; letter-spacing: 0.12em; text-transform: uppercase; color: rgba(255,249,238,0.3); }
    .back-link:hover { color: var(--gold-rich); }
    .default-creds { background: rgba(21,101,192,0.15); border: 1px solid rgba(21,101,192,0.25); border-radius: 6px; padding: 12px 16px; margin-bottom: 24px; font-size: 0.85rem; color: rgba(255,249,238,0.55); line-height: 1.7; }
    .default-creds strong { color: var(--gold-rich); font-family: var(--font-display); letter-spacing: 0.06em; }
  </style>
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <img src="../assets/images/logo.svg" alt="Logo" class="login-logo" />
    <div class="login-title">Admin Portal</div>
    <div class="login-sub">Mapawa Baptist Youth Ministry</div>

    <?php if ($error): ?>
      <div class="error-box">⚠ <?= clean($error) ?></div>
    <?php endif; ?>

    <div class="default-creds">
      Default login →
      <strong>admin</strong> / <strong>MBC@Admin2025!</strong><br>
      <span style="font-size:0.78rem;color:rgba(255,249,238,0.35);">Change this immediately after first login.</span>
    </div>

    <form method="POST" action="">
      <?= csrf_field() ?>
      <label class="login-label" for="username">Username</label>
      <input class="login-input" type="text" name="username" id="username"
             value="<?= clean(post('username')) ?>" autocomplete="username" required />

      <label class="login-label" for="password">Password</label>
      <input class="login-input" type="password" name="password" id="password"
             autocomplete="current-password" required />

      <button class="login-btn" type="submit">Sign In →</button>
    </form>
    <a href="../index.html" class="back-link">← Back to Site</a>
  </div>
</div>
</body>
</html>
