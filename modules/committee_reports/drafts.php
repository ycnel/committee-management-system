<?php
/**
 * modules/committee_reports/drafts.php
 * ------------------------------------------------------------------
 * Committee Report list, creation, and review entry point.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/report_drafts.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]); // read/oversight only; canManage() still gates writes

$pageTitle = 'Committee Reports';
$activeMenu = 'committee_reports';
$pdo = db();
$selectedCommitteeId = (int)($_GET['committee_id'] ?? 0);
$committees = $pdo->query(
    "SELECT c.committee_id, c.committee_name,
            (SELECT GROUP_CONCAT(j2.jurisdiction_name
                                 ORDER BY (j2.jurisdiction_id = c.jurisdiction_id) DESC, j2.jurisdiction_name
                                 SEPARATOR ', ')
             FROM jurisdictions j2
             WHERE j2.status = 'Active'
               AND (j2.category = c.committee_name OR j2.jurisdiction_id = c.jurisdiction_id)
            ) AS jurisdiction_name,
            GROUP_CONCAT(
                CASE WHEN cm.member_role = 'Chairperson' THEN u.full_name END
                ORDER BY u.full_name SEPARATOR ', '
            ) AS chairperson_name,
            GROUP_CONCAT(
                CONCAT(u.full_name, ' (', cm.member_role,
                       IF(cm.political_group IS NULL, '', CONCAT(', ', cm.political_group)), ')')
                ORDER BY FIELD(cm.member_role, 'Chairperson', 'Vice Chairperson', 'Member'), u.full_name
                SEPARATOR ', '
            ) AS member_names
     FROM committees c
     LEFT JOIN committee_members cm ON cm.committee_id = c.committee_id AND cm.status = 'Active'
     LEFT JOIN users u ON u.id = cm.user_id
     GROUP BY c.committee_id, c.committee_name
     ORDER BY c.committee_name"
)->fetchAll();
$committeeJurisdictions = [];
$jurisdictionRows = $pdo->query(
    "SELECT c.committee_id, j.jurisdiction_id, j.jurisdiction_name
     FROM committees c
     INNER JOIN jurisdictions j ON j.status = 'Active'
       AND (j.jurisdiction_id = c.jurisdiction_id OR j.category = c.committee_name)
     ORDER BY c.committee_id, (j.jurisdiction_id = c.jurisdiction_id) DESC, j.jurisdiction_name"
)->fetchAll();
foreach ($jurisdictionRows as $row) {
    $committeeJurisdictions[(int)$row['committee_id']][] = [
        'id' => (int)$row['jurisdiction_id'],
        'name' => $row['jurisdiction_name'],
    ];
}
foreach ($committees as &$committee) {
    $committee['jurisdictions'] = $committeeJurisdictions[(int)$committee['committee_id']] ?? [];
}
unset($committee);
include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper"><div><?php include __DIR__ . '/../../layouts/sidebar.php'; ?></div><div class="main-content committee-reports-page"><?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
<div class="breadcrumb-bar committee-reports-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
  <div><h5 class="mb-0"><i class="bi bi-journal-richtext text-primary"></i> Committee Reports</h5>
    <small class="text-muted">Manage report drafts and reviews. AI-generated text is editable and requires human review.</small>
  </div>
  <div class="committee-reports-actions d-flex gap-2 flex-wrap">
    <a href="export_csv.php?records=1" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv"></i> CSV</a>
    <a href="export_excel.php?records=1" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel</a>
    <a href="operational.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bar-chart"></i> Operational Reports</a>
    <?php if (canManage()): ?>
      <button type="button" class="btn btn-dark btn-sm" id="btnNewDraft"><i class="bi bi-plus-lg"></i> New Committee Report</button>
    <?php endif; ?>
  </div>
</div>

<div class="card committee-reports-filter mb-3"><div class="card-body">
  <div class="row g-2 align-items-end">
    <div class="col-md-5">
      <label class="form-label small mb-1">Committee</label>
      <select class="form-select form-select-sm" id="filterCommittee">
        <option value="0">All Committees</option>
        <?php foreach ($committees as $c): ?><option value="<?= (int)$c['committee_id'] ?>" <?= $selectedCommitteeId === (int)$c['committee_id'] ? 'selected' : '' ?>><?= e($c['committee_name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-5">
      <label class="form-label small mb-1">Status</label>
      <select class="form-select form-select-sm" id="filterStatus">
        <option value="all">All (except Archived)</option>
        <option value="Draft">Draft</option>
        <option value="AI-Assisted Draft">AI-Assisted Draft</option>
        <option value="For Review">For Review</option>
        <option value="Under Review">Under Review</option>
        <option value="Returned for Revision">Returned for Revision</option>
        <option value="Approved">Approved</option>
        <option value="Final">Final</option>
        <option value="Archived">Archived</option>
      </select>
    </div>
    <div class="col-md-2"><button type="button" class="btn btn-primary btn-sm w-100" id="btnApplyFilter">Apply</button></div>
  </div>
</div></div>

<div class="card committee-reports-list"><div class="card-body p-0" id="draftsListBody"><p class="text-muted small p-3 mb-0">Loading…</p></div></div>

<div class="modal fade" id="reportPrintModal" tabindex="-1" aria-labelledby="reportPrintModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportPrintModalTitle">Committee Report Preview</h5>
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

<!-- New/Generate AI Draft modal -->
<div class="modal fade" id="newDraftModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="newDraftModalTitle">New Draft</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label small">Committee</label>
          <select class="form-select form-select-sm" id="ndCommittee">
            <option value="0">Select Committee</option>
            <?php foreach ($committees as $c): ?>
              <option value="<?= (int)$c['committee_id'] ?>"
                      <?= $selectedCommitteeId === (int)$c['committee_id'] ? 'selected' : '' ?>
                      data-chair="<?= e($c['chairperson_name'] ?? '') ?>"
                      data-jurisdiction="<?= e($c['jurisdiction_name'] ?? '') ?>"
                      data-jurisdictions="<?= e(json_encode($c['jurisdictions'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?: '[]') ?>"
                      data-members="<?= e($c['member_names'] ?? '') ?>"><?= e($c['committee_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label small" for="ndJurisdiction">Jurisdiction</label>
          <select class="form-select form-select-sm" id="ndJurisdiction" disabled required>
            <option value="">Select a committee first</option>
          </select>
          <div class="form-text">Choose one jurisdiction linked to the selected committee.</div>
        </div>
        <div class="small text-muted border rounded p-2 mb-2" id="ndCommitteeContext">
          Select a committee to load its chairperson, members, and jurisdiction.
        </div>
        <div class="mb-2"><label class="form-label small">Report Type</label>
          <select class="form-select form-select-sm" id="ndType">
            <option value="Committee">Committee</option><option value="Workload">Workload</option>
            <option value="Performance">Performance</option><option value="Monthly">Monthly</option><option value="Annual">Annual</option>
          </select>
        </div>
        <div class="mb-2"><label class="form-label small" for="ndTitle">Report Title <span class="text-danger">*</span></label>
          <input type="text" class="form-control form-control-sm" id="ndTitle" maxlength="255" required>
          <div class="form-text">Required. Create the draft first, then choose “Generate from CMAS Data” in the report editor.</div>
        </div>
        <div id="ndLegislativeFields">
        <div class="mb-2"><label class="form-label small">Proposed Ordinance / Legislative Measure <span class="text-muted">(optional)</span></label>
          <input type="text" class="form-control form-control-sm" id="ndMeasureTitle" maxlength="500" placeholder="Enter the measure title if available">
        </div>
        <div class="row g-2 mb-2">
          <div class="col-md-6"><label class="form-label small">Official Reference / Measure No.</label><input type="text" class="form-control form-control-sm" id="ndMeasureNo" maxlength="150" placeholder="Enter if assigned"><div class="form-text">Use the number shown on the official source document.</div></div>
          <div class="col-md-6"><label class="form-label small">Date Referred</label><input type="date" class="form-control form-control-sm" id="ndDateReferred"></div>
          <div class="col-12"><label class="form-label small">Referred By</label><input type="text" class="form-control form-control-sm" id="ndReferredBy" maxlength="255" placeholder="Council Member, City Council, Mayor’s Office, etc."><div class="form-text">Enter the person or office that formally sent the matter to the Committee.</div></div>
          <div class="col-12"><label class="form-label small">Subject / Title</label><input type="text" class="form-control form-control-sm" id="ndSubject" maxlength="500" placeholder="Defaults to report title if blank"></div>
        </div>
        </div>
        <div class="row g-2" id="ndDateRangeWrap">
          <div class="col-6"><label class="form-label small">From</label><input type="date" class="form-control form-control-sm" id="ndDateFrom"></div>
          <div class="col-6"><label class="form-label small">To</label><input type="date" class="form-control form-control-sm" id="ndDateTo"></div>
        </div>
      </div>
      <div class="modal-footer">
        <span class="small text-muted me-auto">Scroll this form to reach all fields and actions.</span>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-dark btn-sm" id="btnSubmitNewDraft">Create Draft</button>
      </div>
    </div>
  </div>
</div>

</div></div>
<?php
$extraJs = [APP_URL . '/assets/js/report-drafts.js'];
include __DIR__ . '/../../layouts/footer.php';
?>
