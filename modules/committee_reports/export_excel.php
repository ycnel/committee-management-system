<?php
/**
 * modules/committee_reports/export_excel.php
 * ------------------------------------------------------------------
 * Exports the selected report type as an Excel-openable .xls file
 * using the app's existing outputExcel() helper (native PHP, no
 * external library, matches the rest of the stack).
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/report_data.php';
requireRole([ROLE_ADMIN, ROLE_STAFF]);

$pdo = db();
$type = in_array($_GET['type'] ?? '', ['committee', 'workload', 'performance'], true) ? $_GET['type'] : 'committee';
$committeeId = (int)($_GET['committee_id'] ?? 0);

$labels = ['committee' => 'Committee Report', 'workload' => 'Workload Report', 'performance' => 'Performance Report'];
$title = $labels[$type];

$data = buildCommitteeReportData($pdo, $type, $committeeId);

logActivity(currentUserId(), 'Export', 'Exported ' . $title . ' Excel' . ($committeeId ? ' (committee #' . $committeeId . ')' : ' (all committees)'));

$filename = 'committee-' . $type . '-report-' . date('Ymd-His') . '.xls';

$pdo->prepare(
    'INSERT INTO committee_reports (committee_id, generated_by, report_title, report_type, file_path, generated_at)
     VALUES (:cid, :by, :title, :type, :path, NOW())'
)->execute([
    ':cid' => $committeeId ?: null, ':by' => currentUserId(), ':title' => $title,
    ':type' => ucfirst($type), ':path' => $filename,
]);

outputExcel($filename, $title, $data['headers'], $data['rows']);
