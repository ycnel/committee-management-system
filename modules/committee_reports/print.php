<?php
/**
 * modules/committee_reports/print.php
 * ------------------------------------------------------------------
 * Print-friendly version of the selected report, honoring the same
 * type/committee filters as index.php. Mirrors modules/issues/print.php.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/report_drafts.php';
require_once __DIR__ . '/report_data.php';
require_once __DIR__ . '/report_narrative.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]); // read/oversight only; canManage() still gates writes

if ((int)($_GET['draft_id'] ?? 0) > 0) {
  $draftId = (int)$_GET['draft_id'];
  $draft = getReportDraft(db(), $draftId);
  if (!$draft) {
    http_response_code(404);
    exit('Committee report not found.');
  }
  $draftSections = [
    'matter_referred' => 'I. MATTER REFERRED',
    'committee_proceedings' => 'II. COMMITTEE PROCEEDINGS',
    'findings' => 'III. FINDINGS',
    'discussion_analysis' => 'IV. DISCUSSION / ANALYSIS',
    'conclusion' => 'V. CONCLUSION',
    'recommendations' => 'VI. RECOMMENDATION',
    'legislative_history' => 'VII. LEGISLATIVE HISTORY',
    'committee_amendments' => 'VIII. COMMITTEE AMENDMENTS',
    'individual_views' => 'IX. INDIVIDUAL / MINORITY / SUPPLEMENTAL VIEWS',
    'committee_action' => 'COMMITTEE ACTION',
    'signature_details' => 'SIGNATURES AND CONCURRENCE',
    'appendices' => 'APPENDICES',
  ];
  $isFinal = $draft['status'] === 'Final';
  ?>
  <!DOCTYPE html>
  <html lang="en"><head><meta charset="UTF-8"><title><?= e($draft['report_number']) ?> - Committee Report</title>
  <link href="<?= e(vendorAsset('bootstrap/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css')) ?>" rel="stylesheet">
  <style>
    body{font-family:Georgia,"Times New Roman",serif;color:#171717;padding:28px;font-size:12pt}
    .report-document{max-width:850px;margin:0 auto;position:relative}
    .draft-watermark{position:fixed;inset:40% 0 auto;text-align:center;transform:rotate(-25deg);font:700 76px Arial,sans-serif;color:rgba(180,30,30,.10);pointer-events:none}
    .official-header{text-align:center;border-bottom:2px solid #222;padding-bottom:16px;margin-bottom:20px}
    .official-header img{width:74px;height:74px;object-fit:contain;margin-bottom:8px}
    .official-header strong,.official-header div{display:block;letter-spacing:.04em}
    .official-header h2{font-size:17pt;margin:14px 0 5px;font-weight:700}
    .official-header h3{font-size:15pt;margin:4px 0;font-weight:700}
    .meta{border-bottom:1px solid #999;padding:10px 0;margin-bottom:14px;display:grid;grid-template-columns:1fr 1fr;gap:7px 16px}
    .section{margin:18px 0;break-inside:avoid}
    .section h4{font:700 11pt Arial,sans-serif;text-transform:uppercase;margin-bottom:7px}
    .section p{white-space:pre-wrap;line-height:1.55;margin:0}
    .recommendation{font-weight:700;border:1px solid #555;padding:10px}
    .draft-note{font:12px Arial,sans-serif;text-align:center;color:#9b1c1c;margin-bottom:14px}
    @media print{body{padding:0}.no-print{display:none!important}.report-document{max-width:none}.draft-watermark{position:fixed}}
  </style></head><body>
  <div class="no-print text-end mb-3"></div>
  <?php if (!$isFinal): ?><div class="draft-watermark"><?= e(strtoupper($draft['status'])) ?></div><div class="draft-note">DRAFT — NOT AN OFFICIAL OR APPROVED COMMITTEE REPORT</div><?php endif; ?>
  <main class="report-document">
    <header class="official-header">
      <img src="<?= e(APP_URL) ?>/assets/img/Ph_seal_ncr_manila.svg" alt="Manila City Seal">
      <strong>REPUBLIC OF THE PHILIPPINES</strong><strong>CITY OF MANILA</strong><strong>SANGGUNIANG PANLUNGSOD</strong>
      <h3>COMMITTEE ON <?= e(strtoupper($draft['committee_name'] ?? '')) ?></h3>
      <h2>COMMITTEE REPORT NO. <?= e($draft['report_number'] ?? '') ?></h2>
    </header>
    <div class="meta">
      <?php foreach ([
          'Date Referred' => $draft['date_referred'] ?: '',
          'Referred By' => $draft['referred_by'] ?: '',
          'Reference / Measure No.' => $draft['reference_measure_no'] ?: '',
          'CMAS Internal Reference' => $draft['internal_reference_no'] ?: '',
          'Committee Action Date' => $draft['committee_action_date'] ?: '',
          'Subject / Title' => $draft['subject_title'] ?: $draft['report_title'],
          'Proposed Ordinance / Legislative Measure' => $draft['proposed_ordinance_title'] ?: '',
          'Jurisdiction' => $draft['jurisdiction_name'] ?: '',
          'Prepared By' => $draft['created_by_name'] ?: '',
      ] as $label => $value): ?>
        <?php if ($value !== ''): ?><div><strong><?= e($label) ?>:</strong> <?= e((string)$value) ?></div><?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php foreach ($draftSections as $key => $heading): ?>
      <?php $text = trim((string)($draft[$key] ?? '')); ?>
      <section class="section <?= $key === 'recommendations' ? 'recommendation' : '' ?>">
        <h4><?= e($heading) ?><?= $key === 'recommendations' && $draft['recommendation_type'] ? ' — ' . e($draft['recommendation_type']) : '' ?></h4>
        <p><?= $text !== '' ? e($text) : '<span class="text-muted">Not provided in the report draft.</span>' ?></p>
      </section>
    <?php endforeach; ?>
    <?php if ($draft['ai_generated']): ?><p class="small text-muted mt-4">AI-assisted content is a draft and requires human verification and approval.</p><?php endif; ?>
    <footer class="border-top pt-2 mt-4 small text-muted">Generated by CMAS on <?= e(date('F j, Y g:i A')) ?> · Status: <?= e($draft['status']) ?></footer>
  </main>
  </body></html>
  <?php
  exit;
}

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
