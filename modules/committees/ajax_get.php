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
		'SELECT * FROM committees
		 WHERE committee_id = :id
			 AND (:is_manager = 1 OR committee_id IN
						(SELECT committee_id FROM committee_members WHERE user_id = :uid AND status = \'Active\'))'
);
$stmt->execute([':id' => $id, ':is_manager' => canManage() ? 1 : 0, ':uid' => currentUserId()]);
$committee = $stmt->fetch();

if (!$committee) jsonResponse(false, 'Committee not found.');

jsonResponse(true, '', ['committee' => $committee]);
