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

## Environment

Copy `.env.example` to `.env` and set:
```
ANTHROPIC_API_KEY=sk-ant-...
```

The app uses SQLite by default (`database/database.sqlite`). No database server is needed.

## Architecture

This is a single-page Laravel chatbot that proxies requests to the Claude API (Anthropic).

**Request flow:**
1. User sends a message in the browser (Alpine.js `chatApp()` component in `resources/views/chat.blade.php`)
2. Alpine posts the full conversation history + optional system prompt to `POST /chat/send`
3. `ChatController::send()` (`app/Http/Controllers/ChatController.php`) forwards the messages array directly to `https://api.anthropic.com/v1/messages` using Laravel's `Http` facade
4. The controller extracts text from the response `content` blocks and returns `{ message: "..." }`
5. Alpine appends the assistant reply and scrolls to the bottom

**Key design decisions:**
- The full `messages` array is sent on every request — the client holds conversation state, not the server. There is no session storage or database persistence for chat history.
- The model is hardcoded to `claude-haiku-4-5-20251001` in `ChatController::send()`.
- The system prompt is user-configurable at runtime via a collapsible config panel in the UI; it defaults to `'You are a helpful, friendly AI assistant. Be concise and clear.'`
- Tailwind CSS and Alpine.js are loaded from CDN in `chat.blade.php` — the Vite pipeline (`resources/css/app.css`, `resources/js/app.js`) exists but is not wired into the chat view.
- The Anthropic API key is accessed via `config('services.anthropic.key')` → `ANTHROPIC_API_KEY` in `.env`.
