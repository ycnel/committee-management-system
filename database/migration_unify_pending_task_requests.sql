-- Move outstanding legacy proposals into the Administrator review queue.
-- Historical Accepted/Declined/terminal proposals are left unchanged.

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
    ) NOT NULL DEFAULT 'Pending';

UPDATE workload_assignment_proposals
SET state = 'Pending'
WHERE state = 'Awaiting Response';
