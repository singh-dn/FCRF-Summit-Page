<?php
/**
 * Authentication: login, throttling, session registry, optional TOTP.
 */

declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Argon2id is preferred, but some shared hosts compile PHP without libargon2.
// bcrypt via PASSWORD_DEFAULT is the fallback — still a sound choice, and it
// beats a fatal error on a host we do not control.
if (!defined('FCS_PASSWORD_ALGO')) {
    define('FCS_PASSWORD_ALGO', defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
}

/** A real hash of a random string, so a missing account costs the same as a wrong password. */
function fcs_dummy_hash(): string
{
    static $h = null;
    return $h ??= fcs_hash_password(bin2hex(random_bytes(16)));
}

function fcs_hash_password(string $plain): string
{
    $opts = FCS_PASSWORD_ALGO === PASSWORD_DEFAULT
        ? ['cost' => 12]
        : ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2];
    return password_hash($plain, FCS_PASSWORD_ALGO, $opts);
}

function fcs_log_attempt(string $email, bool $ok): void
{
    fcs_q('INSERT INTO agenda_login_attempts (email, ip_address, successful, user_agent) VALUES (?,?,?,?)',
      [mb_strtolower($email), fcs_ip_bin(), (int)$ok, fcs_user_agent()]);
}

function fcs_recent_failures(string $email): int
{
    $r = fcs_one('SELECT COUNT(*) AS n FROM agenda_login_attempts
               WHERE successful = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
                 AND (email = ? OR ip_address = ?)',
             [FCS_LOGIN_WINDOW_SECONDS, mb_strtolower($email), fcs_ip_bin()]);
    return (int)($r['n'] ?? 0);
}

/**
 * Returns ['status' => 'ok'|'twofa'|'error', ...].
 * Failure messages stay deliberately vague — they must not reveal
 * whether an address is registered.
 */
