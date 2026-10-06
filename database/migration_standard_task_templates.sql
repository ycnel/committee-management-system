-- Standard legislative task templates and task context references.
-- Additive migration: existing assignments/proposals are retained; new
-- references remain NULL for historical rows unless explicitly set later.
-- Run after schema.sql and migration_member_acceptance_workflow.sql.

USE committee_management_db;

CREATE TABLE IF NOT EXISTS task_templates (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    jurisdiction_id INT DEFAULT NULL,
    task_name       VARCHAR(255) NOT NULL,
    description     TEXT DEFAULT NULL,
    task_type       ENUM('Core','Jurisdiction') NOT NULL DEFAULT 'Core',
    sequence_order  INT NOT NULL DEFAULT 0,
    is_required     TINYINT(1) NOT NULL DEFAULT 0,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_task_template_jurisdiction_name (jurisdiction_id, task_name),
    KEY idx_task_templates_active_order (is_active, sequence_order),
    CONSTRAINT fk_task_template_jurisdiction FOREIGN KEY (jurisdiction_id)
        REFERENCES jurisdictions(jurisdiction_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS task_template_requests (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    requested_by    INT DEFAULT NULL,
    jurisdiction_id INT DEFAULT NULL,
    task_name       VARCHAR(255) NOT NULL,
    description     TEXT DEFAULT NULL,
    sequence_order  INT NOT NULL DEFAULT 0,
    is_required     TINYINT(1) NOT NULL DEFAULT 0,
    status          ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    reviewed_by     INT DEFAULT NULL,
    reviewed_at     DATETIME DEFAULT NULL,
    admin_response  VARCHAR(1000) DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_task_template_requests_status (status, created_at),
    KEY idx_task_template_requests_requester (requested_by, created_at),
    CONSTRAINT fk_task_template_request_user FOREIGN KEY (requested_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_task_template_request_jurisdiction FOREIGN KEY (jurisdiction_id)
        REFERENCES jurisdictions(jurisdiction_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_task_template_request_reviewer FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE workload_assignments
    ADD COLUMN IF NOT EXISTS task_template_id INT DEFAULT NULL AFTER committee_member_id,
    ADD COLUMN IF NOT EXISTS jurisdiction_id INT DEFAULT NULL AFTER task_template_id,
    ADD KEY IF NOT EXISTS idx_workload_template (task_template_id),
    ADD KEY IF NOT EXISTS idx_workload_jurisdiction (jurisdiction_id);

ALTER TABLE workload_assignment_proposals
    ADD COLUMN IF NOT EXISTS task_template_id INT DEFAULT NULL AFTER committee_member_id,
    ADD COLUMN IF NOT EXISTS jurisdiction_id INT DEFAULT NULL AFTER task_template_id,
    ADD KEY IF NOT EXISTS idx_proposal_template (task_template_id),
    ADD KEY IF NOT EXISTS idx_proposal_jurisdiction (jurisdiction_id);

DROP PROCEDURE IF EXISTS cmas_add_task_template_foreign_keys;
DELIMITER //
CREATE PROCEDURE cmas_add_task_template_foreign_keys()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = 'workload_assignments'
          AND CONSTRAINT_NAME = 'fk_wl_task_template'
    ) THEN
        ALTER TABLE workload_assignments
            ADD CONSTRAINT fk_wl_task_template FOREIGN KEY (task_template_id)
            REFERENCES task_templates(id) ON UPDATE CASCADE ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = 'workload_assignments'
          AND CONSTRAINT_NAME = 'fk_wl_jurisdiction'
    ) THEN
        ALTER TABLE workload_assignments
            ADD CONSTRAINT fk_wl_jurisdiction FOREIGN KEY (jurisdiction_id)
            REFERENCES jurisdictions(jurisdiction_id) ON UPDATE CASCADE ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = 'workload_assignment_proposals'
          AND CONSTRAINT_NAME = 'fk_proposal_task_template'
    ) THEN
        ALTER TABLE workload_assignment_proposals
            ADD CONSTRAINT fk_proposal_task_template FOREIGN KEY (task_template_id)
            REFERENCES task_templates(id) ON UPDATE CASCADE ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = 'workload_assignment_proposals'
          AND CONSTRAINT_NAME = 'fk_proposal_jurisdiction'
    ) THEN
        ALTER TABLE workload_assignment_proposals
            ADD CONSTRAINT fk_proposal_jurisdiction FOREIGN KEY (jurisdiction_id)
            REFERENCES jurisdictions(jurisdiction_id) ON UPDATE CASCADE ON DELETE SET NULL;
    END IF;
END//
DELIMITER ;
CALL cmas_add_task_template_foreign_keys();
DROP PROCEDURE cmas_add_task_template_foreign_keys;

INSERT INTO task_templates
    (jurisdiction_id, task_name, description, task_type, sequence_order, is_required, is_active)
SELECT NULL, seed.task_name, seed.description, 'Core', seed.sequence_order, seed.is_required, 1
FROM (
    SELECT 'Initial Review of Legislative Measure' AS task_name,
           'Review the referred measure and identify its subject, intent, and committee scope.' AS description,
           10 AS sequence_order, 1 AS is_required
    UNION ALL SELECT 'Gather Supporting Documents',
           'Collect relevant records, background materials, and supporting documents.', 20, 0
    UNION ALL SELECT 'Conduct Committee Deliberation',
           'Discuss the measure and record the committee deliberation.', 30, 0
    UNION ALL SELECT 'Consult Concerned Office/Stakeholders',
           'Seek relevant input from offices, stakeholders, or affected groups.', 40, 0
    UNION ALL SELECT 'Evaluate Proposed Measure',
           'Evaluate the proposal, its expected effects, and implementation considerations.', 50, 0
    UNION ALL SELECT 'Prepare Committee Recommendation',
           'Prepare the committee recommendation for consideration by the legislative body.', 60, 0
    UNION ALL SELECT 'Prepare Committee Report',
           'Prepare the committee report documenting proceedings, findings, and recommendations.', 70, 0
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM task_templates existing
    WHERE existing.jurisdiction_id IS NULL
      AND existing.task_name = seed.task_name
);

INSERT INTO task_templates
    (jurisdiction_id, task_name, description, task_type, sequence_order, is_required, is_active)
SELECT j.jurisdiction_id, seed.task_name, seed.description, 'Jurisdiction',
       seed.sequence_order, 0, 1
FROM (
    SELECT 'City Budget and Expenditures' AS jurisdiction_name,
           'Review Budget Allocation' AS task_name,
           'Review the proposed budget allocation and its relationship to the measure.' AS description,
           110 AS sequence_order
    UNION ALL SELECT 'City Budget and Expenditures', 'Verify Funding Source',
           'Check the proposed funding source and related fiscal considerations.', 120
    UNION ALL SELECT 'Legislative consideration of claims, fund adjustments and expenditures chargeable against city funds.',
           'Review Claims and Fund Adjustments',
           'Review supporting information for claims, fund adjustments, and city expenditures.', 110
    UNION ALL SELECT 'Legal review of proposed ordinance', 'Review Legal Provisions',
           'Review the proposed ordinance language and relevant legal provisions.', 110
    UNION ALL SELECT 'City contract review', 'Review City Contract Terms',
           'Review the relevant city contract terms and supporting records.', 110
    UNION ALL SELECT 'Investigation of alleged misuse of city funds', 'Review Audit and Investigation Records',
           'Review relevant audit materials and records supporting the investigation.', 110
    UNION ALL SELECT 'Public Accountability and Government Transactions', 'Review Public Accountability Records',
           'Review records relevant to public accountability and government transactions.', 110
    UNION ALL SELECT 'Barangay Governance', 'Review Barangay Governance Impacts',
           'Review the proposal’s implications for barangay governance and local services.', 110
    UNION ALL SELECT 'Amusement Establishments', 'Review Amusement Establishment Requirements',
           'Review requirements and supporting information related to amusement establishments.', 110
    UNION ALL SELECT 'Games and Recreational Activities', 'Review Recreation Program Requirements',
           'Review requirements and supporting information related to games and recreational activities.', 110
) AS seed
INNER JOIN jurisdictions j
    ON j.jurisdiction_name = seed.jurisdiction_name
   AND j.status = 'Active'
WHERE NOT EXISTS (
    SELECT 1 FROM task_templates existing
    WHERE existing.jurisdiction_id = j.jurisdiction_id
      AND existing.task_name = seed.task_name
);
