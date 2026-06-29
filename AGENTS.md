# INMACOM MIS V2 — Agent Instructions

Water-resources management system for the Incomati basin (INMACOM). This project tracks stations, measurements, IIMA compliance, hazard/disaster incidents, and document workflows across Mozambique, South Africa, and Eswatini.

## Start Here (Source of Truth)
- Technical scope and treaty context: [INMACOM DOCS/INMACOM_IMS_DDR_Iteration2.md](INMACOM%20DOCS/INMACOM_IMS_DDR_Iteration2.md)
- Project proposal/context: [INMACOM DOCS/INMACOM_IMS_Proposal_Datamatics.md](INMACOM%20DOCS/INMACOM_IMS_Proposal_Datamatics.md)
- App routes and module surface: [routes/web.php](routes/web.php), [routes/auth.php](routes/auth.php)
- Auth flow implementation: [app/Http/Controllers/Auth/FirebaseAuthController.php](app/Http/Controllers/Auth/FirebaseAuthController.php)
- Frontend shell and Mantine defaults: [resources/js/app.tsx](resources/js/app.tsx)
- Domain schema + reference seed data: [database/migrations/2026_05_16_091500_create_inmacom_domain_tables.php](database/migrations/2026_05_16_091500_create_inmacom_domain_tables.php), [database/seeders/DDRIteration2ReferenceDataSeeder.php](database/seeders/DDRIteration2ReferenceDataSeeder.php)

## Stack
- Backend: Laravel 13 (PHP 8.3), Inertia 2, PostgreSQL, Firebase Admin SDK (`kreait/laravel-firebase`)
- Frontend: React 18 + TypeScript, Inertia React, Mantine v8, Tailwind v3, Vite 8, i18next
- Auth: Firebase email/password + Google sign-in, with server-side token verification

## Common Commands
- Dev stack: `composer dev`
- First-time setup: `composer setup`
- Frontend build: `npm run build`
- Tests: `composer test`
- PHP lint/format: `php -l path/to/file.php`, `vendor/bin/pint`
- Migrations: `php artisan migrate --force`
- Targeted migration: `php artisan migrate --path=database/migrations/<file>.php --force`
- Seed one class: `php artisan db:seed --class=Database\\Seeders\\<Name> --force`
- Promote role: `php artisan user:role {email} {role}`
- After editing `config/*`: `php artisan config:clear`
- Local shell note: `rg` is not installed in this environment, use `grep -RInE` instead

## Architecture Snapshot
- Backend controllers and domain logic live under [app/Http/Controllers](app/Http/Controllers)
- Inertia pages live under [resources/js/Pages](resources/js/Pages)
- Reusable UI/components live under [resources/js/Components](resources/js/Components)
- Main layouts live under [resources/js/Layouts](resources/js/Layouts)
- Auth and registration routes are isolated in [routes/auth.php](routes/auth.php)

## Project Conventions

### Roles and Auth
- Canonical code roles are `admin`, `manager`, and `clerk` (see [app/Models/User.php](app/Models/User.php)).
- Legacy names may appear in old SQL/docs: `clerk_admin -> admin`, `data_manager -> manager`, `data_entry -> clerk` (see [database/migrations/2026_05_28_000100_rename_user_roles.php](database/migrations/2026_05_28_000100_rename_user_roles.php)).
- Registration is PIN-gated for new users:
  1. Verify PIN: `POST /register/pin/verify`
  2. Reserve PIN: `POST /register/pin/reserve`
  3. Authenticate Firebase token: `POST /auth/firebase` with optional `registration_pin`
- New Firebase/PIN endpoints must use `throttle:firebase-auth` for consistency.
- Firebase Admin requires `storage/app/firebase-service-account.json` (configured by `FIREBASE_CREDENTIALS`).

### Models and Database
- Domain models use `HasUuids`; mirror this in migrations with `$table->uuid('id')->primary();`.
- The `User` model uses PHP 8 attributes `#[Fillable(...)]` and `#[Hidden(...)]` (not legacy `$fillable`/`$hidden` properties).
- `User` preferences shape is typed in [resources/js/types/index.d.ts](resources/js/types/index.d.ts).
- When extending treaty/reference datasets (IAAP-10, REIWQ Appendix A/E), update labeled TODO blocks in [database/seeders/DDRIteration2ReferenceDataSeeder.php](database/seeders/DDRIteration2ReferenceDataSeeder.php) instead of adding ad-hoc migrations.

