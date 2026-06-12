<?php
// ============================================
// config.php  v1.1 — Fixed geo-location
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


// ── Geo API key (optional but recommended) ───────────────────────
// Option A: https://ipgeolocation.io  — free 30,000/month  ← BEST
// Option B: https://ip-api.com        — free 45 req/min, no key needed
// Option C: https://ipapi.co          — free 1,000/day, no key needed
// Leave empty to auto-try all free services with fallback chain
define('GEO_API_KEY',      '');        // ipgeolocation.io key if you have one
define('GEO_PREFERRED',    'ip-api');  // 'ip-api' | 'ipgeolocation' | 'ipapi'

define('REALTIME_TIMEOUT', 30);

// ── DB ───────────────────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ── IP detection (handles Cloudflare, proxies, load balancers) ───
function getClientIP(): string {
    $candidates = [];

    // Collect all possible IP headers
    $headers = [
        'HTTP_CF_CONNECTING_IP',    // Cloudflare real IP
        'HTTP_X_REAL_IP',           // nginx proxy
        'HTTP_X_FORWARDED_FOR',     // standard proxy header (may be comma list)
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    foreach ($headers as $h) {
        if (empty($_SERVER[$h])) continue;
        // X-Forwarded-For can be a comma-separated list; take the first (original client)
        foreach (explode(',', $_SERVER[$h]) as $ip) {
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $candidates[] = $ip;
            }
        }
    }

    // Prefer a public IP over a private one
    foreach ($candidates as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
    }

    // Fall back to first valid IP even if private
    return $candidates[0] ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

// ── Core HTTP fetch (cURL first, file_get_contents fallback) ─────
function httpGet(string $url, int $timeout = 4): ?string {
    // Try cURL first (more reliable on shared hosting)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 2,
            CURLOPT_SSL_VERIFYPEER => false,   // needed on some shared hosts
            CURLOPT_USERAGENT      => 'VisitorTracker/1.1',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $result = curl_exec($ch);
        $err    = curl_error($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($result !== false && $code >= 200 && $code < 300) {
            return $result;
        }
        // Log curl error for debugging
        if ($err) error_log("GeoIP cURL error for {$url}: {$err}");
    }

    // Fallback: file_get_contents (if allow_url_fopen is on)
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => [
            'timeout'        => $timeout,
            'ignore_errors'  => true,
            'user_agent'     => 'VisitorTracker/1.1',
        ]]);
        $result = @file_get_contents($url, false, $ctx);
        if ($result !== false) return $result;
    }

    return null;
}

// ── Geo data with 3-provider fallback chain ──────────────────────
function getGeoData(string $ip): array {
    // Skip private/loopback IPs immediately
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        error_log("GeoIP: skipping private/local IP: {$ip}");
        return [];
    }

    // Cache per IP for 24 hours (saves API quota)
    $cacheDir  = sys_get_temp_dir();
    $cacheFile = $cacheDir . '/geo_' . md5($ip) . '.json';

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cached['country_code'])) return $cached;
    }

    // Try each provider in order until one succeeds
    $providers = _buildProviderList();
    foreach ($providers as $name => $url) {
        $raw  = httpGet($url);
        $data = $raw ? json_decode($raw, true) : null;
        if (!$data) { error_log("GeoIP: no response from {$name} for {$ip}"); continue; }

        $normalized = _normalizeGeo($name, $data);
        if (empty($normalized['country_code'])) {
            error_log("GeoIP: {$name} returned no country_code for {$ip}. Raw: " . substr($raw, 0, 200));
            continue;
        }

        // Cache successful result
        file_put_contents($cacheFile, json_encode($normalized));
        error_log("GeoIP: success via {$name} for {$ip} → {$normalized['country_name']}, {$normalized['city']}");
        return $normalized;
    }

    error_log("GeoIP: ALL providers failed for IP: {$ip}");
    return [];
}

function _buildProviderList(): array {
    $ip  = getClientIP(); // used in URL building
    $key = GEO_API_KEY;

    // We build the full list and sort preferred one first
    $all = [
        // ip-api.com: free, no key, 45 req/min, very reliable
        'ip-api'        => "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city,lat,lon,timezone",

        // ipgeolocation.io: 30k/month free with key, or limited without
        'ipgeolocation'  => $key
            ? "https://api.ipgeolocation.io/ipgeo?apiKey={$key}&ip={$ip}&fields=country_code2,country_name,state_prov,city,latitude,longitude,time_zone"
            : "https://api.ipgeolocation.io/ipgeo?ip={$ip}&fields=country_code2,country_name,state_prov,city,latitude,longitude",

        // ipapi.co: 1000/day free, no key needed
        'ipapi'          => "https://ipapi.co/{$ip}/json/",

        // freeipapi.com: free, no key needed
        'freeipapi'      => "https://freeipapi.com/api/json/{$ip}",
    ];

    // Move preferred to front
    $preferred = GEO_PREFERRED;
    if (isset($all[$preferred])) {
        $entry = [$preferred => $all[$preferred]];
        unset($all[$preferred]);
        return array_merge($entry, $all);
    }

    return $all;
}

