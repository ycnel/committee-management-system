<?php
/**
 * modules/committees/view.php
 * ------------------------------------------------------------------
 * Full detail view for a single committee: info, jurisdiction,
 * member roster (Module 2: Member Assignment), and quick links into
 * the Workload / Performance / Reports phases for this committee.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/report_drafts.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]);

$id = (int)($_GET['id'] ?? 0);
$pdo = db();

$stmt = $pdo->prepare(
    'SELECT c.*, j.jurisdiction_name, j.category AS jurisdiction_category
     FROM committees c
     LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id
  WHERE c.committee_id = :id
    AND (:is_manager = 1 OR c.committee_id IN
      (SELECT committee_id FROM committee_members WHERE user_id = :uid AND status = \'Active\'))'
);
$stmt->execute([':id' => $id, ':is_manager' => canManage() ? 1 : 0, ':uid' => currentUserId()]);
$committee = $stmt->fetch();

if (!$committee) {
    setFlash('danger', 'Committee not found.');
    redirect(APP_URL . '/modules/committees/index.php');
}

// Users not yet assigned to this committee (candidates for the Assign Member form).
$availableUsers = $pdo->prepare(
    'SELECT u.id, u.full_name, u.email, r.name AS role_name
     FROM users u
     INNER JOIN roles r ON r.id = u.role_id
     WHERE u.status = \'Active\'
       AND u.id NOT IN (
           SELECT user_id FROM committee_members WHERE committee_id = :cid AND status = \'Active\'
       )
     ORDER BY u.full_name'
);
$availableUsers->execute([':cid' => $id]);
$availableUsers = $availableUsers->fetchAll();

$memberCountStmt = $pdo->prepare('SELECT COUNT(*) FROM committee_members WHERE committee_id = :id AND status = \'Active\'');
$memberCountStmt->execute([':id' => $id]);
$memberCount = (int)$memberCountStmt->fetchColumn();

$canViewReports = in_array(currentRole(), [ROLE_ADMIN, ROLE_STAFF, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES], true);
$recentReports = [];
$recentDrafts = [];
if ($canViewReports) {
    $reportsStmt = $pdo->prepare(
        'SELECT report_id, report_title, report_type, generated_at
         FROM committee_reports WHERE committee_id = :id ORDER BY generated_at DESC LIMIT 5'
    );
    $reportsStmt->execute([':id' => $id]);
    $recentReports = $reportsStmt->fetchAll();

    $draftsStmt = $pdo->prepare(
        'SELECT draft_id, report_title, report_type, status, ai_generated, created_at
         FROM committee_report_drafts WHERE committee_id = :id ORDER BY draft_id DESC LIMIT 5'
    );
    $draftsStmt->execute([':id' => $id]);
    $recentDrafts = $draftsStmt->fetchAll();
}

$pageTitle  = $committee['committee_name'];
$activeMenu = 'committees';
$statusColors = ['Active' => 'success', 'Inactive' => 'secondary', 'Dissolved' => 'danger'];

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

  <div class="main-content committee-management-page admin-polished-page">
  <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <nav aria-label="breadcrumb" class="mb-1">
        <ol class="breadcrumb mb-0 small">
          <li class="breadcrumb-item"><a href="index.php">Committees</a></li>
          <li class="breadcrumb-item active"><?= e($committee['committee_name']) ?></li>
        </ol>
      </nav>
      <h5 class="mb-0"><i class="bi bi-diagram-3 text-primary"></i> <?= e($committee['committee_name']) ?>
        <span class="badge bg-<?= $statusColors[$committee['status']] ?? 'secondary' ?>"><?= e($committee['status']) ?></span>
      </h5>
    </div>
    <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Committees</a>
  </div>

  <div class="row g-3 align-items-stretch">
    <div class="col-lg-4">
      <div class="card mb-3 h-100">
        <div class="card-header">Committee Information</div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-5">Jurisdiction</dt>
            <dd class="col-7"><?= $committee['jurisdiction_name'] ? e($committee['jurisdiction_name']) : '<span class="text-muted">Unassigned</span>' ?></dd>

            <dt class="col-5">Category</dt>
            <dd class="col-7"><?= $committee['jurisdiction_category'] ? e($committee['jurisdiction_category']) : '—' ?></dd>

            <dt class="col-5">Date Created</dt>
            <dd class="col-7"><?= formatDate($committee['date_created']) ?></dd>

            <dt class="col-5">Members</dt>
            <dd class="col-7"><span class="badge bg-primary rounded-pill"><?= $memberCount ?></span></dd>
          </dl>
          <?php if ($committee['description']): ?>
            <hr>
            <p class="mb-0 small text-muted"><?= nl2br(e($committee['description'])) ?></p>
          <?php endif; ?>
        </div>
      </div>

      
    </div>

    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-people"></i> Committee Members</span>
          <?php if (canManage()): ?>
            <button type="button" class="btn btn-primary btn-sm" id="btnAssignMember" data-committee-id="<?= (int)$id ?>">
              <i class="bi bi-person-plus"></i> Assign Member
            </button>
          <?php endif; ?>
        </div>
        <div id="membersTableWrap">
          <?php include __DIR__ . '/member_table.php'; ?>
        </div>
      </div>
    </div>
  </div>

  <?php if ($canViewReports): ?>
    <section class="card mt-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-journal-richtext"></i> Committee Reports &amp; Drafts</span>
        <div class="d-flex gap-3">
          <a href="<?= e(APP_URL) ?>/modules/committee_reports/index.php?committee_id=<?= (int)$id ?>" class="small">Reports</a>
          <a href="<?= e(APP_URL) ?>/modules/committee_reports/drafts.php" class="small">All drafts</a>
        </div>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-lg-6">
            <h6 class="small fw-semibold">Recent reports</h6>
            <?php if (!$recentReports): ?>
              <p class="text-muted small mb-0">No reports generated for this committee yet.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($recentReports as $report): ?>
                  <li class="list-group-item px-0 d-flex justify-content-between gap-2">
                    <span><?= e($report['report_title']) ?> <span class="text-muted small"><?= e($report['report_type']) ?></span></span>
                    <span class="text-muted small text-nowrap"><?= e(timeAgo($report['generated_at'])) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
          <div class="col-lg-6">
            <h6 class="small fw-semibold">Report drafts</h6>
            <?php if (!$recentDrafts): ?>
              <p class="text-muted small mb-0">No report drafts for this committee yet.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($recentDrafts as $draft): ?>
                  <li class="list-group-item px-0 d-flex justify-content-between gap-2">
                    <a href="<?= e(APP_URL) ?>/modules/committee_reports/draft_edit.php?id=<?= (int)$draft['draft_id'] ?>">
                      <?= e($draft['report_title']) ?>
                    </a>
                    <span class="badge bg-<?= e(reportDraftStatusColor($draft['status'])) ?>"><?= e($draft['status']) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

<?php if (canManage()): ?>
<div class="modal fade" id="assignMemberModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="assignMemberForm">
        <?= csrfField() ?>
        <input type="hidden" name="committee_id" value="<?= (int)$id ?>">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-plus"></i> Assign Member</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">User <span class="text-danger">*</span></label>
            <select name="user_id" id="am_user" class="form-select" required>
              <option value="">-- Select a user --</option>
              <?php foreach ($availableUsers as $u): ?>
                <option value="<?= (int)$u['id'] ?>"><?= e($u['full_name']) ?> (<?= e($u['role_name']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($availableUsers)): ?>
              <div class="form-text text-warning">All active users are already assigned to this committee.</div>
            <?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label">Committee Role</label>
            <select name="member_role" id="am_role" class="form-select">
              <option value="Member">Member</option>
              <option value="Vice Chairperson">Vice Chairperson</option>
              <option value="Chairperson">Chairperson</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Political Group</label>
            <select name="political_group" id="am_political_group" class="form-select">
              <option value="">Unassigned</option>
              <option value="Majority">Majority</option>
              <option value="Minority">Minority</option>
            </select>
          </div>
          <div class="mb-1">
            <label class="form-label">Assigned Date</label>
            <input type="date" name="assigned_date" id="am_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Assign</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraJs = [APP_URL . '/assets/js/committee-view.js', APP_URL . '/assets/js/availability.js'];
include __DIR__ . '/../../layouts/footer.php';
?>
