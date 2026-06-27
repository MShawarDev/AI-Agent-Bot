# Glassmorphism Redesign + Per-Client Theming Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign every page with a glassmorphism aesthetic and add full per-client theming (accent + secondary color, light/dark mode, background style).

**Architecture:** A theming engine resolves a client's theme into CSS custom properties injected into `<html>`; Tailwind reads those vars so `bg-brand`, `ring-accent/30`, etc. are theme-driven. A small library of reusable glass Blade components (`resources/views/components/ui/*`) is built once, then every page is refactored onto it. Existing Breeze components are restyled in place so nothing breaks during migration.

**Tech Stack:** Laravel (Blade), Tailwind CSS 3 (`class` dark mode), Alpine.js, Vite. SQLite, PHPUnit feature tests.

## Global Constraints

- **No CDN dependencies** — all CSS/JS bundled via Vite.
- **No backend/tenancy changes** — system prompt, chat tool-use loop, and `client_id` scoping are untouched. This is presentation + a theming data layer only.
- **Responsive / mobile-first** — every page works from ~320px up: no horizontal scroll, tap targets ≥44px, sidebars collapse to drawers, wide tables become stacked cards on narrow widths.
- **Dark & light first-class** — both fully styled. Driven by client `theme_mode` (`light`/`dark`/`auto`); `auto` follows `prefers-color-scheme`. Tailwind `class` strategy with a pre-paint inline script (no flash). No per-user toggle. Contrast meets WCAG AA in both modes.
- **Respect `prefers-reduced-motion`** — disable non-essential animation under it.
- **Theme values are never hardcoded** — colors come from `var(--brand)` / `var(--accent)` (Tailwind `brand`/`accent` colors) or neutral slate tokens. No literal brand hex in components/pages.
- **Run tests with** `composer test`; a single test with `php artisan test --filter=Name`. Code style: `./vendor/bin/pint` before each commit.
- **Default theme** (used on guest/auth pages and when a client leaves fields blank): brand `#4f46e5`, accent `#06b6d4`, mode `light`, bg `mesh`.

---

## File Structure

**New files:**
- `config/theme.php` — default theme values.
- `app/Support/Theme.php` — resolves a client (or defaults) into theme vars; hex→rgb helper.
- `resources/views/components/theme-style.blade.php` — pre-paint dark script + `:root` CSS vars (`<x-theme-style>`).
- `resources/views/components/app-background.blade.php` — fixed animated mesh/aurora/dots/solid background layer.
- `resources/views/components/ui/glass-card.blade.php`
- `resources/views/components/ui/btn.blade.php`
- `resources/views/components/ui/field.blade.php`
- `resources/views/components/ui/badge.blade.php`
- `resources/views/components/ui/avatar.blade.php`
- `resources/views/components/ui/empty-state.blade.php`
- `resources/views/components/ui/page-header.blade.php`
- `resources/views/components/ui/section-heading.blade.php`
- `resources/views/components/ui/stat-card.blade.php`
- `tests/Feature/ThemeTest.php`, `tests/Feature/PageRenderTest.php`, `tests/Feature/Admin/ClientThemeTest.php`

**Modified files:**
- `database/migrations/2026_06_27_000000_add_theme_fields_to_clients_table.php` (new migration)
- `app/Models/Client.php` (fillable)
- `app/Http/Controllers/Admin/ClientController.php` (validation)
- `tailwind.config.js`, `resources/css/app.css`
- `resources/views/layouts/app.blade.php`, `guest.blade.php`, `navigation.blade.php`
- Breeze components: `primary-button`, `secondary-button`, `danger-button`, `text-input`, `input-label`, `input-error`, `dropdown`, `modal`, `nav-link`, `responsive-nav-link`
- All page views under `resources/views/{auth,admin,reports,profile}`, `dashboard.blade.php`, `chat.blade.php`, `admin/clients/_form.blade.php`

---

## Test Helper Convention

Tests create a tenant inline (no Client factory exists). Pattern used throughout:

```php
use App\Models\Client;
use App\Models\User;

$client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
$user   = User::factory()->create(['client_id' => $client->id]);
$admin  = User::factory()->create(['client_id' => $client->id, 'is_admin' => true]);
```

`User::factory()` exists (Breeze); default password is `password`.

---

# Phase 1 — Theming Foundation

### Task 1: Theme fields migration + model

**Files:**
- Create: `database/migrations/2026_06_27_000000_add_theme_fields_to_clients_table.php`
- Modify: `app/Models/Client.php:10-20` (fillable array)
- Test: `tests/Feature/ThemeTest.php`

**Interfaces:**
- Produces: `clients.accent_color` (string,nullable), `clients.theme_mode` (string, default `light`), `clients.bg_style` (string, default `mesh`). `Client` mass-assignable for all three.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_persists_theme_fields(): void
    {
        $client = Client::create([
            'name' => 'Acme', 'slug' => 'acme',
            'accent_color' => '#06b6d4', 'theme_mode' => 'dark', 'bg_style' => 'aurora',
        ]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id, 'accent_color' => '#06b6d4',
            'theme_mode' => 'dark', 'bg_style' => 'aurora',
        ]);
    }
}
```

- [ ] **Step 2: Run test, verify it fails**

Run: `php artisan test --filter=test_client_persists_theme_fields`
Expected: FAIL — unknown column `accent_color`.

- [ ] **Step 3: Create the migration**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('accent_color', 20)->nullable()->after('brand_color');
            $table->string('theme_mode', 10)->default('light')->after('accent_color');
            $table->string('bg_style', 10)->default('mesh')->after('theme_mode');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['accent_color', 'theme_mode', 'bg_style']);
        });
    }
};
```

- [ ] **Step 4: Add fields to `Client::$fillable`**

In `app/Models/Client.php`, add to the `$fillable` array (after `'brand_color'`):

```php
        'accent_color',
        'theme_mode',
        'bg_style',
```

- [ ] **Step 5: Migrate and run the test**

Run: `php artisan migrate && php artisan test --filter=test_client_persists_theme_fields`
Expected: PASS.

- [ ] **Step 6: Pint + commit**

```bash
./vendor/bin/pint
git add database/migrations app/Models/Client.php tests/Feature/ThemeTest.php
git commit -m "feat: add per-client theme fields (accent, mode, bg)"
```

---

### Task 2: Theme resolver + config defaults

**Files:**
- Create: `config/theme.php`, `app/Support/Theme.php`
- Test: `tests/Feature/ThemeTest.php` (add methods)

**Interfaces:**
- Produces:
  - `config('theme.defaults')` → `['brand'=>'#4f46e5','accent'=>'#06b6d4','mode'=>'light','bg'=>'mesh']`
  - `App\Support\Theme::forClient(?Client $c): array` → keys `brand, accent, mode, bg, brand_rgb, accent_rgb` (rgb = space-separated `"R G B"`).
  - `App\Support\Theme::current(): array` → `forClient(auth()->user()?->client)`.

- [ ] **Step 1: Write the failing test**

```php
    public function test_theme_falls_back_to_defaults(): void
    {
        $theme = \App\Support\Theme::forClient(null);
        $this->assertSame('#4f46e5', $theme['brand']);
        $this->assertSame('79 70 229', $theme['brand_rgb']);
        $this->assertSame('mesh', $theme['bg']);
    }

    public function test_theme_uses_client_values_and_accent_defaults_to_brand(): void
    {
        $client = Client::create([
            'name' => 'Acme', 'slug' => 'acme',
            'brand_color' => '#ff0000', 'theme_mode' => 'dark',
        ]);
        $theme = \App\Support\Theme::forClient($client);
        $this->assertSame('#ff0000', $theme['brand']);
        $this->assertSame('255 0 0', $theme['brand_rgb']);
        $this->assertSame('#ff0000', $theme['accent']); // accent blank -> brand
        $this->assertSame('dark', $theme['mode']);
    }
```

