<?php
/**
 * Admin. One page: unauthenticated visitors get the sign-in card,
 * everyone else gets the console. Panels switch client-side.
 */

declare(strict_types=1);

// Fail usefully if the host is still on an older PHP: lib/ uses syntax that
// 8.0 cannot parse, which would otherwise render a blank page.
if (PHP_VERSION_ID < 80100) {
    exit('This page needs PHP 8.1 or newer. In hPanel: Advanced -> PHP Configuration -> select 8.2.');
}

require_once __DIR__ . '/lib/auth.php';

fcs_boot_session();
fcs_touch_session();

$user = fcs_current_user();
$perms = $user ? fcs_permission_map() : [];
$pending2fa = !empty($_SESSION['pending_2fa']);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $user ? 'FutureCrime CMS' : 'Sign in' ?></title>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        ink: '#060A14', slate2: '#0E1626', steel: '#1B2740',
        signal: '#00D4C8', amber2: '#FFB020', mist: '#93A4C0', paper: '#E8EEF7',
      },
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
      },
    },
  },
};
</script>
<style>
  body { background: #060A14; color: #E8EEF7; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
  ::-webkit-scrollbar { width: 10px; height: 10px; }
  ::-webkit-scrollbar-track { background: #0E1626; }
  ::-webkit-scrollbar-thumb { background: #24334F; border-radius: 6px; }
  ::-webkit-scrollbar-thumb:hover { background: #2F4165; }
  :focus-visible { outline: 2px solid #00D4C8; outline-offset: 2px; }
  input, select, textarea {
    background: #0B1322; border: 1px solid #1B2740; border-radius: 8px;
    padding: 9px 12px; color: #E8EEF7; width: 100%; font-size: 14px;
  }
  input:focus, select:focus, textarea:focus { outline: none; border-color: #00D4C8; }
  label { font-size: 12px; color: #93A4C0; font-weight: 600; display: block; margin-bottom: 5px; }
  .btn { border-radius: 8px; padding: 9px 15px; font-size: 13.5px; font-weight: 600; cursor: pointer; transition: .15s; }
  .btn-primary { background: #00D4C8; color: #04121A; border: 0; }
  .btn-primary:hover { background: #23E6DB; }
  .btn-ghost { background: transparent; border: 1px solid #1B2740; color: #93A4C0; }
  .btn-ghost:hover { color: #E8EEF7; border-color: #2C3D5F; }
  .btn-danger { background: transparent; border: 1px solid rgba(255,110,110,.35); color: #FF8B8B; }
  .btn-danger:hover { background: rgba(255,110,110,.1); }
  .btn[disabled] { opacity: .45; cursor: not-allowed; }
  .drag-over { border-top: 2px solid #00D4C8 !important; }
  .dragging { opacity: .4; }
  [hidden] { display: none !important; }
</style>
</head>

<?php if (!$user): ?>
<!-- ============================================================ sign in -->
<body class="min-h-screen grid place-items-center p-5">
  <main class="w-full max-w-[380px]">
    <div class="mb-7">
      <div class="font-mono text-[11px] tracking-[.16em] text-signal uppercase mb-2">FutureCrime CMS</div>
      <h1 class="text-2xl font-extrabold tracking-tight">
        <?= $pending2fa ? 'Two-factor code' : 'Sign in' ?>
      </h1>
      <p class="text-mist text-sm mt-1.5">
        <?= $pending2fa
            ? 'Enter the six-digit code from your authenticator app.'
            : 'Team access to the summit agenda.' ?>
      </p>
    </div>

    <div id="login-error" class="hidden mb-4 text-[13px] rounded-lg px-3.5 py-2.5"
         style="background:rgba(255,110,110,.09); border:1px solid rgba(255,110,110,.28); color:#FF9B9B"></div>

    <form id="login-form" class="grid gap-3.5" autocomplete="on">
      <?php if ($pending2fa): ?>
        <div>
          <label for="code">Authentication code</label>
          <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                 maxlength="9" required class="font-mono tracking-[.3em] text-center text-lg">
        </div>
        <button class="btn btn-primary w-full mt-1" type="submit">Verify and continue</button>
        <p class="text-[12px] text-mist text-center mt-1">A backup code works here too.</p>
      <?php else: ?>
        <div>
          <label for="email">Email</label>
          <input id="email" name="email" type="email" autocomplete="username" required autofocus>
        </div>
        <div>
          <label for="password">Password</label>
          <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary w-full mt-1" type="submit">Sign in</button>
      <?php endif; ?>
    </form>

    <p class="text-center mt-7">
      <a href="index.php" class="text-[12.5px] text-mist hover:text-paper">← Back to the agenda</a>
    </p>
  </main>

<script>
const MODE = <?= $pending2fa ? '"twofa"' : '"login"' ?>;
document.getElementById('login-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = e.target.querySelector('button');
  const box = document.getElementById('login-error');
  btn.disabled = true; btn.textContent = 'Checking…';
  box.classList.add('hidden');

  const payload = MODE === 'twofa'
    ? { code: code.value }
    : { email: email.value, password: password.value };

  try {
    const r = await fetch('api.php?action=' + (MODE === 'twofa' ? 'auth.twofa' : 'auth.login'), {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
    });
    const d = await r.json();
    if (!r.ok || d.ok === false || d.status === 'error') throw new Error(d.error || d.message || 'Sign-in failed.');
    location.reload();
  } catch (err) {
    box.textContent = err.message;
    box.classList.remove('hidden');
    btn.disabled = false;
    btn.textContent = MODE === 'twofa' ? 'Verify and continue' : 'Sign in';
  }
});
</script>
</body>

<?php else: ?>
<!-- ============================================================= console -->
<body class="min-h-screen">
<div class="flex min-h-screen">

  <!-- sidebar -->
  <aside id="sidebar"
         class="w-[228px] shrink-0 border-r border-steel bg-slate2 flex flex-col fixed lg:static inset-y-0 left-0 z-40 -translate-x-full lg:translate-x-0 transition-transform">
    <div class="px-5 py-5 border-b border-steel">
      <div class="font-mono text-[10.5px] tracking-[.16em] text-signal uppercase">FutureCrime</div>
      <div class="text-[15px] font-bold tracking-tight mt-0.5">Agenda CMS</div>
    </div>

    <nav class="p-3 grid gap-0.5 flex-1 overflow-y-auto" id="nav">
      <?php
      $items = [
          ['dashboard', 'Dashboard', null],
          ['agenda',    'Agenda',    'agenda.view'],
          ['speakers',  'Speakers',  'speakers.view'],
          ['tracks',    'Tracks',    'tracks.manage'],
          ['venue',     'Venue',     'venue.manage'],
          ['users',     'Users',     'users.view'],
          ['history',   'History',   'history.view'],
          ['trash',     'Trash',     'history.view'],
          ['settings',  'Settings',  'settings.manage'],
      ];
      foreach ($items as [$key, $label, $perm]):
          if ($perm && empty($perms[$perm])) continue; ?>
        <button class="nav-item text-left px-3.5 py-2.5 rounded-lg text-[13.5px] font-semibold text-mist hover:text-paper hover:bg-[#111C31] transition"
                data-panel="<?= $key ?>"><?= $label ?></button>
      <?php endforeach; ?>
    </nav>

    <div class="p-3 border-t border-steel">
      <div class="px-2 pb-2.5">
        <div class="text-[13px] font-semibold truncate"><?= fcs_e($user['full_name']) ?></div>
        <div class="font-mono text-[10.5px] text-signal uppercase tracking-wider"><?= fcs_e($user['role_name']) ?></div>
      </div>
      <button id="logout" class="btn btn-ghost w-full">Sign out</button>
    </div>
  </aside>

  <div class="flex-1 min-w-0">
    <header class="sticky top-0 z-30 bg-ink/90 backdrop-blur border-b border-steel px-5 lg:px-7 py-3.5 flex items-center gap-3">
      <button id="menu" class="lg:hidden btn btn-ghost px-2.5 py-1.5">☰</button>
      <h1 id="panel-title" class="text-[17px] font-bold tracking-tight">Dashboard</h1>
      <div class="ml-auto flex items-center gap-2.5">
        <a href="index.php" target="_blank" class="btn btn-ghost hidden sm:block">View agenda ↗</a>
        <span id="saved" class="font-mono text-[11px] text-signal opacity-0 transition-opacity">Saved</span>
      </div>
    </header>

    <main class="p-5 lg:p-7 max-w-[1240px]">
      <section id="panel-dashboard" class="panel"></section>
      <section id="panel-agenda"    class="panel" hidden></section>
      <section id="panel-speakers"  class="panel" hidden></section>
      <section id="panel-tracks"    class="panel" hidden></section>
      <section id="panel-venue"     class="panel" hidden></section>
      <section id="panel-users"     class="panel" hidden></section>
      <section id="panel-history"   class="panel" hidden></section>
      <section id="panel-trash"     class="panel" hidden></section>
      <section id="panel-settings"  class="panel" hidden></section>
    </main>
  </div>
</div>

<!-- modal host -->
<div id="modal" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" data-close></div>
  <div class="relative bg-slate2 border border-steel rounded-2xl w-full max-w-[620px] max-h-[88vh] overflow-y-auto">
    <div class="sticky top-0 bg-slate2 border-b border-steel px-6 py-4 flex items-center gap-3">
      <h2 id="modal-title" class="text-[16px] font-bold tracking-tight">Edit</h2>
      <button class="ml-auto text-mist hover:text-paper text-xl leading-none" data-close aria-label="Close">&times;</button>
    </div>
    <div id="modal-body" class="p-6"></div>
  </div>
</div>

<div id="toast" class="fixed bottom-5 right-5 z-[60] grid gap-2"></div>

<script>
window.FCS_ADMIN = {
  user: <?= json_encode(['id' => (int)$user['id'], 'name' => $user['full_name'],
                         'role' => $user['role_name'], 'role_slug' => $user['role_slug']]) ?>,
  perms: <?= json_encode($perms) ?>,
  csrf: <?= json_encode(fcs_csrf_token()) ?>,
  days: <?= json_encode(fcs_all('SELECT id, day_number, event_date, label FROM agenda_days ORDER BY day_number')) ?>,
  halls: <?= json_encode(fcs_all('SELECT id, name, venue, floor_info, capacity, color_hex, map_note, sort_order FROM agenda_halls WHERE deleted_at IS NULL ORDER BY sort_order')) ?>,
  tracks: <?= json_encode(fcs_all('SELECT id, name, slug, description, color_hex, sort_order FROM agenda_tracks WHERE deleted_at IS NULL ORDER BY sort_order')) ?>,
  settings: <?= json_encode([
      'event_name' => fcs_setting('event_name', FCS_EVENT_NAME),
      'event_venue' => fcs_setting('event_venue', ''),
      'agenda_published' => (bool)fcs_setting('agenda_published', false),
      'show_admin_button' => (bool)fcs_setting('show_admin_button', true),
      'trash_retention_days' => (int)fcs_setting('trash_retention_days', 30),
  ]) ?>,
};
</script>
<script src="assets/js/admin.js" defer></script>
</body>
<?php endif; ?>
</html>
