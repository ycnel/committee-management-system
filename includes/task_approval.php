<?php
/** Shared authorization, notification, and file helpers for task approvals. */

require_once __DIR__ . '/auth.php';

function taskApprovalChairCanReview(PDO $pdo, int $committeeId): bool
{
    if (isAdmin()) {
        return true;
    }
    if (currentRole() !== ROLE_STAFF || $committeeId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare(
        "SELECT 1
         FROM committee_members
         WHERE committee_id = :committee_id
           AND user_id = :user_id
           AND member_role = 'Chairperson'
           AND status = 'Active'
         LIMIT 1"
    );
    $stmt->execute([
        ':committee_id' => $committeeId,
        ':user_id' => currentUserId(),
    ]);
    return (bool)$stmt->fetchColumn();
}

function taskApprovalReviewRecipients(PDO $pdo, int $committeeId): array
{
    $stmt = $pdo->prepare(
        "SELECT id FROM users
         WHERE status = 'Active'
           AND role_id = (SELECT id FROM roles WHERE name = :admin_role LIMIT 1)
         UNION
         SELECT u.id
         FROM committee_members cm
         INNER JOIN users u ON u.id = cm.user_id
         INNER JOIN roles r ON r.id = u.role_id
         WHERE cm.committee_id = :committee_id
           AND cm.member_role = 'Chairperson'
           AND cm.status = 'Active'
           AND u.status = 'Active'
           AND r.name = :chair_role"
    );
    $stmt->execute([
        ':admin_role' => ROLE_ADMIN,
        ':committee_id' => $committeeId,
        ':chair_role' => ROLE_STAFF,
    ]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function recordTaskApprovalActivity(
    PDO $pdo,
    int $actorId,
    string $action,
    string $details,
    array $recipientIds,
    string $notification,
    string $url
): int {
    $activityId = logActivity($actorId, $action, $details);
    if (!$activityId) {
        throw new RuntimeException('The task approval activity could not be recorded.');
    }
    $insert = $pdo->prepare(
        'INSERT INTO notifications
            (recipient_user_id, activity_log_id, message, url, created_at)
         VALUES (:recipient_id, :activity_id, :message, :url, NOW())'
    );
    foreach (array_unique(array_map('intval', $recipientIds)) as $recipientId) {
        if ($recipientId <= 0) {
            continue;
        }
        $insert->execute([
            ':recipient_id' => $recipientId,
            ':activity_id' => $activityId,
            ':message' => $notification,
            ':url' => $url,
        ]);
    }
    return $activityId;
}

function storeTaskCompletionProof(array $file): array
{
    if (!isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        throw new DomainException('A proof or supporting file is required.');
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        $message = in_array((int)$file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'The proof file exceeds the server upload limit.'
            : 'The proof file could not be uploaded (error ' . (int)$file['error'] . ').';
        throw new DomainException($message);
    }

    $temporaryPath = (string)($file['tmp_name'] ?? '');
    $actualSize = is_file($temporaryPath) ? filesize($temporaryPath) : false;
    if (!is_uploaded_file($temporaryPath) || $actualSize === false || $actualSize <= 0) {
        throw new DomainException('The uploaded proof file is invalid.');
    }
    if ($actualSize > MAX_UPLOAD_SIZE) {
        throw new DomainException('Proof files must not exceed ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . ' MB.');
    }

    $originalFilename = basename(str_replace('\\', '/', (string)($file['name'] ?? '')));
    $originalFilename = preg_replace('/[\x00-\x1F\x7F]/', '', $originalFilename) ?? '';
    $originalFilename = trim(mb_substr($originalFilename, 0, 255));
    $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    $allowedMimesByExtension = [
        'jpg' => ['image/jpeg', 'image/pjpeg', 'image/jpg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg', 'image/jpg'],
        'png' => ['image/png'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/vnd.ms-office', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/vnd.ms-office', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
    ];
    if ($originalFilename === '' || !isset($allowedMimesByExtension[$extension])) {
        throw new DomainException('Unsupported proof file type. Use JPG, JPEG, PNG, PDF, DOC, DOCX, XLS, or XLSX.');
    }
    if (!function_exists('finfo_open')) {
        throw new RuntimeException('File content verification is unavailable on this server.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        throw new RuntimeException('File content verification could not be started.');
    }
    $mimeType = finfo_file($finfo, $temporaryPath);
    finfo_close($finfo);
    if (!is_string($mimeType) || !in_array($mimeType, $allowedMimesByExtension[$extension], true)) {
        throw new DomainException('The proof file content does not match its extension.');
    }

    if (!is_dir(TASK_COMPLETION_UPLOAD_DIR)
        && !mkdir(TASK_COMPLETION_UPLOAD_DIR, 0750, true)
        && !is_dir(TASK_COMPLETION_UPLOAD_DIR)) {
        throw new RuntimeException('The private proof storage directory could not be created.');
    }
    if (!is_writable(TASK_COMPLETION_UPLOAD_DIR)) {
        throw new RuntimeException('The private proof storage directory is not writable.');
    }

    $storedFilename = bin2hex(random_bytes(32)) . '.' . $extension;
    $destination = TASK_COMPLETION_UPLOAD_DIR . $storedFilename;
    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new RuntimeException('The uploaded proof could not be moved into private storage.');
    }

    return [
        'original_filename' => $originalFilename,
        'stored_filename' => $storedFilename,
        'file_path' => $storedFilename,
        'mime_type' => $mimeType,
        'file_size' => (int)$actualSize,
        'absolute_path' => $destination,
    ];
}

function deleteStoredTaskCompletionProof(?string $path): void
{
    if ($path !== null && is_file($path)) {
        if (!unlink($path)) {
            error_log('Could not remove orphaned task completion proof: ' . $path);
        }
    }
}
