-- Phase 2 of the role-hierarchy revision: Member Acceptance Validation.
-- "An AI recommendation is NOT an assignment" — this migration adds a
-- pre-approval proposal table that sits IN FRONT OF workload_assignments.
-- Nothing is written to workload_assignments until a Chairperson/Admin
-- gives final approval, so every existing query against
-- workload_assignments (dashboards, performance, reports, the AI
-- duplicate-check, etc.) keeps working completely unchanged — this
-- migration is purely additive.
--
-- Current workflow:
--   New proposals enter Pending for Administrator review before an
--   assignment is created. Legacy Awaiting Response / Accepted rows remain
--   available for historical member-acceptance workflow handling.
--
-- Run once:
--   mysql -u root -p committee_management_db < database/migration_member_acceptance_workflow.sql

USE committee_management_db;

CREATE TABLE IF NOT EXISTS workload_assignment_proposals (
    proposal_id             INT AUTO_INCREMENT PRIMARY KEY,
    committee_member_id     INT NOT NULL,
    task_title              VARCHAR(255) NOT NULL,
    task_description        TEXT DEFAULT NULL,
    priority                ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
    due_date                DATE DEFAULT NULL,

    -- Links back to the AI recommendation run that produced this
    -- candidate, if the Chairperson picked one via "Generate with AI"
    -- (NULL for a manually-chosen candidate). ai_recommendations.workload_id
    -- / final_member_id / admin_followed_ai are still only populated once
    -- this proposal is Approved and a real workload_assignments row
    -- exists — their meaning is unchanged from before this migration.
    ai_recommendation_id    INT DEFAULT NULL,

    proposed_by             INT DEFAULT NULL,
    proposed_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    state                   ENUM('Pending','Awaiting Response','Accepted','Declined','Approved','Rejected','Reassigned','Stopped')
                             NOT NULL DEFAULT 'Pending',
    responded_at            DATETIME DEFAULT NULL,
    -- Free-text note attached to a Decline or a Stop-distribution action
    -- (which one it is is determined by `state` at the time it was set).
    note                    VARCHAR(500) DEFAULT NULL,

    approved_by             INT DEFAULT NULL,
    approved_at             DATETIME DEFAULT NULL,
    rejected_by             INT DEFAULT NULL,
    rejected_at             DATETIME DEFAULT NULL,
    admin_response          TEXT DEFAULT NULL,
    -- Set only once state='Approved': the real, confirmed assignment row.
    resulting_workload_id   INT DEFAULT NULL,

    -- Reassignment history: a decline/reassign never mutates this row —
    -- it's marked Declined/Reassigned (terminal) and a NEW proposal row
    -- is created for the next candidate, chained back here. Following
    -- previous_proposal_id back to NULL replays the full chain for one task.
    previous_proposal_id    INT DEFAULT NULL,

    KEY idx_proposals_member_state (committee_member_id, state),
    KEY idx_proposals_state (state),
    KEY idx_proposals_chain (previous_proposal_id),
    CONSTRAINT fk_proposal_member FOREIGN KEY (committee_member_id)
        REFERENCES committee_members(committee_member_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_proposal_ai_rec FOREIGN KEY (ai_recommendation_id)
        REFERENCES ai_recommendations(recommendation_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_proposal_proposed_by FOREIGN KEY (proposed_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_proposal_approved_by FOREIGN KEY (approved_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_proposal_rejected_by FOREIGN KEY (rejected_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_proposal_workload FOREIGN KEY (resulting_workload_id)
        REFERENCES workload_assignments(workload_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_proposal_previous FOREIGN KEY (previous_proposal_id)
        REFERENCES workload_assignment_proposals(proposal_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
