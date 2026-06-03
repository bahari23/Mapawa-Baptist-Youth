<?php
require_once __DIR__ . '/../includes/admin_layout.php';

// ── Change password ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'change_password') {
    csrf_check();
    $current  = post('current_password');
    $new_pass = post('new_password');
    $confirm  = post('confirm_password');
    $admin_id = current_admin()['id'];

    $user = db_fetch('SELECT password FROM admin_users WHERE id = ?', [$admin_id]);

    if (!password_verify($current, $user['password'])) {
        flash('error', 'Current password is incorrect.');
    } elseif (strlen($new_pass) < 8) {
        flash('error', 'New password must be at least 8 characters.');
    } elseif ($new_pass !== $confirm) {
        flash('error', 'Passwords do not match.');
    } else {
        admin_change_password($admin_id, $new_pass);
        flash('success', 'Password changed successfully. Please log in again.');
        admin_logout();
        redirect(SITE_URL . '/admin/login.php');
    }
    redirect(SITE_URL . '/admin/settings.php#password');
}

// ── Add admin user (super_admin only) ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'add_user') {
    csrf_check();
    if (!admin_is_super()) {
        flash('error', 'Only super admins can add users.');
    } else {
        $username = post('new_username');
        $email    = post('new_email');
        $pass     = post('new_password');
        $role     = in_array(post('new_role'),['super_admin','admin','editor']) ? post('new_role') : 'editor';

        if (empty($username) || empty($pass)) {
            flash('error', 'Username and password are required.');
        } elseif (strlen($pass) < 8) {
            flash('error', 'Password must be at least 8 characters.');
        } elseif (db_count('admin_users', 'username = ?', [$username]) > 0) {
            flash('error', 'Username already taken.');
        } else {
            db_insert('admin_users', [
                'username'  => $username,
                'email'     => $email ?: $username . '@mapawabaptist.org',
                'password'  => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
                'role'      => $role,
                'is_active' => 1,
            ]);
            flash('success', "Admin user '$username' created.");
        }
    }
    redirect(SITE_URL . '/admin/settings.php#users');
}

// ── Delete admin user ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'delete_user') {
    csrf_check();
    $del_id = intpost('user_id');
    if (!admin_is_super()) {
        flash('error', 'Only super admins can delete users.');
    } elseif ($del_id === current_admin()['id']) {
        flash('error', 'You cannot delete your own account.');
    } else {
        db_update('admin_users', ['is_active' => 0], 'id = ?', [$del_id]);
        flash('success', 'User deactivated.');
    }
    redirect(SITE_URL . '/admin/settings.php#users');
}

// ── Toggle user active ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'toggle_user') {
    csrf_check();
    if (admin_is_super()) {
        $u = db_fetch('SELECT is_active FROM admin_users WHERE id = ?', [intpost('user_id')]);
        if ($u) db_update('admin_users', ['is_active' => $u['is_active'] ? 0 : 1], 'id = ?', [intpost('user_id')]);
        flash('success', 'User status updated.');
    }
    redirect(SITE_URL . '/admin/settings.php#users');
}

// ── Data ──────────────────────────────────────────────────────────
$admin_users = db_fetch_all('SELECT id, username, email, role, is_active, last_login, created_at FROM admin_users ORDER BY created_at ASC');
$current_id  = current_admin()['id'];
$me          = db_fetch('SELECT * FROM admin_users WHERE id = ?', [$current_id]);

admin_head('Settings');
?>
<div class="admin-layout">
<?php admin_sidebar('settings'); ?>
<div class="admin-content">
<?php admin_topbar('Settings'); ?>
<div class="admin-main">

