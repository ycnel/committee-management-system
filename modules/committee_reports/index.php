<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]);
$committeeId = (int)($_GET['committee_id'] ?? 0);
$query = $committeeId > 0 ? '?committee_id=' . $committeeId : '';
redirect(APP_URL . '/modules/committee_reports/drafts.php' . $query);
