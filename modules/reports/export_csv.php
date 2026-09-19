<?php
/**
 * modules/reports/export_csv.php
 * ------------------------------------------------------------------
 * Streams the Reports & Analytics committee breakdown as a CSV file,
 * honoring the same date_from/date_to filter as the page.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/report_queries.php';
requireRole([ROLE_ADMIN, ROLE_STAFF]);

[$dateFrom, $dateTo] = reportDateRange();
$rows = committeeBreakdown(db(), $dateFrom, $dateTo);

$filename = 'reports_analytics_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Reports & Analytics — Committee Breakdown']);
fputcsv($out, ['Generated', date('M d, Y h:i A')]);
fputcsv($out, ['Range', ($dateFrom ?? 'All') . ' to ' . ($dateTo ?? 'All')]);
fputcsv($out, []);
fputcsv($out, ['Committee', 'Status', 'Members', 'Assignments', 'Completed', 'In Progress', 'Pending', 'Overdue', 'Completion %']);

foreach ($rows as $r) {
    $rate = (int)$r['assignments'] > 0 ? round(((int)$r['completed'] / (int)$r['assignments']) * 100) : 0;
    fputcsv($out, [
        $r['committee_name'], $r['status'], (int)$r['member_count'],
        (int)$r['assignments'], (int)$r['completed'], (int)$r['in_progress'],
        (int)$r['pending'], (int)$r['overdue'], $rate . '%',
    ]);
}
fclose($out);

logActivity(currentUserId(), 'Exported', 'Reports & Analytics committee breakdown CSV' . ($dateFrom || $dateTo ? " ($dateFrom to $dateTo)" : ''));
exit;
