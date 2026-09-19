<?php
/**
 * modules/committee_reports/ajax_narrative.php
 * ------------------------------------------------------------------
 * Async narrative for the report preview: the page renders instantly
 * with the factual fallback; this endpoint returns the AI-generated
 * summary/observations in the background and the page swaps them in.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/report_data.php';
require_once __DIR__ . '/report_narrative.php';
requireRole([ROLE_ADMIN, ROLE_STAFF]);
session_write_close(); // read-only endpoint: release the session lock for concurrent requests

$type = in_array($_GET['type'] ?? '', ['committee', 'workload', 'performance'], true) ? $_GET['type'] : 'committee';
$committeeId = (int)($_GET['committee_id'] ?? 0);
$dateFrom = clean($_GET['date_from'] ?? '');
$dateTo = clean($_GET['date_to'] ?? '');
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : null;
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : null;

try {
    $pdo = db();
    $data = buildCommitteeReportData($pdo, $type, $committeeId, $dateFrom, $dateTo);
    $aiUsed = false;
    $narrative = buildReportNarrative($pdo, $type, $data, $committeeId, $aiUsed);
    jsonResponse(true, '', [
        'ai'           => $aiUsed,
        'summary'      => $narrative['executive_summary'] ?? null,
        'observations' => $narrative['observations'] ?? null,
    ]);
} catch (Throwable $e) {
    error_log('Report narrative ajax error: ' . $e->getMessage());
    jsonResponse(false, 'Narrative generation is temporarily unavailable.');
}
