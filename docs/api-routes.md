# API Routes Reference — INMACOM MIS V2.1

INMACOM MIS uses **Inertia.js** — there is no separate REST API. All routes return either an Inertia page response (HTML on first visit, JSON on subsequent navigations) or a redirect. This document summarises every route, its request shape, and its response.

---

## Conventions

- **Auth** column: `public` = no auth required; `auth` = any authenticated user; `admin`, `manager`, `clerk` = role-specific.
- Request bodies use `application/x-www-form-urlencoded` or `multipart/form-data` (file uploads) via Inertia form submissions.
- Successful mutations return a redirect with flash data. Validation failures return back with `errors` in the page props.
- CSV exports return a `StreamedResponse` with `Content-Type: text/csv`.

---

## Public Routes

### `GET /`
**Name:** `landing`  
**Auth:** public  
**Response:** Inertia `Landing/Index` (or redirect to `/dashboard` if authenticated)

### `GET /documents`
**Name:** `documents`  
**Auth:** public  
**Response:** Inertia `Public/Documents` — list of publicly available documents

### `GET /explore`
**Name:** `explore`  
**Auth:** public  
**Response:** Inertia `Public/Explore` — public GIS map with all active stations

### `GET /public/stations/{id}/historical-data`
**Name:** `public.stations.historical-data`  
**Auth:** public  
**Response:** JSON — historical measurements for a single station

---

## Authentication Routes

### `GET /login`
**Name:** `login` | **Auth:** guest  
**Response:** Inertia `Auth/LoginOptions`

### `POST /auth/firebase`
**Name:** `auth.firebase` | **Auth:** guest + throttle  
**Request:**
```json
{ "id_token": "string (required)", "registration_pin": "string|null (6 chars)" }
```
**Response:** Redirect to `/dashboard` on success; back with `errors.auth` on failure

### `POST /register/pin/verify`
**Name:** `register.pin.verify` | **Auth:** guest + throttle  
**Request:** `{ "pin": "string (6 chars)" }`  
**Response:** JSON `{ "valid": true }` or validation error

### `POST /register/pin/reserve`
**Name:** `register.pin.reserve` | **Auth:** guest + throttle  
**Request:** `{ "pin": "string", "email": "string" }`  
**Response:** JSON success or error

### `POST /logout`
**Name:** `logout` | **Auth:** auth  
**Response:** Redirect to `/login`

### `POST /invites`
**Name:** `invites.store` | **Auth:** admin  
**Request:** `{ "email": "string", "role": "admin|manager|clerk", "expires_at": "date|null" }`  
**Response:** Back with flash success or errors

### `DELETE /invites/{pin}`
**Name:** `invites.destroy` | **Auth:** admin  
**Response:** Back with flash success

### `PATCH /invites/{pin}/resend`
**Name:** `invites.resend` | **Auth:** admin  
**Response:** Back with flash success

---

## Dashboard

### `GET /dashboard`
**Name:** `dashboard` | **Auth:** auth  
**Response:** Inertia `Dashboard/Index` — summary counts, recent measurements, hazard status

---

## Stations

### `GET /stations`
**Name:** `stations.index` | **Auth:** auth  
**Response:** Inertia `Stations/Index` — paginated station list with capabilities

### `POST /stations`
**Name:** `stations.store` | **Auth:** admin, manager  
**Request:**
```json
{
  "code": "string",
  "name": "string",
  "latitude": "float",
  "longitude": "float",
  "category": "string",
  "water_source": "string",
  "water_body_type": "string",
  "is_active": "boolean",
  "is_real_time": "boolean",
  "country": "string|null",
  "river_basin": "string|null",
  "capabilities": ["flow", "water_quality", ...]
}
```
**Response:** Redirect to `stations.show` or back with errors

### `POST /stations/import`
**Name:** `stations.import` | **Auth:** admin, manager  
**Request:** `multipart/form-data` — `file` (CSV/XLSX)  
**Response:** Back with import summary flash message

### `GET /stations/{station}`
**Name:** `stations.show` | **Auth:** auth  
**Response:** Inertia `Stations/Show` — full station detail, capabilities, revisions, comments

### `GET /stations/{station}/export-csv`
**Name:** `stations.export-csv` | **Auth:** auth  
**Response:** `StreamedResponse` (CSV)

