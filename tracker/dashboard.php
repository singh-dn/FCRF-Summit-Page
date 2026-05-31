<?php
// ============================================
// dashboard.php — Analytics Dashboard
// Visit: https://yourdomain.com/tracker/dashboard.php
// ============================================

require_once __DIR__ . '/config.php';
session_start();

// ── Login ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === DASHBOARD_PASSWORD) {
        $_SESSION['dashboard_auth'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $loginError = 'Invalid password';
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION['dashboard_auth'])) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Analytics — Login</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{background:#0a0a0f;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'Segoe UI',sans-serif}
  .box{background:#111118;border:1px solid #222;border-radius:16px;padding:48px;width:360px;text-align:center}
  h1{color:#fff;font-size:1.4rem;margin-bottom:8px}
  p{color:#666;font-size:.85rem;margin-bottom:32px}
  input{width:100%;padding:12px 16px;background:#1a1a24;border:1px solid #333;border-radius:10px;color:#fff;font-size:1rem;outline:none;margin-bottom:16px}
  input:focus{border-color:#6c63ff}
  button{width:100%;padding:13px;background:#6c63ff;border:none;border-radius:10px;color:#fff;font-size:1rem;font-weight:600;cursor:pointer}
  button:hover{background:#7c74ff}
  .err{color:#ff6b6b;font-size:.85rem;margin-bottom:12px}
</style>
</head>
<body>
<div class="box">
  <h1>📊 Analytics Dashboard</h1>
  <p>Visitor Tracking System</p>
  <?php if (!empty($loginError)): ?><p class="err"><?= htmlspecialchars($loginError) ?></p><?php endif ?>
  <form method="POST">
    <input type="password" name="password" placeholder="Enter password" autofocus>
    <button type="submit">Sign In</button>
  </form>
</div>
</body>
</html>
<?php exit; }

// ── Data Queries ─────────────────────────────────────────────────
$db     = getDB();
$range  = intval($_GET['days'] ?? 7);
$range  = in_array($range, [1,7,30,90]) ? $range : 7;

function q(PDO $db, string $sql, array $bind = []): array {
    $s = $db->prepare($sql); $s->execute($bind); return $s->fetchAll();
}
function qs(PDO $db, string $sql, array $bind = []): string {
    $s = $db->prepare($sql); $s->execute($bind); return (string)$s->fetchColumn();
}

$since = "-{$range} days";

// Overview cards
$totalSessions   = qs($db, "SELECT COUNT(*) FROM sessions WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY)", [$range]);
$uniqueVisitors  = qs($db, "SELECT COUNT(DISTINCT visitor_id) FROM sessions WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY)", [$range]);
$returningPct    = qs($db, "SELECT ROUND(AVG(is_returning)*100,1) FROM sessions WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY)", [$range]);
$avgDuration     = qs($db, "SELECT ROUND(AVG(duration_seconds)) FROM sessions WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY) AND duration_seconds > 0", [$range]);
$avgPages        = qs($db, "SELECT ROUND(AVG(page_count),1) FROM sessions WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY)", [$range]);
$totalPageviews  = qs($db, "SELECT COUNT(*) FROM pageviews WHERE visited_at > DATE_SUB(NOW(), INTERVAL ? DAY)", [$range]);

// Device breakdown
$devices = q($db, "SELECT device_type, COUNT(*) as cnt FROM sessions WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY device_type ORDER BY cnt DESC", [$range]);

// Browser breakdown
$browsers = q($db, "SELECT browser, COUNT(*) as cnt FROM sessions WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY) AND browser != '' GROUP BY browser ORDER BY cnt DESC LIMIT 8", [$range]);

// Traffic sources
$sources = q($db, "SELECT referrer_source, COUNT(*) as cnt FROM sessions WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY referrer_source ORDER BY cnt DESC", [$range]);

// Top countries
$countries = q($db, "SELECT country_name, country_code, COUNT(*) as cnt FROM sessions WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY) AND country_name != '' GROUP BY country_name, country_code ORDER BY cnt DESC LIMIT 10", [$range]);

// Top cities
$cities = q($db, "SELECT city, country_code, COUNT(*) as cnt FROM sessions WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY) AND city != '' GROUP BY city, country_code ORDER BY cnt DESC LIMIT 10", [$range]);

// Top pages
$topPages = q($db, "SELECT page_url, page_title, COUNT(*) as views, ROUND(AVG(time_on_page)) as avg_time, ROUND(AVG(scroll_depth)) as avg_scroll FROM pageviews WHERE visited_at > DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY page_url, page_title ORDER BY views DESC LIMIT 15", [$range]);

// OS breakdown
$osData = q($db, "SELECT os, COUNT(*) as cnt FROM sessions WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY) AND os != '' GROUP BY os ORDER BY cnt DESC LIMIT 8", [$range]);

// Daily sessions chart data
$dailyData = q($db, "
    SELECT DATE(started_at) as day, COUNT(*) as sessions, COUNT(DISTINCT visitor_id) as visitors
    FROM sessions
    WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY DATE(started_at)
    ORDER BY day ASC
", [$range]);

// Top search keywords
$keywords = q($db, "SELECT keyword, COUNT(*) as cnt FROM searches WHERE searched_at > DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY keyword ORDER BY cnt DESC LIMIT 15", [$range]);

// Top interests
$interests = q($db, "SELECT interest_tag, SUM(score) as total FROM interests GROUP BY interest_tag ORDER BY total DESC LIMIT 10");

// Top clicked elements
$topClicks = q($db, "SELECT element_tag, element_text, COUNT(*) as cnt FROM events WHERE event_type='click' AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY) AND element_text != '' GROUP BY element_tag, element_text ORDER BY cnt DESC LIMIT 10", [$range]);

// Screen resolutions
$screens = q($db, "SELECT CONCAT(screen_width,'×',screen_height) as res, COUNT(*) as cnt FROM sessions WHERE started_at > DATE_SUB(NOW(), INTERVAL ? DAY) AND screen_width > 0 GROUP BY screen_width, screen_height ORDER BY cnt DESC LIMIT 8", [$range]);

// JSON for charts
$dailyJson    = json_encode($dailyData);
$deviceJson   = json_encode($devices);
$sourceJson   = json_encode($sources);
$browserJson  = json_encode($browsers);

function fmt_duration($sec) {
    $sec = (int)$sec;
    if ($sec < 60) return $sec . 's';
    return floor($sec/60) . 'm ' . ($sec%60) . 's';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Analytics Dashboard</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
:root {
  --bg:       #07070d;
  --surface:  #0f0f1a;
  --surface2: #161626;
  --border:   #1e1e30;
  --accent:   #6c63ff;
  --accent2:  #ff6584;
  --accent3:  #43e97b;
  --text:     #e8e8f0;
  --muted:    #888899;
  --card-r:   14px;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; font-size: 14px; min-height: 100vh; }

/* Layout */
.shell { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }
.sidebar { background: var(--surface); border-right: 1px solid var(--border); padding: 24px 16px; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
.sidebar h2 { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 4px; }
.sidebar small { color: var(--muted); font-size: .75rem; }
.nav-section { margin-top: 28px; }
.nav-section label { display: block; color: var(--muted); font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px; }
.nav-link { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 8px; color: var(--muted); text-decoration: none; font-size: .88rem; cursor: pointer; border: none; background: none; width: 100%; text-align: left; }
.nav-link:hover, .nav-link.active { background: var(--surface2); color: var(--text); }
.nav-link .ico { font-size: 1rem; }

.main { padding: 28px 32px; overflow-x: hidden; }
.top-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
.top-bar h1 { font-size: 1.4rem; font-weight: 700; }
.controls { display: flex; align-items: center; gap: 10px; }
select { background: var(--surface); border: 1px solid var(--border); color: var(--text); padding: 8px 12px; border-radius: 8px; font-size: .85rem; cursor: pointer; }
.logout-btn { padding: 8px 16px; background: transparent; border: 1px solid var(--border); border-radius: 8px; color: var(--muted); cursor: pointer; font-size: .85rem; }
.logout-btn:hover { border-color: var(--accent2); color: var(--accent2); }

/* Live badge */
.live-badge { display: flex; align-items: center; gap: 8px; background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 6px 14px; font-size: .82rem; }
.live-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent3); animation: pulse 1.5s infinite; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.3)} }

/* Cards */
.cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px; }
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--card-r); padding: 20px; }
.card .label { color: var(--muted); font-size: .78rem; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 8px; }
.card .value { font-size: 2rem; font-weight: 700; color: #fff; line-height: 1; }
.card .sub { color: var(--muted); font-size: .8rem; margin-top: 4px; }
.card.accent  { border-color: rgba(108,99,255,.4); }
.card.accent2 { border-color: rgba(255,101,132,.4); }
.card.accent3 { border-color: rgba(67,233,123,.4); }

/* Sections */
.section { margin-bottom: 28px; }
.section-title { font-size: .85rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 14px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
@media(max-width:900px){ .grid-2,.grid-3{ grid-template-columns:1fr } .shell{ grid-template-columns:1fr } .sidebar{ display:none } }

.panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--card-r); padding: 20px; }
.panel h3 { font-size: .9rem; font-weight: 600; margin-bottom: 16px; }
canvas { max-height: 220px; }

/* Tables */
.tbl { width: 100%; border-collapse: collapse; }
.tbl th { color: var(--muted); font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; padding: 6px 0; border-bottom: 1px solid var(--border); text-align: left; }
.tbl td { padding: 9px 0; border-bottom: 1px solid rgba(255,255,255,.04); font-size: .85rem; }
.tbl tr:last-child td { border-bottom: none; }
.tbl .url { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #aaa; }
.tbl .num { color: #fff; font-weight: 600; }

/* Bar rows */
.bar-list { display: flex; flex-direction: column; gap: 10px; }
.bar-row { display: flex; flex-direction: column; gap: 4px; }
.bar-label { display: flex; justify-content: space-between; font-size: .82rem; }
.bar-label span:first-child { color: var(--text); }
.bar-label span:last-child { color: var(--muted); }
.bar-track { background: var(--surface2); border-radius: 4px; height: 6px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 4px; background: var(--accent); transition: width .5s ease; }

/* Tags */
.tag { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: .75rem; font-weight: 600; margin: 2px; background: var(--surface2); color: var(--muted); border: 1px solid var(--border); }
.tag.big { padding: 5px 12px; font-size: .85rem; }

/* Flag emoji */
.flag { margin-right: 6px; }

/* Active pages */
.active-pages { display: flex; flex-direction: column; gap: 6px; }
.active-page-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 10px; background: var(--surface2); border-radius: 8px; font-size: .82rem; }
.active-page-row .page-url { color: #aaa; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 70%; }
.active-page-row .page-cnt { background: var(--accent); color: #fff; border-radius: 12px; padding: 2px 10px; font-size: .78rem; font-weight: 700; }

#realtimeTs { color: var(--muted); font-size: .75rem; margin-top: 8px; }
</style>
</head>
<body>
<div class="shell">

<!-- Sidebar -->
<aside class="sidebar">
  <h2>📊 Analytics</h2>
  <small>Visitor Tracking System</small>
  <div class="nav-section">
    <label>Views</label>
    <button class="nav-link active" onclick="showSection('overview')"><span class="ico">🏠</span> Overview</button>
    <button class="nav-link" onclick="showSection('realtime')"><span class="ico">🔴</span> Real-time</button>
    <button class="nav-link" onclick="showSection('pages')"><span class="ico">📄</span> Pages</button>
    <button class="nav-link" onclick="showSection('audience')"><span class="ico">👥</span> Audience</button>
    <button class="nav-link" onclick="showSection('traffic')"><span class="ico">🔗</span> Traffic Sources</button>
    <button class="nav-link" onclick="showSection('behavior')"><span class="ico">🖱️</span> Behavior</button>
    <button class="nav-link" onclick="showSection('geo')"><span class="ico">🌍</span> Geography</button>
  </div>
  <div class="nav-section">
    <label>Account</label>
    <form method="POST" style="display:inline">
      <button name="logout" value="1" class="nav-link" style="color:#ff6584"><span class="ico">🚪</span> Logout</button>
    </form>
  </div>
</aside>

<!-- Main -->
<main class="main">
  <div class="top-bar">
    <h1 id="section-title">Overview</h1>
    <div class="controls">
      <div class="live-badge">
        <div class="live-dot"></div>
        <span id="liveCount">—</span> online now
      </div>
      <form method="GET" style="display:inline">
        <select name="days" onchange="this.form.submit()">
          <option value="1"  <?= $range==1?'selected':'' ?>>Today</option>
          <option value="7"  <?= $range==7?'selected':'' ?>>Last 7 days</option>
          <option value="30" <?= $range==30?'selected':'' ?>>Last 30 days</option>
          <option value="90" <?= $range==90?'selected':'' ?>>Last 90 days</option>
        </select>
      </form>
    </div>
  </div>

  <!-- ── OVERVIEW ── -->
  <div id="sec-overview">
    <div class="cards">
      <div class="card accent"><div class="label">Sessions</div><div class="value"><?= number_format($totalSessions) ?></div><div class="sub">in <?= $range ?> day<?= $range>1?'s':'' ?></div></div>
      <div class="card"><div class="label">Unique Visitors</div><div class="value"><?= number_format($uniqueVisitors) ?></div><div class="sub">distinct IDs</div></div>
      <div class="card"><div class="label">Page Views</div><div class="value"><?= number_format($totalPageviews) ?></div><div class="sub"><?= $totalSessions > 0 ? round($totalPageviews/$totalSessions,1) : 0 ?>/session avg</div></div>
      <div class="card accent3"><div class="label">Avg Duration</div><div class="value"><?= fmt_duration($avgDuration) ?></div><div class="sub">time on site</div></div>
      <div class="card"><div class="label">Pages/Session</div><div class="value"><?= $avgPages ?></div><div class="sub">avg page depth</div></div>
      <div class="card accent2"><div class="label">Returning</div><div class="value"><?= $returningPct ?>%</div><div class="sub">of visitors</div></div>
    </div>

    <div class="grid-2 section">
      <div class="panel">
        <h3>Sessions over time</h3>
        <canvas id="dailyChart"></canvas>
      </div>
      <div class="panel">
        <h3>Traffic Sources</h3>
        <canvas id="sourceChart"></canvas>
      </div>
    </div>

    <div class="grid-3 section">
      <div class="panel">
        <h3>Devices</h3>
        <canvas id="deviceChart"></canvas>
      </div>
      <div class="panel">
        <h3>Browsers</h3>
        <div class="bar-list">
          <?php $maxB = max(array_column($browsers,'cnt') ?: [1]); foreach ($browsers as $b): ?>
          <div class="bar-row">
            <div class="bar-label"><span><?= htmlspecialchars($b['browser']) ?></span><span><?= $b['cnt'] ?></span></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= round($b['cnt']/$maxB*100) ?>%"></div></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="panel">
        <h3>Inferred Interests</h3>
        <div style="margin-top:8px">
          <?php foreach ($interests as $i): ?>
          <span class="tag big"><?= htmlspecialchars($i['interest_tag']) ?> <em style="opacity:.6"><?= $i['total'] ?></em></span>
          <?php endforeach; ?>
          <?php if (!$interests): ?><p style="color:var(--muted);font-size:.85rem">No data yet</p><?php endif ?>
        </div>
      </div>
    </div>
  </div><!-- /overview -->

  <!-- ── REAL-TIME ── -->
  <div id="sec-realtime" style="display:none">
    <div class="cards">
      <div class="card accent3"><div class="label">Active Now</div><div class="value" id="rt-active">—</div><div class="sub">online users</div></div>
      <div class="card"><div class="label">Last 30 min</div><div class="value" id="rt-30min">—</div><div class="sub">unique sessions</div></div>
      <div class="card"><div class="label">Today</div><div class="value" id="rt-today">—</div><div class="sub">total sessions</div></div>
    </div>
    <div class="panel" style="max-width:600px">
      <h3>Active Pages Right Now</h3>
      <div class="active-pages" id="rt-pages" style="min-height:60px">
        <p style="color:var(--muted);font-size:.85rem">Waiting for data…</p>
      </div>
      <p id="realtimeTs"></p>
    </div>
  </div>

  <!-- ── PAGES ── -->
  <div id="sec-pages" style="display:none">
    <div class="panel">
      <h3>Top Pages</h3>
      <table class="tbl">
        <thead><tr><th>Page</th><th>Views</th><th>Avg Time</th><th>Avg Scroll</th></tr></thead>
        <tbody>
          <?php foreach ($topPages as $p): ?>
          <tr>
            <td><div class="url" title="<?= htmlspecialchars($p['page_url']) ?>"><?= htmlspecialchars($p['page_title'] ?: $p['page_url']) ?></div></td>
            <td class="num"><?= number_format($p['views']) ?></td>
            <td><?= fmt_duration($p['avg_time']) ?></td>
            <td><?= $p['avg_scroll'] ?>%</td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$topPages): ?><tr><td colspan="4" style="color:var(--muted)">No pageview data yet</td></tr><?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── AUDIENCE ── -->
  <div id="sec-audience" style="display:none">
    <div class="grid-2 section">
      <div class="panel">
        <h3>Operating Systems</h3>
        <div class="bar-list">
          <?php $maxO = max(array_column($osData,'cnt') ?: [1]); foreach ($osData as $o): ?>
          <div class="bar-row">
            <div class="bar-label"><span><?= htmlspecialchars($o['os']) ?></span><span><?= $o['cnt'] ?></span></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= round($o['cnt']/$maxO*100) ?>%;background:#ff6584"></div></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="panel">
        <h3>Screen Resolutions</h3>
        <div class="bar-list">
          <?php $maxSc = max(array_column($screens,'cnt') ?: [1]); foreach ($screens as $sc): ?>
          <div class="bar-row">
            <div class="bar-label"><span><?= htmlspecialchars($sc['res']) ?></span><span><?= $sc['cnt'] ?></span></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= round($sc['cnt']/$maxSc*100) ?>%;background:#43e97b"></div></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="panel section">
      <h3>Browsers</h3>
      <canvas id="browserChart" style="max-height:200px"></canvas>
    </div>
  </div>

  <!-- ── TRAFFIC ── -->
  <div id="sec-traffic" style="display:none">
    <div class="grid-2 section">
      <div class="panel">
        <h3>Traffic Source Breakdown</h3>
        <div class="bar-list" style="margin-top:8px">
          <?php $maxS = max(array_column($sources,'cnt') ?: [1]);
          $srcIcons = ['direct'=>'🔗','google'=>'🔍','instagram'=>'📸','facebook'=>'👍','twitter'=>'🐦','tiktok'=>'🎵','youtube'=>'▶️','bing'=>'🔎','email'=>'📧','other'=>'🌐','linkedin'=>'💼','yahoo'=>'🟣'];
          foreach ($sources as $s): ?>
          <div class="bar-row">
            <div class="bar-label"><span><?= ($srcIcons[$s['referrer_source']] ?? '🔗') . ' ' . ucfirst(htmlspecialchars($s['referrer_source'])) ?></span><span><?= $s['cnt'] ?></span></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= round($s['cnt']/$maxS*100) ?>%"></div></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="panel">
        <h3>Source Chart</h3>
        <canvas id="sourcePieChart"></canvas>
      </div>
    </div>
  </div>

  <!-- ── BEHAVIOR ── -->
  <div id="sec-behavior" style="display:none">
    <div class="grid-2 section">
      <div class="panel">
        <h3>🔍 Top Search Keywords</h3>
        <table class="tbl">
          <thead><tr><th>Keyword</th><th>Searches</th></tr></thead>
          <tbody>
            <?php foreach ($keywords as $k): ?>
            <tr><td><?= htmlspecialchars($k['keyword']) ?></td><td class="num"><?= $k['cnt'] ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$keywords): ?><tr><td colspan="2" style="color:var(--muted)">No search data yet</td></tr><?php endif ?>
          </tbody>
        </table>
      </div>
      <div class="panel">
        <h3>🖱️ Top Clicked Elements</h3>
        <table class="tbl">
          <thead><tr><th>Element</th><th>Text</th><th>Clicks</th></tr></thead>
          <tbody>
            <?php foreach ($topClicks as $c): ?>
            <tr><td><code style="color:var(--accent);font-size:.8rem">&lt;<?= htmlspecialchars($c['element_tag']) ?>&gt;</code></td><td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($c['element_text']) ?></td><td class="num"><?= $c['cnt'] ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$topClicks): ?><tr><td colspan="3" style="color:var(--muted)">No click data yet</td></tr><?php endif ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── GEO ── -->
  <div id="sec-geo" style="display:none">
    <div class="grid-2 section">
      <div class="panel">
        <h3>🌍 Top Countries</h3>
        <table class="tbl">
          <thead><tr><th>Country</th><th>Sessions</th></tr></thead>
          <tbody>
            <?php foreach ($countries as $c): ?>
            <tr><td><span class="flag"><?= countryFlag($c['country_code']) ?></span><?= htmlspecialchars($c['country_name']) ?></td><td class="num"><?= $c['cnt'] ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$countries): ?><tr><td colspan="2" style="color:var(--muted)">No location data yet</td></tr><?php endif ?>
          </tbody>
        </table>
      </div>
      <div class="panel">
        <h3>🏙️ Top Cities</h3>
        <table class="tbl">
          <thead><tr><th>City</th><th>Sessions</th></tr></thead>
          <tbody>
            <?php foreach ($cities as $c): ?>
            <tr><td><span class="flag"><?= countryFlag($c['country_code']) ?></span><?= htmlspecialchars($c['city']) ?></td><td class="num"><?= $c['cnt'] ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$cities): ?><tr><td colspan="2" style="color:var(--muted)">No city data yet</td></tr><?php endif ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</main>
</div><!-- /shell -->

<script>
// ── Section navigation ────────────────────────────────────────────
const sections = ['overview','realtime','pages','audience','traffic','behavior','geo'];
const titles   = {overview:'Overview',realtime:'Real-time',pages:'Pages',audience:'Audience',traffic:'Traffic Sources',behavior:'Behavior',geo:'Geography'};

function showSection(id) {
  sections.forEach(s => {
    document.getElementById('sec-' + s).style.display = s === id ? '' : 'none';
  });
  document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
  document.querySelector(`.nav-link[onclick*="${id}"]`)?.classList.add('active');
  document.getElementById('section-title').textContent = titles[id] || id;
  if (id === 'realtime' && !sseStarted) startSSE();
}

// ── Charts ────────────────────────────────────────────────────────
const C = Chart;
const gridColor = 'rgba(255,255,255,0.05)';
const daily  = <?= $dailyJson ?>;
const devs   = <?= $deviceJson ?>;
const srcs   = <?= $sourceJson ?>;
const brows  = <?= $browserJson ?>;

// Daily chart
new C(document.getElementById('dailyChart'), {
  type: 'line',
  data: {
    labels: daily.map(d => d.day),
    datasets: [
      { label: 'Sessions', data: daily.map(d => d.sessions), borderColor: '#6c63ff', backgroundColor: 'rgba(108,99,255,0.12)', fill: true, tension: 0.4, pointRadius: 3 },
      { label: 'Visitors', data: daily.map(d => d.visitors), borderColor: '#43e97b', backgroundColor: 'rgba(67,233,123,0.08)', fill: true, tension: 0.4, pointRadius: 3 },
    ]
  },
  options: { responsive: true, plugins: { legend: { labels: { color: '#888' } } }, scales: { x: { ticks: { color: '#666' }, grid: { color: gridColor } }, y: { ticks: { color: '#666' }, grid: { color: gridColor } } } }
});

// Device donut
const devColors = { desktop: '#6c63ff', mobile: '#ff6584', tablet: '#43e97b', bot: '#ffa', unknown: '#555' };
new C(document.getElementById('deviceChart'), {
  type: 'doughnut',
  data: { labels: devs.map(d => d.device_type), datasets: [{ data: devs.map(d => d.cnt), backgroundColor: devs.map(d => devColors[d.device_type] || '#888'), borderWidth: 0 }] },
  options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#888', padding: 12 } } }, cutout: '65%' }
});

// Source donut
const srcColors = ['#6c63ff','#ff6584','#43e97b','#ffd166','#ef476f','#06d6a0','#118ab2','#ffa'];
new C(document.getElementById('sourceChart'), {
  type: 'doughnut',
  data: { labels: srcs.map(s => s.referrer_source), datasets: [{ data: srcs.map(s => s.cnt), backgroundColor: srcColors, borderWidth: 0 }] },
  options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#888', padding: 10 } } }, cutout: '60%' }
});

// Source pie (traffic section)
new C(document.getElementById('sourcePieChart'), {
  type: 'pie',
  data: { labels: srcs.map(s => s.referrer_source), datasets: [{ data: srcs.map(s => s.cnt), backgroundColor: srcColors, borderWidth: 0 }] },
  options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#888' } } } }
});

// Browser bar (audience section)
new C(document.getElementById('browserChart'), {
  type: 'bar',
  data: {
    labels: brows.map(b => b.browser),
    datasets: [{ data: brows.map(b => b.cnt), backgroundColor: '#6c63ff', borderRadius: 6 }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#666' }, grid: { color: gridColor } }, y: { ticks: { color: '#666' }, grid: { color: gridColor } } } }
});

// ── Real-time SSE ─────────────────────────────────────────────────
let sseStarted = false;
function startSSE() {
  sseStarted = true;
  const es = new EventSource('realtime.php');
  es.onmessage = function(e) {
    try {
      const d = JSON.parse(e.data);
      document.getElementById('liveCount').textContent = d.active;
      document.getElementById('rt-active').textContent = d.active;
      document.getElementById('rt-30min').textContent  = d.recent_30min;
      document.getElementById('rt-today').textContent  = d.today;
      document.getElementById('realtimeTs').textContent = 'Updated ' + d.ts;

      const pagesEl = document.getElementById('rt-pages');
      if (d.active_pages && d.active_pages.length) {
        pagesEl.innerHTML = d.active_pages.map(p =>
          `<div class="active-page-row"><span class="page-url" title="${escHtml(p.page_url)}">${escHtml(p.page_url)}</span><span class="page-cnt">${p.cnt} user${p.cnt>1?'s':''}</span></div>`
        ).join('');
      } else {
        pagesEl.innerHTML = '<p style="color:var(--muted);font-size:.85rem">No active users right now</p>';
      }
    } catch(err) {}
  };
}

// Also poll live count for the badge even when not on realtime tab
setInterval(function() {
  fetch('realtime.php', { headers: { 'Accept': 'text/event-stream' } })
    .then(r => r.text())
    .then(t => {
      const m = t.match(/data: ({.*})/);
      if (m) {
        const d = JSON.parse(m[1]);
        document.getElementById('liveCount').textContent = d.active;
      }
    }).catch(() => {});
}, 10000);

function escHtml(s) { return s?.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') || '' }
</script>
</body>
</html>
<?php

function countryFlag(string $code): string {
    if (strlen($code) !== 2) return '';
    $code = strtoupper($code);
    $flag = mb_convert_encoding(
        '&#' . (ord($code[0]) - 65 + 0x1F1E6) . ';' .
        '&#' . (ord($code[1]) - 65 + 0x1F1E6) . ';',
        'UTF-8', 'HTML-ENTITIES'
    );
    return $flag;
}
