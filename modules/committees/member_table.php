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
    "SELECT cm.committee_member_id, cm.member_role, cm.assigned_date, cm.status,
            u.id AS user_id, u.full_name, u.email, r.name AS role_name
     FROM committee_members cm
     INNER JOIN users u ON u.id = cm.user_id
     INNER JOIN roles r ON r.id = u.role_id
     WHERE cm.committee_id = :id AND cm.status = 'Active'
     ORDER BY FIELD(cm.member_role, 'Chairperson', 'Vice Chairperson', 'Member'), u.full_name"
);
$stmt->execute([':id' => $id]);
$members = $stmt->fetchAll();

$roleColors = ['Chairperson' => 'primary', 'Vice Chairperson' => 'info', 'Member' => 'secondary'];
?>
<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead>
      <tr>
        <th>Name</th>
        <th>Account Role</th>
        <th>Committee Role</th>
        <th>Assigned Date</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($members)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No members assigned yet.</td></tr>
      <?php else: foreach ($members as $m): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($m['full_name']) ?></div>
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
