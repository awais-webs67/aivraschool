# Installing & Updating Skooline — cPanel / hPanel and Localhost

Skooline is **SaaS**: AivraSol runs **one** installation, and every school uses it through its own address
(`yourdomain.com/greenfield`). Schools never install anything. When we update the code, every school gets the update at once.

| Method | Use it for |
|---|---|
| **A. Git + automatic deploy** (recommended) | Our live server. `git push` to `main` → tests run → server updates itself. |
| **B. ZIP upload + SQL import** | Hosting without SSH, or a quick demo install. Works like any CodeCanyon PHP script. |
| **C. Localhost** | Development and testing on your own computer. |

---

## A. Recommended: Git + automatic deploy (Hostinger / cPanel with SSH)

Hostinger Premium/Business/Cloud and most cPanel hosts include SSH, Git and Composer.

### One-time server setup
1. **Database:** create one MySQL database + user in the panel. You don't need to import the SQL file here; migrations create the tables.
2. **SSH in** (hPanel → Advanced → SSH Access shows host, port and user) and run:
   ```bash
   cd ~/domains/yourdomain.com                 # cPanel: cd ~
   git clone https://github.com/awais-webs67/aivraschool.git skooline
   cd skooline
   composer2 install --no-dev --optimize-autoloader   # or "composer" on cPanel
   cp .env.example .env && nano .env           # APP_ENV=production, APP_DEBUG=false, APP_URL, DB_*
   php artisan key:generate
   php artisan migrate --force && php artisan db:seed --class=PlanSeeder --force
   ```
   For a private repo, add the server's public key (`ssh-keygen -t ed25519`, then `cat ~/.ssh/id_ed25519.pub`) to GitHub → repo → **Settings → Deploy keys**, and clone with `git@github.com:awais-webs67/aivraschool.git`.
3. **Point the website at `skooline/public`:**
   - hPanel main domain: `mv public_html public_html_old && ln -s skooline/public public_html`
   - cPanel: Domains → set the document root to `skooline/public`
4. Open `https://yourdomain.com/admin` → **Create Super Admin**.
5. Add the cron job (Step 6 below).

### Turn on automatic deploys
1. On your computer, create a key pair just for deploys: `ssh-keygen -t ed25519 -f skooline-deploy -N ""`.
   Add `skooline-deploy.pub` to the server: hPanel → **SSH Access → SSH keys**, or append it to `~/.ssh/authorized_keys` on cPanel.
2. GitHub → repo → **Settings → Secrets and variables → Actions → New secret**:

   | Secret | Value |
   |---|---|
   | `DEPLOY_HOST` | server IP / host from the SSH page |
   | `DEPLOY_PORT` | SSH port (Hostinger shows it on the SSH page; often `65002`) |
   | `DEPLOY_USER` | SSH username (e.g. `u123456789`) |
   | `DEPLOY_PATH` | full project path, e.g. `/home/u123456789/domains/yourdomain.com/skooline` |
   | `DEPLOY_KEY` | contents of the private key file `skooline-deploy` |

From now on, every `git push` to `main` does this automatically (`.github/workflows/deploy.yml` → `scripts/deploy.sh`):
1. Runs all tests. If any test fails, **nothing is deployed**.
2. Puts the site in maintenance mode for a few seconds.
3. Pulls the new code and installs packages.
4. Applies database changes with migrations. **Existing schools' data is kept.**
5. Refreshes caches and brings the site back.

You can also run it by hand on the server: `bash scripts/deploy.sh`.

### Rules that keep updates safe
- Work on a branch, open a PR, merge into `main` only when CI is green. `main` is always what's live.
- Never edit files on the server. Every change goes through Git.
- Database changes are **always new migration files**, never edits to old ones, so every update applies on top of real data.
- Before risky releases, back up the database (see "Backups" below).

---

## B. ZIP upload + SQL import (no SSH)

Skooline installs like any PHP script: **upload files → create one MySQL database → import the SQL file → edit `.env` → open `/admin`.**
No SSH, Composer or Node.js is needed on the server.

**Requirements:** PHP **8.3+** with `pdo_mysql`, `mbstring`, `openssl`, `intl`, `gd`, `zip`, `fileinfo` (all standard on cPanel/hPanel) and MySQL 5.7+/8 or MariaDB 10.4+.

---

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

### Updating a ZIP install
1. Back up the database (phpMyAdmin → Export) and your `.env`.
2. Upload the new ZIP and extract it over the old files. Keep your `.env` and the `storage/` folder.
3. Open **Super Admin → System → Run updates**. This applies any database changes; existing data is kept.

---

---

## C. Localhost (Windows, macOS, Linux)

