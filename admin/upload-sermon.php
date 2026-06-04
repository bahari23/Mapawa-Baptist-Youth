<?php
require_once __DIR__ . '/../includes/admin_layout.php';

$success = '';
$errors  = [];

// ── Handle POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title       = post('title');
    $speaker     = post('speaker');
    $series_num  = post('series_number');
    $series_name = post('series_name');
    $type        = in_array(post('sermon_type'), ['series','standalone']) ? post('sermon_type') : 'series';
    $scripture   = post('scripture_ref');
    $date        = post('preached_date') ?: null;
    $duration    = post('duration');
    $description = post('description');
    $key_points  = post('key_points');
    $youtube     = post('youtube_url');
    $status      = in_array(post('status'), ['draft','published','archived']) ? post('status') : 'draft';

    if (empty($title)) $errors[] = 'Sermon title is required.';

    if (empty($errors)) {
        $slug = unique_slug('sermons', $title);

        $data = [
            'slug'          => $slug,
            'series_number' => $series_num  ?: null,
            'title'         => $title,
            'speaker'       => $speaker     ?: null,
            'series_name'   => $series_name ?: null,
            'sermon_type'   => $type,
            'scripture_ref' => $scripture   ?: null,
            'preached_date' => $date,
            'duration'      => $duration    ?: null,
            'description'   => $description ?: null,
            'key_points'    => $key_points  ?: null,
            'youtube_url'   => $youtube     ?: null,
            'status'        => $status,
            'created_by'    => current_admin()['id'],
        ];

        // Handle audio
        if (!empty($_FILES['audio_file']['name'])) {
            $up = handle_upload('audio_file', 'audio', ALLOWED_AUDIO);
            if ($up['ok']) $data['audio_path'] = $up['path'];
            else $errors[] = 'Audio: ' . $up['error'];
        }

        // Handle video
        if (!empty($_FILES['video_file']['name'])) {
            $up = handle_upload('video_file', 'video', ALLOWED_VIDEO, 500);
            if ($up['ok']) $data['video_path'] = $up['path'];
            else $errors[] = 'Video: ' . $up['error'];
        }

        // Handle PDF
        if (!empty($_FILES['pdf_file']['name'])) {
            $up = handle_upload('pdf_file', 'pdf', ALLOWED_PDF, 20);
            if ($up['ok']) $data['pdf_path'] = $up['path'];
            else $errors[] = 'PDF: ' . $up['error'];
        }

        // Handle thumbnail
        if (!empty($_FILES['thumbnail']['name'])) {
            $up = handle_upload('thumbnail', 'thumbnails', ALLOWED_IMAGE, 5);
            if ($up['ok']) $data['thumbnail_path'] = $up['path'];
            else $errors[] = 'Thumbnail: ' . $up['error'];
        }

        if (empty($errors)) {
            $id = db_insert('sermons', $data);
            flash('success', 'Sermon "' . $title . '" saved successfully (ID: ' . $id . ').');
            redirect(SITE_URL . '/admin/sermons.php');
        }
    }
}

admin_head('Upload Sermon');
?>
<div class="admin-layout">
<?php admin_sidebar('upload'); ?>
<div class="admin-content">
<?php admin_topbar('Upload New Sermon'); ?>
<div class="admin-main">

