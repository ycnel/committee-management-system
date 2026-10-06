<?php
/**
 * modules/jurisdictions/ajax_task_templates.php
 * ------------------------------------------------------------------
 * Lists and manages reusable standard task templates.
 * Template changes are restricted to system administrators.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isSystemAuthority() && !canManage()) {
        jsonResponse(false, 'You do not have permission to view task templates.');
    }

    $filter = clean($_GET['jurisdiction_id'] ?? '');
    $where = '';
    $params = [];
    if ($filter === 'core') {
        $where = 'WHERE tt.jurisdiction_id IS NULL';
    } elseif ($filter !== '' && ctype_digit($filter) && (int)$filter > 0) {
        $where = 'WHERE (tt.jurisdiction_id IS NULL OR tt.jurisdiction_id = :jurisdiction_id)';
        $params[':jurisdiction_id'] = (int)$filter;
    }

    $stmt = $pdo->prepare(
        "SELECT tt.id, tt.jurisdiction_id, tt.task_name, tt.description, tt.proof_requirement, tt.task_type,
                tt.sequence_order, tt.is_required, tt.is_active, j.jurisdiction_name
         FROM task_templates tt
         LEFT JOIN jurisdictions j ON j.jurisdiction_id = tt.jurisdiction_id
         $where
         ORDER BY CASE WHEN tt.jurisdiction_id IS NULL THEN 0 ELSE 1 END,
                  COALESCE(j.jurisdiction_name, ''), tt.sequence_order, tt.task_name"
    );
    $stmt->execute($params);
    jsonResponse(true, '', ['templates' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}
if (!isSystemAuthority()) jsonResponse(false, 'Only a system administrator can manage task templates.');
requireCsrf();

$action = clean($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);

if ($action === 'toggle') {
    if ($id <= 0) jsonResponse(false, 'Invalid task template.');
    $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
    $stmt = $pdo->prepare('UPDATE task_templates SET is_active = :is_active WHERE id = :id');
    $stmt->execute([':is_active' => $isActive, ':id' => $id]);
    if ($stmt->rowCount() === 0) {
        $exists = $pdo->prepare('SELECT 1 FROM task_templates WHERE id = :id');
        $exists->execute([':id' => $id]);
        if (!$exists->fetchColumn()) jsonResponse(false, 'Task template not found.');
    }
    jsonResponse(true, 'Task template status updated.');
}

if ($action !== 'save') {
    jsonResponse(false, 'Invalid task template action.');
}

$taskName = clean($_POST['task_name'] ?? '');
$description = clean($_POST['description'] ?? '');
$proofRequirement = clean($_POST['proof_requirement'] ?? '');
$jurisdictionId = (int)($_POST['jurisdiction_id'] ?? 0);
$sequenceOrder = filter_var($_POST['sequence_order'] ?? '0', FILTER_VALIDATE_INT);
$isRequired = (int)($_POST['is_required'] ?? 0) === 1 ? 1 : 0;
$isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;

if ($taskName === '') jsonResponse(false, 'Task name is required.');
if (mb_strlen($proofRequirement) > 2000) jsonResponse(false, 'Completion proof guidance must not exceed 2,000 characters.');
if ($sequenceOrder === false || $sequenceOrder < 0) jsonResponse(false, 'Sequence order must be zero or greater.');
if ($jurisdictionId > 0) {
    $jurisdictionCheck = $pdo->prepare('SELECT 1 FROM jurisdictions WHERE jurisdiction_id = :id');
    $jurisdictionCheck->execute([':id' => $jurisdictionId]);
    if (!$jurisdictionCheck->fetchColumn()) jsonResponse(false, 'Selected jurisdiction was not found.');
} else {
    $jurisdictionId = 0;
}

$duplicateCheck = $pdo->prepare(
    'SELECT 1 FROM task_templates
     WHERE jurisdiction_id <=> :jurisdiction_id
       AND task_name = :task_name
       AND id <> :id
     LIMIT 1'
);
$duplicateCheck->execute([
    ':jurisdiction_id' => $jurisdictionId ?: null,
    ':task_name' => $taskName,
    ':id' => $id,
]);
if ($duplicateCheck->fetchColumn()) {
    jsonResponse(false, 'A template with this name already exists for the selected scope.');
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE task_templates
             SET jurisdiction_id = :jurisdiction_id, task_name = :task_name,
                 description = :description, proof_requirement = :proof_requirement, task_type = :task_type,
                 sequence_order = :sequence_order, is_required = :is_required,
                 is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute([
            ':jurisdiction_id' => $jurisdictionId ?: null,
            ':task_name' => $taskName,
            ':description' => $description !== '' ? $description : null,
            ':proof_requirement' => $proofRequirement !== '' ? $proofRequirement : null,
            ':task_type' => $jurisdictionId > 0 ? 'Jurisdiction' : 'Core',
            ':sequence_order' => $sequenceOrder,
            ':is_required' => $isRequired,
            ':is_active' => $isActive,
            ':id' => $id,
        ]);
        jsonResponse(true, 'Task template updated.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO task_templates
            (jurisdiction_id, task_name, description, proof_requirement, task_type, sequence_order, is_required, is_active)
         VALUES (:jurisdiction_id, :task_name, :description, :proof_requirement, :task_type, :sequence_order, :is_required, :is_active)'
    );
    $stmt->execute([
        ':jurisdiction_id' => $jurisdictionId ?: null,
        ':task_name' => $taskName,
        ':description' => $description !== '' ? $description : null,
        ':proof_requirement' => $proofRequirement !== '' ? $proofRequirement : null,
        ':task_type' => $jurisdictionId > 0 ? 'Jurisdiction' : 'Core',
        ':sequence_order' => $sequenceOrder,
        ':is_required' => $isRequired,
        ':is_active' => $isActive,
    ]);
    jsonResponse(true, 'Task template added.', ['id' => (int)$pdo->lastInsertId()]);
} catch (PDOException $e) {
    error_log('Task template save error: ' . $e->getMessage());
    if ((int)$e->getCode() === 23000) {
        jsonResponse(false, 'A template with this name already exists for the selected scope.');
    }
    jsonResponse(false, 'A database error occurred while saving the task template.');
}
