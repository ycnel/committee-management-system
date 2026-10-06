<?php
require_once __DIR__ . '/../../includes/task_approval.php';
requireLogin();

$fileId = (int)($_GET['id'] ?? 0);
if ($fileId <= 0) {
    http_response_code(400);
    exit('Invalid proof file.');
}

$stmt = db()->prepare(
    'SELECT f.*, r.submitted_by, COALESCE(r.committee_id, cm.committee_id) AS committee_id
     FROM task_completion_request_files f
     INNER JOIN task_completion_requests r ON r.id = f.request_id
     LEFT JOIN workload_assignments wa ON wa.workload_id = r.workload_id
     LEFT JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     WHERE f.id = :id
     LIMIT 1'
);
$stmt->execute([':id' => $fileId]);
$file = $stmt->fetch();
if (!$file) {
    http_response_code(404);
    exit('Proof file not found.');
}

$authorized = isAdmin()
    || (isCommitteeMember() && (int)$file['submitted_by'] === currentUserId())
    || (currentRole() === ROLE_STAFF
        && taskApprovalChairCanReview(db(), (int)$file['committee_id']));
if (!$authorized) {
    http_response_code(403);
    exit('You do not have permission to access this proof file.');
}

$storedFilename = (string)$file['stored_filename'];
if (!preg_match('/\A[a-f0-9]{64}\.(?:pdf|doc|docx|xls|xlsx|png|jpg|jpeg)\z/i', $storedFilename)) {
    http_response_code(404);
    exit('Proof file not found.');
}
$storageDirectory = realpath(TASK_COMPLETION_UPLOAD_DIR);
$storagePath = realpath(TASK_COMPLETION_UPLOAD_DIR . $storedFilename);
if ($storageDirectory === false
    || $storagePath === false
    || !is_file($storagePath)
    || !str_starts_with($storagePath, $storageDirectory . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit('Proof file not found.');
}

$downloadName = str_replace(["\r", "\n", '"'], '', (string)$file['original_filename']);
$safeAsciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName) ?: 'completion-proof';
$inline = in_array($file['mime_type'], ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'], true);
$forceDownload = ($_GET['download'] ?? '') === '1';
header('Content-Type: ' . $file['mime_type']);
header('Content-Length: ' . filesize($storagePath));
header('Content-Disposition: ' . ($inline && !$forceDownload ? 'inline' : 'attachment')
    . '; filename="' . $safeAsciiName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($storagePath);
