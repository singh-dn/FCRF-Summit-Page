<?php
// ============================================
// geo_test.php  — Geo-location debug tool
// Visit: https://yourdomain.com/tracker/geo_test.php
// DELETE this file after confirming geo works!
// ============================================

require_once __DIR__ . '/config.php';

// Simple password protection
$pass = $_GET['key'] ?? '';
if ($pass !== TRACKER_SECRET) {
    http_response_code(403);
    die('Add ?key=YOUR_TRACKER_SECRET to the URL');
}

$testIP  = $_GET['ip'] ?? getClientIP();
$noCache = isset($_GET['nocache']);

// Clear cache for this IP if requested
if ($noCache) {
    $cacheFile = sys_get_temp_dir() . '/geo_' . md5($testIP) . '.json';
    if (file_exists($cacheFile)) unlink($cacheFile);
}

// Run each provider individually for diagnosis
function testProvider(string $name, string $url): array {
    $start  = microtime(true);
    $raw    = httpGet($url, 5);
    $ms     = round((microtime(true) - $start) * 1000);
    $parsed = $raw ? json_decode($raw, true) : null;
    $normalized = ($parsed && !empty($parsed)) ? _normalizeGeo($name, $parsed) : [];
    return [
        'provider'   => $name,
        'url'        => $url,
        'ms'         => $ms,
        'http_ok'    => $raw !== null,
        'parsed_ok'  => !empty($parsed),
        'geo_ok'     => !empty($normalized['country_code']),
        'result'     => $normalized,
        'raw_sample' => $raw ? substr($raw, 0, 300) : null,
    ];
}

$providers = _buildProviderList();
$results   = [];
foreach ($providers as $name => $url) {
    $results[] = testProvider($name, $url);
}

// Final result (what the system actually returns)
$final = getGeoData($testIP);

