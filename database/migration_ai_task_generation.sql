-- ===========================================================
-- database/migration_ai_task_generation.sql
-- Adds support for full AI task generation (description, priority,
-- workload points, due date, status) alongside the existing
-- ai_recommended_member_id / ai_reasoning columns added by
-- migration_ai_hybrid.sql.
-- ------------------------------------------------------------
-- Run this AFTER migration_ai_hybrid.sql.
--   mysql -u root -p committee_management_db < database/migration_ai_task_generation.sql
-- ===========================================================

USE committee_management_db;

ALTER TABLE ai_recommendations
    ADD COLUMN IF NOT EXISTS ai_generated_fields TEXT DEFAULT NULL AFTER ai_recommended_member_id;

-- Note: candidates_json now stores {"task_title": "...", "members": [...]}
-- for this workflow instead of the old rule-based ranked-candidate payload.
-- ai_generated_fields stores {"description","priority","workload_points","due_date","status"}
-- exactly as validated/corrected by OllamaAI before being shown to the admin.
