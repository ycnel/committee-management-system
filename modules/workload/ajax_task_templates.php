<?php
/**
 * Returns active core and jurisdiction-specific task templates.
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
session_write_close();

$jurisdictionId = (int)($_GET['jurisdiction_id'] ?? 0);
if ($jurisdictionId <= 0) jsonResponse(false, 'Select a valid jurisdiction first.');

$pdo = db();
$jurisdictionCheck = $pdo->prepare(
    "SELECT 1 FROM jurisdictions WHERE jurisdiction_id = :id AND status = 'Active'"
);
$jurisdictionCheck->execute([':id' => $jurisdictionId]);
if (!$jurisdictionCheck->fetchColumn()) jsonResponse(false, 'Selected jurisdiction is not active.');

$stmt = $pdo->prepare(
    "SELECT id, jurisdiction_id, task_name, description, task_type,
            sequence_order, is_required
     FROM task_templates
     WHERE is_active = 1
       AND (jurisdiction_id IS NULL OR jurisdiction_id = :jurisdiction_id)
     ORDER BY CASE WHEN jurisdiction_id IS NULL THEN 0 ELSE 1 END,
              sequence_order, task_name"
);
$stmt->execute([':jurisdiction_id' => $jurisdictionId]);
jsonResponse(true, '', ['templates' => $stmt->fetchAll()]);
