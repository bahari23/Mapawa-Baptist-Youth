<?php
require_once __DIR__ . '/../includes/admin_layout.php';

// ── Delete ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'delete') {
    csrf_check();
    $item = db_fetch('SELECT * FROM gallery_items WHERE id = ?', [intpost('id')]);
    if ($item) {
        $path = __DIR__ . '/../' . $item['filename'];
        if (file_exists($path)) unlink($path);
        db_delete('gallery_items', 'id = ?', [$item['id']]);
        flash('success', 'Photo deleted.');
    }
    redirect(SITE_URL . '/admin/gallery.php');
}

// ── Toggle visibility ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'toggle_visible') {
    csrf_check();
    $item = db_fetch('SELECT is_visible FROM gallery_items WHERE id = ?', [intpost('id')]);
    if ($item) {
        db_update('gallery_items', ['is_visible' => $item['is_visible'] ? 0 : 1], 'id = ?', [intpost('id')]);
    }
    redirect(SITE_URL . '/admin/gallery.php');
}

// ── Upload ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'upload') {
    csrf_check();
    $category = in_array(post('category'),['worship','outreach','retreat','fellowship','other']) ? post('category') : 'other';
    $caption  = post('caption') ?: null;
    $errors   = [];

    if (empty($_FILES['photos']['name'][0])) {
        $errors[] = 'Please select at least one photo.';
    } else {
        $files = $_FILES['photos'];
        $count = count($files['name']);
        $saved = 0;
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            // Restructure to single-file format for handle_upload
            $_FILES['_photo_tmp'] = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $up = handle_upload('_photo_tmp', 'gallery', ALLOWED_IMAGE, 10);
            if ($up['ok']) {
                db_insert('gallery_items', [
                    'filename'    => $up['path'],
                    'caption'     => $caption,
                    'category'    => $category,
                    'is_visible'  => 1,
                    'uploaded_by' => current_admin()['id'],
                ]);
                $saved++;
            } else {
                $errors[] = $files['name'][$i] . ': ' . $up['error'];
            }
        }
        if ($saved > 0) flash('success', "$saved photo(s) uploaded successfully.");
    }
    foreach ($errors as $e) flash('error', $e);
    redirect(SITE_URL . '/admin/gallery.php');
}

// ── Update caption ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'update_caption') {
    csrf_check();
    db_update('gallery_items', [
        'caption'  => post('caption')  ?: null,
        'category' => in_array(post('category'),['worship','outreach','retreat','fellowship','other']) ? post('category') : 'other',
    ], 'id = ?', [intpost('id')]);
    flash('success', 'Updated.');
    redirect(SITE_URL . '/admin/gallery.php');
}

// ── List ─────────────────────────────────────────────────────────
$filter   = get('cat', 'all');
$where    = '1';
if ($filter !== 'all' && in_array($filter,['worship','outreach','retreat','fellowship','other'])) {
    $where = "category = '$filter'";
}
$items = db_fetch_all("SELECT * FROM gallery_items WHERE $where ORDER BY created_at DESC");
$total = count($items);

admin_head('Gallery');
?>
<div class="admin-layout">
<?php admin_sidebar('gallery'); ?>
<div class="admin-content">
<?php admin_topbar('Gallery Manager'); ?>
<div class="admin-main">

<?= render_flashes() ?>

<!-- Upload Card -->
<div class="a-card" style="margin-bottom:24px;">
  <div class="a-card-title">📤 Upload Photos</div>
  <form method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="_action" value="upload">
    <div class="a-grid-2" style="margin-bottom:16px;">
      <div class="a-form-group">
        <label class="a-label">Category</label>
        <select name="category" class="a-input">
          <?php foreach (['worship','outreach','retreat','fellowship','other'] as $c): ?>
            <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="a-form-group">
        <label class="a-label">Caption <span style="opacity:.5;">(applies to all uploaded photos)</span></label>
        <input name="caption" class="a-input" placeholder="e.g. Youth Retreat 2025" />
      </div>
    </div>
    <div class="upload-zone" id="gallery-zone" style="padding:44px;">
      <input type="file" name="photos[]" accept=".jpg,.jpeg,.png,.webp,.gif" multiple
             onchange="previewGallery(this)">
      <div class="upload-zone-icon">🖼️</div>
      <div class="upload-zone-text">Drop images here or click to browse · Multiple files OK · JPG PNG WEBP</div>
    </div>
    <div id="gallery-preview" style="display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin-top:12px;"></div>
    <button type="submit" class="btn btn-gold" style="margin-top:16px;">Upload Photos</button>
  </form>