### `PATCH /stations/{station}`
**Name:** `stations.update` | **Auth:** auth  
**Request:** Same fields as `stations.store` (partial)  
**Response:** Redirect back or to `stations.show`; clerks create a revision instead of direct update

### `DELETE /stations/{station}`
**Name:** `stations.destroy` | **Auth:** admin  
**Response:** Redirect to `stations.index`

---

## Station Revisions

### `POST /station-revisions/{stationRevision}/approve`
**Name:** `station-revisions.approve` | **Auth:** admin, manager  
**Request:** `{ "notes": "string|null" }`  
**Response:** Back with flash

### `POST /station-revisions/{stationRevision}/reject`
**Name:** `station-revisions.reject` | **Auth:** admin, manager  
**Request:** `{ "notes": "string" }`  
**Response:** Back with flash

---

## Thresholds & Allocations

### `GET /thresholds`
**Name:** `thresholds.index` | **Auth:** auth  
**Response:** Inertia `Thresholds/Index`

### `POST /thresholds/compliance/group`
**Name:** `thresholds.compliance.group` | **Auth:** admin, manager  
**Request:** `{ "station_id": "uuid", "data_type": "string", "thresholds": [...] }`  
**Response:** Back with flash

### `PATCH /thresholds/compliance/{id}`
**Name:** `thresholds.compliance.update` | **Auth:** admin, manager  
**Request:** `{ "min_value": "float|null", "max_value": "float|null", "unit": "string|null" }`  
**Response:** Back with flash

### `POST /thresholds/compliance/{id}/reset`
**Name:** `thresholds.compliance.reset` | **Auth:** admin, manager  
**Response:** Back with flash

### `PATCH /thresholds/eflow/{id}`
**Name:** `thresholds.eflow.update` | **Auth:** admin, manager  
**Request:** `{ "min_flow_m3_s": "float", "note": "string|null" }`  
**Response:** Back with flash

### `POST /thresholds/allocations`
**Name:** `thresholds.allocations.store` | **Auth:** admin, manager  
**Request:** `{ "subcatchment_id": "uuid", "country": "string", "user_category": "string", "allocation_mm3_a": "float", "effective_from": "integer" }`  
**Response:** Back with flash

### `PATCH /thresholds/allocations/{id}`
**Name:** `thresholds.allocations.update` | **Auth:** admin, manager  
**Request:** Partial allocation fields  
**Response:** Back with flash

### `DELETE /thresholds/allocations/{id}`
**Name:** `thresholds.allocations.destroy` | **Auth:** admin, manager  
**Response:** Back with flash

### `PATCH /thresholds/hazard`
**Name:** `thresholds.hazard.update` | **Auth:** admin, manager  
**Request:** `{ "hazard_code": "string", "area_id": "uuid", "level_code": "string", "score": "float|null", "notes": "string|null" }`  
**Response:** Back with flash

---

## GIS Data Pages

### `GET /flow-levels`
**Name:** `flow-levels.index` | **Auth:** auth  
**Response:** Inertia `Gis/FlowLevels` — props: `liveData`, `pendingData`, `historicalData`, `stations`

### `GET /flow-levels/export-csv`
**Name:** `flow-levels.export-csv` | **Auth:** auth  
**Response:** `StreamedResponse` (CSV)

*(Same pattern for `/dam-levels`, `/water-quality`, `/rainfall`, `/groundwater`)*

---

## Measurements

### `POST /measurements`
**Name:** `measurements.store` | **Auth:** auth  
**Request:**
```json
{
  "station_id": "uuid",
  "measurement_type": "flow|dam_level|water_quality|rainfall|groundwater",
  "parameter_id": "uuid|null",
  "value": "float",
  "unit": "string",
  "date": "datetime",
  "fsc": "float|null"
}
```
**Response:** Back with flash or errors

### `PATCH /measurements/{id}`
**Name:** `measurements.update` | **Auth:** auth  
**Request:** Partial measurement fields  
**Response:** Back with flash

### `DELETE /measurements/{id}`
**Name:** `measurements.destroy` | **Auth:** auth  
**Response:** Back with flash