- [ ] **Step 2: Run, verify fail**

Run: `php artisan test --filter=ThemeTest`
Expected: FAIL — class `App\Support\Theme` not found.

- [ ] **Step 3: Create `config/theme.php`**

```php
<?php
return [
    'defaults' => [
        'brand'  => '#4f46e5',
        'accent' => '#06b6d4',
        'mode'   => 'light',
        'bg'     => 'mesh',
    ],
];
```

- [ ] **Step 4: Create `app/Support/Theme.php`**

```php
<?php

namespace App\Support;

use App\Models\Client;

class Theme
{
    public static function forClient(?Client $client): array
    {
        $d      = config('theme.defaults');
        $brand  = self::clean($client?->brand_color) ?: $d['brand'];
        $accent = self::clean($client?->accent_color) ?: $brand;
        $mode   = in_array($client?->theme_mode, ['light', 'dark', 'auto'], true) ? $client->theme_mode : $d['mode'];
        $bg     = in_array($client?->bg_style, ['mesh', 'aurora', 'solid', 'dots'], true) ? $client->bg_style : $d['bg'];

        return [
            'brand'      => $brand,
            'accent'     => $accent,
            'mode'       => $mode,
            'bg'         => $bg,
            'brand_rgb'  => self::hexToRgb($brand),
            'accent_rgb' => self::hexToRgb($accent),
        ];
    }

    public static function current(): array
    {
        return self::forClient(auth()->user()?->client);
    }

    private static function clean(?string $hex): ?string
    {
        $hex = trim((string) $hex);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? strtolower($hex) : null;
    }

    private static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r   = hexdec(substr($hex, 0, 2));
        $g   = hexdec(substr($hex, 2, 2));
        $b   = hexdec(substr($hex, 4, 2));

        return "$r $g $b";
    }
}
```

- [ ] **Step 5: Run, verify pass**

Run: `php artisan test --filter=ThemeTest`
Expected: PASS (all three).

- [ ] **Step 6: Pint + commit**

```bash
./vendor/bin/pint
git add config/theme.php app/Support/Theme.php tests/Feature/ThemeTest.php
git commit -m "feat: theme resolver with defaults and hex->rgb"
```

---

### Task 3: Tailwind tokens + glass utilities

**Files:**
- Modify: `tailwind.config.js`, `resources/css/app.css`

**Interfaces:**
- Produces: Tailwind colors `brand` / `accent` (alpha-aware, read from `--brand-rgb` / `--accent-rgb`); `darkMode: 'class'`; CSS component classes `.glass`, `.glass-strong`, `.glass-input`; keyframes `aurora`, `fade-up`.

- [ ] **Step 1: Replace `tailwind.config.js`**

```js
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: 'rgb(var(--brand-rgb) / <alpha-value>)',
                accent: 'rgb(var(--accent-rgb) / <alpha-value>)',
            },
            borderRadius: {
                '2xl': '1.125rem',
                '3xl': '1.5rem',
            },
            boxShadow: {
                glass: '0 10px 30px -12px rgb(2 6 23 / 0.25)',
                'glow': '0 0 0 1px rgb(var(--brand-rgb) / 0.25), 0 8px 30px -8px rgb(var(--brand-rgb) / 0.45)',
            },
            keyframes: {
                aurora: {
                    '0%,100%': { transform: 'translate3d(0,0,0) scale(1)' },
                    '50%': { transform: 'translate3d(4%, -4%, 0) scale(1.15)' },
                },
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                aurora: 'aurora 18s ease-in-out infinite',
                'fade-up': 'fade-up 0.5s ease-out both',
            },
        },
    },
    plugins: [forms],
};
```

- [ ] **Step 2: Replace `resources/css/app.css`**

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
    :root { --brand-rgb: 79 70 229; --accent-rgb: 6 182 212; }
    html { scroll-behavior: smooth; }
    body { @apply bg-slate-50 text-slate-800 dark:bg-slate-950 dark:text-slate-100; }
}

