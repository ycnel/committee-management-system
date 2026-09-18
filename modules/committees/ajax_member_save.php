<?php
/**
 * modules/committees/ajax_member_save.php
 * ------------------------------------------------------------------
 * Member Assignment module: assigns a user to a committee with a
 * committee role (Chairperson / Vice Chairperson / Member).
 * One user can only hold one active assignment per committee
 * (enforced by the uq_committee_user unique key as a DB-level
 * backstop, checked here first for a friendly error message).
 * Re-activates a previously removed (Inactive) assignment instead
 * of erroring on the unique key, if one already exists.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$committeeId  = (int)($_POST['committee_id'] ?? 0);
$userId       = (int)($_POST['user_id'] ?? 0);
$memberRole   = clean($_POST['member_role'] ?? 'Member');
$assignedDate = clean($_POST['assigned_date'] ?? '') ?: date('Y-m-d');

$allowedRoles = ['Chairperson', 'Vice Chairperson', 'Member'];

$errors = [];
if ($committeeId <= 0) $errors[] = 'Invalid committee.';
if ($userId <= 0) $errors[] = 'Please select a user to assign.';
if (!in_array($memberRole, $allowedRoles, true)) $errors[] = 'Invalid committee role.';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $assignedDate)) $errors[] = 'Invalid assigned date.';
if (!empty($errors)) jsonResponse(false, implode(' ', $errors));

$pdo = db();

try {
    $committeeStmt = $pdo->prepare('SELECT committee_name FROM committees WHERE committee_id = :id');
    $committeeStmt->execute([':id' => $committeeId]);
    $committee = $committeeStmt->fetch();
    if (!$committee) jsonResponse(false, 'Committee not found.');

    $userStmt = $pdo->prepare('SELECT full_name FROM users WHERE id = :id AND status = \'Active\'');
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch();
    if (!$user) jsonResponse(false, 'Selected user was not found or is inactive.');

    // Only one Chairperson allowed per committee - demote none automatically,
    // just warn if one already exists.
    if ($memberRole === 'Chairperson') {
        $chairStmt = $pdo->prepare(
            "SELECT u.full_name FROM committee_members cm INNER JOIN users u ON u.id = cm.user_id
             WHERE cm.committee_id = :cid AND cm.member_role = 'Chairperson' AND cm.status = 'Active'"
        );
        $chairStmt->execute([':cid' => $committeeId]);
        $existingChair = $chairStmt->fetch();
        if ($existingChair) {
            jsonResponse(false, $existingChair['full_name'] . ' is already the Chairperson of this committee. Change their role first.');
        }
    }

    // Reactivate an existing Inactive assignment if present, otherwise insert new.
    $existing = $pdo->prepare('SELECT committee_member_id FROM committee_members WHERE committee_id = :cid AND user_id = :uid');
    $existing->execute([':cid' => $committeeId, ':uid' => $userId]);
    $existingRow = $existing->fetch();

    if ($existingRow) {
        $stmt = $pdo->prepare(
            "UPDATE committee_members SET member_role = :role, assigned_date = :date, status = 'Active'
             WHERE committee_member_id = :id"
        );
        $stmt->execute([':role' => $memberRole, ':date' => $assignedDate, ':id' => $existingRow['committee_member_id']]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO committee_members (committee_id, user_id, member_role, assigned_date, status, created_at)
             VALUES (:cid, :uid, :role, :date, 'Active', NOW())"
        );
        $stmt->execute([':cid' => $committeeId, ':uid' => $userId, ':role' => $memberRole, ':date' => $assignedDate]);
    }

    logActivity(currentUserId(), 'Insert', $user['full_name'] . ' assigned to committee "' . $committee['committee_name'] . '" as ' . $memberRole . '.');
    jsonResponse(true, $user['full_name'] . ' has been assigned to the committee.');

} catch (PDOException $e) {
    error_log('Committee member save error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while assigning the member.');
}
