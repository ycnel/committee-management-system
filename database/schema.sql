-- ===========================================================
-- database/schema.sql
-- Committee Management and Assignment System (CMAS) — standalone
-- ------------------------------------------------------------
-- Run this ONCE against a fresh MySQL/MariaDB server:
--   mysql -u root -p < database/schema.sql
-- It creates the database, every table, seeds the 3 roles, and
-- seeds one Administrator login so you can sign in immediately.
-- ===========================================================

CREATE DATABASE IF NOT EXISTS committee_management_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE committee_management_db;

-- ---------------------------------------------------------
-- ROLES
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NOTE: role id 2 was originally seeded as "Legislative Staff" and has
-- since been renamed to "Committee Chairperson" (same id, same
-- permissions model updated in application code). Fresh installs get the
-- new name directly below; existing databases should run
-- database/migration_role_rename.sql once to update the stored value.
INSERT IGNORE INTO roles (id, name) VALUES
    (1, 'Administrator'),
    (2, 'Committee Chairperson'),
    (3, 'Committee Member');

-- ---------------------------------------------------------
-- USERS
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(150) NOT NULL,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role_id     INT NOT NULL,
    status     ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    current_session_token VARCHAR(64) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Administrator login:
--   email:    admin@cmas.local
--   password: Admin@123
-- CHANGE THIS PASSWORD after your first login (Profile page).
INSERT IGNORE INTO users (id, full_name, email, password, role_id, status)
VALUES (
    1, 'System Administrator', 'admin@cmas.local',
    '$2y$10$6VAt7t.5DEUyhInUVwnBU.21lt3h3cpxSdVGks5SdrksNg5okG7iG',
    1, 'Active'
);

-- A couple of extra seed accounts so you have something to assign to
-- committees right away.
--   staff@cmas.local  / Staff@123   (Committee Chairperson)
--   member1@cmas.local / Member@123 (Committee Member)
--   member2@cmas.local / Member@123 (Committee Member)
INSERT IGNORE INTO users (id, full_name, email, password, role_id, status) VALUES
    (2, 'Committee Chairperson One', 'staff@cmas.local',
     '$2y$10$Rhiyl6ijErXiqQ2u.lClRu2jN0Cel.h4BKrZPhwbDam.bN2UErdli', 2, 'Active'),
    (3, 'Juan Dela Cruz', 'member1@cmas.local',
     '$2y$10$eDWSrr687sOeMoBVuxHpj.0iAUP3G5Hdjonl1gPKmA2quRe5Reyz2', 3, 'Active'),
    (4, 'Maria Santos', 'member2@cmas.local',
     '$2y$10$eDWSrr687sOeMoBVuxHpj.0iAUP3G5Hdjonl1gPKmA2quRe5Reyz2', 3, 'Active');

