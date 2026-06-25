# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

**Full dev environment** (server + queue + logs + Vite HMR, all in parallel):
```bash
composer dev
```

**Individual services:**
```bash
php artisan serve          # Laravel dev server
npm run dev                # Vite asset bundler with HMR
php artisan queue:listen --tries=1 --timeout=0
php artisan pail --timeout=0   # log viewer
```

**Build assets:**
```bash
npm run build
```

**Run tests:**
```bash
composer test              # clears config cache then runs phpunit
php artisan test --filter=TestName   # run a single test
```

**Code style (Laravel Pint):**
```bash
./vendor/bin/pint
```

**First-time setup:**
```bash
composer setup             # install, .env, key:generate, migrate, npm install, build
```

**Ingest sales reports (CLI):**
```bash
php artisan sales:ingest --client=my-slug        # associate with a client
php artisan sales:ingest --path=/custom/dir --client=1
```

## Environment

Copy `.env.example` to `.env` and set:
```
ANTHROPIC_API_KEY=sk-ant-...
# Optional overrides:
# ANTHROPIC_MODEL=claude-sonnet-4-6
# UPLOAD_MAX_FILE_KB=10240
# UPLOAD_MAX_FILES_PER_CLIENT=50
```

The app uses SQLite by default (`database/database.sqlite`). No database server is needed.

## Architecture

Multi-tenant, login-gated sales-reporting chatbot backed by the Claude API.

**Tenancy model:**
- A `Client` (tenant) owns its `User`s, `SalesReport`s, `Conversation`s/`Message`s, and branding.
- Tenant is resolved from `auth()->user()->client_id` — no URL slugs.
- Every DB query that returns tenant data is scoped by `client_id`.
- Admin users (`is_admin = true`) access the `/admin` area to provision clients and users.

**Request flow (chat):**
1. User logs in (Laravel Breeze, Blade stack). `/` redirects to login if unauthenticated.
2. `ChatController::index()` loads the latest conversation + history from the DB.
3. Alpine.js `chatApp()` (`resources/views/chat.blade.php`) posts the history + `conversation_id` to `POST /chat/send`.
4. `ChatController::send()` reads the system prompt from `client->system_prompt` (not from the request), scopes report lookups to the client, runs the tool-use loop against `https://api.anthropic.com/v1/messages`, persists each turn into `messages`, and returns `{ message, conversation_id }`.
5. Alpine renders the assistant reply as sanitized markdown (via `marked` + `DOMPurify`).

**Key files:**
- `app/Http/Controllers/ChatController.php` — chat + tool-use loop + persistence
- `app/Http/Controllers/ConversationController.php` — list/load/delete conversations
- `app/Http/Controllers/ReportUploadController.php` — authenticated report uploads
- `app/Http/Controllers/Admin/` — admin CRUD (clients, users, reports)
- `app/Services/ReportIngestionService.php` — shared PDF/DOCX/XLSX parser
- `app/Models/{Client,User,SalesReport,Conversation,Message}.php`
- `resources/views/chat.blade.php` — main chatbot UI (Vite, Alpine, marked/DOMPurify)
- `resources/views/admin/` — admin Blade views
- `resources/views/reports/` — report upload UI
- `config/uploads.php` — file size and count limits
- `config/services.php` — `anthropic.key` and `anthropic.model`

**Key design decisions:**
- Model defaults to `claude-haiku-4-5-20251001`; override with `ANTHROPIC_MODEL` env.
- System prompt is server-side only (from `client->system_prompt`); the browser cannot change it.
- Conversations persist server-side (`conversations` + `messages` tables); history is loaded on page open.
- Report files stored privately under `storage/app/reports/{client_id}/`, never web-served.
- Assets (Tailwind, Alpine, marked, DOMPurify) are bundled via Vite — no CDN dependencies.
- Public registration is disabled; users are admin-provisioned only.
- Rate limiter: 20 chat requests per minute per user (`throttle:chat`).
- Upload limits enforced in `UploadReportRequest` (FormRequest) and re-checked in controller.
- `QUEUE_CONNECTION=sync` — no persistent queue workers needed (suitable for cPanel shared hosting).
