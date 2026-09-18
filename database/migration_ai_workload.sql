-- ===========================================================
-- database/migration_ai_workload.sql
-- Smart AI Workload Distribution (rule-based weighted scoring)
-- ------------------------------------------------------------
-- Run this AFTER database/schema.sql (and optionally after
-- seed_dummy_data.sql). Adds two new tables only — nothing
-- existing is altered or dropped.
--
--   mysql -u root -p committee_management_db < database/migration_ai_workload.sql
-- ===========================================================

USE committee_management_db;

-- ---------------------------------------------------------
-- 1. AI WEIGHT CONFIGURATION
--    One row per scoring factor. Admin-editable via
--    modules/workload/ai_settings.php. Weights are stored as
--    integers 0-100; the engine normalizes them to sum to 1.0
--    across only the *enabled* factors at scoring time, so
--    disabling a factor never silently changes the meaning of
--    the others' weights.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS ai_weight_config (
    factor_key    VARCHAR(50) PRIMARY KEY,
    factor_label  VARCHAR(150) NOT NULL,
    description   VARCHAR(255) NOT NULL,
    weight        TINYINT UNSIGNED NOT NULL DEFAULT 10,   -- 0-100
    direction     ENUM('lower_is_better','higher_is_better') NOT NULL,
    is_enabled    TINYINT(1) NOT NULL DEFAULT 1,
    updated_by    INT DEFAULT NULL,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_aiw_user FOREIGN KEY (updated_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO ai_weight_config (factor_key, factor_label, description, weight, direction, is_enabled) VALUES
    ('current_workload',   'Current Workload',            'Total active workload points already carried (Pending/In Progress tasks).', 25, 'lower_is_better',  1),
    ('active_committees',  'Active Committee Count',       'Number of committees the member currently sits on.',                        10, 'lower_is_better',  1),
    ('completion_rate',    'Task Completion Rate',         'Share of assigned tasks the member has completed.',                         20, 'higher_is_better', 1),
    ('timeliness',         'On-Time Completion Rate',      'Share of completed tasks finished on or before their due date.',            15, 'higher_is_better', 1),
    ('overdue_count',      'Overdue Task Count',           'Number of currently overdue tasks the member is carrying.',                 15, 'lower_is_better',  1),
    ('assignment_recency', 'Fairness / Assignment Recency','Days since the member''s last new task assignment (rotates work fairly).',  10, 'higher_is_better', 1),
    ('role_weight',        'Committee Role Weight',        'Admin-configurable trust multiplier by role (Chairperson/Vice/Member).',      5, 'higher_is_better', 1);

-- ---------------------------------------------------------
-- 2. AI RECOMMENDATIONS LOG
--    One row per time the engine was run for a committee.
--    Stores the full ranked candidate list (JSON) so the
--    "View AI Explanation" screen can show exactly why each
--    candidate scored the way it did, even after workload data
--    has since changed. If the task this recommendation was
--    for is later saved, workload_id links back to it.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS ai_recommendations (
    recommendation_id        INT AUTO_INCREMENT PRIMARY KEY,
    committee_id             INT NOT NULL,
    workload_id               INT DEFAULT NULL,
    recommended_member_id     INT DEFAULT NULL,
    recommended_score         DECIMAL(5,2) DEFAULT NULL,
    confidence                 ENUM('Low','Medium','High') DEFAULT NULL,
    candidates_json           TEXT NOT NULL,
    was_overridden             TINYINT(1) NOT NULL DEFAULT 0,
    final_member_id           INT DEFAULT NULL,
    generated_by               INT NOT NULL,
    generated_at               DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_air_committee FOREIGN KEY (committee_id)
        REFERENCES committees(committee_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_air_workload FOREIGN KEY (workload_id)
        REFERENCES workload_assignments(workload_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_air_recommended_member FOREIGN KEY (recommended_member_id)
        REFERENCES committee_members(committee_member_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_air_final_member FOREIGN KEY (final_member_id)
        REFERENCES committee_members(committee_member_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_air_generated_by FOREIGN KEY (generated_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
