<?php
/**
 * modules/reports/report_queries.php
 * ------------------------------------------------------------------
 * Shared queries for the Reports & Analytics module so the page and
 * the CSV export always report identical numbers.
 * ------------------------------------------------------------------
 */

/** Validated date_from/date_to GET params (Y-m-d or null). */
function reportDateRange(): array
{
    $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_from'] ?? '') ? $_GET['date_from'] : null;
    $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_to'] ?? '') ? $_GET['date_to'] : null;
    return [$from, $to];
}

/** WHERE fragment + params limiting workload_assignments to the range. */
function waRange(?string $from, ?string $to): array
{
    $sql = '1=1';
    $params = [];
    if ($from) { $sql .= ' AND wa.assigned_date >= :dfrom'; $params[':dfrom'] = $from; }
    if ($to)   { $sql .= ' AND wa.assigned_date <= :dto';   $params[':dto'] = $to; }
    return [$sql, $params];
}

/**
 * Per-committee breakdown: members, assignment totals by status.
 * The date range applies to the assignments join so committees with
 * zero assignments in range still appear.
 */
function committeeBreakdown(PDO $pdo, ?string $from, ?string $to): array
{
    [$range, $params] = waRange($from, $to);
    $stmt = $pdo->prepare(
        "SELECT c.committee_id, c.committee_name, c.status,
                COUNT(DISTINCT cm.committee_member_id) AS member_count,
                COUNT(wa.workload_id)                 AS assignments,
                SUM(wa.status = 'Completed')          AS completed,
                SUM(wa.status = 'In Progress')        AS in_progress,
                SUM(wa.status = 'Pending')            AS pending,
                SUM(wa.status = 'Overdue')            AS overdue
         FROM committees c
         LEFT JOIN committee_members cm
                ON cm.committee_id = c.committee_id AND cm.status = 'Active'
         LEFT JOIN workload_assignments wa
                ON wa.committee_member_id = cm.committee_member_id AND $range
         GROUP BY c.committee_id
         ORDER BY assignments DESC, c.committee_name ASC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}
