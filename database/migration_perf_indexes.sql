-- Performance indexes for filtered/sorted hot paths.
-- activity_logs: audit page orders by created_at and filters by action/date range.
-- workload_assignments: status filter + due_date ordering on task lists.
ALTER TABLE `activity_logs`
  ADD KEY `idx_logs_created` (`created_at`),
  ADD KEY `idx_logs_action` (`action`);

ALTER TABLE `workload_assignments`
  ADD KEY `idx_wa_status` (`status`),
  ADD KEY `idx_wa_due` (`due_date`);
