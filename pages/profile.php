<?php
/**
 * pages/profile.php
 * ------------------------------------------------------------------
 * Lets the signed-in user view their account info and change their
 * own password.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle  = 'My Profile';
$activeMenu = 'profile';
$pdo = db();
$user = currentUser();
$backgroundStmt = $pdo->prepare('SELECT id, highest_education, degree_course, school_university, major_specialization, certifications_training, current_profession, years_experience, previous_positions, previous_organizations, government_experience, primary_expertise, secondary_expertise, knowledge_areas, relevant_skills, committee_expertise, expertise_keywords FROM user_background WHERE user_id = :user_id LIMIT 1');
$backgroundStmt->execute([':user_id' => currentUserId()]);
$background = $backgroundStmt->fetch() ?: [];
$profileBackgroundLocked = !empty($background['id']);

include __DIR__ . '/../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar profile-page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0"><i class="bi bi-person-circle text-primary"></i> My Profile</h5>
      <small class="text-muted">Manage your account details, background, and areas of expertise.</small>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card profile-card profile-account-card">
        <div class="card-header profile-card-header"><span class="profile-card-icon"><i class="bi bi-person-badge"></i></span><span><strong>Account Information</strong><small>Signed-in account details</small></span></div>
        <div class="card-body">
          <dl class="row mb-0 profile-account-list">
            <dt class="col-4">Full Name</dt><dd class="col-8"><?= e($user['full_name']) ?></dd>
            <dt class="col-4">Email</dt><dd class="col-8"><?= e($user['email']) ?></dd>
            <dt class="col-4">Role</dt><dd class="col-8"><span class="badge bg-primary"><?= e($user['role_name']) ?></span></dd>
          </dl>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card profile-card">
        <div class="card-header profile-card-header"><span class="profile-card-icon"><i class="bi bi-shield-lock"></i></span><span><strong>Change Password</strong><small>Keep your account secure</small></span></div>
        <div class="card-body">
          <form id="passwordForm">
            <?= csrfField() ?>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Current Password <span class="text-danger">*</span></label>
                <input type="password" name="current_password" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">New Password <span class="text-danger">*</span></label>
                <input type="password" name="new_password" id="pf_new_password" class="form-control" required minlength="8">
              </div>
              <div class="col-md-6">
                <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                <input type="password" name="confirm_password" class="form-control" required minlength="8">
              </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check-circle"></i> Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php if (!isAdmin()): ?>
  <div class="card profile-card profile-background-card mt-3">
    <div class="card-header profile-card-header profile-background-header"><span class="profile-card-icon"><i class="bi bi-journal-text"></i></span><span><strong>Professional and Expertise Profile</strong><small>Help committees understand your background for assignment suitability.</small></span></div>
    <div class="card-body">
      <form id="backgroundForm" method="post" action="<?= e(APP_URL) ?>/pages/ajax_save_background.php">
        <?= csrfField() ?>
        <fieldset id="backgroundFields" <?= $profileBackgroundLocked ? 'disabled' : '' ?>>
        <div class="profile-section-heading"><span class="profile-section-number">01</span><div><h6>Educational Background</h6><p>Share your academic preparation and relevant training.</p></div></div>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Highest Educational Attainment <span class="text-danger">*</span></label><select name="highest_education" class="form-select" required><option value="">-- Select --</option><?php foreach (['High School', 'Vocational/Technical', 'Associate Degree', "Bachelor's Degree", "Master's Degree", 'Doctorate', 'Other'] as $education): ?><option value="<?= e($education) ?>" <?= ($background['highest_education'] ?? '') === $education ? 'selected' : '' ?>><?= e($education) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6"><label class="form-label">Degree/Course</label><input type="text" name="degree_course" class="form-control" maxlength="255" value="<?= e($background['degree_course'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">School/University</label><input type="text" name="school_university" class="form-control" maxlength="255" value="<?= e($background['school_university'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Major/Specialization</label><input type="text" name="major_specialization" class="form-control" maxlength="255" value="<?= e($background['major_specialization'] ?? '') ?>"></div>
          <div class="col-12"><label class="form-label">Relevant Certifications or Training</label><textarea name="certifications_training" class="form-control" rows="2"><?= e($background['certifications_training'] ?? '') ?></textarea></div>
        </div>
        <hr class="profile-section-divider">
        <div class="profile-section-heading"><span class="profile-section-number">02</span><div><h6>Professional Background</h6><p>Add experience that may inform assignment decisions.</p></div></div>
        <div class="row g-3">
          <div class="col-md-8"><label class="form-label">Current/Previous Profession</label><input type="text" name="current_profession" class="form-control" maxlength="255" value="<?= e($background['current_profession'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Years of Experience</label><input type="number" name="years_experience" class="form-control" min="0" max="100" value="<?= e((string)($background['years_experience'] ?? '')) ?>"></div>
          <div class="col-md-6"><label class="form-label">Previous Positions/Roles</label><textarea name="previous_positions" class="form-control" rows="2"><?= e($background['previous_positions'] ?? '') ?></textarea></div>
          <div class="col-md-6"><label class="form-label">Relevant Organizations/Institutions</label><textarea name="previous_organizations" class="form-control" rows="2"><?= e($background['previous_organizations'] ?? '') ?></textarea></div>
          <div class="col-12"><label class="form-label">Government/Legislative Experience</label><textarea name="government_experience" class="form-control" rows="2"><?= e($background['government_experience'] ?? '') ?></textarea></div>
        </div>
        <hr class="profile-section-divider">
        <div class="profile-section-heading"><span class="profile-section-number">03</span><div><h6>Field Expertise</h6><p>Describe your knowledge, skills, and committee-related experience.</p></div></div>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Primary Field of Expertise</label><input type="text" name="primary_expertise" class="form-control" maxlength="255" value="<?= e($background['primary_expertise'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Secondary Fields of Expertise</label><textarea name="secondary_expertise" class="form-control" rows="2"><?= e($background['secondary_expertise'] ?? '') ?></textarea></div>
          <div class="col-md-6"><label class="form-label">Areas of Knowledge</label><textarea name="knowledge_areas" class="form-control" rows="2"><?= e($background['knowledge_areas'] ?? '') ?></textarea></div>
          <div class="col-md-6"><label class="form-label">Relevant Skills</label><textarea name="relevant_skills" class="form-control" rows="2"><?= e($background['relevant_skills'] ?? '') ?></textarea></div>
          <div class="col-md-6"><label class="form-label">Legislative/Committee Expertise</label><textarea name="committee_expertise" class="form-control" rows="2"><?= e($background['committee_expertise'] ?? '') ?></textarea></div>
          <div class="col-md-6"><label class="form-label">Expertise Keywords/Tags</label><textarea name="expertise_keywords" class="form-control" rows="2"><?= e($background['expertise_keywords'] ?? '') ?></textarea></div>
        </div>
        </fieldset>
        <div class="profile-form-footer"><span class="text-muted small"><i class="bi bi-lock"></i> Only you can edit this information.</span><span class="profile-form-actions"><span class="profile-save-status" id="backgroundSaveStatus" aria-live="polite"></span><?php if ($profileBackgroundLocked): ?><button type="button" class="btn btn-outline-primary" id="editBackgroundButton"><i class="bi bi-pencil-square"></i> Edit Information</button><?php endif; ?><button type="submit" class="btn btn-primary" id="saveBackgroundButton" <?= $profileBackgroundLocked ? 'hidden' : '' ?>><i class="bi bi-save"></i> Save Profile Information</button></span></div>
      </form>
    </div>
  </div>
  <?php endif; ?>

<?php
$extraJs = [APP_URL . '/assets/js/profile.js?v=' . (string)filemtime(__DIR__ . '/../assets/js/profile.js')];
include __DIR__ . '/../layouts/footer.php';
?>
