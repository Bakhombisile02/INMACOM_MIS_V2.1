# Database Schema — INMACOM MIS V2.1

This document describes every table in the INMACOM MIS database, its purpose, key fields, and relationships. All domain model primary keys are UUIDs.

---

## Core Laravel Tables

| Table | Purpose |
|---|---|
| `users` | Authenticated users — Firebase UID, role, profile, preferences |
| `sessions` | Laravel database session storage |
| `cache` | Database cache store |
| `jobs` / `job_batches` / `failed_jobs` | Laravel queue job tables |
| `password_reset_tokens` | Laravel password reset flow |

---

## User (`users`)

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `firebase_uid` | string unique | Firebase Authentication UID |
| `display_name` | string | User's full name |
| `email` | string unique | |
| `email_verified_at` | timestamp nullable | |
| `password` | string nullable | argon2id hash; may be null for Google SSO users |
| `role` | string | `admin`, `manager`, or `clerk` |
| `photo_url` | string nullable | Firebase profile photo URL |
| `country` | string nullable | |
| `organization` | string nullable | |
| `telephone` | string nullable | |
| `preferences` | JSON nullable | Language, notification settings, etc. |
| `remember_token` | string nullable | |
| `created_at` / `updated_at` | timestamp | |

---

## Domain Tables

### `stations`

Monitoring stations across the Incomati basin.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `code` | string unique | Short station code (e.g. `B1H001`) |
| `name` | string | Human-readable station name |
| `latitude` | float | WGS 84 |
| `longitude` | float | WGS 84 |
| `category` | string | Station category (e.g. `flow`, `climate`) |
| `water_source` | string | Source type (e.g. `river`, `groundwater`) |
| `water_body_type` | string | (e.g. `perennial`, `ephemeral`) |
| `is_active` | boolean | Whether the station is currently operational |
| `is_real_time` | boolean | Whether telemetry provides real-time data |
| `summary` | text nullable | Description |
| `telemetry_system` | string nullable | Telemetry hardware/provider |
| `gauge_code` | string nullable | Legacy gauge reference code |
| `owner_org` | string nullable | Owning organisation |
| `country` | string nullable | `ZA`, `MZ`, or `SZ` |
| `river_basin` | string nullable | Sub-basin name |

### `station_capabilities`

Which measurement types each station can record. Composite PK.

| Column | Type | Notes |
|---|---|---|
| `station_id` | UUID FK → stations | |
| `measurement_type` | string | `flow`, `dam_level`, `water_quality`, `rainfall`, `groundwater` |
| `is_primary` | boolean | Whether this is the station's primary measurement type |
| `installed_at` | date nullable | |
| `notes` | text nullable | |

### `station_operational_statuses`

History of operational status changes for a station.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `station_id` | UUID FK → stations | |
| `status` | string | e.g. `operational`, `maintenance`, `offline` |
| `reason` | text nullable | |
| `reported_by_id` | UUID FK → users | |
| `started_at` | timestamp | |
| `expected_resolution_at` | timestamp nullable | |
| `resolved_at` | timestamp nullable | |

### `station_revisions`

Proposed changes to a station, submitted by any user and approved/rejected by managers/admins.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `station_id` | UUID FK → stations | |
| `submitted_by_id` | UUID FK → users | |
| `submitted_at` | timestamp | |
| `status` | string | `pending`, `approved`, `rejected` |
| `changes` | JSON | Map of field → new value |
| `reviewed_by_id` | UUID FK → users nullable | |
| `reviewed_at` | timestamp nullable | |
| `review_notes` | text nullable | |
| `is_create_request` | boolean | If true, this revision creates a new station |

---

### `measurements`

Central table for all hydrological measurement readings.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `station_id` | UUID FK → stations | |
| `measurement_type` | string | `flow`, `dam_level`, `water_quality`, `rainfall`, `groundwater` |
| `parameter_id` | UUID FK → water_quality_parameters nullable | Required for `water_quality` type |
| `value` | float | Measured value |
| `unit` | string | Unit of measurement |
| `fsc` | float nullable | Full Supply Capacity (dam levels) |
| `date` | timestamp | Date/time of measurement |
| `status` | string | `pending`, `approved`, `rejected` |
| `submitted_by_id` | UUID FK → users | |
| `submitted_at` | timestamp | |
| `reviewed_by_id` | UUID FK → users nullable | |
| `reviewed_at` | timestamp nullable | |
| `review_notes` | text nullable | |
| `is_self_override` | boolean nullable | True when submitter bypassed the approval queue |

