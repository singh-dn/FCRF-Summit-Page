<?php
/**
 * Core: database, request plumbing, RBAC, audit trail, versioning, trash.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

// ---------------------------------------------------------------- database
function fcs_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', FCS_DB_HOST, FCS_DB_NAME, FCS_DB_CHARSET);
        $pdo = new PDO($dsn, FCS_DB_USER, FCS_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+05:30'");
    }
    return $pdo;
}

function fcs_q(string $sql, array $params = []): PDOStatement
{
    $st = fcs_db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function fcs_one(string $sql, array $params = []): ?array
{
    $r = fcs_q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function fcs_all(string $sql, array $params = []): array
{
    return fcs_q($sql, $params)->fetchAll();
}

// ----------------------------------------------------------------- request
function fcs_json_out($data, int $code = 200): never
{
    // Anything echoed before this point — a PHP notice, a leftover debug
    // dump, a stray blank line after ?> in an edited file, a UTF-8 BOM added
    // by a file manager — lands in front of the JSON and makes the browser
    // fail with "Unexpected token '<'". Capture it, log it, drop it.
    $stray = '';
    while (ob_get_level() > 0) {
        $stray .= (string)ob_get_clean();
    }
    if (trim($stray) !== '') {
        error_log('[fcs] stray output before JSON: ' . mb_substr(trim($stray), 0, 500));
        if (is_array($data) && (($_GET['debug'] ?? '') === '1')) {
            $data['_stray_output'] = mb_substr(trim($stray), 0, 500);
        }
    }

    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fcs_fail(string $message, int $code = 400): never
{
    fcs_json_out(['ok' => false, 'error' => $message], $code);
}

function fcs_body(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $raw = file_get_contents('php://input') ?: '';
    $decoded = json_decode($raw, true);
    $cache = is_array($decoded) ? $decoded : $_POST;
    return $cache;
}

function fcs_input(string $key, $default = null)
{
    $b = fcs_body();
    $v = $b[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

/**
 * Same, but without trimming.
 *
 * Passwords must be read with this. Trimming one on the way in means a
 * password with a leading or trailing space — which password managers and
 * copy-paste produce constantly — is stored one way and checked another,
 * and the account can never be signed into.
 */
function fcs_input_raw(string $key, $default = null)
{
    $b = fcs_body();
    return $b[$key] ?? $_POST[$key] ?? $default;
}

function fcs_client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function fcs_ip_bin(): ?string
{
    $p = @inet_pton(fcs_client_ip());
    return $p === false ? null : $p;
}

function fcs_user_agent(): ?string
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    return $ua ? mb_substr($ua, 0, 255) : null;
}

function fcs_e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// -------------------------------------------------------------------- CSRF
function fcs_csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function fcs_check_csrf(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? fcs_input('csrf_token', '');
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        fcs_fail('Your session expired. Reload the page and sign in again.', 419);
    }
}

// -------------------------------------------------------------------- RBAC
function fcs_current_user(): ?array
{
    static $u = null;
    if ($u !== null) return $u ?: null;
    if (empty($_SESSION['uid'])) { $u = false; return null; }
    $row = fcs_one(
        'SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.level AS role_level
           FROM agenda_users u JOIN agenda_roles r ON r.id = u.role_id
          WHERE u.id = ? AND u.is_active = 1 AND u.deleted_at IS NULL',
        [$_SESSION['uid']]
    );
    $u = $row ?: false;
    return $row;
}

/** Effective permission: user-level deny beats role grant; user-level allow beats role silence. */
function fcs_can(string $perm): bool
{
    $u = fcs_current_user();
    if (!$u) return false;

    static $cache = [];
    if (isset($cache[$perm])) return $cache[$perm];

    $row = fcs_one(
        "SELECT up.effect AS override, rp.role_id AS role_grant
           FROM agenda_permissions p
           LEFT JOIN agenda_user_permissions up ON up.permission_id = p.id AND up.user_id = ?
           LEFT JOIN agenda_role_permissions rp ON rp.permission_id = p.id AND rp.role_id = ?
          WHERE p.slug = ?",
        [$u['id'], $u['role_id'], $perm]
    );
    if (!$row) return $cache[$perm] = false;
    if ($row['override'] === 'deny')  return $cache[$perm] = false;
    if ($row['override'] === 'allow') return $cache[$perm] = true;
    return $cache[$perm] = $row['role_grant'] !== null;
}

