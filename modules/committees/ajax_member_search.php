<?php
/**
 * modules/committees/ajax_member_search.php
 * ------------------------------------------------------------------
 * Returns the refreshed member roster table body HTML for a given
 * committee_id, used after assign/remove/role-change actions.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$id = (int)($_GET['committee_id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid committee id.');

ob_start();
include __DIR__ . '/member_table.php';
$html = ob_get_clean();

jsonResponse(true, '', ['html' => $html]);
