<?php
/** Self-only dashboard for Committee Member accounts. */

$pageTitle = 'My Dashboard';
$activeMenu = 'dashboard';
$pdo = db();
$user = currentUser();
$userId = currentUserId();

$summaryStmt = $pdo->prepare(
  "SELECT COUNT(wa.workload_id) AS total
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     WHERE cm.user_id = :uid"
);
$summaryStmt->execute([':uid' => $userId]);
$summary = $summaryStmt->fetch() ?: ['total' => 0];

$committeeStmt = $pdo->prepare(
  "SELECT c.committee_name, j.jurisdiction_name, cm.member_role,
      COUNT(wa.workload_id) AS assigned_tasks
   FROM committee_members cm
   INNER JOIN committees c ON c.committee_id = cm.committee_id
   LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id
   LEFT JOIN workload_assignments wa ON wa.committee_member_id = cm.committee_member_id
   WHERE cm.user_id = :uid AND cm.status = 'Active'
   GROUP BY cm.committee_member_id, c.committee_name, j.jurisdiction_name, cm.member_role
   ORDER BY c.committee_name"
);
$committeeStmt->execute([':uid' => $userId]);
$myCommittees = $committeeStmt->fetchAll();

$taskStmt = $pdo->prepare(
    "SELECT wa.workload_id, wa.task_title, wa.task_description, wa.assigned_date, wa.due_date, wa.priority,
      c.committee_name, j.jurisdiction_name, COALESCE(head.full_name, c.committee_name) AS assigning_name
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     INNER JOIN committees c ON c.committee_id = cm.committee_id
       LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id
       LEFT JOIN users head ON head.id = c.created_by
     WHERE cm.user_id = :uid
     ORDER BY (wa.due_date IS NULL), wa.due_date ASC, wa.created_at DESC
     LIMIT 10"
);
$taskStmt->execute([':uid' => $userId]);
$myTasks = $taskStmt->fetchAll();

$notificationStmt = $pdo->prepare(
    "SELECT wa.workload_id, wa.task_title, wa.task_description, wa.assigned_date, wa.due_date, wa.priority,
      c.committee_name, j.jurisdiction_name, COALESCE(head.full_name, c.committee_name) AS assigning_name
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     INNER JOIN committees c ON c.committee_id = cm.committee_id
       LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id
       LEFT JOIN users head ON head.id = c.created_by
    WHERE cm.user_id = :uid
     ORDER BY (wa.due_date IS NULL), wa.due_date ASC
     LIMIT 5"
);
$notificationStmt->execute([':uid' => $userId]);
$notifications = $notificationStmt->fetchAll();

$activityStmt = $pdo->prepare(
    'SELECT action, details, created_at, ip_address, user_agent, session_duration_seconds
      FROM activity_logs WHERE user_id = :uid ORDER BY created_at DESC, id DESC LIMIT 5'
);
$activityStmt->execute([':uid' => $userId]);
$myActivity = $activityStmt->fetchAll();

