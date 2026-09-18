<?php
/**
 * modules/committees/ajax_member_role.php
 * ------------------------------------------------------------------
 * Updates a committee member's role (Member Assignment module ->
 * "Update Assignment"), used by the inline role dropdown in the
 * member roster table.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id   = (int)($_POST['id'] ?? 0);
$role = clean($_POST['member_role'] ?? '');
$allowedRoles = ['Chairperson', 'Vice Chairperson', 'Member'];

if ($id <= 0) jsonResponse(false, 'Invalid assignment id.');
if (!in_array($role, $allowedRoles, true)) jsonResponse(false, 'Invalid committee role.');

$pdo = db();
try {
    $stmt = $pdo->prepare(
        'SELECT cm.committee_id, u.full_name, c.committee_name
         FROM committee_members cm
         INNER JOIN users u ON u.id = cm.user_id
         INNER JOIN committees c ON c.committee_id = cm.committee_id
         WHERE cm.committee_member_id = :id'
    );
    $stmt->execute([':id' => $id]);
    $member = $stmt->fetch();
    if (!$member) jsonResponse(false, 'Assignment not found.');

    if ($role === 'Chairperson') {
        $chairStmt = $pdo->prepare(
            "SELECT committee_member_id FROM committee_members
             WHERE committee_id = :cid AND member_role = 'Chairperson' AND status = 'Active' AND committee_member_id != :id"
        );
        $chairStmt->execute([':cid' => $member['committee_id'], ':id' => $id]);
        if ($chairStmt->fetch()) jsonResponse(false, 'This committee already has a Chairperson. Change their role first.');
    }

    $upd = $pdo->prepare('UPDATE committee_members SET member_role = :role WHERE committee_member_id = :id');
    $upd->execute([':role' => $role, ':id' => $id]);

    logActivity(currentUserId(), 'Update', $member['full_name'] . '\'s role in "' . $member['committee_name'] . '" changed to ' . $role . '.');
    jsonResponse(true, 'Role updated successfully.');

} catch (PDOException $e) {
    error_log('Committee member role update error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while updating the role.');
}
