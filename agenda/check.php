<?php
/**
 * Password check — temporary. Delete once you are in.
 *
 * No database. It only looks at config.php and at what the browser sends,
 * which is all the login actually depends on now.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$typed   = $_POST['pw'] ?? null;
$defined = defined('FCS_ADMIN_PASSWORD');
$stored  = $defined ? FCS_ADMIN_PASSWORD : null;

function bytes(string $s): string
{
    $out = [];
    foreach (str_split($s) as $c) {
        $o = ord($c);
        $out[] = ($o < 32 || $o > 126) ? sprintf('[%02X]', $o) : $c;
    }
    return implode('', $out);
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Password check</title>
<style>
 body{font-family:system-ui,sans-serif;max-width:640px;margin:40px auto;padding:0 20px;
      color:#16181d;line-height:1.6}
 h1{font-size:22px;margin:0 0 4px}
 .lede{color:#6b7280;margin:0 0 24px;font-size:14px}
 .box{border:1px solid #e3e6ea;border-radius:8px;padding:16px 18px;margin-bottom:16px}
 .r{display:flex;gap:14px;padding:7px 0;font-size:14px;align-items:baseline}
 .r+.r{border-top:1px solid #f0f2f5}
 .k{color:#6b7280;flex:0 0 210px;font-size:13px}
 .v{font-family:ui-monospace,monospace;font-size:13px;word-break:break-all}
 .ok{color:#12805f}.bad{color:#b3392a}
 input{width:100%;padding:9px 11px;border:1px solid #e3e6ea;border-radius:6px;font-size:15px}
 button{margin-top:10px;padding:9px 16px;border:0;border-radius:6px;background:#16181d;
        color:#fff;font-size:14px;cursor:pointer}
 .big{padding:14px;border-radius:8px;font-weight:600;margin-bottom:16px}
 .good{background:#eefaf4;border:1px solid #bfe6d4;color:#12805f}
 .fail{background:#fdf6f5;border:1px solid #e8cdc8;color:#b3392a}
 code{background:#f6f7f9;padding:1px 5px;border-radius:3px;font-size:13px}
</style></head><body>

<h1>Password check</h1>
<p class="lede">Compares what is in config.php with what your browser sends. Delete this file afterwards.</p>

<?php if ($typed !== null): ?>
  <?php $match = $defined && hash_equals(trim((string)$stored), trim($typed)); ?>
  <div class="big <?= $match ? 'good' : 'fail' ?>">
    <?= $match ? 'MATCH — this password works. Use it on admin.php.'
               : 'NO MATCH — the two strings below are different.' ?>
  </div>
  <div class="box">
    <div class="r"><span class="k">You typed</span>
      <span class="v"><?= htmlspecialchars(bytes($typed)) ?></span></div>
    <div class="r"><span class="k">Length typed</span>
      <span class="v"><?= strlen($typed) ?> bytes<?= trim($typed) !== $typed ? ' (has spaces — trimmed anyway)' : '' ?></span></div>
    <div class="r"><span class="k">config.php holds</span>
      <span class="v"><?= $defined ? htmlspecialchars(bytes((string)$stored)) : '(not defined)' ?></span></div>
    <div class="r"><span class="k">Length in config</span>
      <span class="v"><?= $defined ? strlen((string)$stored) . ' bytes' : '—' ?></span></div>
  </div>
<?php endif; ?>

<div class="box">
  <div class="r"><span class="k">PHP version</span><span class="v"><?= PHP_VERSION ?></span></div>
  <div class="r <?= $defined ? 'ok' : 'bad' ?>">
    <span class="k">FCS_ADMIN_PASSWORD defined</span>
    <span class="v"><?= $defined ? 'yes' : 'NO — you are running an old config.php' ?></span></div>
  <div class="r"><span class="k">Password length</span>
    <span class="v"><?= $defined ? strlen((string)$stored) . ' characters' : '—' ?></span></div>
  <div class="r"><span class="k">First and last character</span>
    <span class="v"><?= $defined && strlen((string)$stored) > 1
        ? htmlspecialchars($stored[0] . str_repeat('*', max(0, strlen($stored) - 2)) . $stored[strlen($stored) - 1])
        : '—' ?></span></div>
  <div class="r"><span class="k">config.php loaded from</span>
    <span class="v"><?= htmlspecialchars(__DIR__ . '/config.php') ?></span></div>
  <div class="r"><span class="k">Modified</span>
    <span class="v"><?= date('j M Y, H:i', (int)filemtime(__DIR__ . '/config.php')) ?></span></div>
  <div class="r"><span class="k">lib/auth.php present</span>
    <span class="v"><?= is_file(__DIR__ . '/lib/auth.php') ? 'yes, modified ' . date('j M H:i', (int)filemtime(__DIR__ . '/lib/auth.php')) : 'MISSING' ?></span></div>
  <div class="r <?= function_exists('fcs_password_ok') || is_file(__DIR__ . '/lib/auth.php') ? '' : 'bad' ?>">
    <span class="k">New auth file?</span>
    <span class="v"><?= is_file(__DIR__ . '/lib/auth.php')
        && str_contains((string)file_get_contents(__DIR__ . '/lib/auth.php'), 'fcs_password_ok')
        ? 'yes — password-based' : 'NO — still the old account-based file' ?></span></div>
</div>

<form method="post">
  <label for="pw" style="font-size:13px;color:#6b7280">Type the password here (shown as plain text)</label>
  <input id="pw" name="pw" type="text" autocomplete="off" autofocus
         value="<?= htmlspecialchars((string)($typed ?? '')) ?>">
  <button type="submit">Check it</button>
</form>

<div class="box" style="margin-top:20px">
  <p style="margin:0 0 10px;font-size:14px">Second test: does the browser's request body reach the server?</p>
  <button type="button" id="probe">Test api.php</button>
  <div id="out" class="v" style="margin-top:12px"></div>
</div>

<script>
document.getElementById('probe').addEventListener('click', async () => {
  const out = document.getElementById('out');
  out.textContent = 'testing…';
  try {
    const r = await fetch('api.php?action=auth.login', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ password: document.getElementById('pw').value })
    });
    const t = await r.text();
    out.textContent = 'HTTP ' + r.status + ' → ' + t.slice(0, 300);
  } catch (e) { out.textContent = 'Request failed: ' + e.message; }
});
</script>
</body></html>
