-- Phase 1 of the role-hierarchy revision: adds the two missing
-- LEGISLATIVE AUTHORITY roles (Pro Tempore, Vice Mayor). Nothing
-- existing is renamed or removed — 'Administrator', 'Committee
-- Chairperson', 'Committee Member', and 'Super Admin' all keep their
-- current ids and meaning.
--
-- Run once per database:
--   mysql -u root -p committee_management_db < database/migration_role_hierarchy_phase1.sql

USE committee_management_db;

INSERT INTO roles (id, name)
SELECT 5, 'Pro Tempore' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'Pro Tempore');

INSERT INTO roles (id, name)
SELECT 6, 'Vice Mayor' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'Vice Mayor');
