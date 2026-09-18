<?php
/** Self-only dashboard for Committee Member accounts. */

$pageTitle = 'My Dashboard';
$activeMenu = 'dashboard';
$pdo = db();
$user = currentUser();
$userId = currentUserId();

$summaryStmt = $pdo->prepare(
    "SELECT
        SUM(CASE WHEN wa.status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN wa.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN wa.status = 'Completed' THEN 1 ELSE 0 END) AS completed
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     WHERE cm.user_id = :uid"
);
$summaryStmt->execute([':uid' => $userId]);
$summary = $summaryStmt->fetch() ?: ['pending' => 0, 'in_progress' => 0, 'completed' => 0];

$taskStmt = $pdo->prepare(
        "SELECT wa.workload_id, wa.task_title, wa.due_date, wa.status,
          c.committee_name, COALESCE(head.full_name, c.committee_name) AS assigning_name
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     INNER JOIN committees c ON c.committee_id = cm.committee_id
       LEFT JOIN users head ON head.id = c.created_by
     WHERE cm.user_id = :uid
     ORDER BY (wa.due_date IS NULL), wa.due_date ASC, wa.created_at DESC
     LIMIT 10"
);
$taskStmt->execute([':uid' => $userId]);
$myTasks = $taskStmt->fetchAll();

$notificationStmt = $pdo->prepare(
        "SELECT wa.workload_id, wa.task_title, wa.due_date, wa.status,
          c.committee_name, COALESCE(head.full_name, c.committee_name) AS assigning_name
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     INNER JOIN committees c ON c.committee_id = cm.committee_id
       LEFT JOIN users head ON head.id = c.created_by
     WHERE cm.user_id = :uid AND wa.status IN ('Pending', 'In Progress')
     ORDER BY (wa.due_date IS NULL), wa.due_date ASC
     LIMIT 5"
);
$notificationStmt->execute([':uid' => $userId]);
$notifications = $notificationStmt->fetchAll();

$activityStmt = $pdo->prepare(
    'SELECT action, details, created_at, ip_address, user_agent, session_duration_seconds
     FROM activity_logs WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5'
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

    <div class="row g-3 mb-3">
      <?php foreach ([['pending', 'Pending', 'bg-gov-blue'], ['in_progress', 'In Progress', 'bg-gov-amber'], ['completed', 'Completed', 'bg-gov-teal']] as $card): ?>
        <div class="col-md-4"><div class="card stat-card <?= $card[2] ?>"><div class="card-body">
          <div class="stat-value"><?= (int)$summary[$card[0]] ?></div><div class="stat-label">My <?= e($card[1]) ?> Tasks</div>
        </div></div></div>
      <?php endforeach; ?>
    </div>

    <div class="row g-3">
      <div class="col-lg-7">
        <div class="card hero-card mb-3"><div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-semibold">Upcoming deadlines</span><span class="text-muted small">My tasks</span></div>
          <?php if (!$notifications): ?><p class="text-muted small mb-0">No pending or in-progress tasks.</p><?php endif; ?>
          <?php foreach ($notifications as $task): ?>
            <a class="list-row text-decoration-none text-reset" href="<?= e(APP_URL) ?>/modules/workload/task.php?id=<?= (int)$task['workload_id'] ?>">
              <div class="list-row-icon" style="background:#60a5fa;"><i class="bi bi-bell"></i></div>
              <div class="flex-grow-1"><div class="fw-semibold small"><?= e($task['task_title']) ?></div><div class="text-muted small">Assigned by <?= e($task['committee_name']) ?> &middot; Due <?= $task['due_date'] ? e(formatDate($task['due_date'])) : 'No due date' ?></div></div>
                <div class="flex-grow-1"><div class="fw-semibold small"><?= e($task['task_title']) ?></div><div class="text-muted small">Assigned by <?= e($task['assigning_name']) ?> (<?= e($task['committee_name']) ?>) &middot; Due <?= $task['due_date'] ? e(formatDate($task['due_date'])) : 'No due date' ?></div></div>
              <span class="badge bg-<?= $task['status'] === 'In Progress' ? 'warning' : 'secondary' ?>"><?= e($task['status']) ?></span>
            </a>
          <?php endforeach; ?>
        </div></div>

        <div class="card hero-card"><div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-semibold">My assigned tasks</span><span class="text-muted small">Quick access</span></div>
          <?php if (!$myTasks): ?><p class="text-muted small mb-0">No tasks assigned to you.</p><?php endif; ?>
          <?php foreach ($myTasks as $task): ?>
            <a class="list-row text-decoration-none text-reset" href="<?= e(APP_URL) ?>/modules/workload/task.php?id=<?= (int)$task['workload_id'] ?>"><div class="flex-grow-1"><div class="fw-semibold small"><?= e($task['task_title']) ?></div><div class="text-muted small"><?= e($task['committee_name']) ?> &middot; <?= $task['due_date'] ? e(formatDate($task['due_date'])) : 'No due date' ?></div></div><span class="badge bg-light text-dark border"><?= e($task['status']) ?></span></a>
          <?php endforeach; ?>
        </div></div>
      </div>

      <div class="col-lg-5"><div class="card hero-card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-semibold">My activity</span><a href="<?= e(APP_URL) ?>/pages/activity_logs.php" class="small">View all</a></div>
        <?php if (!$myActivity): ?><p class="text-muted small mb-0">No activity recorded yet.</p><?php endif; ?>
        <?php foreach ($myActivity as $activity): ?><div class="border-bottom py-2"><div class="small fw-semibold"><?= e($activity['action']) ?></div><div class="text-muted small"><?= e(formatDateTime($activity['created_at'])) ?><?= $activity['ip_address'] ? ' &middot; ' . e($activity['ip_address']) : '' ?></div><?php if ($activity['session_duration_seconds']): ?><div class="text-muted small">Session: <?= e((string)$activity['session_duration_seconds']) ?> seconds</div><?php endif; ?></div><?php endforeach; ?>
      </div></div></div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/layouts/footer.php'; ?>