<?php
/**
 * modules/workload/ajax_members_by_committee.php
 * ------------------------------------------------------------------
 * Populates the "Assign To" dropdown in the task modal once a
 * committee is chosen.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
session_write_close(); // read-only endpoint: release the session lock for concurrent requests

$committeeId = (int)($_GET['committee_id'] ?? 0);
if ($committeeId <= 0) jsonResponse(false, 'Invalid committee id.');

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT cm.committee_member_id, u.full_name, cm.member_role
     FROM committee_members cm
     INNER JOIN users u ON u.id = cm.user_id
     WHERE cm.committee_id = :cid AND cm.status = 'Active'
     ORDER BY u.full_name"
);
$stmt->execute([':cid' => $committeeId]);
$members = $stmt->fetchAll();

jsonResponse(true, '', ['members' => $members]);
