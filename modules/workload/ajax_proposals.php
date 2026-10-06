<?php
/**
 * modules/workload/ajax_proposals.php
 * ------------------------------------------------------------------
 * GET: proposals relevant to the current user.
 *   - canManage() (Administrator/Chairperson): the legacy member-acceptance
 *     queue (Awaiting Response + Accepted) by default, or ?state=all for
 *     full history including terminal states.
 *   - Committee Member: only their own proposals (as the candidate);
 *     ?state=Awaiting%20Response narrows to just what needs their
 *     response, otherwise their full history.
 * Powers the legacy proposal widget on modules/workload/index.php and the
 * "Awaiting Your Response" widget on dashboard_member.php.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(false, 'GET requests only.');
}

session_write_close();

$pdo = db();
$stateFilter = clean((string)($_GET['state'] ?? ''));
$limit = max(1, min((int)($_GET['limit'] ?? 25), 100));

$where = [];
$params = [];

if (canManage()) {
    if ($stateFilter !== '' && $stateFilter !== 'all') {
        $where[] = 'p.state = :state';
        $params[':state'] = $stateFilter;
    } elseif ($stateFilter !== 'all') {
        $where[] = 'p.state IN ("Awaiting Response", "Accepted")';
    }
} elseif (isCommitteeMember() || isLegislativeOversight() || isSuperAdmin()) {
    // Oversight/system roles can see the same "mine" shape but will
    // simply have no committee_members row of their own, so this
    // naturally returns an empty list for them rather than erroring.
    $where[] = 'cm.user_id = :uid';
    $params[':uid'] = currentUserId();
    if ($stateFilter !== '' && $stateFilter !== 'all') {
        $where[] = 'p.state = :state';
        $params[':state'] = $stateFilter;
    }
} else {
    http_response_code(403);
    jsonResponse(false, 'You do not have permission to view proposals.');
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    $stmt = $pdo->prepare(
        "SELECT p.proposal_id, p.task_title, p.priority, p.due_date, p.state, p.proposed_at, p.responded_at, p.note,
                c.committee_name, u.full_name AS assignee_name, cm.user_id AS assignee_user_id,
                pb.full_name AS proposed_by_name
         FROM workload_assignment_proposals p
         INNER JOIN committee_members cm ON cm.committee_member_id = p.committee_member_id
         INNER JOIN users u ON u.id = cm.user_id
         INNER JOIN committees c ON c.committee_id = cm.committee_id
         LEFT JOIN users pb ON pb.id = p.proposed_by
         $whereSql
         ORDER BY p.proposal_id DESC
         LIMIT $limit"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $proposals = array_map(static function (array $r): array {
        return [
            'proposal_id' => (int)$r['proposal_id'],
            'task_title' => $r['task_title'],
            'priority' => $r['priority'],
            'due_date' => $r['due_date'],
            'due_date_human' => $r['due_date'] ? formatDate($r['due_date']) : null,
            'state' => $r['state'],
            'proposed_at' => $r['proposed_at'],
            'proposed_at_human' => timeAgo($r['proposed_at']),
            'responded_at' => $r['responded_at'],
            'note' => $r['note'],
            'committee_name' => $r['committee_name'],
            'assignee_name' => $r['assignee_name'],
            'assignee_user_id' => (int)$r['assignee_user_id'],
            'proposed_by_name' => $r['proposed_by_name'],
        ];
    }, $rows);

    jsonResponse(true, '', ['proposals' => $proposals]);
} catch (Throwable $e) {
    error_log('Proposals list error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while loading proposals.');
}
