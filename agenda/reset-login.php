<?php
/**
 * One-shot login reset.
 *
 * Purpose: guarantee sign-in works, by removing every variable that a
 * remote fix cannot see — hash algorithm mismatches, half-imported SQL,
 * rate-limit lockouts, orphaned accounts, whitespace in the pasted
 * password. Everything happens on YOUR server, so the hash is verified
 * with the same PHP that produced it.
 *
 * USE:
 *   1. Upload this file into /agenda/
 *   2. Open  /agenda/reset-login.php?token=fix-me-now
 *   3. Sign in at /agenda/admin.php
 *   4. Delete this file (File Manager → right-click → Delete)
 *
 * It refuses to run without the token in the URL, so a random visitor
 * cannot use it. Change the token below if you want to be paranoid.
 */

declare(strict_types=1);

const RESET_TOKEN = 'fix-me-now';

const RESET_EMAIL    = 'admin@fcrf.in';
const RESET_PASSWORD = 'FCRF@Summit2026';

if (PHP_VERSION_ID < 80100) {
    exit('Needs PHP 8.1 or newer.');
}

if (($_GET['token'] ?? '') !== RESET_TOKEN) {
    http_response_code(404);
    exit('Not found');
}

require_once __DIR__ . '/lib/auth.php';

header('Content-Type: text/html; charset=utf-8');
$out = [];
$out[] = ['label' => 'PHP version', 'value' => PHP_VERSION, 'ok' => true];

try {
    fcs_db();
    $out[] = ['label' => 'Database', 'value' => FCS_DB_NAME . ' — connected', 'ok' => true];
} catch (Throwable $ex) {
    $out[] = ['label' => 'Database', 'value' => 'FAILED — ' . $ex->getMessage(), 'ok' => false];
    render($out, null); exit;
}

// ---- what is in there right now ------------------------------------------
$roleRow = fcs_one("SELECT id FROM agenda_roles WHERE slug = 'owner'");
$out[] = ['label' => 'Owner role exists',
          'value' => $roleRow ? '#' . $roleRow['id'] : 'MISSING — reimport schema.sql',
          'ok'    => (bool)$roleRow];
if (!$roleRow) { render($out, null); exit; }

$before = fcs_one('SELECT id, email, is_active, deleted_at, locked_until,
                          LEFT(password_hash, 8) AS algo,
                          LENGTH(password_hash) AS len
                     FROM agenda_users WHERE email = ?',
                  [mb_strtolower(RESET_EMAIL)]);

$out[] = ['label' => 'Existing account',
          'value' => $before
            ? '#' . $before['id'] . '  · hash ' . $before['algo'] . '… (' . $before['len'] . ' chars)'
            : 'none — will create one',
          'ok'    => true];

