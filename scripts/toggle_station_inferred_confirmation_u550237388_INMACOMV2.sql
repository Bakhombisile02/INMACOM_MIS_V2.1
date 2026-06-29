-- INMACOM V2: Temporarily mark inferred station summaries as confirmed
-- Target DB: u550237388_INMACOMV2
-- Safe rollback included (restores exact original summary text from backup)

USE u550237388_INMACOMV2;

-- =====================================================================
-- 1) PREVIEW AFFECTED ROWS
-- =====================================================================
SELECT COUNT(*) AS rows_to_change
FROM stations
WHERE summary REGEXP 'Inferred|Unconfirmed|Unverified';

SELECT id, code, name, summary
FROM stations
WHERE summary REGEXP 'Inferred|Unconfirmed|Unverified'
ORDER BY code;

-- =====================================================================
-- 2) APPLY CONFIRMATION UPDATE
-- =====================================================================
START TRANSACTION;

-- Backup the exact original summary values before changing anything.
DROP TABLE IF EXISTS stations_summary_backup_confirm_20260603;
CREATE TABLE stations_summary_backup_confirm_20260603 AS
SELECT id, summary
FROM stations
WHERE summary REGEXP 'Inferred|Unconfirmed|Unverified';

SELECT COUNT(*) AS rows_backed_up
FROM stations_summary_backup_confirm_20260603;

-- Replace inferred/unconfirmed suffix with a confirmed label.
-- This strips existing trailing bracketed metadata safely, then appends [Confirmed July 2019].
UPDATE stations s
JOIN stations_summary_backup_confirm_20260603 b
  ON b.id = s.id
SET s.summary = CONCAT(
    TRIM(
        CASE
            WHEN LOCATE('[', s.summary) > 0 THEN LEFT(s.summary, LOCATE('[', s.summary) - 1)
            ELSE s.summary
        END
    ),
    ' [Confirmed July 2019]'
);

SELECT ROW_COUNT() AS rows_updated;

SELECT COUNT(*) AS rows_still_flagged
FROM stations
WHERE summary REGEXP 'Inferred|Unconfirmed|Unverified';

COMMIT;

-- =====================================================================
-- 3) ROLLBACK (RUN THIS LATER ONLY IF YOU WANT TO REVERT)
-- =====================================================================
-- IMPORTANT: Requires table stations_summary_backup_confirm_20260603.
-- This block is intentionally commented to prevent accidental immediate undo.
/*
START TRANSACTION;

UPDATE stations s
JOIN stations_summary_backup_confirm_20260603 b
  ON b.id = s.id
SET s.summary = b.summary;

SELECT ROW_COUNT() AS rows_restored;

COMMIT;
*/
