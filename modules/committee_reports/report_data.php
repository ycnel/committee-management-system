<?php
/** Shared factual datasets for Committee, Workload, and Assignment Monitoring reports. */

function buildCommitteeReportData(PDO $pdo, string $type, int $committeeId = 0, ?string $dateFrom = null, ?string $dateTo = null): array
{
    $params = [];
    $committeeFilter = '';
    if ($committeeId > 0) { $committeeFilter = ' AND c.committee_id = :cid'; $params[':cid'] = $committeeId; }

    if ($type === 'workload') {
        $dateFilter = '';
        if ($dateFrom !== null) { $dateFilter .= ' AND wa.assigned_date >= :from_date'; $params[':from_date'] = $dateFrom; }
        if ($dateTo !== null) { $dateFilter .= ' AND wa.assigned_date <= :to_date'; $params[':to_date'] = $dateTo; }
        $stmt = $pdo->prepare("SELECT wa.task_title, wa.task_description, u.full_name AS member_name, cm.member_role, c.committee_name, j.jurisdiction_name, wa.priority, wa.assigned_date, wa.due_date
            FROM workload_assignments wa INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id INNER JOIN users u ON u.id = cm.user_id INNER JOIN committees c ON c.committee_id = cm.committee_id LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id
            WHERE 1 = 1 $committeeFilter $dateFilter ORDER BY c.committee_name, u.full_name, wa.assigned_date, wa.workload_id");
        $stmt->execute($params);
        $rows = array_map(static fn($r) => [$r['committee_name'], $r['jurisdiction_name'] ?: 'Unassigned', $r['member_name'], $r['member_role'], $r['task_title'], $r['task_description'] ?: '-', $r['priority'], $r['assigned_date'] ? formatDate($r['assigned_date']) : '-', $r['due_date'] ? formatDate($r['due_date']) : '-'], $stmt->fetchAll());
        return ['headers' => ['Committee', 'Jurisdiction', 'Member', 'Role', 'Assignment', 'Description', 'Priority', 'Assigned Date', 'Due Date'], 'rows' => $rows];
    }

    if ($type === 'performance') {
        $params = [];
        $filters = ["c.status = 'Active'"];
        if ($committeeId > 0) { $filters[] = 'c.committee_id = :cid'; $params[':cid'] = $committeeId; }
        $dateJoin = '';
        if ($dateFrom !== null) { $dateJoin .= ' AND wa.assigned_date >= :from_date'; $params[':from_date'] = $dateFrom; }
        if ($dateTo !== null) { $dateJoin .= ' AND wa.assigned_date <= :to_date'; $params[':to_date'] = $dateTo; }
        $stmt = $pdo->prepare("SELECT c.committee_id, c.committee_name, j.jurisdiction_name, cm.committee_member_id, cm.member_role, u.full_name, wa.workload_id
            FROM committees c LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id LEFT JOIN committee_members cm ON cm.committee_id = c.committee_id AND cm.status = 'Active' LEFT JOIN users u ON u.id = cm.user_id LEFT JOIN workload_assignments wa ON wa.committee_member_id = cm.committee_member_id $dateJoin
            WHERE " . implode(' AND ', $filters) . " ORDER BY c.committee_name, u.full_name, wa.assigned_date, wa.workload_id");
        $stmt->execute($params);
        $committees = [];
        $jurisdictions = [];
        $overall = ['total_assignments' => 0];
        foreach ($stmt->fetchAll() as $record) {
            $cid = (int)$record['committee_id'];
            if (!isset($committees[$cid])) $committees[$cid] = ['committee_id' => $cid, 'committee_name' => $record['committee_name'], 'jurisdiction_name' => $record['jurisdiction_name'], 'assignment_count' => 0, 'members' => []];
            if (!$record['committee_member_id']) continue;
            $mid = (int)$record['committee_member_id'];
            if (!isset($committees[$cid]['members'][$mid])) $committees[$cid]['members'][$mid] = ['committee_member_id' => $mid, 'member_name' => $record['full_name'], 'member_role' => $record['member_role'], 'assignment_count' => 0];
            if (!$record['workload_id']) continue;
            $committees[$cid]['assignment_count']++;
            $committees[$cid]['members'][$mid]['assignment_count']++;
            $jurisdiction = $record['jurisdiction_name'] ?: 'Unassigned';
            $jurisdictions[$jurisdiction] = ($jurisdictions[$jurisdiction] ?? 0) + 1;
            $overall['total_assignments']++;
        }
        $rows = [];
        foreach ($committees as &$committee) { $committee['members'] = array_values($committee['members']); $rows[] = [$committee['committee_name'], $committee['jurisdiction_name'] ?: 'Unassigned', $committee['assignment_count']]; }
        unset($committee);
        return ['headers' => ['Committee', 'Jurisdiction', 'Assignments'], 'rows' => $rows, 'performance_details' => array_values($committees), 'overall_metrics' => $overall, 'jurisdiction_counts' => $jurisdictions, 'export_headers' => ['Committee', 'Jurisdiction', 'Assignments'], 'export_rows' => $rows];
    }

    $where = $committeeId > 0 ? 'WHERE c.committee_id = :cid' : '';
    $stmt = $pdo->prepare("SELECT c.committee_name, j.jurisdiction_name, c.status, c.date_created, (SELECT COUNT(*) FROM committee_members cm WHERE cm.committee_id = c.committee_id AND cm.status = 'Active') AS member_count FROM committees c LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id $where ORDER BY c.committee_name");
    $stmt->execute($committeeId > 0 ? [':cid' => $committeeId] : []);
    $rows = array_map(static fn($r) => [$r['committee_name'], $r['jurisdiction_name'] ?: 'Unassigned', $r['member_count'], $r['status'], $r['date_created'] ? formatDate($r['date_created']) : '-'], $stmt->fetchAll());
    return ['headers' => ['Committee', 'Jurisdiction', 'Members', 'Status', 'Date Created'], 'rows' => $rows];
}
