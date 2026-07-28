<?php
/**
 * TEMPORARY diagnostic. Delete it the moment you are back in.
 *
 * It is disabled until you flip the flag below, because it can reset a
 * password without being logged in. That is exactly what you need right now
 * and exactly what nobody else should ever be able to reach.
 *
 *   1. Edit the next line to true
 *   2. Open /agenda/diagnose.php
 *   3. Fix whatever it reports
 *   4. DELETE THIS FILE
 */

declare(strict_types=1);

const FCS_DIAG_ENABLED = true;   // <-- change to true to use, then delete the file

if (PHP_VERSION_ID < 80100) {
    exit('Needs PHP 8.1+. hPanel: Advanced -> PHP Configuration -> 8.2');
}

if (!FCS_DIAG_ENABLED) {
    http_response_code(404);
    exit('Not found.');
}

require_once __DIR__ . '/lib/auth.php';

$email  = trim((string)($_POST['email'] ?? ''));
$pass   = (string)($_POST['password'] ?? '');      // deliberately not trimmed
$action = (string)($_POST['action'] ?? '');
$report = [];
$notice = null;
$error  = null;

function fcs_row(string $k, string $v, string $state = ''): array
{
    return ['k' => $k, 'v' => $v, 's' => $state];
}

// ------------------------------------------------------------ environment
$report[] = fcs_row('PHP version', PHP_VERSION, PHP_VERSION_ID >= 80100 ? 'ok' : 'bad');
$report[] = fcs_row('Hashing algorithm in use',
    FCS_PASSWORD_ALGO === PASSWORD_DEFAULT ? 'bcrypt (PASSWORD_DEFAULT)' : 'argon2id', 'ok');

try {
    fcs_db();
    $report[] = fcs_row('Database', FCS_DB_NAME . ' — connected', 'ok');
} catch (Throwable $ex) {
    $report[] = fcs_row('Database', 'FAILED: ' . $ex->getMessage(), 'bad');
    $error = 'Fix the database connection in config.php first.';
}

