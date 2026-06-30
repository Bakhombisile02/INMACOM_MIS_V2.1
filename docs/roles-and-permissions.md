# Roles & Permissions — INMACOM MIS V2.1

This document lists every named route in the application and which roles can access it.

## Role Definitions

| Role | Code | Description |
|---|---|---|
| Administrator | `admin` | Full system access; manages users, deletes stations, exports audit log |
| Manager / Data Manager | `manager` | Approves data; manages stations, thresholds, and allocations; uploads documents |
| Clerk / Data Entry | `clerk` | Submits measurements and station revision requests; reads all data |

Roles are enforced server-side by the `role` middleware (e.g. `middleware('role:admin,manager')`). All authenticated routes additionally require `auth` and `verified` middleware.

---

## Authentication & Registration Routes (`routes/auth.php`)

| Method | Path | Route Name | Auth Requirement | Notes |
|---|---|---|---|---|
| GET | `/login` | `login` | Guest only | Firebase login options page |
| GET | `/login/email` | `login.email` | Guest only | Email/password login page |
| GET | `/register` | `register` | Guest only | Registration options page |
| GET | `/register/email` | `register.email` | Guest only | Email/password registration page |
| POST | `/register/pin/verify` | `register.pin.verify` | Guest + throttle:firebase-auth | Verify a registration PIN |
| POST | `/register/pin/reserve` | `register.pin.reserve` | Guest + throttle:firebase-auth | Reserve PIN for a specific email |
| GET | `/register/invite/{token}` | `register.invite` | Guest only | One-time invite link |
| POST | `/auth/firebase` | `auth.firebase` | Guest + throttle:firebase-auth | Verify Firebase ID token, create session |
| POST | `/logout` | `logout` | Authenticated | Destroy session |
| POST | `/invites` | `invites.store` | `admin` (enforced in controller) | Create invitation PIN |
| DELETE | `/invites/{pin}` | `invites.destroy` | `admin` (enforced in controller) | Delete invitation PIN |
| PATCH | `/invites/{pin}/resend` | `invites.resend` | `admin` (enforced in controller) | Resend invite email |

---

## Main Application Routes (`routes/web.php`)

### Public Routes (no authentication required)

| Method | Path | Route Name | Notes |
|---|---|---|---|
| GET | `/` | `landing` | Redirects to dashboard if authenticated |
| GET | `/documents` | `documents` | Public document library |
| GET | `/explore` | `explore` | Public GIS map explorer |
| GET | `/public/stations/{id}/historical-data` | `public.stations.historical-data` | Public historical data for a station |

### Dashboard

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| GET | `/dashboard` | `dashboard` | ✅ | ✅ | ✅ |

### Stations

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| GET | `/stations` | `stations.index` | ✅ | ✅ | ✅ |
| POST | `/stations` | `stations.store` | ✅ | ✅ | ❌ |
| POST | `/stations/import` | `stations.import` | ✅ | ✅ | ❌ |
| GET | `/stations/{station}` | `stations.show` | ✅ | ✅ | ✅ |
| GET | `/stations/{station}/export-csv` | `stations.export-csv` | ✅ | ✅ | ✅ |
| PATCH | `/stations/{station}` | `stations.update` | ✅ | ✅ | ✅* |
| DELETE | `/stations/{station}` | `stations.destroy` | ✅ | ❌ | ❌ |

> *Clerks can submit a station revision request via `stations.update`; the controller creates a `StationRevision` record rather than applying changes directly.

### Station Revisions

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| POST | `/station-revisions/{stationRevision}/approve` | `station-revisions.approve` | ✅ | ✅ | ❌ |
| POST | `/station-revisions/{stationRevision}/reject` | `station-revisions.reject` | ✅ | ✅ | ❌ |

### Thresholds & Allocations

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| GET | `/thresholds` | `thresholds.index` | ✅ | ✅ | ✅ |
| POST | `/thresholds/compliance/group` | `thresholds.compliance.group` | ✅ | ✅ | ❌ |
| PATCH | `/thresholds/compliance/{id}` | `thresholds.compliance.update` | ✅ | ✅ | ❌ |
| POST | `/thresholds/compliance/{id}/reset` | `thresholds.compliance.reset` | ✅ | ✅ | ❌ |
| PATCH | `/thresholds/eflow/{id}` | `thresholds.eflow.update` | ✅ | ✅ | ❌ |
| POST | `/thresholds/allocations` | `thresholds.allocations.store` | ✅ | ✅ | ❌ |
| PATCH | `/thresholds/allocations/{id}` | `thresholds.allocations.update` | ✅ | ✅ | ❌ |
| DELETE | `/thresholds/allocations/{id}` | `thresholds.allocations.destroy` | ✅ | ✅ | ❌ |
| PATCH | `/thresholds/hazard` | `thresholds.hazard.update` | ✅ | ✅ | ❌ |

