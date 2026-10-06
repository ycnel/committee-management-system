<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/report_drafts.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id = (int)($_POST['id'] ?? 0);
$action = clean((string)($_POST['action'] ?? ''));
$comment = trim(clean((string)($_POST['comment'] ?? '')));
if ($id <= 0) jsonResponse(false, 'A report is required.');
if (!in_array($action, ['submit', 'review', 'return', 'approve', 'finalize', 'archive'], true)) {
    jsonResponse(false, 'Invalid report action.');
}
if (mb_strlen($comment) > 5000) jsonResponse(false, 'The review comment must not exceed 5,000 characters.');

$isApprover = hasRole([ROLE_ADMIN, ROLE_PRO_TEMPORE]);
if (in_array($action, ['review', 'return', 'approve', 'finalize'], true) && !$isApprover) {
    jsonResponse(false, 'Only an Administrator or Pro Tempore may review or approve a report.');
}
if ($action === 'return' && $comment === '') jsonResponse(false, 'Provide a reason when returning a report for revision.');

$pdo = db();
try {
    $pdo->beginTransaction();
    $lock = $pdo->prepare('SELECT * FROM committee_report_drafts WHERE draft_id = :id FOR UPDATE');
    $lock->execute([':id' => $id]);
    $draft = $lock->fetch();
    if (!$draft) {
        $pdo->rollBack();
        jsonResponse(false, 'Report not found.');
    }
    if (in_array($action, ['submit', 'archive'], true)
        && !canEditReportDraft($draft, (int)currentUserId())) {
        $pdo->rollBack();
        jsonResponse(false, 'You do not have permission to perform this action.');
    }

    $previousStatus = $draft['status'];
    $newStatus = null;
    $notificationMessage = null;
    $notificationUrl = APP_URL . '/modules/committee_reports/draft_edit.php?id=' . $id;

    if ($action === 'submit') {
        if (!in_array($previousStatus, ['Draft', 'AI-Assisted Draft', 'Returned for Revision', 'AI Draft', 'Revised'], true)) {
            $pdo->rollBack();
            jsonResponse(false, 'Only a draft or returned report can be submitted for review.');
        }
        if (trim((string)$draft['subject_title']) === '' || trim((string)$draft['matter_referred']) === '') {
            $pdo->rollBack();
            jsonResponse(false, 'Enter the subject/title and matter referred before submitting the report.');
        }
        $newStatus = 'For Review';
        $notificationMessage = ($draft['report_number'] ?: 'Committee Report #' . $id) . ' has been submitted for review.';
    } elseif ($action === 'review') {
        if ($previousStatus !== 'For Review') {
            $pdo->rollBack();
            jsonResponse(false, 'Only a report submitted for review can be opened for review.');
        }
        $newStatus = 'Under Review';
    } elseif ($action === 'return') {
        if (!in_array($previousStatus, ['For Review', 'Under Review'], true)) {
            $pdo->rollBack();
            jsonResponse(false, 'Only a report under review can be returned.');
        }
        $newStatus = 'Returned for Revision';
        $notificationMessage = ($draft['report_number'] ?: 'Committee Report #' . $id)
            . ' was returned for revision: ' . $comment;
    } elseif ($action === 'approve') {
        if ($previousStatus !== 'Under Review') {
            $pdo->rollBack();
            jsonResponse(false, 'Only a report under review can be approved.');
        }
        if ((int)$draft['created_by'] === (int)currentUserId()) {
            $pdo->rollBack();
            jsonResponse(false, 'You cannot approve a report you created.');
        }
        if (!in_array(trim((string)$draft['recommendation_type']), REPORT_RECOMMENDATION_TYPES, true)
            || trim((string)$draft['recommendations']) === '') {
            $pdo->rollBack();
            jsonResponse(false, 'An authorized human must select and state the final Committee recommendation before approval.');
        }
        $newStatus = 'Approved';
        $notificationMessage = ($draft['report_number'] ?: 'Committee Report #' . $id) . ' has been approved.';
    } elseif ($action === 'finalize') {
        if ($previousStatus !== 'Approved') {
            $pdo->rollBack();
            jsonResponse(false, 'Only an approved report can be finalized.');
        }
        if ((int)$draft['created_by'] === (int)currentUserId()) {
            $pdo->rollBack();
            jsonResponse(false, 'You cannot finalize a report you created.');
        }
        $finalizationErrors = [];
        $recommendationType = trim((string)($draft['recommendation_type'] ?? ''));
        $recommendationText = trim((string)($draft['recommendations'] ?? ''));
        if (!in_array($recommendationType, REPORT_RECOMMENDATION_TYPES, true) || $recommendationText === ''
            || stripos($recommendationText, 'Pending Committee Action') !== false) {
            $finalizationErrors[] = 'An authorized human must select and record the final Committee recommendation.';
        }
        if (trim((string)($draft['committee_action'] ?? '')) === '') {
            $finalizationErrors[] = 'Record the Committee action actually taken; do not substitute a proposed recommendation.';
        }
        $actionDate = (string)($draft['committee_action_date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $actionDate)
            || !checkdate((int)substr($actionDate, 5, 2), (int)substr($actionDate, 8, 2), (int)substr($actionDate, 0, 4))) {
            $finalizationErrors[] = 'Record a valid date for the Committee action.';
        }
        $signatureDetails = trim((string)($draft['signature_details'] ?? ''));
        $committeeId = (int)($draft['committee_id'] ?? 0);
        if ($committeeId > 0) {
            $memberStmt = $pdo->prepare(
                "SELECT u.full_name
                 FROM committee_members cm
                 INNER JOIN users u ON u.id = cm.user_id
                 WHERE cm.committee_id = :committee_id
                   AND cm.status = 'Active' AND u.status = 'Active'
                 ORDER BY FIELD(cm.member_role, 'Chairperson', 'Vice Chairperson', 'Member'), u.full_name"
            );
            $memberStmt->execute([':committee_id' => $committeeId]);
            $memberNames = $memberStmt->fetchAll(PDO::FETCH_COLUMN);
            $lines = preg_split('/\r\n|\r|\n/', $signatureDetails) ?: [];
            $allowedStatuses = '/\b(Concurred|Dissented|Abstained|Not Present)\b/i';
            $missingStatuses = [];
            foreach ($memberNames as $memberName) {
                $recorded = false;
                foreach ($lines as $line) {
                    if (stripos($line, (string)$memberName) === false) continue;
                    if (preg_match($allowedStatuses, $line)
                        && stripos($line, 'signature') !== false
                        && preg_match('/\b\d{4}-\d{2}-\d{2}\b/', $line)) {
                        $recorded = true;
                        break;
                    }
                }
                if (!$recorded) $missingStatuses[] = (string)$memberName;
            }
            if (!$memberNames) {
                $finalizationErrors[] = 'CMAS has no active Committee-member roster to verify explicit concurrence records.';
            } elseif ($missingStatuses) {
                $finalizationErrors[] = 'Record a separate status, signature record, and date for each active Committee member: '
                    . implode(', ', $missingStatuses) . '.';
            }
        } else {
            $finalizationErrors[] = 'A Committee must be selected before the report can be finalized.';
        }
        if ($finalizationErrors) {
            $pdo->rollBack();
            jsonResponse(false, implode(' ', $finalizationErrors));
        }
        $insert = $pdo->prepare(
            'INSERT INTO committee_reports (committee_id, generated_by, report_title, report_type, generated_at)
             VALUES (:committee_id, :user_id, :title, :type, NOW())'
        );
        $insert->execute([
            ':committee_id' => $draft['committee_id'],
            ':user_id' => currentUserId(),
            ':title' => $draft['report_title'],
            ':type' => $draft['report_type'],
        ]);
        $reportId = (int)$pdo->lastInsertId();
        $update = $pdo->prepare(
            'UPDATE committee_report_drafts
             SET status = "Final", finalized_by = :user_id, finalized_at = NOW(), report_id = :report_id
             WHERE draft_id = :id AND status = "Approved"'
        );
        $update->execute([':user_id' => currentUserId(), ':report_id' => $reportId, ':id' => $id]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('The report changed while it was being finalized.');
        }
        logReportDraftHistory($pdo, $id, 'Finalized', (int)currentUserId(), $previousStatus, 'Final', $comment ?: null);
        $activityId = logActivity((int)currentUserId(), 'Finalize Committee Report', 'Finalized report ' . $draft['report_number']);
        $pdo->commit();
        jsonResponse(true, 'Committee report finalized.', ['report_id' => $reportId]);
    } else {
        if (!in_array($previousStatus, ['Draft', 'AI-Assisted Draft', 'Returned for Revision', 'AI Draft', 'Revised', 'Final'], true)) {
            $pdo->rollBack();
            jsonResponse(false, 'This report cannot be archived in its current status.');
        }
        $newStatus = 'Archived';
    }

    $setApproval = $action === 'approve'
        ? ', approved_by = :approver_id, approved_at = NOW()'
        : '';
    $setReview = $action === 'review'
        ? ', reviewed_by = :reviewer_id, reviewed_at = NOW()'
        : '';
    $setReturnReason = $action === 'return' ? ', returned_reason = :reason' : '';
    $clearReturnReason = $action === 'submit' ? ', returned_reason = NULL' : '';
    $update = $pdo->prepare(
        'UPDATE committee_report_drafts SET status = :new_status, updated_at = NOW()'
        . $setApproval . $setReview . $setReturnReason . $clearReturnReason . ' WHERE draft_id = :id'
    );
    $params = [':new_status' => $newStatus, ':id' => $id];
    if ($action === 'approve') $params[':approver_id'] = currentUserId();
    if ($action === 'review') $params[':reviewer_id'] = currentUserId();
    if ($action === 'return') $params[':reason'] = $comment;
    $update->execute($params);

    logReportDraftHistory($pdo, $id, ucfirst($action), (int)currentUserId(), $previousStatus, $newStatus, $comment ?: null);
    $activityId = logActivity(
        (int)currentUserId(),
        ucfirst($action) . ' Committee Report',
        ucfirst($action) . ' report ' . ($draft['report_number'] ?: '#' . $id)
            . ' (' . $previousStatus . ' -> ' . $newStatus . ')'
    );

    if ($action === 'submit') {
        $reviewerStmt = $pdo->prepare(
            "SELECT u.id
             FROM users u INNER JOIN roles r ON r.id = u.role_id
             WHERE r.name IN (:admin_role, :pro_tempore_role) AND u.status = 'Active'"
        );
        $reviewerStmt->execute([
            ':admin_role' => ROLE_ADMIN,
            ':pro_tempore_role' => ROLE_PRO_TEMPORE,
        ]);
        $recipientIds = array_map('intval', $reviewerStmt->fetchAll(PDO::FETCH_COLUMN));
        if (!$recipientIds) throw new RuntimeException('No active Administrator or Pro Tempore account can review this report.');
    } else {
        $recipientIds = [(int)$draft['created_by']];
    }

    if ($notificationMessage !== null) {
        foreach (array_unique($recipientIds) as $recipientId) {
            if ($recipientId <= 0 || $recipientId === (int)currentUserId()) continue;
            createNotification($recipientId, $notificationMessage, $notificationUrl, $activityId);
            $check = $pdo->prepare(
                'SELECT 1 FROM notifications
                 WHERE recipient_user_id = :recipient AND activity_log_id <=> :activity_id
                   AND url = :url LIMIT 1'
            );
            $check->execute([
                ':recipient' => $recipientId,
                ':activity_id' => $activityId,
                ':url' => $notificationUrl,
            ]);
            if (!$check->fetchColumn()) {
                throw new RuntimeException('The report notification could not be created.');
            }
        }
    }

    $pdo->commit();
    $messages = [
        'submit' => 'Report submitted for review.',
        'review' => 'Report marked Under Review.',
        'return' => 'Report returned for revision.',
        'approve' => 'Report approved.',
        'archive' => 'Report archived.',
    ];
    jsonResponse(true, $messages[$action] ?? 'Report updated.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e instanceof RuntimeException) jsonResponse(false, $e->getMessage());
    error_log('Committee report transition error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while updating the report.');
}
