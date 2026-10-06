USE committee_management_db;

CREATE TABLE IF NOT EXISTS proposal_checklist_items (
    checklist_item_id  INT AUTO_INCREMENT PRIMARY KEY,
    proposal_id         INT NOT NULL,
    item_key            VARCHAR(60) NOT NULL,
    is_checked          TINYINT(1) NOT NULL DEFAULT 0,
    checked_by          INT DEFAULT NULL,
    checked_at          DATETIME DEFAULT NULL,
    notes               VARCHAR(500) DEFAULT NULL,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_proposal_checklist_item (proposal_id, item_key),
    CONSTRAINT fk_checklist_proposal FOREIGN KEY (proposal_id)
        REFERENCES workload_assignment_proposals(proposal_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_checklist_checked_by FOREIGN KEY (checked_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
