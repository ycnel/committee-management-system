<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole([ROLE_ADMIN, ROLE_STAFF]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid jurisdiction id.');

$pdo = db();
try {
    $stmt = $pdo->prepare('SELECT jurisdiction_name FROM jurisdictions WHERE jurisdiction_id = :id');
    $stmt->execute([':id' => $id]);
    $jurisdiction = $stmt->fetch();
    if (!$jurisdiction) jsonResponse(false, 'Jurisdiction not found.');

    $inUse = $pdo->prepare('SELECT COUNT(*) FROM committees WHERE jurisdiction_id = :id');
    $inUse->execute([':id' => $id]);
    if ((int)$inUse->fetchColumn() > 0) {
        jsonResponse(false, 'This jurisdiction is still assigned to one or more committees. Reassign those committees first.');
    }

    $del = $pdo->prepare('DELETE FROM jurisdictions WHERE jurisdiction_id = :id');
    $del->execute([':id' => $id]);

    logActivity(currentUserId(), 'Delete', 'Deleted jurisdiction #' . $id . ' (' . $jurisdiction['jurisdiction_name'] . ')');
    jsonResponse(true, 'Jurisdiction deleted successfully.');

} catch (PDOException $e) {
    error_log('Jurisdiction delete error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while deleting the jurisdiction.');
}
