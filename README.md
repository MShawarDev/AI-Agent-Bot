# AI Agent Bot — Sales Assistant

A multi-tenant, login-gated sales-reporting chatbot backed by the Claude API. Clients upload PDF/DOCX/XLSX reports; their users query the data through a conversational interface powered by Claude.

## Prerequisites

- PHP 8.3+
- Composer
- Node.js 18+ and npm
- An [Anthropic API key](https://console.anthropic.com/)
- SQLite **or** MySQL 5.7+ / MariaDB

## Quick Start (fresh clone)

### 1. Run the one-command setup

```bash
composer setup
```

This installs PHP and Node dependencies, copies `.env.example` → `.env`, generates an app key, runs all migrations, and builds frontend assets.

### 2. Configure your database

**Option A — SQLite (zero-config, default)**

Create the database file (it is gitignored):

```bash
# Linux / macOS
touch database/database.sqlite

# Windows (PowerShell)
New-Item -ItemType File database/database.sqlite
```

Ensure `.env` contains:

```env
DB_CONNECTION=sqlite
```

**Option B — MySQL**

Create the database in MySQL first:

```sql
CREATE DATABASE chatbot;
```

Then set in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=chatbot
DB_USERNAME=root
DB_PASSWORD=
```

After choosing your database, run migrations:

```bash
php artisan migrate
```

### 3. Set your Anthropic API key

Open `.env` and fill in:

```env
ANTHROPIC_API_KEY=sk-ant-...
```

Optional overrides:

```env
ANTHROPIC_MODEL=claude-sonnet-4-6      # default: claude-haiku-4-5-20251001
UPLOAD_MAX_FILE_KB=10240               # max file size in KB (default 10 MB)
UPLOAD_MAX_FILES_PER_CLIENT=50         # max reports per client
```

### 4. Create the first admin user

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'name'     => 'Admin',
    'email'    => 'admin@example.com',
    'password' => bcrypt('your-password'),
    'is_admin' => true,
]);
```

### 5. Start the development server

```bash
composer dev
```

This runs Laravel, the queue worker, and Vite HMR all in parallel. Open [http://localhost:8000](http://localhost:8000) and log in with the admin credentials you just created.

## Admin Area

Visit `/admin` to:
- Create and manage **Clients** (tenants)
- Provision **Users** per client (public registration is disabled)
- Upload and manage **Sales Reports** per client

Regular users are created by the admin and can only see data belonging to their own client.

## Development Commands

| Command | Description |
|---|---|
| `composer dev` | Start all services (server + queue + Vite HMR) |
| `composer test` | Clear config cache and run PHPUnit |
| `npm run build` | Build production assets |
| `./vendor/bin/pint` | Fix code style (Laravel Pint) |
| `php artisan test --filter=TestName` | Run a single test |

### Ingest sales reports via CLI

```bash
php artisan sales:ingest --client=my-slug
php artisan sales:ingest --path=/custom/dir --client=1
```

## Architecture Overview

- **Multi-tenancy:** each `Client` owns its `User`s, `SalesReport`s, and `Conversation`s. Tenant is resolved from the authenticated user's `client_id`.
- **Chat flow:** Alpine.js frontend → `POST /chat/send` → tool-use loop against Claude API → response rendered as sanitized Markdown.
- **Storage:** report files live under `storage/app/reports/{client_id}/` and are never web-served directly.
- **Queue:** `QUEUE_CONNECTION=sync` — no persistent queue workers needed (cPanel-friendly).
- **Rate limit:** 20 chat requests per minute per user.
