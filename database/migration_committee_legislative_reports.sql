-- Extend the existing Committee Report drafts into legislative reports.
-- No committee, jurisdiction, member, or legislative-matter records are
-- duplicated. No ordinance/matter table exists in the current CMAS schema,
-- so the measure fields are editable source references rather than a new
-- legislative-matter registry.

USE committee_management_db;

ALTER TABLE committee_report_drafts
    MODIFY COLUMN status ENUM(
        'Draft',
        'AI-Assisted Draft',
        'For Review',
        'Under Review',
        'Returned for Revision',
        'Approved',
        'Final',
        'Archived',
        'AI Draft',
        'Under Human Review',
        'Revised'
    ) NOT NULL DEFAULT 'Draft',
    ADD COLUMN report_number VARCHAR(40) DEFAULT NULL AFTER report_id,
    ADD COLUMN proposed_ordinance_title VARCHAR(500) DEFAULT NULL AFTER report_number,
    ADD COLUMN reference_measure_no VARCHAR(150) DEFAULT NULL AFTER proposed_ordinance_title,
    ADD COLUMN date_referred DATE DEFAULT NULL AFTER reference_measure_no,
    ADD COLUMN referred_by VARCHAR(255) DEFAULT NULL AFTER date_referred,
    ADD COLUMN subject_title VARCHAR(500) DEFAULT NULL AFTER referred_by,
    ADD COLUMN matter_referred TEXT DEFAULT NULL AFTER subject_title,
    ADD COLUMN committee_proceedings MEDIUMTEXT DEFAULT NULL AFTER matter_referred,
    ADD COLUMN findings MEDIUMTEXT DEFAULT NULL AFTER committee_proceedings,
    ADD COLUMN discussion_analysis MEDIUMTEXT DEFAULT NULL AFTER findings,
    ADD COLUMN recommendation_type VARCHAR(100) DEFAULT NULL AFTER recommendations,
    ADD COLUMN legislative_history MEDIUMTEXT DEFAULT NULL AFTER recommendation_type,
    ADD COLUMN committee_amendments MEDIUMTEXT DEFAULT NULL AFTER legislative_history,
    ADD COLUMN individual_views MEDIUMTEXT DEFAULT NULL AFTER committee_amendments,
    ADD COLUMN committee_action TEXT DEFAULT NULL AFTER individual_views,
    ADD COLUMN committee_action_date DATE DEFAULT NULL AFTER committee_action,
    ADD COLUMN signature_details MEDIUMTEXT DEFAULT NULL AFTER committee_action_date,
    ADD COLUMN appendices MEDIUMTEXT DEFAULT NULL AFTER signature_details,
    ADD COLUMN ai_sources MEDIUMTEXT DEFAULT NULL AFTER appendices,
    ADD COLUMN returned_reason TEXT DEFAULT NULL AFTER ai_sources,
    ADD COLUMN approved_by INT DEFAULT NULL AFTER returned_reason,
    ADD COLUMN approved_at DATETIME DEFAULT NULL AFTER approved_by,
    ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD UNIQUE KEY uq_committee_report_draft_number (report_number),
    ADD KEY idx_report_draft_status_updated (status, updated_at),
    ADD CONSTRAINT fk_draft_approved_by FOREIGN KEY (approved_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;

UPDATE committee_report_drafts
SET status = CASE status
    WHEN 'AI Draft' THEN 'AI-Assisted Draft'
    WHEN 'Under Human Review' THEN 'Under Review'
    WHEN 'Revised' THEN 'Draft'
    ELSE status
END;

UPDATE committee_report_drafts
SET report_number = CONCAT('CR-', YEAR(created_at), '-', LPAD(draft_id, 4, '0'))
WHERE report_number IS NULL;

CREATE TABLE IF NOT EXISTS committee_report_draft_history (
    history_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    draft_id INT NOT NULL,
    action VARCHAR(40) NOT NULL,
    user_id INT DEFAULT NULL,
    user_role VARCHAR(100) DEFAULT NULL,
    previous_status VARCHAR(40) DEFAULT NULL,
    new_status VARCHAR(40) DEFAULT NULL,
    comments TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_report_history_draft (draft_id, created_at),
    CONSTRAINT fk_report_history_draft FOREIGN KEY (draft_id)
        REFERENCES committee_report_drafts(draft_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_report_history_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
