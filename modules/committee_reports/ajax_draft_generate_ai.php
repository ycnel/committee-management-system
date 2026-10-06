<?php
/**
 * modules/committee_reports/ajax_draft_generate_ai.php
 * ------------------------------------------------------------------
 * POST: generate report suggestions through the configured CMAS AI
 * integration. AI output remains a human-editable draft and this
 * endpoint cannot approve or finalize a report.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/report_drafts.php';
require_once __DIR__ . '/../../includes/GeminiAI.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

if ((int)($_POST['draft_id'] ?? 0) > 0) {
    $draftId = (int)$_POST['draft_id'];
    $section = clean((string)($_POST['section'] ?? 'all'));
    $sourceRaw = (string)($_POST['source_content'] ?? '');
    if (strlen($sourceRaw) > 100000) jsonResponse(false, 'Report source text is too large to process.');
    $sourceFields = json_decode($sourceRaw, true);
    if (!is_array($sourceFields)) jsonResponse(false, 'Save or reload the report fields before requesting AI assistance.');

    $pdo = db();
    $draft = getReportDraft($pdo, $draftId);
    if (!$draft) jsonResponse(false, 'Report not found.');
    if ($draft['report_type'] === 'Committee') {
        jsonResponse(false, 'AI generation is disabled for formal Committee Reports. Use Generate from CMAS Data instead.');
    }
    if (!canEditReportDraft($draft, (int)currentUserId())) {
        jsonResponse(false, 'You do not have permission to use AI assistance on this Committee Report.');
    }
    if (!in_array($draft['status'], REPORT_DRAFT_OPEN_STATUSES, true)) {
        jsonResponse(false, 'AI assistance is available only while editing a draft.');
    }

    $allowedSourceFields = [
        'report_title', 'proposed_ordinance_title', 'reference_measure_no', 'date_referred',
        'referred_by', 'subject_title', 'matter_referred', 'committee_proceedings',
        'findings', 'discussion_analysis', 'conclusion', 'recommendations',
        'legislative_history', 'committee_amendments', 'individual_views',
        'committee_action', 'committee_action_date', 'legislative_matter_ref',
        'meeting_ref', 'hearing_ref', 'research_ref', 'document_references',
    ];
    $source = [];
    foreach ($allowedSourceFields as $field) {
        $value = $sourceFields[$field] ?? '';
        if (is_string($value) && trim($value) !== '') $source[$field] = mb_substr(trim($value), 0, 12000);
    }
    $committeeStmt = $pdo->prepare(
        "SELECT c.committee_name, c.description AS committee_description,
                j.jurisdiction_name, j.description AS jurisdiction_description, j.scope_definition,
                GROUP_CONCAT(
                    CONCAT(u.full_name, ' (', cm.member_role,
                           IF(cm.political_group IS NULL, '', CONCAT(', ', cm.political_group)), ')')
                    ORDER BY FIELD(cm.member_role, 'Chairperson', 'Vice Chairperson', 'Member'), u.full_name
                    SEPARATOR ', '
                ) AS members
         FROM committees c
         LEFT JOIN jurisdictions j ON j.jurisdiction_id = COALESCE(
             :selected_jurisdiction_id,
             (SELECT j2.jurisdiction_id
              FROM jurisdictions j2
              WHERE j2.status = 'Active'
                AND (j2.category = c.committee_name OR j2.jurisdiction_id = c.jurisdiction_id)
              ORDER BY (j2.jurisdiction_id = c.jurisdiction_id) DESC, j2.jurisdiction_name
              LIMIT 1)
         )
         LEFT JOIN committee_members cm ON cm.committee_id = c.committee_id AND cm.status = 'Active'
         LEFT JOIN users u ON u.id = cm.user_id
         WHERE c.committee_id = :committee_id
         GROUP BY c.committee_id, c.committee_name, c.description, j.jurisdiction_name, j.description, j.scope_definition"
    );
    $committeeStmt->execute([
        ':committee_id' => (int)$draft['committee_id'],
        ':selected_jurisdiction_id' => $draft['jurisdiction_id'] ?? null,
    ]);
    $committeeSource = $committeeStmt->fetch() ?: [];
    $context = [
        'report_number' => $draft['report_number'],
        'committee' => $committeeSource,
        'user_entered_report_sources' => $source,
        'accuracy_notice' => 'Committee name, its linked jurisdiction records, and listed members are system records. Other facts are only those in user_entered_report_sources. No meeting/hearing or legislative matter records were found in CMAS.',
    ];
    $ai = new GeminiAI($pdo);
    $result = $ai->generateLegislativeReportSections($context, $section);
    if (!$result['available']) {
        jsonResponse(false, 'The configured AI service is unavailable or returned no valid report sections. No report content was changed.');
    }

    $sourceNames = array_values(array_filter([
        'Selected Committee',
        !empty($committeeSource['jurisdiction_name']) ? 'Selected Report Jurisdiction' : null,
        isset($source['proposed_ordinance_title']) ? 'User-Entered Proposed Measure' : null,
        isset($source['date_referred']) || isset($source['referred_by']) ? 'Referral Information' : null,
        isset($source['committee_proceedings']) || isset($source['meeting_ref']) ? 'User-Entered Proceedings / Meeting Reference' : null,
        isset($source['hearing_ref']) ? 'User-Entered Hearing Reference' : null,
        isset($source['document_references']) ? 'User-Entered Supporting Document References' : null,
        isset($source['findings']) || isset($source['discussion_analysis']) ? 'Existing Report Notes' : null,
    ]));
    $sourceLabel = implode("\n", $sourceNames);
    $oldStatus = $draft['status'];
    $newStatus = 'AI-Assisted Draft';
    try {
        $pdo->beginTransaction();
        $sourceStmt = $pdo->prepare(
            'UPDATE committee_report_drafts
             SET status = :status, ai_generated = 1, ai_sources = :sources, updated_at = NOW()
             WHERE draft_id = :id AND status = :old_status'
        );
        $sourceStmt->execute([
            ':status' => $newStatus, ':sources' => $sourceLabel, ':id' => $draftId, ':old_status' => $oldStatus,
        ]);
        if ($sourceStmt->rowCount() !== 1) throw new RuntimeException('The report changed while AI was generating. Reload and try again.');
        logReportDraftHistory($pdo, $draftId, 'AI Generated', (int)currentUserId(), $oldStatus, $newStatus, 'Generated ' . implode(', ', array_keys($result['sections'])));
        logActivity((int)currentUserId(), 'AI Committee Report Draft', 'Generated AI suggestions for report ' . $draft['report_number']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('AI report draft persistence error: ' . $e->getMessage());
        jsonResponse(false, $e instanceof RuntimeException ? $e->getMessage() : 'AI suggestions could not be recorded.');
    }
    jsonResponse(true, 'AI suggestions generated. Review each section before saving.', [
        'sections' => $result['sections'],
        'ai_sources' => $sourceLabel,
    ]);
}

require_once __DIR__ . '/report_data.php';
require_once __DIR__ . '/report_narrative.php';

$type = in_array($_POST['type'] ?? '', ['committee', 'workload', 'performance'], true) ? $_POST['type'] : 'committee';
if ($type === 'committee') {
    jsonResponse(false, 'AI generation is disabled for formal Committee Reports. Create a draft and use Generate from CMAS Data instead.');
}
$committeeId = (int)($_POST['committee_id'] ?? 0);
$jurisdictionRaw = trim((string)($_POST['jurisdiction_id'] ?? ''));
$jurisdictionId = null;
if ($jurisdictionRaw !== '') {
    if (!ctype_digit($jurisdictionRaw) || (int)$jurisdictionRaw <= 0) {
        jsonResponse(false, 'Select one valid jurisdiction.');
    }
    $jurisdictionId = (int)$jurisdictionRaw;
}
$reportTitle = clean((string)($_POST['report_title'] ?? ''));
$dateFrom = clean((string)($_POST['date_from'] ?? ''));
$dateTo = clean((string)($_POST['date_to'] ?? ''));
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : null;
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : null;

if ($reportTitle === '') jsonResponse(false, 'A report title is required.');
if (mb_strlen($reportTitle) > 255) jsonResponse(false, 'Report title is too long.');

$typeLabels = ['committee' => 'Committee', 'workload' => 'Workload', 'performance' => 'Performance'];

$pdo = db();
try {
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

    $data = buildCommitteeReportData($pdo, $type, $committeeId, $dateFrom, $dateTo);
    $aiUsed = false;
    $narrative = buildReportNarrative($pdo, $type, $data, $committeeId, $aiUsed);

    $draftId = createReportDraft(
        $pdo, $committeeId > 0 ? $committeeId : null, $typeLabels[$type], $reportTitle,
        $narrative, $aiUsed, (int)currentUserId(), null, $jurisdictionId
    );

    logReportDraftHistory($pdo, $draftId, 'AI Generated', (int)currentUserId(), null, 'AI-Assisted Draft');
    logActivity((int)currentUserId(), 'Report Draft',
        'Generated ' . ($aiUsed ? 'AI' : 'factual-fallback') . ' draft #' . $draftId . ' ("' . $reportTitle . '")');

    jsonResponse(true, $aiUsed
        ? 'AI-Assisted Draft created — it requires human review and approval.'
        : 'Draft created from factual data (AI narrative was unavailable).',
        ['draft_id' => $draftId, 'ai_used' => $aiUsed]);
} catch (Throwable $e) {
    error_log('Draft AI generation error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while generating the draft.');
}
