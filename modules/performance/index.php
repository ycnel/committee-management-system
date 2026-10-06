<?php
/** Committee Assignment Monitoring: factual assignment distribution only. */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../committee_reports/report_data.php';
require_once __DIR__ . '/../committee_reports/report_narrative.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]);
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

// ---- KPI summary (same filters as the report) --------------------------
$kpiWhere = '1=1';
$kpiParams = [];
if ($selectedCommitteeId > 0) { $kpiWhere .= ' AND cm.committee_id = :cid'; $kpiParams[':cid'] = $selectedCommitteeId; }
if ($dateFrom !== null) { $kpiWhere .= ' AND wa.assigned_date >= :dfrom'; $kpiParams[':dfrom'] = $dateFrom; }
if ($dateTo !== null)   { $kpiWhere .= ' AND wa.assigned_date <= :dto';   $kpiParams[':dto'] = $dateTo; }

$kpiStmt = $pdo->prepare(
    "SELECT COUNT(*) AS total, SUM(wa.status = 'Completed') AS done, SUM(wa.status = 'Overdue') AS overdue
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     WHERE $kpiWhere"
);
$kpiStmt->execute($kpiParams);
$kpi = $kpiStmt->fetch();
$totalAssignments = (int)$kpi['total'];
$completionRate = $totalAssignments > 0 ? round(((int)$kpi['done'] / $totalAssignments) * 100) : 0;

$entityTotals = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM committees WHERE status = 'Active') AS committees,
            (SELECT COUNT(*) FROM committee_members WHERE status = 'Active') AS members"
)->fetch();

