-- Add unique internal CMAS tracking references to existing report drafts.
-- These references are not official legislative measure numbers.

USE committee_management_db;

ALTER TABLE committee_report_drafts
    ADD COLUMN internal_reference_no VARCHAR(40) DEFAULT NULL AFTER report_number,
    ADD UNIQUE KEY uq_committee_report_internal_reference (internal_reference_no);

UPDATE committee_report_drafts
SET internal_reference_no = CONCAT(
    'CMAS-REF-',
    YEAR(created_at),
    '-',
    LPAD(draft_id, 6, '0')
)
WHERE internal_reference_no IS NULL;