@layer components {
    .glass {
        @apply rounded-2xl border backdrop-blur-xl shadow-glass;
        background-color: rgb(255 255 255 / 0.72);
        border-color: rgb(255 255 255 / 0.6);
    }
    .dark .glass {
        background-color: rgb(15 23 42 / 0.55);
        border-color: rgb(255 255 255 / 0.08);
    }
    .glass-strong { @apply glass; background-color: rgb(255 255 255 / 0.88); }
    .dark .glass-strong { background-color: rgb(15 23 42 / 0.78); }
    .glass-input {
        @apply w-full rounded-xl border bg-white/70 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm transition;
        @apply focus:border-brand focus:ring-2 focus:ring-brand/30 focus:outline-none;
        @apply dark:bg-white/5 dark:text-slate-100 dark:border-white/10;
        border-color: rgb(148 163 184 / 0.4);
    }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
}
```

- [ ] **Step 3: Build to verify it compiles**

Run: `npm run build`
Expected: build completes with no errors; `public/build/manifest.json` updated.

- [ ] **Step 4: Pint (no-op for JS/CSS) + commit**

```bash
git add tailwind.config.js resources/css/app.css
git commit -m "feat: tailwind theme tokens, glass utilities, dark mode"
```

---

### Task 4: `<x-theme-style>` + `<x-app-background>` components

**Files:**
- Create: `resources/views/components/theme-style.blade.php`, `resources/views/components/app-background.blade.php`
- Test: `tests/Feature/ThemeTest.php` (add a render test via a temporary route is overkill — assert through a real page in Task wiring). Add unit-style assertion below.

**Interfaces:**
- Produces: `<x-theme-style />` outputs a pre-paint `<script>` that toggles `.dark` on `<html>` from the resolved mode, plus `<style>:root{--brand-rgb;--accent-rgb;--brand;--accent}</style>`. `<x-app-background />` renders a fixed, `aria-hidden` background layer switching on `Theme::current()['bg']`.

- [ ] **Step 1: Create `resources/views/components/theme-style.blade.php`**

```blade
@php($theme = \App\Support\Theme::current())
<script>
    (function () {
        var mode = @json($theme['mode']);
        var dark = mode === 'dark' || (mode === 'auto' &&
            window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
    })();
</script>
<style>
    :root {
        --brand-rgb: {{ $theme['brand_rgb'] }};
        --accent-rgb: {{ $theme['accent_rgb'] }};
        --brand: {{ $theme['brand'] }};
        --accent: {{ $theme['accent'] }};
    }
</style>
```

- [ ] **Step 2: Create `resources/views/components/app-background.blade.php`**

```blade
@php($bg = \App\Support\Theme::current()['bg'])
<div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute inset-0 bg-slate-50 dark:bg-slate-950"></div>

    @if($bg === 'solid')
        <div class="absolute inset-0 bg-gradient-to-b from-brand/5 to-transparent"></div>
    @elseif($bg === 'dots')
        <div class="absolute inset-0 opacity-[0.4] dark:opacity-[0.25]"
             style="background-image: radial-gradient(rgb(var(--brand-rgb) / 0.18) 1px, transparent 1px); background-size: 22px 22px;"></div>
    @else {{-- mesh / aurora --}}
        <div class="absolute -top-32 -left-24 h-[28rem] w-[28rem] rounded-full bg-brand/30 blur-3xl
                    {{ $bg === 'aurora' ? 'animate-aurora' : '' }}"></div>
        <div class="absolute top-1/3 -right-24 h-[26rem] w-[26rem] rounded-full bg-accent/30 blur-3xl
                    {{ $bg === 'aurora' ? 'animate-aurora' : '' }}" style="animation-delay: -6s;"></div>
        <div class="absolute -bottom-32 left-1/4 h-[24rem] w-[24rem] rounded-full bg-brand/20 blur-3xl
                    {{ $bg === 'aurora' ? 'animate-aurora' : '' }}" style="animation-delay: -12s;"></div>
    @endif
</div>
```

- [ ] **Step 3: Add a render assertion to `ThemeTest`**

```php
    public function test_theme_style_component_renders_brand_vars(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme', 'brand_color' => '#ff0000']);
        $user   = User::factory()->create(['client_id' => $client->id]);

        $html = $this->actingAs($user)->blade('<x-theme-style />');

        $html->assertSee('255 0 0', false);
    }
```

> If `$this->blade()` is unavailable, assert via the dashboard page in Task 15 instead; this assertion may be moved there.

- [ ] **Step 4: Run, verify pass**

Run: `php artisan test --filter=ThemeTest`
Expected: PASS.

- [ ] **Step 5: Pint + commit**

```bash
./vendor/bin/pint
git add resources/views/components/theme-style.blade.php resources/views/components/app-background.blade.php tests/Feature/ThemeTest.php
git commit -m "feat: theme-style and app-background blade components"
```

---

# Phase 2 — Component Library

> Each component is an anonymous Blade component. After creating all of them, Task 12+ migrate pages onto them. Components must use only `brand`/`accent`/slate tokens.

### Task 5: `ui/glass-card`

**Files:** Create `resources/views/components/ui/glass-card.blade.php`

**Interfaces:** Produces `<x-ui.glass-card :padded="true" class="...">…</x-ui.glass-card>`. Props: `padded` (bool, default true).

- [ ] **Step 1: Create the component**

```blade
@props(['padded' => true])
<div {{ $attributes->class([
        'glass animate-fade-up',
        'p-5 sm:p-6' => $padded,
    ]) }}>
    {{ $slot }}
</div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/components/ui/glass-card.blade.php
git commit -m "feat(ui): glass-card component"
```

---

### Task 6: `ui/btn` + restyle Breeze buttons

**Files:**
- Create: `resources/views/components/ui/btn.blade.php`
- Modify: `resources/views/components/primary-button.blade.php`, `secondary-button.blade.php`, `danger-button.blade.php`

**Interfaces:** Produces `<x-ui.btn variant="primary|ghost|danger" :href="null" type="submit">`. Renders `<a>` when `href` set, else `<button>`.

- [ ] **Step 1: Create `ui/btn.blade.php`**

```blade
@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-transparent disabled:opacity-50 disabled:pointer-events-none';
    $variants = [
        'primary' => 'bg-brand text-white shadow-glow hover:brightness-110 focus:ring-brand/40',
        'ghost'   => 'border border-slate-300/70 bg-white/60 text-slate-700 hover:bg-white dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10 focus:ring-brand/30',
        'danger'  => 'bg-rose-600 text-white shadow-sm hover:bg-rose-500 focus:ring-rose-400/40',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
```

- [ ] **Step 2: Restyle `primary-button.blade.php`**

```blade
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-glow transition hover:brightness-110 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-brand/40']) }}>
    {{ $slot }}
</button>
```

- [ ] **Step 3: Restyle `secondary-button.blade.php`**

```blade
<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300/70 bg-white/60 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-white active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-brand/30 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10']) }}>
    {{ $slot }}
</button>
```

- [ ] **Step 4: Restyle `danger-button.blade.php`**

```blade
<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-500 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-rose-400/40']) }}>
    {{ $slot }}
</button>
```

- [ ] **Step 5: Build to verify, commit**

Run: `npm run build` → Expected: success.

```bash
git add resources/views/components/ui/btn.blade.php resources/views/components/primary-button.blade.php resources/views/components/secondary-button.blade.php resources/views/components/danger-button.blade.php
git commit -m "feat(ui): btn component + restyle breeze buttons"
```

---

### Task 7: `ui/field` + restyle inputs

**Files:**
- Create: `resources/views/components/ui/field.blade.php`
- Modify: `resources/views/components/text-input.blade.php`, `input-label.blade.php`, `input-error.blade.php`

**Interfaces:** Produces `<x-ui.field name="email" label="Email" :value="old('email')" type="email" required />` and slot mode `<x-ui.field name="x" label="X">…custom control…</x-ui.field>`.

- [ ] **Step 1: Restyle `text-input.blade.php`**

```blade
@props(['disabled' => false])
<input @disabled($disabled) {{ $attributes->merge(['class' => 'glass-input']) }}>
```

- [ ] **Step 2: Restyle `input-label.blade.php`**

```blade
@props(['value'])
<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-slate-600 dark:text-slate-300']) }}>
    {{ $value ?? $slot }}
</label>
```

- [ ] **Step 3: Restyle `input-error.blade.php`**

```blade
@props(['messages'])
@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-1 space-y-1 text-sm text-rose-500']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
```

- [ ] **Step 4: Create `ui/field.blade.php`**

```blade
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false])
<div class="space-y-1.5">
    @if($label)
        <x-input-label :for="$name" :value="$label" />
    @endif
    @if($slot->isNotEmpty())
        {{ $slot }}
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
               value="{{ $value }}" @required($required)
               {{ $attributes->merge(['class' => 'glass-input']) }}>
    @endif
    <x-input-error :messages="$errors->get($name)" />
</div>
```

- [ ] **Step 5: Build + commit**

Run: `npm run build` → success.

```bash
git add resources/views/components/ui/field.blade.php resources/views/components/text-input.blade.php resources/views/components/input-label.blade.php resources/views/components/input-error.blade.php
git commit -m "feat(ui): field component + restyle inputs"
```

---

### Task 8: `ui/badge`, `ui/avatar`, `ui/empty-state`

**Files:** Create the three components.

**Interfaces:**
- `<x-ui.badge color="brand|emerald|rose|slate">Active</x-ui.badge>`
- `<x-ui.avatar :name="$user->name" :src="null" />` — initials fallback, brand bg.
- `<x-ui.empty-state title="..." message="..."><x-slot:icon>…svg…</x-slot:icon><x-slot:action>…</x-slot:action></x-ui.empty-state>`

- [ ] **Step 1: `ui/badge.blade.php`**

```blade
@props(['color' => 'slate'])
@php
    $map = [
        'brand'   => 'bg-brand/10 text-brand ring-brand/20',
        'emerald' => 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/20 dark:text-emerald-400',
        'rose'    => 'bg-rose-500/10 text-rose-600 ring-rose-500/20 dark:text-rose-400',
        'slate'   => 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:text-slate-300',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset', $map[$color] ?? $map['slate']]) }}>
    {{ $slot }}
</span>
```

- [ ] **Step 2: `ui/avatar.blade.php`**

```blade
@props(['name' => '?', 'src' => null])
@php
    $initials = collect(explode(' ', trim($name)))->filter()->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp
@if($src)
    <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->class(['rounded-xl object-cover']) }}>
@else
    <span {{ $attributes->class(['inline-flex items-center justify-center rounded-xl bg-brand font-semibold text-white']) }}>
        {{ $initials ?: '?' }}
    </span>
