-- INMACOM V2 Production Repair Script
-- Date: 2026-06-03
-- Target DB: u550237388_INMACOMV2
-- Goal:
--   1) Preserve data by reassigning all station references before removing aliases
--   2) Link duplicate station aliases to canonical stations
--   3) Move related records (measurements, capabilities, mappings) to canonical stations
--   4) Remove duplicate alias rows so GIS/public maps stop double-plotting
--   5) Correct known coordinate/basin typo issues
--
-- IMPORTANT:
--   - Run in phpMyAdmin SQL tab as a single script.
--   - Review the verification output before/after.
--   - Backup tables are created with prefix: backup_station_fix_20260603_*

USE u550237388_INMACOMV2;

START TRANSACTION;

-- ---------------------------------------------------------------------
-- 0) Add parent_station_id (non-destructive hierarchy column)
-- ---------------------------------------------------------------------
SET @has_parent_station_id := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'stations'
      AND COLUMN_NAME = 'parent_station_id'
);

SET @sql_add_parent_station_id := IF(
    @has_parent_station_id = 0,
    'ALTER TABLE stations ADD COLUMN parent_station_id CHAR(36) NULL AFTER id',
    'SELECT ''parent_station_id already exists'' AS info'
);

PREPARE stmt_add_parent_station_id FROM @sql_add_parent_station_id;
EXECUTE stmt_add_parent_station_id;
DEALLOCATE PREPARE stmt_add_parent_station_id;

-- ---------------------------------------------------------------------
-- 1) Build merge plan
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_station_merge_map;
CREATE TEMPORARY TABLE tmp_station_merge_map (
    child_code VARCHAR(50) NOT NULL,
    parent_code VARCHAR(50) NOT NULL,
    apply_now TINYINT(1) NOT NULL DEFAULT 1,
    deactivate_child TINYINT(1) NOT NULL DEFAULT 1,
    notes VARCHAR(255) NULL,
    PRIMARY KEY (child_code, parent_code)
) ENGINE=InnoDB;

-- High-confidence dam/station alias merges from overlapping imports.
INSERT INTO tmp_station_merge_map (child_code, parent_code, apply_now, deactivate_child, notes) VALUES
    ('47', 'MAGUGA-DAM-01', 1, 1, 'Legacy Maguga code merged into major-dam code'),
    ('48', 'DRIEKOPPIES-DAM-01', 1, 1, 'Legacy Driekoppies code merged into major-dam code'),
    ('43', 'HEYSHOPE-DAM-01', 1, 1, 'Legacy Heyshope code merged into major-dam code'),
    ('44', 'LUBOVANE-DAM-01', 1, 1, 'Legacy Lubovane code merged into major-dam code'),
    ('WST', 'WESTOE-DAM-01', 1, 1, 'Legacy Westoe code merged into major-dam code'),
    ('PONG-DAM', 'JOZINI-DAM-01', 1, 1, 'Pongolapoort alias merged into Jozini major-dam code'),
    ('MNJ', 'MNJOLI-DAM-01', 1, 1, 'Mnjoli legacy code merged into major-dam code'),
    ('JRC', 'JERICHO-DAM-01', 1, 1, 'Jericho legacy code merged into major-dam code'),
    ('42', 'BIVANE-DAM-01', 1, 1, 'Bivane legacy code merged into major-dam code'),
    ('MOZ', 'PEQ', 1, 1, 'Mozambique Border Dam alias merged into Pequenos Libombos canonical code');

-- Manual-review candidates (stored for visibility, not auto-applied).
INSERT INTO tmp_station_merge_map (child_code, parent_code, apply_now, deactivate_child, notes) VALUES
    ('E-23', 'X2H036', 0, 0, 'Cross-border same gauge family; review before merge'),
    ('K-2', 'X2H036', 0, 0, 'Cross-border same gauge family; review before merge'),
    ('SS-51', 'X3H015', 0, 0, 'Likely same Lower Sabie point; review before merge'),
    ('E-45', 'E-413', 0, 0, 'Incoluane duplicate suspicion; review before merge');

DROP TEMPORARY TABLE IF EXISTS tmp_station_pairs;
CREATE TEMPORARY TABLE tmp_station_pairs AS
SELECT
    m.child_code,
    m.parent_code,
    c.id AS child_id,
    p.id AS parent_id,
    m.deactivate_child,
    m.notes
FROM tmp_station_merge_map m
JOIN stations c
    ON UPPER(REPLACE(TRIM(c.code), ' ', '')) = UPPER(REPLACE(TRIM(m.child_code), ' ', ''))
