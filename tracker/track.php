<?php
// ============================================
// track.php — Receives all tracking data
// Upload to your server alongside config.php
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://summit.futurecrime.org');          // restrict to your domain in production
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tracker-Key');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); exit; }

require_once __DIR__ . '/config.php';

// Validate secret key
$key = $_SERVER['HTTP_X_TRACKER_KEY'] ?? '';
if ($key !== TRACKER_SECRET) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$raw    = file_get_contents('php://input');
$data   = json_decode($raw, true);
$action = $data['action'] ?? '';

if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing action']);
    exit;
}

try {
    $db = getDB();

    switch ($action) {

        // ── 1. Session start ───────────────────────────────────────
        case 'session_start':
            $sessionId  = sanitize($data['session_id'] ?? '');
            $visitorId  = sanitize($data['visitor_id'] ?? '');
            if (!$sessionId || !$visitorId) break;

            $ip   = getClientIP();
            $geo  = getGeoData($ip);
            $ref  = parseReferrer($data['referrer'] ?? '');

            // Check if returning visitor
            $stmt = $db->prepare("SELECT COUNT(*) FROM sessions WHERE visitor_id = ? AND session_id != ?");
            $stmt->execute([$visitorId, $sessionId]);
            $isReturning = (int)$stmt->fetchColumn() > 0 ? 1 : 0;

            $stmt = $db->prepare("
                INSERT IGNORE INTO sessions
                    (session_id, visitor_id, is_returning,
                     device_type, browser, browser_version, os,
                     screen_width, screen_height, language, timezone,
                     ip_address, country_code, country_name, region, city, latitude, longitude,
                     referrer_raw, referrer_source, referrer_medium,
                     utm_source, utm_medium, utm_campaign, landing_page)
                VALUES
                    (:sid, :vid, :ret,
                     :dev, :br, :brv, :os,
                     :sw, :sh, :lang, :tz,
                     :ip, :cc, :cn, :reg, :city, :lat, :lon,
                     :rraw, :rsrc, :rmed,
                     :us, :um, :uc, :lp)
            ");
            $stmt->execute([
                ':sid'  => $sessionId,
                ':vid'  => $visitorId,
                ':ret'  => $isReturning,
                ':dev'  => sanitize($data['device_type'] ?? 'unknown'),
                ':br'   => sanitize($data['browser'] ?? ''),
                ':brv'  => sanitize($data['browser_version'] ?? ''),
                ':os'   => sanitize($data['os'] ?? ''),
                ':sw'   => (int)($data['screen_width'] ?? 0),
                ':sh'   => (int)($data['screen_height'] ?? 0),
                ':lang' => sanitize($data['language'] ?? ''),
                ':tz'   => sanitize($data['timezone'] ?? ''),
                ':ip'   => $ip,
                ':cc'   => $geo['country_code'] ?? '',
                ':cn'   => $geo['country_name'] ?? '',
                ':reg'  => $geo['region'] ?? '',
                ':city' => $geo['city'] ?? '',
                ':lat'  => $geo['latitude'] ?? null,
                ':lon'  => $geo['longitude'] ?? null,
                ':rraw' => substr($data['referrer'] ?? '', 0, 2000),
                ':rsrc' => $ref['source'],
                ':rmed' => $ref['medium'],
                ':us'   => sanitize($data['utm_source'] ?? ''),
                ':um'   => sanitize($data['utm_medium'] ?? ''),
                ':uc'   => sanitize($data['utm_campaign'] ?? ''),
                ':lp'   => substr($data['landing_page'] ?? '', 0, 2000),
            ]);

            echo json_encode(['ok' => true, 'returning' => (bool)$isReturning]);
            break;


        // ── 2. Pageview ────────────────────────────────────────────
        case 'pageview':
            $sessionId = sanitize($data['session_id'] ?? '');
            $visitorId = sanitize($data['visitor_id'] ?? '');
            $url       = substr($data['url'] ?? '', 0, 2000);
            $title     = sanitize(substr($data['title'] ?? '', 0, 255));
            if (!$sessionId || !$url) break;

            ensureSession($db, $sessionId, $visitorId);

            $stmt = $db->prepare("
                INSERT INTO pageviews (session_id, visitor_id, page_url, page_title)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$sessionId, $visitorId, $url, $title]);

            // Increment page count on session
            $db->prepare("UPDATE sessions SET page_count = page_count + 1 WHERE session_id = ?")
               ->execute([$sessionId]);

            // Infer interests
            $interests = inferInterests($url, $title);
            foreach ($interests as $tag) {
                $db->prepare("
                    INSERT INTO interests (visitor_id, interest_tag, score)
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE score = score + 1, updated_at = NOW()
                ")->execute([$visitorId, $tag]);
            }

            echo json_encode(['ok' => true]);
            break;


        // ── 3. Page leave (update time on page) ───────────────────
        case 'page_leave':
            $sessionId  = sanitize($data['session_id'] ?? '');
            $url        = substr($data['url'] ?? '', 0, 2000);
            $timeOnPage = (int)($data['time_on_page'] ?? 0);
            $scrollDepth= min(100, (int)($data['scroll_depth'] ?? 0));
            if (!$sessionId || !$url) break;

            $db->prepare("
                UPDATE pageviews
                SET time_on_page = ?, scroll_depth = ?, left_at = NOW()
                WHERE session_id = ? AND page_url = ? AND left_at IS NULL
                ORDER BY visited_at DESC LIMIT 1
            ")->execute([$timeOnPage, $scrollDepth, $sessionId, $url]);

            $db->prepare("
                UPDATE sessions
                SET duration_seconds = duration_seconds + ?, last_seen_at = NOW()
                WHERE session_id = ?
            ")->execute([$timeOnPage, $sessionId]);

            echo json_encode(['ok' => true]);
            break;


        // ── 4. Click event ─────────────────────────────────────────
        case 'click':
            $sessionId = sanitize($data['session_id'] ?? '');
            $visitorId = sanitize($data['visitor_id'] ?? '');
            if (!$sessionId) break;

            ensureSession($db, $sessionId, $visitorId);

            $extra = [];
            if (!empty($data['href']))  $extra['href']  = substr($data['href'], 0, 500);
            if (!empty($data['value'])) $extra['value'] = substr($data['value'], 0, 200);

            $stmt = $db->prepare("
                INSERT INTO events
                    (session_id, visitor_id, event_type, page_url,
                     element_tag, element_text, element_id, element_class, x_pos, y_pos, extra_data)
                VALUES (?, ?, 'click', ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $sessionId,
                $visitorId,
                substr($data['url'] ?? '', 0, 2000),
                sanitize($data['tag'] ?? ''),
                sanitize(substr($data['text'] ?? '', 0, 255)),
                sanitize(substr($data['id'] ?? '', 0, 120)),
                sanitize(substr($data['class'] ?? '', 0, 255)),
                (int)($data['x'] ?? 0),
                (int)($data['y'] ?? 0),
                json_encode($extra) ?: null,
            ]);

            echo json_encode(['ok' => true]);
            break;


        // ── 5. Search keyword ──────────────────────────────────────
        case 'search':
            $sessionId = sanitize($data['session_id'] ?? '');
            $visitorId = sanitize($data['visitor_id'] ?? '');
            $keyword   = sanitize(substr($data['keyword'] ?? '', 0, 255));
            if (!$sessionId || !$keyword) break;

            $db->prepare("
                INSERT INTO searches (session_id, visitor_id, keyword, page_url, results_count)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $sessionId, $visitorId, $keyword,
                substr($data['url'] ?? '', 0, 2000),
                (int)($data['results'] ?? 0),
            ]);

            echo json_encode(['ok' => true]);
            break;


        // ── 6. Heartbeat (real-time active users) ─────────────────
        case 'heartbeat':
            $sessionId = sanitize($data['session_id'] ?? '');
            $url       = substr($data['url'] ?? '', 0, 2000);
            if (!$sessionId) break;

            $db->prepare("
                INSERT INTO heartbeats (session_id, page_url, last_ping)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE page_url = VALUES(page_url), last_ping = NOW()
            ")->execute([$sessionId, $url]);

            // Clean old heartbeats
            $db->prepare("DELETE FROM heartbeats WHERE last_ping < DATE_SUB(NOW(), INTERVAL ? SECOND)")
               ->execute([REALTIME_TIMEOUT]);

            // Return current active count
            $count = $db->query("SELECT COUNT(*) FROM heartbeats")->fetchColumn();
            echo json_encode(['ok' => true, 'active' => (int)$count]);
            break;


        // ── 7. Custom event ────────────────────────────────────────
        case 'event':
            $sessionId = sanitize($data['session_id'] ?? '');
            $visitorId = sanitize($data['visitor_id'] ?? '');
            $eventType = sanitize($data['event_name'] ?? 'custom');
            if (!$sessionId) break;

            $db->prepare("
                INSERT INTO events (session_id, visitor_id, event_type, page_url, extra_data)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $sessionId, $visitorId, $eventType,
                substr($data['url'] ?? '', 0, 2000),
                json_encode($data['meta'] ?? []),
            ]);

            echo json_encode(['ok' => true]);
            break;


        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log('Tracker error: ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
}

// ── Helpers ────────────────────────────────────────────────────────

function sanitize(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

function ensureSession(PDO $db, string $sessionId, string $visitorId): void {
    $exists = $db->prepare("SELECT 1 FROM sessions WHERE session_id = ?");
    $exists->execute([$sessionId]);
    if (!$exists->fetchColumn()) {
        $db->prepare("
            INSERT IGNORE INTO sessions (session_id, visitor_id, ip_address)
            VALUES (?, ?, ?)
        ")->execute([$sessionId, $visitorId, getClientIP()]);
    }
}
