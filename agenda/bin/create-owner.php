<?php
/**
 * Creates the first Owner account. Run once from the CLI, then delete
 * this file — there is deliberately no self-signup anywhere in the app.
 *
 *   php bin/create-owner.php "Dev Singh" dev@fcrf.in
 *
 * The password is asked for interactively so it never lands in
 * your shell history or the process list.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../lib/auth.php';

$name  = $argv[1] ?? null;
$email = $argv[2] ?? null;

if (!$name || !$email) {
    fwrite(STDERR, "Usage: php bin/create-owner.php \"Full Name\" email@example.com\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "That email address is not valid.\n");
    exit(1);
}

echo "Password (min 10 chars, not echoed): ";
shell_exec('stty -echo 2>/dev/null');
$pass = trim((string)fgets(STDIN));
echo "\nConfirm: ";
$again = trim((string)fgets(STDIN));
shell_exec('stty echo 2>/dev/null');
echo "\n";

if (strlen($pass) < 10)  { fwrite(STDERR, "Too short — use at least 10 characters.\n"); exit(1); }
if ($pass !== $again)    { fwrite(STDERR, "Those did not match.\n"); exit(1); }

$owner = fcs_one("SELECT id FROM agenda_roles WHERE slug = 'owner'");
if (!$owner) {
    fwrite(STDERR, "No 'owner' role found. Run agenda_schema.sql first.\n");
    exit(1);
}

if (fcs_one('SELECT id FROM agenda_users WHERE email = ?', [mb_strtolower($email)])) {
    fwrite(STDERR, "An account already uses that email address.\n");
    exit(1);
}

fcs_q('INSERT INTO agenda_users (role_id, full_name, email, password_hash, is_active)
   VALUES (?,?,?,?,1)',
  [$owner['id'], $name, mb_strtolower($email), fcs_hash_password($pass)]);

$id = fcs_db()->lastInsertId();
fcs_q('INSERT INTO agenda_audit_logs (user_id, user_name, action, entity_type, entity_id, entity_label)
   VALUES (?,?,?,?,?,?)',
  [$id, $name, 'create', 'user', $id, $name . ' (owner bootstrap)']);

echo "Owner account created for {$email}.\n";
echo "Sign in at admin.php, then delete bin/create-owner.php.\n";
