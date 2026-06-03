<?php
require_once __DIR__ . '/../includes/admin_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'delete') {
    csrf_check();
    db_delete('contact_messages', 'id = ?', [intpost('id')]);
    flash('success', 'Message deleted.');
    redirect(SITE_URL . '/admin/messages.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'mark_all_read') {
    csrf_check();
    db_query('UPDATE contact_messages SET is_read = 1 WHERE is_read = 0');
    flash('success', 'All messages marked as read.');
    redirect(SITE_URL . '/admin/messages.php');
}

$filter   = get('filter', 'all');
$where    = '1';
if ($filter === 'unread') $where = 'is_read = 0';

$page     = max(1, intget('page', 1));
$per_page = 20;
$total    = db_count('contact_messages', $where);
$pg       = paginate($total, $per_page, $page);
$messages = db_fetch_all("SELECT * FROM contact_messages WHERE $where ORDER BY created_at DESC LIMIT $per_page OFFSET {$pg['offset']}");

$viewing = null;
if (isset($_GET['view'])) {
    $viewing = db_fetch('SELECT * FROM contact_messages WHERE id = ?', [(int)$_GET['view']]);
    if ($viewing && !$viewing['is_read']) {
        db_update('contact_messages', ['is_read' => 1], 'id = ?', [$viewing['id']]);
        $viewing['is_read'] = 1;
    }
}

admin_head('Messages');
?>
<div class="admin-layout">
<?php admin_sidebar('messages'); ?>
<div class="admin-content">
<?php admin_topbar('Contact Messages'); ?>
<div class="admin-main">

<?= render_flashes() ?>

<?php if ($viewing): ?>
<div class="a-card" style="border-color:rgba(245,200,66,0.3);margin-bottom:24px;">
  <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:20px;">
    <div>
      <div class="a-card-title" style="margin-bottom:4px;">✉️ Message #<?= $viewing['id'] ?></div>
      <div style="font-family:var(--font-display);font-size:0.6rem;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,249,238,0.35);">Received <?= fmt_datetime($viewing['created_at']) ?></div>
    </div>
    <a href="messages.php" class="a-action">← Back</a>
  </div>

  <div class="a-grid-3" style="margin-bottom:20px;">
    <div style="background:rgba(255,255,255,0.03);border-radius:6px;padding:14px;">
      <div class="a-label" style="margin-bottom:4px;">From</div>
      <div style="font-family:var(--font-serif);color:var(--cream);"><?= htmlspecialchars($viewing['name']) ?></div>
    </div>
    <div style="background:rgba(255,255,255,0.03);border-radius:6px;padding:14px;">
      <div class="a-label" style="margin-bottom:4px;">Email</div>
      <div style="font-size:0.9rem;color:var(--gold-rich);">
        <?= $viewing['email'] ? '<a href="mailto:'.htmlspecialchars($viewing['email']).'" style="color:var(--gold-rich);">'.htmlspecialchars($viewing['email']).'</a>' : '—' ?>
      </div>
    </div>
    <div style="background:rgba(255,255,255,0.03);border-radius:6px;padding:14px;">
      <div class="a-label" style="margin-bottom:4px;">Subject</div>
      <div style="font-size:0.9rem;color:rgba(255,249,238,0.75);"><?= htmlspecialchars($viewing['subject'] ?? '—') ?></div>
    </div>
  </div>

  <div style="background:rgba(21,101,192,0.06);border-left:3px solid var(--blue-mid);border-radius:0 6px 6px 0;padding:20px;margin-bottom:20px;">
    <div class="a-label" style="margin-bottom:8px;">Message</div>
    <p style="font-family:var(--font-body);font-size:1.05rem;color:rgba(255,249,238,0.85);line-height:1.75;white-space:pre-wrap;"><?= htmlspecialchars($viewing['message']) ?></p>
  </div>

  <?php if ($viewing['email']): ?>
  <div style="margin-bottom:16px;">
    <a href="mailto:<?= htmlspecialchars($viewing['email']) ?>?subject=Re: <?= rawurlencode($viewing['subject'] ?? 'Your message') ?>"
       class="btn btn-gold btn-sm">↩ Reply via Email</a>
  </div>
  <?php endif; ?>

  <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this message?')">
    <?= csrf_field() ?>
    <input type="hidden" name="_action" value="delete">
    <input type="hidden" name="id" value="<?= $viewing['id'] ?>">
    <button type="submit" class="a-action a-action-danger">Delete Message</button>
  </form>
