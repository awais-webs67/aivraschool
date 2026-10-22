# Installing Skooline — cPanel / hPanel and Localhost

Skooline installs like any PHP script: **upload files → create one MySQL database → import the SQL file → edit `.env` → open `/admin`.**
No SSH, Composer or Node.js is needed on the server.

**Requirements:** PHP **8.3+** with `pdo_mysql`, `mbstring`, `openssl`, `intl`, `gd`, `zip`, `fileinfo` (all standard on cPanel/hPanel) and MySQL 5.7+/8 or MariaDB 10.4+.

---

## 1. cPanel / hPanel (shared hosting)

### Step 1 — Get the upload package
Download **`skooline-<version>.zip`**. It already contains `vendor/` and `database/skooline.sql`.
- GitHub → **Actions → Release ZIP → Run workflow**, then download the artifact. Or attach it to a release by pushing a tag like `v0.2.0`.
- Or build it yourself on any computer with PHP + Composer: `bash scripts/make-release.sh` → `dist/skooline-<version>.zip`

### Step 2 — Upload
Choose **one** layout:

| Layout | How | When |
|---|---|---|
| **A. Into `public_html`** (simplest) | File Manager → `public_html` → Upload the ZIP → Extract → move everything from the `skooline` folder up into `public_html` | Main domain, any panel. The root `.htaccess` keeps app files private. |
| **B. Outside `public_html`** (cleaner) | Extract to e.g. `/home/USER/skooline`, then set the domain/subdomain's **document root** to `/home/USER/skooline/public` (cPanel → Domains; hPanel → subdomain / addon domain folder) | When the panel lets you choose the folder |

In hPanel, select **PHP 8.3** under **Advanced → PHP Configuration**. In cPanel, use **Select PHP Version / MultiPHP Manager**.

### Step 3 — Database
1. cPanel → **MySQL® Databases**, or hPanel → **Databases → Management**: create a database and a user, and give the user **all privileges** on it.
2. Open **phpMyAdmin** → select the database → **Import** → choose `database/skooline.sql` → **Go**.
   This creates all tables and the default plans. No accounts are included.

### Step 4 — Configure `.env`
In File Manager, copy **`.env.example`** to **`.env`** and edit these lines:
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_skooline
DB_USERNAME=u123456789_skooline
DB_PASSWORD=your-database-password
```
Leave `APP_KEY=` empty. Skooline generates it and saves it to `.env` on the first visit.
On hPanel the database name and user start with your account prefix (`u123456789_`). Copy them exactly from the panel.

### Step 5 — Create your Super Admin
Open **`https://yourdomain.com/admin`**. On a fresh install it shows **Create Super Admin**. Enter your name, email and password.
That page closes for good once the first Super Admin exists.

### Step 6 — Cron job (once)
Add **one** cron job that runs **every minute**. The exact command for your server is shown in **Super Admin → System**:
```
cd /home/USER/public_html && php artisan schedule:run >> /dev/null 2>&1
```
- cPanel: **Cron Jobs** → Common settings "Once Per Minute".
- hPanel: **Advanced → Cron Jobs**.

It expires overdue trials and subscriptions every night and sends queued messages.

### Done
| URL | What |
|---|---|
| `https://yourdomain.com` | Website |
| `https://yourdomain.com/admin` | Super Admin panel |
| `https://yourdomain.com/greenfield` | Portal of a school added with address `greenfield` |

Schools need **no** subdomains or DNS changes. Each one opens at its own path.

### Updating to a new version
1. Back up the database (phpMyAdmin → Export) and your `.env`.
2. Upload the new ZIP and extract it over the old files. Keep your `.env` and the `storage/` folder.
3. Open **Super Admin → System → Run updates**. This applies any database changes; existing data is kept.

---

## 2. Localhost (Windows, macOS, Linux)

Install **PHP 8.3+, Composer and Git**. On Windows the easiest option is [Laravel Herd](https://herd.laravel.com/windows) (PHP + Composer included). XAMPP/Laragon with PHP 8.3+ also work.

```bash
git clone https://github.com/awais-webs67/aivraschool.git skooline
cd skooline
composer run setup        # installs packages, creates .env + SQLite database, tables, plans and a Super Admin
php artisan serve
```
The Super Admin email and password are printed at the end of `composer run setup`. To choose them, set `SUPER_ADMIN_EMAIL` / `SUPER_ADMIN_PASSWORD` in `.env` first.

| Open | URL |
|---|---|
| Website | http://localhost:8000 |
| Super Admin | http://localhost:8000/admin |
| A school with address `greenfield` | http://localhost:8000/greenfield |

Localhost uses SQLite by default (no database server needed). To use MySQL locally instead (XAMPP/Laragon), create a database, import `database/skooline.sql`, and set the `DB_*` lines in `.env` as in Step 4.

Change styles or scripts? Run `npm install && npm run build` (Node 22) and commit `public/build`.

---

## 3. With SSH (optional, any server)
```bash
git clone https://github.com/awais-webs67/aivraschool.git skooline && cd skooline
composer install --no-dev --optimize-autoloader
cp .env.example .env            # set APP_URL and DB_* as in Step 4
php artisan key:generate
php artisan migrate --force     # instead of importing the SQL file
php artisan config:cache
```
Later updates: `bash scripts/deploy.sh` (pulls code, installs packages, runs migrations, caches config).

---

## Troubleshooting
| Problem | Fix |
|---|---|
| 500 error | Check `storage/logs/laravel.log`. Usually the PHP version is below 8.3 or the `DB_*` values are wrong. |
| "Permission denied" in the log | Make `storage/` and `bootstrap/cache/` writable (755 folders / 644 files are fine on cPanel/hPanel). |
| A file list or 403 instead of the website | The request isn't reaching `public/`. Use layout B, or make sure the root `.htaccess` was uploaded (hidden files must be shown in File Manager). |
| `/admin` asks to create a Super Admin again | The `platform_users` table is empty, so the SQL was imported into a different database than the one in `.env`. |
| A school address shows "404" | No school has that address. Check it in Super Admin → Schools. |
| `.env` changes don't apply | Delete `bootstrap/cache/config.php` if it exists. |