function fcs_attempt_login(string $email, string $password): array
{
    $email = mb_strtolower(trim($email));

    if (fcs_recent_failures($email) >= FCS_LOGIN_MAX_ATTEMPTS) {
        return ['status' => 'error', 'message' => 'Too many attempts. Try again in 15 minutes.'];
    }

    $u = fcs_one('SELECT u.*, r.slug AS role_slug, r.level AS role_level
                FROM agenda_users u JOIN agenda_roles r ON r.id = u.role_id
               WHERE u.email = ? AND u.deleted_at IS NULL', [$email]);

    if (!$u || !password_verify($password, $u['password_hash'])) {
        fcs_log_attempt($email, false);
        // Constant-ish cost even when the account is absent.
        if (!$u) password_verify($password, fcs_dummy_hash());
        return ['status' => 'error', 'message' => 'Email or password is incorrect.'];
    }

    if (!$u['is_active']) {
        fcs_log_attempt($email, false);
        return ['status' => 'error', 'message' => 'This account is switched off. Ask an administrator.'];
    }
    if ($u['locked_until'] && strtotime($u['locked_until']) > time()) {
        fcs_log_attempt($email, false);
        return ['status' => 'error', 'message' => 'This account is temporarily locked.'];
    }

    if (password_needs_rehash($u['password_hash'], FCS_PASSWORD_ALGO)) {
        fcs_q('UPDATE agenda_users SET password_hash = ? WHERE id = ?', [fcs_hash_password($password), $u['id']]);
    }

    if ((int)$u['twofa_enabled'] === 1) {
        $_SESSION['pending_2fa'] = (int)$u['id'];
        return ['status' => 'twofa'];
    }

    fcs_establish_session((int)$u['id']);
    fcs_log_attempt($email, true);
    return ['status' => 'ok'];
}

function fcs_establish_session(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['uid']       = $userId;
    $_SESSION['issued_at'] = time();
    $_SESSION['seen_at']   = time();
    unset($_SESSION['pending_2fa']);

    fcs_q('UPDATE agenda_users SET last_login_at = NOW(), last_login_ip = ?, failed_attempts = 0 WHERE id = ?',
      [fcs_ip_bin(), $userId]);

    fcs_q('INSERT INTO agenda_user_sessions (id, user_id, ip_address, user_agent)
       VALUES (?,?,?,?)
       ON DUPLICATE KEY UPDATE last_seen_at = NOW(), revoked_at = NULL',
      [hash('sha256', session_id()), $userId, fcs_ip_bin(), fcs_user_agent()]);

    fcs_audit('login', 'user', $userId, null);
}

function fcs_touch_session(): void
{
    if (empty($_SESSION['uid'])) return;

    if (isset($_SESSION['seen_at']) && (time() - (int)$_SESSION['seen_at']) > FCS_SESSION_IDLE_TIMEOUT) {
        fcs_logout();
        return;
    }
    $_SESSION['seen_at'] = time();

    $sid = hash('sha256', session_id());
    $row = fcs_one('SELECT revoked_at FROM agenda_user_sessions WHERE id = ?', [$sid]);
    if ($row && $row['revoked_at'] !== null) { fcs_logout(); return; }
    fcs_q('UPDATE agenda_user_sessions SET last_seen_at = NOW() WHERE id = ?', [$sid]);
}

function fcs_logout(): void
{
    if (!empty($_SESSION['uid'])) {
        fcs_audit('logout', 'user', (int)$_SESSION['uid'], null);
        fcs_q('UPDATE agenda_user_sessions SET revoked_at = NOW() WHERE id = ?',
          [hash('sha256', session_id())]);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ------------------------------------------------------------------- TOTP
function fcs_base32_decode_str(string $b32): string
{
    $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(str_replace('=', '', $b32));
    $bits = '';
    for ($i = 0; $i < strlen($b32); $i++) {
        $pos = strpos($map, $b32[$i]);
        if ($pos === false) continue;
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 8) as $chunk) {
        if (strlen($chunk) === 8) $out .= chr(bindec($chunk));
    }
    return $out;
}

function fcs_totp_code(string $secret, ?int $slice = null): string
{
    $slice ??= (int)floor(time() / 30);
    $key = fcs_base32_decode_str($secret);
    $hash = hash_hmac('sha1', pack('N*', 0, $slice), $key, true);
    $offset = ord($hash[19]) & 0x0F;
    $value = ((ord($hash[$offset]) & 0x7F) << 24)
           | ((ord($hash[$offset + 1]) & 0xFF) << 16)
           | ((ord($hash[$offset + 2]) & 0xFF) << 8)
           |  (ord($hash[$offset + 3]) & 0xFF);
    return str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
}

function fcs_verify_totp(string $secret, string $code): bool
{
    $code = preg_replace('/\D/', '', $code) ?? '';
    if (strlen($code) !== 6) return false;
    $now = (int)floor(time() / 30);
    for ($drift = -1; $drift <= 1; $drift++) {          // ±30s clock tolerance
        if (hash_equals(fcs_totp_code($secret, $now + $drift), $code)) return true;
    }
    return false;
}

function fcs_totp_secret(): string
{
    $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $out = '';
    for ($i = 0; $i < 32; $i++) $out .= $map[random_int(0, 31)];
    return $out;
}

function fcs_complete_2fa(string $code): array
{
    $uid = $_SESSION['pending_2fa'] ?? null;
    if (!$uid) return ['status' => 'error', 'message' => 'Start again from the sign-in screen.'];

    $u = fcs_one('SELECT id, email, twofa_secret, twofa_backup FROM agenda_users WHERE id = ?', [$uid]);
    if (!$u) return ['status' => 'error', 'message' => 'Start again from the sign-in screen.'];

    if (fcs_verify_totp((string)$u['twofa_secret'], $code)) {
        fcs_establish_session((int)$u['id']);
        fcs_log_attempt($u['email'], true);
        return ['status' => 'ok'];
    }

    // Backup codes are stored hashed and burn on use.
    $codes = json_decode((string)$u['twofa_backup'], true) ?: [];
    foreach ($codes as $i => $h) {
        if (password_verify($code, $h)) {
            unset($codes[$i]);
            fcs_q('UPDATE agenda_users SET twofa_backup = ? WHERE id = ?',
              [json_encode(array_values($codes)), $u['id']]);
            fcs_establish_session((int)$u['id']);
            fcs_log_attempt($u['email'], true);
            return ['status' => 'ok', 'backup_used' => true];
        }
    }

    fcs_log_attempt($u['email'], false);
    return ['status' => 'error', 'message' => 'That code is not valid.'];
}
