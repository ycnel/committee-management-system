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

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
    <div class="breadcrumb-bar performance-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div><span class="performance-eyebrow">Assignment distribution overview</span><h5 class="mb-1"><i class="bi bi-diagram-3 text-primary"></i> Committee Assignment Monitoring</h5><small class="text-muted">CMAS monitors who is assigned to committee work, not legislative completion.</small></div>
      <div class="d-flex gap-2 align-items-center">
        <?php if (canManage()): ?>
          <button type="button" class="btn btn-primary btn-sm" id="btnAiAnalysis"><i class="bi bi-stars"></i> Generate Analysis</button>
        <?php endif; ?>
        <span class="performance-live-badge"><span></span> Updated from assignment data</span>
      </div>
    </div>

    <div class="performance-dashboard">
      <!-- KPI row -->
      <div class="row g-3 mb-3">
        <?php
        $kpiCards = [
          ['bi-diagram-3',              'blue',  (int)$entityTotals['committees'], 'Active Committees'],
          ['bi-people',                 'blue',  (int)$entityTotals['members'],    'Active Members'],
          ['bi-list-check',             'blue',  $totalAssignments,                'Assignments'],
          ['bi-check-circle',           'green', (int)$kpi['done'],                'Completed'],
          ['bi-exclamation-triangle',   'red',   (int)$kpi['overdue'],             'Overdue'],
          ['bi-percent',                'gold',  $completionRate . '%',            'Completion Rate'],
        ];
        foreach ($kpiCards as [$icon, $color, $value, $label]): ?>
          <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card performance-stat"><div class="performance-stat-icon performance-stat-icon--<?= e($color) ?>"><i class="bi <?= e($icon) ?>"></i></div><span><?= e($label) ?></span><strong><?= e(is_int($value) ? number_format($value) : $value) ?></strong></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- AI analysis result (populated by ajax_analysis.php) -->
      <?php if (canManage()): ?>
      <div class="card hero-card mb-3 d-none" id="aiAnalysisCard">
        <div class="card-body">
          <div class="performance-card-heading"><div><span class="performance-kicker">Smart AI</span><h2>Assignment Analysis</h2></div><span class="badge badge-soft-neutral" id="aiAnalysisSource"></span></div>
          <div id="aiAnalysisBody" class="small"></div>
          <div class="ai-analysis-foot" id="aiAnalysisFoot"></div>
        </div>
      </div>
      <?php endif; ?>

      <div class="row g-3 mb-3">
        <div class="col-xl-8"><div class="card hero-card performance-chart-card h-100"><div class="card-body"><div class="performance-card-heading"><div><span class="performance-kicker">Committee comparison</span><h2>Assignment distribution</h2></div></div>
          <?php if (empty($report['performance_details'])): ?>
            <div class="text-center text-muted py-5"><i class="bi bi-bar-chart" style="font-size:32px;"></i><p class="small mt-2 mb-0">No assignment data yet. Assign tasks in Workload Distribution to see the comparison.</p></div>
          <?php else: ?>
            <div class="performance-bar-wrap"><canvas id="assignmentDistributionChart"></canvas></div>
          <?php endif; ?>
        </div></div></div>
        <div class="col-xl-4"><div class="card hero-card performance-chart-card h-100"><div class="card-body"><div class="performance-card-heading"><div><span class="performance-kicker">Jurisdiction coverage</span><h2>Assignments by jurisdiction</h2></div></div><div class="small text-muted">Counts are based on recorded assignments and defined committee jurisdictions.</div><div class="mt-3">
          <?php if (empty($report['jurisdiction_counts'])): ?>
            <div class="text-center text-muted py-4"><i class="bi bi-geo-alt" style="font-size:28px;"></i><p class="small mt-2 mb-0">No assignments recorded under any jurisdiction yet.</p></div>
          <?php else: foreach ($report['jurisdiction_counts'] as $name => $count): ?>
            <div class="d-flex justify-content-between border-bottom py-2"><span><?= e($name) ?></span><strong><?= (int)$count ?></strong></div>
          <?php endforeach; endif; ?>
        </div></div></div></div>
      </div>

      <div class="card hero-card performance-detail-card mt-3"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="performance-detail-heading"><span class="performance-kicker">Assignment balance</span><h2>Member Assignment Load</h2></div><form method="get" class="d-flex flex-wrap gap-2 align-items-end"><div><label class="form-label small mb-1">Committee</label><select name="committee_id" class="form-select form-select-sm"><option value="0">All Committees</option><?php foreach ($committees as $option): ?><option value="<?= (int)$option['committee_id'] ?>" <?= $selectedCommitteeId === (int)$option['committee_id'] ? 'selected' : '' ?>><?= e($option['committee_name']) ?></option></option><?php endforeach; ?></select></div><div><label class="form-label small mb-1">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom ?? '') ?>"></div><div><label class="form-label small mb-1">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo ?? '') ?>"></div><button class="btn btn-primary btn-sm"><i class="bi bi-filter"></i> Apply</button></form></div>
        <?php if (empty($report['performance_details'])): ?>
          <div class="text-center text-muted py-5"><i class="bi bi-people" style="font-size:32px;"></i><p class="small mt-2 mb-0">No committees or assignments match the current filters.</p></div>
        <?php else: foreach ($report['performance_details'] as $committee): $maxMemberAssignments = max(array_column($committee['members'], 'assignment_count') ?: [0]); ?><section class="assignment-balance-panel mb-3"><div class="assignment-balance-header"><div class="assignment-balance-title"><span class="assignment-balance-icon"><i class="bi bi-diagram-3"></i></span><div><h3><?= e($committee['committee_name']) ?></h3><span><i class="bi bi-geo-alt"></i> <?= e($committee['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?></span></div></div><div class="assignment-balance-total"><strong><?= (int)$committee['assignment_count'] ?></strong><span>assignments</span></div></div><div class="assignment-balance-caption"><span>Member assignment load</span><span><?= count($committee['members']) ?> active member<?= count($committee['members']) === 1 ? '' : 's' ?></span></div><?php if (empty($committee['members'])): ?><p class="text-muted small mb-0">No active members are assigned to this committee.</p><?php else: ?><div class="assignment-load-list"><?php foreach ($committee['members'] as $member): $assignmentCount = (int)$member['assignment_count']; $barWidth = $maxMemberAssignments > 0 ? round(($assignmentCount / $maxMemberAssignments) * 100) : 0; $overdue = (int)($memberOverdue[$member['committee_member_id']] ?? 0); ?><div class="assignment-load-row"><div class="assignment-load-identity"><span class="avatar-circle sm" style="background:<?= perfColor($perfPalette, $member['committee_member_id']) ?>;"><?= e(perfInitials($member['member_name'] ?? '?')) ?></span><span><strong><?= e($member['member_name']) ?></strong><small><?= e($member['member_role']) ?></small></span></div><div class="assignment-load-meter"><div class="assignment-load-track"><span style="width: <?= $barWidth ?>%"></span></div><span class="assignment-load-count"><?= $assignmentCount ?></span><?php if ($overdue > 0): ?><span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="<?= $overdue ?> overdue assignment<?= $overdue === 1 ? '' : 's' ?>"><?= $overdue ?> overdue</span><?php endif; ?></div></div><?php endforeach; ?></div><?php endif; ?></section><?php endforeach; endif; ?>
      </div></div>
    </div>
  </div>
</div>
<?php $extraJs = []; include __DIR__ . '/../../layouts/footer.php'; ?>
<script>
<?php if (!empty($report['performance_details'])): ?>
new Chart(document.getElementById('assignmentDistributionChart'), { type: 'bar', data: { labels: <?= json_encode(array_column($report['performance_details'], 'committee_name')) ?>, datasets: [{ label: 'Assignments', data: <?= json_encode(array_column($report['performance_details'], 'assignment_count')) ?>, backgroundColor: '#2f6fed', borderRadius: 5 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } }, responsive: true, maintainAspectRatio: false } });
<?php endif; ?>

<?php if (canManage()): ?>
(function () {
  const btn = document.getElementById('btnAiAnalysis');
  const card = document.getElementById('aiAnalysisCard');
  const body = document.getElementById('aiAnalysisBody');
  if (!btn || !card || !body) return;

  const sourceBadge = document.getElementById('aiAnalysisSource');
  const foot = document.getElementById('aiAnalysisFoot');

  const AI_NAMES = <?= json_encode((function () use ($report) {
      $m = ['committees' => [], 'members' => []];
      foreach ($report['performance_details'] ?? [] as $c) {
          $m['committees'][(int)$c['committee_id']] = $c['committee_name'];
          foreach ($c['members'] ?? [] as $mem) { $m['members'][(int)$mem['committee_member_id']] = $mem['member_name']; }
      }
      return $m;
  })()) ?>;

  const SECTIONS = {
    executive_summary: 'Executive Summary',
    analysis: 'Analysis',
    observations: 'Observations',
    recommendations: 'Recommendations',
    conclusion: 'Conclusion'
  };

  function appendSection(label, text) {
    const div = document.createElement('div');
    div.className = 'ai-section';
    const span = document.createElement('span');
    span.className = 'ai-section-label';
    span.textContent = label;
    const p = document.createElement('p');
    p.textContent = text;
    div.appendChild(span);
    div.appendChild(p);
    body.appendChild(div);
  }

  function appendInsightGroup(title, entries, names) {
    const rows = Object.keys(entries || {}).filter(function (id) {
      return entries[id] && entries[id].indexOf('Insufficient') !== 0;
    });
    if (!rows.length) return;
    const h = document.createElement('div');
    h.className = 'ai-insight-heading';
    h.textContent = title;
    body.appendChild(h);
    rows.forEach(function (id) {
      const item = document.createElement('div');
      item.className = 'ai-insight';
      const who = document.createElement('strong');
      who.textContent = names[id] || '#' + id;
      const p = document.createElement('p');
      p.textContent = entries[id];
      item.appendChild(who);
      item.appendChild(p);
      body.appendChild(item);
    });
  }

  function showSkeleton() {
    body.innerHTML = '';
    foot.textContent = '';
    for (let i = 0; i < 5; i++) {
      const sk = document.createElement('div');
      sk.className = 'ai-skeleton';
      sk.style.width = (i % 2 === 0 ? '100%' : '72%');
      body.appendChild(sk);
    }
    card.classList.remove('d-none');
    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  btn.addEventListener('click', function () {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating…';
    if (sourceBadge) { sourceBadge.className = 'badge badge-soft-neutral'; sourceBadge.textContent = 'Generating…'; }
    showSkeleton();
    const params = new URLSearchParams({
      csrf_token: window.APP_CSRF_TOKEN || '',
      committee_id: '<?= (int)$selectedCommitteeId ?>',
      date_from: '<?= e($dateFrom ?? '') ?>',
      date_to: '<?= e($dateTo ?? '') ?>'
    });
    fetch(window.APP_URL + '/modules/performance/ajax_analysis.php', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString()
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) { card.classList.add('d-none'); if (window.Swal) Swal.fire('Error', data.message || 'Analysis failed.', 'error'); return; }
        body.innerHTML = '';
        Object.keys(SECTIONS).forEach(function (key) {
          if (data.analysis && data.analysis[key]) appendSection(SECTIONS[key], data.analysis[key]);
        });
        if (data.analysis) {
          appendInsightGroup('Committee insights', data.analysis.committee_analysis, AI_NAMES.committees);
          appendInsightGroup('Member insights', data.analysis.member_analysis, AI_NAMES.members);
        }
        if (sourceBadge) {
          sourceBadge.className = 'badge ' + (data.ai ? 'bg-success-subtle text-success border border-success-subtle' : 'badge-soft-neutral');
          sourceBadge.innerHTML = '<i class="bi ' + (data.ai ? 'bi-stars' : 'bi-cloud-slash') + '"></i> ' + (data.ai ? 'Gemini AI' : 'Offline draft');
        }
        if (foot) {
          foot.textContent = (data.ai ? 'Generated by Gemini AI — ' : 'AI offline, factual draft — ') + 'a draft for review; verify against the data before acting. ' + new Date().toLocaleString();
        }
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      })
      .catch(function () { card.classList.add('d-none'); if (window.Swal) Swal.fire('Error', 'Analysis is temporarily unavailable.', 'error'); })
      .finally(function () {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-stars"></i> Generate Analysis';
      });
  });
})();
<?php endif; ?>
</script>
