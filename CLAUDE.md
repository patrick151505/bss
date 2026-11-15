# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> **Always:** every code change that users can see must also be logged in `config/changelog.php`
> (Settings → Updates) in the same task — see [Version & change log](#version--change-log--required-on-every-code-change).

## Project Overview

**Eb-Konrix** is a Laravel 10 barangay (Philippine village government) management system. Modules: citizens, households, blotter (incident logs), events/attendance, inventory, budget management, document/certificate generation, and citizen ID cards.

**Stack:** Laravel 10, PHP 8+, MySQL, Blade templating, Tailwind CSS 3.3.2 (Konrix admin theme), Vite 4, jQuery 3.7.1.

**Environment:** Runs under XAMPP on `http://localhost:8080/Eb-Konrix/public`. MySQL on port 4033, database `practice_cli_brgy`.

---

## Development Commands

```bash
# Asset compilation
npm run dev        # Vite dev server with HMR
npm run build      # Production build

# Laravel
php artisan serve              # Dev server (localhost:8000)
php artisan migrate            # Run migrations
php artisan migrate:rollback   # Rollback
php artisan db:seed            # Seed data
php artisan tinker             # REPL
php artisan test               # PHPUnit tests
./vendor/bin/pint              # Laravel code style linter

# After deployment
php artisan config:cache
php artisan route:cache
php artisan storage:link       # Symlink storage/app/public → public/storage
```

---

## Architecture

### Request Flow

Routes (`routes/web.php`) → Controllers (`app/Http/Controllers/`) → Eloquent Models (`app/Models/`) → Blade views (`resources/views/`).

All routes are under `middleware(['auth'])` except auth routes. No API-first architecture — everything is server-rendered Blade with jQuery for interactivity.

### Database Naming

Table prefix is `eb_` for all custom tables (e.g. `eb_citizen`, `eb_household`, `eb_blotter`). Laravel's default `users` table has no prefix. All migrations are in `database/migrations/`.

The citizen model uses the table name `eb_citizen` (non-standard singular). It has approval workflow columns, voter/PWD/senior flags, and a `familyId` FK to `eb_family`.

### File Storage

Public uploads go to `storage/app/public/` and are accessed via `storage/` symlink. Citizen photos, budget attachments, blotter photos, QR codes, and generated IDs all live here.

---

## Blade Template Conventions

### Every page must extend the vertical layout with these keys:

```blade
@extends('layouts.vertical', [
    'title'         => 'Page Title',       // <title> tag and breadcrumb leaf
    'sub_title'     => 'Section Name',     // breadcrumb parent label
    'sub_title_url' => route('...index'),  // makes sub_title clickable — always set this
    'tagline'       => 'Short description', // shown under page title — eliminates need for manual heading block in content
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])
```

For deeper nesting (edit/show pages), use `breadcrumbs` array instead of `sub_title`:
```blade
'breadcrumbs' => [
    ['label' => 'Section', 'url' => route('section.index')],
    ['label' => 'Sub',     'url' => route('section.sub.index')],
    ['label' => 'Current', 'url' => ''],
]
```

**Rules:**
- Always set `sub_title_url` — never leave breadcrumb links as dead `#`.
- If `tagline` is set, **remove** any `<h4>/<p>` heading block from `@section('content')` — the layout already renders it.
- Scripts go in `@push('inline-scripts') <script>...</script> @endpush` (not `@section('script')`).
- Skeleton loaders go in `@push('skeleton')`.

### Card patterns

```blade
{{-- Basic --}}
<div class="card p-6">...</div>

{{-- With header + action button --}}
<div class="card">
    <div class="card-header">
        <div class="flex justify-between items-center">
            <h4 class="card-title">Title</h4>
            <a href="..." class="btn bg-primary text-white">Add</a>
        </div>
    </div>
    <div class="p-6">...</div>
</div>
```

### Table pattern

```blade
<div class="overflow-x-auto">
    <table class="table min-w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Col</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="px-5 py-3 text-gray-800 dark:text-gray-200">Value</td>
            </tr>
            {{-- Empty state --}}
            <tr><td colspan="N" class="text-center py-12 text-gray-400">
                <i class="mgc_box_3_line text-4xl mb-2 block opacity-30"></i>No records found.
            </td></tr>
        </tbody>
    </table>
</div>
```

### Badge (dot-pill) — project standard

```blade
{{-- Green / Active --}}
<span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800">
    <span class="w-1.5 h-1.5 inline-block bg-green-400 rounded-full"></span>Active
</span>
{{-- Yellow=Pending, Red=Danger, Blue=bg-primary/25 text-sky-800, Gray=Inactive --}}
```

### Buttons

```blade
<button class="btn bg-primary text-white">Primary</button>
<button class="btn bg-success/25 text-success hover:bg-success hover:text-white">Success soft</button>
<button class="btn bg-danger/25 text-danger hover:bg-danger hover:text-white">Danger soft</button>
<a href="..." class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white"><i class="mgc_arrow_left_line"></i> Back</a>
<button class="btn btn-sm bg-primary text-white"><i class="mgc_edit_line"></i></button>
```

**Dark mode — never use a bare `text-slate-900` / `text-gray-900`.** The neutral
soft button (`bg-dark/25`) renders near-black text on a dark background, making
the label invisible. Always pair it with a dark variant:

```blade
{{-- Wrong: unreadable in dark mode --}}
<button class="btn bg-dark/25 text-slate-900 hover:bg-dark hover:text-white">Edit</button>

{{-- Right --}}
<button class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Edit</button>
```

The same applies to any dark-on-light utility used on a surface that flips with
the theme — `text-gray-800`, `text-slate-700` and so on all need a
`dark:text-*` counterpart.

### Flash messages / alerts

```blade
@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif
```

### Modals (fc-modal)

```blade
<button data-fc-type="modal" data-fc-target="my-modal" class="btn bg-primary text-white">Open</button>

<div id="my-modal" class="fc-modal hidden w-full h-full fixed top-0 start-0 z-50">
    <div class="fc-modal-open:opacity-100 fc-modal-open:duration-500 opacity-0 transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
            <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                <h3 class="font-bold text-gray-800 dark:text-white">Title</h3>
                <button data-fc-dismiss="modal"><i class="mgc_close_line text-xl"></i></button>
            </div>
            <div class="p-4">Content</div>
            <div class="flex justify-end gap-2 py-3 px-4 border-t dark:border-gray-700">
                <button data-fc-dismiss="modal" class="btn bg-dark/25 ...">Cancel</button>
                <button class="btn bg-primary text-white">Confirm</button>
            </div>
        </div>
    </div>
</div>
```

### SweetAlert2 — critical gotcha

`app.css` has a global reset: `button { background-color: transparent }` which overrides Swal's `confirmButtonColor`. **Always** use `didOpen` to force the color:

```js
Swal.fire({
    title, text, icon,
    showCancelButton: true,
    confirmButtonText: 'Yes, do it',
    reverseButtons: true,
    didOpen: () => {
        document.querySelector('.swal2-confirm').style.setProperty('background-color', '#727cf5', 'important');
    },
}).then(r => { if (r.isConfirmed) document.getElementById('form-' + id).submit(); });
```

Also add to CSS to fix the cancel button: `.swal2-cancel.swal2-styled { background-color: #6c757d !important; }`

Standard colors: confirm/approve `#727cf5`, success/paid `#0acf97`, danger/delete `#fa5c7c`, neutral cancel `#6c757d`.

**Never use native `confirm()`** — always Swal.

### Accordions (manual toggle)

```blade
<button onclick="toggleAccordion('body-id', 'chevron-id')">Title <i id="chevron-id" class="mgc_down_line"></i></button>
<div id="body-id" class="hidden">Content</div>
<script>
function toggleAccordion(bodyId, chevronId) {
    const opening = document.getElementById(bodyId).classList.contains('hidden');
    document.getElementById(bodyId).classList.toggle('hidden');
    document.getElementById(chevronId).classList.toggle('mgc_down_line', !opening);
    document.getElementById(chevronId).classList.toggle('mgc_up_line', opening);
}
</script>
```

### Date/time formatting — always use these formats

| Use | Format | Example |
|-----|--------|---------|
| Date only | `M d, Y` | `Jun 15, 2026` |
| Time only | `g:i A` | `2:30 PM` |
| Date + time | `M d, Y g:i A` | `Jun 15, 2026 2:30 PM` |
| Long date | `F d, Y` | `June 15, 2026` |

Never `h:i A` (adds leading zero). Never `h:i:s A` (shows seconds). Timezone is `Asia/Manila`.

### Icons

This project uses **MingCute** icons: `<i class="mgc_ICON_NAME_line"></i>` or `mgc_ICON_NAME_fill`.

Common: `mgc_add_line`, `mgc_edit_line`, `mgc_delete_line`, `mgc_search_line`, `mgc_user_3_line`, `mgc_check_circle_line`, `mgc_alert_line`, `mgc_close_line`, `mgc_down_line`, `mgc_up_line`, `mgc_arrow_left_line`, `mgc_box_3_line`.

### Color tokens (Konrix semantic)

`primary` (blue), `success` (green), `danger` (red), `warning` (amber), `info` (cyan), `dark`, `light`. Soft variants: `bg-primary/10`, `bg-danger/25`.

### Stat/KPI card

```blade
<div class="card p-5">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
            <i class="mgc_user_3_line text-primary text-2xl"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Label</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $count }}</p>
        </div>
    </div>
</div>
```

---

## Known Quirks

### Quill 1.x strips `<table>` elements

Quill's MutationObserver sanitizes tables and complex HTML out of the editor DOM. When toggling between source HTML and visual mode:
- "View Source": dump `quill.root.innerHTML` into a `<textarea>`.
- "Back to Visual": render HTML into a sandboxed `<iframe>` via `doc.write()` — **not** into Quill's contenteditable.
- On form submit: always read from the source `<textarea>`, not `quill.root.innerHTML`.

Applies to: document template editor (`resources/views/documents/types/edit.blade.php`).

---

## Key Routes (web.php)

All routes require `auth` middleware. Main entry points:

| Module | Prefix |
|--------|--------|
| Citizens | `/citizens` |
| Households | `/citizens/household` |
| Citizen IDs | `/citizens/ids` |
| Blotter | `/blotter` |
| Ordinances & Resolutions | `/legislation` |
| Health check-ups | `/health` |
| Events | `/events` |
| Inventory | `/inventory` |
| Budget | `/budget/*` |
| Documents | `/documents` |
| Birthdays | `/birthdays` |
| Users/Roles | `/users`, `/roles` |
| Activity Logs | `/activity-logs` |

---

## Adding a New Module

Standard checklist:
1. Migration with `eb_` prefix table name
2. Eloquent model in `app/Models/`
3. Resource controller in `app/Http/Controllers/`
4. Routes in `routes/web.php` under `middleware(['auth'])`
5. Blade views in `resources/views/{module}/` extending `layouts.vertical`
6. Log modifications via `ActivityLog` model for audit trail
7. Add the change to `config/changelog.php` (shown on Settings → Updates)

## Version & change log — REQUIRED on every code change

The system version is the newest entry in `config/changelog.php` (`config('app.version')`), shown to
users on Settings → Updates (`/settings/updates`). **Every task that changes what a user can see or do
must update `config/changelog.php` in the same task, before reporting the work as done.** Do this
without being asked.

**What to log**
- `new` — a feature, page, button, report, permission or setting that did not exist before.
- `improved` — an existing feature that works or looks better (redesign, extra filter, faster, clearer).
- `fixed` — something that was broken or wrong and now works (include data fixes users will notice).
- Do **not** log changes users cannot notice: refactors, tests, code style, comments, internal renames.

**Which version to add it to**
- If the top entry's `date` is **today**, add the change to that entry.
- Otherwise start a **new entry at the top**: bump the minor number (`3.0` → `3.1` → `3.2` …), set
  `date` to today (`Y-m-d`, Asia/Manila), and write a short `title` and `summary`.
- Bump the major number (`4.0`) only when the user asks for it.
- When adding to an existing entry, update its `title`/`summary` if the new change is a headline item.

**How to write each change**
```php
['type' => 'new', 'module' => 'Health', 'text' => 'Medicines given at a check-up come out of Inventory stock.'],
```
- `module` — use an existing name where it fits (Citizens, Households, Documents, Blotter, Ordinances,
  Health, Events, Inventory, Budget, Roles, Settings, Login, System); keep the list short.
- `text` — one plain sentence for barangay staff: what they can now do or what changed, not how it
  was coded. No class names, file paths, routes or jargon.
- One entry per visible change. If the same feature is extended within the same version, edit its
  existing line instead of adding a near-duplicate.
- Keep the change list grouped by module (entries for the same module next to each other).

**Before finishing a task**
1. `config/changelog.php` updated as above (it is a PHP file — keep it valid; check with
   `php artisan tinker --execute="echo config('app.version');"`).
2. `FEATURES.md` updated if a module's features, roles or status changed.
3. In the summary to the user, mention the version the change was logged under (e.g. "Logged in v3.1").
