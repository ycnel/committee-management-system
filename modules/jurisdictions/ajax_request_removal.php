<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/jurisdiction_removal_requests.php';
requireRole([ROLE_STAFF]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$jurisdictionId = (int)($_POST['jurisdiction_id'] ?? 0);
$reason = clean($_POST['reason'] ?? '');
if ($jurisdictionId <= 0) jsonResponse(false, 'Invalid jurisdiction.');
if (mb_strlen($reason) > 5000) jsonResponse(false, 'The reason must not exceed 5,000 characters.');

$pdo = db();
try {
    $pdo->beginTransaction();
    $jurisdictionStmt = $pdo->prepare(
        'SELECT jurisdiction_name FROM jurisdictions WHERE jurisdiction_id = :id FOR UPDATE'
    );
    $jurisdictionStmt->execute([':id' => $jurisdictionId]);
    $jurisdiction = $jurisdictionStmt->fetch();
    if (!$jurisdiction) {
        $pdo->rollBack();
        jsonResponse(false, 'Jurisdiction not found.');
    }

    $pendingStmt = $pdo->prepare(
        "SELECT id FROM jurisdiction_removal_requests
         WHERE jurisdiction_id = :jurisdiction_id AND status = 'Pending'
         LIMIT 1"
    );
    $pendingStmt->execute([':jurisdiction_id' => $jurisdictionId]);
    if ($pendingStmt->fetch()) {
        $pdo->rollBack();
        jsonResponse(false, 'A removal request is already pending for this jurisdiction.');
    }

    $insert = $pdo->prepare(
        "INSERT INTO jurisdiction_removal_requests
            (jurisdiction_id, jurisdiction_name, requested_by, reason, status, requested_at)
         VALUES (:jurisdiction_id, :jurisdiction_name, :requested_by, :reason, 'Pending', NOW())"
    );
    $insert->execute([
        ':jurisdiction_id' => $jurisdictionId,
        ':jurisdiction_name' => $jurisdiction['jurisdiction_name'],
        ':requested_by' => (int)currentUserId(),
        ':reason' => $reason !== '' ? $reason : null,
    ]);
    $requestId = (int)$pdo->lastInsertId();
    $activityId = logActivity(
        currentUserId(),
        'Request Jurisdiction Removal',
        'Removal request #' . $requestId . ' for jurisdiction #' . $jurisdictionId
            . ' (' . $jurisdiction['jurisdiction_name'] . ')'
    );

    $admins = $pdo->prepare(
        "SELECT u.id
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE r.name = :role AND u.status = 'Active'"
    );
    $admins->execute([':role' => ROLE_ADMIN]);
    $adminIds = array_map('intval', $admins->fetchAll(PDO::FETCH_COLUMN));
    if (!$adminIds) {
        throw new RuntimeException('No active Administrator account is available to receive this request.');
    }

    $requesterName = currentUser()['full_name'] ?? 'A Committee Chairperson';
    $message = 'Jurisdiction Removal Request #' . $requestId . ': Chairperson '
        . $requesterName . ' requested removal of jurisdiction "'
        . $jurisdiction['jurisdiction_name'] . '" on ' . date('Y-m-d H:i:s')
        . '. Status: Pending. Please review the request.';
    $url = APP_URL . '/modules/jurisdictions/requests.php?request_id=' . $requestId;
    foreach ($adminIds as $adminId) {
        insertJurisdictionRemovalNotification($pdo, $adminId, $message, $url, $activityId);
    }

    $pdo->commit();
    jsonResponse(true, 'Your removal request has been sent to the Administrator.', [
        'request_id' => $requestId,
    ]);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Jurisdiction removal request error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while submitting the request.');
}
