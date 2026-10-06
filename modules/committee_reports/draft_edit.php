<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/report_drafts.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]);

$id = (int)($_GET['id'] ?? 0);
$pdo = db();
$draft = $id > 0 ? getReportDraft($pdo, $id) : null;
if (!$draft) {
    setFlash('danger', 'Committee report not found.');
    redirect(APP_URL . '/modules/committee_reports/drafts.php');
}
$reportJurisdictions = !empty($draft['committee_id'])
    ? getCommitteeReportJurisdictions($pdo, (int)$draft['committee_id'])
    : [];
$historyStmt = $pdo->prepare(
    'SELECT h.*, u.full_name FROM committee_report_draft_history h
     LEFT JOIN users u ON u.id = h.user_id
     WHERE h.draft_id = :id ORDER BY h.created_at DESC, h.history_id DESC'
);
$historyStmt->execute([':id' => $id]);
$history = $historyStmt->fetchAll();
$isOpen = in_array($draft['status'], REPORT_DRAFT_OPEN_STATUSES, true);
$canEdit = $isOpen && canEditReportDraft($draft, (int)currentUserId());
$isApprover = hasRole([ROLE_ADMIN, ROLE_PRO_TEMPORE]);
$canApprove = $isApprover && (int)$draft['created_by'] !== (int)currentUserId();
$pageTitle = $draft['report_number'] ?: 'Committee Report';
$activeMenu = 'committee_reports';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper"><div><?php include __DIR__ . '/../../layouts/sidebar.php'; ?></div><div class="main-content"><?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
<div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
  <div>
    <h5 class="mb-1"><i class="bi bi-journal-richtext text-primary"></i> <?= e($draft['report_number'] ?: 'Committee Report') ?> — <?= e($draft['report_title']) ?></h5>
    <small class="text-muted"><?= e($draft['committee_name'] ?? 'Committee unavailable') ?><?= $draft['jurisdiction_name'] ? ' · ' . e($draft['jurisdiction_name']) : '' ?> · Prepared by <?= e($draft['created_by_name'] ?? 'Former user') ?></small>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a href="drafts.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnOpenReportPrintPreview"><i class="bi bi-printer"></i> Print / PDF</button>
  </div>
</div>

<div class="d-flex align-items-center flex-wrap gap-2 mb-3">
  <span class="badge bg-<?= e(reportDraftStatusColor($draft['status'])) ?> fs-6"><?= e($draft['status']) ?></span>
  <?php if ($draft['ai_generated']): ?><span class="badge bg-warning text-dark"><i class="bi bi-stars"></i> AI-Generated Draft — Requires Human Review</span><?php endif; ?>
  <?php if ($draft['approved_by_name']): ?><span class="small text-muted">Approved by <?= e($draft['approved_by_name']) ?><?= $draft['approved_at'] ? ' · ' . e(date('M j, Y g:i A', strtotime($draft['approved_at']))) : '' ?></span><?php endif; ?>
  <?php if ($draft['status'] === 'Final' && $draft['finalized_by_name']): ?><span class="small text-muted">Finalized by <?= e($draft['finalized_by_name']) ?></span><?php endif; ?>
</div>
<?php if ($draft['status'] === 'Returned for Revision' && $draft['returned_reason']): ?>
  <div class="alert alert-warning"><strong>Returned for revision:</strong> <?= nl2br(e($draft['returned_reason'])) ?></div>
<?php endif; ?>