JOIN stations p
    ON UPPER(REPLACE(TRIM(p.code), ' ', '')) = UPPER(REPLACE(TRIM(m.parent_code), ' ', ''))
WHERE m.apply_now = 1
  AND c.id <> p.id;

-- Sanity: list unresolved apply_now=1 mappings (should return 0 rows).
SELECT
    m.child_code,
    m.parent_code,
    c.id AS child_found,
    p.id AS parent_found,
    m.notes
FROM tmp_station_merge_map m
LEFT JOIN stations c
    ON UPPER(REPLACE(TRIM(c.code), ' ', '')) = UPPER(REPLACE(TRIM(m.child_code), ' ', ''))
LEFT JOIN stations p
    ON UPPER(REPLACE(TRIM(p.code), ' ', '')) = UPPER(REPLACE(TRIM(m.parent_code), ' ', ''))
WHERE m.apply_now = 1
  AND (c.id IS NULL OR p.id IS NULL);

-- Sanity: show effective merge pairs.
SELECT child_code, parent_code, child_id, parent_id, notes
FROM tmp_station_pairs
ORDER BY parent_code, child_code;

-- Sanity: if this is 0, the live DB does not have matching child/parent codes.
SELECT COUNT(*) AS matched_pairs
FROM tmp_station_pairs;

-- ---------------------------------------------------------------------
-- 2) Build backup scope and snapshot BEFORE data movement
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_station_backup_codes;
CREATE TEMPORARY TABLE tmp_station_backup_codes (
    code VARCHAR(50) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

INSERT IGNORE INTO tmp_station_backup_codes (code)
SELECT child_code FROM tmp_station_merge_map WHERE apply_now = 1
UNION
SELECT parent_code FROM tmp_station_merge_map WHERE apply_now = 1;

-- Include stations receiving coordinate/basin corrections.
INSERT IGNORE INTO tmp_station_backup_codes (code) VALUES
    ('MNJOLI-DAM-01'),
    ('PONG-RAIN'),
    ('JERICHO-DAM-01'),
    ('BIVANE-DAM-01'),
    ('GS-11-CANAL');

DROP TEMPORARY TABLE IF EXISTS tmp_station_relation_ids;
CREATE TEMPORARY TABLE tmp_station_relation_ids AS
SELECT s.id AS station_id
FROM stations s
JOIN tmp_station_backup_codes b ON b.code = s.code;

DROP TABLE IF EXISTS backup_station_fix_20260603_stations;
CREATE TABLE backup_station_fix_20260603_stations AS
SELECT *
FROM stations
WHERE id IN (SELECT station_id FROM tmp_station_relation_ids);

DROP TABLE IF EXISTS backup_station_fix_20260603_measurements;
CREATE TABLE backup_station_fix_20260603_measurements AS
SELECT *
FROM measurements
WHERE station_id IN (SELECT station_id FROM tmp_station_relation_ids);

DROP TABLE IF EXISTS backup_station_fix_20260603_station_capabilities;
CREATE TABLE backup_station_fix_20260603_station_capabilities AS
SELECT *
FROM station_capabilities
WHERE station_id IN (SELECT station_id FROM tmp_station_relation_ids);

DROP TABLE IF EXISTS backup_station_fix_20260603_management_area_stations;
CREATE TABLE backup_station_fix_20260603_management_area_stations AS
SELECT *
FROM management_area_stations
WHERE station_id IN (SELECT station_id FROM tmp_station_relation_ids);

DROP TABLE IF EXISTS backup_station_fix_20260603_incident_stations;
CREATE TABLE backup_station_fix_20260603_incident_stations AS
SELECT *
FROM incident_stations
WHERE station_id IN (SELECT station_id FROM tmp_station_relation_ids);

DROP TABLE IF EXISTS backup_station_fix_20260603_compliance_thresholds;
CREATE TABLE backup_station_fix_20260603_compliance_thresholds AS
SELECT *
FROM compliance_thresholds
WHERE station_id IN (SELECT station_id FROM tmp_station_relation_ids);

DROP TABLE IF EXISTS backup_station_fix_20260603_station_operational_statuses;
CREATE TABLE backup_station_fix_20260603_station_operational_statuses AS
SELECT *
FROM station_operational_statuses
WHERE station_id IN (SELECT station_id FROM tmp_station_relation_ids);

DROP TABLE IF EXISTS backup_station_fix_20260603_station_revisions;
CREATE TABLE backup_station_fix_20260603_station_revisions AS
SELECT *
FROM station_revisions
WHERE station_id IN (SELECT station_id FROM tmp_station_relation_ids);

DROP TABLE IF EXISTS backup_station_fix_20260603_iima_eflow_key_points;
CREATE TABLE backup_station_fix_20260603_iima_eflow_key_points AS
SELECT *
FROM iima_eflow_key_points
WHERE station_id IN (SELECT station_id FROM tmp_station_relation_ids);

DROP TABLE IF EXISTS backup_station_fix_20260603_iima_eflow_requirements;
CREATE TABLE backup_station_fix_20260603_iima_eflow_requirements AS
SELECT *
FROM iima_eflow_requirements
WHERE station_id IN (SELECT station_id FROM tmp_station_relation_ids);

DROP TABLE IF EXISTS backup_station_fix_20260603_hazard_indicator_readings;
CREATE TABLE backup_station_fix_20260603_hazard_indicator_readings AS
SELECT *
FROM hazard_indicator_readings
WHERE station_id IN (SELECT station_id FROM tmp_station_relation_ids);

-- ---------------------------------------------------------------------
-- 3) Merge references child -> parent (data-preserving)
-- ---------------------------------------------------------------------
-- Ensure parent stations inherit any capability present on child aliases.
INSERT IGNORE INTO station_capabilities (station_id, measurement_type, is_primary, installed_at, notes)
SELECT
    sp.parent_id,
    sc.measurement_type,
    sc.is_primary,
    sc.installed_at,
    CASE
        WHEN sc.notes IS NULL OR sc.notes = '' THEN CONCAT('Merged from ', sp.child_code, ' on 2026-06-03')
        ELSE CONCAT(sc.notes, ' | Merged from ', sp.child_code, ' on 2026-06-03')
    END AS notes
