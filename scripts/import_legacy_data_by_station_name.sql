-- INMACOM legacy-to-new measurement import
--
-- Run this in the NEW database (destination) after the legacy dump has been
-- imported into schema: u550237388_inmacom_db1
--
-- What this script imports:
--   - dam_levels      -> measurements (measurement_type = dam_level)
--   - flow_levels     -> measurements (measurement_type = flow)
--   - rainfall        -> measurements (measurement_type = rainfall)
--   - groundwater     -> measurements (measurement_type = groundwater_level)
--   - water_quality   -> measurements (measurement_type = water_quality)
--
-- Mapping strategy:
--   1) match stations by exact normalized station name
--   2) fallback to station code
--
-- Safety:
--   - prints unmatched stations/parameters before insert
--   - avoids duplicate inserts using NOT EXISTS checks
--   - assigns imported rows as approved and reviewed

START TRANSACTION;

SET @import_note := 'Legacy import from u550237388_inmacom_db1';

-- Prefer admin/manager as import actor; fallback to first available user.
SET @import_user_id := (
    SELECT id
    FROM users
    WHERE role IN ('admin', 'manager')
    ORDER BY CASE WHEN role = 'admin' THEN 0 ELSE 1 END, id
    LIMIT 1
);
SET @import_user_id := COALESCE(@import_user_id, (SELECT id FROM users ORDER BY id LIMIT 1));

SELECT @import_user_id AS import_user_id_used;

-- ---------------------------------------------------------------------------
-- Station map: legacy station -> new station
-- ---------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_station_map;
CREATE TEMPORARY TABLE tmp_station_map AS
SELECT
    ls.id AS legacy_station_id,
    ls.code AS legacy_station_code,
    ls.name AS legacy_station_name,
    ls.category AS legacy_station_category,
    COALESCE(
        (
            SELECT s1.id
            FROM stations s1
            WHERE LOWER(TRIM(s1.name)) = LOWER(TRIM(ls.name))
            LIMIT 1
        ),
        (
            SELECT s2.id
            FROM stations s2
            WHERE LOWER(TRIM(s2.code)) = LOWER(TRIM(ls.code))
            LIMIT 1
        )
    ) AS new_station_id
FROM u550237388_inmacom_db1.station ls;

-- Review any stations that did not map.
SELECT
    legacy_station_id,
    legacy_station_code,
    legacy_station_name,
    legacy_station_category
FROM tmp_station_map
WHERE new_station_id IS NULL
ORDER BY legacy_station_category, legacy_station_name;

-- ---------------------------------------------------------------------------
-- Water quality parameter map: legacy parameter -> new parameter UUID
-- ---------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_wq_param_map;
CREATE TEMPORARY TABLE tmp_wq_param_map AS
SELECT
    p.parameter AS legacy_parameter,
    COALESCE(
        (
            SELECT w1.id
            FROM water_quality_parameters w1
            WHERE LOWER(REPLACE(REPLACE(REPLACE(TRIM(w1.code), ' ', ''), '+', ''), '-', '')) =
                  LOWER(REPLACE(REPLACE(REPLACE(TRIM(p.parameter), ' ', ''), '+', ''), '-', ''))
            LIMIT 1
        ),
        (
            SELECT w2.id
            FROM water_quality_parameters w2
            WHERE LOWER(REPLACE(REPLACE(REPLACE(TRIM(w2.name), ' ', ''), '+', ''), '-', '')) =
                  LOWER(REPLACE(REPLACE(REPLACE(TRIM(p.parameter), ' ', ''), '+', ''), '-', ''))
            LIMIT 1
        )
    ) AS parameter_id
FROM (
    SELECT DISTINCT parameter
    FROM u550237388_inmacom_db1.water_quality
) p;

-- Review any water-quality parameters that did not map.
SELECT legacy_parameter
FROM tmp_wq_param_map
WHERE parameter_id IS NULL
ORDER BY legacy_parameter;