// Per-member overdue markers for the load rows (same filters).
$overdueStmt = $pdo->prepare(
    "SELECT cm.committee_member_id, COUNT(*) AS n
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     WHERE wa.status = 'Overdue' AND $kpiWhere
     GROUP BY cm.committee_member_id"
);
$overdueStmt->execute($kpiParams);
$memberOverdue = $overdueStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$perfPalette = ['#0B2E59', '#D4AF37', '#C62828', '#1F4E85', '#8A6D1D', '#4B5563'];
function perfInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    return strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1)) ?: '?';
}
function perfColor(array $palette, $seed): string
{
    return $palette[abs(crc32((string)$seed)) % count($palette)];
}
/** Minutes -> short human duration ("2h 15m", "3d 4h"), for average response time. */
function formatResponseDuration(int $minutes): string
{
    if ($minutes < 60) return $minutes . 'm';
    $hours = intdiv($minutes, 60);
    if ($hours < 24) return $hours . 'h ' . ($minutes % 60) . 'm';
    $days = intdiv($hours, 24);
    return $days . 'd ' . ($hours % 24) . 'h';
}

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
    <div class="breadcrumb-bar performance-heading performance-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="performance-hero-copy">
        <span class="performance-hero-icon"><i class="bi bi-bar-chart-line"></i></span>
        <div><span class="performance-eyebrow">Assignment distribution overview</span><h5 class="mb-1">Committee Assignment Monitoring</h5><small>Track workload, member capacity, and task responses across committees.</small></div>
      </div>
      <div class="performance-hero-actions d-flex gap-2 align-items-center">
        <span class="performance-live-badge"><span></span> Live assignment data</span>
      </div>
    </div>

    <div class="performance-dashboard">
      <!-- KPI row -->
      <div class="performance-stat-grid">
        <?php
        // Aggregate acceptance rate across every member currently in scope
        // (role-hierarchy revision §12) — a factual ratio of actual
        // Accept/Decline responses, never shown as a percentage when
        // there's nothing to compute it from.
        $totalAccepted = 0; $totalDeclined = 0;
        foreach ($report['performance_details'] ?? [] as $c) {
            foreach ($c['members'] ?? [] as $m) {
                $totalAccepted += (int)($m['accepted_count'] ?? 0);
                $totalDeclined += (int)($m['declined_count'] ?? 0);
            }
        }
        $totalResponses = $totalAccepted + $totalDeclined;
        $acceptanceRateDisplay = $totalResponses > 0 ? round(($totalAccepted / $totalResponses) * 100) . '%' : 'N/A';

        $kpiCards = [
          ['bi-diagram-3',              'blue',  (int)$entityTotals['committees'], 'Active Committees'],
          ['bi-people',                 'blue',  (int)$entityTotals['members'],    'Active Members'],
          ['bi-list-check',             'blue',  $totalAssignments,                'Assignments'],
          ['bi-check-circle',           'green', (int)$kpi['done'],                'Completed'],
          ['bi-exclamation-triangle',   'red',   (int)$kpi['overdue'],             'Overdue'],
          ['bi-percent',                'gold',  $completionRate . '%',            'Completion Rate'],
          ['bi-reply',                  'blue',  $acceptanceRateDisplay,            'Acceptance Rate'],
        ];
        foreach ($kpiCards as [$icon, $color, $value, $label]): ?>
          <div class="card stat-card performance-stat">
            <div class="performance-stat-icon performance-stat-icon--<?= e($color) ?>"><i class="bi <?= e($icon) ?>"></i></div>
            <span><?= e($label) ?></span>
            <strong><?= e(is_int($value) ? number_format($value) : $value) ?></strong>
            <?php if ($label === 'Acceptance Rate' && $totalResponses === 0): ?>
              <small class="performance-stat-note">Insufficient data</small>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-xl-8"><div class="card hero-card performance-chart-card performance-distribution-card h-100"><div class="card-body"><div class="performance-card-heading"><div><span class="performance-kicker">Committee comparison</span><h2>Assignment distribution</h2><p class="performance-chart-description">Current recorded assignments by committee</p></div><span class="performance-chart-mark"><i class="bi bi-bar-chart-steps"></i></span></div>
          <?php if (empty($report['performance_details'])): ?>
            <div class="text-center text-muted py-5"><i class="bi bi-bar-chart" style="font-size:32px;"></i><p class="small mt-2 mb-0">No assignment data yet. Assign tasks in Workload Distribution to see the comparison.</p></div>
          <?php else: ?>
            <div class="performance-chart-scroll">
              <div class="performance-bar-wrap" style="height: <?= max(286, count($report['performance_details']) * 28) ?>px"><canvas id="assignmentDistributionChart"></canvas></div>
            </div>
          <?php endif; ?>
        </div></div></div>
        <div class="col-xl-4"><div class="card hero-card performance-chart-card performance-jurisdiction-card h-100"><div class="card-body"><div class="performance-card-heading"><div><span class="performance-kicker">Jurisdiction coverage</span><h2>Assignments by jurisdiction</h2></div><span class="performance-chart-mark"><i class="bi bi-geo-alt"></i></span></div><div class="small text-muted">Recorded assignments grouped by jurisdiction.</div><div class="performance-jurisdiction-list mt-3">
          <?php if (empty($report['jurisdiction_counts'])): ?>
            <div class="text-center text-muted py-4"><i class="bi bi-geo-alt" style="font-size:28px;"></i><p class="small mt-2 mb-0">No assignments recorded under any jurisdiction yet.</p></div>
          <?php else: foreach ($report['jurisdiction_counts'] as $name => $count): ?>
            <div class="performance-jurisdiction-row"><span><i class="bi bi-geo-alt"></i><?= e($name) ?></span><strong><?= (int)$count ?></strong></div>
          <?php endforeach; endif; ?>
        </div></div></div></div>
      </div>

      <div class="card hero-card performance-detail-card mt-3"><div class="card-body">
        <div class="performance-detail-toolbar">
          <div class="performance-detail-heading"><span class="performance-kicker">Assignment balance</span><h2>Member Assignment Load</h2><p>Compare assigned work and response signals for active members.</p></div>
          <form method="get" class="performance-filter-form">
            <div><label class="form-label small mb-1">Committee</label><select name="committee_id" class="form-select form-select-sm"><option value="0">All Committees</option><?php foreach ($committees as $option): ?><option value="<?= (int)$option['committee_id'] ?>" <?= $selectedCommitteeId === (int)$option['committee_id'] ? 'selected' : '' ?>><?= e($option['committee_name']) ?></option><?php endforeach; ?></select></div>
            <div><label class="form-label small mb-1">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom ?? '') ?>"></div>
            <div><label class="form-label small mb-1">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo ?? '') ?>"></div>
            <button class="btn btn-primary btn-sm"><i class="bi bi-filter"></i> Apply filters</button>
          </form>
        </div>
        <?php if (empty($report['performance_details'])): ?>
          <div class="text-center text-muted py-5"><i class="bi bi-people" style="font-size:32px;"></i><p class="small mt-2 mb-0">No committees or assignments match the current filters.</p></div>
        <?php else: ?>
          <?php foreach ($report['performance_details'] as $committee): ?>
            <?php
              $maxMemberAssignments = max(array_column($committee['members'], 'assignment_count') ?: [0]);
              $memberListId = 'performance-members-' . (int)$committee['committee_id'];
            ?>
            <section class="assignment-balance-panel mb-3">
              <div class="assignment-balance-header">
                <div class="assignment-balance-title">
                  <span class="assignment-balance-icon"><i class="bi bi-diagram-3"></i></span>
                  <div>
                    <h3><?= e($committee['committee_name']) ?></h3>
                    <span><i class="bi bi-geo-alt"></i> <?= e($committee['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?></span>
                  </div>
                </div>
                <div class="assignment-balance-header-actions">
                  <div class="assignment-balance-total">
                    <strong><?= (int)$committee['assignment_count'] ?></strong>
                    <span>assignment<?= (int)$committee['assignment_count'] === 1 ? '' : 's' ?></span>
                  </div>
                  <button type="button" class="assignment-balance-toggle"
                          aria-expanded="false" aria-controls="<?= e($memberListId) ?>"
                          aria-label="Show assigned members for <?= e($committee['committee_name']) ?>"
                          title="Show assigned members">
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                  </button>
                </div>
              </div>
              <div class="assignment-balance-caption">
                <span>Member assignment load</span>
                <span><?= count($committee['members']) ?> active member<?= count($committee['members']) === 1 ? '' : 's' ?></span>
              </div>
              <div class="assignment-balance-members" id="<?= e($memberListId) ?>" hidden>
                <?php if (empty($committee['members'])): ?>
                  <p class="assignment-balance-empty text-muted small mb-0">No active members are assigned to this committee.</p>
                <?php else: ?>
                  <div class="assignment-load-list">
                    <?php foreach ($committee['members'] as $member): ?>
                      <?php
                        $assignmentCount = (int)$member['assignment_count'];
                        $barWidth = $maxMemberAssignments > 0 ? round(($assignmentCount / $maxMemberAssignments) * 100) : 0;
                        $overdue = (int)($memberOverdue[$member['committee_member_id']] ?? 0);
                        $accepted = (int)($member['accepted_count'] ?? 0);
                        $declined = (int)($member['declined_count'] ?? 0);
                        $avgMinutes = $member['avg_response_minutes'] ?? null;
                        $availMeta = availabilityStatusMeta($member['availability_status'] ?? 'Available');
                      ?>
                      <div class="assignment-load-row">
                        <div class="assignment-load-identity">
                          <span class="avatar-circle sm" style="background:<?= perfColor($perfPalette, $member['committee_member_id']) ?>;"><?= e(perfInitials($member['member_name'] ?? '?')) ?></span>
                          <span><strong><?= e($member['member_name']) ?></strong><small><?= e($member['member_role']) ?></small></span>
                        </div>
                        <div class="assignment-load-meter">
                          <div class="assignment-load-track"><span style="width: <?= $barWidth ?>%"></span></div>
                          <span class="assignment-load-count"><?= $assignmentCount ?></span>
                          <?php if ($overdue > 0): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="<?= $overdue ?> overdue assignment<?= $overdue === 1 ? '' : 's' ?>"><?= $overdue ?> overdue</span>
                          <?php endif; ?>
                          <div class="assignment-load-signals small text-muted mt-1 d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge bg-<?= e($availMeta['color']) ?>"><?= e($availMeta['label']) ?></span>
                            <?php if ($accepted === 0 && $declined === 0): ?>
                              <span>No task responses yet</span>
                            <?php else: ?>
                              <span><i class="bi bi-check-circle text-success"></i> <?= $accepted ?> accepted</span>
                              <?php if ($declined > 0): ?><span><i class="bi bi-x-circle text-danger"></i> <?= $declined ?> declined</span><?php endif; ?>
                              <span>Avg. response: <?= $avgMinutes !== null ? e(formatResponseDuration((int)$avgMinutes)) : 'Insufficient data' ?></span>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </section>
          <?php endforeach; ?>
        <?php endif; ?>
      </div></div>
    </div>
  </div>
</div>
<?php $extraJs = []; include __DIR__ . '/../../layouts/footer.php'; ?>
<script>
document.querySelectorAll('.assignment-balance-toggle').forEach(function (toggle) {
  toggle.addEventListener('click', function () {
    const panel = document.getElementById(toggle.getAttribute('aria-controls'));
    if (!panel) return;
    const expanded = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    toggle.setAttribute('aria-label', (expanded ? 'Show' : 'Hide') + ' assigned members');
    toggle.title = expanded ? 'Show assigned members' : 'Hide assigned members';
    panel.hidden = expanded;
  });
});
<?php if (!empty($report['performance_details'])): ?>
new Chart(document.getElementById('assignmentDistributionChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($report['performance_details'], 'committee_name')) ?>,
    datasets: [{
      label: 'Assignments',
      data: <?= json_encode(array_column($report['performance_details'], 'assignment_count')) ?>,
      backgroundColor: '#315f86',
      hoverBackgroundColor: '#497da6',
      borderRadius: 6,
      borderSkipped: false,
      barThickness: 14,
      maxBarThickness: 18,
    }]
  },
  options: {
    indexAxis: 'y',
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#18324d',
        titleColor: '#ffffff',
        bodyColor: '#e4edf5',
        padding: 11,
        cornerRadius: 8,
        displayColors: false,
      }
    },
    scales: {
      x: {
        beginAtZero: true,
        ticks: { precision: 0, color: '#718195', font: { size: 10 } },
        grid: { color: 'rgba(31, 56, 80, .08)', drawBorder: false },
        border: { display: false }
      },
      y: {
        ticks: { color: '#52677d', font: { size: 10 } },
        grid: { display: false },
        border: { display: false }
      }
    },
    responsive: true,
    maintainAspectRatio: false,
  }
});
<?php endif; ?>

</script>
