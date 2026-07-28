<?php
/**
 * Admin. One page: password prompt, then the console.
 */

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

fcs_boot_session();

$in = fcs_is_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $in ? 'FutureCrime CMS' : 'Sign in' ?></title>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        paper: '#ffffff', mist: '#f6f7f9', rule: '#e3e6ea',
        ink: '#16181d', body: '#454b55', soft: '#6b7280',
        signal: '#0f9d94', amber2: '#b26a00', danger: '#b3392a',
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
  body { background:#fff; color:#454b55; font-family:'Plus Jakarta Sans',system-ui,sans-serif; }
  ::-webkit-scrollbar { width:10px; height:10px; }
  ::-webkit-scrollbar-track { background:#f6f7f9; }
  ::-webkit-scrollbar-thumb { background:#d3d8de; border-radius:6px; }
  ::-webkit-scrollbar-thumb:hover { background:#bcc3cb; }
  :focus-visible { outline:2px solid #0f9d94; outline-offset:2px; }
  input, select, textarea {
    background:#fff; border:1px solid #e3e6ea; border-radius:6px;
    padding:9px 12px; color:#16181d; width:100%; font-size:14px; font-family:inherit;
  }
  input:focus, select:focus, textarea:focus { outline:none; border-color:#0f9d94; }
  label { font-size:12px; color:#6b7280; font-weight:600; display:block; margin-bottom:5px; }
  .btn { border-radius:6px; padding:9px 15px; font-size:13.5px; font-weight:600; cursor:pointer; transition:.15s; }
  .btn-primary { background:#16181d; color:#fff; border:0; }
  .btn-primary:hover { background:#2c313a; }
  .btn-ghost { background:#fff; border:1px solid #e3e6ea; color:#454b55; }
  .btn-ghost:hover { border-color:#16181d; color:#16181d; }
  .btn-danger { background:#fff; border:1px solid #e8cdc8; color:#b3392a; }
  .btn-danger:hover { background:#fdf6f5; }
  .btn[disabled] { opacity:.45; cursor:not-allowed; }
  .drag-over { border-top:2px solid #0f9d94 !important; }
  .dragging { opacity:.4; }
  [hidden] { display:none !important; }
</style>
</head>

<?php if (!$in): ?>
<!-- ============================================================ sign in -->
<body class="min-h-screen grid place-items-center p-5">
  <main class="w-full max-w-[360px]">
    <div class="mb-7">
      <div class="font-mono text-[11px] tracking-[.16em] text-signal uppercase mb-2">FutureCrime CMS</div>
      <h1 class="text-2xl font-extrabold tracking-tight text-ink">Sign in</h1>
      <p class="text-soft text-sm mt-1.5">Enter the shared password.</p>
    </div>

    <div id="login-error" class="hidden mb-4 text-[13px] rounded-lg px-3.5 py-2.5"
         style="background:#fdf6f5;border:1px solid #e8cdc8;color:#b3392a"></div>

    <form id="login-form" class="grid gap-3.5" autocomplete="off">
      <div>
        <label for="password">Password</label>
        <!-- name/id avoid the browser refilling an old saved password -->
        <input id="password" name="fcs-access-code" type="password"
               autocomplete="new-password" required autofocus>
      </div>
      <button class="btn btn-primary w-full mt-1" type="submit">Sign in</button>
    </form>

    <p class="text-center mt-7">
      <a href="index.php" class="text-[12.5px] text-soft hover:text-ink">&larr; Back to the agenda</a>
    </p>
  </main>

<script>
document.getElementById('login-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = e.target.querySelector('button');
  const box = document.getElementById('login-error');
  btn.disabled = true; btn.textContent = 'Checking…';
  box.classList.add('hidden');
  try {
    const r = await fetch('api.php?action=auth.login', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ password: document.getElementById('password').value }),
    });
    const d = await r.json();
    if (!d.ok) throw new Error(d.error || 'Sign-in failed.');
    location.reload();
  } catch (err) {
    box.textContent = err.message;
    box.classList.remove('hidden');
    btn.disabled = false; btn.textContent = 'Sign in';
  }
});
</script>
</body>

<?php else: ?>
<!-- ============================================================= console -->
<body class="min-h-screen">
<div class="flex min-h-screen">

  <aside id="sidebar"
         class="w-[220px] shrink-0 border-r border-rule bg-mist flex flex-col fixed lg:static inset-y-0 left-0 z-40 -translate-x-full lg:translate-x-0 transition-transform">
    <div class="px-5 py-5 border-b border-rule">
      <div class="font-mono text-[10.5px] tracking-[.16em] text-signal uppercase">FutureCrime</div>
      <div class="text-[15px] font-bold tracking-tight mt-0.5 text-ink">Agenda CMS</div>
    </div>

    <nav class="p-3 grid gap-0.5 flex-1 overflow-y-auto" id="nav">
      <?php foreach ([
          ['dashboard', 'Dashboard'], ['agenda', 'Agenda'], ['speakers', 'Speakers'],
          ['tracks', 'Tracks'], ['venue', 'Venue'], ['history', 'History'],
          ['trash', 'Trash'], ['settings', 'Settings'],
      ] as [$key, $label]): ?>
        <button class="nav-item text-left px-3.5 py-2.5 rounded-lg text-[13.5px] font-semibold text-soft hover:text-ink hover:bg-white transition"
                data-panel="<?= $key ?>"><?= $label ?></button>
      <?php endforeach; ?>
    </nav>

    <div class="p-3 border-t border-rule">
      <button id="logout" class="btn btn-ghost w-full">Sign out</button>
    </div>
  </aside>

  <div class="flex-1 min-w-0">
    <header class="sticky top-0 z-30 bg-white/92 backdrop-blur border-b border-rule px-5 lg:px-7 py-3.5 flex items-center gap-3">
      <button id="menu" class="lg:hidden btn btn-ghost px-2.5 py-1.5">&#9776;</button>
      <h1 id="panel-title" class="text-[17px] font-bold tracking-tight text-ink">Dashboard</h1>
      <div class="ml-auto flex items-center gap-2.5">
        <a href="index.php" target="_blank" class="btn btn-ghost hidden sm:block">View agenda &#8599;</a>
        <span id="saved" class="font-mono text-[11px] text-signal opacity-0 transition-opacity">Saved</span>
      </div>
    </header>

    <main class="p-5 lg:p-7 max-w-[1240px]">
      <section id="panel-dashboard" class="panel"></section>
      <section id="panel-agenda"    class="panel" hidden></section>
      <section id="panel-speakers"  class="panel" hidden></section>
      <section id="panel-tracks"    class="panel" hidden></section>
      <section id="panel-venue"     class="panel" hidden></section>
      <section id="panel-history"   class="panel" hidden></section>
      <section id="panel-trash"     class="panel" hidden></section>
      <section id="panel-settings"  class="panel" hidden></section>
    </main>
  </div>
</div>

<div id="modal" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="absolute inset-0" style="background:rgba(22,24,29,.45)" data-close></div>
  <div class="relative bg-white border border-rule rounded-xl w-full max-w-[620px] max-h-[88vh] overflow-y-auto shadow-xl">
    <div class="sticky top-0 bg-white border-b border-rule px-6 py-4 flex items-center gap-3">
      <h2 id="modal-title" class="text-[16px] font-bold tracking-tight text-ink">Edit</h2>
      <button class="ml-auto text-soft hover:text-ink text-xl leading-none" data-close aria-label="Close">&times;</button>
    </div>
    <div id="modal-body" class="p-6"></div>
  </div>
</div>

<div id="toast" class="fixed bottom-5 right-5 z-[60] grid gap-2"></div>

<script>
window.FCS_ADMIN = {
  csrf: <?= json_encode(fcs_csrf_token()) ?>,
  perms: <?= json_encode(fcs_permission_map()) ?>,
  days: <?= json_encode(fcs_all('SELECT id, day_number, event_date, label FROM agenda_days ORDER BY day_number')) ?>,
  halls: <?= json_encode(fcs_all('SELECT id, name, venue, floor_info, capacity, color_hex, map_note, sort_order FROM agenda_halls WHERE deleted_at IS NULL ORDER BY sort_order')) ?>,
  tracks: <?= json_encode(fcs_all('SELECT id, name, slug, description, color_hex, sort_order FROM agenda_tracks WHERE deleted_at IS NULL ORDER BY sort_order')) ?>,
  settings: <?= json_encode([
      'event_name' => fcs_setting('event_name', FCS_EVENT_NAME),
      'event_venue' => fcs_setting('event_venue', ''),
      'show_admin_button' => (bool)fcs_setting('show_admin_button', true),
      'trash_retention_days' => (int)fcs_setting('trash_retention_days', 30),
  ]) ?>,
};
</script>
<script src="assets/js/admin.js" defer></script>
</body>
<?php endif; ?>
</html>
