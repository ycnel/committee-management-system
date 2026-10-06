<?php
/**
 * Lists and processes Committee Chairperson requests for task templates.
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$isReviewer = isSystemAuthority();
$isChairperson = currentRole() === ROLE_STAFF;
if (!$isReviewer && !$isChairperson) {
    jsonResponse(false, 'You do not have permission to access task template requests.');
}

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filter = clean($_GET['status'] ?? 'Pending');
    $conditions = [];
    $params = [];
    if (!$isReviewer) {
        $conditions[] = 'r.requested_by = :requester_id';
        $params[':requester_id'] = currentUserId();
    } elseif ($filter !== 'all' && in_array($filter, ['Pending', 'Approved', 'Rejected'], true)) {
        $conditions[] = 'r.status = :status';
        $params[':status'] = $filter;
    }
    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $stmt = $pdo->prepare(
        "SELECT r.id, r.requested_by, r.jurisdiction_id, r.task_name, r.description,
                r.sequence_order, r.is_required, r.status, r.created_at, r.admin_response,
                requester.full_name AS requester_name,
                reviewer.full_name AS reviewer_name,
                j.jurisdiction_name
         FROM task_template_requests r
         LEFT JOIN users requester ON requester.id = r.requested_by
         LEFT JOIN users reviewer ON reviewer.id = r.reviewed_by
         LEFT JOIN jurisdictions j ON j.jurisdiction_id = r.jurisdiction_id
         $where
         ORDER BY CASE WHEN r.status = 'Pending' THEN 0 ELSE 1 END, r.created_at DESC"
    );
    $stmt->execute($params);
    jsonResponse(true, '', ['requests' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}
requireCsrf();
$action = clean($_POST['action'] ?? '');

if ($action === 'create') {
    if (!$isChairperson) jsonResponse(false, 'Only a Committee Chairperson can submit a template request.');

    $taskName = clean($_POST['task_name'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $jurisdictionId = (int)($_POST['jurisdiction_id'] ?? 0);
    $sequenceOrder = filter_var($_POST['sequence_order'] ?? '0', FILTER_VALIDATE_INT);
    $isRequired = (int)($_POST['is_required'] ?? 0) === 1 ? 1 : 0;

    if ($taskName === '') jsonResponse(false, 'Task name is required.');
    if ($sequenceOrder === false || $sequenceOrder < 0) {
        jsonResponse(false, 'Sequence order must be zero or greater.');
    }
    if ($jurisdictionId > 0) {
        $jurisdictionCheck = $pdo->prepare('SELECT 1 FROM jurisdictions WHERE jurisdiction_id = :id');
        $jurisdictionCheck->execute([':id' => $jurisdictionId]);
        if (!$jurisdictionCheck->fetchColumn()) jsonResponse(false, 'Selected jurisdiction was not found.');
    } else {
        $jurisdictionId = 0;
    }

    $duplicateTemplate = $pdo->prepare(
        'SELECT 1 FROM task_templates
         WHERE jurisdiction_id <=> :jurisdiction_id AND task_name = :task_name
         LIMIT 1'
    );
    $duplicateTemplate->execute([
        ':jurisdiction_id' => $jurisdictionId ?: null,
        ':task_name' => $taskName,
    ]);
    if ($duplicateTemplate->fetchColumn()) {
        jsonResponse(false, 'A standard task with this name already exists for the selected scope.');
    }

    $duplicateRequest = $pdo->prepare(
        "SELECT 1 FROM task_template_requests
         WHERE jurisdiction_id <=> :jurisdiction_id AND task_name = :task_name AND status = 'Pending'
         LIMIT 1"
    );
    $duplicateRequest->execute([
        ':jurisdiction_id' => $jurisdictionId ?: null,
        ':task_name' => $taskName,
    ]);
    if ($duplicateRequest->fetchColumn()) {
        jsonResponse(false, 'A request for this task name and scope is already awaiting review.');
    }

    $insert = $pdo->prepare(
        'INSERT INTO task_template_requests
            (requested_by, jurisdiction_id, task_name, description, sequence_order, is_required)
         VALUES (:requested_by, :jurisdiction_id, :task_name, :description, :sequence_order, :is_required)'
    );
    $insert->execute([
        ':requested_by' => currentUserId(),
        ':jurisdiction_id' => $jurisdictionId ?: null,
        ':task_name' => $taskName,
        ':description' => $description !== '' ? $description : null,
        ':sequence_order' => $sequenceOrder,
        ':is_required' => $isRequired,
    ]);
    jsonResponse(true, 'Template request submitted for Administrator review.', [
        'request_id' => (int)$pdo->lastInsertId(),
    ]);
}

if (!$isReviewer) {
    jsonResponse(false, 'Only an Administrator can review template requests.');
}
if (!in_array($action, ['approve', 'reject'], true)) {
    jsonResponse(false, 'Invalid template request action.');
}

$requestId = (int)($_POST['id'] ?? 0);
if ($requestId <= 0) jsonResponse(false, 'A valid template request is required.');

try {
    $pdo->beginTransaction();
    $requestStmt = $pdo->prepare(
        "SELECT * FROM task_template_requests WHERE id = :id AND status = 'Pending' FOR UPDATE"
    );
    $requestStmt->execute([':id' => $requestId]);
    $request = $requestStmt->fetch();
    if (!$request) {
        $pdo->rollBack();
        jsonResponse(false, 'This template request was not found or has already been reviewed.');
    }

    if ($action === 'approve') {
        if ($request['jurisdiction_id'] !== null) {
            $scopeCheck = $pdo->prepare('SELECT 1 FROM jurisdictions WHERE jurisdiction_id = :id');
            $scopeCheck->execute([':id' => (int)$request['jurisdiction_id']]);
            if (!$scopeCheck->fetchColumn()) {
                $pdo->rollBack();
                jsonResponse(false, 'The requested jurisdiction no longer exists. Reject this request and ask the Chairperson to submit it again.');
            }
        }

        $duplicate = $pdo->prepare(
            'SELECT 1 FROM task_templates
             WHERE jurisdiction_id <=> :jurisdiction_id AND task_name = :task_name
             LIMIT 1'
        );
        $duplicate->execute([
            ':jurisdiction_id' => $request['jurisdiction_id'],
            ':task_name' => $request['task_name'],
        ]);
        if ($duplicate->fetchColumn()) {
            $pdo->rollBack();
            jsonResponse(false, 'A standard task with this name already exists for the requested scope.');
        }

        $insertTemplate = $pdo->prepare(
            'INSERT INTO task_templates
                (jurisdiction_id, task_name, description, task_type, sequence_order, is_required, is_active)
             VALUES (:jurisdiction_id, :task_name, :description, :task_type, :sequence_order, :is_required, 1)'
        );
        $insertTemplate->execute([
            ':jurisdiction_id' => $request['jurisdiction_id'],
            ':task_name' => $request['task_name'],
            ':description' => $request['description'],
            ':task_type' => $request['jurisdiction_id'] === null ? 'Core' : 'Jurisdiction',
            ':sequence_order' => $request['sequence_order'],
            ':is_required' => $request['is_required'],
        ]);
    }

    $update = $pdo->prepare(
        'UPDATE task_template_requests
         SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW(),
             admin_response = :response
         WHERE id = :id AND status = "Pending"'
    );
    $update->execute([
        ':status' => $action === 'approve' ? 'Approved' : 'Rejected',
        ':reviewed_by' => currentUserId(),
        ':response' => clean($_POST['admin_response'] ?? '') ?: null,
        ':id' => $requestId,
    ]);
    if ($update->rowCount() !== 1) {
        throw new RuntimeException('This template request has already been reviewed.');
    }
    $pdo->commit();
    jsonResponse(true, $action === 'approve'
        ? 'Template request approved and added to Standard Task Templates.'
        : 'Template request rejected.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e instanceof PDOException) error_log('Task template request review failed: ' . $e->getMessage());
    jsonResponse(false, $e instanceof RuntimeException
        ? $e->getMessage()
        : 'A database error occurred while reviewing this template request.');
}
