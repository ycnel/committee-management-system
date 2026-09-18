<?php
/**
 * modules/committee_reports/export_pdf.php
 * ------------------------------------------------------------------
 * Exports the selected report type (Committee/Workload/Performance)
 * as a downloadable PDF using the app's existing SimplePdf class
 * (same helper modules/issues/export_pdf.php uses).
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/SimplePdf.php';
require_once __DIR__ . '/report_data.php';
requireRole([ROLE_ADMIN, ROLE_STAFF]);

$pdo = db();
$type = in_array($_GET['type'] ?? '', ['committee', 'workload', 'performance'], true) ? $_GET['type'] : 'committee';
$committeeId = (int)($_GET['committee_id'] ?? 0);

$labels = ['committee' => 'Committee Report', 'workload' => 'Workload Report', 'performance' => 'Performance Report'];
$title = $labels[$type];

$data = buildCommitteeReportData($pdo, $type, $committeeId);

$pdf = new SimplePdf($title, 'Generated ' . date('F j, Y g:i A') . '  |  Total: ' . count($data['rows']));

$colWidths = match ($type) {
    'workload'    => [110, 90, 90, 55, 45, 65, 60],
    'performance' => [140, 70, 70, 70, 70, 90],
    default       => [130, 110, 60, 70, 90],
};

$tableRows = $data['rows'];
if (empty($tableRows)) $tableRows[] = array_fill(0, count($data['headers']), '');

$pdf->addTable($data['headers'], $colWidths, $tableRows);

logActivity(currentUserId(), 'Export', 'Exported ' . $title . ' PDF' . ($committeeId ? ' (committee #' . $committeeId . ')' : ' (all committees)'));

$filename = 'committee-' . $type . '-report-' . date('Ymd-His') . '.pdf';

$pdo->prepare(
    'INSERT INTO committee_reports (committee_id, generated_by, report_title, report_type, file_path, generated_at)
     VALUES (:cid, :by, :title, :type, :path, NOW())'
)->execute([
    ':cid' => $committeeId ?: null, ':by' => currentUserId(), ':title' => $title,
    ':type' => ucfirst($type), ':path' => $filename,
]);

$pdf->output($filename);
