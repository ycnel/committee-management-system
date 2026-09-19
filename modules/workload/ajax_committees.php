<?php
/**
 * modules/workload/ajax_committees.php
 * ------------------------------------------------------------------
 * Returns the committees under one jurisdiction as JSON so the
 * jurisdiction cards can open a modal instead of navigating away.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]);
session_write_close(); // read-only endpoint: release the session lock for concurrent requests

$jurisdictionId = (int)($_GET['jurisdiction_id'] ?? 0);
if ($jurisdictionId <= 0) {
    jsonResponse(false, 'Invalid jurisdiction.');
}

$pdo = db();
$jurisdictionStmt = $pdo->prepare(
    "SELECT jurisdiction_id, jurisdiction_name, category, description
     FROM jurisdictions
     WHERE jurisdiction_id = :id AND status = 'Active'
     LIMIT 1"
);
$jurisdictionStmt->execute([':id' => $jurisdictionId]);
$jurisdiction = $jurisdictionStmt->fetch();

if (!$jurisdiction) {
    jsonResponse(false, 'Jurisdiction not found.');
}

if (isCommitteeMember()) {
    $stmt = $pdo->prepare(
        "SELECT c.committee_id, c.committee_name, c.description, c.status
         FROM committees c
         INNER JOIN committee_members cm ON cm.committee_id = c.committee_id
         WHERE c.jurisdiction_id = :jurisdiction_id
           AND c.status = 'Active' AND cm.user_id = :user_id AND cm.status = 'Active'
         ORDER BY c.committee_name"
    );
    $stmt->execute([':jurisdiction_id' => $jurisdictionId, ':user_id' => currentUserId()]);
} else {
    $stmt = $pdo->prepare(
        "SELECT committee_id, committee_name, description, status
         FROM committees
         WHERE jurisdiction_id = :jurisdiction_id AND status = 'Active'
         ORDER BY committee_name"
    );
    $stmt->execute([':jurisdiction_id' => $jurisdictionId]);
}

jsonResponse(true, '', [
    'jurisdiction' => $jurisdiction,
    'committees'   => $stmt->fetchAll(),
]);
