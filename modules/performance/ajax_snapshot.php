<?php
/**
 * modules/performance/ajax_snapshot.php
 * ------------------------------------------------------------------
 * Computes the current KPIs for one committee and inserts a row into
 * `committee_performance`, giving the Committee Reporting module
 * (Module 6) a historical record to export instead of only ever
 * reporting on "right now".
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$committeeId = (int)($_POST['committee_id'] ?? 0);
if ($committeeId <= 0) jsonResponse(false, 'Invalid committee id.');

$pdo = db();

try {
    $nameStmt = $pdo->prepare('SELECT committee_name FROM committees WHERE committee_id = :id');
    $nameStmt->execute([':id' => $committeeId]);
    $committee = $nameStmt->fetch();
    if (!$committee) jsonResponse(false, 'Committee not found.');

    $kpiStmt = $pdo->prepare(
        "SELECT
            COUNT(wa.workload_id) AS total_tasks,
            SUM(CASE WHEN wa.status = 'Completed' THEN 1 ELSE 0 END) AS completed_tasks,
            SUM(CASE WHEN wa.status IN ('Pending','In Progress') THEN 1 ELSE 0 END) AS pending_tasks,
            SUM(CASE WHEN wa.status != 'Completed' AND wa.due_date IS NOT NULL AND wa.due_date < CURDATE() THEN 1 ELSE 0 END) AS overdue_tasks
         FROM committee_members cm
         LEFT JOIN workload_assignments wa ON wa.committee_member_id = cm.committee_member_id
         WHERE cm.committee_id = :cid AND cm.status = 'Active'"
    );
    $kpiStmt->execute([':cid' => $committeeId]);
    $kpi = $kpiStmt->fetch();

    $total = (int)($kpi['total_tasks'] ?? 0);
    $completed = (int)($kpi['completed_tasks'] ?? 0);
    $pending = (int)($kpi['pending_tasks'] ?? 0);
    $overdue = (int)($kpi['overdue_tasks'] ?? 0);
    $rate = $total > 0 ? round(($completed / $total) * 100, 2) : 0.0;
    $period = date('F Y');

    $insert = $pdo->prepare(
        'INSERT INTO committee_performance
            (committee_id, evaluation_period, total_tasks, completed_tasks, pending_tasks, overdue_tasks, completion_rate, remarks, generated_at)
         VALUES (:cid, :period, :total, :completed, :pending, :overdue, :rate, :remarks, NOW())'
    );
    $insert->execute([
        ':cid' => $committeeId, ':period' => $period, ':total' => $total, ':completed' => $completed,
        ':pending' => $pending, ':overdue' => $overdue, ':rate' => $rate,
        ':remarks' => 'Auto-generated snapshot from live workload data.',
    ]);

    logActivity(currentUserId(), 'Insert', 'Saved performance snapshot for "' . $committee['committee_name'] . '" (' . $period . ').');
    jsonResponse(true, 'Snapshot saved for ' . $committee['committee_name'] . '.');

} catch (PDOException $e) {
    error_log('Performance snapshot error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while saving the snapshot.');
}
