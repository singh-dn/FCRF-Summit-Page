<?php
/**
 * Authentication — deliberately as simple as it can possibly be.
 *
 * One password, written in config.php as plain text. No accounts table,
 * no password hashes, no email lookup, no rate limiter, no lockout.
 * The database is never consulted to decide whether you are allowed in.
 *
 * That removes every failure mode that was making sign-in unreliable:
 * nothing to import, nothing to get out of sync, nothing that can be
 * hashed with one algorithm and verified with another.
 */

declare(strict_types=1);

require_once __DIR__ . '/core.php';

/**
 * Constant-time compare, trimmed on both sides so a password pasted with a
 * stray space still works.
 */
function fcs_password_ok(string $entered): bool
{
    return hash_equals(trim(FCS_ADMIN_PASSWORD), trim($entered));
}

function fcs_sign_in(): void
{
    session_regenerate_id(true);
    $_SESSION['fcs_admin'] = true;
    $_SESSION['since']     = time();
}

function fcs_sign_out(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Everyone who signs in is the same person, with full access. */
function fcs_current_user(): ?array
{
    if (empty($_SESSION['fcs_admin'])) return null;
    return [
        'id'         => null,
        'full_name'  => 'Administrator',
        'role_name'  => 'Administrator',
        'role_slug'  => 'owner',
        'role_level' => 10,
    ];
}

function fcs_is_admin(): bool
{
    return fcs_current_user() !== null;
}

/** Kept so the rest of the code reads the same; there is only one level now. */
function fcs_can(string $permission): bool
{
    return fcs_is_admin();
}

function fcs_require_login(): array
{
    $u = fcs_current_user();
    if (!$u) fcs_fail('Please sign in again.', 401);
    return $u;
}

function fcs_require_perm(string $permission): array
{
    return fcs_require_login();
}

function fcs_permission_map(): array
{
    $all = ['agenda.view', 'agenda.create', 'agenda.edit', 'agenda.delete',
            'agenda.publish', 'agenda.reorder', 'speakers.view', 'speakers.create',
            'speakers.edit', 'speakers.delete', 'speakers.upload', 'tracks.manage',
            'venue.manage', 'history.view', 'history.restore', 'trash.restore',
            'trash.purge', 'settings.manage'];
    return array_fill_keys($all, true);
}

/** No idle timeout and no server-side session registry, so nothing to expire. */
function fcs_touch_session(): void
{
}