// Environment checks
$curlOk  = function_exists('curl_init');
$fgcOk   = (bool) ini_get('allow_url_fopen');
$tmpDir  = sys_get_temp_dir();
$tmpWrite= is_writable($tmpDir);

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Geo-location Debug</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f5f7fb;color:#1a1d2e;padding:32px;font-size:14px}
h1{font-size:1.4rem;margin-bottom:4px}
.sub{color:#666;margin-bottom:28px;font-size:.9rem}
.section{background:#fff;border:1px solid #e0e3ec;border-radius:12px;padding:20px 24px;margin-bottom:20px}
.section h2{font-size:.95rem;font-weight:700;margin-bottom:14px;color:#333}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.item{background:#f8faff;border:1px solid #e0e3ec;border-radius:8px;padding:12px}
.item .label{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:#888;margin-bottom:3px}
.item .value{font-size:1rem;font-weight:600;color:#1a1d2e}
.ok  {color:#10b981}
.fail{color:#ef4444}
.warn{color:#f59e0b}
table{width:100%;border-collapse:collapse}
th{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:#888;padding:6px 8px;border-bottom:2px solid #e0e3ec;text-align:left}
td{padding:9px 8px;border-bottom:1px solid #f0f2f8;vertical-align:top;font-size:.85rem}
tr:last-child td{border-bottom:none}
.badge{display:inline-block;padding:2px 9px;border-radius:20px;font-size:.75rem;font-weight:600}
.badge.ok  {background:#d1fae5;color:#065f46}
.badge.fail{background:#fee2e2;color:#7f1d1d}
pre{background:#f1f5f9;border-radius:6px;padding:10px;font-size:.78rem;white-space:pre-wrap;word-break:break-all;color:#334155;margin-top:4px;max-height:120px;overflow-y:auto}
.warn-box{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;font-size:.85rem;color:#92400e;margin-bottom:16px}
.final{background:#ecfdf5;border:1px solid #6ee7b7;border-radius:8px;padding:16px 20px}
.final h2{color:#065f46;margin-bottom:10px}
</style>
</head>
<body>
<h1>🌍 Geo-location Debug Tool</h1>
<p class="sub">Testing IP: <strong><?= htmlspecialchars($testIP) ?></strong>
  &nbsp;|&nbsp; <a href="?key=<?= urlencode(TRACKER_SECRET) ?>&nocache=1">Clear cache &amp; retest</a>
  &nbsp;|&nbsp; <a href="?key=<?= urlencode(TRACKER_SECRET) ?>&ip=8.8.8.8&nocache=1">Test with 8.8.8.8 (Google DNS)</a>
</p>

<!-- Environment -->
<div class="section">
  <h2>⚙️ Server Environment</h2>
  <?php if (!$curlOk && !$fgcOk): ?>
  <div class="warn-box">⚠️ Neither cURL nor allow_url_fopen is available. Geo-location CANNOT work. Contact Hostinger support to enable cURL.</div>
  <?php endif ?>
  <div class="grid">
    <div class="item"><div class="label">cURL extension</div><div class="value <?= $curlOk?'ok':'fail' ?>"><?= $curlOk ? '✓ Available' : '✗ Not available' ?></div></div>
    <div class="item"><div class="label">allow_url_fopen</div><div class="value <?= $fgcOk?'ok':'warn' ?>"><?= $fgcOk ? '✓ Enabled' : '⚠ Disabled' ?></div></div>
    <div class="item"><div class="label">PHP version</div><div class="value"><?= PHP_VERSION ?></div></div>
    <div class="item"><div class="label">Temp dir writable</div><div class="value <?= $tmpWrite?'ok':'fail' ?>"><?= $tmpWrite ? '✓ '.$tmpDir : '✗ Not writable' ?></div></div>
    <div class="item"><div class="label">Server IP (REMOTE_ADDR)</div><div class="value"><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown') ?></div></div>
    <div class="item"><div class="label">Preferred provider</div><div class="value"><?= GEO_PREFERRED ?></div></div>
    <div class="item"><div class="label">API key set</div><div class="value <?= GEO_API_KEY?'ok':'warn' ?>"><?= GEO_API_KEY ? '✓ Yes' : '⚠ No (using free)' ?></div></div>
  </div>
</div>

<!-- Provider tests -->
<div class="section">
  <h2>🔌 Provider Test Results</h2>
  <table>
    <thead><tr><th>Provider</th><th>HTTP</th><th>Parsed</th><th>Got Geo</th><th>Speed</th><th>Country</th><th>City</th><th>Raw sample</th></tr></thead>
    <tbody>
      <?php foreach ($results as $r): ?>
      <tr>
        <td><strong><?= htmlspecialchars($r['provider']) ?></strong></td>
        <td><span class="badge <?= $r['http_ok']   ?'ok':'fail' ?>"><?= $r['http_ok']   ?'✓':'✗' ?></span></td>
        <td><span class="badge <?= $r['parsed_ok'] ?'ok':'fail' ?>"><?= $r['parsed_ok'] ?'✓':'✗' ?></span></td>
        <td><span class="badge <?= $r['geo_ok']    ?'ok':'fail' ?>"><?= $r['geo_ok']    ?'✓':'✗' ?></span></td>
        <td><?= $r['ms'] ?>ms</td>
        <td><?= htmlspecialchars($r['result']['country_name'] ?? '—') ?> (<?= htmlspecialchars($r['result']['country_code'] ?? '—') ?>)</td>
        <td><?= htmlspecialchars($r['result']['city'] ?? '—') ?></td>
        <td><pre><?= htmlspecialchars($r['raw_sample'] ?? 'No response') ?></pre></td>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

<!-- Final result -->
<div class="section">
  <h2>✅ Final Result (what gets stored in DB)</h2>
  <?php if (!empty($final)): ?>
  <div class="final">
    <h2>🎉 Geo-location is working!</h2>
    <div class="grid" style="margin-top:10px">
      <?php foreach ($final as $k => $v): ?>
      <div class="item"><div class="label"><?= htmlspecialchars($k) ?></div><div class="value"><?= htmlspecialchars((string)$v) ?></div></div>
      <?php endforeach ?>
    </div>
  </div>
  <?php else: ?>
  <div class="warn-box" style="background:#fee2e2;border-color:#fca5a5;color:#7f1d1d">
    ❌ Geo-location returned empty. All providers failed for this IP.<br><br>
    <strong>Common fixes:</strong><br>
    1. If testing from localhost — use the link above to test with IP 8.8.8.8<br>
    2. Enable cURL in Hostinger PHP settings (hPanel → PHP Config)<br>
    3. Try getting a free API key from <a href="https://ipgeolocation.io" target="_blank">ipgeolocation.io</a> and add it to config.php<br>
    4. Check PHP error_log for specific error messages
  </div>
  <?php endif ?>
</div>

<p style="color:#aaa;font-size:.8rem;margin-top:8px">
  ⚠️ Delete <code>geo_test.php</code> from your server once geo is confirmed working.
</p>
</body>
</html>
