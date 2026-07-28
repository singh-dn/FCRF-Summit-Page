-- ============================================================
--  FutureCrime Summit — Event Management Portal
--  Database: summit
--  Prefix:   agenda_
--  Engine:   InnoDB / utf8mb4 / MySQL 8.x
-- ============================================================
--  Run against the existing `summit` database. Nothing here
--  touches fcrf_hackathon_2026 or fcrf_defence_passes.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
--  1. ACCESS CONTROL
-- ============================================================

CREATE TABLE IF NOT EXISTS agenda_roles (
  id            TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug          VARCHAR(32)  NOT NULL,
  name          VARCHAR(64)  NOT NULL,
  -- lower level = more power. 10 Owner, 20 Super Admin, 30 Admin, 40 Editor, 50 Viewer
  level         TINYINT UNSIGNED NOT NULL,
  description   VARCHAR(255) NULL,
  is_system     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_slug (slug),
  KEY idx_roles_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agenda_permissions (
  id            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug          VARCHAR(64)  NOT NULL,   -- e.g. agenda.delete
  module        VARCHAR(32)  NOT NULL,   -- agenda | speakers | tracks | venue | users | history | settings
  label         VARCHAR(96)  NOT NULL,   -- "Can Delete Agenda"
  sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_perm_slug (slug),
  KEY idx_perm_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Baseline grants attached to a role.
CREATE TABLE IF NOT EXISTS agenda_role_permissions (
  role_id       TINYINT UNSIGNED  NOT NULL,
  permission_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id)       REFERENCES agenda_roles (id)       ON DELETE CASCADE,
  CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES agenda_permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-user overrides on top of the role. `effect` lets you both
-- grant an extra permission and revoke one the role would allow,
-- which is what makes the per-toggle matrix in the spec work.
CREATE TABLE IF NOT EXISTS agenda_user_permissions (
  user_id       INT UNSIGNED      NOT NULL,
  permission_id SMALLINT UNSIGNED NOT NULL,
  effect        ENUM('allow','deny') NOT NULL DEFAULT 'allow',
  granted_by    INT UNSIGNED      NULL,
  created_at    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agenda_users (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id         TINYINT UNSIGNED NOT NULL,
  full_name       VARCHAR(120) NOT NULL,
  email           VARCHAR(190) NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,      -- Argon2id
  phone           VARCHAR(20)  NULL,
  avatar_path     VARCHAR(255) NULL,
  is_active       TINYINT(1)   NOT NULL DEFAULT 1,
  must_change_pwd TINYINT(1)   NOT NULL DEFAULT 0,
  twofa_secret    VARBINARY(255) NULL,        -- encrypted TOTP secret
  twofa_enabled   TINYINT(1)   NOT NULL DEFAULT 0,
  twofa_backup    JSON         NULL,          -- hashed single-use codes
  failed_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until    DATETIME     NULL,
  last_login_at   DATETIME     NULL,
  last_login_ip   VARBINARY(16) NULL,
  created_by      INT UNSIGNED NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at      DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role_id),
  KEY idx_users_deleted (deleted_at),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES agenda_roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE agenda_user_permissions
  ADD CONSTRAINT fk_up_user FOREIGN KEY (user_id)       REFERENCES agenda_users (id)       ON DELETE CASCADE,
  ADD CONSTRAINT fk_up_perm FOREIGN KEY (permission_id) REFERENCES agenda_permissions (id) ON DELETE CASCADE;

-- Rate limiting + brute-force defence. Logs every attempt, good or bad.
CREATE TABLE IF NOT EXISTS agenda_login_attempts (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email        VARCHAR(190) NOT NULL,
  ip_address   VARBINARY(16) NOT NULL,
  successful   TINYINT(1)   NOT NULL DEFAULT 0,
  user_agent   VARCHAR(255) NULL,
  attempted_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_att_email_time (email, attempted_at),
  KEY idx_att_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Active PHP sessions, so "Editors Online" is a real number and you
-- can force-logout a user from the Users screen.
CREATE TABLE IF NOT EXISTS agenda_user_sessions (
  id           CHAR(64)     NOT NULL,        -- hash of PHP session id
  user_id      INT UNSIGNED NOT NULL,
  ip_address   VARBINARY(16) NULL,
  user_agent   VARCHAR(255) NULL,
  last_seen_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  revoked_at   DATETIME     NULL,
  PRIMARY KEY (id),
  KEY idx_sess_user (user_id),
  KEY idx_sess_seen (last_seen_at),
  CONSTRAINT fk_sess_user FOREIGN KEY (user_id) REFERENCES agenda_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  2. EVENT STRUCTURE
-- ============================================================

CREATE TABLE IF NOT EXISTS agenda_days (
  id          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  day_number  TINYINT UNSIGNED NOT NULL,      -- 1, 2
  event_date  DATE         NOT NULL,          -- 2026-08-06 / 2026-08-07
  label       VARCHAR(64)  NOT NULL,          -- "Day 1"
  subtitle    VARCHAR(160) NULL,              -- theme line for the tab
  is_published TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_days_number (day_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agenda_halls (
  id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(96)  NOT NULL,          -- "Hall A"
  venue       VARCHAR(160) NULL,              -- "Bharat Mandapam, Pragati Maidan"
  floor_info  VARCHAR(96)  NULL,
  capacity    SMALLINT UNSIGNED NULL,
  color_hex   CHAR(7)      NULL,              -- chip colour on the public timeline
  map_note    VARCHAR(255) NULL,              -- wayfinding text for attendees in the hall
  sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  deleted_at  DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_halls_name (name),
  KEY idx_halls_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agenda_tracks (
  id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(96)  NOT NULL,          -- "Cyber Crime Investigation"
  slug        VARCHAR(96)  NOT NULL,
  description VARCHAR(255) NULL,
  color_hex   CHAR(7)      NULL,              -- drives the public filter pills
  sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  deleted_at  DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tracks_slug (slug),
  KEY idx_tracks_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  3. SESSIONS & SPEAKERS
-- ============================================================

CREATE TABLE IF NOT EXISTS agenda_sessions (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  day_id        TINYINT UNSIGNED  NOT NULL,
  hall_id       SMALLINT UNSIGNED NULL,
  track_id      SMALLINT UNSIGNED NULL,
  title         VARCHAR(255) NOT NULL,
  subtitle      VARCHAR(255) NULL,
  description   MEDIUMTEXT   NULL,            -- rich text from TinyMCE/CKEditor
  session_type  ENUM('keynote','panel','fireside','workshop','plenary',
                     'inauguration','valedictory','break','lunch',
                     'networking','award','other') NOT NULL DEFAULT 'panel',
  start_time    TIME         NOT NULL,
  end_time      TIME         NULL,
  is_parallel   TINYINT(1)   NOT NULL DEFAULT 0,  -- runs alongside another hall
  is_featured   TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order    INT UNSIGNED NOT NULL DEFAULT 0,  -- drag & drop within a day
  status        ENUM('draft','published') NOT NULL DEFAULT 'draft',
  external_ref  VARCHAR(64)  NULL,               -- row key from the imported sheet
  created_by    INT UNSIGNED NULL,
  updated_by    INT UNSIGNED NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME     NULL,
  PRIMARY KEY (id),
  KEY idx_sess_day_time (day_id, start_time),
  KEY idx_sess_hall (hall_id),
  KEY idx_sess_track (track_id),
  KEY idx_sess_status (status, deleted_at),
  KEY idx_sess_order (day_id, sort_order),
  FULLTEXT KEY ft_sess_search (title, subtitle, description),
  CONSTRAINT fk_sess_day   FOREIGN KEY (day_id)   REFERENCES agenda_days (id),
  CONSTRAINT fk_sess_hall  FOREIGN KEY (hall_id)  REFERENCES agenda_halls (id)  ON DELETE SET NULL,
  CONSTRAINT fk_sess_track FOREIGN KEY (track_id) REFERENCES agenda_tracks (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agenda_speakers (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name     VARCHAR(160) NOT NULL,
  slug          VARCHAR(160) NOT NULL,
  honorific     VARCHAR(32)  NULL,            -- Dr. / Adv. / IPS prefix handling
  designation   VARCHAR(255) NULL,
  organisation  VARCHAR(255) NULL,
  bio           MEDIUMTEXT   NULL,
  photo_path    VARCHAR(255) NULL,            -- e.g. assets/img/speakers26/name.webp
  linkedin_url  VARCHAR(255) NULL,
  twitter_url   VARCHAR(255) NULL,
  website_url   VARCHAR(255) NULL,
  category      ENUM('government','regulator','defence','law_enforcement',
                     'industry','consulting','academia','other') NULL,
  is_featured   TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order    INT UNSIGNED NOT NULL DEFAULT 0,
  status        ENUM('draft','published') NOT NULL DEFAULT 'published',
  created_by    INT UNSIGNED NULL,
  updated_by    INT UNSIGNED NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_speakers_slug (slug),
  KEY idx_speakers_status (status, deleted_at),
  FULLTEXT KEY ft_speakers_search (full_name, designation, organisation, bio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A speaker can appear in many sessions and a session has many
-- speakers, each with a different role on stage.
CREATE TABLE IF NOT EXISTS agenda_session_speakers (
  session_id   INT UNSIGNED NOT NULL,
  speaker_id   INT UNSIGNED NOT NULL,
  speaker_role ENUM('speaker','panelist','moderator','chair',
                    'chief_guest','keynote','host') NOT NULL DEFAULT 'panelist',
  sort_order   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (session_id, speaker_id),
  KEY idx_ss_speaker (speaker_id),
  CONSTRAINT fk_ss_session FOREIGN KEY (session_id) REFERENCES agenda_sessions (id) ON DELETE CASCADE,
  CONSTRAINT fk_ss_speaker FOREIGN KEY (speaker_id) REFERENCES agenda_speakers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  4. AUDIT, VERSIONS, TRASH
-- ============================================================

CREATE TABLE IF NOT EXISTS agenda_audit_logs (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NULL,             -- NULL survives user deletion
  user_name    VARCHAR(120) NULL,             -- denormalised: log stays readable forever
  action       ENUM('create','update','delete','restore','purge','duplicate',
                    'reorder','publish','unpublish','import','login',
                    'login_failed','logout','permission_change') NOT NULL,
  entity_type  VARCHAR(32)  NOT NULL,         -- session | speaker | user | track | hall | setting
  entity_id    INT UNSIGNED NULL,
  entity_label VARCHAR(255) NULL,             -- "AI in Cyber Crime"
  old_values   JSON         NULL,
  new_values   JSON         NULL,
  ip_address   VARBINARY(16) NULL,
  user_agent   VARCHAR(255) NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_entity (entity_type, entity_id),
  KEY idx_audit_user (user_id),
  KEY idx_audit_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Snapshot written BEFORE each save. Restore = re-apply snapshot.
CREATE TABLE IF NOT EXISTS agenda_versions (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_type  VARCHAR(32)  NOT NULL,
  entity_id    INT UNSIGNED NOT NULL,
  version_no   INT UNSIGNED NOT NULL,
  snapshot     JSON         NOT NULL,         -- full row + relations
  note         VARCHAR(255) NULL,
  created_by   INT UNSIGNED NULL,
  created_by_name VARCHAR(120) NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_version (entity_type, entity_id, version_no),
  KEY idx_version_entity (entity_type, entity_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trash index. The row itself stays put with deleted_at set; this
-- table just makes the Trash screen a single cheap query instead of
-- a UNION across every table.
CREATE TABLE IF NOT EXISTS agenda_deleted_items (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_type  VARCHAR(32)  NOT NULL,
  entity_id    INT UNSIGNED NOT NULL,
  entity_label VARCHAR(255) NULL,
  summary      VARCHAR(255) NULL,             -- "Day 1 · 10:00 · Hall A"
  deleted_by   INT UNSIGNED NULL,
  deleted_by_name VARCHAR(120) NULL,
  deleted_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  purge_after  DATE         NULL,             -- optional auto-clean date
  restored_at  DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_trash_entity (entity_type, entity_id, deleted_at),
  KEY idx_trash_open (restored_at, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  5. SETTINGS
-- ============================================================

CREATE TABLE IF NOT EXISTS agenda_settings (
  setting_key   VARCHAR(64)  NOT NULL,
  setting_value TEXT         NULL,
  value_type    ENUM('string','int','bool','json') NOT NULL DEFAULT 'string',
  is_public     TINYINT(1)   NOT NULL DEFAULT 0,   -- readable by agenda.php
  updated_by    INT UNSIGNED NULL,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  6. SEED DATA
-- ============================================================

INSERT INTO agenda_roles (slug, name, level, description) VALUES
  ('owner',       'Owner',       10, 'Full control including ownership transfer'),
  ('super_admin', 'Super Admin', 20, 'Everything except owner-only controls'),
  ('admin',       'Admin',       30, 'Agenda, speakers and users'),
  ('editor',      'Editor',      40, 'Edit content only, cannot delete or manage users'),
  ('viewer',      'Viewer',      50, 'Read only')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO agenda_permissions (slug, module, label, sort_order) VALUES
  ('agenda.view',      'agenda',   'Can View Agenda',        10),
  ('agenda.create',    'agenda',   'Can Add Agenda',         11),
  ('agenda.edit',      'agenda',   'Can Edit Agenda',        12),
  ('agenda.delete',    'agenda',   'Can Delete Agenda',      13),
  ('agenda.publish',   'agenda',   'Can Publish Agenda',     14),
  ('agenda.reorder',   'agenda',   'Can Reorder Sessions',   15),
  ('speakers.view',    'speakers', 'Can View Speakers',      20),
  ('speakers.create',  'speakers', 'Can Add Speakers',       21),
  ('speakers.edit',    'speakers', 'Can Edit Speakers',      22),
  ('speakers.delete',  'speakers', 'Can Delete Speakers',    23),
  ('speakers.upload',  'speakers', 'Can Upload Images',      24),
  ('tracks.manage',    'tracks',   'Can Manage Tracks',      30),
  ('venue.manage',     'venue',    'Can Manage Venue/Halls', 40),
  ('users.view',       'users',    'Can View Users',         50),
  ('users.manage',     'users',    'Can Manage Users',       51),
  ('users.permissions','users',    'Can Change Permissions', 52),
  ('history.view',     'history',  'Can View History',       60),
  ('history.restore',  'history',  'Can Restore Versions',   61),
  ('trash.restore',    'history',  'Can Restore from Trash', 62),
  ('trash.purge',      'history',  'Can Delete Permanently', 63),
  ('settings.manage',  'settings', 'Can Manage Settings',    70),
  ('import.run',       'settings', 'Can Import from Excel',  71)
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- Owner + Super Admin: everything.
INSERT IGNORE INTO agenda_role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM agenda_roles r CROSS JOIN agenda_permissions p
WHERE r.slug IN ('owner','super_admin');

-- Admin: agenda + speakers + users, no permanent deletion, no settings.
INSERT IGNORE INTO agenda_role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM agenda_roles r CROSS JOIN agenda_permissions p
WHERE r.slug = 'admin' AND p.slug NOT IN ('trash.purge','settings.manage','users.permissions');

-- Editor: view + create + edit, no deletes, no user management.
INSERT IGNORE INTO agenda_role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM agenda_roles r CROSS JOIN agenda_permissions p
WHERE r.slug = 'editor'
  AND p.slug IN ('agenda.view','agenda.create','agenda.edit','agenda.reorder',
                 'speakers.view','speakers.create','speakers.edit','speakers.upload',
                 'history.view');

-- Viewer: read only.
INSERT IGNORE INTO agenda_role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM agenda_roles r CROSS JOIN agenda_permissions p
WHERE r.slug = 'viewer' AND p.slug IN ('agenda.view','speakers.view','history.view');

INSERT INTO agenda_days (day_number, event_date, label) VALUES
  (1, '2026-08-06', 'Day 1'),
  (2, '2026-08-07', 'Day 2')
ON DUPLICATE KEY UPDATE event_date = VALUES(event_date);

INSERT INTO agenda_settings (setting_key, setting_value, value_type, is_public) VALUES
  ('event_name',        'FutureCrime Summit 2026', 'string', 1),
  ('event_venue',       'Bharat Mandapam, Pragati Maidan, New Delhi', 'string', 1),
  ('agenda_published',  '0',    'bool',   1),
  ('show_admin_button', '1',    'bool',   1),   -- flip to 0 before going public
  ('trash_retention_days', '30','int',    0)
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- ============================================================
--  Effective permission check used by the RBAC middleware:
--
--    SELECT 1 FROM agenda_users u
--    JOIN agenda_permissions p ON p.slug = :perm
--    LEFT JOIN agenda_user_permissions up
--           ON up.user_id = u.id AND up.permission_id = p.id
--    LEFT JOIN agenda_role_permissions rp
--           ON rp.role_id = u.role_id AND rp.permission_id = p.id
--    WHERE u.id = :uid AND u.is_active = 1 AND u.deleted_at IS NULL
--      AND (up.effect = 'allow' OR (up.effect IS NULL AND rp.role_id IS NOT NULL));
--
--  User-level 'deny' beats the role grant; user-level 'allow' beats
--  the role's silence. That is the whole matrix in one query.
-- ============================================================
