-- Jurisdiction removal requests from Committee Chairpersons.
-- Run once on existing installations.

USE committee_management_db;

CREATE TABLE IF NOT EXISTS jurisdiction_removal_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jurisdiction_id INT DEFAULT NULL,
    jurisdiction_name VARCHAR(150) NOT NULL,
    requested_by INT DEFAULT NULL,
    reason TEXT DEFAULT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_by INT DEFAULT NULL,
    processed_at DATETIME DEFAULT NULL,
    admin_response TEXT DEFAULT NULL,
    KEY idx_jurisdiction_removal_status (status, requested_at),
    KEY idx_jurisdiction_removal_requester (requested_by, requested_at),
    CONSTRAINT fk_jurisdiction_removal_jurisdiction FOREIGN KEY (jurisdiction_id)
        REFERENCES jurisdictions(jurisdiction_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_jurisdiction_removal_requester FOREIGN KEY (requested_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_jurisdiction_removal_processor FOREIGN KEY (processed_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