<?= render_flashes() ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

  <!-- Change Password -->
  <div class="a-card" id="password">
    <div class="a-card-title">🔒 Change Password</div>
    <div style="background:rgba(232,160,0,0.08);border:1px solid rgba(232,160,0,0.2);border-radius:6px;padding:12px 16px;margin-bottom:20px;font-family:var(--font-display);font-size:0.62rem;letter-spacing:0.1em;text-transform:uppercase;color:var(--gold-rich);">
      Default password: <strong>MBC@Admin2025!</strong> — Change this immediately.
    </div>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="_action" value="change_password">
      <div class="a-form-group">
        <label class="a-label">Current Password</label>
        <input type="password" name="current_password" class="a-input" required autocomplete="current-password" />
      </div>
      <div class="a-form-group">
        <label class="a-label">New Password <span style="opacity:.5;">(min 8 characters)</span></label>
        <input type="password" name="new_password" id="new_pass" class="a-input" required autocomplete="new-password" minlength="8" />
      </div>
      <div class="a-form-group">
        <label class="a-label">Confirm New Password</label>
        <input type="password" name="confirm_password" class="a-input" required autocomplete="new-password" minlength="8" />
      </div>
      <div style="margin-bottom:16px;">
        <div style="font-family:var(--font-display);font-size:0.6rem;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,249,238,0.35);margin-bottom:6px;">Password Strength</div>
        <div class="progress-bar-wrap"><div class="progress-bar-fill" id="strength-bar"></div></div>
        <div id="strength-label" style="font-family:var(--font-display);font-size:0.58rem;letter-spacing:0.08em;text-transform:uppercase;color:rgba(255,249,238,0.3);margin-top:4px;"></div>
      </div>
      <button type="submit" class="btn btn-gold">Update Password</button>
    </form>
  </div>

  <!-- My Profile -->
  <div class="a-card">
    <div class="a-card-title">👤 My Account</div>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <div style="background:rgba(255,255,255,0.03);border-radius:6px;padding:14px;">
        <div class="a-label" style="margin-bottom:4px;">Username</div>
        <div style="font-family:var(--font-serif);font-size:1.05rem;color:var(--cream);"><?= htmlspecialchars($me['username']) ?></div>
      </div>
      <div style="background:rgba(255,255,255,0.03);border-radius:6px;padding:14px;">
        <div class="a-label" style="margin-bottom:4px;">Email</div>
        <div style="font-size:0.95rem;color:rgba(255,249,238,0.75);"><?= htmlspecialchars($me['email']) ?></div>
      </div>
      <div style="background:rgba(255,255,255,0.03);border-radius:6px;padding:14px;">
        <div class="a-label" style="margin-bottom:4px;">Role</div>
        <span class="badge badge-published"><?= htmlspecialchars($me['role']) ?></span>
      </div>
      <div style="background:rgba(255,255,255,0.03);border-radius:6px;padding:14px;">
        <div class="a-label" style="margin-bottom:4px;">Last Login</div>
        <div style="font-size:0.9rem;color:rgba(255,249,238,0.5);"><?= $me['last_login'] ? fmt_datetime($me['last_login']) : 'First login' ?></div>
      </div>
    </div>
  </div>

</div>

