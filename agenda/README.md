# Agenda module — drop-in install

Self-contained. Copy the whole `agenda/` folder into your existing summit
site and it runs. Nothing goes in the site root, nothing existing is edited.

```
summit.futurecrime.org/
├── index.php                  ← your existing site, untouched
├── law-enforcement-pass/      ← untouched
├── assets/                    ← untouched
└── agenda/                    ← the entire module lives here
    ├── index.php              public agenda      → /agenda/
    ├── admin.php              the CMS            → /agenda/admin.php
    ├── api.php                JSON endpoint
    ├── config.php             credentials
    ├── .htaccess              folder protection
    ├── lib/                   core.php, auth.php
    ├── setup.php              browser installer (delete after use)
    ├── bin/                   create-owner.php (CLI alternative)
    ├── assets/                its own css + js
    ├── sql/                   schema.sql, seed.sql
    └── uploads/speakers/      photos land here
```

## Why it cannot collide with what you already have

| Risk | How it is avoided |
|---|---|
| Function name clashes (`db()`, `e()`, `input()`) | Every function is prefixed `fcs_` — `fcs_db()`, `fcs_e()`. 73 of them, no bare names. |
| Constant clashes | Every constant is `FCS_*` and wrapped in `defined() \|\| define()`. |
| Table clashes | Every table is `agenda_*`. Your `fcrf_hackathon_2026` and `fcrf_defence_passes` are never touched. |
| Session clashes | Its own session name (`fcs_agenda_admin`) and a cookie scoped to `/agenda/`. Signing into the CMS cannot log anyone out of anything else. |
| CSS/JS clashes | All styles live in `agenda/assets/` and are loaded only by these two pages. |
| Server config | Protection ships as `.htaccess` inside the folder. No vhost edits. |
| Timezone | `date_default_timezone_set()` runs per request, only on these pages. |

Rename the folder to anything you like (`schedule/`, `programme/`) — paths
resolve from `__DIR__`, so it works wherever it sits.

## Install

**1. Tables**

```bash
mysql -u USER -p summit < agenda/sql/schema.sql
mysql -u USER -p summit < agenda/sql/seed.sql
```

**2. Credentials** — two options, pick one.

*Reuse the site's existing connection.* If your current code defines
`DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS`, open `agenda/config.php` and
uncomment the require near the top, pointing at your bootstrap:

```php
require_once __DIR__ . '/../includes/config.php';
```

The module inherits those and defines nothing that clashes.

*Or set them directly* — edit the fallbacks in `config.php`, or export
`FCS_DB_HOST`, `FCS_DB_NAME`, `FCS_DB_USER`, `FCS_DB_PASS`.

**3. Uploads folder**

```bash
chmod 755 agenda/uploads agenda/uploads/speakers
chown www-data:www-data agenda/uploads/speakers
```

**4. First account**

No SSH (Hostinger's lower plans, most shared hosting) — open
`/agenda/setup.php` in a browser. It checks the environment, creates the Owner
account, then locks itself. Delete the file afterwards.

With SSH:

```bash
cd agenda && php bin/create-owner.php "Your Name" you@fcrf.in
rm bin/create-owner.php
```

**On Hostinger, follow `HOSTINGER-SETUP.md` instead — it walks through hPanel,
phpMyAdmin and File Manager step by step.**

**5. Check** — open `/agenda/`. The schedule should render with the live
rail at the top. Then `/agenda/admin.php` and sign in.

## Linking it from the existing site

Add to your current nav:

```html
<a href="/agenda/">Agenda</a>
```

The page is standalone by design — its own `<head>`, fonts and styles — so
it will not inherit or fight your existing stylesheet. If you would rather
it sat inside your site template, include your header just after `<body>` in
`agenda/index.php` and drop the module's own `.hero` block.

## Reusing your existing speaker images

Photos uploaded through the CMS go to `agenda/uploads/speakers/`. To point at
images you already have, store a **site-absolute** path in `photo_path` — it
resolves from the domain root, not from the module:

```sql
UPDATE agenda_speakers
   SET photo_path = '/assets/img/speakers26/rakshit-tandon.webp'
 WHERE slug = 'dr-rakshit-tandon';
```

Worth renaming that folder from `speakers'26` first — the apostrophe breaks
URLs and needs escaping everywhere it appears.

## Before the agenda goes public

1. **Settings → switch off "Show the admin dot".** The floating dot vanishes
   from `/agenda/`; `admin.php` keeps working. No code edit, no redeploy.
2. **HTTPS.** Session cookies are marked `secure` automatically when HTTPS is
   detected. Without it the login travels in the clear.
3. **Confirm the upload folder cannot execute PHP.** Put a test file at
   `agenda/uploads/speakers/test.php` containing `<?php echo 1;` and request
   it. You want a 403 or the raw source — never `1`. Delete it afterwards.
4. Publish what you want visible. Everything imported as `draft` stays hidden
   until you publish it.

## If you need to remove it

```bash
rm -rf agenda/
mysql -u USER -p summit -e "DROP TABLE agenda_session_speakers, agenda_sessions,
  agenda_speakers, agenda_versions, agenda_deleted_items, agenda_audit_logs,
  agenda_user_permissions, agenda_role_permissions, agenda_user_sessions,
  agenda_login_attempts, agenda_users, agenda_permissions, agenda_roles,
  agenda_tracks, agenda_halls, agenda_days, agenda_settings;"
```

Nothing else in the database or the site is affected.

## Two things to keep in mind

**Session descriptions render as HTML.** That is what makes the rich-text
editor useful, and it also means anyone with `agenda.edit` can inject script
into the public page. Fine for a small trusted team; if volunteers get the
Editor role, sanitise `description` before storing it.

**Speaker photos are re-encoded through GD on upload.** That strips EXIF —
including GPS coordinates from a phone photo — and guarantees the stored file
is a real image rather than a script wearing a `.jpg` extension.

## Roles

| Role | Gets |
|---|---|
| Owner | Everything, including appointing another Owner |
| Super Admin | Everything except Owner controls |
| Admin | Agenda, speakers, users; no permanent deletion, no settings |
| Editor | Create and edit content; no deletes, no user management |
| Viewer | Read only |

Per-person overrides sit on top of the role: `deny` beats a role grant,
`allow` beats a role's silence. Nobody can act on an account at or above
their own rank except the Owner.