// --------------------------------------------------------------- accounts
if (!$error) {
    $users = fcs_all('SELECT u.id, u.full_name, u.email, u.is_active, u.deleted_at,
                             u.locked_until, u.twofa_enabled, u.password_hash,
                             r.name AS role_name
                        FROM agenda_users u
                        JOIN agenda_roles r ON r.id = u.role_id
                       ORDER BY u.id');
    $report[] = fcs_row('Accounts in agenda_users', (string)count($users),
                        count($users) ? 'ok' : 'bad');
}

// ----------------------------------------------------------------- action
if (!$error && $action === 'test' && $email !== '') {
    $key = mb_strtolower($email);
    $u = fcs_one('SELECT * FROM agenda_users WHERE email = ?', [$key]);

    if (!$u) {
        $exact = fcs_one('SELECT email FROM agenda_users WHERE LOWER(TRIM(email)) = ?', [$key]);
        $report[] = fcs_row('Account lookup',
            $exact ? 'Stored email has stray whitespace or capitals: "' . $exact['email'] . '"'
                   : 'No account with that email address',
            'bad');
    } else {
        $h = (string)$u['password_hash'];
        $info = password_get_info($h);
        $verify = $pass !== '' ? password_verify($pass, $h) : null;

        $report[] = fcs_row('Account found', '#' . $u['id'] . ' — ' . $u['full_name'], 'ok');
        $report[] = fcs_row('Active', $u['is_active'] ? 'yes' : 'NO — this blocks login',
                            $u['is_active'] ? 'ok' : 'bad');
        $report[] = fcs_row('Deleted', $u['deleted_at'] ? 'YES — ' . $u['deleted_at'] : 'no',
                            $u['deleted_at'] ? 'bad' : 'ok');
        $report[] = fcs_row('Locked until', $u['locked_until'] ?: 'not locked',
                            $u['locked_until'] && strtotime((string)$u['locked_until']) > time() ? 'bad' : 'ok');
        $report[] = fcs_row('2FA', $u['twofa_enabled'] ? 'enabled' : 'off', 'ok');
        $report[] = fcs_row('Stored hash', mb_substr($h, 0, 12) . '…  (' . strlen($h) . ' chars)',
                            strlen($h) > 20 ? 'ok' : 'bad');
        $report[] = fcs_row('Hash algorithm', (string)($info['algoName'] ?? 'unknown'),
                            ($info['algoName'] ?? '') !== 'unknown' ? 'ok' : 'bad');

        if ($verify !== null) {
            $report[] = fcs_row('password_verify()', $verify ? 'MATCHES' : 'does not match',
                                $verify ? 'ok' : 'bad');
            if (!$verify) {
                $trimmed = password_verify(trim($pass), $h);
                if ($trimmed) {
                    $report[] = fcs_row('With whitespace trimmed', 'MATCHES — the stored password '
                        . 'has a leading or trailing space', 'warn');
                }
            }
        }

        $fails = fcs_all('SELECT successful, attempted_at, INET6_NTOA(ip_address) AS ip
                            FROM agenda_login_attempts
                           WHERE email = ? ORDER BY id DESC LIMIT 6', [$key]);
        $recent = fcs_recent_failures($key);
        $report[] = fcs_row('Recent failures (15 min)', $recent . ' of ' . FCS_LOGIN_MAX_ATTEMPTS,
                            $recent >= FCS_LOGIN_MAX_ATTEMPTS ? 'bad' : 'ok');
        foreach ($fails as $f) {
            $report[] = fcs_row('  attempt ' . $f['attempted_at'],
                ($f['successful'] ? 'success' : 'failed') . ' from ' . ($f['ip'] ?? '?'), '');
        }
    }
}

if (!$error && $action === 'reset' && $email !== '') {
    if (strlen($pass) < 10) {
        $error = 'Use at least 10 characters.';
    } else {
        $key = mb_strtolower(trim($email));
        $u = fcs_one('SELECT id FROM agenda_users WHERE LOWER(TRIM(email)) = ?', [$key]);
        if (!$u) {
            $error = 'No account with that email address.';
        } else {
            fcs_q('UPDATE agenda_users
                      SET email = ?, password_hash = ?, is_active = 1,
                          locked_until = NULL, failed_attempts = 0, deleted_at = NULL
                    WHERE id = ?',
                  [$key, fcs_hash_password($pass), $u['id']]);
            fcs_q('DELETE FROM agenda_login_attempts WHERE email = ?', [$key]);
            $notice = 'Password reset and the account unlocked. Sign in at admin.php — '
                    . 'then delete this file.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Diagnose · agenda</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
 *,*::before,*::after{box-sizing:border-box}
 body{margin:0;background:#060A14;color:#E8EEF7;font-family:'Plus Jakarta Sans',system-ui,sans-serif;
      padding:34px 18px;line-height:1.55}
 main{width:min(680px,100%);margin:0 auto}
 h1{font-size:24px;font-weight:800;letter-spacing:-.03em;margin:0 0 4px}
 .lede{color:#93A4C0;font-size:14px;margin:0 0 22px}
 .panel{background:#0E1626;border:1px solid #1B2740;border-radius:14px;padding:18px 20px;margin-bottom:16px}
 .panel h2{font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.14em;
           text-transform:uppercase;color:#93A4C0;margin:0 0 12px;font-weight:700}
 .r{display:flex;gap:12px;padding:6px 0;font-size:13.5px;align-items:baseline}
 .r+.r{border-top:1px solid rgba(255,255,255,.05)}
 .r .k{color:#93A4C0;flex:0 0 190px;font-size:12.5px}
 .r .v{font-family:'JetBrains Mono',monospace;font-size:12.5px;word-break:break-word}
 .ok .v{color:#4ADE80}.bad .v{color:#FF8B8B}.warn .v{color:#FFB020}
 label{display:block;font-size:12px;color:#93A4C0;font-weight:600;margin:0 0 5px}
 input{width:100%;background:#0B1322;border:1px solid #1B2740;border-radius:8px;padding:10px 12px;
       color:#E8EEF7;font-size:14px;font-family:inherit;margin-bottom:13px}
 input:focus{outline:none;border-color:#00D4C8}
 button{border:0;border-radius:8px;padding:10px 16px;font-size:13.5px;font-weight:700;
        cursor:pointer;font-family:inherit;background:#00D4C8;color:#04121A}
 button.alt{background:transparent;border:1px solid #1B2740;color:#93A4C0}
 .msg{border-radius:9px;padding:12px 14px;font-size:13.5px;margin-bottom:16px}
 .good{background:rgba(74,222,128,.08);border:1px solid rgba(74,222,128,.3);color:#8FE9AE}
 .err{background:rgba(255,110,110,.09);border:1px solid rgba(255,110,110,.3);color:#FF9B9B}
 .danger{background:rgba(255,176,32,.08);border:1px solid rgba(255,176,32,.35);color:#FFC65C}
 code{font-family:'JetBrains Mono',monospace;font-size:12.5px;background:rgba(255,255,255,.06);
      padding:1px 6px;border-radius:4px}
 a{color:#00D4C8}
</style></head><body><main>

<h1>Login diagnostics</h1>
<p class="lede">Finds the exact reason a sign-in is refused, and can reset the password.</p>

<div class="msg danger"><b>Delete this file when you are done.</b>
It can change a password without anyone being signed in.</div>

<?php if ($notice): ?><div class="msg good"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="panel">
  <h2>Report</h2>
  <?php foreach ($report as $r): ?>
    <div class="r <?= $r['s'] ?>"><span class="k"><?= htmlspecialchars($r['k']) ?></span><span class="v"><?= htmlspecialchars($r['v']) ?></span></div>
  <?php endforeach; ?>
</div>

<?php if (!empty($users)): ?>
<div class="panel">
  <h2>Accounts</h2>
  <?php foreach ($users as $u): ?>
    <div class="r"><span class="k"><?= htmlspecialchars($u['role_name']) ?></span>
      <span class="v"><?= htmlspecialchars($u['email']) ?><?= $u['is_active'] ? '' : ' · INACTIVE' ?><?= $u['deleted_at'] ? ' · DELETED' : '' ?></span></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="panel">
  <h2>Test a sign-in</h2>
  <form method="post">
    <label for="e1">Email</label>
    <input id="e1" name="email" value="<?= htmlspecialchars($email) ?>" required>
    <label for="p1">Password to test</label>
    <input id="p1" name="password" type="text" value="<?= htmlspecialchars($pass) ?>">
    <p class="lede" style="font-size:12px;margin:-6px 0 12px">
      Shown as plain text on purpose — a trailing space is invisible in a password field
      and is a common cause of this exact problem.</p>
    <button type="submit" name="action" value="test">Run the test</button>
  </form>
</div>

<div class="panel">
  <h2>Reset the password</h2>
  <form method="post">
    <label for="e2">Email</label>
    <input id="e2" name="email" value="<?= htmlspecialchars($email) ?>" required>
    <label for="p2">New password — at least 10 characters</label>
    <input id="p2" name="password" type="text" required minlength="10">
    <button type="submit" name="action" value="reset">Reset and unlock</button>
  </form>
</div>

<div class="panel">
  <h2>API response check</h2>
  <p class="lede" style="font-size:12.5px;margin:0 0 12px">
    Confirms the endpoint returns clean JSON. If anything is echoed before it —
    a debug dump, a PHP notice, a blank line after <code>?&gt;</code> in an edited
    file — it shows up here.</p>
  <button class="alt" type="button" id="probe">Check api.php</button>
  <div id="probe-out" class="r" style="margin-top:12px"></div>
</div>

<p><a href="admin.php">Go to the sign-in page →</a></p>

<script>
document.getElementById('probe').addEventListener('click', async () => {
  const out = document.getElementById('probe-out');
  out.innerHTML = '<span class="v">checking…</span>';
  try {
    const r = await fetch('api.php?action=auth.state&debug=1', { headers: { 'Accept': 'application/json' } });
    const text = await r.text();
    let parsed = null;
    try { parsed = JSON.parse(text); } catch (_) {}
    if (parsed) {
      out.className = 'r ok';
      out.innerHTML = '<span class="k">Result</span><span class="v">Clean JSON. HTTP ' + r.status +
        ', authenticated: ' + parsed.authenticated + '</span>';
    } else {
      out.className = 'r bad';
      out.innerHTML = '<span class="k">Result</span><span class="v">Not JSON. First 200 characters:<br>' +
        text.slice(0, 200).replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c])) + '</span>';
    }
  } catch (err) {
    out.className = 'r bad';
    out.innerHTML = '<span class="k">Result</span><span class="v">' + err.message + '</span>';
  }
});
</script>
</main></body></html>