-- ---------------------------------------------------------------------------
-- Coverage checks by dataset
-- ---------------------------------------------------------------------------
SELECT 'dam_levels' AS dataset, COUNT(*) AS rows_without_station_match
FROM u550237388_inmacom_db1.dam_levels dl
LEFT JOIN u550237388_inmacom_db1.station ls
    ON (LOWER(TRIM(ls.code)) = LOWER(TRIM(dl.station_id)) OR CAST(ls.id AS CHAR(50)) = TRIM(dl.station_id))
   AND LOWER(TRIM(ls.category)) = 'dam levels'
LEFT JOIN tmp_station_map sm ON sm.legacy_station_id = ls.id
WHERE sm.new_station_id IS NULL;

SELECT 'flow_levels' AS dataset, COUNT(*) AS rows_without_station_match
FROM u550237388_inmacom_db1.flow_levels fl
LEFT JOIN u550237388_inmacom_db1.station ls
    ON (LOWER(TRIM(ls.code)) = LOWER(TRIM(fl.station_id)) OR CAST(ls.id AS CHAR(50)) = TRIM(fl.station_id))
   AND LOWER(TRIM(ls.category)) = 'flow levels'
LEFT JOIN tmp_station_map sm ON sm.legacy_station_id = ls.id
WHERE sm.new_station_id IS NULL;

SELECT 'rainfall' AS dataset, COUNT(*) AS rows_without_station_match
FROM u550237388_inmacom_db1.rainfall rf
LEFT JOIN u550237388_inmacom_db1.station ls
    ON (LOWER(TRIM(ls.code)) = LOWER(TRIM(rf.station_id)) OR CAST(ls.id AS CHAR(50)) = TRIM(rf.station_id))
   AND LOWER(TRIM(ls.category)) = 'rainfall'
LEFT JOIN tmp_station_map sm ON sm.legacy_station_id = ls.id
WHERE sm.new_station_id IS NULL;

SELECT 'groundwater' AS dataset, COUNT(*) AS rows_without_station_match
FROM u550237388_inmacom_db1.groundwater gw
LEFT JOIN u550237388_inmacom_db1.station ls
    ON (LOWER(TRIM(ls.code)) = LOWER(TRIM(gw.station_id)) OR CAST(ls.id AS CHAR(50)) = TRIM(gw.station_id))
   AND LOWER(TRIM(ls.category)) = 'groundwater'
LEFT JOIN tmp_station_map sm ON sm.legacy_station_id = ls.id
WHERE sm.new_station_id IS NULL;

SELECT 'water_quality' AS dataset, COUNT(*) AS rows_without_station_match
FROM u550237388_inmacom_db1.water_quality wq
LEFT JOIN u550237388_inmacom_db1.station ls
    ON (LOWER(TRIM(ls.code)) = LOWER(TRIM(wq.station_id)) OR CAST(ls.id AS CHAR(50)) = TRIM(wq.station_id))
   AND LOWER(TRIM(ls.category)) = 'water quality'
LEFT JOIN tmp_station_map sm ON sm.legacy_station_id = ls.id
WHERE sm.new_station_id IS NULL;

-- ---------------------------------------------------------------------------
-- 1) Dam Levels
-- ---------------------------------------------------------------------------
INSERT INTO measurements (
    id, station_id, measurement_type, parameter_id, fsc, value, unit, date,
    status, submitted_by_id, submitted_at, reviewed_by_id, reviewed_at,
    review_notes, is_self_override
)
SELECT
    UUID(),
    sm.new_station_id,
    'dam_level',
    NULL,
    dl.fsc,
    dl.value,
    '%',
    dl.date,
    'approved',
    @import_user_id,
    dl.date,
    @import_user_id,
    dl.date,
    @import_note,
    1
FROM u550237388_inmacom_db1.dam_levels dl
JOIN u550237388_inmacom_db1.station ls
    ON (LOWER(TRIM(ls.code)) = LOWER(TRIM(dl.station_id)) OR CAST(ls.id AS CHAR(50)) = TRIM(dl.station_id))
   AND LOWER(TRIM(ls.category)) = 'dam levels'
