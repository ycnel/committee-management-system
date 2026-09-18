<?php
/**
 * modules/committees/ajax_delete.php
 * ------------------------------------------------------------------
 * Deletes a committee. DB cascades remove its committee_members,
 * workload_assignments (via committee_members), and committee_
 * performance rows automatically (ON DELETE CASCADE).
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid committee id.');

$pdo = db();
try {
    $stmt = $pdo->prepare('SELECT committee_name FROM committees WHERE committee_id = :id');
    $stmt->execute([':id' => $id]);
    $committee = $stmt->fetch();
    if (!$committee) jsonResponse(false, 'Committee not found.');

    $del = $pdo->prepare('DELETE FROM committees WHERE committee_id = :id');
    $del->execute([':id' => $id]);

    logActivity(currentUserId(), 'Delete', 'Deleted committee #' . $id . ' (' . $committee['committee_name'] . ')');
    jsonResponse(true, 'Committee deleted successfully.');

} catch (PDOException $e) {
    error_log('Committee delete error: ' . $e->getMessage());
    if ((int)$e->getCode() === 23000 || str_contains($e->getMessage(), 'foreign key')) {
        jsonResponse(false, 'This committee cannot be deleted because it has linked records.');
    }
    jsonResponse(false, 'A database error occurred while deleting the committee.');
}
