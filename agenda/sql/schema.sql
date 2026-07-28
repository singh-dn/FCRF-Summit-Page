-- ============================================================
--  FutureCrime Summit — Event Management Portal
--  Ten tables. No accounts, no roles, no permissions: sign-in is
--  a single password in config.php.
--  Import this first, then seed.sql.
-- ============================================================

-- Clears anything left from an earlier install.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS agenda_user_permissions;
DROP TABLE IF EXISTS agenda_role_permissions;
DROP TABLE IF EXISTS agenda_user_sessions;
DROP TABLE IF EXISTS agenda_login_attempts;
DROP TABLE IF EXISTS agenda_permissions;
DROP TABLE IF EXISTS agenda_users;
DROP TABLE IF EXISTS agenda_roles;
DROP TABLE IF EXISTS agenda_session_speakers;
DROP TABLE IF EXISTS agenda_sessions;
DROP TABLE IF EXISTS agenda_speakers;
DROP TABLE IF EXISTS agenda_versions;
DROP TABLE IF EXISTS agenda_deleted_items;
DROP TABLE IF EXISTS agenda_audit_logs;
DROP TABLE IF EXISTS agenda_tracks;
DROP TABLE IF EXISTS agenda_halls;
DROP TABLE IF EXISTS agenda_days;
DROP TABLE IF EXISTS agenda_settings;
SET FOREIGN_KEY_CHECKS = 1;

SET NAMES utf8mb4;

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
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  6. SEED DATA
-- ============================================================







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

