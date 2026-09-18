<?php
/**
 * modules/committee_reports/report_data.php
 * ------------------------------------------------------------------
 * Single source of truth for the three Committee Reporting datasets
 * (Committee / Workload / Performance), so the on-screen preview,
 * PDF export, Excel export, and print view can never drift apart.
 * Returns ['headers' => [...], 'rows' => [[...], ...]].
 * ------------------------------------------------------------------
 */

function buildCommitteeReportData(PDO $pdo, string $type, int $committeeId = 0): array
{
    $where = $committeeId > 0 ? 'WHERE c.committee_id = :cid' : '';
    $params = $committeeId > 0 ? [':cid' => $committeeId] : [];

    if ($type === 'workload') {
        $stmt = $pdo->prepare(
            "SELECT wa.task_title, u.full_name AS member_name, c.committee_name, wa.priority,
                    wa.workload_points, wa.status, wa.due_date
             FROM workload_assignments wa
             INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
             INNER JOIN users u ON u.id = cm.user_id
             INNER JOIN committees c ON c.committee_id = cm.committee_id
             $where
             ORDER BY c.committee_name, wa.due_date"
        );
        $stmt->execute($params);
        $rows = array_map(fn($r) => [
            $r['task_title'], $r['member_name'], $r['committee_name'], $r['priority'],
            $r['workload_points'], $r['status'], $r['due_date'] ? formatDate($r['due_date']) : '-',
        ], $stmt->fetchAll());

        return [
            'headers' => ['Task', 'Assigned To', 'Committee', 'Priority', 'Points', 'Status', 'Due Date'],
            'rows' => $rows,
        ];
    }

    if ($type === 'performance') {
        $stmt = $pdo->prepare(
            "SELECT c.committee_name,
                    COUNT(wa.workload_id) AS total_tasks,
                    SUM(CASE WHEN wa.status = 'Completed' THEN 1 ELSE 0 END) AS completed_tasks,
                    SUM(CASE WHEN wa.status IN ('Pending','In Progress') THEN 1 ELSE 0 END) AS pending_tasks,
                    SUM(CASE WHEN wa.status != 'Completed' AND wa.due_date IS NOT NULL AND wa.due_date < CURDATE() THEN 1 ELSE 0 END) AS overdue_tasks
             FROM committees c
             LEFT JOIN committee_members cm ON cm.committee_id = c.committee_id AND cm.status = 'Active'
             LEFT JOIN workload_assignments wa ON wa.committee_member_id = cm.committee_member_id
             $where
             GROUP BY c.committee_id, c.committee_name
             ORDER BY c.committee_name"
        );
        $stmt->execute($params);
        $rows = array_map(function ($r) {
            $rate = $r['total_tasks'] > 0 ? round(($r['completed_tasks'] / $r['total_tasks']) * 100, 2) : 0.0;
            return [$r['committee_name'], $r['total_tasks'], $r['completed_tasks'], $r['pending_tasks'], $r['overdue_tasks'], $rate . '%'];
        }, $stmt->fetchAll());

        return [
            'headers' => ['Committee', 'Total Tasks', 'Completed', 'Pending', 'Overdue', 'Completion Rate'],
            'rows' => $rows,
        ];
    }

    // ---- default: committee report ----
    $stmt = $pdo->prepare(
        "SELECT c.committee_name, j.jurisdiction_name, c.status, c.date_created,
                (SELECT COUNT(*) FROM committee_members cm WHERE cm.committee_id = c.committee_id AND cm.status = 'Active') AS member_count
         FROM committees c
         LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id
         $where
         ORDER BY c.committee_name"
    );
    $stmt->execute($params);
    $rows = array_map(fn($r) => [
        $r['committee_name'], $r['jurisdiction_name'] ?: 'Unassigned', $r['member_count'], $r['status'],
        $r['date_created'] ? formatDate($r['date_created']) : '-',
    ], $stmt->fetchAll());

    return [
        'headers' => ['Committee', 'Jurisdiction', 'Members', 'Status', 'Date Created'],
        'rows' => $rows,
    ];
}
