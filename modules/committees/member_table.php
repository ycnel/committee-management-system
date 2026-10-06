<?php
/**
 * modules/committees/member_table.php
 * ------------------------------------------------------------------
 * Roster of active members for a single committee. Included by
 * view.php (initial render) and ajax_member_search.php (AJAX
 * refresh after assign/remove/role-change actions).
 * ------------------------------------------------------------------
 */

$id = $id ?? (int)($_GET['id'] ?? $_GET['committee_id'] ?? 0);
$pdo = db();

$stmt = $pdo->prepare(
    "SELECT cm.committee_member_id, cm.member_role, cm.political_group, cm.assigned_date, cm.status,
            u.id AS user_id, u.full_name, u.email, r.name AS role_name
     FROM committee_members cm
     INNER JOIN users u ON u.id = cm.user_id
     INNER JOIN roles r ON r.id = u.role_id
     WHERE cm.committee_id = :id AND cm.status = 'Active'
     ORDER BY FIELD(cm.member_role, 'Chairperson', 'Vice Chairperson', 'Member'), u.full_name"
);
$stmt->execute([':id' => $id]);
$members = $stmt->fetchAll();
$availabilityMap = committeeAvailabilityMap($pdo, $id);

$roleColors = ['Chairperson' => 'primary', 'Vice Chairperson' => 'info', 'Member' => 'secondary'];
$groupColors = [
    'Majority' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
    'Minority' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
];
?>
<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead>
      <tr>
        <th>Name</th>
        <th>Account Role</th>
        <th>Committee Role</th>
        <th>Political Group</th>
        <th>Availability</th>
        <th>Assigned Date</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($members)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No members assigned yet.</td></tr>
      <?php else: foreach ($members as $m): ?>
        <?php
          $availability = $availabilityMap[(int)$m['committee_member_id']] ?? ['status' => 'Available', 'reason' => null];
          $availabilityMeta = availabilityStatusMeta($availability['status']);
          $canViewAvailability = canManage() || isLegislativeOversight() || isSuperAdmin() || (int)$m['user_id'] === (int)currentUserId();
        ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <div class="fw-semibold"><?= e($m['full_name']) ?></div>
              <?php if (!empty($m['political_group'])): ?>
                <span class="badge <?= e($groupColors[$m['political_group']] ?? 'bg-light text-dark border') ?> rounded-pill">
                  <?= e($m['political_group']) ?>
                </span>
              <?php endif; ?>
            </div>
            <div class="small text-muted"><?= e($m['email']) ?></div>
          </td>
          <td><span class="badge bg-light text-dark border"><?= e($m['role_name']) ?></span></td>
          <td>
            <?php if (canManage()): ?>
              <select class="form-select form-select-sm member-role-select" style="width:auto;display:inline-block;"
                      data-member-id="<?= (int)$m['committee_member_id'] ?>">
                <?php foreach (['Member', 'Vice Chairperson', 'Chairperson'] as $r): ?>
                  <option value="<?= e($r) ?>" <?= $m['member_role'] === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <span class="badge bg-<?= $roleColors[$m['member_role']] ?? 'secondary' ?>"><?= e($m['member_role']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (canManage()): ?>
              <select class="form-select form-select-sm member-group-select" style="width:auto;display:inline-block;"
                      data-member-id="<?= (int)$m['committee_member_id'] ?>">
                <option value="" <?= empty($m['political_group']) ? 'selected' : '' ?>>Unassigned</option>
                <?php foreach (['Majority', 'Minority'] as $group): ?>
                  <option value="<?= e($group) ?>" <?= ($m['political_group'] ?? '') === $group ? 'selected' : '' ?>><?= e($group) ?></option>
                <?php endforeach; ?>
              </select>
            <?php elseif (!empty($m['political_group'])): ?>
              <span class="badge <?= e($groupColors[$m['political_group']] ?? 'bg-light text-dark border') ?> rounded-pill"><?= e($m['political_group']) ?></span>
            <?php else: ?>
              <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($canViewAvailability): ?>
              <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 btn-availability"
                      data-member-id="<?= (int)$m['committee_member_id'] ?>"
                      data-member-name="<?= e($m['full_name']) ?>"
                      data-can-edit="<?= (canManage() || (int)$m['user_id'] === (int)currentUserId()) ? '1' : '0' ?>"
                      title="<?= e($availability['reason'] ?? '') ?>">
                <span class="badge bg-<?= e($availabilityMeta['color']) ?>"><?= e($availabilityMeta['label']) ?></span>
              </button>
            <?php else: ?>
              <span class="badge bg-<?= e($availabilityMeta['color']) ?>"><?= e($availabilityMeta['label']) ?></span>
            <?php endif; ?>
          </td>
          <td><?= formatDate($m['assigned_date']) ?></td>
          <td class="text-end">
            <?php if (canManage()): ?>
              <button type="button" class="btn btn-sm btn-outline-danger"
                      data-confirm-delete="the assignment for &quot;<?= e($m['full_name']) ?>&quot;"
                      data-delete-url="<?= e(APP_URL) ?>/modules/committees/ajax_member_remove.php?id=<?= (int)$m['committee_member_id'] ?>"
                      title="Remove from committee">
                <i class="bi bi-person-dash"></i>
              </button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