// Normalize different API response shapes into one consistent format
function _normalizeGeo(string $provider, array $d): array {
    switch ($provider) {
        case 'ip-api':
            if (($d['status'] ?? '') !== 'success') return [];
            return [
                'country_code' => $d['countryCode']  ?? '',
                'country_name' => $d['country']      ?? '',
                'region'       => $d['regionName']   ?? '',
                'city'         => $d['city']         ?? '',
                'latitude'     => $d['lat']          ?? null,
                'longitude'    => $d['lon']          ?? null,
                'timezone'     => $d['timezone']     ?? '',
            ];

        case 'ipgeolocation':
            return [
                'country_code' => $d['country_code2']  ?? $d['country_code'] ?? '',
                'country_name' => $d['country_name']   ?? '',
                'region'       => $d['state_prov']     ?? '',
                'city'         => $d['city']           ?? '',
                'latitude'     => $d['latitude']       ?? null,
                'longitude'    => $d['longitude']      ?? null,
                'timezone'     => $d['time_zone']['name'] ?? $d['timezone'] ?? '',
            ];

        case 'ipapi':
            if (!empty($d['error'])) return [];
            return [
                'country_code' => $d['country_code'] ?? $d['country'] ?? '',
                'country_name' => $d['country_name'] ?? '',
                'region'       => $d['region']       ?? '',
                'city'         => $d['city']         ?? '',
                'latitude'     => $d['latitude']     ?? null,
                'longitude'    => $d['longitude']    ?? null,
                'timezone'     => $d['timezone']     ?? '',
            ];

        case 'freeipapi':
            return [
                'country_code' => $d['countryCode'] ?? '',
                'country_name' => $d['countryName'] ?? '',
                'region'       => $d['regionName']  ?? '',
                'city'         => $d['cityName']    ?? '',
                'latitude'     => $d['latitude']    ?? null,
                'longitude'    => $d['longitude']   ?? null,
                'timezone'     => $d['timeZone']    ?? '',
            ];

        default:
            return [];
    }
}

// ── Referrer parser ──────────────────────────────────────────────
function parseReferrer(string $referrer): array {
    if (empty($referrer)) return ['source' => 'direct', 'medium' => ''];
    $host = strtolower(parse_url($referrer, PHP_URL_HOST) ?: '');
    $map  = [
        'google'    => 'google',    'bing'      => 'bing',
        'yahoo'     => 'yahoo',     'instagram' => 'instagram',
        'facebook'  => 'facebook',  'fb.com'    => 'facebook',
        'twitter'   => 'twitter',   't.co'      => 'twitter',
        'tiktok'    => 'tiktok',    'youtube'   => 'youtube',
        'youtu.be'  => 'youtube',   'linkedin'  => 'linkedin',
        'mail'      => 'email',     'email'     => 'email',
        'substack'  => 'email',
    ];
    foreach ($map as $needle => $source) {
        if (str_contains($host, $needle)) {
            return [
                'source' => $source,
                'medium' => in_array($source, ['google','bing','yahoo']) ? 'organic' : 'social',
            ];
        }
    }
    return ['source' => 'other', 'medium' => 'referral'];
}

// ── Interest inference ───────────────────────────────────────────
function inferInterests(string $url, string $title): array {
    $text = strtolower($url . ' ' . $title);
    $map  = [
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

function sanitize(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

function ensureSession(PDO $db, string $sessionId, string $visitorId): void {
    $s = $db->prepare("SELECT 1 FROM sessions WHERE session_id = ?");
    $s->execute([$sessionId]);
    if (!$s->fetchColumn()) {
        $db->prepare("INSERT IGNORE INTO sessions (session_id, visitor_id, ip_address) VALUES (?, ?, ?)")
           ->execute([$sessionId, $visitorId, getClientIP()]);
    }
}

function countryFlag(string $code): string {
    if (strlen($code) !== 2) return '';
    $code = strtoupper($code);
    return mb_convert_encoding(
        '&#' . (ord($code[0]) - 65 + 0x1F1E6) . ';&#' . (ord($code[1]) - 65 + 0x1F1E6) . ';',
        'UTF-8', 'HTML-ENTITIES'
    );
}