function fcs_require_login(): array
{
    $u = fcs_current_user();
    if (!$u) fcs_fail('Sign in to continue.', 401);
    return $u;
}

function fcs_require_perm(string $perm): array
{
    $u = fcs_require_login();
    if (!fcs_can($perm)) fcs_fail("You don't have permission to do that.", 403);
    return $u;
}

/** Nobody may act on a user at or above their own level, except the Owner. */
function fcs_require_rank_over(array $target): void
{
    $u = fcs_require_login();
    if ($u['role_slug'] === 'owner') return;
    if ((int)$target['role_level'] <= (int)$u['role_level']) {
        fcs_fail('That account outranks yours.', 403);
    }
}

function fcs_permission_map(): array
{
    $u = fcs_current_user();
    if (!$u) return [];
    $rows = fcs_all(
        "SELECT p.slug,
                CASE WHEN up.effect = 'deny' THEN 0
                     WHEN up.effect = 'allow' THEN 1
                     WHEN rp.role_id IS NOT NULL THEN 1 ELSE 0 END AS granted
           FROM agenda_permissions p
           LEFT JOIN agenda_user_permissions up ON up.permission_id = p.id AND up.user_id = ?
           LEFT JOIN agenda_role_permissions rp ON rp.permission_id = p.id AND rp.role_id = ?",
        [$u['id'], $u['role_id']]
    );
    $out = [];
    foreach ($rows as $r) $out[$r['slug']] = (bool)(int)$r['granted'];
    return $out;
}

// ------------------------------------------------------------- audit trail
function fcs_audit(string $action, string $entityType, ?int $entityId, ?string $label,
               ?array $old = null, ?array $new = null): void
{
    $u = fcs_current_user();
    fcs_q('INSERT INTO agenda_audit_logs
         (user_id, user_name, action, entity_type, entity_id, entity_label,
          old_values, new_values, ip_address, user_agent)
       VALUES (?,?,?,?,?,?,?,?,?,?)',
      [
          $u['id'] ?? null,
          $u['full_name'] ?? null,
          $action, $entityType, $entityId, $label !== null ? mb_substr($label, 0, 255) : null,
          $old !== null ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
          $new !== null ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
          fcs_ip_bin(), fcs_user_agent(),
      ]);
}

/** Only the fields that actually changed, so the history reads cleanly. */
function fcs_diff(array $old, array $new): array
{
    $o = $n = [];
    foreach ($new as $k => $v) {
        $before = $old[$k] ?? null;
        if ((string)$before !== (string)$v) { $o[$k] = $before; $n[$k] = $v; }
    }
    return [$o, $n];
}

