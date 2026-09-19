<?php
/**
 * modules/committees/ajax_save.php
 * ------------------------------------------------------------------
 * Handles CREATE and UPDATE for the `committees` table (id=0 means
 * create). committee_name is unique (Committee Formation module).
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id             = (int)($_POST['id'] ?? 0);
$committeeName  = clean($_POST['committee_name'] ?? '');
$description    = clean($_POST['description'] ?? '');
$jurisdictionId = (int)($_POST['jurisdiction_id'] ?? 0) ?: null;
$dateCreated    = clean($_POST['date_created'] ?? '') ?: null;
$status         = clean($_POST['status'] ?? 'Active');

$allowedStatus = ['Active', 'Inactive', 'Dissolved'];

$errors = [];
if ($committeeName === '') $errors[] = 'Committee name is required.';
if (mb_strlen($committeeName) > 150) $errors[] = 'Committee name is too long.';
if (!in_array($status, $allowedStatus, true)) $errors[] = 'Invalid status value.';
if ($dateCreated !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateCreated)) $errors[] = 'Invalid date format.';
if (!empty($errors)) jsonResponse(false, implode(' ', $errors));

$pdo = db();

// Jurisdiction must exist if provided.
if ($jurisdictionId !== null) {
    $chk = $pdo->prepare('SELECT jurisdiction_id FROM jurisdictions WHERE jurisdiction_id = :id');
    $chk->execute([':id' => $jurisdictionId]);
    if (!$chk->fetch()) jsonResponse(false, 'Selected jurisdiction does not exist.');
}

try {
    // Uniqueness check (case-insensitive), excluding self on update.
    $dupStmt = $pdo->prepare('SELECT committee_id FROM committees WHERE LOWER(committee_name) = LOWER(:name) AND committee_id != :id');
    $dupStmt->execute([':name' => $committeeName, ':id' => $id]);
    if ($dupStmt->fetch()) jsonResponse(false, 'A committee with this name already exists.');

    if ($id > 0) {
        $before = $pdo->prepare('SELECT committee_id FROM committees WHERE committee_id = :id');
        $before->execute([':id' => $id]);
        if (!$before->fetch()) jsonResponse(false, 'Committee not found.');

        $stmt = $pdo->prepare(
            'UPDATE committees SET committee_name = :name, description = :desc, jurisdiction_id = :jid,
             date_created = :dc, status = :status WHERE committee_id = :id'
        );
        $stmt->execute([
            ':name' => $committeeName, ':desc' => $description, ':jid' => $jurisdictionId,
            ':dc' => $dateCreated, ':status' => $status, ':id' => $id,
        ]);

        logActivity(currentUserId(), 'Update', 'Updated committee #' . $id . ' (' . $committeeName . ')');
        jsonResponse(true, 'Committee updated successfully.', ['id' => $id]);
    }

    // ---- CREATE ----
    $stmt = $pdo->prepare(
        'INSERT INTO committees (committee_name, description, jurisdiction_id, date_created, status, created_by, created_at)
         VALUES (:name, :desc, :jid, :dc, :status, :by, NOW())'
    );
    $stmt->execute([
        ':name' => $committeeName, ':desc' => $description, ':jid' => $jurisdictionId,
        ':dc' => $dateCreated, ':status' => $status, ':by' => currentUserId(),
    ]);
    $id = (int)$pdo->lastInsertId();

    logActivity(currentUserId(), 'Insert', 'Created committee #' . $id . ' (' . $committeeName . ')');
    jsonResponse(true, 'Committee created successfully.', ['id' => $id]);

} catch (PDOException $e) {
    error_log('Committee save error: ' . $e->getMessage());
    if ((int)$e->getCode() === 23000) {
        jsonResponse(false, 'A committee with this name already exists.');
    }
    jsonResponse(false, 'A database error occurred while saving the committee.');
}
