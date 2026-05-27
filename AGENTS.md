# INMACOM MIS V2 — Agent Instructions

Water-resources management system for the Incomati basin (INMACOM). Tracks monitoring stations, water-quality measurements, IIMA compliance, hazards/disaster incidents, and documents across Mozambique / South Africa / Eswatini.

## Stack
- **Backend**: Laravel 13 (PHP 8.3), Inertia 2, PostgreSQL (pgsql), Kreait Firebase Admin SDK (`kreait/laravel-firebase`).
- **Frontend**: React 18 + TypeScript, Inertia React, Mantine v8 (`@mantine/core`, `@mantine/form`, `@mantine/dropzone`, `@mantine/notifications`), Tailwind v3, Vite 8, i18next.
- **Auth**: Firebase email/password + Google sign-in. Server verifies Firebase ID token in [app/Http/Controllers/Auth/FirebaseAuthController.php](app/Http/Controllers/Auth/FirebaseAuthController.php), then signs in a Laravel `User` row.

## Commands
- Run dev stack: `composer dev` (serve + queue + pail + vite, names: server/queue/logs/vite).
- Build assets: `npm run build` (runs `tsc` then `vite build`).
- Tests: `composer test` (clears config then runs `php artisan test`).
- Lint PHP: `vendor/bin/pint`. Quick syntax: `php -l path/to/file.php`.
- Migrate: `php artisan migrate` (always `--force` non-interactive). Targeted: `php artisan migrate --path=database/migrations/<file>.php`.
- Seed one class: `php artisan db:seed --class=Database\\Seeders\\<Name> --force`.
- Promote user role: `php artisan user:role {email} {role}` (validates role; defined in [app/Console/Commands/PromoteUserRole.php](app/Console/Commands/PromoteUserRole.php)).
- After editing `config/*`: `php artisan config:clear`.
- Note: `ripgrep`/`rg` is **not** installed locally — use `grep -RInE` instead.

## Architecture & Conventions

