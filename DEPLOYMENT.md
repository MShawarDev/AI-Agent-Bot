# Deployment Guide — Hostinger Shared Hosting

## Prerequisites

| Requirement | Notes |
|---|---|
| Hostinger plan | Business Hosting or above (SSH access required) |
| PHP 8.3+ | Set in hPanel → Advanced → PHP Configuration |
| MySQL database | Created in hPanel → Databases → MySQL Databases |
| Domain / subdomain | Pointing to the correct document root |
| Git + SSH access | On your local machine |

---

## 1. First-time server setup (do once)

### 1a. Set the document root

In **hPanel → Websites → your domain → Manage → Files → Document Root**, set the path to:

```
/home/<username>/<app-folder>/public
```

> **Why:** Laravel's entry point is `public/index.php`. Pointing the root one level up exposes all your PHP source files to the web.

### 1b. Configure PHP 8.3

hPanel → Advanced → PHP Configuration → select **8.3** and enable these extensions:
- `mbstring`, `pdo_mysql`, `fileinfo`, `gd`, `zip`, `xml`, `openssl`

### 1c. Create the MySQL database

hPanel → Databases → MySQL Databases:
1. Create a database (e.g. `u123456_chatbot`)
2. Create a user with a strong password
3. Assign the user to the database (All Privileges)

Note the host (`127.0.0.1`), database name, username, and password.

### 1d. Add your SSH public key to Hostinger

hPanel → Advanced → SSH Access → Manage SSH Keys → Add Key.

Paste in your **public** key (`~/.ssh/id_rsa.pub` or your key manager's export).

---

## 2. First manual deployment

SSH into the server and clone the repository or manually upload the files:

```bash
ssh -p 65002 <username>@<host>
cd /home/<username>/
git clone https://github.com/your-org/your-repo.git chatbot
cd chatbot
```

> **Note:** If git is not available, run the GitHub Actions workflow (step 4) after creating the `.env` file below.

### 2a. Create the `.env` file

```bash
cp .env.example .env
nano .env        # or use hPanel File Manager
```

Fill in every value (see the full `.env.example` below for all variables). At minimum:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=              # fill in after step 2b

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u123456_chatbot
DB_USERNAME=u123456_chatbot_user
DB_PASSWORD=your-strong-password

ANTHROPIC_API_KEY=sk-ant-...
```

### 2b. Generate the app key

```bash
php artisan key:generate
```

### 2c. Run migrations

```bash
php artisan migrate --force
```

### 2d. Create a symlink for public storage

```bash
php artisan storage:link
```

### 2e. Cache config / routes / views

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 3. Set up GitHub secrets

In your GitHub repo → **Settings → Secrets and variables → Actions**, add:

| Secret name | Value | Example |
|---|---|---|
| `SSH_HOST` | Hostinger SSH hostname | `srv12345.hostinger.com` |
| `SSH_USER` | SSH username | `u123456` |
| `SSH_PORT` | SSH port (usually 65002 on Hostinger) | `65002` |
| `SSH_PRIVATE_KEY` | Full content of your private key | `-----BEGIN OPENSSH...` |
| `DEPLOY_PATH` | Absolute path to the app root on the server | `/home/u123456/chatbot` |

> **`SSH_PORT`:** Hostinger typically uses port **65002** for SSH (not 22). Check hPanel → Advanced → SSH Access for the exact port.

---

## 4. Running a deployment

Go to **GitHub → Actions → Deploy to Hostinger → Run workflow**:

| Input | What it does |
|---|---|
| `run_migrations` | Check ✓ only when you have new migrations (risky on live data — test first) |
| `clear_caches` | Uncheck only if you're doing a hotfix and know caches are valid |

The workflow will:
1. Check out the code
2. Install Composer dependencies (production only, no dev packages)
3. Install Node deps and build frontend assets with Vite
4. rsync all files to the server (skipping `.env`, `.git`, `node_modules`, SQLite, logs)
5. Run artisan cache commands via SSH
6. Optionally run migrations

---

## 5. Production `.env` reference

```dotenv
APP_NAME="Sales Assistant"
APP_ENV=production
APP_KEY=base64:...          # php artisan key:generate
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=error             # only log errors in production

# ── Database ─────────────────────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u123456_chatbot
DB_USERNAME=u123456_chatbot
DB_PASSWORD=your-strong-password

# ── Sessions ─────────────────────────────────────────────────────────────────
SESSION_DRIVER=database     # more reliable than file on shared hosting
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true  # requires HTTPS

# ── Cache ────────────────────────────────────────────────────────────────────
CACHE_STORE=database        # or 'file' if DB queries are limited

# ── Queue ────────────────────────────────────────────────────────────────────
QUEUE_CONNECTION=sync       # no persistent worker needed on shared hosting

# ── Anthropic API ────────────────────────────────────────────────────────────
ANTHROPIC_API_KEY=sk-ant-...
# ANTHROPIC_MODEL=claude-haiku-4-5-20251001  # default; uncomment to override

# ── Upload limits ────────────────────────────────────────────────────────────
UPLOAD_MAX_FILE_KB=10240          # 10 MB per file
UPLOAD_MAX_FILES_PER_CLIENT=50
```

---

## 6. Cron (optional — for scheduled tasks)

If you add Laravel scheduled commands, add a cron via hPanel → Advanced → Cron Jobs:

```
* * * * * php /home/<username>/chatbot/artisan schedule:run >> /dev/null 2>&1
```

---

## 7. Troubleshooting

| Symptom | Fix |
|---|---|
| White screen / 500 | Set `APP_DEBUG=true` temporarily, check `storage/logs/laravel.log` |
| Migrations fail | Confirm `DB_*` vars and that the MySQL user has privileges |
| Assets 404 | Run `npm run build` locally, re-deploy, or check hPanel PHP version |
| SSE streaming buffered | Normal on Apache shared hosting — the JS falls back to the blocking endpoint automatically |
| `storage/` not writable | Run `chmod -R 775 storage bootstrap/cache` on the server |
| `php artisan` not found | Use the full path: `/usr/local/bin/php8.3 artisan …` |

---

## 8. Rolling back

Deployments use `rsync --delete` which means reverting to a previous commit means:

```bash
# Local
git checkout <previous-sha>
# Then run the GitHub Actions workflow again — it will sync the old files
```

Or via SSH on the server:

```bash
cd /home/<username>/chatbot
git fetch && git checkout <previous-sha>
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
