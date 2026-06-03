<?php
require_once __DIR__ . '/../includes/admin_layout.php';

// ── Mark read/unread ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'mark_read') {
    csrf_check();
    db_update('prayer_requests', ['is_read' => 1], 'id = ?', [intpost('id')]);
    redirect(SITE_URL . '/admin/prayers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'mark_all_read') {
    csrf_check();
    db_query('UPDATE prayer_requests SET is_read = 1 WHERE is_read = 0');
    flash('success', 'All requests marked as read.');
    redirect(SITE_URL . '/admin/prayers.php');
}

// ── Save note ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'save_note') {
    csrf_check();
    db_update('prayer_requests', [
        'admin_note' => post('admin_note'),
        'is_read'    => 1,
    ], 'id = ?', [intpost('id')]);
    flash('success', 'Note saved.');
    redirect(SITE_URL . '/admin/prayers.php');
}

// ── Delete ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'delete') {
    csrf_check();
    db_delete('prayer_requests', 'id = ?', [intpost('id')]);
    flash('success', 'Request deleted.');
    redirect(SITE_URL . '/admin/prayers.php');
}

// ── List ─────────────────────────────────────────────────────────
$filter    = get('filter', 'all');
$where     = '1';
if ($filter === 'unread') $where = 'is_read = 0';
if ($filter === 'public')  $where = 'is_public = 1';
if ($filter === 'anon')    $where = 'is_anonymous = 1';

$page     = max(1, intget('page', 1));
$per_page = 20;
$total    = db_count('prayer_requests', $where);
$pg       = paginate($total, $per_page, $page);
$requests = db_fetch_all("SELECT * FROM prayer_requests WHERE $where ORDER BY created_at DESC LIMIT $per_page OFFSET {$pg['offset']}");

$viewing  = null;
if (isset($_GET['view'])) {
    $viewing = db_fetch('SELECT * FROM prayer_requests WHERE id = ?', [(int)$_GET['view']]);
    // Auto-mark as read
    if ($viewing && !$viewing['is_read']) {
        db_update('prayer_requests', ['is_read' => 1], 'id = ?', [$viewing['id']]);
        $viewing['is_read'] = 1;
    }
}

admin_head('Prayer Requests');
?>
<div class="admin-layout">
<?php admin_sidebar('prayers'); ?>
<div class="admin-content">
<?php admin_topbar('Prayer Requests'); ?>
<div class="admin-main">

<?= render_flashes() ?>

<?php if ($viewing): ?>
<!-- View single request -->
<div class="a-card" style="border-color:rgba(245,200,66,0.3);margin-bottom:24px;">
  <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
    <div>
      <div class="a-card-title" style="margin-bottom:4px;">🙏 Prayer Request #<?= $viewing['id'] ?></div>
      <div style="font-family:var(--font-display);font-size:0.6rem;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,249,238,0.35);">
        Received <?= fmt_datetime($viewing['created_at']) ?>
      </div>
    </div>
    <a href="prayers.php" class="a-action">← Back to list</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    <div style="background:rgba(255,255,255,0.03);border-radius:6px;padding:14px;">
      <div class="a-label" style="margin-bottom:4px;">From</div>
      <div style="font-family:var(--font-serif);font-size:1rem;color:var(--cream);">
        <?= $viewing['is_anonymous'] ? '<em style="color:rgba(255,249,238,0.4);">Anonymous</em>' : htmlspecialchars($viewing['name'] ?? 'Unknown') ?>
      </div>
    </div>
    <div style="background:rgba(255,255,255,0.03);border-radius:6px;padding:14px;">
      <div class="a-label" style="margin-bottom:4px;">Category</div>
      <div style="font-family:var(--font-serif);font-size:1rem;color:var(--cream);"><?= htmlspecialchars($viewing['category'] ?? 'General') ?></div>
    </div>
  </div>

  <div style="background:rgba(245,200,66,0.04);border-left:3px solid var(--gold-rich);border-radius:0 6px 6px 0;padding:20px;margin-bottom:20px;">
    <div class="a-label" style="margin-bottom:8px;">Prayer Request</div>
    <p style="font-family:var(--font-body);font-size:1.05rem;color:rgba(255,249,238,0.85);line-height:1.75;white-space:pre-wrap;"><?= htmlspecialchars($viewing['request']) ?></p>
  </div>

  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="_action" value="save_note">
    <input type="hidden" name="id" value="<?= $viewing['id'] ?>">
    <div class="a-form-group">
      <label class="a-label">Admin Note (private — not visible to requester)</label>
      <textarea name="admin_note" class="a-input" style="min-height:80px;" placeholder="Prayer note, follow-up, or action taken…"><?= htmlspecialchars($viewing['admin_note'] ?? '') ?></textarea>
    </div>
    <div style="display:flex;gap:12px;">
      <button type="submit" class="btn btn-gold btn-sm">Save Note</button>
      <form method="POST" style="display:inline;">
        <?= csrf_field() ?>
        <input type="hidden" name="_action" value="delete">
        <input type="hidden" name="id" value="<?= $viewing['id'] ?>">
        <button type="submit" class="btn btn-outline btn-sm a-action-danger" style="border-color:rgba(248,136,136,0.3);color:#f88;" onclick="return confirm('Delete this request?')">Delete</button>
      </form>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Controls -->
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
  <div style="display:flex;gap:6px;flex-wrap:wrap;">
    <?php foreach (['all'=>'All','unread'=>'Unread','public'=>'Public','anon'=>'Anonymous'] as $k=>$l): ?>
      <a href="prayers.php?filter=<?= $k ?>"
         style="font-family:var(--font-display);font-size:0.6rem;letter-spacing:0.1em;text-transform:uppercase;padding:6px 12px;border-radius:2px;border:1px solid <?= $filter===$k ? 'var(--gold-rich)':'rgba(245,200,66,0.15)' ?>;background:<?= $filter===$k ? 'var(--gold-rich)':'transparent' ?>;color:<?= $filter===$k ? 'var(--blue-deep)':'rgba(255,249,238,0.5)' ?>;text-decoration:none;"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="_action" value="mark_all_read">
    <button type="submit" class="btn btn-outline btn-sm" style="border-color:rgba(245,200,66,0.2);color:rgba(255,249,238,0.5);">Mark All Read</button>
  </form>
