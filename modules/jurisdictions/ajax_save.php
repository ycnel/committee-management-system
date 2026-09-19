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
$scopeDefinition = clean($_POST['scope_definition'] ?? '');
$coveredAreas = clean($_POST['covered_areas'] ?? '');
$primaryResponsibilities = clean($_POST['primary_responsibilities'] ?? '');
$typicalLegislativeMatters = clean($_POST['typical_legislative_matters'] ?? '');
$outsideScope = clean($_POST['outside_scope'] ?? '');
$notes = clean($_POST['notes'] ?? '');
$status  = clean($_POST['status'] ?? 'Active');

$errors = [];
if ($name === '') $errors[] = 'Jurisdiction name is required.';
if (mb_strlen($name) > 150) $errors[] = 'Jurisdiction name is too long.';
if (mb_strlen($category) > 100) $errors[] = 'Category is too long.';
foreach ([
    'Description' => $description,
    'Scope definition' => $scopeDefinition,
    'Covered areas' => $coveredAreas,
    'Primary responsibilities' => $primaryResponsibilities,
    'Typical legislative matters' => $typicalLegislativeMatters,
    'Outside scope' => $outsideScope,
    'Notes' => $notes,
] as $label => $value) {
    if (mb_strlen($value) > 10000) $errors[] = $label . ' is too long.';
}
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
            'UPDATE jurisdictions SET jurisdiction_name = :name, category = :cat, description = :desc,
             scope_definition = :scope_definition, covered_areas = :covered_areas,
             primary_responsibilities = :primary_responsibilities,
             typical_legislative_matters = :typical_legislative_matters,
             outside_scope = :outside_scope, notes = :notes, status = :status
             WHERE jurisdiction_id = :id'
        );
        $stmt->execute([
            ':name' => $name, ':cat' => $category, ':desc' => $description,
            ':scope_definition' => $scopeDefinition, ':covered_areas' => $coveredAreas,
            ':primary_responsibilities' => $primaryResponsibilities,
            ':typical_legislative_matters' => $typicalLegislativeMatters,
            ':outside_scope' => $outsideScope, ':notes' => $notes,
            ':status' => $status, ':id' => $id,
        ]);

        logActivity(currentUserId(), 'Update', 'Updated jurisdiction #' . $id . ' (' . $name . ')');
        jsonResponse(true, 'Jurisdiction updated successfully.', ['id' => $id]);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO jurisdictions
            (jurisdiction_name, category, description, scope_definition, covered_areas,
             primary_responsibilities, typical_legislative_matters, outside_scope,
             notes, status, created_by, created_at)
         VALUES
            (:name, :cat, :desc, :scope_definition, :covered_areas,
             :primary_responsibilities, :typical_legislative_matters, :outside_scope,
             :notes, :status, :by, NOW())'
    );
    $stmt->execute([
        ':name' => $name, ':cat' => $category, ':desc' => $description,
        ':scope_definition' => $scopeDefinition, ':covered_areas' => $coveredAreas,
        ':primary_responsibilities' => $primaryResponsibilities,
        ':typical_legislative_matters' => $typicalLegislativeMatters,
        ':outside_scope' => $outsideScope, ':notes' => $notes,
        ':status' => $status, ':by' => currentUserId(),
    ]);
    $id = (int)$pdo->lastInsertId();

    logActivity(currentUserId(), 'Insert', 'Created jurisdiction #' . $id . ' (' . $name . ')');
    jsonResponse(true, 'Jurisdiction created successfully.', ['id' => $id]);

} catch (PDOException $e) {
    error_log('Jurisdiction save error: ' . $e->getMessage());
    if ((int)$e->getCode() === 23000) {
        jsonResponse(false, 'A jurisdiction with this name already exists.');
    }
    jsonResponse(false, 'A database error occurred while saving the jurisdiction.');
}
