<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole([ROLE_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid user id.');
if ($id === currentUserId()) jsonResponse(false, 'You cannot delete your own account while logged in.');

$pdo = db();
try {
    $stmt = $pdo->prepare('SELECT full_name FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    if (!$user) jsonResponse(false, 'User not found.');

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
