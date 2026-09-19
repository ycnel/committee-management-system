<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../committee_reports/report_data.php';
require_once __DIR__ . '/../committee_reports/report_narrative.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();
session_write_close(); // read-only endpoint: release the session lock for concurrent requests

$committeeId = (int)($_POST['committee_id'] ?? 0);
$dateFrom = clean($_POST['date_from'] ?? '');
$dateTo = clean($_POST['date_to'] ?? '');
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : null;
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : null;

try {
    $pdo = db();
    $data = buildCommitteeReportData($pdo, 'performance', $committeeId, $dateFrom, $dateTo);
    $aiUsed = false;
    $narrative = buildReportNarrative($pdo, 'performance', $data, $committeeId, $aiUsed);
    jsonResponse(true, 'Performance analysis generated.', ['analysis' => $narrative, 'ai' => $aiUsed]);
} catch (Throwable $e) {
    error_log('Performance analysis error: ' . $e->getMessage());
    jsonResponse(false, 'Performance analysis is temporarily unavailable.');
}
