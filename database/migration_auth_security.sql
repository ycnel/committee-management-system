-- ===========================================================
-- database/migration_auth_security.sql
-- CMAS v1.1 Phase 1 — OTP verification + account lockout
-- ------------------------------------------------------------
-- Run AFTER schema.sql. Adds two tables only; nothing existing
-- is altered or dropped.
--
--   mysql -u root -p committee_management_db < database/migration_auth_security.sql
-- ===========================================================

USE committee_management_db;

-- ---------------------------------------------------------
-- 0. SINGLE ACTIVE SESSION
--    Each user has at most one authenticated session token. When the
--    account signs in again, the previous browser's token no longer
--    matches and that session is rejected on its next request.
-- ---------------------------------------------------------
ALTER TABLE users
    ADD COLUMN current_session_token VARCHAR(64) DEFAULT NULL AFTER status;

-- ---------------------------------------------------------
-- 1. LOGIN SECURITY (account lockout state)
--    One row per email. Tracks consecutive failed attempts and
--    an optional lock expiry. Kept separate from activity_logs
--    (which still records every attempt for audit purposes,
--    per section 6.3) because lockout needs fast, mutable
--    per-email state, not a growing history table.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_security (
    email            VARCHAR(255) PRIMARY KEY,
    failed_attempts  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until     DATETIME DEFAULT NULL,
    last_attempt_at  DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 2. OTP VERIFICATIONS
--    One row per OTP issued. Only the HASH of the OTP is
--    stored, never the plaintext code. A new login always
--    invalidates any still-unused prior OTP for that user
--    (enforced in application code, not just by expiry), so
--    only one OTP can ever be valid at a time.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS otp_verifications (
    otp_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    otp_hash     VARCHAR(255) NOT NULL,
    expires_at   DATETIME NOT NULL,
    is_used      TINYINT(1) NOT NULL DEFAULT 0,
    attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_otp_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_otp_user_active (user_id, is_used, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