@endif
```

- [ ] **Step 3: `ui/empty-state.blade.php`**

```blade
@props(['title', 'message' => null])
<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300/70 px-6 py-12 text-center dark:border-white/10">
    @isset($icon)
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand/10 text-brand">{{ $icon }}</div>
    @endisset
    <h3 class="text-base font-semibold text-slate-700 dark:text-slate-200">{{ $title }}</h3>
    @if($message)<p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400">{{ $message }}</p>@endif
    @isset($action)<div class="mt-5">{{ $action }}</div>@endisset
</div>
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/ui/badge.blade.php resources/views/components/ui/avatar.blade.php resources/views/components/ui/empty-state.blade.php
git commit -m "feat(ui): badge, avatar, empty-state components"
```

---

### Task 9: `ui/page-header`, `ui/section-heading`

**Files:** Create both.

**Interfaces:**
- `<x-ui.page-header title="Reports" subtitle="..."><x-slot:actions>…</x-slot:actions></x-ui.page-header>`
- `<x-ui.section-heading>Appearance</x-ui.section-heading>`

- [ ] **Step 1: `ui/page-header.blade.php`**

```blade
@props(['title', 'subtitle' => null])
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between animate-fade-up">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white sm:text-3xl">{{ $title }}</h1>
        @if($subtitle)<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
</div>
```

- [ ] **Step 2: `ui/section-heading.blade.php`**

```blade
<h2 {{ $attributes->class(['flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400']) }}>
    <span class="h-4 w-1 rounded-full bg-brand"></span>{{ $slot }}
</h2>
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/ui/page-header.blade.php resources/views/components/ui/section-heading.blade.php
git commit -m "feat(ui): page-header and section-heading"
```

---

### Task 10: `ui/stat-card` with animated counter

**Files:** Create `resources/views/components/ui/stat-card.blade.php`

**Interfaces:** `<x-ui.stat-card label="Reports" :value="42" icon-color="brand"><x-slot:icon>…svg…</x-slot:icon></x-ui.stat-card>`. Value animates from 0 (Alpine), respects reduced motion via CSS (animation off) — counter still lands on final value.

- [ ] **Step 1: Create the component**

```blade
@props(['label', 'value' => 0, 'iconColor' => 'brand'])
<div class="glass flex items-center gap-4 p-5 animate-fade-up"
     x-data="{ shown: 0, target: {{ (int) $value }} }"
     x-init="
        let reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) { shown = target; return; }
        let start = performance.now(), dur = 900;
        let tick = (t) => { let p = Math.min((t - start) / dur, 1);
            shown = Math.round(p * target); if (p < 1) requestAnimationFrame(tick); };
        requestAnimationFrame(tick);
     ">
    @isset($icon)
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-brand">{{ $icon }}</div>
    @endisset
    <div>
        <p class="text-2xl font-bold tabular-nums text-slate-800 dark:text-white" x-text="shown">{{ (int) $value }}</p>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>
    </div>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/components/ui/stat-card.blade.php
git commit -m "feat(ui): stat-card with animated counter"
```

---

# Phase 3 — Layout Shell & Navigation

### Task 11: App layout shell

**Files:** Modify `resources/views/layouts/app.blade.php`

**Interfaces:** Consumes `<x-theme-style>`, `<x-app-background>`. Produces a themed shell: vars + dark script in `<head>`, background layer, sticky glass nav, `$header` slot, `@stack('styles')` preserved.

- [ ] **Step 1: Replace `layouts/app.blade.php`**

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <x-theme-style />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="font-sans antialiased">
        <x-app-background />
        <div class="flex min-h-screen flex-col">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-white/40 dark:border-white/5">
                    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
```

- [ ] **Step 2: Build + manual check**

Run: `npm run build`. Then `composer dev`, log in, confirm background blobs + no console errors. (Nav restyled next task.)

- [ ] **Step 3: Commit**

```bash
git add resources/views/layouts/app.blade.php
git commit -m "feat: glass app layout shell with themed background"
```

---

### Task 12: Navigation redesign

**Files:** Modify `resources/views/layouts/navigation.blade.php`; restyle `components/nav-link.blade.php`, `responsive-nav-link.blade.php`, `dropdown.blade.php`.

**Interfaces:** Sticky translucent glass nav; brand-pill nav links with active state; avatar dropdown; mobile drawer. Uses `<x-ui.avatar>`.

- [ ] **Step 1: Restyle `components/nav-link.blade.php`**

```blade
@props(['active' => false])
@php
    $classes = $active
        ? 'inline-flex items-center rounded-xl bg-brand/10 px-3.5 py-2 text-sm font-semibold text-brand transition'
        : 'inline-flex items-center rounded-xl px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-500/10 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white';
@endphp
<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
```

- [ ] **Step 2: Restyle `components/responsive-nav-link.blade.php`**

```blade
@props(['active' => false])
@php
    $classes = $active
        ? 'block rounded-xl bg-brand/10 px-3 py-2 text-base font-semibold text-brand'
        : 'block rounded-xl px-3 py-2 text-base font-medium text-slate-600 hover:bg-slate-500/10 dark:text-slate-300 dark:hover:text-white';
@endphp
<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
```

- [ ] **Step 3: Update `components/dropdown.blade.php` content panel classes**

Find the dropdown content `<div>` (the one with `class="absolute z-50 ..."`) and ensure its inner panel uses glass. Replace the content wrapper's class string `rounded-md ... bg-white` portion with:

```
rounded-2xl glass-strong ring-0 shadow-glass
```

(Keep the existing `x-show`, transition, and `:class` alignment attributes intact.)

- [ ] **Step 4: Replace `layouts/navigation.blade.php`**

```blade
<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-white/40 bg-white/70 backdrop-blur-xl dark:border-white/5 dark:bg-slate-950/60">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('chat') }}" class="flex items-center gap-2">
                        @if(auth()->user()?->client?->logo_path)
                            <img src="{{ Storage::url(auth()->user()->client->logo_path) }}" alt="" class="h-8 w-8 rounded-lg object-cover">
                        @else
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand text-white font-bold">{{ mb_substr(auth()->user()?->client?->name ?? 'A', 0, 1) }}</span>
                        @endif
                        <span class="hidden text-sm font-semibold text-slate-800 dark:text-white sm:block">{{ auth()->user()?->client?->bot_name ?? config('app.name') }}</span>
                    </a>
                </div>
                <div class="hidden space-x-1 sm:-my-px sm:ms-8 sm:flex sm:items-center">
                    <x-nav-link :href="route('chat')" :active="request()->routeIs('chat')">{{ __('Chat') }}</x-nav-link>
                    <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">{{ __('Reports') }}</x-nav-link>
                    @if(auth()->user()?->is_admin)
                        <x-nav-link :href="route('admin.clients.index')" :active="request()->routeIs('admin.clients.*')">{{ __('Admin') }}</x-nav-link>
                        <x-nav-link :href="route('admin.usage')" :active="request()->routeIs('admin.usage')">{{ __('Usage') }}</x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-xl px-2 py-1.5 text-sm text-slate-600 transition hover:bg-slate-500/10 dark:text-slate-300">
                            <x-ui.avatar :name="Auth::user()->name" class="h-8 w-8 text-xs" />
                            <span class="font-medium">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-500/10">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-white/40 dark:border-white/5 sm:hidden">
        <div class="space-y-1 px-3 pt-2 pb-3">
            <x-responsive-nav-link :href="route('chat')" :active="request()->routeIs('chat')">{{ __('Chat') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">{{ __('Reports') }}</x-responsive-nav-link>
            @if(auth()->user()?->is_admin)
                <x-responsive-nav-link :href="route('admin.clients.index')" :active="request()->routeIs('admin.clients.*')">{{ __('Admin') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.usage')" :active="request()->routeIs('admin.usage')">{{ __('Usage') }}</x-responsive-nav-link>
            @endif
        </div>
        <div class="border-t border-white/40 px-4 py-3 dark:border-white/5">
            <div class="font-medium text-slate-800 dark:text-white">{{ Auth::user()->name }}</div>
            <div class="text-sm text-slate-500">{{ Auth::user()->email }}</div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
```

