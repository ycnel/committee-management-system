<?php
/**
 * modules/committee_reports/ajax_draft_save.php
 * ------------------------------------------------------------------
 * POST: id=0 creates a new report draft; id>0 saves content and
 * references on an editable draft or returned report.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/report_drafts.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id = (int)($_POST['id'] ?? 0);
$committeeId = (int)($_POST['committee_id'] ?? 0);
$jurisdictionRaw = trim((string)($_POST['jurisdiction_id'] ?? ''));
$jurisdictionId = null;
if ($jurisdictionRaw !== '') {
    if (!ctype_digit($jurisdictionRaw) || (int)$jurisdictionRaw <= 0) {
        jsonResponse(false, 'Select one valid jurisdiction.');
    }
    $jurisdictionId = (int)$jurisdictionRaw;
}
$reportType = clean((string)($_POST['report_type'] ?? 'Committee'));
$reportTitle = clean((string)($_POST['report_title'] ?? ''));
$fields = [
    'report_title' => $reportTitle,
    'jurisdiction_id' => $jurisdictionId,
    'executive_summary' => clean((string)($_POST['executive_summary'] ?? '')) ?: null,
    'analysis' => clean((string)($_POST['analysis'] ?? '')) ?: null,
    'observations' => clean((string)($_POST['observations'] ?? '')) ?: null,
    'recommendations' => clean((string)($_POST['recommendations'] ?? '')) ?: null,
    'conclusion' => clean((string)($_POST['conclusion'] ?? '')) ?: null,
    'legislative_matter_ref' => clean((string)($_POST['legislative_matter_ref'] ?? '')) ?: null,
    'meeting_ref' => clean((string)($_POST['meeting_ref'] ?? '')) ?: null,
    'hearing_ref' => clean((string)($_POST['hearing_ref'] ?? '')) ?: null,
    'research_ref' => clean((string)($_POST['research_ref'] ?? '')) ?: null,
    'document_references' => clean((string)($_POST['document_references'] ?? '')) ?: null,
    'proposed_ordinance_title' => clean((string)($_POST['proposed_ordinance_title'] ?? '')) ?: null,
    'reference_measure_no' => clean((string)($_POST['reference_measure_no'] ?? '')) ?: null,
    'date_referred' => clean((string)($_POST['date_referred'] ?? '')) ?: null,
    'referred_by' => clean((string)($_POST['referred_by'] ?? '')) ?: null,
    'subject_title' => clean((string)($_POST['subject_title'] ?? '')) ?: null,
    'matter_referred' => clean((string)($_POST['matter_referred'] ?? '')) ?: null,
    'committee_proceedings' => clean((string)($_POST['committee_proceedings'] ?? '')) ?: null,
    'findings' => clean((string)($_POST['findings'] ?? '')) ?: null,
    'discussion_analysis' => clean((string)($_POST['discussion_analysis'] ?? '')) ?: null,
    'recommendation_type' => clean((string)($_POST['recommendation_type'] ?? '')) ?: null,
    'legislative_history' => clean((string)($_POST['legislative_history'] ?? '')) ?: null,
    'committee_amendments' => clean((string)($_POST['committee_amendments'] ?? '')) ?: null,
    'individual_views' => clean((string)($_POST['individual_views'] ?? '')) ?: null,
    'committee_action' => clean((string)($_POST['committee_action'] ?? '')) ?: null,
    'committee_action_date' => clean((string)($_POST['committee_action_date'] ?? '')) ?: null,
    'signature_details' => clean((string)($_POST['signature_details'] ?? '')) ?: null,
    'appendices' => clean((string)($_POST['appendices'] ?? '')) ?: null,
];

if (!in_array($reportType, ['Committee', 'Workload', 'Performance', 'Monthly', 'Annual'], true)) {
    jsonResponse(false, 'Invalid report type.');
}
if ($fields['recommendation_type'] !== null
    && !in_array($fields['recommendation_type'], REPORT_RECOMMENDATION_TYPES, true)) {
    jsonResponse(false, 'Select a valid Committee recommendation type.');
}
if ($reportTitle === '') jsonResponse(false, 'A report title is required.');
if (mb_strlen($reportTitle) > 255) jsonResponse(false, 'Report title is too long.');
if ($reportType === 'Committee' && $committeeId <= 0) jsonResponse(false, 'Select a committee for a Committee Report.');
foreach (['date_referred', 'committee_action_date'] as $dateField) {
    if ($fields[$dateField] !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fields[$dateField])) {
        jsonResponse(false, 'Enter a valid date.');
    }
}

$pdo = db();
try {
    if ($id === 0) {
        if ($committeeId > 0) {
            $committeeCheck = $pdo->prepare('SELECT 1 FROM committees WHERE committee_id = :id');
            $committeeCheck->execute([':id' => $committeeId]);
            if (!$committeeCheck->fetchColumn()) jsonResponse(false, 'Selected committee was not found.');
            $availableJurisdictions = getCommitteeReportJurisdictions($pdo, $committeeId);
            if ($availableJurisdictions && $jurisdictionId === null) {
                jsonResponse(false, 'Select one jurisdiction for this committee.');
            }
            if ($jurisdictionId !== null && !isCommitteeReportJurisdiction($pdo, $committeeId, $jurisdictionId)) {
                jsonResponse(false, 'The selected jurisdiction is not linked to this committee.');
            }
        } elseif ($jurisdictionId !== null) {
            jsonResponse(false, 'Select a committee before choosing a jurisdiction.');
        }
        $pdo->beginTransaction();
        $draftId = createReportDraft(
            $pdo, $committeeId > 0 ? $committeeId : null, $reportType, $reportTitle,
            [], false, (int)currentUserId(), null, $jurisdictionId
        );
        updateReportDraftContent($pdo, $draftId, $fields, (int)currentUserId());
        logReportDraftHistory($pdo, $draftId, 'Created', (int)currentUserId(), null, 'Draft');
        logActivity((int)currentUserId(), 'Report Draft', 'Created manual draft #' . $draftId . ' ("' . $reportTitle . '")');
        $pdo->commit();
        jsonResponse(true, 'Draft created.', ['draft_id' => $draftId]);
    }

    $before = getReportDraft($pdo, $id);
    if (!$before) jsonResponse(false, 'Report not found.');
    if (!canEditReportDraft($before, (int)currentUserId())) {
        jsonResponse(false, 'You do not have permission to edit this Committee Report.');
    }
    $draftCommitteeId = (int)($before['committee_id'] ?? 0);
    $availableJurisdictions = $draftCommitteeId > 0
        ? getCommitteeReportJurisdictions($pdo, $draftCommitteeId)
        : [];
    if ($availableJurisdictions && $jurisdictionId === null) {
        jsonResponse(false, 'Select one jurisdiction for this committee.');
    }
    if ($jurisdictionId !== null
        && ($draftCommitteeId <= 0 || !isCommitteeReportJurisdiction($pdo, $draftCommitteeId, $jurisdictionId))) {
        jsonResponse(false, 'The selected jurisdiction is not linked to this committee.');
    }
    $pdo->beginTransaction();
    updateReportDraftContent($pdo, $id, $fields, (int)currentUserId());
    $after = getReportDraft($pdo, $id);
    logReportDraftHistory($pdo, $id, 'Edited', (int)currentUserId(), $before['status'], $after['status']);
    logActivity((int)currentUserId(), 'Report Draft', 'Updated draft #' . $id . ' ("' . $reportTitle . '")');
    $pdo->commit();
    jsonResponse(true, 'Draft saved.', ['draft_id' => $id]);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, $e->getMessage());
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Draft save error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while saving the draft.');
}