</div>

<!-- Filter tabs -->
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;align-items:center;">
  <?php foreach (['all'=>'All ('.$total.')','worship'=>'Worship','outreach'=>'Outreach','retreat'=>'Retreat','fellowship'=>'Fellowship','other'=>'Other'] as $k=>$l): ?>
    <a href="gallery.php?cat=<?= $k ?>"
       style="font-family:var(--font-display);font-size:0.6rem;letter-spacing:0.1em;text-transform:uppercase;padding:6px 12px;border-radius:2px;border:1px solid <?= $filter===$k ? 'var(--gold-rich)':'rgba(245,200,66,0.15)' ?>;background:<?= $filter===$k ? 'var(--gold-rich)':'transparent' ?>;color:<?= $filter===$k ? 'var(--blue-deep)':'rgba(255,249,238,0.5)' ?>;text-decoration:none;"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<!-- Gallery grid -->
<?php if (empty($items)): ?>
  <div style="text-align:center;padding:60px;color:rgba(255,249,238,0.3);">
    <div style="font-size:3rem;margin-bottom:12px;opacity:0.3;">🖼️</div>
    <div style="font-family:var(--font-display);font-size:0.65rem;letter-spacing:0.14em;text-transform:uppercase;">No photos yet. Upload some above.</div>
  </div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
  <?php foreach ($items as $item): ?>
  <div style="border-radius:8px;overflow:hidden;background:rgba(255,255,255,0.04);border:1px solid rgba(245,200,66,0.1);">
    <div style="aspect-ratio:4/3;overflow:hidden;position:relative;background:#0a1525;">
      <img src="<?= SITE_URL . '/' . htmlspecialchars($item['filename']) ?>" alt=""
           style="width:100%;height:100%;object-fit:cover;opacity:<?= $item['is_visible'] ? '1':'0.3' ?>;"
           onerror="this.style.display='none'" />
      <?php if (!$item['is_visible']): ?>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:0.6rem;letter-spacing:0.1em;text-transform:uppercase;color:#f88;">Hidden</div>
      <?php endif; ?>
    </div>
    <div style="padding:10px;">
      <form method="POST" style="margin-bottom:8px;">
        <?= csrf_field() ?>
        <input type="hidden" name="_action" value="update_caption">
        <input type="hidden" name="id" value="<?= $item['id'] ?>">
        <input name="caption" class="a-input" style="padding:6px 10px;font-size:0.82rem;margin-bottom:6px;"
               value="<?= htmlspecialchars($item['caption'] ?? '') ?>" placeholder="Caption…" />
        <select name="category" class="a-input" style="padding:5px 10px;font-size:0.75rem;margin-bottom:8px;">
          <?php foreach (['worship','outreach','retreat','fellowship','other'] as $c): ?>
            <option value="<?= $c ?>" <?= $item['category']===$c ? 'selected':'' ?>><?= ucfirst($c) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="a-action" style="font-size:0.58rem;">Save</button>
      </form>
      <div style="display:flex;gap:6px;">
        <form method="POST" style="flex:1;">
          <?= csrf_field() ?>
          <input type="hidden" name="_action" value="toggle_visible">
          <input type="hidden" name="id" value="<?= $item['id'] ?>">
          <button type="submit" class="a-action" style="font-size:0.58rem;width:100%;"><?= $item['is_visible'] ? '👁 Hide' : '👁 Show' ?></button>
        </form>
        <form method="POST" onsubmit="return confirm('Delete this photo?')">
          <?= csrf_field() ?>
          <input type="hidden" name="_action" value="delete">
          <input type="hidden" name="id" value="<?= $item['id'] ?>">
          <button type="submit" class="a-action a-action-danger" style="font-size:0.58rem;">🗑</button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

</div>
<?php admin_footer(); ?>
<script>
function previewGallery(input) {
  const preview = document.getElementById('gallery-preview');
  preview.innerHTML = '';
  Array.from(input.files).forEach(file => {
    const url = URL.createObjectURL(file);
    const div = document.createElement('div');
    div.style.cssText = 'aspect-ratio:1;border-radius:4px;overflow:hidden;';
    const img = document.createElement('img');
    img.src = url; img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
    div.appendChild(img);
    preview.appendChild(div);
  });
  document.getElementById('gallery-zone').classList.add('has-file');
}
</script>