### Frontend
- Resolve pages via Inertia `import.meta.glob('./Pages/**/*.tsx')` in [resources/js/app.tsx](resources/js/app.tsx).
- Mantine defaults are global in [resources/js/app.tsx](resources/js/app.tsx). Do not add inline `radius`/`size` props to inputs/buttons unless there is a clear exception.
- For transient UX messages, use Mantine notifications (`notifications.show()` from `@mantine/notifications`), not inline alert blocks.
- Inertia forms: `useForm().post(...)` does not throw validation exceptions. Handle via `onError`/page errors.
- i18n: never hardcode user-facing strings. Add keys to both locale trees in [resources/js/locales/en](resources/js/locales/en) and [resources/js/locales/pt](resources/js/locales/pt).
- CSS Modules: use plain CSS media queries. Avoid Mantine SCSS breakpoint vars like `$mantine-breakpoint-*` in `*.module.css`.

### GIS Workflow
- GIS pages (`FlowLevels`, `DamLevels`, `WaterQuality`, `Rainfall`, `Groundwater`) are handled by [app/Http/Controllers/GisController.php](app/Http/Controllers/GisController.php) and rendered from [resources/js/Pages/Gis](resources/js/Pages/Gis).
- Each GIS page expects three data arrays: live rows, pending rows, and historical rows.
- Review flow is two-step: submitter creates pending data, approver (`admin`/`manager`) approves or rejects with notes.
- Shared map component: [resources/js/Components/Dashboard/GisMap.tsx](resources/js/Components/Dashboard/GisMap.tsx).

## Pitfalls
- If migrate fails with `relation "documents" already exists`, ensure old legacy `documents/folders` migrations are not being reintroduced (document tables were superseded by [database/migrations/2026_05_16_120000_create_documents_tables.php](database/migrations/2026_05_16_120000_create_documents_tables.php)).
- `router.post` is fire-and-forget; do not assume `await` gives validated completion state.
- Old deployment docs and SQL dumps may still show pre-rename roles (`clerk_admin`, `data_manager`, `data_entry`). Prefer application constants and current migrations as truth.

## Matt Pocock Agent Skills

A suite of development skills from Matt Pocock's framework are installed under `.agents/skills/`. You should invoke and consult these skills when undertaking architectural modifications, diagnosing bugs, or designing new features:

- **[improve-codebase-architecture](file:///Users/siyadl/Documents/GitHub/INMACOM_MIS_V2.0/.agents/skills/improve-codebase-architecture/SKILL.md)**: Find refactoring opportunities, create deep modules with minimal interfaces, and reduce cognitive load.
- **[tdd](file:///Users/siyadl/Documents/GitHub/INMACOM_MIS_V2.0/.agents/skills/tdd/SKILL.md)**: Use test-driven development conventions, interface-first design, and deep test boundaries.
- **[grill-me](file:///Users/siyadl/Documents/GitHub/INMACOM_MIS_V2.0/.agents/skills/grill-me/SKILL.md)**: Trigger an interactive dialogue/interview structure to align on design decisions and resolve ambiguities.
- **[grill-with-docs](file:///Users/siyadl/Documents/GitHub/INMACOM_MIS_V2.0/.agents/skills/grill-with-docs/SKILL.md)**: Maintain system alignment via Context files and Architecture Decision Records (ADRs).
- **[diagnose](file:///Users/siyadl/Documents/GitHub/INMACOM_MIS_V2.0/.agents/skills/diagnose/SKILL.md)**: Systematically isolate bugs, construct reproduction cases, and fix issues logically.
- **[prototype](file:///Users/siyadl/Documents/GitHub/INMACOM_MIS_V2.0/.agents/skills/prototype/SKILL.md)**: Rapidly stub UI and business logic interfaces before full-scale implementation.
- **[triage](file:///Users/siyadl/Documents/GitHub/INMACOM_MIS_V2.0/.agents/skills/triage/SKILL.md)**: Classify tasks, issues, and requests into structured action plans.
- **[zoom-out](file:///Users/siyadl/Documents/GitHub/INMACOM_MIS_V2.0/.agents/skills/zoom-out/SKILL.md)**: Perform high-level codebase audits and dependency tracing.
- **[to-prd](file:///Users/siyadl/Documents/GitHub/INMACOM_MIS_V2.0/.agents/skills/to-prd/SKILL.md)**: Distill ambiguous requirements into concrete Product Requirement Documents (PRDs).


