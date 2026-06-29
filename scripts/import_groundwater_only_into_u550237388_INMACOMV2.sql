-- INMACOM groundwater-only legacy import
-- Generated from: u550237388_inmacom_db1 (2).sql
-- Target DB: u550237388_INMACOMV2
-- Self-contained: includes inline source rows from legacy dump.

USE u550237388_INMACOMV2;

START TRANSACTION;

SET @import_note := CONCAT('Legacy GROUNDWATER import ', DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')); 
SET @import_user_id := (
    SELECT id
    FROM users
    WHERE role IN ('admin', 'manager')
    ORDER BY CASE WHEN role = 'admin' THEN 0 ELSE 1 END, id
    LIMIT 1
);
SET @import_user_id := COALESCE(@import_user_id, (SELECT id FROM users ORDER BY id LIMIT 1));

SELECT @import_user_id AS import_user_id_used, @import_note AS import_note_used;

DROP TEMPORARY TABLE IF EXISTS tmp_legacy_station;
CREATE TEMPORARY TABLE tmp_legacy_station (
    id VARCHAR(50) NOT NULL,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,5) NULL,
    longitude DECIMAL(10,5) NULL,
    category VARCHAR(100) NOT NULL
);

DROP TEMPORARY TABLE IF EXISTS tmp_legacy_groundwater;
CREATE TEMPORARY TABLE tmp_legacy_groundwater (
    id BIGINT NOT NULL,
    station_id VARCHAR(50) NOT NULL,
    value DECIMAL(14,6) NULL,
    parameter VARCHAR(50) NULL,
    unit VARCHAR(50) NOT NULL,
    date_value VARCHAR(19) NOT NULL
);

