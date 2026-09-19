<?php
/**
 * database/seed_dev_data.php — DEV ONLY.
 * Inserts a few sample records so dashboards/modules show output.
 * Idempotent: safe to re-run (skips rows that already exist).
 * Usage: php database/seed_dev_data.php
 */

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/auth.php';

$pdo = db();
$out = function ($m) { echo $m . "\n"; };

// -- Look up existing users + jurisdictions ---------------------------------
$users = $pdo->query("SELECT id, full_name FROM users WHERE status = 'Active' ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);
$jurs  = $pdo->query("SELECT jurisdiction_id, jurisdiction_name FROM jurisdictions ORDER BY jurisdiction_id")->fetchAll(PDO::FETCH_KEY_PAIR);
if (count($users) < 3) { $out('Need at least 3 active users in `users` — aborting.'); exit(1); }
$userIds = array_keys($users);
$jurIds  = array_keys($jurs);

// -- Committees --------------------------------------------------------------
$committees = [
    ['Committee on Education',  'Oversees education policies, school programs, and scholarship initiatives.', 'Active'],
    ['Ways and Means Committee','Handles taxation, revenue measures, and appropriation reviews.', 'Active'],
    ['Committee on Senior Citizens and Social Welfare', 'Reviews programs for senior citizens and social welfare services.', 'Inactive'],
];
$committeeIds = [];
$cIns = $pdo->prepare('INSERT INTO committees (committee_name, description, jurisdiction_id, status, date_created) VALUES (?, ?, ?, ?, ?)');
$cGet = $pdo->prepare('SELECT committee_id FROM committees WHERE committee_name = ?');
foreach ($committees as $i => [$name, $desc, $status]) {
    $cGet->execute([$name]);
    if ($id = $cGet->fetchColumn()) { $committeeIds[] = (int)$id; $out("committee exists: $name"); continue; }
    $cIns->execute([$name, $desc, $jurIds[$i % count($jurIds)], $status, date('Y-m-d', strtotime("-{$i}0 days"))]);
    $committeeIds[] = (int)$pdo->lastInsertId();
    $out("committee added: $name");
}

// -- Committee members -------------------------------------------------------
// Chairperson = first user, others rotate through remaining users.
$mIns = $pdo->prepare('INSERT INTO committee_members (committee_id, user_id, member_role, assigned_date, status) VALUES (?, ?, ?, ?, "Active")');
$mGet = $pdo->prepare("SELECT committee_member_id FROM committee_members WHERE committee_id = ? AND user_id = ? AND status = 'Active'");
$memberIds = []; // committee_member_id => [committee_id, user_id]
$roles = ['Chairperson', 'Vice Chairperson', 'Member'];
foreach ($committeeIds as $ci => $cid) {
    foreach ($roles as $ri => $role) {
        $uid = $userIds[($ci + $ri) % count($userIds)];
        $mGet->execute([$cid, $uid]);
        if ($mid = $mGet->fetchColumn()) { $memberIds[] = [$cid, (int)$mid, $uid]; continue; }
        $mIns->execute([$cid, $uid, $role, date('Y-m-d', strtotime('-30 days'))]);
        $memberIds[] = [$cid, (int)$pdo->lastInsertId(), $uid];
        $out("member: {$users[$uid]} -> committee #$cid as $role");
    }
}

// -- Workload assignments ----------------------------------------------------
// Mixed statuses incl. overdue (past due_date) so charts/KPIs show all states.
$tasks = [
    ['Draft ordinance review — barangay health stations', 'Pending',     'High',   3, '+10 days', null],
    ['Prepare committee report for Q3 session',           'In Progress', 'Medium',  2, '+5 days',  null],
    ['Public hearing minutes consolidation',              'Completed',   'Medium',  2, '-2 days',  '-3 days'],
    ['Budget deliberation schedule',                      'Overdue',     'Urgent',  4, '-6 days',  null],
    ['Stakeholder consultation summary',                  'In Progress', 'Low',     1, '+12 days', null],
    ['Annual accomplishment report draft',                'Pending',     'High',    3, '+20 days', null],
    ['Review of waste management proposals',              'Completed',   'Medium',  2, '-8 days',  '-9 days'],
];
$wIns = $pdo->prepare('INSERT INTO workload_assignments (committee_member_id, task_title, task_description, priority, workload_points, assigned_date, due_date, completion_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$wGet = $pdo->prepare('SELECT workload_id FROM workload_assignments WHERE committee_member_id = ? AND task_title = ?');
$added = 0;
foreach ($tasks as $i => [$title, $status, $prio, $pts, $due, $done]) {
    if (empty($memberIds)) break;
    [$cid, $mid, $uid] = $memberIds[$i % count($memberIds)];
    $wGet->execute([$mid, $title]);
    if ($wGet->fetchColumn()) continue;
    $wIns->execute([$mid, $title, 'Seeded sample task for dashboard/report output.', $prio, $pts,
        date('Y-m-d', strtotime('-14 days')), date('Y-m-d', strtotime($due)),
        $done ? date('Y-m-d', strtotime($done)) : null, $status]);
    $added++;
}
$out("workload assignments added: $added");

// -- Notifications -----------------------------------------------------------
$adminId = $userIds[0];
$notes = [
    'New assignment added to your queue — review in Workload Distribution.',
    'Committee roster updated — a member was assigned a new role.',
];
$nGet = $pdo->prepare('SELECT notification_id FROM notifications WHERE recipient_user_id = ? AND message = ?');
$nIns = $pdo->prepare('INSERT INTO notifications (recipient_user_id, message, url) VALUES (?, ?, ?)');
foreach ($notes as $msg) {
    $nGet->execute([$adminId, $msg]);
    if ($nGet->fetchColumn()) continue;
    $nIns->execute([$adminId, $msg, APP_URL . '/modules/workload/index.php']);
    $out("notification added for {$users[$adminId]}");
}

$out('Done.');
