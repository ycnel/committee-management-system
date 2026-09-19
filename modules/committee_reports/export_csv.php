<?php
/**
 * CSV export for Committee, Workload, and Performance reports.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/report_data.php';
requireRole([ROLE_ADMIN, ROLE_STAFF]);

$pdo = db();
$type = in_array($_GET['type'] ?? '', ['committee', 'workload', 'performance'], true) ? $_GET['type'] : 'committee';
$committeeId = (int)($_GET['committee_id'] ?? 0);
$dateFrom = clean($_GET['date_from'] ?? '');
$dateTo = clean($_GET['date_to'] ?? '');
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : null;
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : null;
$labels = ['committee' => 'Committee Report', 'workload' => 'Workload Report', 'performance' => 'Performance Report'];
$title = $labels[$type];
$data = buildCommitteeReportData($pdo, $type, $committeeId, $dateFrom, $dateTo);
$filename = 'committee-' . $type . '-report-' . date('Y-m-d') . '.csv';

logActivity(currentUserId(), 'Export', 'Exported ' . $title . ' CSV' . ($committeeId ? ' (committee #' . $committeeId . ')' : ' (all committees)'));

$pdo->prepare(
    'INSERT INTO committee_reports (committee_id, generated_by, report_title, report_type, file_path, generated_at)
     VALUES (:cid, :by, :title, :type, :path, NOW())'
)->execute([
    ':cid' => $committeeId ?: null, ':by' => currentUserId(), ':title' => $title,
    ':type' => ucfirst($type), ':path' => $filename,
]);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');

$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");
$headers = $data['export_headers'] ?? $data['headers'];
$rows = $data['export_rows'] ?? $data['rows'];
fputcsv($output, $headers);
foreach ($rows as $row) {
    fputcsv($output, $row);
}
fclose($output);
exit;
