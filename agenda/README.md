# FutureCrime Summit — agenda + CMS

Same build as before, with two changes: **white theme**, and **one password
instead of user accounts**.

```
agenda/
├── index.php      public agenda     → /agenda/
├── admin.php      the console       → /agenda/admin.php
├── api.php        JSON endpoint
├── config.php     DB details + THE PASSWORD
├── lib/           core.php, auth.php
├── assets/        css + js
├── sql/           schema.sql, seed.sql
└── uploads/speakers/
```

## Install on Hostinger

**1. PHP 8.2** — hPanel → Advanced → PHP Configuration.

**2. phpMyAdmin** → select your database → Import:

- `sql/schema.sql` (drops anything from the earlier install, builds 10 tables)
- `sql/seed.sql` (38 sessions, 58 speakers)

**3. Upload** the `agenda` folder into `public_html`. **Delete the old one
first** — `setup.php`, `reset-login.php`, `bin/` and the old `lib/` are gone.

**4. Edit `config.php`** — database name, user, password. Hostinger prefixes
these with your account number, e.g. `u545411682_summit`.

**5. `uploads/speakers` → permission 755.**

**6. Open `/agenda/admin.php`.**

```
Password: summit2026
```

## Changing the password

Line 55 of `config.php`:

```php
defined('FCS_ADMIN_PASSWORD') || define('FCS_ADMIN_PASSWORD', 'summit2026');
```

Change the text, save, done. The old one stops working immediately.

## Why sign-in cannot fail now

The password is a plain string in `config.php`, compared directly to what you
type. No accounts table, no password hash, no email lookup, no rate limiter,
no lockout — **the database is never consulted to decide whether you get in.**
Every part that was breaking is gone.

Leading and trailing spaces are trimmed on both sides, so a pasted password
still works.

If the browser autofills an old saved password over the field, clear it and
type it manually — the field is named to discourage autofill, but Chrome is
persistent about passwords it has already saved for a site.

## What the console does

Dashboard, Agenda, Speakers, Tracks, Venue, History, Trash, Settings.

Create, edit, duplicate, reorder by dragging, publish and unpublish sessions.
Add speakers, upload photos, assign them to sessions. Every change is still
logged in History with old-to-new values, still snapshotted so you can restore
an earlier version, and deletes still go to Trash rather than vanishing.

The Users panel is gone — there is one password and it has full access.

## Before it goes public

Settings → switch off **"Show the admin dot"**. The dot disappears from
`/agenda/`; `admin.php` keeps working.

## Tables

`agenda_sessions`, `agenda_speakers`, `agenda_session_speakers`, `agenda_days`,
`agenda_halls`, `agenda_tracks`, `agenda_audit_logs`, `agenda_versions`,
`agenda_deleted_items`, `agenda_settings`.

The seven account tables — users, roles, permissions, role_permissions,
user_permissions, user_sessions, login_attempts — are dropped by `schema.sql`.
