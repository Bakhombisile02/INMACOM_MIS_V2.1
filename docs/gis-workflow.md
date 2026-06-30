# GIS Workflow — INMACOM MIS V2.1

This document describes the full lifecycle of GIS measurement data in INMACOM MIS: from initial entry through the 2-step approval workflow to live and historical records.

---

## Overview

INMACOM MIS tracks five types of hydrological measurements:

| Type | Code | Pages |
|---|---|---|
| Flow / River Levels | `flow` | Flow Levels |
| Dam Levels | `dam_level` | Dam Levels |
| Water Quality | `water_quality` | Water Quality |
| Rainfall | `rainfall` | Rainfall |
| Groundwater | `groundwater` | Groundwater |

Each measurement goes through a **3-state lifecycle** before appearing as live data on the dashboard and map.

---

## Data Lifecycle Diagram

```mermaid
flowchart TD
    A([Clerk / Manager / Admin]) -->|POST /measurements| B[Measurement created\nstatus: pending]
    B --> C{Reviewed by\nManager or Admin}
    C -->|POST /measurements/{id}/approve| D[status: approved\nLive data ✅]
    C -->|POST /measurements/{id}/reject| E[status: rejected\nArchived ❌]
    D --> F[Historical record\nquery with date range]
    E --> F

    G([Manager / Admin]) -->|POST /flow-levels/import\nPOST /dam-levels/import\netc.| H{Bulk import}
    H -->|is_self_override: true| D
    H -->|normal| B
```

---

## States

| State | Description | Who sets it |
|---|---|---|
| `pending` | Submitted; awaiting review | Any authenticated user on submission |
| `approved` | Accepted; counted as live data | Manager or Admin |
| `rejected` | Rejected; preserved for audit | Manager or Admin |

---

## Step 1 — Submitting a Measurement

Any authenticated user (clerk, manager, or admin) can submit a new measurement:

**Endpoint:** `POST /measurements`

**Required fields:**
```json
{
  "station_id":        "uuid — must have the relevant capability",
  "measurement_type":  "flow | dam_level | water_quality | rainfall | groundwater",
  "value":             "numeric",
  "unit":              "string (e.g. m³/s, m, mg/L, mm, m bgl)",
  "date":              "ISO 8601 datetime",
  "parameter_id":      "uuid — required for water_quality type only",
  "fsc":               "numeric — Full Supply Capacity, optional for dam levels"
}
```

The `MeasurementStateManager` service creates the record with `status = pending`, `submitted_by_id` = current user, and `submitted_at` = now.

### Self-Override (Managers & Admins)
Managers and admins can bypass the approval step by setting `is_self_override = true` in their submission. The measurement is created directly with `status = approved`.

---

## Step 2 — Review

A manager or admin sees all `pending` measurements in the **Pending** tab of each GIS page.

### Approve
**Endpoint:** `POST /measurements/{id}/approve`  
**Request:** `{ "notes": "string (optional)" }`

The `MeasurementStateManager` transitions:
- `status` → `approved`
- `reviewed_by_id` → current user
- `reviewed_at` → now
- `review_notes` → provided notes

### Reject
**Endpoint:** `POST /measurements/{id}/reject`  
**Request:** `{ "notes": "string (required)" }`

Transitions `status` → `rejected` with the same reviewer fields.

---

## GIS Page Data Arrays

Each GIS page controller action (e.g. `GisController::flowLevels()`) passes three arrays to the Inertia page:

| Prop | Content |
|---|---|
| `liveData` | Latest `approved` measurement per station (or per station + parameter for water quality) |
| `pendingData` | All `pending` measurements |
| `historicalData` | All `approved` measurements, ordered by date descending |

The frontend renders these in separate tabs: **Live**, **Pending**, and **Historical**.

---

## Bulk CSV Import

Managers and admins can import measurements in bulk from CSV/Excel files using the import templates in the `import templates/` directory.

| Import endpoint | Route name |
|---|---|
| `POST /flow-levels/import` | `measurements.flow.import` |
| `POST /dam-levels/import` | `measurements.dam.import` |
| `POST /water-quality/import` | `measurements.wq.import` |
| `POST /rainfall/import` | `measurements.rainfall.import` |
| `POST /groundwater/import` | `measurements.groundwater.import` |

**Request:** `multipart/form-data` with a `file` field containing the CSV/XLSX.

Each row in the import is validated against the station capabilities and the required fields for the measurement type. Valid rows are created as `approved` measurements (bulk imports bypass the pending queue). Invalid rows are reported in the flash message summary.

---

## Historical Data

Both authenticated and public users can query historical data for a specific station:

- **Authenticated:** `GET /stations/{id}/historical-data?from=YYYY-MM-DD&to=YYYY-MM-DD&type=flow`
- **Public:** `GET /public/stations/{id}/historical-data`

The response is a JSON array of approved measurements within the requested date range, ordered by `date` ascending.

---

## Water Quality — Multi-Parameter

Water quality measurements differ from other types because each reading is associated with a specific **water quality parameter** (e.g. pH, dissolved oxygen, turbidity — defined in `water_quality_parameters`).

The `liveData` for water quality is grouped by station, with the latest approved reading for each parameter shown as a row in the parameter table. Compliance status is calculated against `compliance_thresholds` per station and parameter.

---

## Compliance Thresholds

Each station can have `compliance_thresholds` configured for any measurement type and parameter. When querying live data, `StationMeasurementQuery` joins the threshold table and returns:

- `min_value` / `max_value` — threshold bounds
- A computed `compliant` flag (value within bounds)

Thresholds are managed via the `/thresholds` page (admin/manager only).

---

## Map Display

Each GIS page renders an interactive Leaflet map (via `GisMap` component) showing all **active stations** that have the relevant capability. Clicking a station marker opens a popup with the latest live reading and a link to the historical data modal.

Station coordinates come from the `stations` table (`latitude`, `longitude`).

---

## Audit Trail

Every approval, rejection, and bulk import action is written to `audit_logs` by `AuditService`. This provides an immutable record of who reviewed each measurement and when.
