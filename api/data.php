<?php
// api/data.php — Read-only JSON API for the frontend
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // 5 min cache

$resource = get('resource', '');

switch ($resource) {

    case 'sermons':
        $type   = get('type');
        $status = get('status', 'published');
        $where  = 'status = ?';
        $params = [$status];
        if ($type && in_array($type, ['series','standalone'])) {
            $where .= ' AND sermon_type = ?';
            $params[] = $type;
        }
        $sermons = db_fetch_all("SELECT id, slug, series_number, title, speaker, series_name, sermon_type, scripture_ref, preached_date, duration, description, audio_path, video_path, youtube_url, pdf_path, thumbnail_path, status, view_count FROM sermons WHERE $where ORDER BY series_number ASC, created_at DESC", $params);
        json_ok(['sermons' => $sermons, 'total' => count($sermons)]);

    case 'sermon':
        $slug = get('slug');
        $id   = intget('id');
        $s = $id
            ? db_fetch('SELECT * FROM sermons WHERE id = ? AND status = ?', [$id, 'published'])
            : db_fetch('SELECT * FROM sermons WHERE slug = ? AND status = ?', [$slug, 'published']);
        if (!$s) json_err('Sermon not found.', 404);
        // Increment view count
        db_query('UPDATE sermons SET view_count = view_count + 1 WHERE id = ?', [$s['id']]);
        json_ok(['sermon' => $s]);

    case 'events':
        $status = get('status', 'upcoming');
        $where  = $status === 'all' ? '1' : 'status = ?';
        $params = $status === 'all' ? [] : [$status];
        $events = db_fetch_all("SELECT id, title, slug, category, event_date, event_time, end_date, location, open_to, theme, requires_reg, description, image_path, status FROM events WHERE $where ORDER BY event_date ASC", $params);
        json_ok(['events' => $events, 'total' => count($events)]);

    case 'gallery':
        $cat    = get('category');
        $where  = 'is_visible = 1';
        $params = [];
        if ($cat && in_array($cat, ['worship','outreach','retreat','fellowship','other'])) {
            $where .= ' AND category = ?';
            $params[] = $cat;
        }
        $items = db_fetch_all("SELECT id, filename, caption, category FROM gallery_items WHERE $where ORDER BY sort_order ASC, created_at DESC", $params);
        // Add full URL
        foreach ($items as &$item) {
            $item['url'] = SITE_URL . '/' . $item['filename'];
        }
        json_ok(['items' => $items, 'total' => count($items)]);

    default:
        json_err('Unknown resource. Use ?resource=sermons|sermon|events|gallery', 400);
}
