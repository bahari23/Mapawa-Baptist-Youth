<?php
// includes/admin_layout.php
// Usage: require this at the top of every admin page AFTER setting $page_title and $active_tab
// Then call admin_footer() at the bottom.

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_admin_login();

$admin = current_admin();

// Unread counts for sidebar badges
$unread_prayers  = db_count('prayer_requests',  'is_read = 0');
$unread_messages = db_count('contact_messages', 'is_read = 0');

function admin_head(string $title): void {
    global $unread_prayers, $unread_messages;
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($title) ?> — Admin · <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css" />
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css" />
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/responsive.css" />
  <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/assets/images/logo.svg" />
</head>
<body class="admin-body">
<?php
}

function admin_sidebar(string $active): void {
    global $admin, $unread_prayers, $unread_messages;
    $nav = [
        ['href' => 'index.php',           'icon' => '📊', 'label' => 'Dashboard',        'key' => 'dashboard'],
        ['href' => 'sermons.php',         'icon' => '📖', 'label' => 'Sermons',           'key' => 'sermons'],
        ['href' => 'upload-sermon.php',   'icon' => '📤', 'label' => 'Upload Sermon',     'key' => 'upload'],
        ['href' => 'events.php',          'icon' => '📅', 'label' => 'Events',            'key' => 'events'],
        ['href' => 'gallery.php',         'icon' => '🖼️', 'label' => 'Gallery',           'key' => 'gallery'],
        ['href' => 'prayers.php',         'icon' => '🙏', 'label' => 'Prayer Requests',   'key' => 'prayers',  'badge' => $unread_prayers],
        ['href' => 'messages.php',        'icon' => '✉️', 'label' => 'Messages',          'key' => 'messages', 'badge' => $unread_messages],
        ['href' => 'settings.php',        'icon' => '⚙️', 'label' => 'Settings',          'key' => 'settings'],
    ];
    ?>
<aside class="admin-sidebar">
  <div class="admin-brand">
    <img src="<?= SITE_URL ?>/assets/images/logo.svg" alt="Logo" style="width:36px;" />
    <div>
      <div class="admin-brand-name">MBC Youth</div>
      <div class="admin-brand-sub">Admin Panel</div>
    </div>
  </div>

  <nav class="admin-nav">
    <?php foreach ($nav as $item): ?>
    <a href="<?= $item['href'] ?>" class="admin-nav-item <?= $active === $item['key'] ? 'active' : '' ?>">
      <span class="anav-icon"><?= $item['icon'] ?></span>
      <span class="anav-label"><?= $item['label'] ?></span>
      <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
        <span class="anav-badge"><?= $item['badge'] ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <div class="admin-sidebar-footer">
    <a href="<?= SITE_URL ?>/index.html" class="admin-nav-item" target="_blank">
      <span class="anav-icon">🌐</span><span class="anav-label">View Site</span>
    </a>
    <a href="logout.php" class="admin-nav-item admin-logout">
      <span class="anav-icon">🔒</span><span class="anav-label">Logout (<?= htmlspecialchars($admin['username']) ?>)</span>
    </a>
  </div>
</aside>
<?php
}

function admin_topbar(string $title): void {
    global $admin, $unread_prayers, $unread_messages;
    ?>
<div class="admin-topbar">
  <button class="admin-menu-toggle" onclick="document.querySelector('.admin-sidebar').classList.toggle('open')">☰</button>
  <h1 class="admin-page-title"><?= htmlspecialchars($title) ?></h1>
  <div class="admin-topbar-right">
    <?php if ($unread_prayers + $unread_messages > 0): ?>
      <a href="prayers.php" class="topbar-badge">🔔 <?= $unread_prayers + $unread_messages ?></a>
    <?php endif; ?>
    <span class="topbar-user">👤 <?= htmlspecialchars($admin['username']) ?></span>
  </div>
</div>
<?php
}

function admin_footer(): void {
    ?>
</div><!-- .admin-content -->
</div><!-- .admin-layout -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
<?php
}