### Auth (PIN-gated registration)
- Roles: `clerk_admin`, `data_manager`, `data_entry` (constants on [app/Models/User.php](app/Models/User.php#L28)).
- Registration requires a single-use code from `registration_pins` table that encodes the target role. Flow:
  1. Frontend asks for PIN → `POST /register/pin/verify` ([routes/auth.php](routes/auth.php#L14)).
  2. On valid PIN → `POST /register/pin/reserve` reserves it for `{email | firebase_uid}`.
  3. Firebase sign-in then calls `POST /auth/firebase` with `registration_pin` field (forwarded by [resources/js/lib/firebaseAuth.ts](resources/js/lib/firebaseAuth.ts)).
- `FirebaseAuthController::authenticate` rejects new users (Firebase UID not in `users`) when no pin/reservation is found by throwing `RuntimeException('no_pin')`, returning a friendly `back()->withErrors(['auth' => ...])` message. Existing users sign in normally.
- All Firebase auth routes are throttled with the named limiter `throttle:firebase-auth`.
- Firebase Admin requires `storage/app/firebase-service-account.json` (path from `FIREBASE_CREDENTIALS`). Missing/empty file → 500 on `/auth/firebase`.
- Bootstrap admin PIN seeded: `RFC42M` (role `clerk_admin`).

### Models
- All domain models use `HasUuids` (UUID primary keys). Mirror that on new models and migrations (`$table->uuid('id')->primary();`).
- `User` uses PHP 8 attributes `#[Fillable([...])]` and `#[Hidden([...])]` instead of `$fillable`/`$hidden` properties — follow the same pattern when adding model attributes.
- `User::canManageDocuments()` returns `true` for `clerk_admin` and `data_manager` roles.
- `UserPreferences` shape (stored as JSON in `users.preferences`) is typed in [resources/js/types/index.d.ts](resources/js/types/index.d.ts).

### Frontend

**Pages & Layouts**
- Pages live in `resources/js/Pages/<Area>/<Page>.tsx`, resolved by Inertia via `import.meta.glob('./Pages/**/*.tsx')` in [resources/js/app.tsx](resources/js/app.tsx).
- Layouts in [resources/js/Layouts/](resources/js/Layouts/): `AuthenticatedLayout` (main app with sidebar), `AuthLayout` (auth shell), `LibraryLayout`, `PublicLayout`.
- Reusable components: `resources/js/Components/{Auth,Dashboard,Library,UI}/`.

**Mantine theme**
All Mantine form inputs and buttons inherit these defaults from [resources/js/app.tsx](resources/js/app.tsx) — do **not** add `radius` or `size` inline props unless overriding for a specific reason:

| Component | radius | size |
|-----------|--------|------|
| `TextInput`, `PasswordInput`, `NumberInput` | `md` | `md` |
| `Select`, `Textarea` | `md` | `md` |
| `Button` | `md` | `md` |
| `Modal` | `md` | — |

**Notifications**
Use Mantine `notifications.show()` from `@mantine/notifications` — do NOT add inline `<Alert>` for transient messages:
- **Errors / warnings** → `autoClose: false`, `withCloseButton: true`, `IconAlertTriangle`, color `red`/`yellow`.
- **Success** → `autoClose: 5000`, `IconCheck`, color `green`.
- Canonical helpers: `notifyError` / `notifySuccess` in [resources/js/Pages/Auth/LoginOptions.tsx](resources/js/Pages/Auth/LoginOptions.tsx).

**Inertia + async**
`router.post` is fire-and-forget; `await` returns immediately. Reset loading state inside the `onError` callback, not after `await`. `useForm().post(...)` does NOT throw on validation errors — handle through `onError` or `errors` from `usePage()`.

**i18n**
Never hardcode user-facing strings. Add keys to **both** locales under [resources/js/locales/en/](resources/js/locales/en/) and [resources/js/locales/pt/](resources/js/locales/pt/). Namespaces: `app`, `auth`, `common`, `documents`, `errors`, `landing`, `navigation`, `settings`, `stations`, `thresholds`, `users`. Server-side locale switching: [app/Http/Middleware/SetLocale.php](app/Http/Middleware/SetLocale.php).

**CSS Modules**
Use `*.module.css` for component-scoped styles. Use plain CSS breakpoints (`@media (max-width: 768px)`) — do NOT use Mantine SCSS vars (`$mantine-breakpoint-*`) or `@mixin` directives; lightningcss will error on them.

### GIS Module (measurements + review workflow)
- GIS pages: `FlowLevels`, `DamLevels`, `WaterQuality`, `Rainfall`, `Groundwater` — all in [resources/js/Pages/Gis/](resources/js/Pages/Gis/) and handled by [app/Http/Controllers/GisController.php](app/Http/Controllers/GisController.php).
- Each page receives **three parallel data arrays** as Inertia props:
  1. **Live rows** — current station readings with metadata.
  2. **Pending rows** — unreviewed submissions (visible to `data_manager` / `clerk_admin`).
  3. **Historical rows** — archived records with reviewer notes.
- **Two-step review pattern**: `data_entry` submits → `data_manager` approves or rejects with `review_notes`. DB columns: `submitted_by_id`, `reviewed_by_id`, `review_notes`, `is_self_override`.
- The map component is [resources/js/Components/Dashboard/GisMap.tsx](resources/js/Components/Dashboard/GisMap.tsx) (exports typed `GisStationData`).

### Database (DDR Iteration 2 aligned)
- Core domain schema in [database/migrations/2026_05_16_091500_create_inmacom_domain_tables.php](database/migrations/2026_05_16_091500_create_inmacom_domain_tables.php). 8 modules: Users & Access, Monitoring Stations, Measurements & Verification, Management Areas, IIMA Compliance, Disaster Management, Documents, Audit.
- Document library schema (separate, supersedes legacy `documents`/`folders`) in [database/migrations/2026_05_16_120000_create_documents_tables.php](database/migrations/2026_05_16_120000_create_documents_tables.php) — `document_storages` + `documents` with `media_type` enum + dropzone uploads.
- DDR alignment additions (priority pollutant flag, `iima_eflow_key_points`, `hazard_indicator_readings`, `hazard_scores`) in [database/migrations/2026_05_19_000000_align_with_ddr_iteration2.php](database/migrations/2026_05_19_000000_align_with_ddr_iteration2.php).
- Reference data: [database/seeders/DDRIteration2ReferenceDataSeeder.php](database/seeders/DDRIteration2ReferenceDataSeeder.php) — idempotent (`updateOrInsert`). When adding more IAAP-10 allocations / REIWQ Appendix A thresholds / REIWQ Appendix E priority pollutants, fill the labeled TODO blocks in this seeder, not new migrations.
- IIMA compliance uses `subcatchment_id` (FK to `management_areas`) — not a separate subcatchments table.

## Things That Will Bite You
- **`documents` table conflict**: if `migrate` fails with `relation "documents" already exists`, the legacy migration was superseded by the documents-tables migration which drops old `documents`/`folders` in its `up()`. Do not reintroduce them.
- **lightningcss + `@mixin`**: Mantine's `@mixin hover` and `$mantine-breakpoint-*` SCSS vars inside `@media` queries in `*.module.css` cause build errors. Use plain CSS values only.
- **Inertia form errors**: `useForm().post(...)` does NOT throw on validation errors — handle through `onError` or `errors` from `usePage()`.
- **Throttling**: any new Firebase / PIN endpoint must use `->middleware('throttle:firebase-auth')` to stay consistent.
- **Inline radius/size props**: Do not add `radius` or `size` to Mantine inputs/buttons inline — defaults are set globally in [resources/js/app.tsx](resources/js/app.tsx) (defaulting to `md` radius/size). All fields (text inputs, search inputs, dropdowns) must strictly use the standard Mantine input style without any custom inline override (e.g. no `radius="xl"`) or custom CSS.

