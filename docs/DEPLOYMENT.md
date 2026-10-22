# Running Skooline — Localhost & Hostinger (live preview)

Skooline needs: **PHP 8.3+** (with `pdo_sqlite`/`pdo_mysql`, `mbstring`, `intl`, `openssl`), **Composer 2**, **Git**.
Node.js is **not** required to run it: the compiled CSS/JS is committed in `public/build`.
You only need Node 22 if you change styles or scripts (`npm install && npm run build`).

> The code currently lives on the branch `claude/confident-noether-uavff0`.
> Clone that branch, or merge it into `main` first and clone `main`.

---

## 1. Localhost (Windows, macOS, Linux)

### Install the tools (once)
- **Windows:** install [Laravel Herd](https://herd.laravel.com/windows) (it includes PHP + Composer) and [Git](https://git-scm.com/download/win).
  XAMPP or Laragon also work if their PHP is 8.3 or newer.
- **macOS:** Laravel Herd, or `brew install php composer git`.
- **Linux:** `sudo apt install php8.3-cli php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-intl php8.3-curl composer git`.

Check: `php -v` shows 8.3 or newer, and `composer -V` shows 2.x.

### Get it running
```bash
git clone -b claude/confident-noether-uavff0 https://github.com/awais-webs67/aivraschool.git skooline
cd skooline
composer run setup        # installs packages, creates .env + SQLite DB, migrates, seeds plans + Super Admin
php artisan serve
```
`composer run setup` prints the **Super Admin email and password** at the end. Save them.
To pick your own password, set `SUPER_ADMIN_PASSWORD=...` in `.env` before running setup.

| Open | URL |
|---|---|
| Website | http://localhost:8000 |
| Super Admin | http://localhost:8000/admin |
| A school with subdomain `greenfield` | http://greenfield.localhost:8000/login |

`*.localhost` addresses work automatically in Chrome, Edge and Firefox. If a browser can't open them,
add a line `127.0.0.1 greenfield.localhost` to your hosts file
(Windows: `C:\Windows\System32\drivers\etc\hosts`, run Notepad as Administrator).

Each new school's database is a file in `database/` named `skooline_<id>`.

---

## 2. Hostinger hPanel (live preview)

### Important: shared hosting vs VPS
Skooline creates a **new database automatically for every school**.
- On **shared / cloud hosting**, databases are normally created by hand in hPanel, and the MySQL user is unlikely to be allowed to create new databases from code. Run `SHOW GRANTS FOR CURRENT_USER();` in phpMyAdmin to check. So for a preview on shared hosting, use **SQLite**: the central DB and every school DB are files inside the project. This works with no database setup.
- For **production with real schools**, use a **Hostinger VPS** with MySQL 8, where Skooline can create school databases itself. Wildcard subdomains and wildcard SSL are also straightforward there. Hostinger documents wildcard SSL as VPS-only.

### Step 1 — Prepare hPanel
1. **Websites → Manage → Advanced → PHP Configuration:** select **PHP 8.3** (or newer).
2. **Advanced → SSH Access:** enable SSH and note the host, port, user and password.
   SSH is available on Premium, Business and Cloud plans.

### Step 2 — Get the code onto the server

**Option A: hPanel Git (simplest)**
1. **Advanced → Git**. Repository: `git@github.com:awais-webs67/aivraschool.git`, Branch: `claude/confident-noether-uavff0` (or `main` after merging), Directory: leave empty to use `public_html`. The folder must be empty, so delete Hostinger's default `default.php` first.
2. If the repository is private, hPanel shows an **SSH key**. In GitHub open the repo **Settings → Deploy keys → Add deploy key**, paste the key (read-only), then click **Create** in hPanel.
3. The included root `.htaccess` sends every request to `public/`, so `.env`, `vendor` and the app code are never reachable from the browser.

**Option B: SSH (cleaner: code outside `public_html`)**
```bash
cd ~/domains/YOURDOMAIN.com
git clone -b claude/confident-noether-uavff0 https://github.com/awais-webs67/aivraschool.git skooline
mv public_html public_html_old            # keep a backup of the default folder
ln -s skooline/public public_html         # the website now serves Skooline's public folder
```
For a private repo, clone over SSH with a deploy key, or use a GitHub personal access token.

### Step 3 — Install (SSH)
```bash
cd ~/domains/YOURDOMAIN.com/public_html      # Option A
# or: cd ~/domains/YOURDOMAIN.com/skooline   # Option B

composer -V            # if this shows 1.x, use "composer2" in the commands below
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
nano .env              # edit the values below, then save
touch database/database.sqlite
php artisan migrate --seed --force           # prints the Super Admin login
php artisan config:cache
```

Values to set in `.env` for the preview:
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOURDOMAIN.com
CENTRAL_DOMAINS=YOURDOMAIN.com
DB_CONNECTION=sqlite
SESSION_SECURE_COOKIE=true
SUPER_ADMIN_EMAIL=you@aivrasol.com
SUPER_ADMIN_PASSWORD=choose-a-strong-password
```
Open `https://YOURDOMAIN.com` for the website and `https://YOURDOMAIN.com/admin` for the Super Admin panel.

### Step 4 — School subdomains
Every school opens on `schoolname.YOURDOMAIN.com`. All of them must point at the **same** Skooline `public` folder:
- **If hPanel allows a wildcard subdomain** (`*`), create it under **Domains → Subdomains** and point it to the Skooline folder. New schools then work immediately.
- **Otherwise**, after adding a school in the Super Admin panel, create that subdomain in **Domains → Subdomains** (e.g. `greenfield`) and set its folder to:
  - Option A: `public_html/public`
  - Option B: `domains/YOURDOMAIN.com/skooline/public`
- Turn on the free SSL for each subdomain under **Security → SSL**.

### Step 5 — Daily subscription check (cron)
**Advanced → Cron Jobs**, run every minute:
```
cd /home/YOURUSER/domains/YOURDOMAIN.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```
(Use the `skooline` folder path for Option B.) This expires overdue trials and subscriptions every night.

### Updating the live site later
After new code is pushed to GitHub:
```bash
cd ~/domains/YOURDOMAIN.com/public_html     # or the skooline folder
bash scripts/deploy.sh                       # COMPOSER=composer2 bash scripts/deploy.sh if needed
```
The script pulls the code, installs packages, migrates the central DB **and every school DB**, and refreshes caches.
The **Deploy** button in hPanel's Git page only pulls code, so still run the script afterwards.

### Troubleshooting
| Problem | Fix |
|---|---|
| 500 error right after setup | `php artisan config:clear`, check `storage/logs/laravel.log`, make sure PHP is 8.3+ |
| Website shows a file list or 403 | Document root isn't reaching `public/`; use Option B or check the root `.htaccess` was uploaded |
| School subdomain shows Hostinger's default page | The subdomain's folder isn't Skooline's `public` folder |
| "Tenant could not be identified" | That subdomain doesn't belong to any school; check the school's subdomain in Super Admin |
| Changes to `.env` not applied | Run `php artisan config:cache` again |
