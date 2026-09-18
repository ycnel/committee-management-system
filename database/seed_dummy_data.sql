-- ===========================================================
-- database/seed_dummy_data.sql
-- ------------------------------------------------------------
-- OPTIONAL demo data for the Committee Management and Assignment
-- System. Run this AFTER database/schema.sql to populate the app
-- with realistic-looking committees, members, and tasks so every
-- dashboard/module has something to show instead of empty tables.
--
--   mysql -u root -p committee_management_db < database/seed_dummy_data.sql
--
-- Safe to re-run: users/committees/committee_members use INSERT
-- IGNORE against their unique keys, and workload_assignments uses
-- a NOT EXISTS guard keyed on (member, task title) so re-running
-- this script will not create duplicate rows.
-- ===========================================================

USE committee_management_db;

-- ---------------------------------------------------------
-- 1. Extra dummy Committee Member accounts
--    Password for all of them: Member@123
-- ---------------------------------------------------------
INSERT IGNORE INTO users (full_name, email, password, role_id, status) VALUES
    ('Pedro Reyes',      'member3@cmas.local', '$2y$10$eDWSrr687sOeMoBVuxHpj.0iAUP3G5Hdjonl1gPKmA2quRe5Reyz2', 3, 'Active'),
    ('Ana Garcia',       'member4@cmas.local', '$2y$10$eDWSrr687sOeMoBVuxHpj.0iAUP3G5Hdjonl1gPKmA2quRe5Reyz2', 3, 'Active'),
    ('Jose Ramirez',     'member5@cmas.local', '$2y$10$eDWSrr687sOeMoBVuxHpj.0iAUP3G5Hdjonl1gPKmA2quRe5Reyz2', 3, 'Active'),
    ('Liza Fernandez',   'member6@cmas.local', '$2y$10$eDWSrr687sOeMoBVuxHpj.0iAUP3G5Hdjonl1gPKmA2quRe5Reyz2', 3, 'Active'),
    ('Carlos Mendoza',   'member7@cmas.local', '$2y$10$eDWSrr687sOeMoBVuxHpj.0iAUP3G5Hdjonl1gPKmA2quRe5Reyz2', 3, 'Active'),
    ('Grace Torres',     'member8@cmas.local', '$2y$10$eDWSrr687sOeMoBVuxHpj.0iAUP3G5Hdjonl1gPKmA2quRe5Reyz2', 3, 'Active');

-- ---------------------------------------------------------
-- 2. Dummy committees, one per seeded jurisdiction
--    (jurisdictions themselves already come from schema.sql)
-- ---------------------------------------------------------
INSERT IGNORE INTO committees (committee_name, description, jurisdiction_id, status, date_created, created_by)
SELECT 'Committee on Health and Sanitation',
       'Handles public health programs, hospital services, and sanitation ordinances.',
       j.jurisdiction_id, 'Active', DATE_SUB(CURDATE(), INTERVAL 90 DAY), 1
FROM jurisdictions j WHERE j.jurisdiction_name = 'Health and Sanitation';

INSERT IGNORE INTO committees (committee_name, description, jurisdiction_id, status, date_created, created_by)
SELECT 'Committee on Budget and Appropriations',
       'Reviews the annual budget, fund releases, and supplemental appropriations.',
       j.jurisdiction_id, 'Active', DATE_SUB(CURDATE(), INTERVAL 75 DAY), 1
FROM jurisdictions j WHERE j.jurisdiction_name = 'Budget and Appropriations';

INSERT IGNORE INTO committees (committee_name, description, jurisdiction_id, status, date_created, created_by)
SELECT 'Committee on Peace and Order',
       'Oversees public safety, police coordination, and disaster preparedness.',
       j.jurisdiction_id, 'Active', DATE_SUB(CURDATE(), INTERVAL 60 DAY), 1
FROM jurisdictions j WHERE j.jurisdiction_name = 'Peace and Order';