<?php foreach ($errors as $e): ?>
  <div class="a-alert a-alert-error">⚠ <?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<form method="POST" enctype="multipart/form-data" id="sermon-form">
  <?= csrf_field() ?>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">

    <!-- Main form -->
    <div>
      <div class="a-card">
        <div class="a-card-title">📖 Sermon Details</div>

        <div class="a-form-group">
          <label class="a-label" for="title">Sermon Title *</label>
          <input type="text" name="title" id="title" class="a-input"
                 value="<?= htmlspecialchars(post('title')) ?>"
                 placeholder="e.g. Foundation of the Youth Ministry" required />
        </div>

        <div class="a-grid-2">
          <div class="a-form-group">
            <label class="a-label" for="speaker">Speaker</label>
            <input type="text" name="speaker" id="speaker" class="a-input"
                   value="<?= htmlspecialchars(post('speaker')) ?>"
                   placeholder="Youth Pastor" />
          </div>
          <div class="a-form-group">
            <label class="a-label" for="scripture_ref">Scripture Reference</label>
            <input type="text" name="scripture_ref" id="scripture_ref" class="a-input"
                   value="<?= htmlspecialchars(post('scripture_ref')) ?>"
                   placeholder="e.g. Acts 2:42-47" />
          </div>
          <div class="a-form-group">
            <label class="a-label" for="sermon_type">Type</label>
            <select name="sermon_type" id="sermon_type" class="a-input">
              <option value="series"     <?= post('sermon_type') !== 'standalone' ? 'selected' : '' ?>>Part of a Series</option>
              <option value="standalone" <?= post('sermon_type') === 'standalone' ? 'selected' : '' ?>>Standalone Message</option>
            </select>
          </div>
          <div class="a-form-group">
            <label class="a-label" for="series_number">Series Number</label>
            <input type="text" name="series_number" id="series_number" class="a-input"
                   value="<?= htmlspecialchars(post('series_number')) ?>"
                   placeholder="e.g. 001, 002…" />
          </div>
          <div class="a-form-group">
            <label class="a-label" for="series_name">Series Name</label>
            <input type="text" name="series_name" id="series_name" class="a-input"
                   value="<?= htmlspecialchars(post('series_name')) ?>"
                   placeholder="e.g. Ministry Foundations" />
          </div>
          <div class="a-form-group">
            <label class="a-label" for="preached_date">Date Preached</label>
            <input type="date" name="preached_date" id="preached_date" class="a-input"
                   value="<?= htmlspecialchars(post('preached_date')) ?>" />
          </div>
          <div class="a-form-group">
            <label class="a-label" for="duration">Duration</label>
            <input type="text" name="duration" id="duration" class="a-input"
                   value="<?= htmlspecialchars(post('duration')) ?>"
                   placeholder="e.g. 45 min" />
          </div>
        </div>

        <div class="a-form-group">
          <label class="a-label" for="description">Description</label>
          <textarea name="description" id="description" class="a-input" style="min-height:120px;"
                    placeholder="Brief overview of the message…"><?= htmlspecialchars(post('description')) ?></textarea>
        </div>

        <div class="a-form-group">
          <label class="a-label" for="key_points">Key Points <span style="opacity:0.5;">(one per line)</span></label>
          <textarea name="key_points" id="key_points" class="a-input" style="min-height:100px;"
                    placeholder="1. The Church is God's Idea&#10;2. Youth Ministry is Discipleship&#10;3. You are Here on Purpose"><?= htmlspecialchars(post('key_points')) ?></textarea>
        </div>

        <div class="a-form-group">
          <label class="a-label" for="youtube_url">YouTube / Video URL <span style="opacity:0.5;">(alternative to file upload)</span></label>
          <input type="url" name="youtube_url" id="youtube_url" class="a-input"
                 value="<?= htmlspecialchars(post('youtube_url')) ?>"
                 placeholder="https://www.youtube.com/watch?v=…" />
        </div>
      </div>

      <!-- Media uploads -->
      <div class="a-card">
        <div class="a-card-title">🎵 Media Files</div>
        <div class="a-grid-2" style="margin-bottom:16px;">
          <div>
            <label class="a-label">Audio File <span style="opacity:0.5;">(MP3 / WAV / M4A · max <?= MAX_UPLOAD_MB ?>MB)</span></label>
            <div class="upload-zone" id="audio-zone">
              <input type="file" name="audio_file" accept=".mp3,.wav,.ogg,.m4a,.aac"
                     onchange="markUploaded(this,'audio-zone','🎵')">
              <div class="upload-zone-icon" id="audio-icon">🎵</div>
              <div class="upload-zone-text" id="audio-text">Drop audio or click to browse</div>
            </div>
          </div>
          <div>
            <label class="a-label">Video File <span style="opacity:0.5;">(MP4 · max 500MB)</span></label>
            <div class="upload-zone" id="video-zone">
              <input type="file" name="video_file" accept=".mp4,.mov,.webm"
                     onchange="markUploaded(this,'video-zone','🎬')">
              <div class="upload-zone-icon" id="video-icon">🎬</div>
              <div class="upload-zone-text" id="video-text">Drop video or click to browse</div>
            </div>
          </div>
        </div>
        <div>
          <label class="a-label">Sermon Notes <span style="opacity:0.5;">(PDF · max 20MB)</span></label>
          <div class="upload-zone" id="pdf-zone" style="padding:20px;">
            <input type="file" name="pdf_file" accept=".pdf"
                   onchange="markUploaded(this,'pdf-zone','📄')">
            <div class="upload-zone-text" id="pdf-text">📄 Drop PDF or click to browse</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div style="position:sticky;top:20px;display:flex;flex-direction:column;gap:16px;">
      <div class="a-card">
        <div class="a-card-title">⚙️ Publish</div>
        <div class="a-form-group">
          <label class="a-label" for="status">Status</label>
          <select name="status" id="status" class="a-input">
            <option value="draft"     <?= post('status','draft') === 'draft'     ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= post('status','draft') === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="archived"  <?= post('status','draft') === 'archived'  ? 'selected' : '' ?>>Archived</option>
          </select>
        </div>
        <div class="a-form-group">
          <label class="a-label">Thumbnail Image <span style="opacity:0.5;">(optional)</span></label>
          <div class="upload-zone" id="thumb-zone" style="padding:20px;">
            <input type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.webp"
                   onchange="markUploaded(this,'thumb-zone','🖼️')">
            <div class="upload-zone-text" id="thumb-text">🖼️ Upload thumbnail</div>
          </div>
        </div>
        <button type="submit" name="status" value="published" class="btn btn-gold" style="width:100%;justify-content:center;margin-bottom:10px;">
          ✓ Save &amp; Publish
        </button>
        <button type="submit" name="status" value="draft" class="btn btn-outline" style="width:100%;justify-content:center;border-color:rgba(255,255,255,0.2);color:rgba(255,249,238,0.5);">
          Save as Draft
        </button>
      </div>

      <div class="a-card" style="background:rgba(21,101,192,0.1);">
        <div style="font-family:var(--font-display);font-size:0.62rem;letter-spacing:0.12em;text-transform:uppercase;color:#64b5f6;margin-bottom:8px;">ℹ️ Tip</div>
        <p style="font-size:0.88rem;color:rgba(255,249,238,0.55);line-height:1.6;">You can paste a YouTube URL instead of uploading a video file. Both work — the video player will automatically use the uploaded file if present, or fall back to YouTube.</p>
      </div>
    </div>

  </div>
</form>

</div>
<?php admin_footer(); ?>
<script>
function markUploaded(input, zoneId, icon) {
  if (!input.files[0]) return;
  const zone = document.getElementById(zoneId);
  zone.classList.add('has-file');
  const iconEl = document.getElementById(zoneId.replace('-zone','-icon'));
  const textEl = document.getElementById(zoneId.replace('-zone','-text'));
  if (iconEl) { iconEl.textContent = icon; iconEl.style.opacity = '1'; }
  if (textEl) { textEl.textContent = input.files[0].name; }
}
</script>
