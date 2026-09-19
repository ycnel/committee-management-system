-- ===========================================================
-- database/migration_jurisdiction_scope.sql
-- Adds detailed scope definition fields to existing jurisdictions.
-- Run this after database/schema.sql.
-- ===========================================================

USE committee_management_db;

ALTER TABLE jurisdictions
    ADD COLUMN IF NOT EXISTS scope_definition TEXT DEFAULT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS covered_areas TEXT DEFAULT NULL AFTER scope_definition,
    ADD COLUMN IF NOT EXISTS primary_responsibilities TEXT DEFAULT NULL AFTER covered_areas,
    ADD COLUMN IF NOT EXISTS typical_legislative_matters TEXT DEFAULT NULL AFTER primary_responsibilities,
    ADD COLUMN IF NOT EXISTS outside_scope TEXT DEFAULT NULL AFTER typical_legislative_matters,
    ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL AFTER outside_scope;
