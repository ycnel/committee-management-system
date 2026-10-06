-- Fresh-install schema for the Committee Report legislative-document
-- workflow. committee_reports remains the existing export/final-report
-- index; committee_report_drafts stores editable report content and review.
--
-- Run once:
--   mysql -u root -p committee_management_db < database/migration_committee_report_drafts.sql

USE committee_management_db;

CREATE TABLE IF NOT EXISTS committee_report_drafts (
    draft_id          INT AUTO_INCREMENT PRIMARY KEY,
    committee_id      INT DEFAULT NULL,
    jurisdiction_id   INT DEFAULT NULL,
    report_type       ENUM('Committee','Workload','Performance','Monthly','Annual') NOT NULL DEFAULT 'Committee',
    report_title      VARCHAR(255) NOT NULL,

    status             ENUM('Draft','AI-Assisted Draft','For Review','Under Review','Returned for Revision','Approved','Final','Archived',
                            'AI Draft','Under Human Review','Revised')
                        NOT NULL DEFAULT 'Draft',
    ai_generated        TINYINT(1) NOT NULL DEFAULT 0,

    -- Narrative content — same shape includes/GeminiAI.php's
    -- generateReportNarrative() / report_narrative.php's fallback
    -- already produce, so an AI-generated draft's content can be saved
    -- here verbatim with no reshaping.
    executive_summary   TEXT DEFAULT NULL,
    analysis             TEXT DEFAULT NULL,
    observations          TEXT DEFAULT NULL,
    recommendations        TEXT DEFAULT NULL,
    conclusion              TEXT DEFAULT NULL,

    -- §17's linkage list, for the data CMAS doesn't itself own the
    -- authoritative record of: free-text reference fields rather than
    -- foreign keys into subsystems that don't exist in this database
    -- (role-hierarchy revision §13: "CMAS references the legislative
    -- matter" — a reference, not a duplicate record).
    legislative_matter_ref  VARCHAR(255) DEFAULT NULL,
    meeting_ref              VARCHAR(255) DEFAULT NULL,
    hearing_ref                VARCHAR(255) DEFAULT NULL,
    research_ref                 VARCHAR(255) DEFAULT NULL,
    -- One "label — reference/URL" pair per line; see
    -- includes/report_drafts.php::parseDocumentReferences().
    document_references            TEXT DEFAULT NULL,
    -- Assignment linkage (§17): an optional specific task this report
    -- covers, e.g. a report written about one particular assignment
    -- rather than the whole committee.
    linked_workload_id               INT DEFAULT NULL,

    created_by     INT DEFAULT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by    INT DEFAULT NULL,
    reviewed_at    DATETIME DEFAULT NULL,
    finalized_by   INT DEFAULT NULL,
    finalized_at   DATETIME DEFAULT NULL,
    -- Set only once Finalized: links to the resulting export-log row,
    -- the same way workload_assignment_proposals.resulting_workload_id
    -- links to its resulting confirmed assignment.
    report_id      INT DEFAULT NULL,
    report_number VARCHAR(40) DEFAULT NULL,
    internal_reference_no VARCHAR(40) DEFAULT NULL,
    proposed_ordinance_title VARCHAR(500) DEFAULT NULL,
    reference_measure_no VARCHAR(150) DEFAULT NULL,
    date_referred DATE DEFAULT NULL,
    referred_by VARCHAR(255) DEFAULT NULL,
    subject_title VARCHAR(500) DEFAULT NULL,
    matter_referred TEXT DEFAULT NULL,
    committee_proceedings MEDIUMTEXT DEFAULT NULL,
    findings MEDIUMTEXT DEFAULT NULL,
    discussion_analysis MEDIUMTEXT DEFAULT NULL,
    recommendation_type VARCHAR(100) DEFAULT NULL,
    legislative_history MEDIUMTEXT DEFAULT NULL,
    committee_amendments MEDIUMTEXT DEFAULT NULL,
    individual_views MEDIUMTEXT DEFAULT NULL,
    committee_action TEXT DEFAULT NULL,
    committee_action_date DATE DEFAULT NULL,
    signature_details MEDIUMTEXT DEFAULT NULL,
    appendices MEDIUMTEXT DEFAULT NULL,
    ai_sources MEDIUMTEXT DEFAULT NULL,
    returned_reason TEXT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_draft_committee (committee_id, status),
    KEY idx_draft_jurisdiction (jurisdiction_id),
    KEY idx_report_draft_status_updated (status, updated_at),
    UNIQUE KEY uq_committee_report_draft_number (report_number),
    UNIQUE KEY uq_committee_report_internal_reference (internal_reference_no),
    CONSTRAINT fk_draft_committee FOREIGN KEY (committee_id)
        REFERENCES committees(committee_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_draft_jurisdiction FOREIGN KEY (jurisdiction_id)
        REFERENCES jurisdictions(jurisdiction_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_draft_workload FOREIGN KEY (linked_workload_id)
        REFERENCES workload_assignments(workload_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_draft_created_by FOREIGN KEY (created_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_draft_reviewed_by FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_draft_finalized_by FOREIGN KEY (finalized_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_draft_approved_by FOREIGN KEY (approved_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_draft_report FOREIGN KEY (report_id)
        REFERENCES committee_reports(report_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
