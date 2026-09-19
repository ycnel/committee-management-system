<?php
/**
 * pages/users.php
 * ------------------------------------------------------------------
 * Admin-only User Management: create/edit/delete accounts and
 * assign roles. Committee members are drawn from this table, so
 * this page is how new committee members get into the system.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole([ROLE_ADMIN]);

$pageTitle  = 'User Management';
$activeMenu = 'users';
$pdo = db();

$roles = $pdo->query('SELECT id, name FROM roles ORDER BY id')->fetchAll();

include __DIR__ . '/../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0"><i class="bi bi-person-gear text-primary"></i> User Management</h5>
      <small class="text-muted">Create accounts and assign roles. Committee Member accounts can then be assigned to committees.</small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" id="btnAddUser">
      <i class="bi bi-person-plus"></i> New User
    </button>
  </div>

  <div class="card mb-3">
    <div class="card-body py-3">
      <form id="filterForm" class="d-flex flex-wrap align-items-center gap-2">
        <div class="input-group input-group-sm" style="max-width:260px;">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control" id="searchInput" name="search" placeholder="Search name or email...">
        </div>
        <select class="form-select form-select-sm" name="role_id" style="min-width:130px;width:auto;">
          <option value="">All Roles</option>
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="form-select form-select-sm" name="status" style="min-width:120px;width:auto;">
          <option value="">All Statuses</option>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm position-relative" id="filterApply">
          <i class="bi bi-check2"></i> Apply<span class="apply-pending-dot d-none" id="applyPendingDot" aria-hidden="true"></span>
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="filterReset"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div id="usersTableWrap">
      <?php include __DIR__ . '/users_table.php'; ?>
    </div>
  </div>

<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form id="userForm">
        <?= csrfField() ?>
        <input type="hidden" name="id" id="us_id" value="0">
        <div class="modal-header">
          <h5 class="modal-title" id="userModalTitle"><i class="bi bi-person-plus"></i> New User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" id="us_full_name" class="form-control" required maxlength="150">
          </div>
          <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" id="us_email" class="form-control" required maxlength="255">
          </div>
          <div class="mb-3" id="us_password_wrap">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" id="us_password" class="form-control" minlength="8">
            <div class="form-text">Leave blank when editing to keep the current password.</div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Role <span class="text-danger">*</span></label>
              <select name="role_id" id="us_role" class="form-select" required>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" id="us_status" class="form-select">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>

          <hr class="my-4">
          <h6 class="text-primary mb-3"><i class="bi bi-mortarboard"></i> Educational Background</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Highest Educational Attainment</label>
              <select name="highest_education" id="us_highest_education" class="form-select">
                <option value="">-- Select --</option>
                <option>High School</option><option>Vocational/Technical</option>
                <option>Associate Degree</option><option>Bachelor's Degree</option>
                <option>Master's Degree</option><option>Doctorate</option><option>Other</option>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Degree/Course</label><input type="text" name="degree_course" id="us_degree_course" class="form-control" maxlength="255"></div>
            <div class="col-md-6"><label class="form-label">School/University</label><input type="text" name="school_university" id="us_school_university" class="form-control" maxlength="255"></div>
            <div class="col-md-6"><label class="form-label">Major/Specialization</label><input type="text" name="major_specialization" id="us_major_specialization" class="form-control" maxlength="255"></div>
            <div class="col-12"><label class="form-label">Relevant Certifications or Training</label><textarea name="certifications_training" id="us_certifications_training" class="form-control" rows="2" placeholder="One certification or training per line"></textarea></div>
          </div>

          <h6 class="text-primary mt-4 mb-3"><i class="bi bi-briefcase"></i> Professional Background</h6>
          <div class="row g-3">
            <div class="col-md-8"><label class="form-label">Current/Previous Profession</label><input type="text" name="current_profession" id="us_current_profession" class="form-control" maxlength="255"></div>
            <div class="col-md-4"><label class="form-label">Years of Experience</label><input type="number" name="years_experience" id="us_years_experience" class="form-control" min="0" max="100"></div>
            <div class="col-md-6"><label class="form-label">Previous Positions/Roles</label><textarea name="previous_positions" id="us_previous_positions" class="form-control" rows="2"></textarea></div>
            <div class="col-md-6"><label class="form-label">Relevant Organizations/Institutions</label><textarea name="previous_organizations" id="us_previous_organizations" class="form-control" rows="2"></textarea></div>
            <div class="col-12"><label class="form-label">Government/Legislative Experience</label><textarea name="government_experience" id="us_government_experience" class="form-control" rows="2"></textarea></div>
          </div>

          <h6 class="text-primary mt-4 mb-3"><i class="bi bi-lightbulb"></i> Field Expertise</h6>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Primary Field of Expertise</label><input type="text" name="primary_expertise" id="us_primary_expertise" class="form-control" maxlength="255" placeholder="e.g. Public Administration, Finance"></div>
            <div class="col-md-6"><label class="form-label">Secondary Fields of Expertise</label><input type="text" name="secondary_expertise" id="us_secondary_expertise" class="form-control" placeholder="Separate fields with commas"></div>
            <div class="col-md-6"><label class="form-label">Areas of Knowledge</label><textarea name="knowledge_areas" id="us_knowledge_areas" class="form-control" rows="2"></textarea></div>
            <div class="col-md-6"><label class="form-label">Relevant Skills</label><textarea name="relevant_skills" id="us_relevant_skills" class="form-control" rows="2"></textarea></div>
            <div class="col-md-6"><label class="form-label">Legislative/Committee Expertise</label><textarea name="committee_expertise" id="us_committee_expertise" class="form-control" rows="2"></textarea></div>
            <div class="col-md-6"><label class="form-label">Expertise Keywords/Tags</label><textarea name="expertise_keywords" id="us_expertise_keywords" class="form-control" rows="2" placeholder="e.g. infrastructure, budgeting, governance"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraJs = [APP_URL . '/assets/js/users.js?v=2'];
include __DIR__ . '/../layouts/footer.php';
?>
