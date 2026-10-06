<?php
/**
 * modules/committees/ajax_member_group.php
 * ------------------------------------------------------------------
 * Updates a committee member's political group classification.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id = (int)($_POST['id'] ?? 0);
$politicalGroup = clean($_POST['political_group'] ?? '');
$allowedGroups = ['', 'Majority', 'Minority'];

if ($id <= 0) jsonResponse(false, 'Invalid assignment id.');
if (!in_array($politicalGroup, $allowedGroups, true)) jsonResponse(false, 'Invalid political group.');

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

    $value = $politicalGroup !== '' ? $politicalGroup : null;
    $upd = $pdo->prepare('UPDATE committee_members SET political_group = :group WHERE committee_member_id = :id');
    $upd->execute([':group' => $value, ':id' => $id]);

    $activityText = $politicalGroup !== ''
        ? $member['full_name'] . '\'s political group in "' . $member['committee_name'] . '" changed to ' . $politicalGroup . '.'
        : $member['full_name'] . '\'s political group in "' . $member['committee_name'] . '" was cleared.';

    $activityId = logActivity(currentUserId(), 'Update', $activityText);
    createNotification(
        (int)$member['user_id'],
        'Your political group in committee "' . $member['committee_name'] . '" was updated.',
        APP_URL . '/modules/committees/view.php?id=' . $member['committee_id'],
        $activityId
    );
    jsonResponse(true, 'Political group updated successfully.');

} catch (PDOException $e) {
    error_log('Committee member political group update error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while updating the political group.');
}
