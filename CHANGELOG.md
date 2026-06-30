# Changelog

All notable changes to INMACOM MIS are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [2.1.0] — 2026-06-30

### Added

#### GIS Measurements & Approval Workflow
- Five GIS data pages: Flow Levels, Dam Levels, Water Quality, Rainfall, and Groundwater.
- Each page displays live (approved), pending, and historical data in separate tabs.
- Two-step approval workflow: clerks submit pending measurements; managers/admins approve or reject with notes.
- Bulk CSV/Excel import for all five measurement types (admin/manager only).
- CSV export for all GIS pages.
- Interactive Leaflet map on each GIS page showing active station locations.
- Historical data modal/panel accessible from the station map and data tables.

#### Station Management
- Station list and detail pages with full CRUD (admin/manager).
- Station capability tracking (which measurement types each station supports).
- Station revision workflow: clerks submit change requests; managers/admins approve or reject.
- Station CSV export.
- Bulk station import from template.

#### Disaster & Hazard Management
- Disaster incident logging with reference numbers, hazard type, severity, and affected area.
- Hazard status dashboard showing current status per management area.
- Incident action tracking (phase, action type, performer, outcome).
- Incident notification log (recipient, channel, sent/acknowledged timestamps).
- Pollution incident detail sub-form (USEPA category, pollutant, fish kill, waterborne disease flags).

#### Document Library
- Private document library for treaty documents and reports.
- Upload (admin/manager), download (all authenticated users), and metadata display.
- Public document listing page accessible without authentication.

#### Audit Log
- Immutable audit log recording all significant system actions with actor, action, model, and timestamp.
- Admin/manager view; admin-only CSV export.

#### Comments & Mentions
- Polymorphic comment system on measurements, stations, station revisions, and disaster incidents.
- `@mention` support with in-app notification badge for mentioned users.
- Resolve/unresolve threads; delete own comments.

#### IIMA Compliance & Thresholds
- Compliance threshold configuration per station and data type.
- E-flow requirement tracking against IIMA treaty reference data.
- IIMA allocation management per subcatchment and user category.
- Hazard indicator weights and thresholds configuration.

#### User & Access Management
- Role-based access control: `admin`, `manager`, `clerk`.
- Admin user management: edit role, delete user, trigger password reset.
- PIN-gated registration with one-time invite links.
- Firebase email/password and Google sign-in.

#### Internationalisation
- Full English (EN) and Portuguese (PT) translations across all modules.
- Language toggle in user settings.

#### Settings & Profile
- Profile page with display name, photo, country, organisation, and telephone.
- Preferences: language, notifications, map default zoom.

#### Infrastructure & Deployment
- Multi-stage Docker build (Node 20 + FrankenPHP/PHP 8.3).
- `docker-entrypoint.sh` for production startup.
- Google Cloud Run + Cloud SQL deployment target.
- `composer setup` and `composer dev` scripts for local development.

#### Documentation
- Comprehensive `README.md` with architecture overview, quick start, env variable reference, and roles table.
- `docs/architecture.md` — architectural narrative.
- `docs/deployment.md` — Cloud Run deployment guide.
- `docs/roles-and-permissions.md` — full route permission table.
- `docs/api-routes.md` — all routes with method, path, auth, and shape.
- `docs/gis-workflow.md` — GIS data lifecycle with Mermaid diagram.
- `docs/database-schema.md` — entity-relationship narrative for all domain tables.
- `CONTRIBUTING.md`, `CHANGELOG.md`, `LICENSE`, `SECURITY.md`.
- GitHub community files: issue templates, PR template, CODEOWNERS.

---

[Unreleased]: https://github.com/Bakhombisile02/INMACOM_MIS_V2.1/compare/v2.1.0...HEAD
[2.1.0]: https://github.com/Bakhombisile02/INMACOM_MIS_V2.1/releases/tag/v2.1.0
