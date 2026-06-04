<?php
require_once __DIR__ . '/../includes/admin_layout.php';

// ── Delete ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'delete') {
    csrf_check();
    $id = intpost('id');
    $e  = db_fetch('SELECT * FROM events WHERE id = ?', [$id]);
    if ($e) {
        if (!empty($e['image_path']) && file_exists(__DIR__ . '/../' . $e['image_path'])) {
            unlink(__DIR__ . '/../' . $e['image_path']);
        }
        db_delete('events', 'id = ?', [$id]);
        flash('success', 'Event deleted.');
    }
    redirect(SITE_URL . '/admin/events.php');
}

// ── Create / Update ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(post('_action'), ['create','update'])) {
    csrf_check();
    $action = post('_action');
    $errors = [];

    $title       = post('title');
    $category    = in_array(post('category'),['retreat','outreach','worship','training','general']) ? post('category') : 'general';
    $event_date  = post('event_date');
    $event_time  = post('event_time') ?: null;
    $end_date    = post('end_date')   ?: null;
    $location    = post('location')   ?: null;
    $open_to     = post('open_to')    ?: null;
    $theme       = post('theme')      ?: null;
    $requires_reg= intpost('requires_reg', 0);
    $description = post('description')?: null;
    $status      = in_array(post('status'),['upcoming','ongoing','past','cancelled']) ? post('status') : 'upcoming';

    if (empty($title))      $errors[] = 'Event title is required.';
    if (empty($event_date)) $errors[] = 'Event date is required.';

    $data = compact('title','category','event_date','event_time','end_date','location',
                    'open_to','theme','requires_reg','description','status');

    if ($action === 'create') {
        $data['slug'] = unique_slug('events', $title);
        $data['created_by'] = current_admin()['id'];
    }

    // Image upload
    if (!empty($_FILES['image_file']['name'])) {
        $up = handle_upload('image_file', 'events', ALLOWED_IMAGE, 10);
        if ($up['ok']) $data['image_path'] = $up['path'];
        else $errors[] = 'Image: ' . $up['error'];
    }

    if (empty($errors)) {
        if ($action === 'create') {
            db_insert('events', $data);
            flash('success', 'Event created.');
        } else {
            db_update('events', $data, 'id = ?', [intpost('id')]);
            flash('success', 'Event updated.');
        }
        redirect(SITE_URL . '/admin/events.php');
    }
}

// ── Load for edit ────────────────────────────────────────────────
$editing  = null;
$show_new = isset($_GET['new']);
if (isset($_GET['edit'])) {
    $editing   = db_fetch('SELECT * FROM events WHERE id = ?', [(int)$_GET['edit']]);
    $show_new  = false;
}

// ── List ─────────────────────────────────────────────────────────
$events = db_fetch_all('SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id=e.id) AS reg_count FROM events e ORDER BY e.event_date DESC');

admin_head('Events');
?>
<div class="admin-layout">
<?php admin_sidebar('events'); ?>
<div class="admin-content">
<?php admin_topbar('Manage Events'); ?>
<div class="admin-main">

<?= render_flashes() ?>

