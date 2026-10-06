<?php
/**
 * modules/committee_reports/ajax_draft_list.php
 * ------------------------------------------------------------------
 * GET: report drafts, newest first. Powers both the standalone
 * drafts.php list page and the "Committee Reports" tab's draft widget
 * on modules/committees/view.php.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/report_drafts.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]); // read/oversight only; canManage() still gates writes

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(false, 'GET requests only.');
}
session_write_close();

$committeeId = (int)($_GET['committee_id'] ?? 0);
$status = clean((string)($_GET['status'] ?? ''));
$limit = max(1, min((int)($_GET['limit'] ?? 25), 100));

$where = [];
$params = [];
if ($committeeId > 0) { $where[] = 'd.committee_id = :cid'; $params[':cid'] = $committeeId; }
if ($status === 'all') {
    $where[] = "d.status <> 'Archived'";
} elseif ($status !== '') {
    $where[] = 'd.status = :status';
    $params[':status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$pdo = db();
try {
    $stmt = $pdo->prepare(
        "SELECT d.draft_id, d.report_number, d.internal_reference_no, d.report_title, d.subject_title,
                d.proposed_ordinance_title, d.reference_measure_no, d.date_referred,
                d.legislative_matter_ref, d.report_type, d.status, d.ai_generated,
                d.created_at, d.updated_at, c.committee_name, j.jurisdiction_name,
                cb.full_name AS created_by_name
         FROM committee_report_drafts d
         LEFT JOIN committees c ON c.committee_id = d.committee_id
         LEFT JOIN jurisdictions j ON j.jurisdiction_id = d.jurisdiction_id
         LEFT JOIN users cb ON cb.id = d.created_by
         $whereSql
         ORDER BY d.draft_id DESC LIMIT $limit"
    );
    $stmt->execute($params);
    $drafts = array_map(static function (array $r): array {
        return [
            'draft_id' => (int)$r['draft_id'], 'report_title' => $r['report_title'],
            'report_number' => $r['report_number'],
            'internal_reference_no' => $r['internal_reference_no'],
            'subject_title' => $r['subject_title'],
            'proposed_ordinance_title' => $r['proposed_ordinance_title'],
            'reference_measure_no' => $r['reference_measure_no'],
            'date_referred' => $r['date_referred'],
            'legislative_matter_ref' => $r['legislative_matter_ref'],
            'report_type' => $r['report_type'], 'status' => $r['status'],
            'status_color' => reportDraftStatusColor($r['status']), 'ai_generated' => (bool)$r['ai_generated'],
            'committee_name' => $r['committee_name'] ?? 'All committees',
            'jurisdiction_name' => $r['jurisdiction_name'],
            'created_by_name' => $r['created_by_name'], 'created_at_human' => timeAgo($r['created_at']),
            'updated_at_human' => timeAgo($r['updated_at']),
        ];
    }, $stmt->fetchAll());

    jsonResponse(true, '', ['drafts' => $drafts]);
} catch (Throwable $e) {
    error_log('Draft list error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while loading drafts.');
}