-- station rows: 72
INSERT INTO tmp_legacy_station (id, code, name, latitude, longitude, category) VALUES
(42, '42', 'Bivane', -27.51875, 31.02708, 'Dam Levels'),
(43, '43', 'Heyshope', -27.00069, 30.51598, 'Dam Levels'),
(44, '44', 'Lubovane', -26.74374, 31.69375, 'Dam Levels'),
(45, '45', 'Morgenstond', -26.72061, 30.53542, 'Dam Levels'),
(46, '46', 'Luphohlo', -26.39374, 31.10208, 'Dam Levels'),
(47, '47', 'Maguga', -26.06041, 31.25208, 'Dam Levels'),
(48, '48', 'Driekoppies', -25.76614, 31.48802, 'Dam Levels'),
(49, '49', 'Vygeboom', -25.85624, 30.62292, 'Dam Levels'),
(50, '50', 'Nooitgedacht', -25.96607, 30.07164, 'Dam Levels'),
(206, 'E-173', 'Xinavane', -25.73333, 32.68167, 'Flow Levels'),
(207, 'E-23', 'Ressano Garcia', -25.43750, 31.99056, 'Flow Levels'),
(208, 'E-28', 'Manhica', -25.61278, 32.67222, 'Flow Levels'),
(209, 'E-4', 'Maputo em Salamanga', -26.47000, 32.66333, 'Flow Levels'),
(210, 'E-413', 'Incoluane', -25.31667, 32.29167, 'Flow Levels'),
(211, 'E-564', 'Maragra', -25.47250, 32.81000, 'Flow Levels'),
(212, 'E-572', 'Marracuene', -25.00139, 32.73333, 'Flow Levels'),
(213, 'E-6', 'Maputo em Madubula I', -26.79000, 32.44000, 'Flow Levels'),
(214, 'E-630', 'Barragem Corrumane', -25.05333, 32.13250, 'Flow Levels'),
(215, 'E-634', 'Barragem Corrumane - Jusante', -25.21583, 32.13611, 'Flow Levels'),
(216, 'GS31', 'Usuthu', -26.80622, 32.00110, 'Flow Levels'),
(217, 'GS21', 'Ngwempisi', -26.74140, 30.80196, 'Flow Levels'),
(218, 'GS25', 'Mkhondvo', -27.07167, 31.04809, 'Flow Levels'),
(219, 'GS30', 'Mananga', -25.94741, 31.74810, 'Flow Levels'),
(220, 'GS33', 'Lusushwana', -26.30504, 30.91603, 'Flow Levels'),
(221, 'GS34', 'Matsamo', -25.75546, 31.44687, 'Flow Levels'),
(222, 'GS8', 'Ngwavuma', -27.08821, 31.79695, 'Flow Levels'),
(223, 'W4H013', 'Pongolapoort Dam Outflow', -27.42266, 32.08047, 'Flow Levels'),
(224, 'X1H001', 'Komati at Hooggenoeg', -26.03617, 30.99761, 'Flow Levels'),
(225, 'X1H007', 'Diepgezet', -26.00056, 31.06645, 'Flow Levels'),
(226, 'X1H049', 'Driekoppies Dam outflow', -25.71085, 31.53368, 'Flow Levels'),
(227, 'X2H016', 'Crocodile', -25.36386, 31.95572, 'Flow Levels'),
(228, 'X2H036', 'Komati at Komatipoort', -25.43661, 31.98244, 'Flow Levels'),
(229, 'X3H015', 'Sabie at Lower Sabie', -25.14953, 31.94067, 'Flow Levels'),
(230, 'U-26', 'Assegai River at Mahamba Border Gate', -27.06519, 30.99356, 'Water Quality'),
(231, 'U-44', 'Ngwempisi River at R33 Road Bridge', -26.67981, 30.70253, 'Water Quality'),
(232, 'U-53', 'Usuthu at Nerston Border Gate', -26.51305, 30.78633, 'Water Quality'),
(233, 'U-57', 'Mpuluzi River at Mpuluzi Area', -26.32367, 30.80501, 'Water Quality'),
(234, 'U-61', 'Lusushwana River at Swallow Nest Area', -26.26522, 30.90338, 'Water Quality'),
(236, 'K-25', 'Driekoppies Dam at the dam wall', -25.71215, 31.53353, 'Water Quality'),
(237, 'K-13', 'Komati River at Mananga Border gate', -25.93218, 31.76018, 'Water Quality'),
(238, 'K-2', 'Komati River at Komatipoort', -25.44322, 31.96417, 'Water Quality'),
(239, 'C-72', 'Crocodile River at Komatipoort Golf Course ', -25.43789, 31.97369, 'Water Quality'),
(240, 'SS-51', 'Sabie River at Lower Sabie Rest Camp KNP', -25.12072, 31.92485, 'Water Quality'),
(243, 'GS16', 'Usuthu', -26.80622, 32.00110, 'Flow Levels'),
(247, 'GS23', 'Usuthu', -26.40246, 30.86128, 'Flow Levels'),
(248, 'E-393', 'Fronteira Oeste', -26.84333, 32.14277, 'Flow Levels'),
(249, 'E-43', 'Magude', -25.02111, 32.75083, 'Flow Levels'),
(250, 'E-45', 'Incoluane', -25.00138, 32.73333, 'Flow Levels'),
(255, 'ESW2323', 'Mankayane', -26.72061, 31.10208, 'Rainfall'),
(263, 'TEST', 'Testing Station', -26.12345, 30.12345, 'Groundwater'),
(264, 'CRL-39', 'Komati River at Ekulindeni', -26.02847, 31.05479, 'Water Quality'),
(265, 'f23', 'Piet Retief', -26.00000, 30.00000, 'Rainfall'),
(267, 'M202', 'Test', -26.00000, 30.62700, 'Water Quality'),
(268, 'ESW4040', 'Ngwempisi', -26.00000, 30.00000, 'Water Quality'),
(270, 'PEQ', 'Pequenos Libombos', -26.10500, 32.21194, 'Dam Levels'),
(271, 'MNJ', 'Mnjoli', -26.16040, 30.65970, 'Dam Levels'),
(272, 'KWN', 'Kwena', -25.36250, 30.37500, 'Dam Levels'),
(273, 'INJ', 'Injaka', -24.88444, 31.66667, 'Dam Levels'),
(274, 'WST', 'Westoe', -26.50417, 30.61806, 'Dam Levels'),
(275, 'JRC', 'Jericho', -26.65417, 30.48611, 'Dam Levels'),
(276, 'COR', 'Corumana', -25.05333, 32.13250, 'Dam Levels'),
(280, 'X1H053', 'Lebombo', -25.44699, 31.94824, ''),
(281, 'E-3', 'Bela Vista', 0.00000, 0.00000, ''),
(282, 'U-43', 'Hlelo River at R33 Road Bridge ', -26.85395, 30.73167, 'Water Quality'),
(291, 'GS34', 'Matsamo', -25.75546, 31.44687, 'Water Quality'),
(292, 'U-5', 'Heyshope Dam at the dam wall', -26.99561, 30.53406, 'Water Quality'),
(293, 'U-47', 'Jericho Dam at the dam wall', -26.65794, 30.48010, 'Water Quality'),
(294, 'U-55', 'Westoe Dam at the dam wall', -26.50734, 30.62566, 'Water Quality'),
(295, 'U-49', 'Morgenstond Dam at the dam wall', -26.71230, 30.54005, 'Water Quality'),
(297, 'BIV', 'Bivane Dam Rainfall', -27.52000, 31.03583, 'Rainfall'),
(299, 'PONG', 'Pongolapoort Dam Rainfall', -26.42111, 32.07056, 'Rainfall'),
(301, 'PONG', 'Pongolapoort', -27.40133, 31.95516, 'Dam Levels');