<form id="draftForm">
  <input type="hidden" id="dfId" value="<?= $id ?>">
  <input type="hidden" id="dfCommitteeId" value="<?= (int)($draft['committee_id'] ?? 0) ?>">
  <input type="hidden" id="dfReportType" value="<?= e($draft['report_type']) ?>">

  <section class="card mb-3">
    <div class="card-header fw-semibold">Report Identification and Referral</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Committee Report No.</label><input class="form-control" value="<?= e($draft['report_number'] ?? '') ?>" disabled></div>
        <div class="col-md-4"><label class="form-label">CMAS Reference <span class="text-muted">(internal)</span></label><input class="form-control" value="<?= e($draft['internal_reference_no'] ?? '') ?>" disabled><div class="form-text">Auto-generated tracking ID; not an official measure number.</div></div>
        <div class="col-md-5"><label class="form-label">Report Title <span class="text-danger">*</span></label><input type="text" class="form-control" id="dfTitle" maxlength="255" value="<?= e($draft['report_title']) ?>" <?= $canEdit ? '' : 'disabled' ?>></div>
        <div class="col-md-8"><label class="form-label">Proposed Ordinance / Legislative Measure</label><input type="text" class="form-control" id="dfMeasureTitle" maxlength="500" value="<?= e($draft['proposed_ordinance_title'] ?? '') ?>" <?= $canEdit ? '' : 'disabled' ?>></div>
        <div class="col-md-4"><label class="form-label">Official Reference / Measure No.</label><input type="text" class="form-control" id="dfMeasureNo" maxlength="150" value="<?= e($draft['reference_measure_no'] ?? $draft['legislative_matter_ref'] ?? '') ?>" placeholder="Enter the official number from the source document" <?= $canEdit ? '' : 'disabled' ?>></div>
        <div class="col-md-4"><label class="form-label">Date Referred</label><input type="date" class="form-control" id="dfDateReferred" value="<?= e($draft['date_referred'] ?? '') ?>" <?= $canEdit ? '' : 'disabled' ?>></div>
        <div class="col-md-8"><label class="form-label">Referred By</label><input type="text" class="form-control" id="dfReferredBy" maxlength="255" value="<?= e($draft['referred_by'] ?? '') ?>" placeholder="Name of the council member or office that referred the measure" <?= $canEdit ? '' : 'disabled' ?>><div class="form-text">Enter the person or office that formally sent the matter to the Committee.</div></div>
        <div class="col-md-5"><label class="form-label" for="dfJurisdiction">Jurisdiction</label>
          <select class="form-select" id="dfJurisdiction" <?= $canEdit && $reportJurisdictions ? 'required' : '' ?> <?= $canEdit && $reportJurisdictions ? '' : 'disabled' ?>>
            <option value=""><?= $reportJurisdictions ? 'Select one jurisdiction' : 'No jurisdiction assigned' ?></option>
            <?php foreach ($reportJurisdictions as $jurisdiction): ?>
              <option value="<?= (int)$jurisdiction['jurisdiction_id'] ?>"
                <?= (int)($draft['jurisdiction_id'] ?? 0) === (int)$jurisdiction['jurisdiction_id'] ? 'selected' : '' ?>>
                <?= e($jurisdiction['jurisdiction_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Choose one jurisdiction linked to this committee.</div>
        </div>
        <div class="col-12"><label class="form-label">Subject / Title of the Measure</label><input type="text" class="form-control" id="dfSubject" maxlength="500" value="<?= e($draft['subject_title'] ?? '') ?>" placeholder="Enter the subject or title" <?= $canEdit ? '' : 'disabled' ?>></div>
        <div class="col-12">
          <div class="small text-muted rounded border bg-light p-2">
            <?= e($draft['committee_name'] ?? 'Committee') ?>
            <?php if ($draft['jurisdiction_name']): ?> · Jurisdiction: <?= e($draft['jurisdiction_name']) ?><?php endif; ?>
            <?php if ($draft['chairperson_name'] ?? null): ?> · Chairperson: <?= e($draft['chairperson_name']) ?><?php endif; ?>
            <?php if ($draft['member_names'] ?? null): ?><div class="mt-1">Committee members: <?= e($draft['member_names']) ?></div><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="card mb-3">
    <div class="card-header fw-semibold">Committee Report</div>
    <div class="card-body">
      <?php
        $reportSections = [
            'matter_referred' => ['I. Matter Referred', 'This section can be generated from saved Committee, jurisdiction, and report referral fields. Verify all entries against the source documents.'],
            'committee_proceedings' => ['II. Committee Proceedings', 'Meetings, dates, time, venue, presiding chair, attendees, invitees, hearings. Do not enter unverified details.'],
            'findings' => ['III. Findings', 'Documented deliberations, materials reviewed, hearing information, testimony, and verified legal sources.'],
            'discussion_analysis' => ['IV. Discussion / Analysis', 'Analysis based on the stated matter and documented source facts.'],
            'conclusion' => ['V. Conclusion', 'A concise conclusion based only on the report findings and analysis.'],
            'legislative_history' => ['VII. Legislative History', 'Introduction, referral, meetings, hearings, consultations, prior actions, related measures. Leave unknown facts blank.'],
            'committee_amendments' => ['VIII. Committee Amendments', 'Document amendments approved or proposed by the Committee. Enter “No Committee Amendments” when appropriate. AI does not create amendments.'],
            'individual_views' => ['IX. Individual / Minority / Supplemental Views', 'Record member views as provided. Do not automatically classify a view as minority or dissenting.'],
            'signature_details' => ['Signatures and Concurrence', 'For each active Committee member, enter a separate line with their name, explicit status (Concurred, Dissented, Abstained, or Not Present), signature record, and date. Do not presume concurrence.'],
            'appendices' => ['Appendices', 'Reference existing meeting minutes, attendance sheets, source measures, hearing records, amendments, and supporting documents.'],
        ];
      ?>
      <?php foreach ($reportSections as $field => [$label, $help]): ?>
        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center gap-2">
            <label class="form-label fw-semibold mb-1" for="df_<?= e($field) ?>"><?= e($label) ?></label>
            <?php if ($canEdit && $draft['report_type'] !== 'Committee'): ?><button type="button" class="btn btn-outline-secondary btn-sm" data-ai-section="<?= e($field) ?>"><i class="bi bi-stars"></i> AI Assist</button><?php endif; ?>
          </div>
          <div class="form-text mb-2"><?= e($help) ?></div>
          <textarea class="form-control" id="df_<?= e($field) ?>" rows="<?= in_array($field, ['committee_proceedings', 'findings', 'discussion_analysis', 'legislative_history', 'signature_details'], true) ? 5 : 3 ?>" placeholder="<?= $field === 'committee_proceedings' ? e("Meeting date(s):\nTime:\nVenue:\nPresiding chair:\nMembers present:\nMembers absent:\nResource persons / invitees:\nPublic hearing conducted (Yes / No / Unknown; include date only if documented):") : ($field === 'signature_details' ? e("Member Name — Concurred / Dissented / Abstained / Not Present — Signature recorded — Date: YYYY-MM-DD\nOne line for each active Committee member.") : '') ?>" <?= $canEdit ? '' : 'disabled' ?>><?= e($draft[$field] ?? '') ?></textarea>
        </div>
      <?php endforeach; ?>

      <div class="mb-4">
        <label class="form-label fw-semibold" for="df_recommendation_type">VI. Recommendation Type</label>
        <?php $recommendationOptions = REPORT_RECOMMENDATION_TYPES; ?>
        <select class="form-select mb-2" id="df_recommendation_type" <?= $canEdit ? '' : 'disabled' ?>>
          <option value="">Select only after Committee action</option>
          <?php foreach ($recommendationOptions as $option): ?><option value="<?= e($option) ?>" <?= ($draft['recommendation_type'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?>
        </select>
        <label class="form-label" for="df_recommendations">Recommendation / Proposed Committee Action</label>
        <?php if ($canEdit && $draft['report_type'] !== 'Committee'): ?><button type="button" class="btn btn-outline-secondary btn-sm float-end" data-ai-section="recommendations"><i class="bi bi-stars"></i> AI Assist (suggestion only)</button><?php endif; ?>
        <textarea class="form-control mt-2" id="df_recommendations" rows="4" <?= $canEdit ? '' : 'disabled' ?>><?= e($draft['recommendations'] ?? '') ?></textarea>
        <div class="form-text">The final recommendation must be explicitly selected and confirmed by an authorized human. AI cannot choose or finalize the Committee’s action.</div>
      </div>
      <div class="row g-3">
        <div class="col-md-8"><label class="form-label fw-semibold" for="df_committee_action">Committee Action Actually Taken</label><textarea class="form-control" id="df_committee_action" rows="3" <?= $canEdit ? '' : 'disabled' ?>><?= e($draft['committee_action'] ?? '') ?></textarea></div>
        <div class="col-md-4"><label class="form-label fw-semibold" for="df_committee_action_date">Committee Action Date</label><input type="date" class="form-control" id="df_committee_action_date" value="<?= e($draft['committee_action_date'] ?? '') ?>" <?= $canEdit ? '' : 'disabled' ?>></div>
      </div>
    </div>
  </section>

  <section class="card mb-3">
    <div class="card-header fw-semibold">Source References</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Legislative Matter Reference</label><input class="form-control" id="dfMatterRef" value="<?= e($draft['legislative_matter_ref'] ?? '') ?>" <?= $canEdit ? '' : 'disabled' ?>></div>
        <div class="col-md-6"><label class="form-label">Meeting Reference</label><input class="form-control" id="dfMeetingRef" value="<?= e($draft['meeting_ref'] ?? '') ?>" <?= $canEdit ? '' : 'disabled' ?>></div>
        <div class="col-md-6"><label class="form-label">Hearing Reference</label><input class="form-control" id="dfHearingRef" value="<?= e($draft['hearing_ref'] ?? '') ?>" <?= $canEdit ? '' : 'disabled' ?>></div>
        <div class="col-md-6"><label class="form-label">Research Reference</label><input class="form-control" id="dfResearchRef" value="<?= e($draft['research_ref'] ?? '') ?>" <?= $canEdit ? '' : 'disabled' ?>></div>
        <div class="col-12"><label class="form-label">Supporting Documents / Existing CMAS References</label><textarea class="form-control" id="dfDocumentRefs" rows="3" placeholder="One document reference per line" <?= $canEdit ? '' : 'disabled' ?>><?= e($draft['document_references'] ?? '') ?></textarea></div>
      </div>
      <details class="mt-3" id="aiSourcesPanel" <?= $draft['ai_sources'] ? 'open' : '' ?>>
        <summary class="fw-semibold">AI Sources used for generation</summary>
        <pre class="small text-wrap mt-2 mb-0" id="aiSourcesText"><?= e($draft['ai_sources'] ?: 'No AI-generated content recorded.') ?></pre>
      </details>
    </div>
  </section>

  <?php if ($canEdit || ($canApprove && in_array($draft['status'], ['For Review', 'Under Review', 'Approved'], true))): ?>
    <div class="d-flex gap-2 flex-wrap mb-4">
      <?php if ($canEdit): ?>
        <button type="button" class="btn btn-primary btn-sm" id="btnSaveDraft"><i class="bi bi-save"></i> Save Changes</button>
        <?php if ($draft['report_type'] === 'Committee'): ?>
          <button type="button" class="btn btn-outline-primary btn-sm" id="btnGenerateFromCmas"><i class="bi bi-database-check"></i> Generate from CMAS Data</button>
        <?php else: ?>
          <button type="button" class="btn btn-dark btn-sm" id="btnGenerateLegislativeReport"><i class="bi bi-stars"></i> AI Generate Report</button>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($canEdit && in_array($draft['status'], ['Draft', 'AI-Assisted Draft', 'Returned for Revision', 'AI Draft', 'Revised'], true)): ?><button type="button" class="btn btn-outline-primary btn-sm" id="btnSubmitForReview"><i class="bi bi-send"></i> Submit for Review</button><?php endif; ?>
      <?php if ($isApprover && $draft['status'] === 'For Review'): ?><button type="button" class="btn btn-info btn-sm" id="btnMarkReview">Start Review</button><?php endif; ?>
      <?php if ($canApprove && in_array($draft['status'], ['For Review', 'Under Review'], true)): ?><button type="button" class="btn btn-outline-danger btn-sm" id="btnReturnReport">Return for Revision</button><?php endif; ?>
      <?php if ($canApprove && $draft['status'] === 'Under Review'): ?><button type="button" class="btn btn-success btn-sm" id="btnApproveReport">Approve Report</button><?php endif; ?>
      <?php if ($canApprove && $draft['status'] === 'Approved'): ?><button type="button" class="btn btn-dark btn-sm" id="btnFinalize"><i class="bi bi-patch-check"></i> Finalize Official Report</button><?php endif; ?>
      <?php if (canManage() && !in_array($draft['status'], ['Archived', 'Final', 'For Review', 'Under Review', 'Approved'], true)): ?><button type="button" class="btn btn-outline-secondary btn-sm" id="btnArchive">Archive</button><?php endif; ?>
    </div>
  <?php endif; ?>
</form>

<?php if ($canEdit && $draft['report_type'] === 'Committee'): ?>
  <div class="modal fade" id="cmasPreviewModal" tabindex="-1" aria-labelledby="cmasPreviewTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="cmasPreviewTitle">CMAS-generated report preview</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted">This preview uses saved CMAS records and saved report fields only. Missing records are identified explicitly. No AI is used. Source labels are added to the audit history when the preview is generated; report content changes only when you apply and save it.</p>
          <div class="small mb-3"><strong>Sources:</strong> <span id="cmasPreviewSources"></span></div>
          <div id="cmasPreviewNotices" class="alert alert-info small"></div>
          <div id="cmasPreviewSections"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary btn-sm" id="btnApplyCmasPreview">Apply preview to empty sections</button>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="modal fade" id="reportPrintModal" tabindex="-1" aria-labelledby="reportPrintModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportPrintModalTitle"><?= e($draft['report_number'] ?: 'Committee Report') ?> — Print Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="reportPrintFrame" title="Committee report print preview" class="w-100 border-0" style="height:75vh"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnPrintReportPreview"><i class="bi bi-printer"></i> Print / Save PDF</button>
      </div>
    </div>
  </div>
</div>

<section class="card mb-4" id="reportHistory">
  <div class="card-header fw-semibold">Approval History</div>
  <?php if (!$history): ?><div class="card-body text-muted small">No history recorded yet.</div>
  <?php else: ?><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Date / Time</th><th>Action</th><th>User / Role</th><th>Status</th><th>Comments</th></tr></thead><tbody>
    <?php foreach ($history as $item): ?><tr><td><?= e(date('M j, Y g:i A', strtotime($item['created_at']))) ?></td><td><?= e($item['action']) ?></td><td><?= e($item['full_name'] ?? 'Former user') ?><small class="d-block text-muted"><?= e($item['user_role'] ?? '') ?></small></td><td><?= e(($item['previous_status'] ?: '—') . ' → ' . ($item['new_status'] ?: '—')) ?></td><td><?= nl2br(e($item['comments'] ?? '')) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
</section>
</div></div>
<?php
$extraJs = [APP_URL . '/assets/js/report-draft-edit.js?v=' . (int)@filemtime(__DIR__ . '/../../assets/js/report-draft-edit.js')];
include __DIR__ . '/../../layouts/footer.php';
?>
