# Installing on Hostinger

Written for hPanel, no SSH needed. Roughly 15 minutes.

Everything lives in one folder. Nothing outside `agenda/` is touched, and no
existing table, file or session is modified.

---

## Step 1 — Set PHP to 8.2

**hPanel → Websites → Manage → Advanced → PHP Configuration**

Set the PHP version to **8.2** and save. On the *PHP extensions* tab, make sure
`pdo_mysql` and `gd` are ticked (`gd` handles speaker photo uploads).

This has to come first. On PHP 8.0 the pages will not even parse.

---

## Step 2 — Database

**hPanel → Databases → Management**

You can reuse the database your registration forms already write to — the new
tables are all prefixed `agenda_` and cannot collide with `fcrf_hackathon_2026`
or `fcrf_defence_passes`. If you'd rather keep it separate, create a new one.

Either way, write down three things. Hostinger prefixes names with your account
number, so they look like this:

```
Database name:  u123456789_summit
Database user:  u123456789_dev
Password:       (the one you set)
Host:           localhost
```

**Import the tables.** Click **Enter phpMyAdmin** next to the database.

1. Confirm the correct database is selected in the left sidebar — the import
   goes into whatever is highlighted there.
2. **Import** tab → **Choose File** → `sql/schema.sql` → **Go**.
   You should see roughly 17 tables created plus the seed rows for roles and
   permissions.
3. **Import** again → `sql/seed.sql` → **Go**.
   This loads the sessions and speakers from your planning sheet.

If step 3 reports errors about missing tables, step 2 did not finish — check
the selected database and run it again.

---

## Step 3 — Upload the files

**hPanel → Files → File Manager**

1. Open `public_html`.
2. Upload `agenda.zip`.
3. Right-click it → **Extract** → extract into `public_html`.
   You end up with `public_html/agenda/`.
4. Delete `agenda.zip`.

Check that hidden files came through: click the **eye / Show hidden files**
toggle. You should see `.htaccess` inside `agenda/` and another inside
`agenda/uploads/speakers/`. Those two files are what stop the support folders
being readable and stop uploaded files from executing — if the extract dropped
them, create them by hand from the copies in this package.

---

## Step 4 — Credentials

In File Manager, open `agenda/config.php` and edit the four fallback values
near the top:

```php
$fcsHost = getenv('FCS_DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
$fcsName = getenv('FCS_DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'u123456789_summit');
$fcsUser = getenv('FCS_DB_USER') ?: (defined('DB_USER') ? DB_USER : 'u123456789_dev');
$fcsPass = getenv('FCS_DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : 'your-password');
```

Replace the last value in each line — the part after the final `:`. Leave the
rest alone; it lets the module inherit credentials automatically if you ever
wire it into a shared bootstrap.

Save.

---

## Step 5 — Run setup

Open **`https://summit.futurecrime.org/agenda/setup.php`**

You get a checklist: PHP version, database connection, tables, uploads folder.
Everything should be green. If something is not, the page says what to change.

Fill in your name, email and a password of at least 10 characters, and create
the Owner account.

**Then delete `setup.php`** — File Manager, right-click, Delete. The page locks
itself once an account exists, but there is no reason to leave it there.

---

## Step 6 — Check it

| URL | What you should see |
|---|---|
| `/agenda/` | The schedule, with a small dot in the top-right corner |
| `/agenda/admin.php` | Sign-in, then the console |
| `/agenda/lib/core.php` | **404** — if you see PHP source, `.htaccess` did not upload |
| `/agenda/config.php` | **403 or 404** — never the file contents |

That third row matters. If `lib/core.php` renders as text, stop and fix the
`.htaccess` before going further.

---

## Step 7 — Permissions

In File Manager, right-click `agenda/uploads/speakers` → **Permissions** → set
to **755**. Try uploading a speaker photo from the console; if it fails, try
775.

Do not set anything to 777 on shared hosting.

---

## Step 8 — Before it goes public

1. Sign in → **Settings** → switch off **"Show the admin dot on the agenda
   page"**. The dot disappears from `/agenda/`; `admin.php` still works.
2. Confirm HTTPS is on (hPanel → Security → SSL). Session cookies only get the
   `secure` flag over HTTPS.
3. Publish the sessions you want visible. Everything unconfirmed imported as
   **draft** and stays hidden until you publish it.
4. Link it from your site menu: `<a href="/agenda/">Agenda</a>`

---

## Hostinger-specific notes

**LiteSpeed, not Apache.** Hostinger runs LiteSpeed. The usual
`php_flag engine off` trick for locking down an uploads folder throws a 500
error there, so the `.htaccess` in this package wraps it in a module check and
relies on `RemoveHandler` and a `FilesMatch` deny instead. Do not paste in a
generic snippet from a tutorial — it will take the site down.

**Argon2id may be missing.** Some Hostinger PHP builds ship without it. The
code detects that and falls back to bcrypt at cost 12, which is still solid.
The setup page tells you which one is in use.

**No `exec`, no cron for this.** Nothing here needs a scheduled job. Trash
retention is enforced by a date column, not a cleanup task.

**Backups.** hPanel → Files → Backups makes a database snapshot. Take one
before importing, so an import mistake is a two-click rollback.

---

## If something goes wrong

**Blank white page.** PHP error display is off. hPanel → Advanced → PHP
Configuration → turn on `display_errors` temporarily, reload, read the message,
turn it back off.

**"Database connection failed" on setup.php.** The name or user is wrong.
Hostinger's are always prefixed with `u` and your account number — check the
exact strings in Databases → Management.

**Import stops partway.** Usually the wrong database was selected in the
phpMyAdmin sidebar. Drop the `agenda_*` tables and start again — the uninstall
command in `README.md` has the full DROP list.

**500 error after upload.** Nearly always `.htaccess`. Rename it to
`htaccess.txt`, reload, and if the site returns, the directives are the
problem — tell me and I will adjust them for your stack.
