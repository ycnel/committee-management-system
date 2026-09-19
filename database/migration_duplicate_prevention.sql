-- ===========================================================
-- database/migration_duplicate_prevention.sql
-- Adds the safe database backstop for workload task duplicates.
-- Run this after database/schema.sql.
--
-- Committee names, jurisdiction names, user emails, and committee
-- membership already have unique keys in the existing schema.
-- Performance snapshots are protected server-side because existing
-- databases may contain historical duplicate committee/period rows.
-- ===========================================================

USE committee_management_db;

ALTER TABLE workload_assignments
    ADD UNIQUE KEY IF NOT EXISTS uq_workload_member_task_due
        (committee_member_id, task_title, due_date);
