<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_SUPER_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid user id.');
if ($id === currentUserId()) jsonResponse(false, 'You cannot delete your own account while logged in.');

$pdo = db();
try {
    $stmt = $pdo->prepare('SELECT full_name, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    if (!$user) jsonResponse(false, 'User not found.');

    // Same administrator-account guard as ajax_user_save.php: a plain
    // Administrator cannot delete an Administrator or Super Admin account.
    if (!canManageAdminAccounts() && in_array($user['role_name'], [ROLE_ADMIN, ROLE_SUPER_ADMIN], true)) {
        jsonResponse(false, 'Only a Super Admin can delete an administrator-level account.');
    }

    $del = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $del->execute([':id' => $id]);

    logActivity(currentUserId(), 'Delete', 'Deleted user #' . $id . ' (' . $user['full_name'] . ')');
    jsonResponse(true, 'User deleted successfully.');

} catch (PDOException $e) {
    error_log('User delete error: ' . $e->getMessage());
    if ((int)$e->getCode() === 23000 || str_contains($e->getMessage(), 'foreign key')) {
        jsonResponse(false, 'This user cannot be deleted because they still have linked records (e.g. committee assignments). Set them to Inactive instead, or remove those assignments first.');
    }
    jsonResponse(false, 'A database error occurred while deleting the user.');
}