FROM tmp_station_pairs sp
JOIN station_capabilities sc ON sc.station_id = sp.child_id;

-- Junction table: management_area_stations
INSERT IGNORE INTO management_area_stations (management_area_id, station_id)
SELECT mas.management_area_id, sp.parent_id
FROM management_area_stations mas
JOIN tmp_station_pairs sp ON sp.child_id = mas.station_id;

DELETE mas
FROM management_area_stations mas
JOIN tmp_station_pairs sp ON sp.child_id = mas.station_id;

-- Junction table: incident_stations
INSERT IGNORE INTO incident_stations (incident_id, station_id)
SELECT ist.incident_id, sp.parent_id
FROM incident_stations ist
JOIN tmp_station_pairs sp ON sp.child_id = ist.station_id;

DELETE ist
FROM incident_stations ist
JOIN tmp_station_pairs sp ON sp.child_id = ist.station_id;

-- Direct station_id moves
UPDATE measurements m
JOIN tmp_station_pairs sp ON sp.child_id = m.station_id
SET m.station_id = sp.parent_id;

UPDATE compliance_thresholds ct
JOIN tmp_station_pairs sp ON sp.child_id = ct.station_id
SET ct.station_id = sp.parent_id;

UPDATE station_operational_statuses sos
JOIN tmp_station_pairs sp ON sp.child_id = sos.station_id
SET sos.station_id = sp.parent_id;

UPDATE station_revisions sr
JOIN tmp_station_pairs sp ON sp.child_id = sr.station_id
SET sr.station_id = sp.parent_id;

UPDATE iima_eflow_key_points kp
JOIN tmp_station_pairs sp ON sp.child_id = kp.station_id
SET kp.station_id = sp.parent_id;

UPDATE iima_eflow_requirements er
JOIN tmp_station_pairs sp ON sp.child_id = er.station_id
SET er.station_id = sp.parent_id;

UPDATE hazard_indicator_readings hir
JOIN tmp_station_pairs sp ON sp.child_id = hir.station_id
SET hir.station_id = sp.parent_id;

-- Remove old child capability rows after moving references.
DELETE sc
FROM station_capabilities sc
JOIN tmp_station_pairs sp ON sp.child_id = sc.station_id;

