# App Redesign — Glassmorphism + Full Per-Client Theming

**Date:** 2026-06-27
**Status:** Approved (design phase)

## Goal

Redesign every page of the multi-tenant sales-reporting chatbot to feel like a
premium modern SaaS product — a glassmorphism aesthetic (frosted-glass surfaces,
gradient mesh/aurora backgrounds, soft glows, tasteful motion) — while supporting
**full per-client theming** so each tenant can re-skin the app (accent + secondary
color, light/dark, background style).

Scope: **all page groups** — auth, chat (main app), reports, admin, dashboard,
profile.

## Constraints & context

- Laravel Breeze (Blade stack), Tailwind 3, Alpine.js, Vite. No CDN deps.
- Multi-tenant: a `Client` owns users/reports/conversations and branding.
- Tenant resolved from `auth()->user()->client_id`; auth/guest pages fall back to
  app defaults.
- System prompt and tenancy scoping logic are untouched — this is a presentation
  redesign plus a theming data layer.
- `QUEUE_CONNECTION=sync`, SQLite by default.

## Approach (chosen: Design-system-first)

Build the foundation (tokens + theming engine + reusable glass components) first,
then refactor every page onto it. Rejected page-by-page bespoke (inconsistent,
duplicated, theming becomes find-and-replace) and the hybrid shortcut (component
API churn).

## Part 1 — Theming engine & data model

Theme is driven entirely by **CSS custom properties** set on `<html>`; components
read `var(--brand)`, `var(--accent)`, etc. No hardcoded brand colors.

New `clients` columns (one migration):

| Column        | Type    | Notes                                            |
|---------------|---------|--------------------------------------------------|
| `brand_color` | string  | *exists* — primary accent                        |
| `accent_color`| string  | secondary accent (gradients/glows); default brand|
| `theme_mode`  | string  | `light` \| `dark` \| `auto`; default `light`     |
| `bg_style`    | string  | `mesh` \| `aurora` \| `solid` \| `dots`; `mesh`  |

- `<x-theme-style>` Blade component renders a `<style>:root{…}</style>` block into
  both `layouts/app` and `layouts/guest`, reading the resolved client (authed
  user's client, else app defaults).
- Dark mode via Tailwind `class` strategy; a tiny inline `<head>` script sets
  `.dark` on `<html>` from `theme_mode` (and `prefers-color-scheme` when `auto`)
  before paint to avoid flash.
- Derived tokens computed in CSS with `color-mix()`: glass tints, ring colors,
  glow shadows, hover states — client picks 2 colors, gets a full palette.

## Cross-cutting requirements

These apply to **every** page and component, not a single phase:

- **Responsive / mobile-first:** every page must work and look polished from
  ~320px up. Design mobile-first, then layer on `sm`/`md`/`lg`/`xl`. No horizontal
  scroll, tap targets ≥44px, glass blur tuned down on small screens for perf,
  sidebars collapse to drawers, tables become stacked cards on narrow widths,
  the chat composer and nav stay reachable with on-screen keyboards.
- **Dark & light mode:** both modes are first-class and fully styled (no
  "afterthought" dark mode). Driven by the client's `theme_mode`
  (`light`/`dark`/`auto`); `auto` follows `prefers-color-scheme`. Tailwind `class`
  strategy with a pre-paint inline script to avoid flash. No per-user toggle
  (client-set only). Glass tints, borders, glows, and text contrast all have
  light- and dark-mode token values that meet WCAG AA contrast.

## Part 2 — Visual language (tokens)

- **Surfaces:** frosted glass — `backdrop-blur`, semi-transparent bg
  (`color-mix` of surface + brand), 1px hairline border, layered soft shadow +
  subtle inner highlight, `rounded-2xl`.
- **Background:** fixed animated gradient mesh/aurora blobs (per `bg_style`), low
  opacity, GPU-cheap (transform/opacity only).
- **Type:** keep Figtree; stronger heading scale, tighter tracking, numeric/stat
  emphasis.
- **Motion:** hover lift on cards, glow pulse on primary buttons, fade/slide-in on
  load, animated number counters. Respect `prefers-reduced-motion`.
- **Depth:** 3-level elevation system as shadow tokens.

## Part 3 — Component library

New components in `resources/views/components/ui/`:
`glass-card`, `stat-card` (animated counter), `btn` (primary/ghost/danger),
`field` (label+input+error), `badge`, `empty-state`, `page-header`,
`section-heading`, `avatar`, `theme-style`.

Existing Breeze components (`primary-button`, `text-input`, `modal`, …) restyled
in place to avoid breaking current usages; pages then migrate to the richer
`ui/*` set.

## Part 4 — Layout shell & navigation

- **`layouts/app`:** themed aurora/mesh background layer; sticky translucent glass
  top nav (blur on scroll); optional collapsible glass sidebar on `lg+` for main
  app, top nav fallback on mobile; `@header` slot becomes `<x-ui.page-header>`
  (breadcrumb + title + actions).
- **`layouts/guest`:** centered floating glass auth card over animated background;
  split-panel feel on wide screens; client/app logo.
- **`navigation.blade.php`:** glass nav pills, animated active indicator,
  redesigned avatar dropdown, polished mobile drawer.

## Part 5 — Per-page redesigns

- **Chat** (highest impact): glass conversation panel; refined user/assistant
  bubbles; typing/streaming affordance; glass starter-prompt cards; sticky glass
  composer; polished conversation sidebar.
- **Reports:** drag-and-drop glass upload zone with progress; report list as glass
  cards/table with type icons + status badges; empty-state.
- **Dashboard:** replace placeholder with real overview — stat cards (reports,
  conversations, usage), recent activity, quick actions.
- **Admin (clients/users/usage):** glass data tables; usage stat cards; CRUD forms
  on `ui/field`; restyled confirmation modals.
- **Profile:** glass settings sections.
- **Auth (login/register/forgot/reset/verify):** all on the new guest shell.

## Part 6 — Admin theming UI

`admin/clients/_form` gains an **Appearance** section: brand + accent color
pickers, `theme_mode` select, `bg_style` select, with a **live mini-preview** glass
card that updates as values change (Alpine-driven).

## Verification

- `composer test` stays green.
- `npm run build` compiles assets cleanly.
- Manual pass per page group at mobile (~320–375px), tablet, and desktop widths;
  no horizontal scroll, tap targets adequate.
- Confirm theming changes by creating a second client with different colors.
- Verify **both** light and dark modes on every page group, plus `auto`
  following the OS setting, with no flash on load.
- Verify `prefers-reduced-motion`.

## Implementation phases (ordered, each checkpoint-able)

1. Theming engine + migration + Tailwind tokens/config.
2. Component library (`ui/*`) + restyle Breeze components.
3. Layout shell + navigation.
4. Page groups: auth → chat → reports → dashboard/admin/profile.
5. Admin theming UI (Appearance section + live preview).
6. Verification pass.

## Out of scope

- Backend/tenancy logic, system-prompt handling, chat tool-use loop.
- New features beyond presentation + the theming data layer.
- Unrelated refactoring.
