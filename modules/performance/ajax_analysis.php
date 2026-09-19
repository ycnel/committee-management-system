<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../committee_reports/report_data.php';
require_once __DIR__ . '/../committee_reports/report_narrative.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$committeeId = (int)($_POST['committee_id'] ?? 0);
$dateFrom = clean($_POST['date_from'] ?? '');
$dateTo = clean($_POST['date_to'] ?? '');
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : null;
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : null;

try {
    $pdo = db();
    $data = buildCommitteeReportData($pdo, 'performance', $committeeId, $dateFrom, $dateTo);
    $narrative = buildReportNarrative($pdo, 'performance', $data, $committeeId);
    jsonResponse(true, 'Performance analysis generated.', ['analysis' => $narrative]);
} catch (Throwable $e) {
    error_log('Performance analysis error: ' . $e->getMessage());
    jsonResponse(false, 'Performance analysis is temporarily unavailable.');
}
