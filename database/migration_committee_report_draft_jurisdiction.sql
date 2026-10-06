-- Save the single jurisdiction selected for each committee report.
-- Existing reports use the committee's directly assigned jurisdiction when present.
-- Run once:
--   mysql -u root -p committee_management_db < database/migration_committee_report_draft_jurisdiction.sql

USE committee_management_db;

ALTER TABLE committee_report_drafts
    ADD COLUMN jurisdiction_id INT DEFAULT NULL AFTER committee_id,
    ADD KEY idx_draft_jurisdiction (jurisdiction_id),
    ADD CONSTRAINT fk_draft_jurisdiction
        FOREIGN KEY (jurisdiction_id)
        REFERENCES jurisdictions(jurisdiction_id) ON UPDATE CASCADE ON DELETE SET NULL;

UPDATE committee_report_drafts d
INNER JOIN committees c ON c.committee_id = d.committee_id
SET d.jurisdiction_id = c.jurisdiction_id
WHERE d.jurisdiction_id IS NULL AND c.jurisdiction_id IS NOT NULL;
