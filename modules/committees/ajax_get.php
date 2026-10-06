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
session_write_close(); // read-only endpoint: release the session lock for concurrent requests

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid committee id.');

$pdo = db();
$stmt = $pdo->prepare(
		'SELECT c.*
		 FROM committees c
		 WHERE c.committee_id = :id
			 AND (:is_manager = 1 OR c.committee_id IN
						(SELECT committee_id FROM committee_members WHERE user_id = :uid AND status = \'Active\'))'
);
$stmt->execute([':id' => $id, ':is_manager' => canManage() ? 1 : 0, ':uid' => currentUserId()]);
$committee = $stmt->fetch();

if (!$committee) jsonResponse(false, 'Committee not found.');

$jurisdictionStmt = $pdo->prepare(
		'SELECT DISTINCT jurisdiction_id, jurisdiction_name, category, description, scope_definition, status
		 FROM jurisdictions
		 WHERE category = :committee_name OR jurisdiction_id = :jurisdiction_id
		 ORDER BY jurisdiction_name'
);
$jurisdictionStmt->execute([
	':committee_name' => $committee['committee_name'],
	':jurisdiction_id' => (int)($committee['jurisdiction_id'] ?? 0),
]);
$jurisdictions = $jurisdictionStmt->fetchAll();

$members = $pdo->prepare(
	"SELECT cm.committee_member_id, cm.member_role, cm.political_group, cm.assigned_date,
	        u.full_name, u.email, r.name AS role_name
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

$availableUsers = [];
if (canManage()) {
	$available = $pdo->prepare(
		"SELECT u.id, u.full_name, u.email, r.name AS role_name
		 FROM users u
		 INNER JOIN roles r ON r.id = u.role_id
		 WHERE u.status = 'Active'
		   AND u.id NOT IN (
			   SELECT user_id FROM committee_members WHERE committee_id = :id AND status = 'Active'
		   )
		 ORDER BY u.full_name"
	);
	$available->execute([':id' => $id]);
	$availableUsers = $available->fetchAll();
}

jsonResponse(true, '', [
	'committee' => $committee,
	'jurisdictions' => $jurisdictions,
	'members'   => $members,
	'available_users' => $availableUsers,
	'created_label' => formatDate($committee['date_created']),
]);