### `POST /measurements/{id}/approve`
**Name:** `measurements.approve` | **Auth:** admin, manager  
**Request:** `{ "notes": "string|null" }`  
**Response:** Back with flash

### `POST /measurements/{id}/reject`
**Name:** `measurements.reject` | **Auth:** admin, manager  
**Request:** `{ "notes": "string" }`  
**Response:** Back with flash

### `GET /stations/{id}/historical-data`
**Name:** `stations.historical-data` | **Auth:** auth  
**Query params:** `from`, `to` (ISO dates), `type` (measurement type)  
**Response:** JSON array of historical measurements

### Bulk Imports
`POST /flow-levels/import`, `POST /dam-levels/import`, `POST /water-quality/import`, `POST /rainfall/import`, `POST /groundwater/import`  
**Auth:** admin, manager  
**Request:** `multipart/form-data` — `file` (CSV/XLSX matching the import template)  
**Response:** Back with import summary flash

---

## Disaster Management

### `GET /disaster-management`
**Name:** `disaster.index` | **Auth:** auth  
**Response:** Inertia `Disaster/Index` — incidents list, hazard status dashboard

---

## Users

### `GET /users`
**Name:** `users.index` | **Auth:** auth  
**Response:** Inertia `Users/Index`

### `PATCH /users/{user}`
**Name:** `users.update` | **Auth:** admin  
**Request:** `{ "role": "admin|manager|clerk", "display_name": "string|null" }`  
**Response:** Back with flash

### `DELETE /users/{user}`
**Name:** `users.destroy` | **Auth:** admin  
**Response:** Back with flash

### `POST /users/{user}/password-reset`
**Name:** `users.password-reset` | **Auth:** admin  
**Response:** Back with flash (triggers Firebase password reset email)

### `GET /users/search`
**Name:** `users.search` | **Auth:** auth  
**Query params:** `q` (search string)  
**Response:** JSON array `[{ "id": "uuid", "display_name": "string", "email": "string" }]`

---

## Document Library

### `GET /library`
**Name:** `library` | **Auth:** auth  
**Response:** Inertia `Library/Index` — document list with metadata

### `POST /library/documents`
**Name:** `library.documents.store` | **Auth:** admin, manager  
**Request:** `multipart/form-data` — `file`, `title`, `description|null`, `category|null`  
**Response:** Back with flash or errors

### `GET /library/documents/{document}/download`
**Name:** `library.documents.download` | **Auth:** auth  
**Response:** `StreamedResponse` (file download)

---

## Audit Log

### `GET /audit`
**Name:** `audit.index` | **Auth:** admin, manager  
**Response:** Inertia `Audit/Index` — paginated log entries

### `GET /audit/export`
**Name:** `audit.export` | **Auth:** admin  
**Response:** `StreamedResponse` (CSV)

---

## Comments & Mentions

### `POST /comments`
**Name:** `comments.store` | **Auth:** auth  
**Request:** `{ "body": "string", "commentable_type": "string", "commentable_id": "uuid" }`  
**Response:** Back with flash

### `PATCH /comments/{comment}/resolve`
**Name:** `comments.resolve` | **Auth:** auth  
**Response:** Back with flash

### `DELETE /comments/{comment}`
**Name:** `comments.destroy` | **Auth:** auth  
**Response:** Back with flash

### `GET /comments/mentions/me`
**Name:** `comments.mentions` | **Auth:** auth  
**Response:** JSON — list of unread mention notifications

### `POST /comments/mentions/read`
**Name:** `comments.mentions.read` | **Auth:** auth  
**Request:** `{ "mention_ids": ["uuid", ...] }`  
**Response:** JSON success

---

## Settings

### `GET /settings`
**Name:** `settings.index` | **Auth:** auth  
**Response:** Inertia `Settings/Index`

### `PATCH /settings/profile`
**Name:** `settings.profile.update` | **Auth:** auth  
**Request:** `{ "display_name": "string", "country": "string|null", "organization": "string|null", "telephone": "string|null" }`  
**Response:** Back with flash

### `PATCH /settings/preferences`
**Name:** `settings.preferences.update` | **Auth:** auth  
**Request:** `{ "language": "en|pt", "notifications_enabled": "boolean", ... }`  
**Response:** Back with flash
