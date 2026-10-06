<?php
/**
 * modules/workload/ajax_checklist_save.php
 * ------------------------------------------------------------------
 * POST: check/uncheck one manual Procedural Checklist item
 * (role-hierarchy revision §19) for a proposal. canManage()-only —
 * these are chairperson/admin verification steps ("I confirmed the
 * related hearing", "the report is prepared"), not something the
 * assigned member self-certifies.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/checklist.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$proposalId = (int)($_POST['proposal_id'] ?? 0);
$itemKey = clean((string)($_POST['item_key'] ?? ''));
$checked = (string)($_POST['checked'] ?? '0') === '1';
$notes = clean((string)($_POST['notes'] ?? ''));

if ($proposalId <= 0) jsonResponse(false, 'A proposal is required.');
if (!array_key_exists($itemKey, MANUAL_CHECKLIST_ITEMS)) jsonResponse(false, 'Unknown checklist item.');
if (mb_strlen($notes) > 500) jsonResponse(false, 'Notes are too long (max 500 characters).');

$pdo = db();
$proposal = getProposalWithContext($pdo, $proposalId);
if (!$proposal) jsonResponse(false, 'Proposal not found.');

try {
    setChecklistItem($pdo, $proposalId, $itemKey, $checked, $notes !== '' ? $notes : null, (int)currentUserId());

    logActivity(
        (int)currentUserId(), 'Checklist',
        'Proposal #' . $proposalId . ' (' . $proposal['task_title'] . '): "' . MANUAL_CHECKLIST_ITEMS[$itemKey] . '" '
        . ($checked ? 'checked' : 'unchecked')
    );

    jsonResponse(true, 'Checklist updated.');
} catch (Throwable $e) {
    error_log('Checklist save error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while updating the checklist.');
}
