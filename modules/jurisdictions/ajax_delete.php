<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/jurisdiction_removal_requests.php';
requireRole([ROLE_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$currentPassword = (string)($_POST['current_password'] ?? '');
if ($id <= 0) jsonResponse(false, 'Invalid jurisdiction id.');
if ($currentPassword === '') jsonResponse(false, 'Enter your current password to continue.');

$pdo = db();
try {
    $passwordStmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
    $passwordStmt->execute([':id' => (int)currentUserId()]);
    $passwordHash = $passwordStmt->fetchColumn();
    if (!$passwordHash || !password_verify($currentPassword, (string)$passwordHash)) {
        jsonResponse(false, 'Your current password is incorrect. The jurisdiction was not removed.');
    }

    $pdo->beginTransaction();
    $jurisdiction = lockJurisdictionForRemoval($pdo, $id);
    $pendingRequest = $pdo->prepare(
        "SELECT id FROM jurisdiction_removal_requests
         WHERE jurisdiction_id = :jurisdiction_id AND status = 'Pending'
         LIMIT 1"
    );
    $pendingRequest->execute([':jurisdiction_id' => $id]);
    if ($pendingRequest->fetch()) {
        $pdo->rollBack();
        jsonResponse(false, 'A removal request is pending for this jurisdiction. Process it from the Requests page first.');
    }
    $del = $pdo->prepare('DELETE FROM jurisdictions WHERE jurisdiction_id = :id');
    $del->execute([':id' => $id]);
    logActivity(
        currentUserId(),
        'Delete',
        'Deleted jurisdiction #' . $id . ' (' . $jurisdiction['jurisdiction_name'] . ')'
    );
    $pdo->commit();
    jsonResponse(true, 'Jurisdiction removed successfully.');
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Jurisdiction delete error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while removing the jurisdiction.');
}
