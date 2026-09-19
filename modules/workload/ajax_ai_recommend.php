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
 *      current assignment metrics straight from the database.
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
$taskContext  = [
    'description' => trim((string)($_GET['task_description'] ?? '')),
    'priority' => trim((string)($_GET['priority'] ?? '')),
];

if ($committeeId <= 0) jsonResponse(false, 'Invalid committee id.');
if ($taskTitle === '') jsonResponse(false, 'Please enter a task title first, then click Generate with AI.');
if (mb_strlen($taskTitle) > 255) jsonResponse(false, 'Task title is too long.');

$pdo = db();

$chk = $pdo->prepare(
    'SELECT c.committee_name, j.jurisdiction_name, j.category
     FROM committees c
     LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id
     WHERE c.committee_id = :id'
);
$chk->execute([':id' => $committeeId]);
$committee = $chk->fetch();
if (!$committee) jsonResponse(false, 'Committee not found.');

/**
 * ---- Gather ACTUAL committee/member/workload data directly ----
 * Gathers factual assignment indicators directly so this workflow has
 * no dependency on arbitrary workload-point scoring.
 */
function cmas_ai_active_assignments(PDO $pdo, int $committeeMemberId): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM workload_assignments
         WHERE committee_member_id = :id"
    );
    $stmt->execute([':id' => $committeeMemberId]);
    return (int)$stmt->fetchColumn();
}

function cmas_ai_active_committee_count(PDO $pdo, int $userId): float
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM committee_members WHERE user_id = :uid AND status = 'Active'");
    $stmt->execute([':uid' => $userId]);
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

function cmas_ai_previous_assignments(PDO $pdo, int $committeeMemberId): array
{
    $stmt = $pdo->prepare(
        "SELECT task_title, priority, due_date
         FROM workload_assignments
         WHERE committee_member_id = :id
         ORDER BY created_at DESC LIMIT 8"
    );
    $stmt->execute([':id' => $committeeMemberId]);
    return array_map(static function (array $task): array {
        return [
            'title' => (string)$task['task_title'],
            'priority' => (string)$task['priority'],
            'due_date' => $task['due_date'],
        ];
    }, $stmt->fetchAll());
}

try {
    $memberStmt = $pdo->prepare(
        "SELECT cm.committee_member_id, cm.member_role, cm.user_id, u.full_name,
            ub.highest_education, ub.degree_course, ub.school_university,
            ub.major_specialization, ub.certifications_training,
            ub.current_profession, ub.years_experience, ub.previous_positions,
            ub.previous_organizations, ub.government_experience,
            ub.primary_expertise, ub.secondary_expertise, ub.knowledge_areas,
            ub.relevant_skills, ub.committee_expertise, ub.expertise_keywords
         FROM committee_members cm
         INNER JOIN users u ON u.id = cm.user_id
         LEFT JOIN user_background ub ON ub.user_id = u.id
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
            'active_assignments' => cmas_ai_active_assignments($pdo, $cmId),
            'active_committees' => cmas_ai_active_committee_count($pdo, $userId),
            'days_since_last_assignment' => cmas_ai_assignment_recency_days($pdo, $cmId),
            'background' => [
                'highest_education' => $m['highest_education'],
                'degree_course' => $m['degree_course'],
                'school_university' => $m['school_university'],
                'major_specialization' => $m['major_specialization'],
                'certifications_training' => $m['certifications_training'],
                'current_profession' => $m['current_profession'],
                'years_experience' => $m['years_experience'] === null ? null : (int)$m['years_experience'],
                'previous_positions' => $m['previous_positions'],
                'previous_organizations' => $m['previous_organizations'],
                'government_experience' => $m['government_experience'],
                'primary_expertise' => $m['primary_expertise'],
                'secondary_expertise' => $m['secondary_expertise'],
                'knowledge_areas' => $m['knowledge_areas'],
                'relevant_skills' => $m['relevant_skills'],
                'committee_expertise' => $m['committee_expertise'],
                'expertise_keywords' => $m['expertise_keywords'],
            ],
            'previous_assignments' => cmas_ai_previous_assignments($pdo, $cmId),
        ];
    }

    $validIds = array_column($members, 'member_id');
    // Version the recommendation policy so cached results created under an
    // older priority order cannot bypass the profile-first logic.
    $recommendationPolicyVersion = 'profile-first-v1';
    $candidatesHash = hash('sha256', json_encode([$recommendationPolicyVersion, $taskTitle, $taskContext, $committeeId, $members], JSON_UNESCAPED_UNICODE));

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
        $aiResult = $gemini->generateTaskRecommendation(
            $taskTitle,
            $committee['committee_name'],
            $members,
            array_merge($taskContext, [
                'jurisdiction' => $committee['jurisdiction_name'],
                'jurisdiction_category' => $committee['category'],
            ])
        );
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
        'due_date' => $aiResult['due_date'] ?? null,
        'expertise_match' => $aiResult['expertise_match'] ?? null,
        'experience_match' => $aiResult['experience_match'] ?? null,
        'workload_factor' => $aiResult['workload_factor'] ?? null,
        'committee_relevance' => $aiResult['committee_relevance'] ?? null,
        'overall_relevance' => $aiResult['overall_relevance'] ?? null,
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
