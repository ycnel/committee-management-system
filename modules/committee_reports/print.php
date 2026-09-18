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
requireRole([ROLE_ADMIN, ROLE_STAFF]);

$pdo = db();
$type = in_array($_GET['type'] ?? '', ['committee', 'workload', 'performance'], true) ? $_GET['type'] : 'committee';
$committeeId = (int)($_GET['committee_id'] ?? 0);

$labels = ['committee' => 'Committee Report', 'workload' => 'Workload Report', 'performance' => 'Performance Report'];
$title = $labels[$type];

$data = buildCommitteeReportData($pdo, $type, $committeeId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= e($title) ?> - Print</title>
<link href="<?= e(vendorAsset('bootstrap/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css')) ?>" rel="stylesheet">
<style>
  body { padding: 30px; font-size: 14px; }
  .print-header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #0b3d6e; padding-bottom: 14px; }
  table th { background: #f4f6f9; }
  @media print { .no-print { display: none; } }
</style>
</head>
<body onload="window.print()">
<div class="no-print text-end mb-3">
  <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="print-header">
  <h4 class="mb-0"><?= e(APP_NAME) ?></h4>
  <div class="text-muted"><?= e($title) ?></div>
  <div class="small text-muted">Generated on <?= date('F j, Y g:i A') ?></div>
</div>

<table class="table table-bordered table-sm">
  <thead>
    <tr><th>#</th><?php foreach ($data['headers'] as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr>
  </thead>
  <tbody>
    <?php if (empty($data['rows'])): ?>
      <tr><td colspan="<?= count($data['headers']) + 1 ?>" class="text-center text-muted">No data matches the selected filters.</td></tr>
    <?php endif; ?>
    <?php foreach ($data['rows'] as $i => $row): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <?php foreach ($row as $cell): ?><td><?= e((string)$cell) ?></td><?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<p class="text-muted small mt-4">Total: <?= count($data['rows']) ?> record(s)</p>
</body>
</html>
