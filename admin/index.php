<?php
require_once __DIR__ . '/../includes/admin_layout.php';

$stats = [
  'sermons_total'     => db_count('sermons'),
  'sermons_published' => db_count('sermons', 'status = ?', ['published']),
  'sermons_draft'     => db_count('sermons', 'status = ?', ['draft']),
  'events_upcoming'   => db_count('events',  'status = ?', ['upcoming']),
  'prayers_unread'    => db_count('prayer_requests',  'is_read = 0'),
  'messages_unread'   => db_count('contact_messages', 'is_read = 0'),
  'gallery_total'     => db_count('gallery_items', 'is_visible = 1'),
  'registrations'     => db_count('event_registrations'),
];

$recent_sermons  = db_fetch_all('SELECT * FROM sermons ORDER BY created_at DESC LIMIT 5');
$recent_prayers  = db_fetch_all('SELECT * FROM prayer_requests ORDER BY created_at DESC LIMIT 5');
$upcoming_events = db_fetch_all("SELECT * FROM events WHERE status = 'upcoming' ORDER BY event_date ASC LIMIT 4");

admin_head('Dashboard');
?>
<div class="admin-layout">
<?php admin_sidebar('dashboard'); ?>
<div class="admin-content">
<?php admin_topbar('Dashboard'); ?>
<div class="admin-main">

<?= render_flashes() ?>

