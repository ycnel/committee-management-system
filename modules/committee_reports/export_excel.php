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
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]); // read/oversight only; canManage() still gates writes

$pdo = db();
if (($_GET['records'] ?? '') === '1') {
    $records = $pdo->query(
        "SELECT d.report_number, d.internal_reference_no, d.subject_title, d.report_title, c.committee_name,
                j.jurisdiction_name,
                d.proposed_ordinance_title, d.reference_measure_no, d.date_referred,
                u.full_name AS prepared_by, d.status, d.updated_at
         FROM committee_report_drafts d
         LEFT JOIN committees c ON c.committee_id = d.committee_id
         LEFT JOIN jurisdictions j ON j.jurisdiction_id = d.jurisdiction_id
         LEFT JOIN users u ON u.id = d.created_by
         ORDER BY d.updated_at DESC, d.draft_id DESC"
    )->fetchAll();
    $headers = ['Committee Report No.', 'CMAS Internal Reference', 'Subject / Title', 'Report Title', 'Committee', 'Jurisdiction', 'Proposed Measure', 'Official Reference / Measure No.', 'Date Referred', 'Prepared By', 'Status', 'Last Updated'];
    $rows = array_map(static function (array $record): array {
        return array_map(static function ($value): string {
            $value = (string)($value ?? '');
            return preg_match('/^\s*[=+\-@]/', $value) ? "'" . $value : $value;
        }, [
            $record['report_number'], $record['internal_reference_no'], $record['subject_title'], $record['report_title'],
            $record['committee_name'], $record['jurisdiction_name'], $record['proposed_ordinance_title'],
            $record['reference_measure_no'], $record['date_referred'],
            $record['prepared_by'], $record['status'], $record['updated_at'],
        ]);
    }, $records);
    logActivity(currentUserId(), 'Export', 'Exported Committee Report records Excel');
    $filename = 'committee-report-records-' . date('Ymd-His') . '.xls';
    $pdo->prepare(
        'INSERT INTO committee_reports (generated_by, report_title, report_type, file_path, generated_at)
         VALUES (:by, :title, :type, :path, NOW())'
    )->execute([
        ':by' => currentUserId(), ':title' => 'Committee Report Records',
        ':type' => 'Committee', ':path' => $filename,
    ]);
    outputExcel($filename, 'Committee Report Records', $headers, $rows);
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

logActivity(currentUserId(), 'Export', 'Exported ' . $title . ' Excel' . ($committeeId ? ' (committee #' . $committeeId . ')' : ' (all committees)'));

$filename = 'committee-' . $type . '-report-' . date('Ymd-His') . '.xls';

$pdo->prepare(
    'INSERT INTO committee_reports (committee_id, generated_by, report_title, report_type, file_path, generated_at)
     VALUES (:cid, :by, :title, :type, :path, NOW())'
)->execute([
    ':cid' => $committeeId ?: null, ':by' => currentUserId(), ':title' => $title,
    ':type' => ucfirst($type), ':path' => $filename,
]);

$headers = $data['export_headers'] ?? $data['headers'];
$rows = $data['export_rows'] ?? $data['rows'];
outputExcel($filename, $title, $headers, $rows);
