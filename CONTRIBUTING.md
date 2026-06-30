# Contributing to INMACOM MIS

Thank you for helping improve INMACOM MIS! Please read this guide before opening any pull request.

---

## Code of Conduct

All contributors are expected to be respectful and professional. This project serves a multi-national water-management treaty body — please keep discussions inclusive and focused on the technical work.

---

## Getting Started

1. **Fork** the repository on GitHub.
2. **Clone** your fork locally:
   ```bash
   git clone https://github.com/<your-username>/INMACOM_MIS_V2.1.git
   cd INMACOM_MIS_V2.1
   ```
3. Follow the [Quick Start](README.md#quick-start) in the README to set up your local environment.
4. Create a **feature branch** from `main` (see Branch Naming below).
5. Make your changes (see conventions below).
6. Open a **pull request** against `main`.

---

## Branch Naming

| Type | Pattern | Example |
|---|---|---|
| New feature | `feature/<short-description>` | `feature/rainfall-csv-export` |
| Bug fix | `fix/<short-description>` | `fix/flow-level-approval-redirect` |
| Documentation | `docs/<short-description>` | `docs/add-deployment-guide` |
| Chore / maintenance | `chore/<short-description>` | `chore/bump-mantine-v8` |
| Hotfix | `hotfix/<short-description>` | `hotfix/session-expiry-loop` |

---

## Commit Message Style

Follow **Conventional Commits**:

```
<type>(<scope>): <short imperative summary>

[optional body]

[optional footer: refs #issue]
```

**Types:** `feat`, `fix`, `docs`, `chore`, `refactor`, `test`, `perf`  
**Scope:** module name, e.g. `gis`, `auth`, `stations`, `disaster`, `library`

Examples:
```
feat(gis): add groundwater CSV export endpoint
fix(auth): handle revoked Firebase token on re-login
docs: add deployment guide for Cloud Run
chore(deps): upgrade Mantine to 8.3.18
```

---

## Linting & Formatting

Run these before every commit:

### PHP (Laravel Pint)
```bash
vendor/bin/pint
```

Pint is configured to follow the Laravel coding style. Fix all warnings before pushing.

### TypeScript / React
```bash
npm run build
```

The build runs `tsc` — fix all TypeScript errors before pushing. There is no separate ESLint step, but type-safe code is required.

### PHP syntax check (quick)
```bash
php -l path/to/ChangedFile.php
```

---

## Testing

```bash
composer test
```

- Tests live in `tests/Feature/` and `tests/Unit/`.
- All existing tests must pass before a PR is merged.
- Add feature tests for new controller actions or business logic.
- Use Laravel's `RefreshDatabase` trait in feature tests that touch the database.

---

## Internationalisation (i18n) Requirement

**Every user-facing string must be translated.** If you add or change any text visible to users:

1. Add the key to **both** locale files:
   - `resources/js/locales/en/<namespace>.json`
   - `resources/js/locales/pt/<namespace>.json`
2. Use the `useTranslation` hook in React components:
   ```tsx
   const { t } = useTranslation('gis');
   // …
   <p>{t('flow_levels.title')}</p>
   ```
3. **Never hardcode** user-visible strings in TSX/PHP views.

Namespace files correspond to modules: `gis.json`, `stations.json`, `auth.json`, `common.json`, etc.

---

## Database & Migration Rules

- All domain model primary keys must be **UUIDs** (`$table->uuid('id')->primary()`).
- Models must use the `HasUuids` trait.
- When adding new fields to existing tables, create a new timestamped migration — do not modify existing migration files.
- To extend treaty/reference data (IIMA allocations, e-flow requirements, water quality parameters), update the labeled `TODO` blocks in `database/seeders/DDRIteration2ReferenceDataSeeder.php` rather than adding ad-hoc migrations.
- Always provide a `down()` method that correctly reverses your migration.

---

## Frontend Conventions

- **Mantine global defaults** are set in `resources/js/app.tsx`. Do not add inline `radius` or `size` props to inputs or buttons unless there is a documented exception.
- **Notifications** — use `notifications.show()` from `@mantine/notifications` for transient UX messages; do not use inline alert blocks.
- **Inertia forms** — `useForm().post(...)` does not throw on validation failure. Handle errors via `onError` callbacks or by reading `page.props.errors`.
- **CSS Modules** — use plain CSS media queries; avoid Mantine SCSS breakpoint variables (`$mantine-breakpoint-*`) in `*.module.css` files.

---

## Pull Request Checklist

Before submitting a PR, confirm the following:

- [ ] `vendor/bin/pint` passes with no changes
- [ ] `npm run build` passes with no TypeScript errors
- [ ] `composer test` passes
- [ ] i18n keys added to both `en/` and `pt/` locales
- [ ] No secrets or credentials committed (check `.env`, JSON files)
- [ ] Migration has a correct `down()` method
- [ ] PR description explains *what* changed and *why*
