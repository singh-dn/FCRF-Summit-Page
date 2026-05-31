<?php
// ============================================
// realtime.php — Server-Sent Events endpoint
// Streams live active user count to dashboard
// ============================================

require_once __DIR__ . '/config.php';

// Auth check
session_start();
if (!isset($_SESSION['dashboard_auth']) || $_SESSION['dashboard_auth'] !== true) {
    http_response_code(401);
    exit('Unauthorized');
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

set_time_limit(0);
ignore_user_abort(false);

$db = getDB();

while (true) {
    if (connection_aborted()) break;

    try {
        // Clean stale heartbeats
        $db->prepare("DELETE FROM heartbeats WHERE last_ping < DATE_SUB(NOW(), INTERVAL ? SECOND)")
           ->execute([REALTIME_TIMEOUT]);

        // Active users right now
        $active = (int)$db->query("SELECT COUNT(*) FROM heartbeats")->fetchColumn();

        // Visitors in last 30 min
        $recent = (int)$db->query("SELECT COUNT(DISTINCT session_id) FROM sessions WHERE last_seen_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)")->fetchColumn();

        // Today's sessions
        $today = (int)$db->query("SELECT COUNT(*) FROM sessions WHERE DATE(started_at) = CURDATE()")->fetchColumn();

        // Top active pages right now
        $stmt = $db->query("
            SELECT page_url, COUNT(*) as cnt
            FROM heartbeats
            GROUP BY page_url
            ORDER BY cnt DESC
            LIMIT 5
        ");
        $activePages = $stmt->fetchAll();

        $payload = json_encode([
            'active'       => $active,
            'recent_30min' => $recent,
            'today'        => $today,
            'active_pages' => $activePages,
            'ts'           => date('H:i:s'),
        ]);

        echo "data: {$payload}\n\n";
        ob_flush();
        flush();

    } catch (Exception $e) {
        echo "data: {\"error\":true}\n\n";
        ob_flush();
        flush();
    }

    sleep(5); // Update every 5 seconds
}
