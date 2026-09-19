<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid jurisdiction id.');

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM jurisdictions WHERE jurisdiction_id = :id');
$stmt->execute([':id' => $id]);
$jurisdiction = $stmt->fetch();

if (!$jurisdiction) jsonResponse(false, 'Jurisdiction not found.');

$committeeStmt = $pdo->prepare(
    'SELECT committee_id, committee_name, status
     FROM committees
     WHERE jurisdiction_id = :id
     ORDER BY committee_name'
);
$committeeStmt->execute([':id' => $id]);

jsonResponse(true, '', [
    'jurisdiction' => $jurisdiction,
    'committees'   => $committeeStmt->fetchAll(),
]);
