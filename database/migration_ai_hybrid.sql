-- ===========================================================
-- database/migration_ai_hybrid.sql
-- Hybrid AI Decision-Support layer (Local Ollama + Qwen2.5 3B)
-- ------------------------------------------------------------
-- Run this AFTER database/schema.sql AND database/migration_ai_workload.sql.
-- Adds one new table (ai_system_settings) and extends the existing
-- ai_recommendations table with hybrid-AI columns. Nothing existing
-- is dropped or renamed — the rule-based engine and its log rows
-- keep working exactly as before.
--
--   mysql -u root -p committee_management_db < database/migration_ai_hybrid.sql
-- ===========================================================

USE committee_management_db;

-- ---------------------------------------------------------
-- 1. AI SYSTEM SETTINGS
--    Runtime-editable Ollama configuration (Admin -> Smart AI
--    Settings -> Local AI). Falls back to includes/ai_config.php
--    constants if a key has never been saved here.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS ai_system_settings (
    setting_key    VARCHAR(50) PRIMARY KEY,
    setting_value  VARCHAR(255) NOT NULL,
    updated_by     INT DEFAULT NULL,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_aisys_user FOREIGN KEY (updated_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO ai_system_settings (setting_key, setting_value) VALUES
    ('ollama_enabled', '1'),
    ('ollama_url',     'http://localhost:11434'),
    ('ollama_model',   'qwen2.5:1.5b'),
    ('ollama_timeout', '30');

-- ---------------------------------------------------------
-- 2. EXTEND ai_recommendations WITH HYBRID AI COLUMNS
--    rule-based columns (recommended_member_id, recommended_score,
--    confidence, was_overridden, final_member_id, ...) are untouched.
--    These new columns hold the *AI's* opinion alongside them so a
--    single row captures both sides of every recommendation event.
-- ---------------------------------------------------------
ALTER TABLE ai_recommendations
    ADD COLUMN IF NOT EXISTS ai_available            TINYINT(1) DEFAULT NULL           AFTER candidates_json,
    ADD COLUMN IF NOT EXISTS ai_recommended_member_id INT DEFAULT NULL                  AFTER ai_available,
    ADD COLUMN IF NOT EXISTS ai_confidence             ENUM('Low','Medium','High') DEFAULT NULL AFTER ai_recommended_member_id,
    ADD COLUMN IF NOT EXISTS ai_risk_assessment       ENUM('Low','Medium','High') DEFAULT NULL AFTER ai_confidence,
    ADD COLUMN IF NOT EXISTS ai_reasoning             TEXT DEFAULT NULL                 AFTER ai_risk_assessment,
    ADD COLUMN IF NOT EXISTS ai_alternative_ids       VARCHAR(255) DEFAULT NULL         AFTER ai_reasoning,
    ADD COLUMN IF NOT EXISTS agreement_status         ENUM('Yes','No','N/A') DEFAULT 'N/A' AFTER ai_alternative_ids,
    ADD COLUMN IF NOT EXISTS ai_model_used             VARCHAR(100) DEFAULT NULL         AFTER agreement_status,
    ADD COLUMN IF NOT EXISTS ai_response_time_ms       INT DEFAULT NULL                  AFTER ai_model_used,
    ADD COLUMN IF NOT EXISTS ai_warning                 VARCHAR(255) DEFAULT NULL         AFTER ai_response_time_ms,
    ADD COLUMN IF NOT EXISTS candidates_hash           VARCHAR(64) DEFAULT NULL          AFTER ai_warning,
    ADD COLUMN IF NOT EXISTS admin_followed_ai         TINYINT(1) DEFAULT NULL           AFTER was_overridden;

ALTER TABLE ai_recommendations
    ADD CONSTRAINT fk_air_ai_recommended_member FOREIGN KEY (ai_recommended_member_id)
        REFERENCES committee_members(committee_member_id) ON UPDATE CASCADE ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_air_committee_hash ON ai_recommendations (committee_id, candidates_hash);

-- Notes:
--  * `agreement_status` = 'Yes' when ai_recommended_member_id equals
--    recommended_member_id (the rule-based top pick), 'No' when they
--    differ, 'N/A' when the AI was unavailable for that run.
--  * `admin_followed_ai` mirrors `was_overridden` semantics but against
--    the AI's pick instead of the rule-based pick; set when the task is
--    actually saved (same place markOutcome() is called from).
--  * `candidates_hash` lets the app skip a fresh Ollama call and reuse a
--    recent AI answer when nothing about the candidate pool has changed
--    (see OLLAMA_CACHE_MINUTES in includes/ai_config.php).