- [ ] **Step 5: Build + commit**

Run: `npm run build` → success.

```bash
git add resources/views/layouts/navigation.blade.php resources/views/components/nav-link.blade.php resources/views/components/responsive-nav-link.blade.php resources/views/components/dropdown.blade.php
git commit -m "feat: glass navigation with avatar dropdown and mobile drawer"
```

---

### Task 13: Guest layout redesign

**Files:** Modify `resources/views/layouts/guest.blade.php`

**Interfaces:** Floating glass auth card over themed background; logo; uses default theme (no auth user).

- [ ] **Step 1: Replace `layouts/guest.blade.php`**

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <x-theme-style />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased dark:text-slate-100">
        <x-app-background />
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <a href="/" class="mb-6 flex items-center gap-2">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand text-2xl font-bold text-white shadow-glow">{{ mb_substr(config('app.name', 'A'), 0, 1) }}</span>
                <span class="text-xl font-bold tracking-tight">{{ config('app.name') }}</span>
            </a>
            <div class="w-full max-w-md">
                <div class="glass-strong animate-fade-up p-6 sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
```

- [ ] **Step 2: Build + commit**

Run: `npm run build` → success. Visit `/login` to confirm glass card renders.

```bash
git add resources/views/layouts/guest.blade.php
git commit -m "feat: glass guest layout"
```

---

# Phase 4 — Page Redesigns

> Each page task ends with a render smoke test (status 200 + a marker the redesign introduces). Add all page render tests to `tests/Feature/PageRenderTest.php`.

### Task 14: Auth pages

**Files:** Modify `resources/views/auth/{login,register,forgot-password,reset-password,verify-email,confirm-password}.blade.php`. Restyle `components/auth-session-status.blade.php`.

**Interfaces:** Each uses `<x-guest-layout>` (unchanged) and the restyled inputs/buttons. Add a heading block to each.

- [ ] **Step 1: Restyle `components/auth-session-status.blade.php`**

```blade
@props(['status'])
@if ($status)
    <div {{ $attributes->merge(['class' => 'mb-4 rounded-xl bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-600 dark:text-emerald-400']) }}>
        {{ $status }}
    </div>
@endif
```

- [ ] **Step 2: Update `auth/login.blade.php`** — add heading above the form, keep the existing `<form>` action/fields, wrap labels with restyled components. Prepend inside `<x-guest-layout>`:

```blade
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold tracking-tight">Welcome back</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Sign in to continue</p>
    </div>
```

Ensure the submit button uses `<x-primary-button class="w-full justify-center">` and the "Remember me"/links use `text-brand hover:underline` for anchors. Keep all field `name` attributes (`email`, `password`, `remember`) unchanged.

- [ ] **Step 3: Apply the same heading + full-width primary button pattern to the other five auth views**, with these headings:
  - `register.blade.php` → "Create your account" / "Get started in seconds"
  - `forgot-password.blade.php` → "Forgot password?" / keep existing explanatory paragraph, restyle to `text-sm text-slate-500`
  - `reset-password.blade.php` → "Reset password"
  - `verify-email.blade.php` → "Verify your email"
  - `confirm-password.blade.php` → "Confirm password"
  Keep every form `action`, `method`, `@csrf`, and field `name` unchanged. Replace any `text-indigo-*` / `text-gray-*` link classes with `text-brand` / `text-slate-500`.

- [ ] **Step 4: Add render smoke tests** to `tests/Feature/PageRenderTest.php`

```php
<?php
namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_redesign(): void
    {
        $this->get('/login')->assertStatus(200)->assertSee('Welcome back');
    }

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertStatus(200)->assertSee('Create your account');
    }
}
```

- [ ] **Step 5: Run, verify pass; build**

Run: `php artisan test --filter=PageRenderTest` → PASS. `npm run build` → success.

- [ ] **Step 6: Pint + commit**

```bash
./vendor/bin/pint
git add resources/views/auth resources/views/components/auth-session-status.blade.php tests/Feature/PageRenderTest.php
git commit -m "feat: redesign auth pages on glass guest layout"
```

---

### Task 15: Dashboard overview

**Files:** Modify `resources/views/dashboard.blade.php`

**Interfaces:** Real overview with stat cards. Note: `/dashboard` route exists from Breeze. Compute counts inline from the authed user's client (read-only).

- [ ] **Step 1: Replace `dashboard.blade.php`**

```blade
<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @php
            $client = auth()->user()->client;
            $reportCount = $client?->salesReports()->count() ?? 0;
            $convoCount  = \App\Models\Conversation::where('user_id', auth()->id())->count();
        @endphp

        <x-ui.page-header title="Welcome back, {{ auth()->user()->name }}" subtitle="Here's your workspace at a glance.">
            <x-slot:actions>
                <x-ui.btn :href="route('chat')" variant="primary">Open chat</x-ui.btn>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-ui.stat-card label="Sales reports" :value="$reportCount">
                <x-slot:icon><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6h6v6m-9 4h12a2 2 0 002-2V7l-5-4H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></x-slot:icon>
            </x-ui.stat-card>
            <x-ui.stat-card label="Conversations" :value="$convoCount">
                <x-slot:icon><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
            </x-ui.stat-card>
            <x-ui.glass-card class="flex flex-col justify-between">
                <div>
                    <x-ui.section-heading>Quick actions</x-ui.section-heading>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Jump back into your work.</p>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.btn :href="route('chat')" variant="ghost">Chat</x-ui.btn>
                    <x-ui.btn :href="route('reports.index')" variant="ghost">Reports</x-ui.btn>
                </div>
            </x-ui.glass-card>
        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 2: Add render test** to `PageRenderTest`

```php
    public function test_dashboard_renders_overview(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $user   = User::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)->get('/dashboard')->assertStatus(200)->assertSee('your workspace at a glance', false);
    }
```

- [ ] **Step 3: Run, verify pass; build; commit**

Run: `php artisan test --filter=test_dashboard_renders_overview` → PASS. `npm run build` → success.

```bash
./vendor/bin/pint
git add resources/views/dashboard.blade.php tests/Feature/PageRenderTest.php
git commit -m "feat: dashboard overview with stat cards"
```

---

### Task 16: Chat page

**Files:** Modify `resources/views/chat.blade.php`

**Interfaces:** Keep the entire `x-data="chatApp({...})"` wiring, all `x-` bindings, routes, and the `$history`/`$conversation`/`$client` usage **unchanged**. Restyle only the markup/classes: glass container, refined bubbles, glass starter-prompt cards, glass sticky composer. Remove the page-local `:root{--brand}` block (now global via `<x-theme-style>`); keep `[x-cloak]`, the `bounce-dot` keyframes, and `.prose-chat` styles (update table/code colors to use slate + dark variants).

