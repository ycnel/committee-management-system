<?php
/**
 * modules/workload/ajax_ai_recommend.php
 * ------------------------------------------------------------------
 * "Generate with AI" for the Assign Task modal.
 *
 * CHANGED WORKFLOW: this endpoint no longer runs WorkloadAI's
 * rule-based scoring engine. WorkloadAI.php is intentionally NOT
 * required or used here — it's left completely untouched for
 * anything else in the system that still depends on it, but this
 * workflow bypasses it entirely per the new design.
 *
 * New flow:
 *   1. Require committee_id + task_title (AI needs a title to work from).
 *   2. Pull the committee's ACTUAL active members and their real,
 *      current workload metrics straight from the database.
 *   3. Send title + real data to the Gemini service.
 *   4. Validate everything the AI returns (already done inside
 *      OllamaAI, but the member id is re-checked here too as a
 *      second, independent guard).
 *   5. Log the attempt (available or not) and return the result.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/GeminiAI.php';
requireLogin();
// This is part of the Assign Task workflow -- only Committee Chairperson
// (the role that assigns tasks) may trigger AI-assisted generation.
if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');

$committeeId = (int)($_GET['committee_id'] ?? 0);
$taskTitle    = trim((string)($_GET['task_title'] ?? ''));

if ($committeeId <= 0) jsonResponse(false, 'Invalid committee id.');
if ($taskTitle === '') jsonResponse(false, 'Please enter a task title first, then click Generate with AI.');
if (mb_strlen($taskTitle) > 255) jsonResponse(false, 'Task title is too long.');

$pdo = db();

$chk = $pdo->prepare('SELECT committee_name FROM committees WHERE committee_id = :id');
$chk->execute([':id' => $committeeId]);
$committee = $chk->fetch();
if (!$committee) jsonResponse(false, 'Committee not found.');

/**
 * ---- Gather ACTUAL committee/member/workload data directly ----
 * Mirrors the same real metrics WorkloadAI computes internally, but
 * queried here independently so this workflow has no dependency on
 * WorkloadAI's rule-based scoring/ranking logic.
 */
function cmas_ai_current_workload(PDO $pdo, int $committeeMemberId): float
{
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(workload_points), 0) FROM workload_assignments
         WHERE committee_member_id = :id AND status IN ('Pending','In Progress')"
    );
    $stmt->execute([':id' => $committeeMemberId]);
    return (float)$stmt->fetchColumn();
}

function cmas_ai_active_committee_count(PDO $pdo, int $userId): float
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM committee_members WHERE user_id = :uid AND status = 'Active'");
    $stmt->execute([':uid' => $userId]);
    return (float)$stmt->fetchColumn();
}

function cmas_ai_completion_rate(PDO $pdo, int $userId): float
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(wa.workload_id) AS total, SUM(CASE WHEN wa.status = 'Completed' THEN 1 ELSE 0 END) AS completed
         FROM workload_assignments wa
         INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
         WHERE cm.user_id = :uid"
    );
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch();
    $total = (int)($row['total'] ?? 0);
    if ($total === 0) return 50.0; // neutral, no history yet
    return round(((int)$row['completed'] / $total) * 100, 1);
}

function cmas_ai_timeliness(PDO $pdo, int $userId): float
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total_completed,
                SUM(CASE WHEN wa.completion_date <= wa.due_date OR wa.due_date IS NULL THEN 1 ELSE 0 END) AS on_time
         FROM workload_assignments wa
         INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
         WHERE cm.user_id = :uid AND wa.status = 'Completed'"
    );
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch();
    $total = (int)($row['total_completed'] ?? 0);
    if ($total === 0) return 50.0;
    return round(((int)$row['on_time'] / $total) * 100, 1);
}

function cmas_ai_overdue_count(PDO $pdo, int $committeeMemberId): float
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM workload_assignments
         WHERE committee_member_id = :id AND status != 'Completed'
           AND due_date IS NOT NULL AND due_date < CURDATE()"
    );
    $stmt->execute([':id' => $committeeMemberId]);
    return (float)$stmt->fetchColumn();
}

function cmas_ai_assignment_recency_days(PDO $pdo, int $committeeMemberId): float
{
    $stmt = $pdo->prepare("SELECT MAX(assigned_date) FROM workload_assignments WHERE committee_member_id = :id");
    $stmt->execute([':id' => $committeeMemberId]);
    $last = $stmt->fetchColumn();
    if (!$last) return 999.0; // never assigned anything -> maximally "due" for fair rotation
    return (float)((new DateTime())->diff(new DateTime($last))->days);
}

