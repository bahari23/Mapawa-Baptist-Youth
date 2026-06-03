<?php
// ================================================================
// includes/auth.php — Authentication & session management
// ================================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Start session with secure settings
function session_start_secure(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

// ── Admin auth ────────────────────────────────────────────────────

function is_admin_logged_in(): bool {
    session_start_secure();
    if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_exp'])) return false;
    if (time() > $_SESSION['admin_exp']) {
        session_unset();
        session_destroy();
        return false;
    }
    // Slide expiry window
    $_SESSION['admin_exp'] = time() + SESSION_TIMEOUT;
    return true;
}

function require_admin_login(): void {
    if (!is_admin_logged_in()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function admin_login(string $username, string $password): bool {
    session_start_secure();
    $user = db_fetch(
        'SELECT * FROM admin_users WHERE username = ? AND is_active = 1',
        [$username]
    );
    if (!$user) return false;
    if (!password_verify($password, $user['password'])) return false;

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['admin_id']       = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_role']     = $user['role'];
    $_SESSION['admin_exp']      = time() + SESSION_TIMEOUT;

    // Update last login + log
    db_update('admin_users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
    db_insert('admin_sessions', [
        'admin_id'   => $user['id'],
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
    ]);

    return true;
}

function admin_logout(): void {
    session_start_secure();
    if (!empty($_SESSION['admin_id'])) {
        db_query(
            'UPDATE admin_sessions SET logged_out = NOW() WHERE admin_id = ? AND logged_out IS NULL',
            [$_SESSION['admin_id']]
        );
    }
    session_unset();
    session_destroy();
}

function current_admin(): array {
    return [
        'id'       => $_SESSION['admin_id']       ?? 0,
        'username' => $_SESSION['admin_username'] ?? '',
        'role'     => $_SESSION['admin_role']     ?? '',
    ];
}

function admin_is_super(): bool {
    return ($_SESSION['admin_role'] ?? '') === 'super_admin';
}

// ── CSRF ──────────────────────────────────────────────────────────

function csrf_token(): string {
    session_start_secure();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

function csrf_verify(): bool {
    session_start_secure();
    $token = $_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

function csrf_check(): void {
    if (!csrf_verify()) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Invalid CSRF token.']));
    }
}

// ── Change password ───────────────────────────────────────────────

function admin_change_password(int $admin_id, string $new_password): bool {
    if (strlen($new_password) < 8) return false;
    $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
    return db_update('admin_users', ['password' => $hash], 'id = ?', [$admin_id]) > 0;
}

// ── Rate limiting (simple DB-free, file-based) ────────────────────

function rate_limit(string $key, int $max = 5, int $window = 300): bool {
    $file = sys_get_temp_dir() . '/mbc_rl_' . md5($key) . '.json';
    $now  = time();
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['hits' => [], 'blocked_until' => 0];

    if ($now < ($data['blocked_until'] ?? 0)) return false;

    // Clean old hits
    $data['hits'] = array_filter($data['hits'], fn($t) => $t > $now - $window);
    $data['hits'][] = $now;

    if (count($data['hits']) > $max) {
        $data['blocked_until'] = $now + $window;
        file_put_contents($file, json_encode($data));
        return false;
    }

    file_put_contents($file, json_encode($data));
    return true;
}
