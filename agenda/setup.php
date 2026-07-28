<?php
/**
 * One-time setup, for hosts without SSH (Hostinger's lower plans included).
 *
 * It refuses to do anything once an account exists, so leaving it around
 * briefly is not a disaster — but delete it as soon as you are signed in.
 * There is no self-signup anywhere else in this application.
 */

declare(strict_types=1);

// Fail usefully if the host is still on an older PHP: lib/ uses syntax that
// 8.0 cannot parse, which would otherwise render a blank page.
if (PHP_VERSION_ID < 80100) {
    exit('The agenda module needs PHP 8.1 or newer. In hPanel: Advanced -> PHP Configuration -> select 8.2.');
}

require_once __DIR__ . '/lib/auth.php';

fcs_boot_session();

// ------------------------------------------------------------- diagnostics
$checks = [];
$fatal = false;

function fcs_check(string $label, bool $ok, string $fix = '', bool $blocking = true): void
{
    global $checks, $fatal;
    $checks[] = ['label' => $label, 'ok' => $ok, 'fix' => $fix, 'blocking' => $blocking];
    if (!$ok && $blocking) $fatal = true;
}

fcs_check('PHP ' . PHP_VERSION, true);
fcs_check('PDO MySQL driver', extension_loaded('pdo_mysql'),
          'Enable pdo_mysql under Advanced → PHP Configuration → PHP extensions.');
fcs_check('GD image library', extension_loaded('gd'),
          'Needed for speaker photo uploads. Enable the gd extension.', false);
fcs_check('Argon2id password hashing', defined('PASSWORD_ARGON2ID'),
          'Not available here, so bcrypt is used instead. That is fine.', false);

$dbOk = false;
try {
    fcs_db();
    $dbOk = true;
    fcs_check('Database connection (' . FCS_DB_NAME . ')', true);
} catch (Throwable $ex) {
    fcs_check('Database connection', false,
        'Open config.php and set FCS_DB_NAME, FCS_DB_USER and FCS_DB_PASS. On Hostinger the '
      . 'database name and user both start with your account number, like u123456789_summit. '
      . 'Server error: ' . htmlspecialchars($ex->getMessage()));
}

$tablesOk = false;
if ($dbOk) {
    $need = ['agenda_roles', 'agenda_permissions', 'agenda_users', 'agenda_sessions', 'agenda_speakers'];
    $missing = [];
    foreach ($need as $t) {
        if (!fcs_one("SHOW TABLES LIKE '$t'")) $missing[] = $t;
    }
    $tablesOk = !$missing;
    fcs_check('Tables installed', $tablesOk,
        $missing ? 'Missing: ' . implode(', ', $missing)
                 . '. Import sql/schema.sql through phpMyAdmin, then sql/seed.sql.' : '');
}

$uploadOk = is_dir(FCS_UPLOAD_DIR) ? is_writable(FCS_UPLOAD_DIR) : @mkdir(FCS_UPLOAD_DIR, 0755, true);
fcs_check('Uploads folder is writable', (bool)$uploadOk,
          'Set uploads/speakers to permission 755 in the File Manager.', false);

$hasOwner = false;
if ($tablesOk) {
    $hasOwner = (bool)fcs_one('SELECT id FROM agenda_users WHERE deleted_at IS NULL LIMIT 1');
}

