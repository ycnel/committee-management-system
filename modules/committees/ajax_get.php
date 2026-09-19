<?php
/**
 * modules/committees/ajax_get.php
 * ------------------------------------------------------------------
 * Returns a single committee's data as JSON, for populating the
 * Edit modal.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid committee id.');

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

if (!$committee) jsonResponse(false, 'Committee not found.');

$members = $pdo->prepare(
	"SELECT cm.member_role, cm.assigned_date, u.full_name, u.email, r.name AS role_name
	 FROM committee_members cm
	 INNER JOIN users u ON u.id = cm.user_id
	 INNER JOIN roles r ON r.id = u.role_id
	 WHERE cm.committee_id = :id AND cm.status = 'Active'
	 ORDER BY FIELD(cm.member_role, 'Chairperson', 'Vice Chairperson', 'Member'), u.full_name"
);
$members->execute([':id' => $id]);
$members = $members->fetchAll();
foreach ($members as &$m) { $m['assigned_label'] = formatDate($m['assigned_date']); }
unset($m);

jsonResponse(true, '', [
	'committee' => $committee,
	'members'   => $members,
	'created_label' => formatDate($committee['date_created']),
]);