Install **PHP 8.3+, Composer and Git**. On Windows the easiest option is [Laravel Herd](https://herd.laravel.com/windows) (PHP + Composer included) + [Git for Windows](https://git-scm.com/download/win). XAMPP/Laragon with PHP 8.3+ also work.

```bash
git clone -b claude/confident-noether-uavff0 https://github.com/awais-webs67/aivraschool.git skooline
cd skooline
composer run setup        # packages, .env, SQLite database, tables, plans, Super Admin, 2 demo schools
php artisan serve
```

| Open | URL | Login |
|---|---|---|
| Website | http://localhost:8000 | — |
| Super Admin | http://localhost:8000/admin | `admin@skooline.test` / `password123` |
| Greenfield (paid, Standard) | http://localhost:8000/greenfield | `admin@greenfield.test` / `password` |
| Riverside (trial, Premium) | http://localhost:8000/riverside | `admin@riverside.test` / `password` |

These logins are for localhost only.

| When | Command |
|---|---|
| Get the latest changes | `composer run refresh` (git pull + packages + database changes) |
| Start over with fresh demo data | `composer run reset-demo` (deletes all local data) |
| Run the tests | `php artisan test` |

### Localhost with XAMPP (htdocs + phpMyAdmin)

The project can live in `C:\xampp\htdocs\aivraschool` and open at `http://localhost/aivraschool`. The root `index.php` + `.htaccess` make it work from a subfolder.

1. **PHP must be 8.3 or newer.** XAMPP Control Panel → **Shell** → `php -v`. If it shows 8.2 or lower, install a newer XAMPP, or use Laragon/Herd.
2. Install **[Composer for Windows](https://getcomposer.org/Composer-Setup.exe)**. When it asks for PHP, choose `C:\xampp\php\php.exe`.
3. Start **Apache** and **MySQL** in the XAMPP Control Panel.
4. **phpMyAdmin** (`http://localhost/phpmyadmin`) → **New** → database name `skooline`, collation `utf8mb4_unicode_ci` → **Create**. Then **Import** → `database/skooline.sql` → **Go**.
5. In the XAMPP **Shell**:
   ```bash
   cd C:\xampp\htdocs\aivraschool
   composer install
   copy .env.example .env
   ```
   If Composer says an extension is missing, open `C:\xampp\php\php.ini`, remove the `;` before `extension=intl` / `extension=zip`, and restart Apache.
6. Open `.env` in a text editor and set:
   ```dotenv
   APP_URL=http://localhost/aivraschool
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=skooline
   DB_USERNAME=root
   DB_PASSWORD=
   ```
7. Open **http://localhost/aivraschool/admin** → **Create Super Admin**.
   Optional demo schools: `php artisan db:seed --class=DemoSeeder` (Greenfield and Riverside, password `password`).

| Open | URL |
|---|---|
| Website | http://localhost/aivraschool |
| Super Admin | http://localhost/aivraschool/admin |
| Greenfield | http://localhost/aivraschool/greenfield |

**Getting updates:** `git pull`, then `composer install`, then **Super Admin → System → Run updates** (or `php artisan migrate`).

### Notes

Localhost uses SQLite by default when set up with `composer run setup` (no database server needed). To use MySQL instead, create a database, import `database/skooline.sql`, and set the `DB_*` lines in `.env`.

Change styles or scripts? Run `npm install && npm run build` (Node 22) and commit `public/build`.

---

## Backups

Everything Skooline knows lives in **two places**: the **database** and the **`storage/` folder** (uploaded files). Plus your **`.env`** (settings and passwords).
- Turn on the host's automatic daily backups (Hostinger and most cPanel hosts include them).
- Before big updates: phpMyAdmin → select database → **Export** → Quick → SQL. Also download `.env`.

---

## Moving to a new host (e.g. after a year)

Nothing in Skooline is tied to one hosting company. Moving takes about an hour, and schools' URLs stay the same if the domain stays the same.

1. **Old host:** turn on maintenance mode (`php artisan down`, or just do it at night).
2. **Export the database:** phpMyAdmin → Export → SQL. This one file holds **all schools, users, plans and subscriptions**.
3. **Download** `.env` and the `storage/app` folder (uploaded files).
4. **New host:** install with method **A** (git clone) or **B** (ZIP). **Skip** `database/skooline.sql`, and instead **import your exported SQL** into the new empty database.
5. Put your old `.env` in place and change only the `DB_*` lines to the new database's details. **Keep the same `APP_KEY`.**
6. Upload `storage/app` back. Add the cron job. If using auto-deploy, update the `DEPLOY_*` secrets in GitHub.
7. Change the domain's DNS (or nameservers) to the new host and turn on SSL. Done: every school opens at the same address with all its data.

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

## Speed: make pages load fast

Laravel reads many PHP files on every click. **OPcache** keeps them ready in memory, which makes pages several times faster. Super Admin → System shows whether it is on.

**XAMPP (Windows):** open `C:\xampp\php\php.ini`, find these lines, remove the `;` in front and set the values, then restart Apache:

```
zend_extension=opcache
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0
realpath_cache_size=4096K
realpath_cache_ttl=600
```

**cPanel / hPanel:** OPcache is usually on already. If System shows "Off", enable the `opcache` extension under *Select PHP Version* (cPanel) or *PHP Configuration → PHP extensions* (hPanel).

On the live server keep `APP_ENV=production` and `APP_DEBUG=false` in `.env`. "Run updates" then also pre-builds the configuration and page templates. After editing `.env` on the live server, press **Run updates** once so the change is picked up.
