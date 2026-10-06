<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/jurisdiction_removal_requests.php';
requireRole([ROLE_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$requestId = (int)($_POST['request_id'] ?? 0);
$decision = clean($_POST['decision'] ?? '');
$adminResponse = clean($_POST['admin_response'] ?? '');
if ($requestId <= 0) jsonResponse(false, 'Invalid removal request.');
if (!in_array($decision, ['Approved', 'Rejected'], true)) jsonResponse(false, 'Invalid request decision.');
if (mb_strlen($adminResponse) > 5000) jsonResponse(false, 'The response must not exceed 5,000 characters.');

$pdo = db();
try {
    $pdo->beginTransaction();
    $requestStmt = $pdo->prepare(
        'SELECT id, jurisdiction_id, jurisdiction_name, requested_by, status
         FROM jurisdiction_removal_requests WHERE id = :id FOR UPDATE'
    );
    $requestStmt->execute([':id' => $requestId]);
    $request = $requestStmt->fetch();
    if (!$request) {
        $pdo->rollBack();
        jsonResponse(false, 'Removal request not found.');
    }
    if ($request['status'] !== 'Pending') {
        $pdo->rollBack();
        jsonResponse(false, 'This removal request has already been processed.');
    }
    if ($decision === 'Approved') {
        if (!$request['jurisdiction_id']) {
            $pdo->rollBack();
            jsonResponse(false, 'The jurisdiction no longer exists.');
        }
        $jurisdiction = lockJurisdictionForRemoval($pdo, (int)$request['jurisdiction_id']);
        $message = 'Your jurisdiction removal request #' . $requestId . ' for "'
            . $request['jurisdiction_name'] . '" was approved. The jurisdiction was removed.';
        $activityDetails = 'Approved removal request #' . $requestId . ' and deleted jurisdiction #'
            . (int)$request['jurisdiction_id'] . ' (' . $jurisdiction['jurisdiction_name'] . ')';
    } else {
        $message = 'Your jurisdiction removal request #' . $requestId . ' for "'
            . $request['jurisdiction_name'] . '" was rejected.'
            . ($adminResponse !== '' ? ' Administrator response: ' . $adminResponse : '');
        $activityDetails = 'Rejected jurisdiction removal request #' . $requestId . ' for "'
            . $request['jurisdiction_name'] . '"';
    }

    $update = $pdo->prepare(
        'UPDATE jurisdiction_removal_requests
         SET status = :status, processed_by = :admin_id, processed_at = NOW(),
             admin_response = :response
         WHERE id = :id AND status = :pending'
    );
    $update->execute([
        ':status' => $decision,
        ':admin_id' => (int)currentUserId(),
        ':response' => $adminResponse !== '' ? $adminResponse : null,
        ':id' => $requestId,
        ':pending' => 'Pending',
    ]);
    if ($update->rowCount() !== 1) {
        $pdo->rollBack();
        jsonResponse(false, 'This removal request has already been processed.');
    }

    if ($decision === 'Approved') {
        $delete = $pdo->prepare('DELETE FROM jurisdictions WHERE jurisdiction_id = :id');
        $delete->execute([':id' => (int)$request['jurisdiction_id']]);
    }
    $activityId = logActivity(
        currentUserId(),
        $decision === 'Approved' ? 'Approve Jurisdiction Removal' : 'Reject Jurisdiction Removal',
        $activityDetails
    );
    if ($request['requested_by']) {
        insertJurisdictionRemovalNotification(
            $pdo,
            (int)$request['requested_by'],
            $message,
            APP_URL . '/modules/jurisdictions/requests.php?request_id=' . $requestId,
            $activityId
        );
    }
    $adminNotificationUpdate = $pdo->prepare(
        "UPDATE notifications
         SET message = REPLACE(message, 'Status: Pending.', :status_text)
         WHERE url = :request_url"
    );
    $adminNotificationUpdate->execute([
        ':status_text' => 'Status: ' . $decision . '.',
        ':request_url' => APP_URL . '/modules/jurisdictions/requests.php?request_id=' . $requestId,
    ]);

    $pdo->commit();
    jsonResponse(true, 'The removal request was ' . strtolower($decision) . '.');
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Jurisdiction removal decision error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while processing the request.');
}