$attempts = fcs_one("SELECT COUNT(*) AS n FROM agenda_login_attempts
                      WHERE successful = 0
                        AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$out[] = ['label' => 'Recent failed attempts (all)',
          'value' => (string)($attempts['n'] ?? 0),
          'ok'    => true];

// ---- fix -----------------------------------------------------------------
// Bcrypt directly — every PHP build supports it, so there is no possible
// algorithm mismatch between the hash stored here and password_verify at
// login time.
$hash = password_hash(RESET_PASSWORD, PASSWORD_BCRYPT, ['cost' => 12]);

if ($before) {
    fcs_q('UPDATE agenda_users
              SET role_id = ?, full_name = "Administrator",
                  password_hash = ?, is_active = 1, deleted_at = NULL,
                  locked_until = NULL, failed_attempts = 0,
                  twofa_enabled = 0, twofa_secret = NULL, twofa_backup = NULL
            WHERE id = ?',
          [$roleRow['id'], $hash, $before['id']]);
    $action = 'Updated account #' . $before['id'];
} else {
    fcs_q('INSERT INTO agenda_users (role_id, full_name, email, password_hash, is_active)
           VALUES (?, "Administrator", ?, ?, 1)',
          [$roleRow['id'], mb_strtolower(RESET_EMAIL), $hash]);
    $action = 'Created account #' . fcs_db()->lastInsertId();
}

// Clear EVERYTHING that could still block sign-in: rate-limit history from
// prior wrong passwords (indexed by IP as well as email), any active
// sessions belonging to the account.
fcs_q('DELETE FROM agenda_login_attempts');
fcs_q('UPDATE agenda_user_sessions SET revoked_at = NOW() WHERE revoked_at IS NULL');

$out[] = ['label' => 'Action', 'value' => $action, 'ok' => true];

// ---- prove it actually verifies ------------------------------------------
$stored = fcs_one('SELECT password_hash FROM agenda_users WHERE email = ?',
                  [mb_strtolower(RESET_EMAIL)]);
$verified = password_verify(RESET_PASSWORD, (string)$stored['password_hash']);
$out[] = ['label' => 'Self-check: password_verify() on the new hash',
          'value' => $verified ? 'MATCHES ✓' : 'MISMATCH — do not proceed',
          'ok'    => $verified];

render($out, $verified);

// -------------------------------------------------------------------------
function render(array $rows, ?bool $verified): void
{
?><!DOCTYPE html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Login reset</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
<style>
 body{margin:0;background:#060A14;color:#E8EEF7;font-family:'Plus Jakarta Sans',system-ui,sans-serif;
      padding:40px 20px;line-height:1.55;display:grid;place-items:center;min-height:100vh}
 main{width:min(560px,100%)}
 h1{font-size:24px;font-weight:800;letter-spacing:-.03em;margin:0 0 6px}
 .lede{color:#93A4C0;margin:0 0 22px;font-size:14px}
 .panel{background:#0E1626;border:1px solid #1B2740;border-radius:14px;padding:20px 22px;margin-bottom:16px}
 .row{display:flex;gap:12px;padding:8px 0;font-size:13.5px;align-items:baseline}
 .row+.row{border-top:1px solid rgba(255,255,255,.05)}
 .k{color:#93A4C0;flex:0 0 210px;font-size:12.5px}
 .v{font-family:'JetBrains Mono',monospace;font-size:12.5px;word-break:break-word}
 .ok .v{color:#4ADE80} .bad .v{color:#FF8B8B}
 .creds{background:rgba(74,222,128,.08);border:1px solid rgba(74,222,128,.32);
        color:#8FE9AE;border-radius:12px;padding:18px;margin-bottom:14px}
 .creds b{color:#E8EEF7;font-weight:700}
 .creds code{font-family:'JetBrains Mono',monospace;font-size:15px;background:rgba(0,0,0,.3);
             padding:2px 8px;border-radius:5px;display:inline-block;margin-top:2px}
 .warn{background:rgba(255,176,32,.08);border:1px solid rgba(255,176,32,.32);color:#FFC65C;
       border-radius:9px;padding:12px 14px;font-size:13px}
 a{color:#00D4C8;text-decoration:none;font-weight:700}
</style></head><body><main>
<h1>Login reset</h1>
<p class="lede">Hashes the password on this server, so there is no way the check can disagree with the stored value.</p>

<?php if ($verified): ?>
  <div class="creds">
    <div style="margin-bottom:6px">Sign in with</div>
    <div><b>Email</b><br><code><?= htmlspecialchars(RESET_EMAIL) ?></code></div>
    <div style="margin-top:8px"><b>Password</b><br><code><?= htmlspecialchars(RESET_PASSWORD) ?></code></div>
    <div style="margin-top:14px;font-size:12.5px">
      Then change the password from <b>Users</b>, and <b>delete this file</b> from File Manager.
    </div>
  </div>
  <p><a href="admin.php">Open the sign-in page →</a></p>
<?php elseif ($verified === false): ?>
  <div class="warn">
    The self-check failed. That is very unusual — copy the report below and send it to me.
  </div>
<?php endif; ?>

<div class="panel">
<?php foreach ($rows as $r): ?>
  <div class="row <?= $r['ok'] ? 'ok' : 'bad' ?>">
    <span class="k"><?= htmlspecialchars($r['label']) ?></span>
    <span class="v"><?= htmlspecialchars((string)$r['value']) ?></span>
  </div>
<?php endforeach; ?>
</div>
</main></body></html>
<?php }
