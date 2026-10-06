<?php
/**
 * includes/report_drafts.php
 * ------------------------------------------------------------------
 * Shared persistence and workflow helpers for legislative committee reports.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/database.php';

/** Statuses that still allow editing content / transitions. */
const REPORT_DRAFT_OPEN_STATUSES = [
    'Draft', 'AI-Assisted Draft', 'Returned for Revision', 'AI Draft', 'Revised',
];

const REPORT_RECOMMENDATION_TYPES = [
    'Approval', 'Approval Without Amendment', 'Approval With Amendments', 'Disapproval',
    'Referral to Another Committee', 'Further Study', 'Consolidated With Another Measure',
    'Substitute Measure / Substitute Bill', 'Archived / No Further Action', 'Other',
];

/** Bootstrap color per status, for one consistent badge everywhere. */
function reportDraftStatusColor(string $status): string
{
    $map = [
        'Draft' => 'secondary', 'AI Draft' => 'warning', 'AI-Assisted Draft' => 'warning',
        'For Review' => 'primary', 'Under Review' => 'info', 'Under Human Review' => 'info',
        'Returned for Revision' => 'danger', 'Revised' => 'primary',
        'Approved' => 'success', 'Final' => 'success', 'Archived' => 'dark',
    ];
    return $map[$status] ?? 'secondary';
}