JOIN tmp_station_map sm ON sm.legacy_station_id = ls.id
WHERE sm.new_station_id IS NOT NULL
  AND dl.date <> '0000-00-00 00:00:00'
  AND @import_user_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM measurements m
      WHERE m.station_id = sm.new_station_id
        AND m.measurement_type = 'dam_level'
        AND m.parameter_id IS NULL
        AND m.date = dl.date
        AND ABS(m.value - dl.value) < 0.000001
        AND m.unit = '%'
  );
SELECT ROW_COUNT() AS inserted_dam_level_rows;

-- ---------------------------------------------------------------------------
-- 2) Flow Levels
-- ---------------------------------------------------------------------------
INSERT INTO measurements (
    id, station_id, measurement_type, parameter_id, fsc, value, unit, date,
    status, submitted_by_id, submitted_at, reviewed_by_id, reviewed_at,
    review_notes, is_self_override
)
SELECT
    UUID(),
    sm.new_station_id,
    'flow',
    NULL,
    NULL,
    fl.value,
    fl.norm_unit,
    fl.date,
    'approved',
    @import_user_id,
    fl.date,
    @import_user_id,
    fl.date,
    @import_note,
    1
FROM (
    SELECT
        f.*,
        CASE
            WHEN LOWER(REPLACE(REPLACE(REPLACE(TRIM(f.unit), '^', ''), ' ', ''), '³', '3')) = 'm3/s' THEN 'm³/s'
            ELSE TRIM(f.unit)
        END AS norm_unit
    FROM u550237388_inmacom_db1.flow_levels f
) fl
JOIN u550237388_inmacom_db1.station ls
    ON (LOWER(TRIM(ls.code)) = LOWER(TRIM(fl.station_id)) OR CAST(ls.id AS CHAR(50)) = TRIM(fl.station_id))
   AND LOWER(TRIM(ls.category)) = 'flow levels'
JOIN tmp_station_map sm ON sm.legacy_station_id = ls.id
WHERE sm.new_station_id IS NOT NULL
  AND fl.date <> '0000-00-00 00:00:00'
  AND @import_user_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM measurements m
      WHERE m.station_id = sm.new_station_id
        AND m.measurement_type = 'flow'
        AND m.parameter_id IS NULL
        AND m.date = fl.date
        AND ABS(m.value - fl.value) < 0.000001
        AND m.unit = fl.norm_unit
  );
SELECT ROW_COUNT() AS inserted_flow_rows;

-- ---------------------------------------------------------------------------
-- 3) Rainfall
-- ---------------------------------------------------------------------------
INSERT INTO measurements (
    id, station_id, measurement_type, parameter_id, fsc, value, unit, date,
    status, submitted_by_id, submitted_at, reviewed_by_id, reviewed_at,
    review_notes, is_self_override
)
SELECT
    UUID(),
    sm.new_station_id,
    'rainfall',
    NULL,
    NULL,
    rf.value,
    TRIM(rf.unit),
    rf.date,
    'approved',
    @import_user_id,
    rf.date,
    @import_user_id,
    rf.date,
    @import_note,
    1
FROM u550237388_inmacom_db1.rainfall rf
JOIN u550237388_inmacom_db1.station ls
    ON (LOWER(TRIM(ls.code)) = LOWER(TRIM(rf.station_id)) OR CAST(ls.id AS CHAR(50)) = TRIM(rf.station_id))
   AND LOWER(TRIM(ls.category)) = 'rainfall'
JOIN tmp_station_map sm ON sm.legacy_station_id = ls.id
WHERE sm.new_station_id IS NOT NULL
  AND rf.date <> '0000-00-00 00:00:00'
  AND @import_user_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM measurements m
      WHERE m.station_id = sm.new_station_id
        AND m.measurement_type = 'rainfall'
        AND m.parameter_id IS NULL
        AND m.date = rf.date
        AND ABS(m.value - rf.value) < 0.000001
        AND m.unit = TRIM(rf.unit)
  );