// --------------------------------------------------------------- versioning
function fcs_entity_snapshot(string $type, int $id): ?array
{
    switch ($type) {
        case 'session':
            $row = fcs_one('SELECT * FROM agenda_sessions WHERE id = ?', [$id]);
            if (!$row) return null;
            $row['_speakers'] = fcs_all(
                'SELECT speaker_id, speaker_role, sort_order
                   FROM agenda_session_speakers WHERE session_id = ? ORDER BY sort_order', [$id]);
            return $row;
        case 'speaker':
            $row = fcs_one('SELECT * FROM agenda_speakers WHERE id = ?', [$id]);
            if (!$row) return null;
            $row['_sessions'] = fcs_all(
                'SELECT session_id, speaker_role, sort_order
                   FROM agenda_session_speakers WHERE speaker_id = ?', [$id]);
            return $row;
        case 'hall':  return fcs_one('SELECT * FROM agenda_halls  WHERE id = ?', [$id]);
        case 'track': return fcs_one('SELECT * FROM agenda_tracks WHERE id = ?', [$id]);
        case 'user':
            $row = fcs_one('SELECT id, role_id, full_name, email, phone, is_active
                          FROM agenda_users WHERE id = ?', [$id]);
            return $row ?: null;
    }
    return null;
}

/** Call immediately BEFORE writing changes. */
function fcs_snapshot(string $type, int $id, ?string $note = null): void
{
    $data = fcs_entity_snapshot($type, $id);
    if ($data === null) return;
    $u = fcs_current_user();
    $next = (int)(fcs_one('SELECT COALESCE(MAX(version_no), 0) + 1 AS n
                         FROM agenda_versions WHERE entity_type = ? AND entity_id = ?',
                      [$type, $id])['n'] ?? 1);
    fcs_q('INSERT INTO agenda_versions
         (entity_type, entity_id, version_no, snapshot, note, created_by, created_by_name)
       VALUES (?,?,?,?,?,?,?)',
      [$type, $id, $next, json_encode($data, JSON_UNESCAPED_UNICODE), $note,
       $u['id'] ?? null, $u['full_name'] ?? null]);
}

const FCS_VERSION_TABLES = [
    'session' => 'agenda_sessions',
    'speaker' => 'agenda_speakers',
    'hall'    => 'agenda_halls',
    'track'   => 'agenda_tracks',
];

function fcs_restore_version(int $versionId): array
{
    $v = fcs_one('SELECT * FROM agenda_versions WHERE id = ?', [$versionId]);
    if (!$v) fcs_fail('That version no longer exists.', 404);
    $type = $v['entity_type'];
    if (!isset(FCS_VERSION_TABLES[$type])) fcs_fail('That entity type cannot be restored.', 400);

    $table = FCS_VERSION_TABLES[$type];
    $data  = json_decode($v['snapshot'], true);
    $id    = (int)$v['entity_id'];
    $links = $data['_speakers'] ?? $data['_sessions'] ?? null;
    unset($data['_speakers'], $data['_sessions']);

    fcs_snapshot($type, $id, 'Auto-snapshot before restoring v' . $v['version_no']);

    $cols = array_keys($data);
    $set  = implode(', ', array_map(fn($c) => "`$c` = ?", $cols));
    $vals = array_values($data);
    $vals[] = $id;
    fcs_q("UPDATE `$table` SET $set WHERE id = ?", $vals);

    if ($type === 'session' && is_array($links)) {
        fcs_q('DELETE FROM agenda_session_speakers WHERE session_id = ?', [$id]);
        foreach ($links as $l) {
            fcs_q('INSERT IGNORE INTO agenda_session_speakers
                 (session_id, speaker_id, speaker_role, sort_order) VALUES (?,?,?,?)',
              [$id, $l['speaker_id'], $l['speaker_role'], $l['sort_order']]);
        }
    }
    fcs_audit('restore', $type, $id, $data['title'] ?? $data['full_name'] ?? $data['name'] ?? null,
          null, ['restored_version' => $v['version_no']]);
    return ['id' => $id, 'version_no' => $v['version_no']];
}

// -------------------------------------------------------------- soft delete
const FCS_TRASH_TABLES = [
    'session' => ['agenda_sessions', 'title'],
    'speaker' => ['agenda_speakers', 'full_name'],
    'hall'    => ['agenda_halls', 'name'],
    'track'   => ['agenda_tracks', 'name'],
    'user'    => ['agenda_users', 'full_name'],
];

function fcs_soft_delete(string $type, int $id, ?string $summary = null): void
{
    if (!isset(FCS_TRASH_TABLES[$type])) fcs_fail('Unknown item type.', 400);
    [$table, $labelCol] = FCS_TRASH_TABLES[$type];

    $row = fcs_one("SELECT * FROM `$table` WHERE id = ? AND deleted_at IS NULL", [$id]);
    if (!$row) fcs_fail('That item is already gone.', 404);

    fcs_snapshot($type, $id, 'Auto-snapshot before delete');
    fcs_q("UPDATE `$table` SET deleted_at = NOW() WHERE id = ?", [$id]);

    $u = fcs_current_user();
    $retention = (int)(fcs_setting('trash_retention_days') ?? 30);
    fcs_q('INSERT INTO agenda_deleted_items
         (entity_type, entity_id, entity_label, summary, deleted_by, deleted_by_name, purge_after)
       VALUES (?,?,?,?,?,?, DATE_ADD(CURDATE(), INTERVAL ? DAY))',
      [$type, $id, $row[$labelCol] ?? null, $summary,
       $u['id'] ?? null, $u['full_name'] ?? null, $retention]);

    fcs_audit('delete', $type, $id, $row[$labelCol] ?? null, $row, null);
}

function fcs_restore_deleted(int $trashId): array
{
    $t = fcs_one('SELECT * FROM agenda_deleted_items WHERE id = ? AND restored_at IS NULL', [$trashId]);
    if (!$t) fcs_fail('That item is not in the trash.', 404);
    [$table] = FCS_TRASH_TABLES[$t['entity_type']] ?? [null];
    if (!$table) fcs_fail('Unknown item type.', 400);

    fcs_q("UPDATE `$table` SET deleted_at = NULL WHERE id = ?", [$t['entity_id']]);
    fcs_q('UPDATE agenda_deleted_items SET restored_at = NOW() WHERE id = ?', [$trashId]);
    fcs_audit('restore', $t['entity_type'], (int)$t['entity_id'], $t['entity_label']);
    return ['id' => (int)$t['entity_id'], 'type' => $t['entity_type']];
}

function fcs_purge_deleted(int $trashId): void
{
    $t = fcs_one('SELECT * FROM agenda_deleted_items WHERE id = ?', [$trashId]);
    if (!$t) fcs_fail('That item is not in the trash.', 404);
    [$table] = FCS_TRASH_TABLES[$t['entity_type']] ?? [null];
    if (!$table) fcs_fail('Unknown item type.', 400);

    fcs_q("DELETE FROM `$table` WHERE id = ? AND deleted_at IS NOT NULL", [$t['entity_id']]);
    fcs_q('DELETE FROM agenda_deleted_items WHERE id = ?', [$trashId]);
    fcs_audit('purge', $t['entity_type'], (int)$t['entity_id'], $t['entity_label']);
}

// ----------------------------------------------------------------- settings
function fcs_setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (fcs_all('SELECT setting_key, setting_value, value_type FROM agenda_settings') as $r) {
            $v = $r['setting_value'];
            $cache[$r['setting_key']] = match ($r['value_type']) {
                'int'  => (int)$v,
                'bool' => $v === '1' || $v === 'true',
                'json' => json_decode((string)$v, true),
                default => $v,
            };
        }
    }
    return $cache[$key] ?? $default;
}

function fcs_set_setting(string $key, $value, string $type = 'string', bool $isPublic = false): void
{
    $u = fcs_current_user();
    $stored = $type === 'json' ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : (string)$value);
    fcs_q('INSERT INTO agenda_settings (setting_key, setting_value, value_type, is_public, updated_by)
       VALUES (?,?,?,?,?)
       ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value),
                               value_type = VALUES(value_type),
                               is_public = VALUES(is_public),
                               updated_by = VALUES(updated_by)',
      [$key, $stored, $type, (int)$isPublic, $u['id'] ?? null]);
}

// -------------------------------------------------------------------- misc
function fcs_slugify(string $text, string $fallback = 'item'): string
{
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $s = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $s) ?? '');
    return trim($s, '-') ?: $fallback;
}

function fcs_unique_slug(string $table, string $base, ?int $ignoreId = null): string
{
    $slug = $base; $n = 1;
    while (true) {
        $sql = "SELECT id FROM `$table` WHERE slug = ?" . ($ignoreId ? ' AND id <> ?' : '');
        $args = $ignoreId ? [$slug, $ignoreId] : [$slug];
        if (!fcs_one($sql, $args)) return $slug;
        $slug = $base . '-' . (++$n);
    }
}

function fcs_boot_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name(FCS_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        // Scoped to this folder so the CMS cookie is never sent with — or
        // confused for — the main site's session.
        'path'     => FCS_BASE_PATH,
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