</div>

<!-- Table -->
<div class="a-card" style="padding:0;overflow:hidden;">
  <table class="a-table">
    <thead>
      <tr><th>Date</th><th>Name</th><th>Category</th><th>Request</th><th>Flags</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (empty($requests)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:rgba(255,249,238,0.3);">No prayer requests found.</td></tr>
    <?php else: foreach ($requests as $r): ?>
      <tr style="<?= !$r['is_read'] ? 'background:rgba(245,200,66,0.03);' : '' ?>">
        <td style="white-space:nowrap;font-size:0.85rem;color:rgba(255,249,238,0.4);"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
        <td style="font-family:var(--font-serif);font-size:0.95rem;color:var(--cream);">
          <?= $r['is_anonymous'] ? '<em style="color:rgba(255,249,238,0.3);">Anonymous</em>' : htmlspecialchars($r['name'] ?? '—') ?>
        </td>
        <td><span style="font-family:var(--font-display);font-size:0.58rem;letter-spacing:0.08em;color:rgba(255,249,238,0.4);"><?= htmlspecialchars($r['category'] ?? '—') ?></span></td>
        <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.9rem;color:rgba(255,249,238,0.65);">
          <?= htmlspecialchars(substr($r['request'], 0, 90)) ?>…
        </td>
        <td>
          <?= $r['is_anonymous'] ? '<span title="Anonymous">🕶️</span>' : '' ?>
          <?= $r['is_public']    ? '<span title="Public">🌐</span>'     : '' ?>
          <?= $r['admin_note']   ? '<span title="Has note">📝</span>'   : '' ?>
        </td>
        <td><span class="badge badge-<?= $r['is_read'] ? 'read' : 'unread' ?>"><?= $r['is_read'] ? 'Read' : 'New' ?></span></td>
        <td>
          <a href="prayers.php?view=<?= $r['id'] ?>" class="a-action">View</a>
          <?php if (!$r['is_read']): ?>
          <form method="POST" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="mark_read">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button type="submit" class="a-action">✓ Read</button>
          </form>
          <?php endif; ?>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this request?')">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="delete">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button type="submit" class="a-action a-action-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<?php if ($pg['total_pages'] > 1): ?>
<div class="a-pagination">
  <?php if ($pg['has_prev']): ?><a href="?filter=<?= $filter ?>&page=<?= $pg['current']-1 ?>">← Prev</a><?php endif; ?>
  <?php for ($i=1; $i<=$pg['total_pages']; $i++): ?>
    <a href="?filter=<?= $filter ?>&page=<?= $i ?>" class="<?= $i===$pg['current'] ? 'current':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
  <?php if ($pg['has_next']): ?><a href="?filter=<?= $filter ?>&page=<?= $pg['current']+1 ?>">Next →</a><?php endif; ?>
</div>
<?php endif; ?>

</div>
<?php admin_footer(); ?>
