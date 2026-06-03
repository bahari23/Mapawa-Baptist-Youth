<?php
// ================================================================
// includes/helpers.php — General utility functions
// ================================================================

require_once __DIR__ . '/config.php';

// ── JSON response ─────────────────────────────────────────────────

function json_ok(array $data = [], string $message = 'OK'): never {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => true, 'message' => $message], $data));
    exit;
}

function json_err(string $message, int $code = 400): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// ── Slugify ───────────────────────────────────────────────────────

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function unique_slug(string $table, string $base): string {
    $slug = slugify($base);
    $orig = $slug;
    $i    = 1;
    while (db_count($table, 'slug = ?', [$slug]) > 0) {
        $slug = $orig . '-' . $i++;
    }
    return $slug;
}

// ── File upload ───────────────────────────────────────────────────

function ensure_upload_dirs(): void {
    $dirs = [
        UPLOAD_DIR,
        UPLOAD_DIR . 'audio/',
        UPLOAD_DIR . 'video/',
        UPLOAD_DIR . 'pdf/',
        UPLOAD_DIR . 'gallery/',
        UPLOAD_DIR . 'thumbnails/',
        UPLOAD_DIR . 'events/',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }
    // Prevent direct PHP execution in uploads
    $htaccess = UPLOAD_DIR . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Options -Indexes\nphp_flag engine off\n");
    }
}

function handle_upload(string $field, string $subdir, array $allowed_exts, int $max_mb = 0): array {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form limit.',
            UPLOAD_ERR_PARTIAL    => 'File only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp directory.',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by extension.',
        ];
        $code = $_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE;
        return ['ok' => false, 'error' => $errors[$code] ?? 'Upload error.'];
    }

    $file    = $_FILES[$field];
    $max_mb  = $max_mb > 0 ? $max_mb : MAX_UPLOAD_MB;
    $max_b   = $max_mb * 1024 * 1024;

    if ($file['size'] > $max_b) {
        return ['ok' => false, 'error' => "File exceeds {$max_mb}MB limit."];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts, true)) {
        return ['ok' => false, 'error' => 'File type not allowed. Allowed: ' . implode(', ', $allowed_exts)];
    }

    // Verify MIME for images
    if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
        $info = getimagesize($file['tmp_name']);
        if (!$info) return ['ok' => false, 'error' => 'Invalid image file.'];
    }

    ensure_upload_dirs();
    $dest_dir = UPLOAD_DIR . trim($subdir, '/') . '/';
    $filename = uniqid('mbc_', true) . '.' . $ext;
    $dest     = $dest_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => 'Failed to save file.'];
    }

    return [
        'ok'       => true,
        'filename' => $filename,
        'path'     => 'assets/uploads/' . trim($subdir, '/') . '/' . $filename,
        'url'      => UPLOAD_URL . trim($subdir, '/') . '/' . $filename,
        'ext'      => $ext,
        'size'     => $file['size'],
    ];
}

// ── Pagination ────────────────────────────────────────────────────

function paginate(int $total, int $per_page, int $current_page): array {
    $total_pages = (int) ceil($total / $per_page);
    $current_page = max(1, min($current_page, $total_pages));
    return [
        'total'       => $total,
        'per_page'    => $per_page,
        'current'     => $current_page,
        'total_pages' => $total_pages,
        'offset'      => ($current_page - 1) * $per_page,
        'has_prev'    => $current_page > 1,
        'has_next'    => $current_page < $total_pages,
    ];
}

// ── Sanitize ─────────────────────────────────────────────────────

function clean(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function post(string $key, mixed $default = ''): mixed {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function get(string $key, mixed $default = ''): mixed {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

function intpost(string $key, int $default = 0): int {
    return isset($_POST[$key]) ? (int)$_POST[$key] : $default;
}

function intget(string $key, int $default = 0): int {
    return isset($_GET[$key]) ? (int)$_GET[$key] : $default;
}

// ── Flash messages ───────────────────────────────────────────────

function flash(string $type, string $msg): void {
    session_start_secure();
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flashes(): array {
    session_start_secure();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function render_flashes(): string {
    $html = '';
    foreach (get_flashes() as $f) {
        $cls = $f['type'] === 'success' ? '#1a5c1a' : ($f['type'] === 'error' ? 'var(--burgundy)' : 'var(--blue-mid)');
        $html .= '<div style="background:'.$cls.';color:var(--cream);border-radius:6px;padding:12px 20px;margin-bottom:12px;font-family:var(--font-display);font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;">'
               . clean($f['msg'])
               . '</div>';
    }
    return $html;
}

// ── Format helpers ────────────────────────────────────────────────

function fmt_date(string $date): string {
    return date('F j, Y', strtotime($date));
}

function fmt_datetime(string $dt): string {
    return date('M j, Y · g:i A', strtotime($dt));
}

function bytes_human(int $bytes): string {
    foreach (['B','KB','MB','GB'] as $unit) {
        if ($bytes < 1024) return round($bytes, 2) . ' ' . $unit;
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' TB';
}

// ── Redirect helper ───────────────────────────────────────────────

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}