- [ ] **Step 1: Update the `@push('styles')` block** — delete the `@if($client?->brand_color) … @endif` style block (lines ~27–32). Keep `[x-cloak]` and `bounce-dot`. Update `.prose-chat` borders/backgrounds to dark-aware values:

```css
        .prose-chat th, .prose-chat td   { border: 1px solid rgb(148 163 184 / 0.4); padding: 0.25rem 0.5rem; text-align: left; }
        .prose-chat th                   { background: rgb(148 163 184 / 0.15); }
        .prose-chat code                 { background: rgb(148 163 184 / 0.2); border-radius: 3px; padding: 0 3px; font-size: 0.85em; }
        .prose-chat pre                  { background: rgb(148 163 184 / 0.2); border-radius: 6px; padding: 0.5rem 0.75rem; overflow-x: auto; margin: 0.5rem 0; }
```

- [ ] **Step 2: Restyle the chat container** — change the container `<div>` (currently `bg-white rounded-2xl border border-slate-200 shadow-sm`) to `glass-strong flex flex-col overflow-hidden`. Keep the inline `style="height: calc(...)"`.

- [ ] **Step 3: Restyle the header bar** — change `bg-slate-50 rounded-t-2xl` to `border-white/40 dark:border-white/5 bg-white/40 dark:bg-white/5`. Keep logo/`brand-bg` block (now `bg-brand` via global var — replace `brand-bg {{ ... }}` and the `bg-indigo-600` fallback with a single `bg-brand`).

- [ ] **Step 4: Restyle message bubbles** — locate the assistant/user bubble classes in the Alpine `<template>` loop. User bubble: `bg-brand text-white`; assistant bubble: `bg-white/70 dark:bg-white/5 text-slate-800 dark:text-slate-100 border border-white/50 dark:border-white/10`. Keep rounded-2xl and the `prose-chat` class on assistant content.

- [ ] **Step 5: Restyle starter-prompt buttons** — give each starter prompt button `glass hover:shadow-glow transition text-left p-3 text-sm`. Keep the `@click`/`x-for` bindings.

- [ ] **Step 6: Restyle the composer** — the input row: wrap in `border-t border-white/40 dark:border-white/5`, make the textarea `glass-input resize-none`, and the send button `bg-brand text-white` rounded-xl. Keep all `x-model`, `@keydown`, `@click="send"` bindings and the disabled state.

- [ ] **Step 7: Add render test** to `PageRenderTest`

```php
    public function test_chat_page_renders(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme', 'bot_name' => 'Acme Bot']);
        $user   = User::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)->get(route('chat'))->assertStatus(200)->assertSee('Acme Bot');
    }
```

- [ ] **Step 8: Run, verify pass; build; manual chat smoke**

Run: `php artisan test --filter=test_chat_page_renders` → PASS. `npm run build` → success. Manually send one message to confirm chat still works.

- [ ] **Step 9: Pint + commit**

```bash
./vendor/bin/pint
git add resources/views/chat.blade.php tests/Feature/PageRenderTest.php
git commit -m "feat: redesign chat UI in glass style (logic unchanged)"
```

---

### Task 17: Reports page

**Files:** Modify `resources/views/reports/index.blade.php`

**Interfaces:** Keep the upload `<form>` (action, `enctype`, file input `name`, CSRF) and the reports listing data unchanged. Restyle into: `<x-ui.page-header>`, a glass drag-and-drop upload zone (Alpine `x-data` for dragover state + selected filename), and report rows as a glass table on `sm+` / stacked glass cards on mobile, with `<x-ui.badge>` for status/type and `<x-ui.empty-state>` when none.

- [ ] **Step 1: Read the current file** to capture exact form fields and the loop variable names.

Run: open `resources/views/reports/index.blade.php`.

- [ ] **Step 2: Wrap content** in `<x-app-layout>` with a `mx-auto max-w-5xl px-4 py-8` container and a `<x-ui.page-header title="Reports" subtitle="Upload and browse your sales reports.">`.

- [ ] **Step 3: Replace the upload form body** with a glass dropzone (preserve `name`, `action`, `method="POST"`, `@csrf`, `enctype="multipart/form-data"`):

```blade
<x-ui.glass-card>
    <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data"
          x-data="{ over: false, file: '' }" class="space-y-4">
        @csrf
        <label @dragover.prevent="over = true" @dragleave.prevent="over = false"
               @drop="over = false"
               class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-10 text-center transition"
               :class="over ? 'border-brand bg-brand/5' : 'border-slate-300/70 dark:border-white/10'">
            <svg class="mb-3 h-10 w-10 text-brand" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V18a3 3 0 003 3h12a3 3 0 003-3v-1.5M16.5 7.5L12 3m0 0L7.5 7.5M12 3v13.5"/></svg>
            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Drop a report here, or click to browse</span>
            <span class="mt-1 text-xs text-slate-400" x-text="file || 'PDF, DOCX, or XLSX'"></span>
            <input type="file" name="report" class="hidden" @change="file = $event.target.files[0]?.name ?? ''">
        </label>
        <x-input-error :messages="$errors->get('report')" />
        <div class="flex justify-end">
            <x-primary-button>Upload report</x-primary-button>
        </div>
    </form>
</x-ui.glass-card>
```

> If the existing file input `name` is not `report`, use the actual name from Step 1.

- [ ] **Step 4: Replace the listing** with a glass table (desktop) + stacked cards (mobile) over the existing reports collection. Use the actual loop variable from Step 1 (shown here as `$reports`):

```blade
@if($reports->isEmpty())
    <x-ui.empty-state title="No reports yet" message="Upload your first sales report to get started." class="mt-6" />
@else
    <x-ui.glass-card :padded="false" class="mt-6 overflow-hidden">
        <table class="hidden w-full text-left text-sm sm:table">
            <thead class="border-b border-white/40 text-xs uppercase text-slate-400 dark:border-white/5">
                <tr><th class="px-5 py-3">Report</th><th class="px-5 py-3">Date</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                    <tr class="border-b border-white/30 last:border-0 dark:border-white/5">
                        <td class="px-5 py-3 font-medium text-slate-700 dark:text-slate-200">{{ $report->original_name ?? $report->report_date }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $report->report_date }}</td>
                        <td class="px-5 py-3 text-right"><x-ui.badge color="emerald">Ready</x-ui.badge></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="divide-y divide-white/30 dark:divide-white/5 sm:hidden">
            @foreach($reports as $report)
                <div class="flex items-center justify-between px-4 py-3">
                    <div>
                        <p class="font-medium text-slate-700 dark:text-slate-200">{{ $report->original_name ?? $report->report_date }}</p>
                        <p class="text-xs text-slate-400">{{ $report->report_date }}</p>
                    </div>
                    <x-ui.badge color="emerald">Ready</x-ui.badge>
                </div>
            @endforeach
        </div>
    </x-ui.glass-card>
@endif
```

> Use the real column accessors from Step 1 (`original_name`/`report_date` are placeholders to swap for the actual `SalesReport` attributes).

- [ ] **Step 5: Add render test** to `PageRenderTest`

```php
    public function test_reports_page_renders(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $user   = User::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)->get(route('reports.index'))->assertStatus(200)->assertSee('Drop a report here', false);
    }
```

- [ ] **Step 6: Run, verify pass; build; manual upload smoke; commit**

Run: `php artisan test --filter=test_reports_page_renders` → PASS. `npm run build` → success.

```bash
./vendor/bin/pint
git add resources/views/reports/index.blade.php tests/Feature/PageRenderTest.php
git commit -m "feat: redesign reports page with glass dropzone and table"
```

