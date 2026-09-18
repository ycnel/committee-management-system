-- Add request/session metadata used by the self-only activity log.
USE committee_management_db;

ALTER TABLE activity_logs
    ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL AFTER details,
    ADD COLUMN user_agent VARCHAR(500) DEFAULT NULL AFTER ip_address,
    ADD COLUMN session_duration_seconds INT UNSIGNED DEFAULT NULL AFTER user_agent;