<!-- Admin Users -->
<?php if (admin_is_super()): ?>
<div class="a-card" id="users" style="margin-top:24px;">
  <div class="a-card-title">👥 Admin Users</div>

  <!-- Add user form -->
  <details style="margin-bottom:24px;">
    <summary style="font-family:var(--font-display);font-size:0.65rem;letter-spacing:0.12em;text-transform:uppercase;color:var(--gold-rich);cursor:pointer;padding:8px 0;">+ Add New Admin User</summary>
    <form method="POST" style="margin-top:16px;">
      <?= csrf_field() ?>
      <input type="hidden" name="_action" value="add_user">
      <div class="a-grid-2">
        <div class="a-form-group">
          <label class="a-label">Username *</label>
          <input name="new_username" class="a-input" required placeholder="e.g. pastor_john" />
        </div>
        <div class="a-form-group">
          <label class="a-label">Email</label>
          <input type="email" name="new_email" class="a-input" placeholder="john@mapawa.org" />
        </div>
        <div class="a-form-group">
          <label class="a-label">Password * <span style="opacity:.5;">(min 8 chars)</span></label>
          <input type="password" name="new_password" class="a-input" required minlength="8" />
        </div>
        <div class="a-form-group">
          <label class="a-label">Role</label>
          <select name="new_role" class="a-input">
            <option value="editor">Editor — can upload sermons &amp; manage content</option>
            <option value="admin">Admin — full access except user management</option>
            <option value="super_admin">Super Admin — full access</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-gold btn-sm">Create User</button>
    </form>
  </details>

  <!-- Users table -->
  <table class="a-table">
    <thead>
      <tr><th>Username</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($admin_users as $u): ?>
    <tr>
      <td style="font-family:var(--font-serif);color:var(--cream);">
        <?= htmlspecialchars($u['username']) ?>
        <?= $u['id'] === $current_id ? ' <span style="font-family:var(--font-display);font-size:0.55rem;color:var(--gold-rich);">(you)</span>' : '' ?>
      </td>
      <td style="font-size:0.85rem;color:rgba(255,249,238,0.5);"><?= htmlspecialchars($u['email']) ?></td>
      <td><span class="badge badge-draft"><?= $u['role'] ?></span></td>
      <td style="font-size:0.82rem;color:rgba(255,249,238,0.4);"><?= $u['last_login'] ? date('d M Y', strtotime($u['last_login'])) : 'Never' ?></td>
      <td><span class="badge badge-<?= $u['is_active'] ? 'published':'archived' ?>"><?= $u['is_active'] ? 'Active':'Inactive' ?></span></td>
      <td>
        <?php if ($u['id'] !== $current_id): ?>
        <form method="POST" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="_action" value="toggle_user">
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <button type="submit" class="a-action"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
        </form>
        <?php else: ?>
        <span style="font-family:var(--font-display);font-size:0.58rem;color:rgba(255,249,238,0.2);">Current user</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Session log -->
<div class="a-card" style="margin-top:24px;">
  <div class="a-card-title">📋 Recent Login Activity</div>
  <?php
  $sessions = db_fetch_all('SELECT s.*, u.username FROM admin_sessions s JOIN admin_users u ON u.id = s.admin_id ORDER BY s.logged_in DESC LIMIT 10');
  ?>
  <table class="a-table">
    <thead><tr><th>User</th><th>IP</th><th>Logged In</th><th>Logged Out</th></tr></thead>
    <tbody>
    <?php if (empty($sessions)): ?>
      <tr><td colspan="4" style="text-align:center;color:rgba(255,249,238,0.3);">No sessions recorded.</td></tr>
    <?php else: foreach ($sessions as $s): ?>
    <tr>
      <td style="font-family:var(--font-serif);color:var(--cream);"><?= htmlspecialchars($s['username']) ?></td>
      <td style="font-size:0.85rem;color:rgba(255,249,238,0.4);"><?= htmlspecialchars($s['ip_address']) ?></td>
      <td style="font-size:0.85rem;color:rgba(255,249,238,0.5);"><?= fmt_datetime($s['logged_in']) ?></td>
      <td style="font-size:0.85rem;color:rgba(255,249,238,0.4);"><?= $s['logged_out'] ? fmt_datetime($s['logged_out']) : '<span style="color:#6dbf6d;">Active</span>' ?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

</div>
<?php admin_footer(); ?>
<script>
// Password strength meter
document.getElementById('new_pass').addEventListener('input', function() {
  const v = this.value, bar = document.getElementById('strength-bar'), lbl = document.getElementById('strength-label');
  let score = 0;
  if (v.length >= 8)  score++;
  if (v.length >= 12) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;
  const levels = [{w:'20%',c:'#f44336',l:'Weak'},{w:'40%',c:'#ff9800',l:'Fair'},{w:'60%',c:'#ffc107',l:'Good'},{w:'80%',c:'#8bc34a',l:'Strong'},{w:'100%',c:'#4caf50',l:'Very Strong'}];
  const lv = levels[Math.min(score, 4)];
  bar.style.width = lv.w; bar.style.background = lv.c;
  lbl.textContent = lv.l; lbl.style.color = lv.c;
});
</script>