---

### Task 18: Profile pages

**Files:** Modify `resources/views/profile/edit.blade.php` and `profile/partials/{update-profile-information-form,update-password-form,delete-user-form}.blade.php`.

**Interfaces:** Keep all form actions, methods (`@method('patch')`/`delete`), field names, and the `delete-user-form` modal wiring unchanged. Wrap each partial's outer container in `<x-ui.glass-card>` and add a `<x-ui.section-heading>`.

- [ ] **Step 1: Replace `profile/edit.blade.php` layout wrapper**

```blade
<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Profile" subtitle="Manage your account details and security." />

        <x-ui.glass-card>@include('profile.partials.update-profile-information-form')</x-ui.glass-card>
        <x-ui.glass-card>@include('profile.partials.update-password-form')</x-ui.glass-card>
        <x-ui.glass-card>@include('profile.partials.delete-user-form')</x-ui.glass-card>
    </div>
</x-app-layout>
```

- [ ] **Step 2: In each partial**, replace the existing `<header>` heading markup with `<x-ui.section-heading>…</x-ui.section-heading>` plus the existing descriptive `<p>` restyled to `text-sm text-slate-500 dark:text-slate-400`. Leave the `<form>` and `<x-modal>` blocks intact.

- [ ] **Step 3: Add render test** to `PageRenderTest`

```php
    public function test_profile_page_renders(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $user   = User::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)->get(route('profile.edit'))->assertStatus(200);
    }
```

- [ ] **Step 4: Run, verify pass; build; commit**

Run: `php artisan test --filter=test_profile_page_renders` → PASS. `npm run build` → success.

```bash
./vendor/bin/pint
git add resources/views/profile tests/Feature/PageRenderTest.php
git commit -m "feat: redesign profile pages in glass cards"
```

---

### Task 19: Admin — clients (index/show/create/edit)

**Files:** Modify `resources/views/admin/clients/{index,show,create,edit}.blade.php`. (`_form.blade.php` Appearance section is Task 22.)

**Interfaces:** Keep all forms/actions/route params unchanged. `index` → glass table + cards; `show` → glass detail with stat counts (`users_count`, `sales_reports_count`, `conversations_count` already loaded); `create`/`edit` → glass card wrapping `_form` with `<x-ui.page-header>`.

- [ ] **Step 1: Read all four files** to capture exact column names, route names, and loop variables.

- [ ] **Step 2: `index.blade.php`** — wrap in `<x-app-layout>` + `mx-auto max-w-7xl px-4 py-8`, add `<x-ui.page-header title="Clients">` with a "New client" `<x-ui.btn :href="route('admin.clients.create')">`. Render the `$clients` collection as the glass table/stacked-cards pattern from Task 17 Step 4 (columns: name, slug, users count, reports count, an `<x-ui.badge :color="$client->is_active ? 'emerald' : 'slate'">` status, and an Edit `<x-ui.btn variant=\"ghost\" :href=\"route('admin.clients.edit', $client)\">`). Use `<x-ui.empty-state>` if empty.

- [ ] **Step 3: `show.blade.php`** — `<x-ui.page-header :title="$client->name">` with Edit action; a grid of three `<x-ui.stat-card>` for `users_count`, `sales_reports_count`, `conversations_count`; a `<x-ui.glass-card>` listing details (slug, currency, bot name, brand/accent swatches, theme mode, bg style). Render color swatches as `<span class="inline-block h-4 w-4 rounded-full" style="background: {{ $client->brand_color ?? '#4f46e5' }}">`.

- [ ] **Step 4: `create.blade.php` & `edit.blade.php`** — wrap with `<x-app-layout>`, `<x-ui.page-header>` ("New client" / "Edit {{ $client->name }}"), and a `<x-ui.glass-card>` containing the existing `<form>` + `@include('admin.clients._form')` + submit `<x-primary-button>`. Keep `@method('PUT')` on edit and all hidden fields.

- [ ] **Step 5: Add render tests** to `PageRenderTest`

```php
    public function test_admin_clients_index_renders(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $admin  = User::factory()->create(['client_id' => $client->id, 'is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.clients.index'))->assertStatus(200)->assertSee('Acme');
    }
```

- [ ] **Step 6: Run, verify pass; build; commit**

Run: `php artisan test --filter=test_admin_clients_index_renders` → PASS. `npm run build` → success.

```bash
./vendor/bin/pint
git add resources/views/admin/clients tests/Feature/PageRenderTest.php
git commit -m "feat: redesign admin clients pages in glass style"
```

---

### Task 20: Admin — users (create/edit)

**Files:** Modify `resources/views/admin/users/{create,edit}.blade.php`.

**Interfaces:** Keep forms/actions/field names unchanged. Wrap in `<x-app-layout>` + `<x-ui.page-header>` + `<x-ui.glass-card>`; migrate inputs to restyled `<x-text-input>`/`<x-input-label>` (already restyled) and submit `<x-primary-button>`.

- [ ] **Step 1: Read both files** for exact fields/routes.

- [ ] **Step 2: Rewrap** both with the page-header + glass-card pattern from Task 19 Step 4. Replace any `text-gray-*`/`bg-white` card classes with the glass components. Keep selects/checkboxes; add `glass-input` class to any raw `<select>`.

- [ ] **Step 3: Add render test** to `PageRenderTest`

```php
    public function test_admin_users_create_renders(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $admin  = User::factory()->create(['client_id' => $client->id, 'is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.users.create'))->assertStatus(200);
    }
```

> If `admin.users.create` requires a client param, adjust the route call to match the actual route signature found in Step 1.

- [ ] **Step 4: Run, verify pass; build; commit**

Run: `php artisan test --filter=test_admin_users_create_renders` → PASS. `npm run build` → success.

```bash
./vendor/bin/pint
git add resources/views/admin/users tests/Feature/PageRenderTest.php
git commit -m "feat: redesign admin user forms"
```

---

### Task 21: Admin — usage

**Files:** Modify `resources/views/admin/usage.blade.php`.

**Interfaces:** Keep all computed data unchanged. Present headline metrics as `<x-ui.stat-card>` and any breakdown as a glass table (Task 17 pattern).

- [ ] **Step 1: Read the file** for the variables it receives.

- [ ] **Step 2: Rewrap** in `<x-app-layout>` + `<x-ui.page-header title="Usage">`; convert top metrics to a `grid ... sm:grid-cols-3` of `<x-ui.stat-card>`; convert any per-client/per-period table to the glass table pattern. Keep all values/expressions.

- [ ] **Step 3: Add render test** to `PageRenderTest`

```php
    public function test_admin_usage_renders(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $admin  = User::factory()->create(['client_id' => $client->id, 'is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.usage'))->assertStatus(200);
    }
```

- [ ] **Step 4: Run, verify pass; build; commit**

Run: `php artisan test --filter=test_admin_usage_renders` → PASS. `npm run build` → success.

```bash
./vendor/bin/pint
git add resources/views/admin/usage.blade.php tests/Feature/PageRenderTest.php
git commit -m "feat: redesign admin usage page with stat cards"
```

---

# Phase 5 — Admin Theming UI

### Task 22: Appearance section + live preview + validation

**Files:**
- Modify: `resources/views/admin/clients/_form.blade.php`, `app/Http/Controllers/Admin/ClientController.php` (store + update validation)
- Test: `tests/Feature/Admin/ClientThemeTest.php`

