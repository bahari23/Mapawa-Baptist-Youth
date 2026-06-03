<?php
require_once __DIR__ . '/../includes/admin_layout.php';

// ── Delete ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'delete') {
    csrf_check();
    $id = intpost('id');
    $s  = db_fetch('SELECT * FROM sermons WHERE id = ?', [$id]);
    if ($s) {
        // Remove files
        foreach (['audio_path','video_path','pdf_path','thumbnail_path'] as $col) {
            if (!empty($s[$col]) && file_exists(__DIR__ . '/../' . $s[$col])) {
                unlink(__DIR__ . '/../' . $s[$col]);
            }
        }
        db_delete('sermons', 'id = ?', [$id]);
        flash('success', 'Sermon deleted.');
    }
    redirect(SITE_URL . '/admin/sermons.php');
}

// ── Toggle status ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'toggle_status') {
    csrf_check();
    $id     = intpost('id');
    $status = post('new_status');
    if (in_array($status, ['draft','published','archived'])) {
        db_update('sermons', ['status' => $status], 'id = ?', [$id]);
        flash('success', 'Status updated.');
    }
    redirect(SITE_URL . '/admin/sermons.php');
}

// ── Edit (load) ──────────────────────────────────────────────────
$editing = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'save_edit') {
    csrf_check();
    $id     = intpost('id');
    $title  = post('title');
    $errors = [];
    if (empty($title)) $errors[] = 'Title required.';

    if (empty($errors)) {
        $data = [
            'title'         => $title,
            'speaker'       => post('speaker')       ?: null,
            'series_number' => post('series_number') ?: null,
            'series_name'   => post('series_name')   ?: null,
            'sermon_type'   => in_array(post('sermon_type'),['series','standalone']) ? post('sermon_type') : 'series',
            'scripture_ref' => post('scripture_ref') ?: null,
            'preached_date' => post('preached_date') ?: null,
            'duration'      => post('duration')      ?: null,
            'description'   => post('description')   ?: null,
            'key_points'    => post('key_points')    ?: null,
            'youtube_url'   => post('youtube_url')   ?: null,
            'status'        => in_array(post('status'),['draft','published','archived']) ? post('status') : 'draft',
        ];
        // New audio
        if (!empty($_FILES['audio_file']['name'])) {
            $up = handle_upload('audio_file','audio',ALLOWED_AUDIO);
            if ($up['ok']) { $data['audio_path'] = $up['path']; }
            else $errors[] = 'Audio: ' . $up['error'];
        }
        // New video
        if (!empty($_FILES['video_file']['name'])) {
            $up = handle_upload('video_file','video',ALLOWED_VIDEO,500);
            if ($up['ok']) { $data['video_path'] = $up['path']; }
            else $errors[] = 'Video: ' . $up['error'];
        }
        // New PDF
        if (!empty($_FILES['pdf_file']['name'])) {
            $up = handle_upload('pdf_file','pdf',ALLOWED_PDF,20);
            if ($up['ok']) { $data['pdf_path'] = $up['path']; }
            else $errors[] = 'PDF: ' . $up['error'];
        }
        if (empty($errors)) {
            db_update('sermons', $data, 'id = ?', [$id]);
            flash('success', 'Sermon updated.');
            redirect(SITE_URL . '/admin/sermons.php');
        }
    }
}

if (isset($_GET['edit'])) {
    $editing = db_fetch('SELECT * FROM sermons WHERE id = ?', [(int)$_GET['edit']]);
}

// ── Pagination & list ────────────────────────────────────────────
$search      = get('q');
$filter_type = get('type');
$filter_stat = get('status');
$page        = max(1, intget('page', 1));
$per_page    = 15;

