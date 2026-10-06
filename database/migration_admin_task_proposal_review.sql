-- Add the Admin-review stage to the existing workload proposal workflow.
-- Existing member-response and reassignment history states are preserved.

USE committee_management_db;

ALTER TABLE workload_assignment_proposals
    MODIFY COLUMN state ENUM(
        'Pending',
        'Awaiting Response',
        'Accepted',
        'Declined',
        'Approved',
        'Rejected',
        'Reassigned',
        'Stopped'
    ) NOT NULL DEFAULT 'Pending',
    ADD COLUMN rejected_by INT DEFAULT NULL AFTER approved_at,
    ADD COLUMN rejected_at DATETIME DEFAULT NULL AFTER rejected_by,
    ADD COLUMN admin_response TEXT DEFAULT NULL AFTER rejected_at,
    ADD KEY idx_proposals_submitter_state (proposed_by, state),
    ADD CONSTRAINT fk_proposal_rejected_by FOREIGN KEY (rejected_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;