SELECT ROW_COUNT() AS inserted_rainfall_rows;

-- ---------------------------------------------------------------------------
-- 4) Groundwater
-- ---------------------------------------------------------------------------
INSERT INTO measurements (
    id, station_id, measurement_type, parameter_id, fsc, value, unit, date,
    status, submitted_by_id, submitted_at, reviewed_by_id, reviewed_at,
    review_notes, is_self_override
)
SELECT
    UUID(),
    sm.new_station_id,
    'groundwater_level',
    NULL,
    NULL,
    gw.value,
    TRIM(gw.unit),
    gw.date,
    'approved',
    @import_user_id,
    gw.date,
    @import_user_id,
    gw.date,
    @import_note,
    1
FROM u550237388_inmacom_db1.groundwater gw
JOIN u550237388_inmacom_db1.station ls
    ON (LOWER(TRIM(ls.code)) = LOWER(TRIM(gw.station_id)) OR CAST(ls.id AS CHAR(50)) = TRIM(gw.station_id))
   AND LOWER(TRIM(ls.category)) = 'groundwater'
JOIN tmp_station_map sm ON sm.legacy_station_id = ls.id
WHERE sm.new_station_id IS NOT NULL
  AND gw.date <> '0000-00-00 00:00:00'
  AND @import_user_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM measurements m
      WHERE m.station_id = sm.new_station_id
        AND m.measurement_type = 'groundwater_level'
        AND m.parameter_id IS NULL
        AND m.date = gw.date
        AND ABS(m.value - gw.value) < 0.000001
        AND m.unit = TRIM(gw.unit)
  );
SELECT ROW_COUNT() AS inserted_groundwater_rows;

-- ---------------------------------------------------------------------------
-- 5) Water Quality
-- ---------------------------------------------------------------------------
INSERT INTO measurements (
    id, station_id, measurement_type, parameter_id, fsc, value, unit, date,
    status, submitted_by_id, submitted_at, reviewed_by_id, reviewed_at,
    review_notes, is_self_override
)
SELECT
    UUID(),
    sm.new_station_id,
    'water_quality',
    pm.parameter_id,
    NULL,
    wq.value,
    TRIM(wq.unit),
    wq.date,
    'approved',
    @import_user_id,
    wq.date,
    @import_user_id,
    wq.date,
    @import_note,
    1
FROM u550237388_inmacom_db1.water_quality wq
JOIN u550237388_inmacom_db1.station ls
    ON (LOWER(TRIM(ls.code)) = LOWER(TRIM(wq.station_id)) OR CAST(ls.id AS CHAR(50)) = TRIM(wq.station_id))
   AND LOWER(TRIM(ls.category)) = 'water quality'
JOIN tmp_station_map sm ON sm.legacy_station_id = ls.id
JOIN tmp_wq_param_map pm ON LOWER(TRIM(pm.legacy_parameter)) = LOWER(TRIM(wq.parameter))
WHERE sm.new_station_id IS NOT NULL
  AND pm.parameter_id IS NOT NULL
  AND wq.date <> '0000-00-00 00:00:00'
  AND @import_user_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM measurements m
      WHERE m.station_id = sm.new_station_id
        AND m.measurement_type = 'water_quality'
        AND m.parameter_id <=> pm.parameter_id
        AND m.date = wq.date
        AND ABS(m.value - wq.value) < 0.000001
        AND m.unit = TRIM(wq.unit)
  );
SELECT ROW_COUNT() AS inserted_water_quality_rows;

-- Summary of rows imported by this script run.
SELECT measurement_type, COUNT(*) AS imported_rows
FROM measurements
WHERE review_notes = @import_note
GROUP BY measurement_type
ORDER BY measurement_type;

COMMIT;
