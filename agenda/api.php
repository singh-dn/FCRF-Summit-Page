<?php
/**
 * Single JSON endpoint for both pages.  api.php?action=<name>
 * Reads are GET, everything that changes state is POST + CSRF.
 */

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

// Buffer everything so that any accidental output — from this app, from a
// stray edit, from a host-level prepend — can be stripped before the JSON
// rather than corrupting it.
ob_start();

fcs_boot_session();
fcs_touch_session();

header('Referrer-Policy: same-origin');
$action = (string)($_GET['action'] ?? '');
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

// Public reads need no session at all.
const FCS_PUBLIC_ACTIONS = ['agenda.public', 'auth.login', 'auth.state'];

if (!in_array($action, FCS_PUBLIC_ACTIONS, true)) {
    fcs_require_login();
}
if ($isPost && $action !== 'auth.login') {
    fcs_check_csrf();
}

try {
    fcs_json_out(fcs_route($action, $isPost));
} catch (PDOException $ex) {
    error_log('[fcs] ' . $ex->getMessage());
    fcs_fail('The database rejected that change. Nothing was saved.', 500);
}

function fcs_route(string $action, bool $isPost): array
{
    return match ($action) {
        'agenda.public'      => fcs_public_agenda(),
        'auth.state'         => fcs_auth_state(),
        'auth.login'         => fcs_auth_login(),
        'auth.logout'        => (function () { fcs_sign_out(); return ['ok' => true]; })(),
        'dash.stats'         => fcs_dash_stats(),

        'session.list'       => ['ok' => true, 'sessions' => fcs_admin_sessions()],
        'session.save'       => fcs_session_save(),
        'session.delete'     => fcs_session_delete(),
        'session.duplicate'  => fcs_session_duplicate(),
        'session.reorder'    => fcs_session_reorder(),
        'session.publish'    => fcs_session_publish(),

        'speaker.list'       => ['ok' => true, 'speakers' => fcs_admin_speakers()],
        'speaker.save'       => fcs_speaker_save(),
        'speaker.delete'     => fcs_speaker_delete(),
        'speaker.photo'      => fcs_speaker_photo(),

        'hall.save'          => fcs_taxonomy_save('hall'),
        'hall.delete'        => fcs_taxonomy_delete('hall'),
        'track.save'         => fcs_taxonomy_save('track'),
        'track.delete'       => fcs_taxonomy_delete('track'),


        'history.list'       => fcs_history_list(),
        'version.list'       => fcs_version_list(),
        'version.restore'    => fcs_version_restore(),

        'trash.list'         => fcs_trash_list(),
        'trash.restore'      => fcs_trash_restore(),
        'trash.purge'        => fcs_trash_purge(),

        'settings.save'      => fcs_settings_save(),
        default              => fcs_fail('Unknown action.', 404),
    };
}

