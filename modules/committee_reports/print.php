<?php
/**
 * modules/committee_reports/print.php
 * ------------------------------------------------------------------
 * Print-friendly version of the selected report, honoring the same
 * type/committee filters as index.php. Mirrors modules/issues/print.php.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/report_data.php';
require_once __DIR__ . '/report_narrative.php';
requireRole([ROLE_ADMIN, ROLE_STAFF]);

$pdo = db();
$type = in_array($_GET['type'] ?? '', ['committee', 'workload', 'performance'], true) ? $_GET['type'] : 'committee';
$committeeId = (int)($_GET['committee_id'] ?? 0);
$dateFrom = clean($_GET['date_from'] ?? '');
$dateTo = clean($_GET['date_to'] ?? '');
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : null;
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : null;

$labels = ['committee' => 'Committee Report', 'workload' => 'Workload Report', 'performance' => 'Performance Report'];
$title = $labels[$type];

$data = buildCommitteeReportData($pdo, $type, $committeeId, $dateFrom, $dateTo);
$narrative = buildReportNarrative($pdo, $type, $data, $committeeId);
$committeeName = 'All Committees';
if ($committeeId > 0) {
  $committeeStmt = $pdo->prepare('SELECT committee_name FROM committees WHERE committee_id = :id');
  $committeeStmt->execute([':id' => $committeeId]);
  $committeeName = (string)($committeeStmt->fetchColumn() ?: 'Selected Committee');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= e($title) ?> - Print</title>
<link href="<?= e(vendorAsset('bootstrap/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css')) ?>" rel="stylesheet">
<style>
  body { padding: 30px; font-size: 13px; color: #222; counter-reset: page; }
  .report-document { max-width: 980px; margin: 0 auto; }
  .print-header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #0B2E59; padding-bottom: 14px; }
  .print-header img { width: 76px; height: 76px; object-fit: contain; }
  .government { color: #0B2E59; font-size: 12px; font-weight: 700; letter-spacing: .08em; }
  .council { font-size: 14px; }
  .print-title { color: #0B2E59; letter-spacing: .04em; margin-top: 14px; }
  .meta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; border-bottom: 1px solid #d9dee7; padding-bottom: 14px; margin-bottom: 18px; }
  .section-title { color: #0B2E59; font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; margin-top: 18px; }
  .narrative { line-height: 1.6; }
  table th { background: #eef2f7; color: #0B2E59; }
  .certification { border-top: 1px solid #d9dee7; margin-top: 20px; padding-top: 10px; color: #6b7280; font-size: 11px; font-style: italic; }
  .print-footer { border-top: 1px solid #d9dee7; margin-top: 24px; padding-top: 8px; color: #6b7280; font-size: 10px; display: flex; justify-content: space-between; }
  .page-number::after { content: counter(page); }
  @media print { .no-print { display: none; } .print-footer { position: fixed; bottom: 10px; left: 30px; right: 30px; } }
</style>
</head>
<body onload="window.print()">
<div class="no-print text-end mb-3">
  <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="report-document">
<div class="print-header">
  <img src="<?= e(APP_URL) ?>/assets/img/Ph_seal_ncr_manila.svg" alt="Manila Seal">
  <div class="government">REPUBLIC OF THE PHILIPPINES</div>
  <div class="government">CITY OF MANILA</div>
  <div class="government council">CITY COUNCIL OF MANILA</div>
  <h2 class="print-title"><?= e(strtoupper($title)) ?></h2>
  <div class="small text-muted">Generated on <?= date('F j, Y g:i A') ?> &middot; Draft for review</div>
</div>

<?php $tableHeaders = $data['export_headers'] ?? $data['headers']; $tableRows = $data['export_rows'] ?? $data['rows']; ?>
<div class="meta">
  <div><strong>Committee:</strong> <?= e($committeeName) ?></div>
  <div><strong>Records:</strong> <?= count($tableRows) ?></div>
  <div><strong>Prepared By:</strong> <?= e(currentUser()['full_name'] ?? 'CMAS User') ?></div>
  <?php if ($type === 'performance'): ?><div><strong>Reporting Period:</strong> <?= e(($dateFrom ?? 'Beginning') . ' to ' . ($dateTo ?? 'Current')) ?></div><?php endif; ?>
</div>

<?php foreach ([
    'executive_summary' => 'Executive Summary',
    'analysis' => $type === 'committee' ? 'Findings / Observations' : ($type === 'workload' ? 'Workload Analysis' : 'Performance Analysis'),
    'observations' => 'Observations',
    'recommendations' => 'Recommendations',
    'conclusion' => 'Conclusion',
] as $key => $heading): ?>
  <div class="section-title"><?= e($heading) ?></div>
  <p class="narrative"><?= nl2br(e($narrative[$key] ?? 'Insufficient data available for this section.')) ?></p>
<?php endforeach; ?>

<div class="section-title">Supporting Data</div>

<table class="table table-bordered table-sm">
  <thead>
    <tr><th>#</th><?php foreach ($tableHeaders as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr>
  </thead>
  <tbody>
    <?php if (empty($tableRows)): ?>
      <tr><td colspan="<?= count($tableHeaders) + 1 ?>" class="text-center text-muted">No data matches the selected filters.</td></tr>
    <?php endif; ?>
    <?php foreach ($tableRows as $i => $row): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <?php foreach ($row as $cell): ?><td><?= e((string)$cell) ?></td><?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<p class="text-muted small mt-4">Total: <?= count($tableRows) ?> record(s)</p>
<div class="certification">Generated by CMAS. This report is a draft for review and is not an official legislative decision.</div>
<div class="print-footer"><span><?= e($title) ?> &middot; Generated by CMAS</span><span>Page <span class="page-number"></span></span></div>
</div>
</body>
</html>
