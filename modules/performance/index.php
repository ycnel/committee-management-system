<?php
/** Committee Assignment Monitoring: factual assignment distribution only. */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../committee_reports/report_data.php';
require_once __DIR__ . '/../committee_reports/report_narrative.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]);
$pageTitle = 'Committee Assignment Monitoring';
$activeMenu = 'performance';
$pdo = db();
$selectedCommitteeId = (int)($_GET['committee_id'] ?? 0);
$dateFrom = clean($_GET['date_from'] ?? '');
$dateTo = clean($_GET['date_to'] ?? '');
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : null;
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : null;
$report = buildCommitteeReportData($pdo, 'performance', $selectedCommitteeId, $dateFrom, $dateTo);
$committees = $pdo->query("SELECT committee_id, committee_name FROM committees WHERE status = 'Active' ORDER BY committee_name")->fetchAll();
$totalAssignments = (int)($report['overall_metrics']['total_assignments'] ?? 0);
include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
    <div class="breadcrumb-bar performance-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div><span class="performance-eyebrow">Assignment distribution overview</span><h5 class="mb-1"><i class="bi bi-diagram-3 text-primary"></i> Committee Assignment Monitoring</h5><small class="text-muted">CMAS monitors who is assigned to committee work, not legislative completion.</small></div>
      <span class="performance-live-badge"><span></span> Updated from assignment data</span>
    </div>
    <div class="performance-dashboard">
      <div class="performance-stat-grid"><div class="card stat-card performance-stat"><div class="performance-stat-icon performance-stat-icon--blue"><i class="bi bi-list-check"></i></div><span>Total assignments</span><strong><?= number_format($totalAssignments) ?></strong></div></div>
      <div class="row g-3 mb-3">
        <div class="col-xl-8"><div class="card hero-card performance-chart-card h-100"><div class="card-body"><div class="performance-card-heading"><div><span class="performance-kicker">Committee comparison</span><h2>Assignment distribution</h2></div></div><div class="performance-bar-wrap"><canvas id="assignmentDistributionChart"></canvas></div></div></div></div>
        <div class="col-xl-4"><div class="card hero-card performance-chart-card h-100"><div class="card-body"><div class="performance-card-heading"><div><span class="performance-kicker">Jurisdiction coverage</span><h2>Assignments by jurisdiction</h2></div></div><div class="small text-muted">Counts are based on recorded assignments and defined committee jurisdictions.</div><div class="mt-3"><?php foreach ($report['jurisdiction_counts'] ?? [] as $name => $count): ?><div class="d-flex justify-content-between border-bottom py-2"><span><?= e($name) ?></span><strong><?= (int)$count ?></strong></div><?php endforeach; ?><?php if (empty($report['jurisdiction_counts'])): ?><p class="text-muted mb-0">Insufficient assignment data available for analysis.</p><?php endif; ?></div></div></div></div>
      </div>
      <div class="card hero-card performance-detail-card mt-3"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="performance-detail-heading"><span class="performance-kicker">Assignment balance</span><h2>Member Assignment Load</h2></div><form method="get" class="d-flex flex-wrap gap-2 align-items-end"><div><label class="form-label small mb-1">Committee</label><select name="committee_id" class="form-select form-select-sm"><option value="0">All Committees</option><?php foreach ($committees as $option): ?><option value="<?= (int)$option['committee_id'] ?>" <?= $selectedCommitteeId === (int)$option['committee_id'] ? 'selected' : '' ?>><?= e($option['committee_name']) ?></option><?php endforeach; ?></select></div><div><label class="form-label small mb-1">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom ?? '') ?>"></div><div><label class="form-label small mb-1">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo ?? '') ?>"></div><button class="btn btn-primary btn-sm"><i class="bi bi-filter"></i> Apply</button></form></div>
        <?php if (empty($report['performance_details'])): ?><p class="text-muted mb-0">Insufficient assignment data available for analysis.</p><?php else: foreach ($report['performance_details'] as $committee): $maxMemberAssignments = max(array_column($committee['members'], 'assignment_count') ?: [0]); ?><section class="assignment-balance-panel mb-3"><div class="assignment-balance-header"><div class="assignment-balance-title"><span class="assignment-balance-icon"><i class="bi bi-diagram-3"></i></span><div><h3><?= e($committee['committee_name']) ?></h3><span><i class="bi bi-geo-alt"></i> <?= e($committee['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?></span></div></div><div class="assignment-balance-total"><strong><?= (int)$committee['assignment_count'] ?></strong><span>assignments</span></div></div><div class="assignment-balance-caption"><span>Member assignment load</span><span><?= count($committee['members']) ?> active member<?= count($committee['members']) === 1 ? '' : 's' ?></span></div><?php if (empty($committee['members'])): ?><p class="text-muted small mb-0">No active members are assigned to this committee.</p><?php else: ?><div class="assignment-load-list"><?php foreach ($committee['members'] as $member): $assignmentCount = (int)$member['assignment_count']; $barWidth = $maxMemberAssignments > 0 ? round(($assignmentCount / $maxMemberAssignments) * 100) : 0; ?><div class="assignment-load-row"><div class="assignment-load-identity"><span class="assignment-load-avatar"><i class="bi bi-person"></i></span><span><strong><?= e($member['member_name']) ?></strong><small><?= e($member['member_role']) ?></small></span></div><div class="assignment-load-meter"><div class="assignment-load-track"><span style="width: <?= $barWidth ?>%"></span></div><span class="assignment-load-count"><?= $assignmentCount ?></span></div></div><?php endforeach; ?></div><?php endif; ?></section><?php endforeach; endif; ?>
      </div></div>
    </div>
  </div>
</div>
<?php $extraJs = []; include __DIR__ . '/../../layouts/footer.php'; ?>
<script>
new Chart(document.getElementById('assignmentDistributionChart'), { type: 'bar', data: { labels: <?= json_encode(array_column($report['performance_details'] ?? [], 'committee_name')) ?>, datasets: [{ label: 'Assignments', data: <?= json_encode(array_column($report['performance_details'] ?? [], 'assignment_count')) ?>, backgroundColor: '#2f6fed', borderRadius: 5 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } }, responsive: true, maintainAspectRatio: false } });
</script>