### `water_quality_parameters`

Reference list of water quality parameters (pH, turbidity, dissolved oxygen, etc.).

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `code` | string unique | Short code (e.g. `PH`, `DO`) |
| `name` | string | |
| `description` | text nullable | |
| `default_unit` | string nullable | |
| `display_order` | integer | UI sort order |
| `is_active` | boolean | |

### `compliance_thresholds`

Min/max thresholds for each station + measurement type + parameter combination.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `station_id` | UUID FK → stations | |
| `data_type` | string | Measurement type |
| `parameter_id` | UUID FK → water_quality_parameters | |
| `min_value` | float nullable | |
| `max_value` | float nullable | |
| `unit` | string nullable | |
| `notes` | text nullable | |

---

## Management Areas

### `management_areas`

Subcatchments and management zones within the Incomati basin.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `code` | string unique | |
| `name` | string | |
| `basin` | string | |
| `is_active` | boolean | |
| `country` | string nullable | |
| `description` | text nullable | |

### `management_area_stations`

Many-to-many link between management areas and stations.

| Column | Type |
|---|---|
| `management_area_id` | UUID FK → management_areas |
| `station_id` | UUID FK → stations |

---

## IIMA Compliance

### `iima_user_categories`

Water user categories defined by the IIMA treaty (e.g. Urban, Agriculture, Environment).

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `code` | string unique | |
| `name` | string | |
| `description` | text nullable | |
| `assurance_pct_primary` | float nullable | |
| `allocation_share_primary` | float nullable | |
| `assurance_pct_secondary` | float nullable | |
| `allocation_share_secondary` | float nullable | |
| `priority_order` | integer | Water allocation priority |

### `iima_restriction_levels`

Curtailment levels per user category under water shortage conditions. Composite PK.

| Column | Type |
|---|---|
| `level` | integer |
| `user_category` | string FK → iima_user_categories.code |
| `max_curtailment_pct` | float nullable |
| `description` | text nullable |

### `iima_allocations`

Annual water allocations per subcatchment, country, and user category.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `subcatchment_id` | UUID FK → management_areas | |
| `country` | string | `ZA`, `MZ`, `SZ` |
| `user_category` | string FK → iima_user_categories.code | |
| `allocation_mm3_a` | float | Annual allocation in million m³ |
| `effective_from` | integer | Year the allocation became effective |
| `note` | text nullable | |

### `iima_eflow_requirements`

Environmental flow requirements per key point, as defined in IIMA treaty annexures.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `subcatchment_id` | UUID FK → management_areas | |
| `river` | string | |
| `key_point` | string | Measurement point name |
| `station_id` | UUID FK → stations nullable | Associated monitoring station |
| `mean_annual_mm3` | float | Mean annual runoff in million m³ |
| `min_flow_m3_s` | float | Minimum flow requirement |
| `source_article` | string nullable | Treaty article reference |
| `note` | text nullable | |

### `water_abstractions`

Reported water abstraction volumes per subcatchment, country, and user category.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `subcatchment_id` | UUID FK → management_areas | |
| `country` | string | |
| `user_category` | string FK → iima_user_categories.code | |
| `period_year` | integer | |
| `period_month` | integer nullable | |
| `volume_mm3` | float | |
| `data_source` | string nullable | |
| `is_estimate` | boolean | |
| `status` | string | `pending`, `approved`, `rejected` |
| `submitted_by_id` | UUID FK → users | |
| `submitted_at` | timestamp | |
| `reviewed_by_id` | UUID FK → users nullable | |
| `reviewed_at` | timestamp nullable | |
| `review_notes` | text nullable | |

---

## Hazard & Disaster Management

### `hazard_types`

Types of hazards tracked by the system (e.g. drought, flood, pollution).

| Column | Type |
|---|---|
| `id` | UUID PK |
| `code` | string unique |
| `name` | string |
| `description` | text nullable |

### `hazard_status_levels`

Named severity levels per hazard type. Composite PK.

| Column | Type |
|---|---|
| `hazard_code` | string FK → hazard_types.code |
| `level_code` | string |
| `name` | string |
| `severity` | integer |
| `color` | string nullable |
| `description` | text nullable |
| `actions_required` | text nullable |

### `hazard_status_current`

Current hazard status per management area (one row per hazard + area combination).

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `hazard_code` | string FK → hazard_types.code | |
| `area_id` | UUID FK → management_areas | |
| `level_code` | string | FK → hazard_status_levels |
| `calculated_at` | timestamp | |
| `score` | float nullable | Computed risk score |
| `next_review_at` | timestamp nullable | |
| `calculated_by_id` | UUID FK → users nullable | |
| `calculation_notes` | text nullable | |