$where  = '1';
$params = [];
if ($search) { $where .= ' AND (title LIKE ? OR speaker LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($filter_type && in_array($filter_type, ['series','standalone'])) { $where .= ' AND sermon_type = ?'; $params[] = $filter_type; }
if ($filter_stat && in_array($filter_stat, ['draft','published','archived'])) { $where .= ' AND status = ?'; $params[] = $filter_stat; }

$total   = db_count('sermons', $where, $params);
$pg      = paginate($total, $per_page, $page);
$sermons = db_fetch_all(
    "SELECT * FROM sermons WHERE $where ORDER BY created_at DESC LIMIT $per_page OFFSET {$pg['offset']}",
    $params
);

admin_head('Sermons');
?>
<div class="admin-layout">
<?php admin_sidebar('sermons'); ?>
<div class="admin-content">
<?php admin_topbar('Manage Sermons'); ?>
<div class="admin-main">

<?= render_flashes() ?>

<?php if ($editing): ?>
<!-- Edit Form -->
<div class="a-card" style="border-color:rgba(245,200,66,0.3);">
  <div class="a-card-title">✏️ Editing: <?= htmlspecialchars($editing['title']) ?></div>
  <form method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="_action" value="save_edit" />
    <input type="hidden" name="id" value="<?= $editing['id'] ?>" />
    <div class="a-grid-2">
      <div class="a-form-group"><label class="a-label">Title *</label>
        <input name="title" class="a-input" value="<?= htmlspecialchars($editing['title']) ?>" required /></div>
      <div class="a-form-group"><label class="a-label">Speaker</label>
        <input name="speaker" class="a-input" value="<?= htmlspecialchars($editing['speaker'] ?? '') ?>" /></div>
      <div class="a-form-group"><label class="a-label">Series Number</label>
        <input name="series_number" class="a-input" value="<?= htmlspecialchars($editing['series_number'] ?? '') ?>" /></div>
      <div class="a-form-group"><label class="a-label">Series Name</label>
        <input name="series_name" class="a-input" value="<?= htmlspecialchars($editing['series_name'] ?? '') ?>" /></div>
      <div class="a-form-group"><label class="a-label">Scripture</label>
        <input name="scripture_ref" class="a-input" value="<?= htmlspecialchars($editing['scripture_ref'] ?? '') ?>" /></div>
      <div class="a-form-group"><label class="a-label">Date Preached</label>
        <input type="date" name="preached_date" class="a-input" value="<?= htmlspecialchars($editing['preached_date'] ?? '') ?>" /></div>
      <div class="a-form-group"><label class="a-label">Type</label>
        <select name="sermon_type" class="a-input">
          <option value="series"     <?= $editing['sermon_type']==='series'     ? 'selected':'' ?>>Series</option>
          <option value="standalone" <?= $editing['sermon_type']==='standalone' ? 'selected':'' ?>>Standalone</option>
        </select></div>
      <div class="a-form-group"><label class="a-label">Status</label>
        <select name="status" class="a-input">
          <option value="draft"     <?= $editing['status']==='draft'     ? 'selected':'' ?>>Draft</option>
          <option value="published" <?= $editing['status']==='published' ? 'selected':'' ?>>Published</option>
          <option value="archived"  <?= $editing['status']==='archived'  ? 'selected':'' ?>>Archived</option>
        </select></div>
    </div>
    <div class="a-form-group"><label class="a-label">YouTube URL</label>
      <input name="youtube_url" class="a-input" value="<?= htmlspecialchars($editing['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/watch?v=…" /></div>
    <div class="a-form-group"><label class="a-label">Description</label>
      <textarea name="description" class="a-input"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea></div>
    <div class="a-form-group"><label class="a-label">Key Points (one per line)</label>
      <textarea name="key_points" class="a-input"><?= htmlspecialchars($editing['key_points'] ?? '') ?></textarea></div>

    <div class="a-grid-2" style="margin-bottom:16px;">
      <div><label class="a-label">Replace Audio</label>
        <div class="upload-zone" style="padding:16px;"><input type="file" name="audio_file" accept=".mp3,.wav,.m4a">
          <div class="upload-zone-text">🎵 <?= $editing['audio_path'] ? '✓ Has audio — upload to replace' : 'No audio yet — upload now' ?></div></div></div>
      <div><label class="a-label">Replace Video / PDF</label>
        <div class="upload-zone" style="padding:16px;"><input type="file" name="video_file" accept=".mp4,.mov,.webm">
          <div class="upload-zone-text">🎬 <?= $editing['video_path'] ? '✓ Has video — upload to replace' : 'No video yet' ?></div></div>
        <div class="upload-zone" style="padding:16px;margin-top:8px;"><input type="file" name="pdf_file" accept=".pdf">
          <div class="upload-zone-text">📄 <?= $editing['pdf_path'] ? '✓ Has PDF — upload to replace' : 'No PDF yet' ?></div></div></div>
    </div>

    <div style="display:flex;gap:12px;">
      <button type="submit" class="btn btn-gold">Save Changes</button>
      <a href="sermons.php" class="btn btn-outline" style="border-color:rgba(255,255,255,0.2);color:rgba(255,249,238,0.5);">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Filters -->
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;align-items:center;">
  <form method="GET" style="display:flex;gap:8px;flex:1;min-width:200px;">
    <input name="q" class="a-input" style="flex:1;padding:8px 12px;" placeholder="Search sermons…"
           value="<?= htmlspecialchars($search) ?>" />
    <input type="hidden" name="type"   value="<?= htmlspecialchars($filter_type) ?>" />
    <input type="hidden" name="status" value="<?= htmlspecialchars($filter_stat) ?>" />
    <button type="submit" class="btn btn-gold btn-sm">Search</button>
  </form>
  <div style="display:flex;gap:6px;flex-wrap:wrap;">
    <?php foreach ([''=>'All','series'=>'Series','standalone'=>'Standalone'] as $v => $l): ?>
      <a href="?type=<?= $v ?>&status=<?= htmlspecialchars($filter_stat) ?>&q=<?= htmlspecialchars($search) ?>"
         style="font-family:var(--font-display);font-size:0.6rem;letter-spacing:0.1em;text-transform:uppercase;padding:6px 12px;border-radius:2px;border:1px solid <?= $filter_type===$v ? 'var(--gold-rich)':'rgba(245,200,66,0.15)' ?>;background:<?= $filter_type===$v ? 'var(--gold-rich)':'transparent' ?>;color:<?= $filter_type===$v ? 'var(--blue-deep)':'rgba(255,249,238,0.5)' ?>;text-decoration:none;"><?= $l ?></a>
    <?php endforeach; ?>
    <?php foreach ([''=>'All Status','draft'=>'Draft','published'=>'Published','archived'=>'Archived'] as $v => $l): ?>
      <a href="?status=<?= $v ?>&type=<?= htmlspecialchars($filter_type) ?>&q=<?= htmlspecialchars($search) ?>"
         style="font-family:var(--font-display);font-size:0.6rem;letter-spacing:0.1em;text-transform:uppercase;padding:6px 12px;border-radius:2px;border:1px solid <?= $filter_stat===$v ? 'var(--gold-rich)':'rgba(245,200,66,0.15)' ?>;background:<?= $filter_stat===$v ? 'var(--gold-rich)':'transparent' ?>;color:<?= $filter_stat===$v ? 'var(--blue-deep)':'rgba(255,249,238,0.5)' ?>;text-decoration:none;"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <a href="upload-sermon.php" class="btn btn-gold btn-sm">+ New Sermon</a>
</div>

<!-- Table -->
<div class="a-card" style="padding:0;overflow:hidden;">
  <table class="a-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Title</th>
        <th>Speaker</th>
        <th>Scripture</th>
        <th>Type</th>
        <th>Status</th>
        <th>Media</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($sermons)): ?>
      <tr><td colspan="8" style="text-align:center;padding:40px;color:rgba(255,249,238,0.3);">No sermons found.</td></tr>
    <?php else: foreach ($sermons as $s): ?>
      <tr>
        <td style="color:rgba(255,249,238,0.3);font-family:var(--font-display);font-size:0.7rem;"><?= $s['series_number'] ? '#'.$s['series_number'] : '—' ?></td>
        <td class="td-title"><?= htmlspecialchars($s['title']) ?>
          <?php if ($s['series_name']): ?><br><span style="font-family:var(--font-display);font-size:0.58rem;letter-spacing:0.08em;color:rgba(255,249,238,0.3);"><?= htmlspecialchars($s['series_name']) ?></span><?php endif; ?>
        </td>
        <td><?= htmlspecialchars($s['speaker'] ?? '—') ?></td>
        <td style="font-size:0.85rem;"><?= htmlspecialchars($s['scripture_ref'] ?? '—') ?></td>
        <td><span class="badge badge-draft" style="background:transparent;"><?= $s['sermon_type'] ?></span></td>
        <td>
          <form method="POST" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="_action"    value="toggle_status" />
            <input type="hidden" name="id"         value="<?= $s['id'] ?>" />
            <input type="hidden" name="new_status" value="<?= $s['status']==='published' ? 'draft' : 'published' ?>" />
            <button type="submit" class="badge badge-<?= $s['status'] ?>" style="cursor:pointer;border:none;">
              <?= $s['status'] ?>
            </button>
          </form>
        </td>
        <td style="font-size:0.85rem;">
          <?= $s['audio_path'] ? '<span title="Audio">🎵</span>' : '' ?>
          <?= ($s['video_path'] || $s['youtube_url']) ? '<span title="Video">🎬</span>' : '' ?>
          <?= $s['pdf_path']   ? '<span title="PDF">📄</span>'   : '' ?>
          <?= (!$s['audio_path'] && !$s['video_path'] && !$s['youtube_url'] && !$s['pdf_path']) ? '<span style="color:rgba(255,249,238,0.2);">None</span>' : '' ?>
        </td>
        <td>
          <a href="sermons.php?edit=<?= $s['id'] ?>" class="a-action">Edit</a>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this sermon? This cannot be undone.')">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="delete" />
            <input type="hidden" name="id"      value="<?= $s['id'] ?>" />
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
  <?php if ($pg['has_prev']): ?>
    <a href="?page=<?= $pg['current']-1 ?>&q=<?= htmlspecialchars($search) ?>&type=<?= htmlspecialchars($filter_type) ?>&status=<?= htmlspecialchars($filter_stat) ?>">← Prev</a>
  <?php endif; ?>
  <?php for ($i = 1; $i <= $pg['total_pages']; $i++): ?>
    <a href="?page=<?= $i ?>&q=<?= htmlspecialchars($search) ?>&type=<?= htmlspecialchars($filter_type) ?>&status=<?= htmlspecialchars($filter_stat) ?>"
       class="<?= $i === $pg['current'] ? 'current' : '' ?>"><?= $i ?></a>
  <?php endfor; ?>
  <?php if ($pg['has_next']): ?>
    <a href="?page=<?= $pg['current']+1 ?>&q=<?= htmlspecialchars($search) ?>&type=<?= htmlspecialchars($filter_type) ?>&status=<?= htmlspecialchars($filter_stat) ?>">Next →</a>
  <?php endif; ?>
</div>
<?php endif; ?>

</div>
<?php admin_footer(); ?>
