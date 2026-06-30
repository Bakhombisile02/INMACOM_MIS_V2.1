# Architecture — INMACOM MIS V2.1

This document describes the high-level architecture of INMACOM MIS: how the layers fit together, how authentication flows, and how the core domain workflows are structured.

---

## 1. Technology Stack

```
Browser
  └─ React 18 + TypeScript (Mantine v8, Tailwind v3, Leaflet)
       │  Inertia.js 2 (SPA navigation, shared props)
       ▼
Laravel 13 / PHP 8.3
  ├─ Controllers  (app/Http/Controllers/)
  ├─ Services     (app/Services/)
  ├─ Models       (app/Models/)  ← Eloquent + UUID PKs
  └─ Queries      (app/Queries/) ← reusable query builders
       │
       ▼
PostgreSQL 15+
```

The application is a **server-driven SPA** via [Inertia.js](https://inertiajs.com/). There is no separate REST API consumed by the frontend. Laravel renders initial page props server-side; subsequent navigations use Inertia's client-side router to fetch JSON from the same controller actions and swap React components without a full page reload.

---

## 2. Frontend Architecture

### Page Resolution
Inertia pages are auto-resolved via Vite's glob import in `resources/js/app.tsx`:

```ts
const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true });
```

Every file under `resources/js/Pages/` is a potential Inertia page. The component default export receives its props directly from the corresponding Laravel controller.

### Component Layers
| Layer | Location | Purpose |
|---|---|---|
| **Pages** | `resources/js/Pages/` | One file per route; receives Inertia page props |
| **Components** | `resources/js/Components/` | Reusable UI, grouped by domain (Gis, Auth, Stations, …) |
| **Layouts** | `resources/js/Layouts/` | App shell (sidebar, header, notification bell) |
| **Lib** | `resources/js/lib/` | Utilities, Zod schemas, helpers |

### UI Conventions
- **Mantine v8** is the primary component library. Global defaults (theme, color scheme, notification system) are configured in `app.tsx`.
- **Tailwind CSS v3** is used for layout and spacing utilities alongside Mantine.
- **Phosphor Icons** and **Tabler Icons** are used for iconography.
- **i18next** + **react-i18next** provides EN/PT internationalisation. All user-facing strings are keyed in `resources/js/locales/{en,pt}/<namespace>.json`.

### Form Handling
Forms use `@mantine/form` with Zod resolvers (`mantine-form-zod-resolver`). Submissions go through Inertia's `useForm().post()` / `.patch()` / `.delete()`, which is fire-and-forget — errors are received via `onError` callbacks or `usePage().props.errors`, not exceptions.

---

## 3. Backend Architecture

### Controllers
Each module has one or more dedicated controllers under `app/Http/Controllers/`. Controllers are thin: they validate the request, call a service or Eloquent query, and return an Inertia response or redirect.

| Controller | Responsibility |
|---|---|
| `GisController` | GIS pages, measurement CRUD, CSV import/export |
| `StationsController` | Station CRUD and CSV export |
| `StationRevisionsController` | Station revision approval/rejection |
| `ThresholdsController` | Compliance thresholds, e-flow, IIMA allocations |
| `DisasterController` | Disaster/hazard incident management |
| `DocumentUploadController` | Private library document upload/download |
| `LibraryController` | Library page rendering |
| `UsersController` | User management (admin) |
| `AuditController` | Audit log view and export |
| `CommentsController` | Polymorphic comments and mentions |
| `SettingsController` | User preferences and profile |
| `DashboardController` | Dashboard summary data |

Auth controllers live in `app/Http/Controllers/Auth/`:

| Controller | Responsibility |
|---|---|
| `FirebaseAuthController` | Firebase token verification, session creation, logout |
| `RegistrationPinController` | PIN verify and reserve endpoints |
| `RegistrationInviteController` | One-time invite link generation and consumption |

### Services
Business logic that spans multiple models is extracted into services under `app/Services/`:

| Service | Responsibility |
|---|---|
| `MeasurementStateManager` | Transitions measurements between `pending → approved / rejected` states |
| `RegistrationService` | Creates or updates a User record from a verified Firebase token |
| `AuditService` | Writes entries to the audit log |
| `CommentMentionService` | Parses `@mention` tokens and creates notification records |
| `StationRevisionManager` | Applies approved station revisions to the station record |

### Queries
`app/Queries/StationMeasurementQuery.php` is a fluent query builder that encapsulates the complex joins required across `measurements`, `stations`, `station_capabilities`, `compliance_thresholds`, and `water_quality_parameters`. All GIS pages use it as their primary data source.

---

## 4. Authentication Flow

```
[Browser]
  1. User enters credentials → Firebase Client SDK authenticates
  2. Firebase returns a signed ID token (JWT)
  3. Browser POSTs id_token to POST /auth/firebase

[Laravel: FirebaseAuthController::authenticate()]
  4. Firebase Admin SDK (kreait) calls verifyIdToken(id_token)
     - Validates signature against Firebase public keys
     - Checks token expiry and revocation status
  5. If valid → extract uid, email, display_name, picture, email_verified
  6. RegistrationService::findOrCreateUser() → upsert User record
  7. Auth::login($user) → Laravel session created
  8. Redirect to /dashboard

[Subsequent requests]
  - Standard Laravel session cookie (stored in `sessions` DB table)
  - No Firebase token needed after login
```

### PIN-Gated Registration Flow
New users must have a valid invitation PIN before completing registration:

```
POST /register/pin/verify  → checks PIN exists and is unused
POST /register/pin/reserve → marks PIN as reserved for the registering email
POST /auth/firebase        → fires with id_token + registration_pin
                             → RegistrationService validates PIN ownership
                             → creates User and consumes PIN
```

All three endpoints are protected by `throttle:firebase-auth` rate limiting.

---

## 5. GIS 3-State Data Model

Each measurement record has a `status` field with one of three values:

| Status | Meaning |
|---|---|
| `pending` | Submitted by a clerk; awaiting review |
| `approved` | Accepted by a manager or admin; counted as live data |
| `rejected` | Rejected by a manager or admin; preserved for audit |

GIS pages receive three separate data arrays from the controller:
- `liveData` — approved measurements, latest per station
- `pendingData` — pending measurements awaiting review
- `historicalData` — all approved measurements (paginated or filtered by date range)

The `MeasurementStateManager` service enforces valid transitions and records the reviewer, timestamp, and optional review notes. Managers and admins can override their own submissions (`is_self_override = true`).

---

## 6. Polymorphic Comments

The `comments` table uses a polymorphic relationship via `commentable_type` / `commentable_id` columns. Comments can be attached to:

- `Measurement` — on individual data readings
- `Station` — on station profile pages
- `StationRevision` — on revision requests
- `DisasterIncident` — on disaster incident records

The `CommentMentionService` parses `@display_name` tokens in comment bodies, resolves them to user IDs, and creates `comment_mentions` records. The notification bell in the app shell queries `comment_mentions` for unread counts.

---

## 7. Document Storage

Documents are stored using Laravel's filesystem abstraction (`Storage::disk('local')`). File metadata (name, MIME type, size, uploader, timestamps) is persisted in the `documents` table. Downloads are served via a signed controller action (`DocumentUploadController::download`) that authorises the user before streaming the file — files are never exposed directly via the web server.

---

## 8. Audit Logging

`AuditService::log()` writes a record to `audit_logs` for every significant state change. Entries capture:
- `actor_id` — the authenticated user
- `action` — human-readable action string (e.g. `measurement.approved`)
- `auditable_type` / `auditable_id` — the affected model (polymorphic)
- `metadata` — JSON blob with before/after values or relevant context
- `created_at` — immutable timestamp

The audit log table is append-only by convention; no application code updates or deletes rows.