// ------------------------------------------------------------------ action
$error = null;
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$fatal && !$hasOwner) {
    $name  = trim((string)($_POST['full_name'] ?? ''));
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $pass  = (string)($_POST['password'] ?? '');
    $again = (string)($_POST['confirm'] ?? '');

    if ($name === '')                                    $error = 'Enter your name.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))  $error = 'That email address is not valid.';
    elseif (strlen($pass) < 10)                          $error = 'Use a password of at least 10 characters.';
    elseif ($pass !== $again)                            $error = 'The two passwords do not match.';
    else {
        $role = fcs_one("SELECT id FROM agenda_roles WHERE slug = 'owner'");
        if (!$role) {
            $error = 'The roles table is empty — import sql/schema.sql first.';
        } else {
            fcs_q('INSERT INTO agenda_users (role_id, full_name, email, password_hash, is_active)
                   VALUES (?,?,?,?,1)',
                  [$role['id'], $name, $email, fcs_hash_password($pass)]);
            $id = (int)fcs_db()->lastInsertId();
            fcs_q('INSERT INTO agenda_audit_logs (user_id, user_name, action, entity_type, entity_id, entity_label)
                   VALUES (?,?,?,?,?,?)',
                  [$id, $name, 'create', 'user', $id, $name . ' (first owner)']);
            $done = true;
            $hasOwner = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Setup · FutureCrime agenda</title>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after { box-sizing: border-box; }
  body { margin:0; background:#060A14; color:#E8EEF7; font-family:'Plus Jakarta Sans',system-ui,sans-serif;
         display:grid; place-items:center; min-height:100vh; padding:34px 18px; line-height:1.55; }
  main { width:min(520px,100%); }
  .eyebrow { font-family:'JetBrains Mono',monospace; font-size:11px; letter-spacing:.16em;
             text-transform:uppercase; color:#00D4C8; margin:0 0 7px; }
  h1 { font-size:26px; font-weight:800; letter-spacing:-.03em; margin:0 0 6px; }
  .lede { color:#93A4C0; font-size:14px; margin:0 0 26px; }
  .panel { background:#0E1626; border:1px solid #1B2740; border-radius:14px; padding:20px 22px; margin-bottom:18px; }
  .panel h2 { font-family:'JetBrains Mono',monospace; font-size:10.5px; letter-spacing:.14em;
              text-transform:uppercase; color:#93A4C0; margin:0 0 13px; font-weight:700; }
  .row { display:flex; gap:11px; align-items:flex-start; padding:7px 0; font-size:13.5px; }
  .row + .row { border-top:1px solid rgba(255,255,255,.05); }
  .mark { font-family:'JetBrains Mono',monospace; font-size:12px; width:16px; flex:none; padding-top:1px; }
  .ok { color:#4ADE80; } .warn { color:#FFB020; } .bad { color:#FF8B8B; }
  .fix { color:#93A4C0; font-size:12.5px; margin-top:3px; }
  label { display:block; font-size:12px; color:#93A4C0; font-weight:600; margin:0 0 5px; }
  input { width:100%; background:#0B1322; border:1px solid #1B2740; border-radius:8px;
          padding:10px 12px; color:#E8EEF7; font-size:14px; font-family:inherit; }
  input:focus { outline:none; border-color:#00D4C8; }
  .field { margin-bottom:14px; }
  button { width:100%; background:#00D4C8; color:#04121A; border:0; border-radius:8px;
           padding:11px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; }
  button:hover { background:#23E6DB; }
  .err { background:rgba(255,110,110,.09); border:1px solid rgba(255,110,110,.3); color:#FF9B9B;
         border-radius:9px; padding:11px 13px; font-size:13px; margin-bottom:16px; }
  .good { background:rgba(74,222,128,.08); border:1px solid rgba(74,222,128,.3); color:#8FE9AE;
          border-radius:9px; padding:14px; font-size:13.5px; margin-bottom:18px; }
  code { font-family:'JetBrains Mono',monospace; font-size:12.5px; background:rgba(255,255,255,.06);
         padding:1px 6px; border-radius:4px; }
  a { color:#00D4C8; }
</style>
</head>
<body>
<main>
  <p class="eyebrow">FutureCrime agenda</p>
  <h1><?= $hasOwner && !$done ? 'Already set up' : 'Setup' ?></h1>
  <p class="lede">
    <?= $hasOwner && !$done
        ? 'An account already exists, so this page will not create another one.'
        : 'One page, once. It checks the hosting environment, then creates your Owner account.' ?>
  </p>

  <div class="panel">
    <h2>Environment</h2>
    <?php foreach ($checks as $c): ?>
      <div class="row">
        <span class="mark <?= $c['ok'] ? 'ok' : ($c['blocking'] ? 'bad' : 'warn') ?>">
          <?= $c['ok'] ? '✓' : ($c['blocking'] ? '✕' : '!') ?>
        </span>
        <span>
          <?= htmlspecialchars($c['label']) ?>
          <?php if (!$c['ok'] && $c['fix']): ?><div class="fix"><?= $c['fix'] ?></div><?php endif; ?>
        </span>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($done): ?>
    <div class="good">
      <b>Account created.</b><br>
      Delete <code>setup.php</code> now — in hPanel, File Manager, right-click the file.
      Then sign in.
    </div>
    <a href="admin.php"><button type="button">Go to the admin console</button></a>

  <?php elseif ($fatal): ?>
    <div class="err">Fix the items marked ✕ above, then reload this page.</div>

  <?php elseif ($hasOwner): ?>
    <div class="err">
      Delete <code>setup.php</code> from the server. Use
      <a href="admin.php">the admin console</a> to add more people.
    </div>

  <?php else: ?>
    <div class="panel">
      <h2>Owner account</h2>
      <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post" autocomplete="off">
        <div class="field">
          <label for="full_name">Your name</label>
          <input id="full_name" name="full_name" required
                 value="<?= htmlspecialchars((string)($_POST['full_name'] ?? '')) ?>">
        </div>
        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required
                 value="<?= htmlspecialchars((string)($_POST['email'] ?? '')) ?>">
        </div>
        <div class="field">
          <label for="password">Password — at least 10 characters</label>
          <input id="password" name="password" type="password" required minlength="10">
        </div>
        <div class="field">
          <label for="confirm">Confirm password</label>
          <input id="confirm" name="confirm" type="password" required minlength="10">
        </div>
        <button type="submit">Create the Owner account</button>
      </form>
    </div>
    <p class="lede" style="font-size:12.5px">
      This account can do everything, including appointing another Owner.
      Add the rest of the team from the console afterwards.
    </p>
  <?php endif; ?>
</main>
</body>
</html>