### `hazard_status_history`

Historical log of hazard status changes.

*Same columns as `hazard_status_current` plus `previous_level`.*

### `hazard_indicators`

Individual risk indicators that feed into hazard score calculation.

| Column | Type |
|---|---|
| `id` | UUID PK |
| `hazard_code` | string FK → hazard_types.code |
| `code` | string (unique with hazard_code) |
| `name` | string |
| `description` | text nullable |
| `data_source` | string nullable |
| `unit` | string nullable |
| `is_active` | boolean |

### `hazard_indicator_thresholds` / `hazard_indicator_weights` / `hazard_status_lookups`

Supporting tables for hazard score computation — rating scales, indicator weights per area, and score-to-level lookup tables.

### `disaster_incidents`

Reported disaster or hazard incidents requiring active management.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `reference` | string unique | Auto-generated reference number |
| `hazard_code` | string FK → hazard_types.code | |
| `title` | string | |
| `incident_status` | string | e.g. `active`, `resolved` |
| `reported_at` | timestamp | |
| `submitted_by_id` | UUID FK → users | |
| `submitted_at` | timestamp | |
| `review_status` | string | `pending`, `approved`, `rejected` |
| `description` | text nullable | |
| `severity_level` | string nullable | |
| `area_id` | UUID FK → management_areas nullable | |
| `latitude` / `longitude` | float nullable | Incident location |
| `affected_radius_km` | float nullable | |
| `occurred_at` | timestamp nullable | |
| `resolved_at` | timestamp nullable | |
| `incident_commander_id` | UUID FK → users nullable | |
| `reviewed_by_id` | UUID FK → users nullable | |
| `reviewed_at` / `review_notes` | — | |

### `pollution_incident_details`

Extended fields for pollution-type incidents (one-to-one with `disaster_incidents`).

Includes USEPA category, pollutant name, CAS number, estimated mass/volume, fish kill observations, and waterborne disease flags.

### `incident_stations` / `incident_actions` / `incident_notifications`

Supporting tables linking incidents to affected stations, recording response actions, and tracking notifications sent to recipient organisations.

---

## Documents

### `documents`

Document library entries for the private (authenticated) library.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `title` | string | |
| `description` | text nullable | |
| `category` | string nullable | |
| `original_filename` | string | |
| `stored_filename` | string | Internal storage path |
| `mime_type` | string | |
| `size_bytes` | integer | |
| `uploaded_by_id` | UUID FK → users | |
| `created_at` / `updated_at` | timestamp | |

---

## Registration

### `registration_pins`

One-time PINs for user registration, managed by admins.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `pin` | string (6 chars) | The 6-digit code |
| `role` | string | Role to assign on use |
| `created_by_id` | UUID FK → users | |
| `reserved_for_email` | string nullable | Set when PIN is reserved |
| `used_at` | timestamp nullable | |
| `expires_at` | timestamp nullable | |
| `invite_token` | string nullable | Unique token for one-time invite link |
| `invite_sent_at` | timestamp nullable | |
| `invite_email` | string nullable | Email the invite was sent to |

---

## Comments

### `comments`

Polymorphic comment records.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `body` | text | Raw comment body (may contain @mention tokens) |
| `commentable_type` | string | Model class name |
| `commentable_id` | UUID | FK to the commented-on model |
| `author_id` | UUID FK → users | |
| `is_resolved` | boolean | |
| `resolved_by_id` | UUID FK → users nullable | |
| `resolved_at` | timestamp nullable | |
| `created_at` / `updated_at` | timestamp | |

### `comment_mentions`

Notification records for `@mention` tokens parsed from comment bodies.

| Column | Type |
|---|---|
| `id` | UUID PK |
| `comment_id` | UUID FK → comments |
| `mentioned_user_id` | UUID FK → users |
| `read_at` | timestamp nullable |

---

## Audit Log

### `audit_logs`

Append-only audit trail of significant system actions.

| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `actor_id` | UUID FK → users nullable | |
| `action` | string | e.g. `measurement.approved` |
| `auditable_type` | string | Model class name |
| `auditable_id` | string | UUID of the affected model |
| `metadata` | JSON nullable | Before/after values, context |
| `ip_address` | string nullable | |
| `created_at` | timestamp | Immutable; no `updated_at` |
