<?php
/**
 * POST: preview deterministic Committee Report sections from saved CMAS
 * records. This endpoint does not call AI or persist report content;
 * it records the verified source labels in the report history.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/report_drafts.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$draftId = (int)($_POST['id'] ?? 0);
if ($draftId <= 0) jsonResponse(false, 'A report is required.');

$pdo = db();
$draft = getReportDraft($pdo, $draftId);
if (!$draft) jsonResponse(false, 'Report not found.');
if (!canEditReportDraft($draft, (int)currentUserId())) {
    jsonResponse(false, 'You do not have permission to generate this Committee Report.');
}
if (!in_array($draft['status'], REPORT_DRAFT_OPEN_STATUSES, true)) {
    jsonResponse(false, 'CMAS report generation is available only while editing a draft.');
}
if ($draft['report_type'] !== 'Committee') {
    jsonResponse(false, 'Deterministic legislative report generation is available only for Committee Reports.');
}

$committeeId = (int)($draft['committee_id'] ?? 0);
$jurisdictionId = (int)($draft['jurisdiction_id'] ?? 0);
$availableJurisdictions = $committeeId > 0
    ? getCommitteeReportJurisdictions($pdo, $committeeId)
    : [];
if ($availableJurisdictions && $jurisdictionId <= 0) {
    jsonResponse(false, 'Select one jurisdiction and save the report before generating a preview.');
}
if ($jurisdictionId > 0
    && ($committeeId <= 0 || !isCommitteeReportJurisdiction($pdo, $committeeId, $jurisdictionId))) {
    jsonResponse(false, 'The report jurisdiction is not linked to its committee.');
}

$scope = null;
if ($jurisdictionId > 0) {
    $scopeStmt = $pdo->prepare(
        "SELECT description, scope_definition
         FROM jurisdictions
         WHERE jurisdiction_id = :jurisdiction_id AND status = 'Active'"
    );
    $scopeStmt->execute([':jurisdiction_id' => $jurisdictionId]);
    $scope = $scopeStmt->fetch() ?: null;
}

$cleanValue = static function (array $row, string $key): string {
    return trim((string)($row[$key] ?? ''));
};
$formatDate = static function (string $date): string {
    $timestamp = strtotime($date);
    return $timestamp === false ? $date : date('F j, Y', $timestamp);
};
$existingOr = static function (string $key, string $fallback) use ($draft, $cleanValue): string {
    $existing = $cleanValue($draft, $key);
    return $existing !== '' ? $existing : $fallback;
};

$matterLines = [];
if ($cleanValue($draft, 'committee_name') !== '') {
    $matterLines[] = 'Committee: ' . $cleanValue($draft, 'committee_name');
}
if ($cleanValue($draft, 'jurisdiction_name') !== '') {
    $matterLines[] = 'Selected jurisdiction: ' . $cleanValue($draft, 'jurisdiction_name');
}
$scopeText = $scope ? trim((string)($scope['scope_definition'] ?: $scope['description'] ?: '')) : '';
if ($scopeText !== '') $matterLines[] = 'Recorded jurisdiction scope: ' . $scopeText;
foreach ([
    'proposed_ordinance_title' => 'Proposed measure',
    'reference_measure_no' => 'Official measure reference',
    'subject_title' => 'Subject',
    'legislative_matter_ref' => 'Legislative matter reference',
    'referred_by' => 'Referred by',
] as $key => $label) {
    $value = $cleanValue($draft, $key);
    if ($value !== '') $matterLines[] = $label . ': ' . $value;
}
if ($cleanValue($draft, 'date_referred') !== '') {
    $matterLines[] = 'Date referred: ' . $formatDate($cleanValue($draft, 'date_referred'));
}
if ($cleanValue($draft, 'matter_referred') !== '') {
    $matterLines[] = 'Matter description entered for this report: ' . $cleanValue($draft, 'matter_referred');
}

$historyLines = [];
foreach ([
    'date_referred' => 'Date referred',
    'referred_by' => 'Referred by',
    'reference_measure_no' => 'Official measure reference',
    'legislative_matter_ref' => 'Legislative matter reference',
] as $key => $label) {
    $value = $cleanValue($draft, $key);
    if ($value === '') continue;
    if ($key === 'date_referred') $value = $formatDate($value);
    $historyLines[] = $label . ': ' . $value;
}

$documentReferences = $cleanValue($draft, 'document_references');
$appendicesFallback = $documentReferences !== ''
    ? 'Supporting document references entered for this report:' . "\n" . $documentReferences
    : 'No supporting document references are recorded in CMAS.';

$generated = [
    'matter_referred' => $existingOr(
        'matter_referred',
        $matterLines ? implode("\n", $matterLines) : 'Not recorded in CMAS.'
    ),
    'committee_proceedings' => $existingOr('committee_proceedings', 'Not recorded in CMAS.'),
    'findings' => $existingOr('findings', 'Not recorded in CMAS.'),
    'discussion_analysis' => $existingOr('discussion_analysis', 'Not recorded in CMAS.'),
    'recommendations' => $existingOr(
        'recommendations',
        'Pending Committee Action — no Committee recommendation is recorded in CMAS.'
    ),
    'conclusion' => $existingOr('conclusion', 'Not recorded in CMAS.'),
    'legislative_history' => $existingOr(
        'legislative_history',
        $historyLines ? implode("\n", $historyLines) : 'Not recorded in CMAS.'
    ),
    'committee_amendments' => $existingOr('committee_amendments', 'No Committee Amendments are recorded in CMAS.'),
    'individual_views' => $existingOr(
        'individual_views',
        'No individual, minority, or supplemental views are recorded in CMAS.'
    ),
    'signature_details' => $existingOr(
        'signature_details',
        'No member concurrence, dissent, abstention, or attendance status is recorded in CMAS.'
    ),
    'appendices' => $existingOr('appendices', $appendicesFallback),
];

$sourceNames = ['Report draft record #' . $draftId];
if ($committeeId > 0 && $cleanValue($draft, 'committee_name') !== '') {
    $sourceNames[] = 'Committee record #' . $committeeId . ': ' . $cleanValue($draft, 'committee_name');
}
if ($jurisdictionId > 0 && $cleanValue($draft, 'jurisdiction_name') !== '') {
    $sourceNames[] = 'Jurisdiction record #' . $jurisdictionId . ': ' . $cleanValue($draft, 'jurisdiction_name');
}
if ($scopeText !== '') $sourceNames[] = 'Recorded jurisdiction scope: ' . $scopeText;
if ($historyLines) $sourceNames = array_merge($sourceNames, $historyLines);
if ($documentReferences !== '') $sourceNames[] = 'Supporting document references entered in CMAS: ' . $documentReferences;
$preservedFields = [];
foreach ([
    'matter_referred' => 'matter description',
    'committee_proceedings' => 'proceedings',
    'findings' => 'findings',
    'discussion_analysis' => 'discussion/analysis',
    'recommendations' => 'recommendation',
    'conclusion' => 'conclusion',
    'legislative_history' => 'legislative history',
    'committee_amendments' => 'amendments',
    'individual_views' => 'member views',
    'committee_action' => 'Committee action',
    'signature_details' => 'signature/concurrence notes',
    'appendices' => 'appendix notes',
] as $key => $label) {
    if ($cleanValue($draft, $key) !== '') $preservedFields[] = $label;
}
if ($preservedFields) {
    $sourceNames[] = 'Existing report-draft text preserved (not independently verified): ' . implode(', ', $preservedFields);
}
if (!$sourceNames) $sourceNames[] = 'No source records or references were available.';
$sourceList = implode("\n", $sourceNames);
if (strlen($sourceList) > 60000) {
    jsonResponse(false, 'The saved source references are too large to record in the report audit history.');
}

try {
    $pdo->beginTransaction();
    $lock = $pdo->prepare('SELECT status FROM committee_report_drafts WHERE draft_id = :id FOR UPDATE');
    $lock->execute([':id' => $draftId]);
    $lockedStatus = $lock->fetchColumn();
    if (!is_string($lockedStatus) || !in_array($lockedStatus, REPORT_DRAFT_OPEN_STATUSES, true)) {
        $pdo->rollBack();
        jsonResponse(false, 'The report is no longer editable. Reload and try again.');
    }
    logReportDraftHistory(
        $pdo,
        $draftId,
        'CMAS Preview Generated',
        (int)currentUserId(),
        $lockedStatus,
        $lockedStatus,
        $sourceList
    );
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('CMAS report preview audit error: ' . $e->getMessage());
    jsonResponse(false, 'CMAS preview sources could not be recorded. No preview was returned.');
}

jsonResponse(true, 'CMAS data preview generated. Review and confirm each section before saving.', [
    'sections' => $generated,
    'sources' => $sourceNames,
    'notices' => [
        'No structured meeting, hearing, legislative-matter, amendment, research, or member-concurrence records are available in CMAS.',
        'Existing report text was preserved. Generated placeholders do not represent Committee findings or action.',
        'CMAS does not generate a recommendation type. An authorized human must select it and record the actual Committee action.',
    ],
]);
