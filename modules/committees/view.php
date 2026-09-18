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
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]);

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

$pageTitle  = $committee['committee_name'];
$activeMenu = 'committees';
$statusColors = ['Active' => 'success', 'Inactive' => 'secondary', 'Dissolved' => 'danger'];

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

  <div class="main-content">
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
$extraJs = [APP_URL . '/assets/js/committee-view.js'];
include __DIR__ . '/../../layouts/footer.php';
?>