INSERT IGNORE INTO committees (committee_name, description, jurisdiction_id, status, date_created, created_by)
SELECT 'Committee on Infrastructure and Public Works',
       'Reviews road, drainage, and public facility construction projects.',
       j.jurisdiction_id, 'Active', DATE_SUB(CURDATE(), INTERVAL 45 DAY), 1
FROM jurisdictions j WHERE j.jurisdiction_name = 'Infrastructure and Public Works';

-- ---------------------------------------------------------
-- 3. Member assignments (roles within each committee)
-- ---------------------------------------------------------
INSERT IGNORE INTO committee_members (committee_id, user_id, member_role, assigned_date, status)
SELECT c.committee_id, u.id, x.member_role, DATE_SUB(CURDATE(), INTERVAL 60 DAY), 'Active'
FROM (
    SELECT 'Committee on Health and Sanitation' AS committee_name, 'member1@cmas.local' AS email, 'Chairperson' AS member_role
    UNION ALL SELECT 'Committee on Health and Sanitation', 'member3@cmas.local', 'Vice Chairperson'
    UNION ALL SELECT 'Committee on Health and Sanitation', 'member4@cmas.local', 'Member'

    UNION ALL SELECT 'Committee on Budget and Appropriations', 'member2@cmas.local', 'Chairperson'
    UNION ALL SELECT 'Committee on Budget and Appropriations', 'member5@cmas.local', 'Vice Chairperson'
    UNION ALL SELECT 'Committee on Budget and Appropriations', 'member6@cmas.local', 'Member'

    UNION ALL SELECT 'Committee on Peace and Order', 'member7@cmas.local', 'Chairperson'
    UNION ALL SELECT 'Committee on Peace and Order', 'member1@cmas.local', 'Vice Chairperson'
    UNION ALL SELECT 'Committee on Peace and Order', 'member8@cmas.local', 'Member'

    UNION ALL SELECT 'Committee on Infrastructure and Public Works', 'member8@cmas.local', 'Chairperson'
    UNION ALL SELECT 'Committee on Infrastructure and Public Works', 'member4@cmas.local', 'Vice Chairperson'
    UNION ALL SELECT 'Committee on Infrastructure and Public Works', 'member5@cmas.local', 'Member'
) x
INNER JOIN committees c ON c.committee_name = x.committee_name
INNER JOIN users u ON u.email = x.email;

-- ---------------------------------------------------------
-- 4. Workload tasks — a mix of Pending / In Progress / Completed /
--    Overdue, across different priorities and due dates, so the
--    Workload dashboard, Recommendation panel, and Performance
--    charts all have something meaningful to display.
-- ---------------------------------------------------------
INSERT INTO workload_assignments
    (committee_member_id, task_title, task_description, priority, workload_points, assigned_date, due_date, completion_date, status)
SELECT cm.committee_member_id, t.task_title, t.task_description, t.priority, t.workload_points,
       t.assigned_date, t.due_date, t.completion_date, t.status
