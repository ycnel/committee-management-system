-- ===========================================================
-- database/migration_user_background.sql
-- Professional and educational background for users/committee members.
-- Run this after database/schema.sql.
-- ===========================================================

USE committee_management_db;

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
