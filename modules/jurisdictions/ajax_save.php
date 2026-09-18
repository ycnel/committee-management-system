<?php
/**
 * modules/jurisdictions/ajax_save.php
 * ------------------------------------------------------------------
 * Handles CREATE and UPDATE for the `jurisdictions` table.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole([ROLE_ADMIN, ROLE_STAFF]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id      = (int)($_POST['id'] ?? 0);
$name    = clean($_POST['jurisdiction_name'] ?? '');
$category = clean($_POST['category'] ?? '');
$description = clean($_POST['description'] ?? '');
$status  = clean($_POST['status'] ?? 'Active');

$errors = [];
if ($name === '') $errors[] = 'Jurisdiction name is required.';
if (mb_strlen($name) > 150) $errors[] = 'Jurisdiction name is too long.';
if (!in_array($status, ['Active', 'Inactive'], true)) $errors[] = 'Invalid status value.';
if (!empty($errors)) jsonResponse(false, implode(' ', $errors));

$pdo = db();

try {
    $dupStmt = $pdo->prepare('SELECT jurisdiction_id FROM jurisdictions WHERE LOWER(jurisdiction_name) = LOWER(:name) AND jurisdiction_id != :id');
    $dupStmt->execute([':name' => $name, ':id' => $id]);
    if ($dupStmt->fetch()) jsonResponse(false, 'A jurisdiction with this name already exists.');

    if ($id > 0) {
        $before = $pdo->prepare('SELECT jurisdiction_id FROM jurisdictions WHERE jurisdiction_id = :id');
        $before->execute([':id' => $id]);
        if (!$before->fetch()) jsonResponse(false, 'Jurisdiction not found.');

        $stmt = $pdo->prepare(
            'UPDATE jurisdictions SET jurisdiction_name = :name, category = :cat, description = :desc, status = :status
             WHERE jurisdiction_id = :id'
        );
        $stmt->execute([':name' => $name, ':cat' => $category, ':desc' => $description, ':status' => $status, ':id' => $id]);

        logActivity(currentUserId(), 'Update', 'Updated jurisdiction #' . $id . ' (' . $name . ')');
        jsonResponse(true, 'Jurisdiction updated successfully.', ['id' => $id]);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO jurisdictions (jurisdiction_name, category, description, status, created_by, created_at)
         VALUES (:name, :cat, :desc, :status, :by, NOW())'
    );
    $stmt->execute([':name' => $name, ':cat' => $category, ':desc' => $description, ':status' => $status, ':by' => currentUserId()]);
    $id = (int)$pdo->lastInsertId();

    logActivity(currentUserId(), 'Insert', 'Created jurisdiction #' . $id . ' (' . $name . ')');
    jsonResponse(true, 'Jurisdiction created successfully.', ['id' => $id]);

} catch (PDOException $e) {
    error_log('Jurisdiction save error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while saving the jurisdiction.');
}