// ============================================================ public agenda
function fcs_public_agenda(): array
{
    $days = fcs_all('SELECT id, day_number, event_date, label, subtitle
                   FROM agenda_days WHERE is_published = 1 ORDER BY day_number');
    $halls = fcs_all('SELECT id, name, venue, floor_info, color_hex, map_note
                    FROM agenda_halls WHERE deleted_at IS NULL ORDER BY sort_order, name');
    $tracks = fcs_all('SELECT id, name, slug, color_hex
                     FROM agenda_tracks WHERE deleted_at IS NULL ORDER BY sort_order, name');

    $sessions = fcs_all(
        "SELECT s.id, s.day_id, s.hall_id, s.track_id, s.title, s.subtitle, s.description,
                s.session_type, s.start_time, s.end_time, s.is_featured, s.sort_order
           FROM agenda_sessions s
          WHERE s.status = 'published' AND s.deleted_at IS NULL
          ORDER BY s.day_id, s.start_time, s.sort_order");

    $links = fcs_all(
        "SELECT ss.session_id, ss.speaker_role, ss.sort_order,
                sp.id, sp.full_name, sp.honorific, sp.designation, sp.organisation,
                sp.bio, sp.photo_path, sp.linkedin_url
           FROM agenda_session_speakers ss
           JOIN agenda_speakers sp ON sp.id = ss.speaker_id
           JOIN agenda_sessions s  ON s.id = ss.session_id
          WHERE sp.status = 'published' AND sp.deleted_at IS NULL
            AND s.status = 'published'  AND s.deleted_at IS NULL
          ORDER BY ss.sort_order");

    $bySession = [];
    foreach ($links as $l) {
        $bySession[$l['session_id']][] = [
            'id' => (int)$l['id'], 'name' => $l['full_name'], 'honorific' => $l['honorific'],
            'designation' => $l['designation'], 'organisation' => $l['organisation'],
            'bio' => $l['bio'], 'photo' => $l['photo_path'], 'linkedin' => $l['linkedin_url'],
            'role' => $l['speaker_role'],
        ];
    }
    foreach ($sessions as &$s) {
        $s['id'] = (int)$s['id'];
        $s['speakers'] = $bySession[$s['id']] ?? [];
    }
    unset($s);

    $directory = fcs_all(
        "SELECT id, full_name, honorific, designation, organisation, bio,
                photo_path, linkedin_url, category, is_featured
           FROM agenda_speakers
          WHERE status = 'published' AND deleted_at IS NULL
          ORDER BY is_featured DESC, sort_order, full_name");

    return [
        'ok' => true,
        'event' => [
            'name'  => fcs_setting('event_name', FCS_EVENT_NAME),
            'venue' => fcs_setting('event_venue', 'Bharat Mandapam, Pragati Maidan, New Delhi'),
        ],
        'days' => $days, 'halls' => $halls, 'tracks' => $tracks,
        'sessions' => $sessions, 'speakers' => $directory,
        'generated_at' => date('c'),
    ];
}

// ===================================================================== auth
function fcs_auth_state(): array
{
    if (!fcs_is_admin()) return ['ok' => true, 'authenticated' => false, 'csrf' => fcs_csrf_token()];
    return [
        'ok' => true, 'authenticated' => true, 'csrf' => fcs_csrf_token(),
        'user' => ['name' => 'Administrator', 'role' => 'Administrator'],
        'permissions' => fcs_permission_map(),
    ];
}

function fcs_auth_login(): array
{
    if (!fcs_password_ok((string)fcs_input_raw('password', ''))) {
        usleep(350000);                       // take the edge off guessing
        fcs_fail('That password is not right.', 401);
    }
    fcs_sign_in();
    return ['ok' => true, 'csrf' => fcs_csrf_token()];
}

// ================================================================ dashboard
function fcs_dash_stats(): array
{
    $today = fcs_one("SELECT COUNT(*) AS n FROM agenda_audit_logs
                   WHERE DATE(created_at) = CURDATE()
                     AND action IN ('create','update','delete','publish','unpublish','restore')");
    $sessions = fcs_one("SELECT COUNT(*) AS n FROM agenda_sessions WHERE deleted_at IS NULL");
    $published = fcs_one("SELECT COUNT(*) AS n FROM agenda_sessions WHERE deleted_at IS NULL AND status='published'");
    $speakers = fcs_one("SELECT COUNT(*) AS n FROM agenda_speakers WHERE deleted_at IS NULL");
    $confirmed = fcs_one("SELECT COUNT(*) AS n FROM agenda_speakers WHERE deleted_at IS NULL AND status='published'");
    $last = fcs_one("SELECT created_at, user_name, action, entity_type, entity_label
                   FROM agenda_audit_logs ORDER BY id DESC LIMIT 1");
    $trash = fcs_one("SELECT COUNT(*) AS n FROM agenda_deleted_items WHERE restored_at IS NULL");
    $unassigned = fcs_one("SELECT COUNT(*) AS n FROM agenda_sessions s
                        WHERE s.deleted_at IS NULL AND s.session_type IN ('panel','keynote','workshop')
                          AND NOT EXISTS (SELECT 1 FROM agenda_session_speakers ss WHERE ss.session_id = s.id)");

    return [
        'ok' => true,
        'stats' => [
            'updates_today'     => (int)$today['n'],
            'sessions'          => (int)$sessions['n'],
            'sessions_live'     => (int)$published['n'],
            'speakers'          => (int)$speakers['n'],
            'speakers_live'     => (int)$confirmed['n'],
            'in_trash'          => (int)$trash['n'],
            'sessions_no_speaker' => (int)$unassigned['n'],
        ],
        'last_change' => $last,
        'feed' => fcs_all("SELECT id, user_name, action, entity_type, entity_label, created_at
                         FROM agenda_audit_logs
                        WHERE action NOT IN ('login','logout','login_failed')
                        ORDER BY id DESC LIMIT 12"),
    ];
}

// ================================================================= sessions
function fcs_admin_sessions(): array
{
    $rows = fcs_all("SELECT s.*, h.name AS hall_name, t.name AS track_name, d.day_number
                   FROM agenda_sessions s
                   LEFT JOIN agenda_halls h  ON h.id = s.hall_id
                   LEFT JOIN agenda_tracks t ON t.id = s.track_id
                   JOIN agenda_days d ON d.id = s.day_id
                  WHERE s.deleted_at IS NULL
                  ORDER BY s.day_id, s.sort_order, s.start_time");
    $links = fcs_all("SELECT ss.session_id, ss.speaker_id, ss.speaker_role, ss.sort_order, sp.full_name
                    FROM agenda_session_speakers ss
                    JOIN agenda_speakers sp ON sp.id = ss.speaker_id
                   WHERE sp.deleted_at IS NULL ORDER BY ss.sort_order");
    $by = [];
    foreach ($links as $l) $by[$l['session_id']][] = $l;
    foreach ($rows as &$r) {
        $r['id'] = (int)$r['id'];
        $r['speakers'] = $by[$r['id']] ?? [];
    }
    return $rows;
}

function fcs_session_save(): array
{
    $id = (int)fcs_input('id', 0);
    fcs_require_perm($id ? 'agenda.edit' : 'agenda.create');

    $title = (string)fcs_input('title', '');
    if ($title === '') fcs_fail('Give the session a title.');
    $dayId = (int)fcs_input('day_id', 0);
    if (!$dayId) fcs_fail('Pick which day this session runs on.');

    $start = (string)fcs_input('start_time', '');
    $end   = (string)fcs_input('end_time', '') ?: null;
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $start)) fcs_fail('Start time must look like 14:30.');
    if ($end && $end <= $start) fcs_fail('The session cannot end before it starts.');

    $fields = [
        'day_id'       => $dayId,
        'hall_id'      => fcs_input('hall_id') ? (int)fcs_input('hall_id') : null,
        'track_id'     => fcs_input('track_id') ? (int)fcs_input('track_id') : null,
        'title'        => $title,
        'subtitle'     => fcs_input('subtitle') ?: null,
        'description'  => fcs_input('description') ?: null,
        'session_type' => (string)fcs_input('session_type', 'panel'),
        'start_time'   => strlen($start) === 5 ? $start . ':00' : $start,
        'end_time'     => $end ? (strlen($end) === 5 ? $end . ':00' : $end) : null,
        'is_parallel'  => (int)(bool)fcs_input('is_parallel', 0),
        'is_featured'  => (int)(bool)fcs_input('is_featured', 0),
        'status'       => fcs_input('status') === 'published' ? 'published' : 'draft',
    ];
    if ($fields['status'] === 'published') fcs_require_perm('agenda.publish');

    $u = fcs_current_user();

    if ($id) {
        $old = fcs_one('SELECT * FROM agenda_sessions WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$old) fcs_fail('That session no longer exists.', 404);
        fcs_snapshot('session', $id);
        $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($fields)));
        fcs_fcs_q("UPDATE agenda_sessions SET $set WHERE id = ?", [...array_values($fields), $id]);
        [$o, $n] = fcs_diff($old, $fields);
        fcs_audit('update', 'session', $id, $title, $o, $n);
    } else {
        $maxOrder = (int)(fcs_one('SELECT COALESCE(MAX(sort_order),0)+10 AS n FROM agenda_sessions WHERE day_id = ?', [$dayId])['n'] ?? 10);
        $fields['sort_order'] = $maxOrder;
        $cols = implode('`,`', array_keys($fields));
        $ph   = implode(',', array_fill(0, count($fields), '?'));
        fcs_q("INSERT INTO agenda_sessions (`$cols`) VALUES ($ph)", array_values($fields));
        $id = (int)fcs_db()->lastInsertId();
        fcs_audit('create', 'session', $id, $title, null, $fields);
    }

    // Speaker assignment arrives as [{speaker_id, speaker_role}, ...]
    $speakers = fcs_body()['speakers'] ?? null;
    if (is_array($speakers)) {
        fcs_q('DELETE FROM agenda_session_speakers WHERE session_id = ?', [$id]);
        $i = 0;
        foreach ($speakers as $sp) {
            $sid = (int)($sp['speaker_id'] ?? 0);
            if (!$sid) continue;
            fcs_q('INSERT IGNORE INTO agenda_session_speakers
                 (session_id, speaker_id, speaker_role, sort_order) VALUES (?,?,?,?)',
              [$id, $sid, $sp['speaker_role'] ?? 'panelist', ++$i]);
        }
    }
    return ['ok' => true, 'id' => $id];
}

function fcs_session_delete(): array
{
    fcs_require_perm('agenda.delete');
    $id = (int)fcs_input('id', 0);
    $s = fcs_one('SELECT s.*, h.name AS hall_name, d.day_number FROM agenda_sessions s
                LEFT JOIN agenda_halls h ON h.id = s.hall_id
                JOIN agenda_days d ON d.id = s.day_id WHERE s.id = ?', [$id]);
    if (!$s) fcs_fail('That session no longer exists.', 404);
    fcs_soft_delete('session', $id, sprintf('Day %s · %s · %s',
        $s['day_number'], substr((string)$s['start_time'], 0, 5), $s['hall_name'] ?? 'No hall'));
    return ['ok' => true];
}

function fcs_session_duplicate(): array
{
    fcs_require_perm('agenda.create');
    $id = (int)fcs_input('id', 0);
    $s = fcs_one('SELECT * FROM agenda_sessions WHERE id = ? AND deleted_at IS NULL', [$id]);
    if (!$s) fcs_fail('That session no longer exists.', 404);

    $u = fcs_current_user();
    fcs_q("INSERT INTO agenda_sessions
         (day_id, hall_id, track_id, title, subtitle, description, session_type,
          start_time, end_time, is_parallel, is_featured, sort_order, status)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'draft')",
      [$s['day_id'], $s['hall_id'], $s['track_id'], $s['title'] . ' (copy)', $s['subtitle'],
       $s['description'], $s['session_type'], $s['start_time'], $s['end_time'],
       $s['is_parallel'], 0, (int)$s['sort_order'] + 1]);
    $new = (int)fcs_db()->lastInsertId();

    fcs_q('INSERT INTO agenda_session_speakers (session_id, speaker_id, speaker_role, sort_order)
       SELECT ?, speaker_id, speaker_role, sort_order FROM agenda_session_speakers WHERE session_id = ?',
      [$new, $id]);

    fcs_audit('duplicate', 'session', $new, $s['title'] . ' (copy)', null, ['copied_from' => $id]);
    return ['ok' => true, 'id' => $new];
}

function fcs_session_reorder(): array
{
    fcs_require_perm('agenda.reorder');
    $order = fcs_body()['order'] ?? [];
    if (!is_array($order) || !$order) fcs_fail('Nothing to reorder.');
    $pdo = fcs_db();
    $pdo->beginTransaction();
    try {
        $i = 0;
        foreach ($order as $sid) {
            fcs_q('UPDATE agenda_sessions SET sort_order = ? WHERE id = ?', [($i += 10), (int)$sid]);
        }
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
    fcs_audit('reorder', 'session', null, count($order) . ' sessions reordered');
    return ['ok' => true];
}

function fcs_session_publish(): array
{
    fcs_require_perm('agenda.publish');
    $id = (int)fcs_input('id', 0);
    $publish = (bool)fcs_input('publish', true);
    $s = fcs_one('SELECT title, status FROM agenda_sessions WHERE id = ? AND deleted_at IS NULL', [$id]);
    if (!$s) fcs_fail('That session no longer exists.', 404);
    fcs_snapshot('session', $id);
    fcs_q('UPDATE agenda_sessions SET status = ? WHERE id = ?', [$publish ? 'published' : 'draft', $id]);
    fcs_audit($publish ? 'publish' : 'unpublish', 'session', $id, $s['title'],
          ['status' => $s['status']], ['status' => $publish ? 'published' : 'draft']);
    return ['ok' => true];
}

// ================================================================= speakers
function fcs_admin_speakers(): array
{
    return fcs_all("SELECT sp.*,
                       (SELECT COUNT(*) FROM agenda_session_speakers ss WHERE ss.speaker_id = sp.id) AS session_count
                  FROM agenda_speakers sp
                 WHERE sp.deleted_at IS NULL
                 ORDER BY sp.sort_order, sp.full_name");
}

function fcs_speaker_save(): array
{
    $id = (int)fcs_input('id', 0);
    fcs_require_perm($id ? 'speakers.edit' : 'speakers.create');

    $name = (string)fcs_input('full_name', '');
    if ($name === '') fcs_fail('Give the speaker a name.');

    $fields = [
        'full_name'    => $name,
        'honorific'    => fcs_input('honorific') ?: null,
        'designation'  => fcs_input('designation') ?: null,
        'organisation' => fcs_input('organisation') ?: null,
        'bio'          => fcs_input('bio') ?: null,
        'linkedin_url' => filter_var((string)fcs_input('linkedin_url', ''), FILTER_VALIDATE_URL) ?: null,
        'website_url'  => filter_var((string)fcs_input('website_url', ''), FILTER_VALIDATE_URL) ?: null,
        'category'     => fcs_input('category') ?: null,
        'is_featured'  => (int)(bool)fcs_input('is_featured', 0),
        'status'       => fcs_input('status') === 'draft' ? 'draft' : 'published',
    ];
    $u = fcs_current_user();

    if ($id) {
        $old = fcs_one('SELECT * FROM agenda_speakers WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$old) fcs_fail('That speaker no longer exists.', 404);
        fcs_snapshot('speaker', $id);
        $fields['slug'] = fcs_unique_slug('agenda_speakers', fcs_slugify($name, 'speaker'), $id);
        $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($fields)));
        fcs_q("UPDATE agenda_speakers SET $set WHERE id = ?", [...array_values($fields), $id]);
        [$o, $n] = fcs_diff($old, $fields);
        fcs_audit('update', 'speaker', $id, $name, $o, $n);
    } else {
        $fields['slug'] = fcs_unique_slug('agenda_speakers', fcs_slugify($name, 'speaker'));
        $cols = implode('`,`', array_keys($fields));
        $ph   = implode(',', array_fill(0, count($fields), '?'));
        fcs_q("INSERT INTO agenda_speakers (`$cols`) VALUES ($ph)", array_values($fields));
        $id = (int)fcs_db()->lastInsertId();
        fcs_audit('create', 'speaker', $id, $name, null, $fields);
    }

    $sessions = fcs_body()['sessions'] ?? null;
    if (is_array($sessions)) {
        fcs_q('DELETE FROM agenda_session_speakers WHERE speaker_id = ?', [$id]);
        foreach ($sessions as $s) {
            $sid = (int)($s['session_id'] ?? 0);
            if (!$sid) continue;
            fcs_q('INSERT IGNORE INTO agenda_session_speakers
                 (session_id, speaker_id, speaker_role, sort_order) VALUES (?,?,?,0)',
              [$sid, $id, $s['speaker_role'] ?? 'panelist']);
        }
    }
    return ['ok' => true, 'id' => $id];
}

function fcs_speaker_delete(): array
{
    fcs_require_perm('speakers.delete');
    $id = (int)fcs_input('id', 0);
    $sp = fcs_one('SELECT full_name, organisation FROM agenda_speakers WHERE id = ?', [$id]);
    if (!$sp) fcs_fail('That speaker no longer exists.', 404);
    fcs_soft_delete('speaker', $id, $sp['organisation'] ?: null);
    return ['ok' => true];
}

/** Re-encodes through GD, which strips EXIF (including any GPS tags). */
function fcs_speaker_photo(): array
{
    fcs_require_perm('speakers.upload');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) fcs_fail('Save the speaker first, then add a photo.');
    if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        fcs_fail('The upload did not complete. Try again.');
    }
    $f = $_FILES['photo'];
    if ($f['size'] > FCS_MAX_UPLOAD_BYTES) fcs_fail('Photos must be under 3 MB.');

    $info = @getimagesize($f['tmp_name']);
    if (!$info) fcs_fail('That file is not an image.');
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!isset($allowed[$info[2]])) fcs_fail('Use a JPG, PNG or WebP image.');

    $src = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($f['tmp_name']),
        IMAGETYPE_PNG  => @imagecreatefrompng($f['tmp_name']),
        IMAGETYPE_WEBP => @imagecreatefromwebp($f['tmp_name']),
    };
    if (!$src) fcs_fail('That image could not be read.');

    // Square crop from the centre, capped at 640px.
    $w = imagesx($src); $h = imagesy($src);
    $side = min($w, $h); $target = min(640, $side);
    $dst = imagecreatetruecolor($target, $target);
    imagecopyresampled($dst, $src, 0, 0, (int)(($w - $side) / 2), (int)(($h - $side) / 2),
                       $target, $target, $side, $side);

    if (!is_dir(FCS_UPLOAD_DIR)) mkdir(FCS_UPLOAD_DIR, 0755, true);
    $name = bin2hex(random_bytes(8)) . '.jpg';
    imagejpeg($dst, FCS_UPLOAD_DIR . '/' . $name, 86);
    imagedestroy($src); imagedestroy($dst);

    $old = fcs_one('SELECT photo_path, full_name FROM agenda_speakers WHERE id = ?', [$id]);
    fcs_snapshot('speaker', $id);
    fcs_q('UPDATE agenda_speakers SET photo_path = ? WHERE id = ?', [FCS_UPLOAD_URL . '/' . $name, $id]);

    if (!empty($old['photo_path'])) {
        $prev = FCS_APP_ROOT . '/' . $old['photo_path'];
        if (is_file($prev) && str_starts_with(realpath($prev) ?: '', realpath(FCS_UPLOAD_DIR) ?: 'x')) {
            @unlink($prev);
        }
    }
    fcs_audit('update', 'speaker', $id, $old['full_name'] ?? null,
          ['photo_path' => $old['photo_path'] ?? null], ['photo_path' => FCS_UPLOAD_URL . '/' . $name]);

    return ['ok' => true, 'photo' => FCS_UPLOAD_URL . '/' . $name];
}

// ========================================================== halls / tracks
function fcs_taxonomy_save(string $type): array
{
    fcs_require_perm($type === 'hall' ? 'venue.manage' : 'tracks.manage');
    $table = $type === 'hall' ? 'agenda_halls' : 'agenda_tracks';
    $id = (int)fcs_input('id', 0);
    $name = (string)fcs_input('name', '');
    if ($name === '') fcs_fail('Give it a name.');

    if ($type === 'hall') {
        $fields = [
            'name' => $name, 'venue' => fcs_input('venue') ?: null,
            'floor_info' => fcs_input('floor_info') ?: null,
            'capacity' => fcs_input('capacity') ? (int)fcs_input('capacity') : null,
            'color_hex' => fcs_input('color_hex') ?: null,
            'map_note' => fcs_input('map_note') ?: null,
            'sort_order' => (int)fcs_input('sort_order', 0),
        ];
    } else {
        $fields = [
            'name' => $name,
            'slug' => fcs_unique_slug($table, fcs_slugify($name, 'track'), $id ?: null),
            'description' => fcs_input('description') ?: null,
            'color_hex' => fcs_input('color_hex') ?: null,
            'sort_order' => (int)fcs_input('sort_order', 0),
        ];
    }

    if ($id) {
        fcs_snapshot($type, $id);
        $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($fields)));
        fcs_q("UPDATE `$table` SET $set WHERE id = ?", [...array_values($fields), $id]);
        fcs_audit('update', $type, $id, $name, null, $fields);
    } else {
        $cols = implode('`,`', array_keys($fields));
        $ph = implode(',', array_fill(0, count($fields), '?'));
        fcs_q("INSERT INTO `$table` (`$cols`) VALUES ($ph)", array_values($fields));
        $id = (int)fcs_db()->lastInsertId();
        fcs_audit('create', $type, $id, $name, null, $fields);
    }
    return ['ok' => true, 'id' => $id];
}

function fcs_taxonomy_delete(string $type): array
{
    fcs_require_perm($type === 'hall' ? 'venue.manage' : 'tracks.manage');
    fcs_soft_delete($type, (int)fcs_input('id', 0));
    return ['ok' => true];
}

// ==================================================================== users




// ============================================== history / versions / trash
function fcs_history_list(): array
{
    fcs_require_perm('history.view');
    $limit  = min(200, max(10, (int)fcs_input('limit', 60)));
    $offset = max(0, (int)fcs_input('offset', 0));
    $type   = fcs_input('entity_type');
    $where  = $type ? 'WHERE entity_type = ?' : '';
    $args   = $type ? [$type] : [];

    $rows = fcs_all("SELECT id, user_name, action, entity_type, entity_id, entity_label,
                        old_values, new_values, INET6_NTOA(ip_address) AS ip, created_at
                   FROM agenda_audit_logs $where
                  ORDER BY id DESC LIMIT $limit OFFSET $offset", $args);
    foreach ($rows as &$r) {
        $r['old_values'] = $r['old_values'] ? json_decode($r['old_values'], true) : null;
        $r['new_values'] = $r['new_values'] ? json_decode($r['new_values'], true) : null;
    }
    return ['ok' => true, 'entries' => $rows];
}

function fcs_version_list(): array
{
    fcs_require_perm('history.view');
    return ['ok' => true, 'versions' => fcs_all(
        'SELECT id, version_no, note, created_by_name, created_at
           FROM agenda_versions WHERE entity_type = ? AND entity_id = ?
          ORDER BY version_no DESC LIMIT 50',
        [(string)fcs_input('entity_type', ''), (int)fcs_input('entity_id', 0)])];
}

function fcs_version_restore(): array
{
    fcs_require_perm('history.restore');
    return ['ok' => true] + fcs_restore_version((int)fcs_input('version_id', 0));
}

function fcs_trash_list(): array
{
    fcs_require_perm('history.view');
    return ['ok' => true, 'items' => fcs_all(
        'SELECT id, entity_type, entity_id, entity_label, summary,
                deleted_by_name, deleted_at, purge_after
           FROM agenda_deleted_items WHERE restored_at IS NULL ORDER BY deleted_at DESC')];
}

function fcs_trash_restore(): array
{
    fcs_require_perm('trash.restore');
    return ['ok' => true] + fcs_restore_deleted((int)fcs_input('id', 0));
}

function fcs_trash_purge(): array
{
    fcs_require_perm('trash.purge');
    fcs_purge_deleted((int)fcs_input('id', 0));
    return ['ok' => true];
}

// ================================================================ settings
function fcs_settings_save(): array
{
    fcs_require_perm('settings.manage');
    $map = [
        'event_name'        => ['string', true],
        'event_venue'       => ['string', true],
        'agenda_published'  => ['bool', true],
        'show_admin_button' => ['bool', true],
        'trash_retention_days' => ['int', false],
    ];
    $changed = [];
    foreach (fcs_body() as $k => $v) {
        if (!isset($map[$k])) continue;
        [$type, $public] = $map[$k];
        fcs_set_setting($k, $type === 'bool' ? (bool)$v : $v, $type, $public);
        $changed[$k] = $v;
    }
    fcs_audit('update', 'setting', null, 'Settings', null, $changed);
    return ['ok' => true, 'changed' => $changed];
}