<?php
$form_data = $editing ?? [];
$form_action = $editing ? 'update' : 'create';
if ($show_new || $editing):
?>
<!-- Event Form -->
<div class="a-card" style="border-color:rgba(245,200,66,0.3);margin-bottom:28px;">
  <div class="a-card-title"><?= $editing ? '✏️ Edit: '.htmlspecialchars($editing['title']) : '➕ New Event' ?></div>
  <form method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="_action" value="<?= $form_action ?>">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

    <div class="a-grid-2">
      <div class="a-form-group">
        <label class="a-label">Event Title *</label>
        <input name="title" class="a-input" value="<?= htmlspecialchars($form_data['title'] ?? '') ?>" required placeholder="e.g. Youth Retreat 2025" />
      </div>
      <div class="a-form-group">
        <label class="a-label">Category</label>
        <select name="category" class="a-input">
          <?php foreach (['retreat','outreach','worship','training','general'] as $c): ?>
            <option value="<?= $c ?>" <?= ($form_data['category'] ?? '') === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="a-form-group">
        <label class="a-label">Event Date *</label>
        <input type="date" name="event_date" class="a-input" value="<?= htmlspecialchars($form_data['event_date'] ?? '') ?>" required />
      </div>
      <div class="a-form-group">
        <label class="a-label">Time</label>
        <input type="time" name="event_time" class="a-input" value="<?= htmlspecialchars($form_data['event_time'] ?? '') ?>" />
      </div>
      <div class="a-form-group">
        <label class="a-label">End Date <span style="opacity:.5;">(for multi-day)</span></label>
        <input type="date" name="end_date" class="a-input" value="<?= htmlspecialchars($form_data['end_date'] ?? '') ?>" />
      </div>
      <div class="a-form-group">
        <label class="a-label">Location</label>
        <input name="location" class="a-input" value="<?= htmlspecialchars($form_data['location'] ?? '') ?>" placeholder="e.g. Mapawa Nature Park" />
      </div>
      <div class="a-form-group">
        <label class="a-label">Open To</label>
        <input name="open_to" class="a-input" value="<?= htmlspecialchars($form_data['open_to'] ?? '') ?>" placeholder="e.g. All Registered Youth (13–30)" />
      </div>
      <div class="a-form-group">
        <label class="a-label">Theme</label>
        <input name="theme" class="a-input" value="<?= htmlspecialchars($form_data['theme'] ?? '') ?>" placeholder="e.g. Surrender &amp; Sent" />
      </div>
      <div class="a-form-group">
        <label class="a-label">Status</label>
        <select name="status" class="a-input">
          <?php foreach (['upcoming','ongoing','past','cancelled'] as $st): ?>
            <option value="<?= $st ?>" <?= ($form_data['status'] ?? 'upcoming') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="a-form-group">
        <label class="a-label">Requires Registration?</label>
        <select name="requires_reg" class="a-input">
          <option value="0" <?= empty($form_data['requires_reg']) ? 'selected' : '' ?>>No — Walk-in welcome</option>
          <option value="1" <?= !empty($form_data['requires_reg']) ? 'selected' : '' ?>>Yes — Registration required</option>
        </select>
      </div>
    </div>

    <div class="a-form-group">
      <label class="a-label">Description</label>
      <textarea name="description" class="a-input" style="min-height:120px;" placeholder="Describe the event…"><?= htmlspecialchars($form_data['description'] ?? '') ?></textarea>
    </div>

    <div class="a-form-group">
      <label class="a-label">Event Image <span style="opacity:.5;">(JPG/PNG/WEBP · max 10MB)</span></label>
      <div class="upload-zone" style="padding:20px;">
        <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" onchange="this.closest('.upload-zone').querySelector('.upload-zone-text').textContent = this.files[0].name">
        <div class="upload-zone-text">🖼️ <?= !empty($form_data['image_path']) ? '✓ Has image — upload to replace' : 'Drop image or click to browse' ?></div>
      </div>
    </div>

    <div style="display:flex;gap:12px;">
      <button type="submit" class="btn btn-gold"><?= $editing ? 'Save Changes' : 'Create Event' ?></button>
      <a href="events.php" class="btn btn-outline" style="border-color:rgba(255,255,255,0.2);color:rgba(255,249,238,0.5);">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Header row -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
  <div style="font-family:var(--font-display);font-size:0.65rem;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,249,238,0.4);"><?= count($events) ?> event(s) total</div>
  <?php if (!$show_new && !$editing): ?>
    <a href="events.php?new=1" class="btn btn-gold btn-sm">+ New Event</a>
  <?php endif; ?>
</div>

<!-- Table -->
<div class="a-card" style="padding:0;overflow:hidden;">
  <table class="a-table">
    <thead>
      <tr>
        <th>Date</th><th>Event</th><th>Category</th><th>Location</th><th>Status</th><th>Reg.</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($events)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:rgba(255,249,238,0.3);">No events yet.</td></tr>
    <?php else: foreach ($events as $e): ?>
      <tr>
        <td style="white-space:nowrap;">
          <strong style="font-family:var(--font-display);font-size:0.9rem;color:var(--gold-bright);"><?= date('d M', strtotime($e['event_date'])) ?></strong><br>
          <span style="font-family:var(--font-display);font-size:0.58rem;color:rgba(255,249,238,0.3);"><?= date('Y', strtotime($e['event_date'])) ?></span>
        </td>
        <td class="td-title"><?= htmlspecialchars($e['title']) ?>
          <?php if ($e['theme']): ?><br><span style="font-family:var(--font-display);font-size:0.58rem;color:rgba(255,249,238,0.3);"><?= htmlspecialchars($e['theme']) ?></span><?php endif; ?>
        </td>
        <td><span class="badge badge-draft"><?= $e['category'] ?></span></td>
        <td style="font-size:0.85rem;"><?= htmlspecialchars($e['location'] ?? '—') ?></td>
        <td><span class="badge badge-<?= $e['status'] === 'upcoming' ? 'upcoming' : 'past' ?>"><?= $e['status'] ?></span></td>
        <td>
          <a href="registrations.php?event_id=<?= $e['id'] ?>" class="a-action">
            <?= $e['reg_count'] ?> <?= $e['requires_reg'] ? '🔒' : '✓' ?>
          </a>
        </td>
        <td>
          <a href="events.php?edit=<?= $e['id'] ?>" class="a-action">Edit</a>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this event?')">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="delete">
            <input type="hidden" name="id" value="<?= $e['id'] ?>">
            <button type="submit" class="a-action a-action-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

</div>
<?php admin_footer(); ?>
