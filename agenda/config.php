<?php
/**
 * FutureCrime Summit — Agenda module configuration.
 *
 * This module is self-contained: every constant and function it defines is
 * prefixed (FCS_ / fcs_) so it cannot clash with anything already in the
 * project, and every define() is guarded in case a name is already taken.
 *
 * Two ways to supply credentials — pick one:
 *
 *   A. Reuse what the rest of the site already has. If your existing
 *      bootstrap defines DB_HOST / DB_NAME / DB_USER / DB_PASS, uncomment
 *      the require below and this file will inherit them.
 *
 *   B. Fill in the fallbacks at the bottom of this block, or set the
 *      FCS_DB_* environment variables.
 */

declare(strict_types=1);

// ---- Option A: inherit the site's existing connection details -------------
// require_once __DIR__ . '/../includes/config.php';

$fcsHost = getenv('FCS_DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
$fcsName = getenv('FCS_DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'u545411682_summit');
$fcsUser = getenv('FCS_DB_USER') ?: (defined('DB_USER') ? DB_USER : 'u545411682_summit');
$fcsPass = getenv('FCS_DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : 'Summit2026');

// ---- Database -------------------------------------------------------------
defined('FCS_DB_HOST')    || define('FCS_DB_HOST', $fcsHost);
defined('FCS_DB_NAME')    || define('FCS_DB_NAME', $fcsName);
defined('FCS_DB_USER')    || define('FCS_DB_USER', $fcsUser);
defined('FCS_DB_PASS')    || define('FCS_DB_PASS', $fcsPass);
defined('FCS_DB_CHARSET') || define('FCS_DB_CHARSET', 'utf8mb4');

unset($fcsHost, $fcsName, $fcsUser, $fcsPass);

// ---- Paths ----------------------------------------------------------------
// Everything is resolved from this folder, so the module works wherever you
// drop it — /agenda/, /schedule/, a subdomain root, anywhere.
defined('FCS_APP_ROOT')   || define('FCS_APP_ROOT', __DIR__);
defined('FCS_UPLOAD_DIR') || define('FCS_UPLOAD_DIR', __DIR__ . '/uploads/speakers');
defined('FCS_UPLOAD_URL') || define('FCS_UPLOAD_URL', 'uploads/speakers');

/** Web path of this folder, e.g. /agenda — used for the session cookie scope. */
if (!defined('FCS_BASE_PATH')) {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/agenda/index.php'));
    if (basename($dir) === 'bin') $dir = dirname($dir);
    define('FCS_BASE_PATH', rtrim($dir, '/') . '/');
}

// ---- Security -------------------------------------------------------------
// A distinct session name so the CMS login never collides with, or logs
// anyone out of, whatever sessions the rest of the site uses.
defined('FCS_SESSION_NAME')         || define('FCS_SESSION_NAME', 'fcs_agenda_admin');
defined('FCS_SESSION_IDLE_TIMEOUT') || define('FCS_SESSION_IDLE_TIMEOUT', 60 * 60 * 2);
defined('FCS_LOGIN_MAX_ATTEMPTS')   || define('FCS_LOGIN_MAX_ATTEMPTS', 5);
defined('FCS_LOGIN_WINDOW_SECONDS') || define('FCS_LOGIN_WINDOW_SECONDS', 900);
defined('FCS_LOCKOUT_SECONDS')      || define('FCS_LOCKOUT_SECONDS', 900);
defined('FCS_MAX_UPLOAD_BYTES')     || define('FCS_MAX_UPLOAD_BYTES', 3 * 1024 * 1024);

// ---- Event ----------------------------------------------------------------
defined('FCS_EVENT_TIMEZONE') || define('FCS_EVENT_TIMEZONE', 'Asia/Kolkata');
defined('FCS_EVENT_NAME')     || define('FCS_EVENT_NAME', 'FutureCrime Summit 2026');

// Fallback only — the real switch is Settings → "Show the admin dot".
defined('FCS_SHOW_ADMIN_BUTTON_FALLBACK') || define('FCS_SHOW_ADMIN_BUTTON_FALLBACK', true);

// Scoped to this request only; does not disturb the rest of the site.
date_default_timezone_set(FCS_EVENT_TIMEZONE);