<!-- Stats -->
<div class="stats-row">
  <div class="stat-box">
    <div class="stat-n"><?= $stats['sermons_total'] ?></div>
    <div class="stat-l">Total Sermons</div>
  </div>
  <div class="stat-box">
    <div class="stat-n"><?= $stats['sermons_published'] ?></div>
    <div class="stat-l">Published</div>
  </div>
  <div class="stat-box">
    <div class="stat-n"><?= $stats['events_upcoming'] ?></div>
    <div class="stat-l">Upcoming Events</div>
  </div>
  <div class="stat-box">
    <div class="stat-n" style="color:<?= $stats['prayers_unread'] > 0 ? '#f5c842' : 'inherit' ?>"><?= $stats['prayers_unread'] + $stats['messages_unread'] ?></div>
    <div class="stat-l">Unread Inbox</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

  <!-- Recent Sermons -->
  <div class="a-card">
    <div class="a-card-title">📖 Recent Sermons</div>
    <?php if (empty($recent_sermons)): ?>
      <p style="color:rgba(255,249,238,0.3);font-size:0.9rem;">No sermons yet. <a href="upload-sermon.php" style="color:var(--gold-rich);">Upload one →</a></p>
    <?php else: ?>
      <?php foreach ($recent_sermons as $s): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(245,200,66,0.06);">
        <div style="flex:1;">
          <div style="font-family:var(--font-serif);font-size:0.95rem;color:var(--cream);"><?= htmlspecialchars($s['title']) ?></div>
          <div style="font-family:var(--font-display);font-size:0.58rem;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,249,238,0.35);">
            <?= htmlspecialchars($s['scripture_ref'] ?? '') ?>
          </div>
        </div>
        <span class="badge badge-<?= $s['status'] ?>"><?= $s['status'] ?></span>
        <a href="sermons.php?edit=<?= $s['id'] ?>" class="a-action">Edit</a>
      </div>
      <?php endforeach; ?>
      <div style="margin-top:14px;">
        <a href="sermons.php" class="a-action">View All Sermons →</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Upcoming Events -->
  <div class="a-card">
    <div class="a-card-title">📅 Upcoming Events</div>
    <?php if (empty($upcoming_events)): ?>
      <p style="color:rgba(255,249,238,0.3);font-size:0.9rem;">No upcoming events. <a href="events.php" style="color:var(--gold-rich);">Add one →</a></p>
    <?php else: ?>
      <?php foreach ($upcoming_events as $e): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(245,200,66,0.06);">
        <div style="background:rgba(245,200,66,0.1);border-radius:6px;padding:8px 12px;text-align:center;min-width:50px;">
          <div style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:var(--gold-bright);line-height:1;"><?= date('d', strtotime($e['event_date'])) ?></div>
          <div style="font-family:var(--font-display);font-size:0.55rem;letter-spacing:0.08em;text-transform:uppercase;color:rgba(245,200,66,0.6);"><?= date('M', strtotime($e['event_date'])) ?></div>
        </div>
        <div style="flex:1;">
          <div style="font-family:var(--font-serif);font-size:0.95rem;color:var(--cream);"><?= htmlspecialchars($e['title']) ?></div>
          <div style="font-family:var(--font-display);font-size:0.58rem;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,249,238,0.35);"><?= htmlspecialchars($e['location'] ?? '') ?></div>
        </div>
        <a href="events.php?edit=<?= $e['id'] ?>" class="a-action">Edit</a>
      </div>
      <?php endforeach; ?>
      <div style="margin-top:14px;">
        <a href="events.php" class="a-action">Manage Events →</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Prayer Requests -->
  <div class="a-card">
    <div class="a-card-title">🙏 Recent Prayer Requests <?php if ($stats['prayers_unread'] > 0): ?><span class="anav-badge"><?= $stats['prayers_unread'] ?> new</span><?php endif; ?></div>
    <?php if (empty($recent_prayers)): ?>
      <p style="color:rgba(255,249,238,0.3);font-size:0.9rem;">No prayer requests yet.</p>
    <?php else: ?>
      <?php foreach ($recent_prayers as $p): ?>
      <div style="padding:10px 0;border-bottom:1px solid rgba(245,200,66,0.06);">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
          <span style="font-family:var(--font-serif);font-size:0.9rem;color:var(--cream);">
            <?= $p['is_anonymous'] ? '<em style="color:rgba(255,249,238,0.4);">Anonymous</em>' : htmlspecialchars($p['name'] ?? 'Unknown') ?>
          </span>
          <span class="badge badge-<?= $p['is_read'] ? 'read' : 'unread' ?>"><?= $p['is_read'] ? 'Read' : 'New' ?></span>
          <?php if ($p['category']): ?>
            <span style="font-family:var(--font-display);font-size:0.56rem;letter-spacing:0.08em;text-transform:uppercase;color:rgba(255,249,238,0.3);"><?= htmlspecialchars($p['category']) ?></span>
          <?php endif; ?>
        </div>
        <div style="font-size:0.88rem;color:rgba(255,249,238,0.5);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:320px;">
          <?= htmlspecialchars(substr($p['request'], 0, 100)) ?>…
        </div>
      </div>
      <?php endforeach; ?>
      <div style="margin-top:14px;">
        <a href="prayers.php" class="a-action">View All Requests →</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Quick Links -->
  <div class="a-card">
    <div class="a-card-title">⚡ Quick Actions</div>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <a href="upload-sermon.php" class="btn btn-gold btn-sm" style="text-align:center;">📤 Upload New Sermon</a>
      <a href="events.php?new=1" class="btn btn-blue btn-sm" style="text-align:center;">📅 Create New Event</a>
      <a href="gallery.php" class="btn btn-outline btn-sm" style="border-color:var(--gold-rich);color:var(--gold-rich);text-align:center;">🖼️ Upload to Gallery</a>
      <a href="settings.php" class="btn btn-outline btn-sm" style="border-color:rgba(255,255,255,0.2);color:rgba(255,249,238,0.5);text-align:center;">⚙️ Settings &amp; Password</a>
    </div>
    <div style="margin-top:24px;padding-top:16px;border-top:1px solid rgba(245,200,66,0.08);">
      <div class="a-card-title" style="margin-bottom:12px;">📈 Library Stats</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;text-align:center;">
        <div style="background:rgba(255,255,255,0.03);border-radius:6px;padding:14px;">
          <div style="font-family:var(--font-display);font-size:1.5rem;font-weight:700;color:var(--gold-bright);"><?= $stats['gallery_total'] ?></div>
          <div style="font-family:var(--font-display);font-size:0.55rem;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,249,238,0.3);">Gallery Photos</div>
        </div>
        <div style="background:rgba(255,255,255,0.03);border-radius:6px;padding:14px;">
          <div style="font-family:var(--font-display);font-size:1.5rem;font-weight:700;color:var(--gold-bright);"><?= $stats['registrations'] ?></div>
          <div style="font-family:var(--font-display);font-size:0.55rem;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,249,238,0.3);">Registrations</div>
        </div>
      </div>
    </div>
  </div>

</div>
</div>
<?php admin_footer(); ?>