-- ---------------------------------------------------------
-- USER BACKGROUND (optional profile data for members and staff)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_background (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    user_id                 INT NOT NULL,
    highest_education       VARCHAR(150) DEFAULT NULL,
    degree_course           VARCHAR(255) DEFAULT NULL,
    school_university       VARCHAR(255) DEFAULT NULL,
    major_specialization    VARCHAR(255) DEFAULT NULL,
    certifications_training TEXT DEFAULT NULL,
    current_profession      VARCHAR(255) DEFAULT NULL,
    years_experience        SMALLINT UNSIGNED DEFAULT NULL,
    previous_positions      TEXT DEFAULT NULL,
    previous_organizations  TEXT DEFAULT NULL,
    government_experience   TEXT DEFAULT NULL,
    primary_expertise       VARCHAR(255) DEFAULT NULL,
    secondary_expertise     TEXT DEFAULT NULL,
    knowledge_areas         TEXT DEFAULT NULL,
    relevant_skills         TEXT DEFAULT NULL,
    committee_expertise     TEXT DEFAULT NULL,
    expertise_keywords      TEXT DEFAULT NULL,
    created_at              DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_background_user (user_id),
    CONSTRAINT fk_user_background_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- ACTIVITY LOGS
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
    id                       INT AUTO_INCREMENT PRIMARY KEY,
    user_id                  INT DEFAULT NULL,
    action                   VARCHAR(100) NOT NULL,
    details                  TEXT,
    ip_address               VARCHAR(45) DEFAULT NULL,
    user_agent               VARCHAR(500) DEFAULT NULL,
    session_duration_seconds INT UNSIGNED DEFAULT NULL,
    created_at               DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- NOTIFICATIONS
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    notification_id    INT AUTO_INCREMENT PRIMARY KEY,
    recipient_user_id  INT NOT NULL,
    activity_log_id    INT DEFAULT NULL,
    message            VARCHAR(500) NOT NULL,
    url                VARCHAR(500) DEFAULT NULL,
    read_at            DATETIME DEFAULT NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_notification_activity_recipient (activity_log_id, recipient_user_id),
    KEY idx_notifications_recipient (recipient_user_id, read_at, created_at),
    CONSTRAINT fk_notification_recipient FOREIGN KEY (recipient_user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notification_activity FOREIGN KEY (activity_log_id)
        REFERENCES activity_logs(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- JURISDICTIONS  (Module 3: Jurisdiction and Scope)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS jurisdictions (
    jurisdiction_id   INT AUTO_INCREMENT PRIMARY KEY,
    jurisdiction_name VARCHAR(150) NOT NULL,
    category           VARCHAR(100) DEFAULT NULL,
    description         TEXT,
    scope_definition   TEXT DEFAULT NULL,
    covered_areas      TEXT DEFAULT NULL,
    primary_responsibilities TEXT DEFAULT NULL,
    typical_legislative_matters TEXT DEFAULT NULL,
    outside_scope     TEXT DEFAULT NULL,
    notes             TEXT DEFAULT NULL,
    status             ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_by         INT DEFAULT NULL,
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_jurisdiction_name (jurisdiction_name),
    CONSTRAINT fk_jurisdiction_creator FOREIGN KEY (created_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- COMMITTEES  (Module 1: Committee Formation)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS committees (
    committee_id     INT AUTO_INCREMENT PRIMARY KEY,
    committee_name   VARCHAR(150) NOT NULL,
    description       TEXT,
    jurisdiction_id   INT DEFAULT NULL,
    status           ENUM('Active','Inactive','Dissolved') NOT NULL DEFAULT 'Active',
    date_created     DATE DEFAULT NULL,
    created_by       INT DEFAULT NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_committee_name (committee_name),
    CONSTRAINT fk_committee_jurisdiction FOREIGN KEY (jurisdiction_id)
        REFERENCES jurisdictions(jurisdiction_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_committee_creator FOREIGN KEY (created_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- COMMITTEE MEMBERS  (Module 2: Member Assignment)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS committee_members (
    committee_member_id INT AUTO_INCREMENT PRIMARY KEY,
    committee_id         INT NOT NULL,
    user_id             INT NOT NULL,
    member_role         ENUM('Chairperson','Vice Chairperson','Member') NOT NULL DEFAULT 'Member',
    assigned_date       DATE DEFAULT NULL,
    status               ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_committee_user (committee_id, user_id),
    CONSTRAINT fk_cm_committee FOREIGN KEY (committee_id)
        REFERENCES committees(committee_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_cm_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- WORKLOAD ASSIGNMENTS  (Module 4: Smart Workload Distribution)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS workload_assignments (
    workload_id           INT AUTO_INCREMENT PRIMARY KEY,
    committee_member_id   INT NOT NULL,
    task_title             VARCHAR(255) NOT NULL,
    task_description       TEXT,
    priority               ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
    workload_points       INT NOT NULL DEFAULT 1,
    assigned_date         DATE DEFAULT NULL,
    due_date               DATE DEFAULT NULL,
    completion_date       DATE DEFAULT NULL,
    status                 ENUM('Pending','In Progress','Completed','Overdue') NOT NULL DEFAULT 'Pending',
    created_at             DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_workload_member_task_due (committee_member_id, task_title, due_date),

    CONSTRAINT fk_wl_member FOREIGN KEY (committee_member_id)
        REFERENCES committee_members(committee_member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- COMMITTEE PERFORMANCE  (Module 5: Performance Monitoring)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS committee_performance (
    performance_id     INT AUTO_INCREMENT PRIMARY KEY,
    committee_id         INT NOT NULL,
    evaluation_period   VARCHAR(100) NOT NULL,
    total_tasks         INT NOT NULL DEFAULT 0,
    completed_tasks     INT NOT NULL DEFAULT 0,
    pending_tasks       INT NOT NULL DEFAULT 0,
    overdue_tasks       INT NOT NULL DEFAULT 0,
    completion_rate     DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    remarks             TEXT,
    generated_at         DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_committee_performance_period (committee_id, evaluation_period),

    CONSTRAINT fk_perf_committee FOREIGN KEY (committee_id)
        REFERENCES committees(committee_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- COMMITTEE REPORTS  (Module 6: Committee Reporting)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS committee_reports (
    report_id       INT AUTO_INCREMENT PRIMARY KEY,
    committee_id     INT DEFAULT NULL,
    generated_by     INT NOT NULL,
    report_title     VARCHAR(255) NOT NULL,
    report_type     ENUM('Committee','Workload','Performance','Monthly','Annual') NOT NULL,
    file_path         VARCHAR(255) DEFAULT NULL,
    generated_at     DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_report_committee FOREIGN KEY (committee_id)
        REFERENCES committees(committee_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_report_user FOREIGN KEY (generated_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Seed jurisdictions so the Committees dropdown isn't empty.
-- ---------------------------------------------------------
INSERT IGNORE INTO jurisdictions (jurisdiction_name, category, description, status, created_by)
VALUES
    ('Health and Sanitation', 'Social Services', 'Oversees public health programs, sanitation, and hospital services.', 'Active', 1),
    ('Budget and Appropriations', 'Finance', 'Reviews and recommends the annual budget and fund allocations.', 'Active', 1),
    ('Peace and Order', 'Public Safety', 'Oversees police matters, public safety, and disaster preparedness.', 'Active', 1),
    ('Infrastructure and Public Works', 'Infrastructure', 'Reviews infrastructure projects, roads, and public facilities.', 'Active', 1);
