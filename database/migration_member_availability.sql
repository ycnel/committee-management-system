-- Phase 3 of the role-hierarchy revision: Availability / Pause Mode
-- (role-hierarchy revision §11). An append-only history table — each
-- status change is a new row, never an overwrite — so "Availability
-- history is preserved" is true by construction, the same pattern
-- already used for workload_assignment_proposals' reassignment chain.
--
-- The CURRENT status for a committee_member_id is simply its most
-- recent row (see includes/availability.php::currentAvailability()).
-- No row at all means the default, implicit state: Available — "do not
-- automatically assume absence means unavailable unless verified"
-- (role-hierarchy revision §11) applies here too: nothing is assumed
-- unavailable without an explicit, verified status change.
--
-- Run once:
--   mysql -u root -p committee_management_db < database/migration_member_availability.sql

USE committee_management_db;

CREATE TABLE IF NOT EXISTS member_availability (
    availability_id      INT AUTO_INCREMENT PRIMARY KEY,
    committee_member_id  INT NOT NULL,
    status                ENUM('Available','Unavailable','Idle','Emergency') NOT NULL DEFAULT 'Available',
    reason                VARCHAR(500) DEFAULT NULL,
    start_date            DATE DEFAULT NULL,
    end_date              DATE DEFAULT NULL,
    updated_by            INT DEFAULT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY idx_availability_member (committee_member_id, availability_id),
    CONSTRAINT fk_availability_member FOREIGN KEY (committee_member_id)
        REFERENCES committee_members(committee_member_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_availability_updated_by FOREIGN KEY (updated_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;