-- groundwater rows: 0
DROP TEMPORARY TABLE IF EXISTS tmp_station_map;
CREATE TEMPORARY TABLE tmp_station_map AS
SELECT
    ls.id AS legacy_station_id,
    ls.code AS legacy_station_code,
    ls.name AS legacy_station_name,
    ls.category AS legacy_station_category,
    COALESCE(
        (SELECT s1.id FROM stations s1 WHERE LOWER(TRIM(s1.name)) = LOWER(TRIM(ls.name)) LIMIT 1),
        (SELECT s2.id FROM stations s2 WHERE LOWER(TRIM(s2.code)) = LOWER(TRIM(ls.code)) LIMIT 1)
    ) AS new_station_id
FROM tmp_legacy_station ls;

-- Unmatched stations for this dataset
SELECT legacy_station_id, legacy_station_code, legacy_station_name, legacy_station_category
FROM tmp_station_map
WHERE new_station_id IS NULL
  AND LOWER(TRIM(legacy_station_category)) = 'groundwater'
ORDER BY legacy_station_name;

SELECT 'groundwater' AS dataset, COUNT(*) AS rows_without_station_match
FROM tmp_legacy_groundwater t
LEFT JOIN tmp_legacy_station ls
    ON (LOWER(TRIM(ls.code)) = LOWER(TRIM(t.station_id)) OR TRIM(ls.id) = TRIM(t.station_id))
   AND LOWER(TRIM(ls.category)) = 'groundwater'
LEFT JOIN tmp_station_map sm ON sm.legacy_station_id = ls.id
WHERE sm.new_station_id IS NULL;

INSERT INTO measurements (
    id, station_id, measurement_type, parameter_id, fsc, value, unit, date,
    status, submitted_by_id, submitted_at, reviewed_by_id, reviewed_at, review_notes, is_self_override
)
SELECT
    UUID(),
    sm.new_station_id,
    'groundwater_level',
    NULL,
    NULL,
    t.value,
    TRIM(t.unit),
    STR_TO_DATE(t.date_value, '%Y-%m-%d %H:%i:%s'),
    'approved',
    @import_user_id,
    STR_TO_DATE(t.date_value, '%Y-%m-%d %H:%i:%s'),
    @import_user_id,
    STR_TO_DATE(t.date_value, '%Y-%m-%d %H:%i:%s'),
    @import_note,
    1
FROM tmp_legacy_groundwater t
JOIN tmp_legacy_station ls
    ON (LOWER(TRIM(ls.code)) = LOWER(TRIM(t.station_id)) OR TRIM(ls.id) = TRIM(t.station_id))
   AND LOWER(TRIM(ls.category)) = 'groundwater'
JOIN tmp_station_map sm ON sm.legacy_station_id = ls.id
WHERE t.date_value <> '0000-00-00 00:00:00'
  AND STR_TO_DATE(t.date_value, '%Y-%m-%d %H:%i:%s') IS NOT NULL
  AND sm.new_station_id IS NOT NULL
  AND @import_user_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM measurements m
      WHERE m.station_id = sm.new_station_id
        AND m.measurement_type = 'groundwater_level'
        AND m.parameter_id IS NULL
        AND m.date = STR_TO_DATE(t.date_value, '%Y-%m-%d %H:%i:%s')
        AND ABS(m.value - t.value) < 0.000001
        AND m.unit = TRIM(t.unit)
  );

SELECT ROW_COUNT() AS inserted_groundwater_level_rows;

SELECT COUNT(*) AS all_groundwater_level_rows_now
FROM measurements
WHERE measurement_type = 'groundwater_level';

COMMIT;