-- Safety check: these should all be 0 before deleting child aliases.
DROP TEMPORARY TABLE IF EXISTS tmp_child_reference_counts;
CREATE TEMPORARY TABLE tmp_child_reference_counts AS
SELECT 'measurements' AS table_name, COUNT(*) AS ref_count
FROM measurements
WHERE station_id IN (SELECT child_id FROM tmp_station_pairs WHERE deactivate_child = 1)
UNION ALL
SELECT 'station_capabilities' AS table_name, COUNT(*) AS ref_count
FROM station_capabilities
WHERE station_id IN (SELECT child_id FROM tmp_station_pairs WHERE deactivate_child = 1)
UNION ALL
SELECT 'management_area_stations' AS table_name, COUNT(*) AS ref_count
FROM management_area_stations
WHERE station_id IN (SELECT child_id FROM tmp_station_pairs WHERE deactivate_child = 1)
UNION ALL
SELECT 'incident_stations' AS table_name, COUNT(*) AS ref_count
FROM incident_stations
WHERE station_id IN (SELECT child_id FROM tmp_station_pairs WHERE deactivate_child = 1)
UNION ALL
SELECT 'compliance_thresholds' AS table_name, COUNT(*) AS ref_count
FROM compliance_thresholds
WHERE station_id IN (SELECT child_id FROM tmp_station_pairs WHERE deactivate_child = 1)
UNION ALL
SELECT 'station_operational_statuses' AS table_name, COUNT(*) AS ref_count
FROM station_operational_statuses
WHERE station_id IN (SELECT child_id FROM tmp_station_pairs WHERE deactivate_child = 1)
UNION ALL
SELECT 'station_revisions' AS table_name, COUNT(*) AS ref_count
FROM station_revisions
WHERE station_id IN (SELECT child_id FROM tmp_station_pairs WHERE deactivate_child = 1)
UNION ALL
SELECT 'iima_eflow_key_points' AS table_name, COUNT(*) AS ref_count
FROM iima_eflow_key_points
WHERE station_id IN (SELECT child_id FROM tmp_station_pairs WHERE deactivate_child = 1)
UNION ALL
SELECT 'iima_eflow_requirements' AS table_name, COUNT(*) AS ref_count
FROM iima_eflow_requirements
WHERE station_id IN (SELECT child_id FROM tmp_station_pairs WHERE deactivate_child = 1)
UNION ALL
SELECT 'hazard_indicator_readings' AS table_name, COUNT(*) AS ref_count
FROM hazard_indicator_readings
WHERE station_id IN (SELECT child_id FROM tmp_station_pairs WHERE deactivate_child = 1);

SELECT table_name, ref_count
FROM tmp_child_reference_counts
WHERE ref_count > 0
ORDER BY table_name;

-- Station row updates for non-deleted aliases (if deactivate_child = 0 in future mappings).
UPDATE stations child
JOIN tmp_station_pairs sp ON sp.child_id = child.id
SET
    child.parent_station_id = sp.parent_id,
    child.summary = CASE
        WHEN child.summary IS NULL OR child.summary = ''
            THEN CONCAT(child.name, ' [Merged alias of ', sp.parent_code, ']')
        WHEN child.summary LIKE CONCAT('%[Merged alias of ', sp.parent_code, ']%')
            THEN child.summary
        ELSE CONCAT(child.summary, ' [Merged alias of ', sp.parent_code, ']')
    END
WHERE sp.deactivate_child = 0;

-- Remove merged alias stations after all references are moved.
DELETE child
FROM stations child
JOIN tmp_station_pairs sp ON sp.child_id = child.id
WHERE sp.deactivate_child = 1;

-- Ensure canonical parents remain active.
UPDATE stations parent
JOIN tmp_station_pairs sp ON sp.parent_id = parent.id
SET parent.is_active = 1;

-- ---------------------------------------------------------------------
-- 4) Correct known coordinate / basin typos
-- ---------------------------------------------------------------------
UPDATE stations
SET latitude = -26.1604,
    longitude = 30.6597
WHERE code = 'MNJOLI-DAM-01';

UPDATE stations
SET latitude = -27.42111
WHERE code = 'PONG-RAIN';

UPDATE stations
SET latitude = -26.65417,
    longitude = 30.48611
WHERE code = 'JERICHO-DAM-01';

UPDATE stations
SET latitude = -27.51875,
    longitude = 31.02708
WHERE code = 'BIVANE-DAM-01';

UPDATE stations
SET river_basin = 'Lomati'
WHERE code = 'GS-11-CANAL'
  AND (river_basin = 'Lusutfu' OR river_basin IS NULL OR river_basin = '');

-- ---------------------------------------------------------------------
-- 5) Verification output
-- ---------------------------------------------------------------------
SELECT
    sp.parent_code,
    parent.id AS parent_id,
    parent.is_active AS parent_active,
    COUNT(*) AS merged_child_count
FROM tmp_station_pairs sp
JOIN stations parent ON parent.id = sp.parent_id
GROUP BY sp.parent_code, parent.id, parent.is_active
ORDER BY sp.parent_code;

SELECT
    sp.child_code AS alias_code,
    sp.parent_code,
    CASE WHEN child.id IS NULL THEN 'deleted' ELSE 'retained' END AS alias_row_status,
    child.is_active AS alias_active
FROM tmp_station_pairs sp
LEFT JOIN stations child ON child.id = sp.child_id
ORDER BY sp.parent_code, sp.child_code;