### GIS Data Pages

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| GET | `/flow-levels` | `flow-levels.index` | ✅ | ✅ | ✅ |
| GET | `/flow-levels/export-csv` | `flow-levels.export-csv` | ✅ | ✅ | ✅ |
| GET | `/dam-levels` | `dam-levels.index` | ✅ | ✅ | ✅ |
| GET | `/dam-levels/export-csv` | `dam-levels.export-csv` | ✅ | ✅ | ✅ |
| GET | `/water-quality` | `water-quality.index` | ✅ | ✅ | ✅ |
| GET | `/water-quality/export-csv` | `water-quality.export-csv` | ✅ | ✅ | ✅ |
| GET | `/rainfall` | `rainfall.index` | ✅ | ✅ | ✅ |
| GET | `/rainfall/export-csv` | `rainfall.export-csv` | ✅ | ✅ | ✅ |
| GET | `/groundwater` | `groundwater.index` | ✅ | ✅ | ✅ |
| GET | `/groundwater/export-csv` | `groundwater.export-csv` | ✅ | ✅ | ✅ |

### Measurements (CRUD & Approval)

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| POST | `/measurements` | `measurements.store` | ✅ | ✅ | ✅ |
| PATCH | `/measurements/{id}` | `measurements.update` | ✅ | ✅ | ✅* |
| DELETE | `/measurements/{id}` | `measurements.destroy` | ✅ | ✅ | ✅* |
| POST | `/measurements/{id}/approve` | `measurements.approve` | ✅ | ✅ | ❌ |
| POST | `/measurements/{id}/reject` | `measurements.reject` | ✅ | ✅ | ❌ |
| GET | `/stations/{id}/historical-data` | `stations.historical-data` | ✅ | ✅ | ✅ |

> *Clerks can edit/delete only their own pending measurements (enforced in the controller).

### Bulk Measurement Imports

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| POST | `/flow-levels/import` | `measurements.flow.import` | ✅ | ✅ | ❌ |
| POST | `/dam-levels/import` | `measurements.dam.import` | ✅ | ✅ | ❌ |
| POST | `/water-quality/import` | `measurements.wq.import` | ✅ | ✅ | ❌ |
| POST | `/rainfall/import` | `measurements.rainfall.import` | ✅ | ✅ | ❌ |
| POST | `/groundwater/import` | `measurements.groundwater.import` | ✅ | ✅ | ❌ |

### Disaster Management

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| GET | `/disaster-management` | `disaster.index` | ✅ | ✅ | ✅ |

### Users

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| GET | `/users` | `users.index` | ✅ | ✅ | ❌* |
| PATCH | `/users/{user}` | `users.update` | ✅ | ❌ | ❌ |
| DELETE | `/users/{user}` | `users.destroy` | ✅ | ❌ | ❌ |
| POST | `/users/{user}/password-reset` | `users.password-reset` | ✅ | ❌ | ❌ |
| GET | `/users/search` | `users.search` | ✅ | ✅ | ✅ |

> *`users.index` is accessible to all authenticated users but non-admins see a limited view.

### Document Library

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| GET | `/library` | `library` | ✅ | ✅ | ✅ |
| POST | `/library/documents` | `library.documents.store` | ✅ | ✅ | ❌ |
| GET | `/library/documents/{document}/download` | `library.documents.download` | ✅ | ✅ | ✅ |

### Audit Log

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| GET | `/audit` | `audit.index` | ✅ | ✅ | ❌ |
| GET | `/audit/export` | `audit.export` | ✅ | ❌ | ❌ |

### Comments & Mentions

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| GET | `/comments` | `comments.index` | ✅ | ✅ | ✅ |
| POST | `/comments` | `comments.store` | ✅ | ✅ | ✅ |
| PATCH | `/comments/{comment}/resolve` | `comments.resolve` | ✅ | ✅ | ✅* |
| PATCH | `/comments/{comment}/unresolve` | `comments.unresolve` | ✅ | ✅ | ✅* |
| DELETE | `/comments/{comment}` | `comments.destroy` | ✅ | ✅ | ✅* |
| GET | `/comments/mentions/me` | `comments.mentions` | ✅ | ✅ | ✅ |
| POST | `/comments/mentions/read` | `comments.mentions.read` | ✅ | ✅ | ✅ |

> *Clerks can only resolve/delete their own comments.

### Settings & Profile

| Method | Path | Route Name | `admin` | `manager` | `clerk` |
|---|---|---|:---:|:---:|:---:|
| GET | `/profile` | `profile` | ✅ | ✅ | ✅ |
| GET | `/settings` | `settings.index` | ✅ | ✅ | ✅ |
| PATCH | `/settings/profile` | `settings.profile.update` | ✅ | ✅ | ✅ |
| PATCH | `/settings/preferences` | `settings.preferences.update` | ✅ | ✅ | ✅ |
