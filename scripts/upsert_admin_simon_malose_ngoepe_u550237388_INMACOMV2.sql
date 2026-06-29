-- INMACOM V2: Upsert admin user for Simon Malose Ngoepe
-- Target DB: u550237388_INMACOMV2
-- Behavior:
--   1) If user exists by email, update display_name, role=admin, password hash.
--   2) If user does not exist, insert a new admin user (requires Firebase UID).
--
-- IMPORTANT:
-- - This app authenticates through Firebase ID tokens.
-- - SQL below updates the local users table password field, but for login you should
--   also set the same password in Firebase Auth for this email.
-- - If Firebase Password Policy requires lowercase/symbols, `INMACOM2026` will be
--   rejected by Firebase with `INVALID_LOGIN_CREDENTIALS` until policy is relaxed
--   or a policy-compliant password is used.

USE u550237388_INMACOMV2;

SET @target_email := 'Ngoepem@dws.gov.za';
SET @target_name := 'Simon Malose Ngoepe';
SET @target_role := 'admin';
SET @target_password_hash := '$2y$12$9UYykmTBAAEF9iS1bME3Iel8GiWWvc8a.DlNBIpechbGRvcFMATG6'; -- Inmacom2026!

-- Only needed if the user does not already exist.
-- Replace this with the real Firebase UID before running if Simon is not yet in users.
SET @firebase_uid_for_new_user := 'REPLACE_WITH_REAL_FIREBASE_UID_IF_USER_DOES_NOT_EXIST';

-- =====================================================================
-- 1) PREVIEW CURRENT USER (IF ANY)
-- =====================================================================
SELECT id, email, display_name, role, firebase_uid, updated_at
FROM users
WHERE LOWER(email) = LOWER(@target_email)
LIMIT 1;

-- =====================================================================
-- 2) APPLY UPSERT
-- =====================================================================
START TRANSACTION;

-- Update existing user (if present)
UPDATE users
SET
    display_name = @target_name,
    role = @target_role,
    password = @target_password_hash,
    updated_at = NOW()
WHERE LOWER(email) = LOWER(@target_email);

SELECT ROW_COUNT() AS rows_updated;

-- Insert only if user does not exist AND a real Firebase UID was provided
INSERT INTO users (
    id,
    display_name,
    email,
    role,
    firebase_uid,
    password,
    email_verified_at,
    created_at,
    updated_at
)
SELECT
    UUID(),
    @target_name,
    LOWER(@target_email),
    @target_role,
    @firebase_uid_for_new_user,
    @target_password_hash,
    NOW(),
    NOW(),
    NOW()
WHERE NOT EXISTS (
        SELECT 1
        FROM users
        WHERE LOWER(email) = LOWER(@target_email)
    )
  AND @firebase_uid_for_new_user <> 'REPLACE_WITH_REAL_FIREBASE_UID_IF_USER_DOES_NOT_EXIST';

SELECT ROW_COUNT() AS rows_inserted;

COMMIT;

-- =====================================================================
-- 3) VERIFY RESULT
-- =====================================================================
SELECT
    id,
    email,
    display_name,
    role,
    firebase_uid,
    CASE WHEN password IS NULL THEN 'no' ELSE 'yes' END AS has_password,
    updated_at
FROM users
WHERE LOWER(email) = LOWER(@target_email)
LIMIT 1;

SELECT CASE
    WHEN EXISTS (
        SELECT 1 FROM users WHERE LOWER(email) = LOWER(@target_email)
    ) THEN 'SUCCESS: user is present and role/password updated.'
    ELSE 'NO CHANGE: user not found and Firebase UID placeholder not replaced.'
END AS status_message;