SELECT code, name, latitude, longitude, river_basin
FROM stations
WHERE code IN ('MNJOLI-DAM-01', 'PONG-RAIN', 'JERICHO-DAM-01', 'BIVANE-DAM-01', 'GS-11-CANAL')
ORDER BY code;

COMMIT;

-- =====================================================================
-- OPTIONAL ROLLBACK (ONLY IF YOU NEED TO UNDO THIS FIX)
-- =====================================================================
-- This rollback expects the backup_station_fix_20260603_* tables above.
--
-- START TRANSACTION;
--
-- DROP TEMPORARY TABLE IF EXISTS tmp_station_relation_ids;
-- CREATE TEMPORARY TABLE tmp_station_relation_ids AS
-- SELECT id AS station_id FROM backup_station_fix_20260603_stations;
--
-- -- Restore tables with station_id references.
-- DELETE m
-- FROM measurements m
-- JOIN backup_station_fix_20260603_measurements b ON b.id = m.id;
-- INSERT INTO measurements SELECT * FROM backup_station_fix_20260603_measurements;
--
-- DELETE sc
-- FROM station_capabilities sc
-- WHERE sc.station_id IN (SELECT station_id FROM tmp_station_relation_ids);
-- INSERT INTO station_capabilities SELECT * FROM backup_station_fix_20260603_station_capabilities;
--
-- DELETE mas
-- FROM management_area_stations mas
-- WHERE mas.station_id IN (SELECT station_id FROM tmp_station_relation_ids);
-- INSERT INTO management_area_stations SELECT * FROM backup_station_fix_20260603_management_area_stations;
--
-- DELETE ist
-- FROM incident_stations ist
-- WHERE ist.station_id IN (SELECT station_id FROM tmp_station_relation_ids);
-- INSERT INTO incident_stations SELECT * FROM backup_station_fix_20260603_incident_stations;
--
-- DELETE ct
-- FROM compliance_thresholds ct
-- WHERE ct.station_id IN (SELECT station_id FROM tmp_station_relation_ids);
-- INSERT INTO compliance_thresholds SELECT * FROM backup_station_fix_20260603_compliance_thresholds;
--
-- DELETE sos
-- FROM station_operational_statuses sos
-- WHERE sos.station_id IN (SELECT station_id FROM tmp_station_relation_ids);
-- INSERT INTO station_operational_statuses SELECT * FROM backup_station_fix_20260603_station_operational_statuses;
--
-- DELETE sr
-- FROM station_revisions sr
-- WHERE sr.station_id IN (SELECT station_id FROM tmp_station_relation_ids);
-- INSERT INTO station_revisions SELECT * FROM backup_station_fix_20260603_station_revisions;
--
-- DELETE kp
-- FROM iima_eflow_key_points kp
-- WHERE kp.station_id IN (SELECT station_id FROM tmp_station_relation_ids);
-- INSERT INTO iima_eflow_key_points SELECT * FROM backup_station_fix_20260603_iima_eflow_key_points;
--
-- DELETE er
-- FROM iima_eflow_requirements er
-- WHERE er.station_id IN (SELECT station_id FROM tmp_station_relation_ids);
-- INSERT INTO iima_eflow_requirements SELECT * FROM backup_station_fix_20260603_iima_eflow_requirements;
--
-- DELETE hir
-- FROM hazard_indicator_readings hir
-- WHERE hir.station_id IN (SELECT station_id FROM tmp_station_relation_ids);
-- INSERT INTO hazard_indicator_readings SELECT * FROM backup_station_fix_20260603_hazard_indicator_readings;
--
-- -- Reinsert deleted station rows, then restore original station fields.
-- INSERT INTO stations
-- SELECT b.*
-- FROM backup_station_fix_20260603_stations b
-- LEFT JOIN stations s ON s.id = b.id
-- WHERE s.id IS NULL;
--
-- UPDATE stations s
-- JOIN backup_station_fix_20260603_stations b ON b.id = s.id
-- SET
--     s.code = b.code,
--     s.name = b.name,
--     s.latitude = b.latitude,
--     s.longitude = b.longitude,
--     s.category = b.category,
--     s.water_source = b.water_source,
--     s.water_body_type = b.water_body_type,
--     s.is_active = b.is_active,
--     s.is_real_time = b.is_real_time,
--     s.summary = b.summary,
--     s.telemetry_system = b.telemetry_system,
--     s.gauge_code = b.gauge_code,
--     s.owner_org = b.owner_org,
--     s.country = b.country,
--     s.river_basin = b.river_basin,
--     s.parent_station_id = b.parent_station_id;
--
-- COMMIT;
