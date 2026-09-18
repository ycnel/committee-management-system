-- ===========================================================
-- database/migration_role_rename.sql
-- ------------------------------------------------------------
-- Renames the "Legislative Staff" role to "Committee Chairperson"
-- on an EXISTING database (same role id, same users, only the
-- display name changes). Run this ONCE if your database was
-- created before this rename:
--
--   mysql -u root -p committee_management_db < database/migration_role_rename.sql
--
-- Safe to re-run: the UPDATE only matches rows still named
-- 'Legislative Staff', so running it again after it has already
-- taken effect is a harmless no-op.
--
-- No other schema changes are required for the accompanying role/
-- permission update -- only this display name and the application
-- code's permission logic changed. All existing users keep the
-- same account, committee memberships, and task history; only what
-- that role is allowed to do (and what it's called) changes.
-- ===========================================================

USE committee_management_db;

UPDATE roles SET name = 'Committee Chairperson' WHERE name = 'Legislative Staff';
