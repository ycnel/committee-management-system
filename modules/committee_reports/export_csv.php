<?php
/**
 * CSV export for Committee, Workload, and Performance reports.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/report_data.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]); // read/oversight only; canManage() still gates writes

$pdo = db();
if (($_GET['records'] ?? '') === '1') {
    $stmt = $pdo->query(
        "SELECT d.report_number, d.internal_reference_no, d.subject_title, d.report_title, c.committee_name,
                j.jurisdiction_name,
                d.proposed_ordinance_title, d.reference_measure_no, d.date_referred,
                u.full_name AS prepared_by, d.status, d.updated_at
         FROM committee_report_drafts d
         LEFT JOIN committees c ON c.committee_id = d.committee_id
         LEFT JOIN jurisdictions j ON j.jurisdiction_id = d.jurisdiction_id
         LEFT JOIN users u ON u.id = d.created_by
         ORDER BY d.updated_at DESC, d.draft_id DESC"
    );
    $records = $stmt->fetchAll();
    $safeCell = static function ($value): string {
        $value = (string)($value ?? '');
        return preg_match('/^\s*[=+\-@]/', $value) ? "'" . $value : $value;
    };
    $filename = 'committee-report-records-' . date('Y-m-d') . '.csv';
    logActivity(currentUserId(), 'Export', 'Exported Committee Report records CSV');
    $pdo->prepare(
        'INSERT INTO committee_reports (generated_by, report_title, report_type, file_path, generated_at)
         VALUES (:by, :title, :type, :path, NOW())'
    )->execute([
        ':by' => currentUserId(), ':title' => 'Committee Report Records',
        ':type' => 'Committee', ':path' => $filename,
    ]);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    $output = fopen('php://output', 'wb');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Committee Report No.', 'CMAS Internal Reference', 'Subject / Title', 'Report Title', 'Committee', 'Jurisdiction', 'Proposed Measure', 'Official Reference / Measure No.', 'Date Referred', 'Prepared By', 'Status', 'Last Updated']);
    foreach ($records as $record) {
        fputcsv($output, [
            $safeCell($record['report_number']), $safeCell($record['internal_reference_no']), $safeCell($record['subject_title']), $safeCell($record['report_title']),
            $safeCell($record['committee_name']), $safeCell($record['jurisdiction_name']),
            $safeCell($record['proposed_ordinance_title']),
            $safeCell($record['reference_measure_no']), $safeCell($record['date_referred']),
            $safeCell($record['prepared_by']), $safeCell($record['status']), $safeCell($record['updated_at']),
        ]);
    }
    fclose($output);
    exit;
}

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
