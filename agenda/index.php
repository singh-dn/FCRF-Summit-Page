<?php
/**
 * Public agenda. No login. The payload is inlined so the page paints
 * without waiting on a second round trip — this gets read on venue wifi.
 */

declare(strict_types=1);

// Fail usefully if the host is still on an older PHP: lib/ uses syntax that
// 8.0 cannot parse, which would otherwise render a blank page.
if (PHP_VERSION_ID < 80100) {
    exit('This page needs PHP 8.1 or newer. In hPanel: Advanced -> PHP Configuration -> select 8.2.');
}

require_once __DIR__ . '/lib/core.php';

fcs_boot_session();

$days   = fcs_all('SELECT id, day_number, event_date, label, subtitle FROM agenda_days WHERE is_published = 1 ORDER BY day_number');
$halls  = fcs_all('SELECT id, name, venue, floor_info, color_hex, map_note FROM agenda_halls WHERE deleted_at IS NULL ORDER BY sort_order, name');
$tracks = fcs_all('SELECT id, name, slug, color_hex FROM agenda_tracks WHERE deleted_at IS NULL ORDER BY sort_order, name');

$sessions = fcs_all(
    "SELECT id, day_id, hall_id, track_id, title, subtitle, description,
            session_type, start_time, end_time, is_featured, sort_order
       FROM agenda_sessions
      WHERE status = 'published' AND deleted_at IS NULL
      ORDER BY day_id, start_time, sort_order");

$links = fcs_all(
    "SELECT ss.session_id, ss.speaker_role, sp.id, sp.full_name, sp.honorific,
            sp.designation, sp.organisation, sp.bio, sp.photo_path, sp.linkedin_url
       FROM agenda_session_speakers ss
       JOIN agenda_speakers sp ON sp.id = ss.speaker_id
       JOIN agenda_sessions s  ON s.id = ss.session_id
      WHERE sp.status = 'published' AND sp.deleted_at IS NULL
        AND s.status = 'published'  AND s.deleted_at IS NULL
      ORDER BY ss.sort_order");

$bySession = [];
foreach ($links as $l) {
    $bySession[(int)$l['session_id']][] = [
        'id' => (int)$l['id'], 'name' => $l['full_name'], 'honorific' => $l['honorific'],
        'designation' => $l['designation'], 'organisation' => $l['organisation'],
        'bio' => $l['bio'], 'photo' => $l['photo_path'], 'linkedin' => $l['linkedin_url'],
        'role' => $l['speaker_role'],
    ];
}
foreach ($sessions as &$s) {
    $s['id'] = (int)$s['id'];
    $s['day_id'] = (int)$s['day_id'];
    $s['hall_id'] = $s['hall_id'] !== null ? (int)$s['hall_id'] : null;
    $s['speakers'] = $bySession[$s['id']] ?? [];
}
unset($s);

$directory = fcs_all(
    "SELECT id, full_name, honorific, designation, organisation, bio,
            photo_path, linkedin_url, category, is_featured
       FROM agenda_speakers
      WHERE status = 'published' AND deleted_at IS NULL
      ORDER BY is_featured DESC, sort_order, full_name");

$data = [
    'event'    => ['name' => fcs_setting('event_name', FCS_EVENT_NAME),
                   'venue' => fcs_setting('event_venue', 'Bharat Mandapam, Pragati Maidan, New Delhi')],
    'days'     => $days,
    'halls'    => $halls,
    'tracks'   => $tracks,
    'sessions' => $sessions,
    'speakers' => $directory,
];

$showAdminButton = (bool)fcs_setting('show_admin_button', FCS_SHOW_ADMIN_BUTTON_FALLBACK);
$dateRange = '';
if ($days) {
    $first = strtotime($days[0]['event_date']);
    $last  = strtotime($days[count($days) - 1]['event_date']);
    $dateRange = date('j', $first) . '–' . date('j F Y', $last);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Agenda · <?= fcs_e($data['event']['name']) ?></title>
<meta name="description" content="Full two-day session schedule and speaker directory for <?= fcs_e($data['event']['name']) ?>, <?= fcs_e($dateRange) ?>.">
<meta name="theme-color" content="#ffffff">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/agenda.css">
</head>
<body>

<div class="grain" aria-hidden="true"></div>

<header class="hero">
  <div class="wrap">
    <p class="eyebrow">
      <span class="dot" aria-hidden="true"></span>
      <?= fcs_e($dateRange) ?> · <?= fcs_e($data['event']['venue']) ?>
    </p>

    <div class="hero-top">
      <h1 class="hero-title">
        <span class="l1">FutureCrime</span>
        <span class="l2">Summit <span class="year">2026</span></span>
      </h1>
      <div class="clock" aria-live="off">
        <span id="clock-time">--:--</span>
        <span class="clock-tz">IST</span>
      </div>
    </div>

    <!-- Signature element: the page states what is happening right now. -->
    <section class="live" id="live-strip" aria-live="polite" aria-label="Current session">
      <div class="live-skeleton">Reading the schedule…</div>
    </section>
  </div>
</header>

<nav class="controls" id="controls" aria-label="Agenda filters">
  <div class="wrap controls-inner">
    <div class="days" id="day-tabs" role="tablist" aria-label="Event day"></div>
    <div class="filters">
      <div class="halls" id="hall-filters" role="group" aria-label="Filter by hall"></div>
      <div class="search">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
        <input type="search" id="search" placeholder="Search sessions, speakers, organisations" aria-label="Search the agenda" autocomplete="off">
        <button type="button" id="search-clear" class="clear" aria-label="Clear search" hidden>&times;</button>
      </div>
    </div>
  </div>
</nav>

<main class="wrap">
  <section id="timeline" class="timeline" aria-label="Session timeline"></section>
  <p class="empty" id="empty" hidden>
    <strong>No sessions match that.</strong>
    Clear the search or pick a different hall to see the full day.
  </p>

  <section class="directory" id="directory" aria-labelledby="dir-heading">
    <div class="dir-head">
      <h2 id="dir-heading">Speakers</h2>
      <p class="dir-count" id="dir-count"></p>
    </div>
    <div class="dir-grid" id="dir-grid"></div>
  </section>
</main>

<footer class="foot">
  <div class="wrap">
    <p><?= fcs_e($data['event']['name']) ?> · <?= fcs_e($data['event']['venue']) ?></p>
    <p class="foot-note">The programme is being finalised. Times and speakers may change.</p>
  </div>
</footer>

<?php if ($showAdminButton): ?>
<a href="admin.php" class="admin-dot" title="Team access" aria-label="Team access">
  <span aria-hidden="true"></span>
</a>
<?php endif; ?>

<div class="sheet" id="sheet" hidden role="dialog" aria-modal="true" aria-labelledby="sheet-title">
  <div class="sheet-backdrop" data-close></div>
  <div class="sheet-panel" role="document">
    <button class="sheet-close" data-close aria-label="Close">&times;</button>
    <div class="sheet-body" id="sheet-body"></div>
  </div>
</div>

<script>window.FCS = <?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="assets/js/agenda.js" defer></script>
</body>
</html>
