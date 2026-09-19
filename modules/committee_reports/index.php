<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/report_data.php';
require_once __DIR__ . '/report_narrative.php';
requireRole([ROLE_ADMIN, ROLE_STAFF]);
$pageTitle = 'Committee Reports';
$activeMenu = 'committee_reports';
$pdo = db();
$type = in_array($_GET['type'] ?? '', ['committee', 'workload', 'performance'], true) ? $_GET['type'] : 'committee';
$committeeId = (int)($_GET['committee_id'] ?? 0);
$dateFrom = clean($_GET['date_from'] ?? '');
$dateTo = clean($_GET['date_to'] ?? '');
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : null;
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : null;
$committees = $pdo->query('SELECT committee_id, committee_name FROM committees ORDER BY committee_name')->fetchAll();
$data = buildCommitteeReportData($pdo, $type, $committeeId, $dateFrom, $dateTo);
// Render instantly with the factual fallback — the AI narrative is
// fetched asynchronously by ajax_narrative.php and swapped in below.
$narrative = reportNarrativeFallback($type, $data);
$labels = ['committee' => 'Committee Report', 'workload' => 'Workload Report', 'performance' => 'Assignment Monitoring Report'];
include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper"><div><?php include __DIR__ . '/../../layouts/sidebar.php'; ?></div><div class="main-content"><?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
<div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2"><div><h5 class="mb-0"><i class="bi bi-file-earmark-text text-primary"></i> Committee Reporting</h5><small class="text-muted">Assignment distribution, workload, jurisdiction, and committee reports.</small></div><div class="d-flex gap-2 no-print"><a class="btn btn-outline-secondary btn-sm" target="_blank" href="print.php?type=<?= e($type) ?>&committee_id=<?= (int)$committeeId ?>"><i class="bi bi-printer"></i> Print</a><a class="btn btn-outline-secondary btn-sm" href="export_pdf.php?type=<?= e($type) ?>&committee_id=<?= (int)$committeeId ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a><a class="btn btn-outline-secondary btn-sm" href="export_excel.php?type=<?= e($type) ?>&committee_id=<?= (int)$committeeId ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a></div></div>
<div class="card mb-3"><div class="card-body py-3"><form method="get" class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label small mb-1">Report Type</label><select name="type" class="form-select form-select-sm"><?php foreach ($labels as $key => $label): ?><option value="<?= e($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label small mb-1">Committee</label><select name="committee_id" class="form-select form-select-sm"><option value="0">All Committees</option><?php foreach ($committees as $committee): ?><option value="<?= (int)$committee['committee_id'] ?>" <?= $committeeId === (int)$committee['committee_id'] ? 'selected' : '' ?>><?= e($committee['committee_name']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><label class="form-label small mb-1">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom ?? '') ?>"></div><div class="col-md-2"><label class="form-label small mb-1">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo ?? '') ?>"></div><div class="col-12"><button class="btn btn-primary btn-sm"><i class="bi bi-filter"></i> Apply</button></div></form></div></div>
<div class="card report-preview"><div class="card-body"><div class="report-letterhead text-center"><div class="report-government">CITY COUNCIL OF MANILA</div><h4 class="report-title"><?= e(strtoupper($labels[$type])) ?></h4><div class="small text-muted">Generated <?= e(date('F j, Y')) ?> · Draft for review</div></div><div class="row g-3 report-meta mb-4"><div class="col-md-4"><strong>Report Type:</strong> <?= e($labels[$type]) ?></div><div class="col-md-4"><strong>Records:</strong> <?= count($data['rows']) ?></div><div class="col-md-4"><strong>Prepared By:</strong> <?= e(currentUser()['full_name'] ?? 'CMAS User') ?></div></div><div class="report-narrative-section"><h6>Assignment Monitoring Summary <span class="badge bg-success-subtle text-success border border-success-subtle no-print d-none" id="reportAiBadge"><i class="bi bi-stars"></i> Gemini AI</span></h6><p id="reportSummary"><?= nl2br(e($narrative['executive_summary'] ?? 'Insufficient data available for this section.')) ?></p></div><div class="report-narrative-section"><h6>Observations</h6><p id="reportObservations"><?= nl2br(e($narrative['observations'] ?? 'Insufficient data available for this section.')) ?></p></div><h6 class="report-section-heading">Supporting Assignment Data</h6><div class="table-responsive report-table-wrap"><table class="table table-sm align-middle mb-0 report-data-table"><thead><tr><?php foreach ($data['headers'] as $header): ?><th><?= e($header) ?></th><?php endforeach; ?></tr></thead><tbody><?php if (empty($data['rows'])): ?><tr><td colspan="<?= count($data['headers']) ?>" class="text-center text-muted py-4">Insufficient assignment data available for analysis.</td></tr><?php else: foreach ($data['rows'] as $row): ?><tr><?php foreach ($row as $cell): ?><td><?= e((string)$cell) ?></td><?php endforeach; ?></tr><?php endforeach; endif; ?></tbody></table></div><div class="report-certification">Generated by CMAS. This report monitors assignment distribution and management; it does not assess legislative completion or output quality.</div></div></div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const params = new URLSearchParams({
    type: '<?= e($type) ?>',
    committee_id: '<?= (int)$committeeId ?>',
    date_from: '<?= e($dateFrom ?? '') ?>',
    date_to: '<?= e($dateTo ?? '') ?>'
  });
  fetch('ajax_narrative.php?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success || !data.ai) return; // keep fallback text when AI is unavailable
      if (data.summary) document.getElementById('reportSummary').textContent = data.summary;
      if (data.observations) document.getElementById('reportObservations').textContent = data.observations;
      document.getElementById('reportAiBadge').classList.remove('d-none');
    })
    .catch(function () {});
});
</script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