/** One draft, with committee/creator names joined in. Null if not found. */
function getReportDraft(PDO $pdo, int $draftId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT d.*, c.committee_name, cb.full_name AS created_by_name,
                rb.full_name AS reviewed_by_name, fb.full_name AS finalized_by_name,
                ab.full_name AS approved_by_name,
                j.jurisdiction_name,
                (SELECT GROUP_CONCAT(u_chair.full_name ORDER BY u_chair.full_name SEPARATOR ', ')
                 FROM committee_members cm_chair
                 INNER JOIN users u_chair ON u_chair.id = cm_chair.user_id
                 WHERE cm_chair.committee_id = c.committee_id
                   AND cm_chair.status = 'Active' AND cm_chair.member_role = 'Chairperson') AS chairperson_name,
                (SELECT GROUP_CONCAT(CONCAT(u_member.full_name, ' (', cm_member.member_role,
                                           IF(cm_member.political_group IS NULL, '', CONCAT(', ', cm_member.political_group)), ')')
                                     ORDER BY FIELD(cm_member.member_role, 'Chairperson', 'Vice Chairperson', 'Member'), u_member.full_name
                                     SEPARATOR ', ')
                 FROM committee_members cm_member
                 INNER JOIN users u_member ON u_member.id = cm_member.user_id
                 WHERE cm_member.committee_id = c.committee_id AND cm_member.status = 'Active') AS member_names,
                wa.task_title AS linked_task_title
         FROM committee_report_drafts d
         LEFT JOIN committees c ON c.committee_id = d.committee_id
         LEFT JOIN jurisdictions j ON j.jurisdiction_id = d.jurisdiction_id
         LEFT JOIN users cb ON cb.id = d.created_by
         LEFT JOIN users rb ON rb.id = d.reviewed_by
         LEFT JOIN users fb ON fb.id = d.finalized_by
         LEFT JOIN users ab ON ab.id = d.approved_by
         LEFT JOIN workload_assignments wa ON wa.workload_id = d.linked_workload_id
         WHERE d.draft_id = :id"
    );
    $stmt->execute([':id' => $draftId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Active jurisdictions available to the selected committee, direct assignment first. */
function getCommitteeReportJurisdictions(PDO $pdo, int $committeeId): array
{
    $stmt = $pdo->prepare(
        "SELECT j.jurisdiction_id, j.jurisdiction_name
         FROM committees c
         INNER JOIN jurisdictions j ON j.status = 'Active'
           AND (j.jurisdiction_id = c.jurisdiction_id OR j.category = c.committee_name)
         WHERE c.committee_id = :committee_id
         ORDER BY (j.jurisdiction_id = c.jurisdiction_id) DESC, j.jurisdiction_name"
    );
    $stmt->execute([':committee_id' => $committeeId]);
    return $stmt->fetchAll();
}

/** Verify that a report's chosen jurisdiction belongs to its committee. */
function isCommitteeReportJurisdiction(PDO $pdo, int $committeeId, int $jurisdictionId): bool
{
    foreach (getCommitteeReportJurisdictions($pdo, $committeeId) as $jurisdiction) {
        if ((int)$jurisdiction['jurisdiction_id'] === $jurisdictionId) return true;
    }
    return false;
}

function canEditReportDraft(array $draft, int $userId): bool
{
    if (!canManage()) return false;
    if (isAdmin()) return true;
    if ((int)($draft['created_by'] ?? 0) === $userId) return true;
    if (currentRole() !== ROLE_STAFF || empty($draft['committee_id'])) return false;

    $stmt = db()->prepare(
        "SELECT 1 FROM committee_members
         WHERE committee_id = :committee_id AND user_id = :user_id
           AND member_role = 'Chairperson' AND status = 'Active'
         LIMIT 1"
    );
    $stmt->execute([
        ':committee_id' => (int)$draft['committee_id'],
        ':user_id' => $userId,
    ]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Create a new draft. $narrative is the shape buildReportNarrative()
 * already returns (executive_summary/analysis/observations/
 * recommendations/conclusion) — an AI-generated one is saved verbatim,
 * a blank manual draft just passes an array of nulls.
 */
function createReportDraft(
    PDO $pdo,
    ?int $committeeId,
    string $reportType,
    string $reportTitle,
    array $narrative,
    bool $aiGenerated,
    int $createdBy,
    ?int $linkedWorkloadId = null,
    ?int $jurisdictionId = null
): int {
    $stmt = $pdo->prepare(
        'INSERT INTO committee_report_drafts
         (committee_id, jurisdiction_id, report_type, report_title, status, ai_generated,
          executive_summary, analysis, observations, recommendations, conclusion,
          linked_workload_id, created_by, created_at)
         VALUES (:cid, :jurisdiction_id, :type, :title, :status, :ai, :summary, :analysis, :observations, :recommendations, :conclusion,
                 :workload, :by, NOW())'
    );
    $stmt->execute([
        ':cid' => $committeeId, ':jurisdiction_id' => $jurisdictionId, ':type' => $reportType, ':title' => $reportTitle,
        ':status' => $aiGenerated ? 'AI-Assisted Draft' : 'Draft', ':ai' => $aiGenerated ? 1 : 0,
        ':summary' => $narrative['executive_summary'] ?? null, ':analysis' => $narrative['analysis'] ?? null,
        ':observations' => $narrative['observations'] ?? null, ':recommendations' => $narrative['recommendations'] ?? null,
        ':conclusion' => $narrative['conclusion'] ?? null,
        ':workload' => $linkedWorkloadId, ':by' => $createdBy,
    ]);
    $draftId = (int)$pdo->lastInsertId();
    $reportNumber = 'CR-' . date('Y') . '-' . str_pad((string)$draftId, 4, '0', STR_PAD_LEFT);
    $yearStmt = $pdo->prepare('SELECT YEAR(created_at) FROM committee_report_drafts WHERE draft_id = :id');
    $yearStmt->execute([':id' => $draftId]);
    $createdYear = (int)$yearStmt->fetchColumn();
    $internalReference = 'CMAS-REF-' . $createdYear . '-' . str_pad((string)$draftId, 6, '0', STR_PAD_LEFT);
    $numberStmt = $pdo->prepare(
        'UPDATE committee_report_drafts
         SET report_number = :number, internal_reference_no = :internal_reference
         WHERE draft_id = :id'
    );
    $numberStmt->execute([
        ':number' => $reportNumber, ':internal_reference' => $internalReference, ':id' => $draftId,
    ]);
    return $draftId;
}

/**
 * Save edited content for an open report and retain AI-assisted status
 * where relevant; a returned manual report returns to Draft after edits.
 */
function updateReportDraftContent(PDO $pdo, int $draftId, array $fields, int $updatedBy): void
{
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) $pdo->beginTransaction();
    try {
        $statusStmt = $pdo->prepare('SELECT status, ai_generated FROM committee_report_drafts WHERE draft_id = :id FOR UPDATE');
        $statusStmt->execute([':id' => $draftId]);
        $draftState = $statusStmt->fetch();
        $status = $draftState['status'] ?? null;
        if (!is_string($status) || !in_array($status, REPORT_DRAFT_OPEN_STATUSES, true)) {
            throw new RuntimeException('This draft can no longer be edited.');
        }

        $newStatus = match ($status) {
            'AI Draft' => 'AI-Assisted Draft',
            'Returned for Revision' => (int)$draftState['ai_generated'] === 1 ? 'AI-Assisted Draft' : 'Draft',
            'Revised' => 'Draft',
            default => $status,
        };
        $stmt = $pdo->prepare(
            'UPDATE committee_report_drafts SET
                report_title = :title, executive_summary = :summary,
                analysis = :analysis, observations = :observations,
                recommendations = :recommendations, conclusion = :conclusion,
                jurisdiction_id = :jurisdiction_id,
                legislative_matter_ref = :matter, meeting_ref = :meeting, hearing_ref = :hearing,
                research_ref = :research, document_references = :documents, status = :status,
                proposed_ordinance_title = :measure_title, reference_measure_no = :measure_no,
                date_referred = :date_referred, referred_by = :referred_by, subject_title = :subject,
                matter_referred = :matter_referred, committee_proceedings = :proceedings,
                findings = :findings, discussion_analysis = :discussion,
                recommendation_type = :recommendation_type, legislative_history = :history,
                committee_amendments = :amendments, individual_views = :views,
                committee_action = :committee_action, committee_action_date = :action_date,
                signature_details = :signatures, appendices = :appendices, updated_at = NOW()
             WHERE draft_id = :id AND status = :original_status'
        );
        $stmt->execute([
            ':title' => $fields['report_title'], ':summary' => $fields['executive_summary'],
            ':analysis' => $fields['analysis'], ':observations' => $fields['observations'],
            ':recommendations' => $fields['recommendations'], ':conclusion' => $fields['conclusion'],
            ':matter' => $fields['legislative_matter_ref'], ':meeting' => $fields['meeting_ref'],
            ':hearing' => $fields['hearing_ref'], ':research' => $fields['research_ref'],
            ':documents' => $fields['document_references'], ':status' => $newStatus,
            ':jurisdiction_id' => $fields['jurisdiction_id'] ?? null,
            ':measure_title' => $fields['proposed_ordinance_title'] ?? null,
            ':measure_no' => $fields['reference_measure_no'] ?? null,
            ':date_referred' => $fields['date_referred'] ?? null,
            ':referred_by' => $fields['referred_by'] ?? null,
            ':subject' => $fields['subject_title'] ?? null,
            ':matter_referred' => $fields['matter_referred'] ?? null,
            ':proceedings' => $fields['committee_proceedings'] ?? null,
            ':findings' => $fields['findings'] ?? null,
            ':discussion' => $fields['discussion_analysis'] ?? null,
            ':recommendation_type' => $fields['recommendation_type'] ?? null,
            ':history' => $fields['legislative_history'] ?? null,
            ':amendments' => $fields['committee_amendments'] ?? null,
            ':views' => $fields['individual_views'] ?? null,
            ':committee_action' => $fields['committee_action'] ?? null,
            ':action_date' => $fields['committee_action_date'] ?? null,
            ':signatures' => $fields['signature_details'] ?? null,
            ':appendices' => $fields['appendices'] ?? null,
            ':id' => $draftId, ':original_status' => $status,
        ]);
        if ($stmt->rowCount() === 0) {
            $currentStatus = $pdo->prepare('SELECT status FROM committee_report_drafts WHERE draft_id = :id');
            $currentStatus->execute([':id' => $draftId]);
            if ($currentStatus->fetchColumn() !== $newStatus) {
                throw new RuntimeException('This draft changed while it was being saved. Reload and try again.');
            }
        }
        if ($ownsTransaction) $pdo->commit();
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function logReportDraftHistory(
    PDO $pdo,
    int $draftId,
    string $action,
    int $userId,
    ?string $previousStatus,
    ?string $newStatus,
    ?string $comments = null
): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO committee_report_draft_history
            (draft_id, action, user_id, user_role, previous_status, new_status, comments, created_at)
         VALUES (:draft_id, :action, :user_id, :role, :previous, :new, :comments, NOW())'
    );
    $user = currentUser();
    $stmt->execute([
        ':draft_id' => $draftId, ':action' => $action, ':user_id' => $userId,
        ':role' => $user['role_name'] ?? null, ':previous' => $previousStatus,
        ':new' => $newStatus, ':comments' => $comments,
    ]);
}

/** Archive a Final (or abandoned open) draft. */
function archiveReportDraft(PDO $pdo, int $draftId): void
{
    $stmt = $pdo->prepare(
        'UPDATE committee_report_drafts SET status = "Archived"
         WHERE draft_id = :id AND status IN ("Draft", "AI Draft", "Under Human Review", "Revised", "Final")'
    );
    $stmt->execute([':id' => $draftId]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('This draft can no longer be archived.');
    }
}

/** Parse the "label — reference/URL" per-line document_references text into a structured list for display. */
function parseDocumentReferences(?string $raw): array
{
    if ($raw === null || trim($raw) === '') return [];
    $lines = preg_split('/\r\n|\r|\n/', trim($raw));
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if (str_contains($line, ' — ')) {
            [$label, $ref] = array_map('trim', explode(' — ', $line, 2));
        } elseif (str_contains($line, ' - ')) {
            [$label, $ref] = array_map('trim', explode(' - ', $line, 2));
        } else {
            $label = $line; $ref = '';
        }
        $items[] = ['label' => $label, 'reference' => $ref];
    }
    return $items;
}