try {
    $memberStmt = $pdo->prepare(
        "SELECT cm.committee_member_id, cm.member_role, cm.user_id, u.full_name
         FROM committee_members cm
         INNER JOIN users u ON u.id = cm.user_id
         WHERE cm.committee_id = :cid AND cm.status = 'Active'"
    );
    $memberStmt->execute([':cid' => $committeeId]);
    $rows = $memberStmt->fetchAll();

    if (empty($rows)) {
        jsonResponse(true, 'This committee has no active members yet.', ['result' => null]);
    }

    $members = [];
    foreach ($rows as $m) {
        $cmId = (int)$m['committee_member_id'];
        $userId = (int)$m['user_id'];
        $members[] = [
            'member_id' => $cmId,
            'name' => $m['full_name'],
            'role' => $m['member_role'],
            'current_workload' => cmas_ai_current_workload($pdo, $cmId),
            'active_committees' => cmas_ai_active_committee_count($pdo, $userId),
            'completion_rate' => cmas_ai_completion_rate($pdo, $userId),
            'on_time_rate' => cmas_ai_timeliness($pdo, $userId),
            'overdue_tasks' => cmas_ai_overdue_count($pdo, $cmId),
            'days_since_last_assignment' => cmas_ai_assignment_recency_days($pdo, $cmId),
        ];
    }

    $validIds = array_column($members, 'member_id');
    $candidatesHash = hash('sha256', $taskTitle . '|' . $committeeId . '|' . json_encode($members));

    // ---- Reuse a very recent identical request (title unchanged, data unchanged) ----
    $cacheMinutes = defined('GEMINI_CACHE_MINUTES') ? GEMINI_CACHE_MINUTES : 10;
    $cacheStmt = $pdo->prepare(
        "SELECT ai_available, ai_recommended_member_id, ai_generated_fields, ai_reasoning,
                ai_model_used, ai_response_time_ms, ai_warning
         FROM ai_recommendations
         WHERE committee_id = :cid AND candidates_hash = :hash
           AND generated_at >= DATE_SUB(NOW(), INTERVAL :mins MINUTE)
         ORDER BY generated_at DESC LIMIT 1"
    );
    $cacheStmt->execute([':cid' => $committeeId, ':hash' => $candidatesHash, ':mins' => $cacheMinutes]);
    $cached = $cacheStmt->fetch();

    if ($cached && (int)$cached['ai_available'] === 1) {
        $fields = json_decode($cached['ai_generated_fields'] ?? '{}', true) ?: [];
        $aiResult = array_merge([
            'ai_available' => true,
            'recommended_member_id' => (int)$cached['ai_recommended_member_id'],
            'reasoning' => $cached['ai_reasoning'],
            'model_used' => $cached['ai_model_used'],
            'response_time_ms' => (int)$cached['ai_response_time_ms'],
            'warning' => $cached['ai_warning'],
            'from_cache' => true,
        ], $fields);
    } else {
        $gemini = new GeminiAI($pdo);
        $aiResult = $gemini->generateTaskRecommendation($taskTitle, $committee['committee_name'], $members);
        $aiResult['from_cache'] = false;

        // Independent second guard: never let an invented member id through,
        // even if the service's own validation is ever changed later.
        if ($aiResult['ai_available'] && !in_array((int)$aiResult['recommended_member_id'], $validIds, true)) {
            $aiResult['ai_available'] = false;
            $aiResult['warning'] = 'AI recommended a member that is not on this committee. Please fill in the task details manually.';
        }
    }

    // ---- Log this attempt ----
    $generatedFields = [
        'description' => $aiResult['description'] ?? null,
        'priority' => $aiResult['priority'] ?? null,
        'workload_points' => $aiResult['workload_points'] ?? null,
        'due_date' => $aiResult['due_date'] ?? null,
        'status' => $aiResult['status'] ?? null,
    ];

    $insertStmt = $pdo->prepare(
        "INSERT INTO ai_recommendations
            (committee_id, candidates_json, ai_available, ai_recommended_member_id, ai_generated_fields,
             ai_reasoning, ai_model_used, ai_response_time_ms, ai_warning, candidates_hash, generated_by, generated_at)
         VALUES
            (:cid, :members_json, :avail, :arid, :fields, :reasoning, :model, :rtime, :warn, :hash, :by, NOW())"
    );
    $insertStmt->execute([
        ':cid' => $committeeId,
        ':members_json' => json_encode(['task_title' => $taskTitle, 'members' => $members], JSON_UNESCAPED_UNICODE),
        ':avail' => $aiResult['ai_available'] ? 1 : 0,
        ':arid' => $aiResult['recommended_member_id'] ?: null,
        ':fields' => json_encode($generatedFields, JSON_UNESCAPED_UNICODE),
        ':reasoning' => $aiResult['reasoning'] ?? null,
        ':model' => $aiResult['model_used'] ?? null,
        ':rtime' => $aiResult['response_time_ms'] ?? null,
        ':warn' => $aiResult['warning'] ?? null,
        ':hash' => $candidatesHash,
        ':by' => currentUserId(),
    ]);
    $recommendationId = (int)$pdo->lastInsertId();

    $aiResult['recommendation_id'] = $recommendationId;
    $aiResult['members'] = $members; // so the frontend can show the recommended member's name/role without a second lookup

    jsonResponse(true, '', ['result' => $aiResult]);

} catch (Throwable $e) {
    error_log('GeminiAI task generation error: ' . $e->getMessage());
    jsonResponse(false, 'The AI recommendation engine could not be run right now.');
}
