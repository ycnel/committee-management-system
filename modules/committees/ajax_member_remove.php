<?php
/**
 * modules/committees/ajax_member_remove.php
 * ------------------------------------------------------------------
 * Removes a member from a committee. Soft-deletes (status =
 * 'Inactive') rather than hard-deleting, so that historical
 * workload/performance records tied to this committee_member_id
 * (Phase 2) remain intact and auditable.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid assignment id.');

$pdo = db();
try {
    $stmt = $pdo->prepare(
        'SELECT cm.committee_id, cm.user_id, u.full_name, c.committee_name
         FROM committee_members cm
         INNER JOIN users u ON u.id = cm.user_id
         INNER JOIN committees c ON c.committee_id = cm.committee_id
         WHERE cm.committee_member_id = :id'
    );
    $stmt->execute([':id' => $id]);
    $member = $stmt->fetch();
    if (!$member) jsonResponse(false, 'Assignment not found.');

    $upd = $pdo->prepare("UPDATE committee_members SET status = 'Inactive' WHERE committee_member_id = :id");
    $upd->execute([':id' => $id]);

    $activityId = logActivity(currentUserId(), 'Delete', $member['full_name'] . ' removed from committee "' . $member['committee_name'] . '".');
    createNotification(
        (int)$member['user_id'],
        'You were removed from committee "' . $member['committee_name'] . '".',
        APP_URL . '/modules/committees/index.php',
        $activityId
    );
    jsonResponse(true, 'Member removed from committee.');

} catch (PDOException $e) {
    error_log('Committee member remove error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while removing the member.');
}
