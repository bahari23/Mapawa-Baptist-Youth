<?php
require_once __DIR__ . '/../includes/admin_layout.php';

// ── Delete registration ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'delete') {
    csrf_check();
    db_delete('event_registrations', 'id = ?', [intpost('id')]);
    flash('success', 'Registration removed.');
    redirect(SITE_URL . '/admin/registrations.php?event_id=' . intpost('event_id'));
}

$event_id = intget('event_id');
$event    = $event_id ? db_fetch('SELECT * FROM events WHERE id = ?', [$event_id]) : null;

if (!$event) {
    // Show all events with registration counts
    $events = db_fetch_all(
        'SELECT e.*, COUNT(r.id) AS reg_count FROM events e
         LEFT JOIN event_registrations r ON r.event_id = e.id
         GROUP BY e.id ORDER BY e.event_date DESC'
    );

    admin_head('Event Registrations');
    ?>
    <div class="admin-layout">
    <?php admin_sidebar('events'); ?>
    <div class="admin-content">
    <?php admin_topbar('Event Registrations'); ?>
    <div class="admin-main">
    <?= render_flashes() ?>
    <div class="a-card" style="padding:0;overflow:hidden;">
      <table class="a-table">
        <thead><tr><th>Event</th><th>Date</th><th>Requires Reg.</th><th>Registrations</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($events as $e): ?>
        <tr>
          <td class="td-title"><?= htmlspecialchars($e['title']) ?></td>
          <td style="white-space:nowrap;font-size:0.85rem;"><?= fmt_date($e['event_date']) ?></td>
          <td><?= $e['requires_reg'] ? '<span class="badge badge-unread">Required</span>' : '<span class="badge badge-read">No</span>' ?></td>
          <td>
            <span style="font-family:var(--font-display);font-size:1rem;font-weight:700;color:var(--gold-bright);"><?= $e['reg_count'] ?></span>
          </td>
          <td><a href="registrations.php?event_id=<?= $e['id'] ?>" class="a-action">View List →</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    </div>
    <?php admin_footer();
    exit;
}

// ── Show registrations for specific event ────────────────────────
$regs = db_fetch_all('SELECT * FROM event_registrations WHERE event_id = ? ORDER BY created_at DESC', [$event_id]);

admin_head('Registrations: ' . $event['title']);
?>
<div class="admin-layout">
<?php admin_sidebar('events'); ?>
<div class="admin-content">
<?php admin_topbar('Registrations: ' . htmlspecialchars($event['title'])); ?>
<div class="admin-main">

<?= render_flashes() ?>

<div style="display:flex;gap:16px;align-items:center;margin-bottom:24px;flex-wrap:wrap;">
  <a href="registrations.php" class="a-action">← All Events</a>
  <span style="font-family:var(--font-display);font-size:0.65rem;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,249,238,0.4);">
    <?= count($regs) ?> registration(s) · <?= fmt_date($event['event_date']) ?> · <?= htmlspecialchars($event['location'] ?? '') ?>
  </span>
  <a href="registrations.php?event_id=<?= $event_id ?>&export=csv" class="btn btn-outline btn-sm" style="border-color:var(--gold-rich);color:var(--gold-rich);margin-left:auto;">📥 Export CSV</a>
</div>

<?php
// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="registrations-' . slugify($event['title']) . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name','Email','Phone','Age Group','Notes','Registered At']);
    foreach ($regs as $r) {
        fputcsv($out, [$r['name'], $r['email'], $r['phone'], $r['age_group'], $r['notes'], $r['created_at']]);
    }
    fclose($out);
    exit;
}
?>

<div class="a-card" style="padding:0;overflow:hidden;">
  <table class="a-table">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Age Group</th><th>Notes</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (empty($regs)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:rgba(255,249,238,0.3);">No registrations yet.</td></tr>
    <?php else: foreach ($regs as $r): ?>
    <tr>
      <td class="td-title"><?= htmlspecialchars($r['name']) ?></td>
      <td style="font-size:0.85rem;"><?= $r['email'] ? '<a href="mailto:'.htmlspecialchars($r['email']).'" style="color:var(--gold-rich);">'.htmlspecialchars($r['email']).'</a>' : '—' ?></td>
      <td style="font-size:0.85rem;color:rgba(255,249,238,0.55);"><?= htmlspecialchars($r['phone'] ?? '—') ?></td>
      <td style="font-size:0.82rem;color:rgba(255,249,238,0.55);"><?= htmlspecialchars($r['age_group'] ?? '—') ?></td>
      <td style="font-size:0.82rem;color:rgba(255,249,238,0.45);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($r['notes'] ?? '—') ?></td>
      <td style="font-size:0.82rem;color:rgba(255,249,238,0.4);"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
      <td>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this registration?')">
          <?= csrf_field() ?>
          <input type="hidden" name="_action"  value="delete">
          <input type="hidden" name="id"       value="<?= $r['id'] ?>">
          <input type="hidden" name="event_id" value="<?= $event_id ?>">
          <button type="submit" class="a-action a-action-danger">Remove</button>
        </form>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

</div>
<?php admin_footer(); ?>
