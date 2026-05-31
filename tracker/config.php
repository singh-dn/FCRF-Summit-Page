<?php
// ============================================
// config.php — Edit these with your Hostinger
// MySQL credentials before uploading
// ============================================

define('DB_HOST', 'localhost');          // usually localhost on Hostinger
define('DB_NAME', 'u545411682_summit');    // your database name
define('DB_USER', 'u545411682_summit');   // your MySQL username
define('DB_PASS', 'Summit2026');   // your MySQL password
define('DB_CHARSET', 'utf8mb4');

// Security: secret key to validate tracker requests
// Change this and update it in tracker.js too
define('TRACKER_SECRET', 'x9Kf2LmQ7vNp4RsT8yZa1BcDeFgH6Jk');

// Dashboard password (simple protection)
define('DASHBOARD_PASSWORD', 'fcsummit');

// IP Geolocation API (free tier available)
// Get key from: https://ipapi.co  (no key needed for basic)
// Or https://ipgeolocation.io (free 30k/month)
define('GEO_API_KEY', '');   // leave empty to use ipapi.co free

// How many seconds without heartbeat = session expired (for real-time count)
define('REALTIME_TIMEOUT', 30);

// ---- DO NOT EDIT BELOW ----

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

function getClientIP(): string {
    $headers = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $ip = trim(explode(',', $_SERVER[$h])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function getGeoData(string $ip): array {
    $cacheFile = sys_get_temp_dir() . '/geo_' . md5($ip) . '.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
        return json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    $key = GEO_API_KEY ? '?api_key=' . GEO_API_KEY : '';
    $url = "https://ipapi.co/{$ip}/json/{$key}";
    $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);
    $data = $raw ? (json_decode($raw, true) ?: []) : [];
    if (!empty($data['country_code'])) {
        file_put_contents($cacheFile, json_encode($data));
    }
    return $data;
}

function parseReferrer(string $referrer): array {
    if (empty($referrer)) return ['source' => 'direct', 'medium' => ''];
    $host = strtolower(parse_url($referrer, PHP_URL_HOST) ?: '');
    $map = [
        'google'    => 'google',
        'bing'      => 'bing',
        'yahoo'     => 'yahoo',
        'instagram' => 'instagram',
        'facebook'  => 'facebook',
        'fb.com'    => 'facebook',
        'twitter'   => 'twitter',
        't.co'      => 'twitter',
        'tiktok'    => 'tiktok',
        'youtube'   => 'youtube',
        'youtu.be'  => 'youtube',
        'linkedin'  => 'linkedin',
        'mail'      => 'email',
        'email'     => 'email',
        'substack'  => 'email',
    ];
    foreach ($map as $needle => $source) {
        if (str_contains($host, $needle)) {
            return ['source' => $source, 'medium' => in_array($source, ['google','bing','yahoo']) ? 'organic' : 'social'];
        }
    }
    return ['source' => 'other', 'medium' => 'referral'];
}

function inferInterests(string $url, string $title): array {
    $text = strtolower($url . ' ' . $title);
    $map = [
        'technology'  => ['tech','software','code','app','digital','ai','programming'],
        'fashion'     => ['fashion','clothing','outfit','style','wear','dress'],
        'food'        => ['food','recipe','restaurant','eat','drink','cook'],
        'travel'      => ['travel','hotel','flight','tour','destination','trip'],
        'health'      => ['health','fitness','gym','workout','medical','wellness'],
        'finance'     => ['finance','invest','stock','crypto','money','bank','loan'],
        'sports'      => ['sport','football','cricket','tennis','match','game'],
        'ecommerce'   => ['product','cart','buy','shop','checkout','order','price'],
        'news'        => ['news','article','blog','post','latest','update'],
        'education'   => ['course','learn','tutorial','edu','university','school'],
    ];
    $found = [];
    foreach ($map as $interest => $keywords) {
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) { $found[] = $interest; break; }
        }
    }
    return $found;
}