include __DIR__ . '/layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/layouts/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/layouts/content-topbar.php'; ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
      <div>
        <h4 class="mb-0">My Dashboard</h4>
        <small class="text-muted">Welcome back, <?= e($user['full_name']) ?></small>
      </div>
      <a href="<?= e(APP_URL) ?>/modules/workload/index.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-task"></i> My Tasks</a>
    </div>

    <div class="card hero-card overview-card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="fw-semibold">Overview</span>
          <span class="badge badge-soft-neutral">My assignments</span>
        </div>
        <div class="row">
          <div class="col-6">
            <div class="text-muted small mb-1"><i class="bi bi-diagram-3"></i> My Committees</div>
            <div class="hero-number"><?= count($myCommittees) ?></div>
            <span class="badge badge-soft-success mt-2"><i class="bi bi-person-check"></i> Active memberships</span>
          </div>
          <div class="col-6 border-start">
            <div class="text-muted small mb-1 ps-3"><i class="bi bi-list-task"></i> My Assignments</div>
            <div class="hero-number ps-3"><?= (int)$summary['total'] ?></div>
            <span class="badge badge-soft-gold mt-2 ms-3">Recorded assignments</span>
          </div>
        </div>
        <div class="d-flex gap-2 mt-3">
          <span class="badge badge-soft-neutral">Personal view</span>
          <span class="badge badge-soft-neutral">Assignment details available</span>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-7 member-dashboard-column">
        <div class="card hero-card mb-3 member-dashboard-card member-dashboard-top-card"><div class="card-body">
          <div class="member-card-heading"><div><span class="performance-kicker">Recent workload</span><h5>Upcoming deadlines</h5></div><i class="bi bi-calendar3 member-card-heading-icon"></i></div>
          <?php if (!$notifications): ?><p class="text-muted small mb-0">No upcoming assignments.</p><?php endif; ?>
          <?php foreach ($notifications as $task): ?>
            <a class="list-row text-decoration-none text-reset" href="<?= e(APP_URL) ?>/modules/workload/task.php?id=<?= (int)$task['workload_id'] ?>">
              <div class="list-row-icon member-task-icon"><i class="bi bi-bell"></i></div>
              <div class="flex-grow-1"><div class="fw-semibold small"><?= e($task['task_title']) ?></div><div class="text-muted small"><?= e($task['committee_name']) ?> &middot; <?= e($task['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?></div><div class="text-muted small">Assigned <?= $task['assigned_date'] ? e(formatDate($task['assigned_date'])) : 'No date' ?> &middot; Due <?= $task['due_date'] ? e(formatDate($task['due_date'])) : 'No due date' ?></div><div class="text-muted small mt-1"><?= e(truncate($task['task_description'] ?: 'No description provided.', 120)) ?></div><div class="mt-1"><span class="badge badge-soft-neutral"><?= e($task['priority']) ?></span></div></div>
                <span class="badge badge-soft-neutral">Assigned</span>
            </a>
          <?php endforeach; ?>
        </div></div>

        <div class="card hero-card member-dashboard-card"><div class="card-body">
          <div class="member-card-heading"><div><span class="performance-kicker">Task queue</span><h5>My assigned tasks</h5></div><a href="<?= e(APP_URL) ?>/modules/workload/index.php" class="small">View all</a></div>
          <?php if (!$myTasks): ?><p class="text-muted small mb-0">No tasks assigned to you.</p><?php endif; ?>
          <?php foreach ($myTasks as $task): ?>
              <a class="list-row text-decoration-none text-reset" href="<?= e(APP_URL) ?>/modules/workload/task.php?id=<?= (int)$task['workload_id'] ?>"><div class="list-row-icon member-task-icon"><i class="bi bi-list-task"></i></div><div class="flex-grow-1"><div class="fw-semibold small"><?= e($task['task_title']) ?></div><div class="text-muted small"><?= e($task['committee_name']) ?> &middot; <?= e($task['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?></div><div class="text-muted small">Assigned <?= $task['assigned_date'] ? e(formatDate($task['assigned_date'])) : 'No date' ?> &middot; Due <?= $task['due_date'] ? e(formatDate($task['due_date'])) : 'No due date' ?></div><div class="text-muted small mt-1"><?= e(truncate($task['task_description'] ?: 'No description provided.', 120)) ?></div><div class="mt-1"><span class="badge badge-soft-neutral"><?= e($task['priority']) ?></span></div></div><span class="badge badge-soft-neutral">Assigned</span></a>
          <?php endforeach; ?>
        </div></div>
      </div>

      <div class="col-lg-5 member-dashboard-column"><div class="card hero-card member-dashboard-card member-dashboard-top-card"><div class="card-body">
        <div class="member-card-heading"><div><span class="performance-kicker">Account history</span><h5>My activity</h5></div></div>
        <?php if (!$myActivity): ?><p class="text-muted small mb-0">No activity recorded yet.</p><?php endif; ?>
        <?php foreach ($myActivity as $activity): ?><div class="member-activity-row"><span class="member-activity-icon"><i class="bi bi-activity"></i></span><div><div class="small fw-semibold"><?= e($activity['action']) ?></div><div class="text-muted small"><?= e(formatDateTime($activity['created_at'])) ?><?= $activity['ip_address'] ? ' &middot; ' . e($activity['ip_address']) : '' ?></div><?php if ($activity['session_duration_seconds']): ?><div class="text-muted small">Session: <?= e((string)$activity['session_duration_seconds']) ?> seconds</div><?php endif; ?></div></div><?php endforeach; ?>
        </div></div>
        <div class="card hero-card member-dashboard-card mt-3"><div class="card-body"><div class="member-card-heading"><div><span class="performance-kicker">Committee assignments</span><h5>My Assigned Committees</h5></div><i class="bi bi-diagram-3 member-card-heading-icon"></i></div><div class="row g-3">
          <?php foreach ($myCommittees as $committee): ?><div class="col-12"><article class="member-committee-card"><div class="member-committee-top"><span class="member-committee-jurisdiction"><?= e($committee['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?></span><span class="member-committee-role"><?= e($committee['member_role']) ?></span></div><h6><?= e($committee['committee_name']) ?></h6><div class="member-committee-meta"><span><?= (int)$committee['assigned_tasks'] ?> assignments</span></div></article></div><?php endforeach; ?>
          <?php if (!$myCommittees): ?><div class="col-12"><p class="text-muted small mb-0">No active committee assignments.</p></div><?php endif; ?>
        </div></div></div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/layouts/footer.php'; ?>