</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
  <div style="display:flex;gap:6px;">
    <?php foreach (['all'=>'All','unread'=>'Unread'] as $k=>$l): ?>
      <a href="messages.php?filter=<?= $k ?>"
         style="font-family:var(--font-display);font-size:0.6rem;letter-spacing:0.1em;text-transform:uppercase;padding:6px 12px;border-radius:2px;border:1px solid <?= $filter===$k ? 'var(--gold-rich)':'rgba(245,200,66,0.15)' ?>;background:<?= $filter===$k ? 'var(--gold-rich)':'transparent' ?>;color:<?= $filter===$k ? 'var(--blue-deep)':'rgba(255,249,238,0.5)' ?>;text-decoration:none;"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="_action" value="mark_all_read">
    <button type="submit" class="btn btn-outline btn-sm" style="border-color:rgba(245,200,66,0.2);color:rgba(255,249,238,0.5);">Mark All Read</button>
  </form>
</div>

<div class="a-card" style="padding:0;overflow:hidden;">
  <table class="a-table">
    <thead>
      <tr><th>Date</th><th>Name</th><th>Email</th><th>Subject</th><th>Preview</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (empty($messages)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:rgba(255,249,238,0.3);">No messages found.</td></tr>
    <?php else: foreach ($messages as $m): ?>
      <tr style="<?= !$m['is_read'] ? 'background:rgba(21,101,192,0.05);' : '' ?>">
        <td style="white-space:nowrap;font-size:0.85rem;color:rgba(255,249,238,0.4);"><?= date('d M Y', strtotime($m['created_at'])) ?></td>
        <td style="font-family:var(--font-serif);color:var(--cream);"><?= htmlspecialchars($m['name']) ?></td>
        <td style="font-size:0.82rem;">
          <?= $m['email'] ? '<a href="mailto:'.htmlspecialchars($m['email']).'" style="color:var(--gold-rich);">'.htmlspecialchars($m['email']).'</a>' : '—' ?>
        </td>
        <td style="font-size:0.85rem;color:rgba(255,249,238,0.65);"><?= htmlspecialchars($m['subject'] ?? '—') ?></td>
        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.85rem;color:rgba(255,249,238,0.45);">
          <?= htmlspecialchars(substr($m['message'], 0, 70)) ?>…
        </td>
        <td><span class="badge badge-<?= $m['is_read'] ? 'read':'unread' ?>"><?= $m['is_read'] ? 'Read':'New' ?></span></td>
        <td>
          <a href="messages.php?view=<?= $m['id'] ?>" class="a-action">View</a>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="delete">
            <input type="hidden" name="id" value="<?= $m['id'] ?>">
            <button type="submit" class="a-action a-action-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php if ($pg['total_pages'] > 1): ?>
<div class="a-pagination">
  <?php if ($pg['has_prev']): ?><a href="?filter=<?= $filter ?>&page=<?= $pg['current']-1 ?>">← Prev</a><?php endif; ?>
  <?php for ($i=1;$i<=$pg['total_pages'];$i++): ?>
    <a href="?filter=<?= $filter ?>&page=<?= $i ?>" class="<?= $i===$pg['current']?'current':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
  <?php if ($pg['has_next']): ?><a href="?filter=<?= $filter ?>&page=<?= $pg['current']+1 ?>">Next →</a><?php endif; ?>
</div>
<?php endif; ?>

</div>
<?php admin_footer(); ?>