FROM (
    -- Health and Sanitation
    SELECT 'member1@cmas.local' AS email, 'Committee on Health and Sanitation' AS committee_name,
           'Draft sanitation ordinance revision' AS task_title,
           'Update the city sanitation code to reflect new DOH guidelines.' AS task_description,
           'High' AS priority, 8 AS workload_points,
           DATE_SUB(CURDATE(), INTERVAL 20 DAY) AS assigned_date, DATE_ADD(CURDATE(), INTERVAL 5 DAY) AS due_date,
           NULL AS completion_date, 'In Progress' AS status
    UNION ALL SELECT 'member3@cmas.local', 'Committee on Health and Sanitation',
           'Inspect barangay health centers', 'Site visits to assess facility readiness.',
           'Medium', 5, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), NULL, 'Overdue'
    UNION ALL SELECT 'member4@cmas.local', 'Committee on Health and Sanitation',
           'Compile Q2 health program report', 'Summarize outcomes of Q2 public health initiatives.',
           'Low', 3, DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Completed'

    -- Budget and Appropriations
    UNION ALL SELECT 'member2@cmas.local', 'Committee on Budget and Appropriations',
           'Review supplemental budget request', 'Evaluate the requested supplemental appropriation for disaster relief.',
           'Urgent', 10, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 'In Progress'
    UNION ALL SELECT 'member5@cmas.local', 'Committee on Budget and Appropriations',
           'Prepare fund utilization summary', 'Summarize fund releases and utilization rates for the quarter.',
           'Medium', 6, DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), NULL, 'Pending'
    UNION ALL SELECT 'member6@cmas.local', 'Committee on Budget and Appropriations',
           'Finalize FY budget hearing schedule', 'Coordinate hearing dates with department heads.',
           'Low', 2, DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), 'Completed'

    -- Peace and Order
    UNION ALL SELECT 'member7@cmas.local', 'Committee on Peace and Order',
           'Coordinate disaster preparedness drill', 'Organize a city-wide earthquake drill with the police and fire departments.',
           'High', 9, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), NULL, 'Pending'
    UNION ALL SELECT 'member1@cmas.local', 'Committee on Peace and Order',
           'Review curfew ordinance complaints', 'Assess reported issues with curfew enforcement.',
           'Medium', 4, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), NULL, 'Overdue'
    UNION ALL SELECT 'member8@cmas.local', 'Committee on Peace and Order',
           'Submit crime statistics report', 'Compile quarterly crime statistics from the local police station.',
           'Low', 3, DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'Completed'

    -- Infrastructure and Public Works
    UNION ALL SELECT 'member8@cmas.local', 'Committee on Infrastructure and Public Works',
           'Inspect drainage rehabilitation project', 'Site inspection of ongoing drainage works in District 2.',
           'High', 7, DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), NULL, 'In Progress'
    UNION ALL SELECT 'member4@cmas.local', 'Committee on Infrastructure and Public Works',
           'Review road widening proposal', 'Evaluate the feasibility study for the main avenue widening project.',
           'Urgent', 10, DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL, 'Pending'
    UNION ALL SELECT 'member5@cmas.local', 'Committee on Infrastructure and Public Works',
           'Close out streetlight installation project', 'Final walkthrough and acceptance of completed streetlight units.',
           'Medium', 5, DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_SUB(CURDATE(), INTERVAL 22 DAY), 'Completed'
) t
INNER JOIN committees c ON c.committee_name = t.committee_name
INNER JOIN users u ON u.email = t.email
INNER JOIN committee_members cm ON cm.committee_id = c.committee_id AND cm.user_id = u.id
WHERE NOT EXISTS (
    SELECT 1 FROM workload_assignments wa
    WHERE wa.committee_member_id = cm.committee_member_id AND wa.task_title = t.task_title
);

-- ---------------------------------------------------------
-- 5. A couple of historical performance snapshots, so the
--    Performance module's "Recent Snapshots" table isn't empty
--    on first look either.
-- ---------------------------------------------------------
INSERT INTO committee_performance
    (committee_id, evaluation_period, total_tasks, completed_tasks, pending_tasks, overdue_tasks, completion_rate, remarks, generated_at)
SELECT c.committee_id, 'Demo Data Snapshot', 3, 1, 1, 1, 33.33, 'Seeded demo snapshot.', DATE_SUB(NOW(), INTERVAL 30 DAY)
FROM committees c WHERE c.committee_name = 'Committee on Health and Sanitation'
AND NOT EXISTS (SELECT 1 FROM committee_performance cp WHERE cp.committee_id = c.committee_id AND cp.evaluation_period = 'Demo Data Snapshot');

INSERT INTO committee_performance
    (committee_id, evaluation_period, total_tasks, completed_tasks, pending_tasks, overdue_tasks, completion_rate, remarks, generated_at)
SELECT c.committee_id, 'Demo Data Snapshot', 3, 1, 1, 0, 33.33, 'Seeded demo snapshot.', DATE_SUB(NOW(), INTERVAL 30 DAY)
FROM committees c WHERE c.committee_name = 'Committee on Budget and Appropriations'
AND NOT EXISTS (SELECT 1 FROM committee_performance cp WHERE cp.committee_id = c.committee_id AND cp.evaluation_period = 'Demo Data Snapshot');