**Interfaces:** Consumes restyled inputs and theme fields. Produces an Appearance section: `brand_color` (color + hex), `accent_color`, `theme_mode` select, `bg_style` select, and an Alpine live-preview glass card. Controller validates and saves all four.

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_client_theme_fields(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $admin  = User::factory()->create(['client_id' => $client->id, 'is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.clients.update', $client), [
            'name' => 'Acme', 'slug' => 'acme',
            'brand_color' => '#112233', 'accent_color' => '#445566',
            'theme_mode' => 'dark', 'bg_style' => 'aurora',
        ])->assertRedirect();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id, 'brand_color' => '#112233',
            'accent_color' => '#445566', 'theme_mode' => 'dark', 'bg_style' => 'aurora',
        ]);
    }

    public function test_invalid_theme_mode_is_rejected(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $admin  = User::factory()->create(['client_id' => $client->id, 'is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.clients.update', $client), [
            'name' => 'Acme', 'slug' => 'acme', 'theme_mode' => 'rainbow',
        ])->assertSessionHasErrors('theme_mode');
    }
}
```

- [ ] **Step 2: Run, verify fail**

Run: `php artisan test --filter=ClientThemeTest`
Expected: FAIL — fields not saved / no validation error.

- [ ] **Step 3: Add validation to `ClientController` store() and update()**

In **both** validation arrays, add after `'brand_color' => 'nullable|string|max:20',`:

```php
            'accent_color'        => 'nullable|string|max:20',
            'theme_mode'          => 'nullable|in:light,dark,auto',
            'bg_style'            => 'nullable|in:mesh,aurora,solid,dots',
```

(`Client::$fillable` already includes these from Task 1, so `$client->update($data)` / `Client::create($data)` persist them.)

- [ ] **Step 4: Run, verify pass**

Run: `php artisan test --filter=ClientThemeTest`
Expected: PASS (both).

- [ ] **Step 5: Add the Appearance section to `_form.blade.php`**

Append before the final `is_active` checkbox block:

```blade
<div class="space-y-4 rounded-2xl border border-slate-200/70 p-4 dark:border-white/10"
     x-data="{
        brand: '{{ old('brand_color', $client?->brand_color ?? '#4f46e5') }}',
        accent: '{{ old('accent_color', $client?->accent_color ?? '#06b6d4') }}',
        mode: '{{ old('theme_mode', $client?->theme_mode ?? 'light') }}'
     }">
    <x-ui.section-heading>Appearance</x-ui.section-heading>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="brand_color" value="Brand color" />
            <div class="mt-1 flex items-center gap-2">
                <input type="color" x-model="brand" class="h-10 w-12 rounded-lg border-0 bg-transparent p-0">
                <x-text-input id="brand_color" name="brand_color" x-model="brand" class="flex-1" placeholder="#4f46e5" />
            </div>
            <x-input-error :messages="$errors->get('brand_color')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="accent_color" value="Accent color" />
            <div class="mt-1 flex items-center gap-2">
                <input type="color" x-model="accent" class="h-10 w-12 rounded-lg border-0 bg-transparent p-0">
                <x-text-input id="accent_color" name="accent_color" x-model="accent" class="flex-1" placeholder="#06b6d4" />
            </div>
            <x-input-error :messages="$errors->get('accent_color')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="theme_mode" value="Theme mode" />
            <select id="theme_mode" name="theme_mode" x-model="mode" class="glass-input mt-1">
                @foreach(['light' => 'Light', 'dark' => 'Dark', 'auto' => 'Auto (follow device)'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(old('theme_mode', $client?->theme_mode ?? 'light') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="bg_style" value="Background style" />
            <select id="bg_style" name="bg_style" class="glass-input mt-1">
                @foreach(['mesh' => 'Mesh', 'aurora' => 'Aurora (animated)', 'solid' => 'Solid', 'dots' => 'Dots'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(old('bg_style', $client?->bg_style ?? 'mesh') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Live preview --}}
    <div class="rounded-2xl border border-slate-200/70 p-4 dark:border-white/10"
         :class="mode === 'dark' ? 'bg-slate-900' : 'bg-slate-50'">
        <p class="mb-2 text-xs font-medium uppercase tracking-wide" :class="mode === 'dark' ? 'text-slate-400' : 'text-slate-500'">Preview</p>
        <div class="flex items-center gap-3 rounded-xl p-3 backdrop-blur"
             :style="`background: ${mode === 'dark' ? 'rgba(15,23,42,.6)' : 'rgba(255,255,255,.7)'}`">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl text-white" :style="`background:${brand}`">A</span>
            <div class="flex-1">
                <div class="h-2 w-24 rounded-full" :style="`background:${brand}`"></div>
                <div class="mt-1 h-2 w-16 rounded-full" :style="`background:${accent}`"></div>
            </div>
            <span class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white" :style="`background:${brand}`">Button</span>
        </div>
    </div>
</div>
```

- [ ] **Step 6: Build; manual check** — edit a client, change colors, confirm the preview updates live and saved theme applies after redirect. Then `npm run build`.

- [ ] **Step 7: Pint + commit**

```bash
./vendor/bin/pint
git add resources/views/admin/clients/_form.blade.php app/Http/Controllers/Admin/ClientController.php tests/Feature/Admin/ClientThemeTest.php
git commit -m "feat: admin appearance controls with live theme preview"
```

---

# Phase 6 — Verification

### Task 23: Full verification pass

**Files:** none (verification only).

- [ ] **Step 1: Full test suite**

Run: `composer test`
Expected: all green (existing auth/profile tests + new Theme/PageRender/ClientTheme tests).

- [ ] **Step 2: Production build**

Run: `npm run build`
Expected: success, no warnings about missing classes.

- [ ] **Step 3: Pint check**

Run: `./vendor/bin/pint --test`
Expected: no style violations.

- [ ] **Step 4: Manual two-client theming check**

Create a second client with a different brand/accent, `theme_mode=dark`, `bg_style=aurora`; create a user under it; log in as each user and confirm: accent colors differ, dark mode applies with no white flash on load, aurora background animates.

- [ ] **Step 5: Manual responsive + a11y check**

At 320–375px width across chat, reports, dashboard, admin index: no horizontal scroll, nav drawer works, tables become stacked cards. Toggle OS reduced-motion and confirm animations stop. Spot-check text contrast in both modes.

- [ ] **Step 6: Final commit (if any cleanup)**

```bash
./vendor/bin/pint
git add -A
git commit -m "chore: redesign verification cleanup"
```

---

## Self-Review Notes (author)

- **Spec coverage:** theming engine (T1–T4), tokens/visual language (T3), component library (T5–T10), layout shell + nav + guest (T11–T13), all page groups — auth (T14), dashboard (T15), chat (T16), reports (T17), profile (T18), admin (T19–T21) — admin theming UI (T22), verification incl. dark/light + responsive + reduced-motion (T23). ✅
- **Cross-cutting:** dark mode handled globally (T3/T4 + every component has `dark:` variants); responsive handled per page (stacked-card patterns) and verified in T23; reduced-motion in CSS (T3) and stat-card JS (T10). ✅
- **Type/name consistency:** `Theme::forClient()`/`current()` keys (`brand,accent,mode,bg,brand_rgb,accent_rgb`) used consistently in T4/T22; Tailwind `brand`/`accent` colors used everywhere; component prop names stable. ✅
- **Known read-first tasks:** T17/T19/T20/T21 require reading the current view to confirm exact column/route/field names before editing — flagged in their Step 1. This is deliberate (those views weren't fully quoted in the spec).
