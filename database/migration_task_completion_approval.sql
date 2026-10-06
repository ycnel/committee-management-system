-- Task completion approval and member-requested task removal workflows.
-- Additive migration; existing assignment and proposal rows are preserved.
-- Run after migration_standard_task_templates.sql.

USE committee_management_db;

ALTER TABLE task_templates
    ADD COLUMN IF NOT EXISTS proof_requirement TEXT DEFAULT NULL AFTER description;

CREATE TABLE IF NOT EXISTS task_completion_requests (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    workload_id           INT DEFAULT NULL,
    submitted_by          INT DEFAULT NULL,
    task_title_snapshot   VARCHAR(255) NOT NULL,
    committee_id          INT DEFAULT NULL,
    committee_snapshot    VARCHAR(150) NOT NULL,
    jurisdiction_snapshot VARCHAR(150) DEFAULT NULL,
    completion_notes      TEXT NOT NULL,
    status                ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    reviewed_by           INT DEFAULT NULL,
    reviewed_at           DATETIME DEFAULT NULL,
    reviewer_remarks      TEXT DEFAULT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_completion_task_status (workload_id, status),
    KEY idx_completion_submitter (submitted_by, created_at),
    KEY idx_completion_status_created (status, created_at),
    CONSTRAINT fk_completion_workload FOREIGN KEY (workload_id)
        REFERENCES workload_assignments(workload_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_completion_submitter FOREIGN KEY (submitted_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_completion_committee FOREIGN KEY (committee_id)
        REFERENCES committees(committee_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_completion_reviewer FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS task_completion_request_files (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    request_id        INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename   VARCHAR(100) NOT NULL,
    file_path         VARCHAR(255) NOT NULL,
    mime_type         VARCHAR(127) NOT NULL,
    file_size         BIGINT UNSIGNED NOT NULL,
    uploaded_by       INT DEFAULT NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_completion_stored_filename (stored_filename),
    KEY idx_completion_file_request (request_id),
    CONSTRAINT fk_completion_file_request FOREIGN KEY (request_id)
        REFERENCES task_completion_requests(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_completion_file_uploader FOREIGN KEY (uploaded_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS task_removal_requests (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    workload_id           INT DEFAULT NULL,
    submitted_by          INT DEFAULT NULL,
    task_title_snapshot   VARCHAR(255) NOT NULL,
    committee_id          INT DEFAULT NULL,
    committee_snapshot    VARCHAR(150) NOT NULL,
    jurisdiction_snapshot VARCHAR(150) DEFAULT NULL,
    reason                TEXT NOT NULL,
    status                ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    reviewed_by           INT DEFAULT NULL,
    reviewed_at           DATETIME DEFAULT NULL,
    reviewer_remarks      TEXT DEFAULT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_removal_task_status (workload_id, status),
    KEY idx_removal_submitter (submitted_by, created_at),
    KEY idx_removal_committee_status (committee_id, status, created_at),
    CONSTRAINT fk_removal_workload FOREIGN KEY (workload_id)
        REFERENCES workload_assignments(workload_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_removal_submitter FOREIGN KEY (submitted_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_removal_committee FOREIGN KEY (committee_id)
        REFERENCES committees(committee_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_removal_reviewer FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
