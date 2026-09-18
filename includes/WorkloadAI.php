<?php
/**
 * includes/WorkloadAI.php
 * ------------------------------------------------------------------
 * Smart AI Workload Distribution engine.
 *
 * This is a RULE-BASED WEIGHTED SCORING model, not a generative or
 * machine-learning model. Every score it produces is a deterministic,
 * explainable function of real data already in the database — the
 * same recommendation given the same data will always be reproduced,
 * and every factor that contributed to it can be shown to the admin.
 *
 * Algorithm (see docs/AI_WORKLOAD_ALGORITHM.md for the full writeup):
 *   1. For the target committee, gather every Active member and compute
 *      their raw value for each enabled factor in ai_weight_config.
 *   2. Min-max normalize each factor to a 0-100 scale *within this
 *      candidate pool* (so factors on wildly different scales, like
 *      "workload points" vs "days since last task", become comparable).
 *   3. Multiply each normalized factor by its configured weight and
 *      sum them into a single Suitability Score (0-100).
 *   4. Rank candidates by score; the top candidate is the Recommended
 *      Member, the rest are Alternative Candidates.
 *   5. Generate plain-language reasoning from each candidate's
 *      strongest and weakest normalized factors.
 *   6. Estimate a Confidence level from the score gap between the
 *      top two candidates and how much task history exists to score
 *      the top candidate against.
 * ------------------------------------------------------------------
 */

class WorkloadAI
{
    private PDO $pdo;
    private array $weights = []; // factor_key => ['weight' => float 0..1, 'direction' => ..., 'label' => ...]

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->loadWeights();
    }

    /**
     * Load enabled factor weights and normalize them to sum to 1.0
     * across only the enabled factors, so disabling a factor never
     * silently changes what the remaining weights mean.
     */
    private function loadWeights(): void
    {
        $stmt = $this->pdo->query(
            "SELECT factor_key, factor_label, description, weight, direction
             FROM ai_weight_config WHERE is_enabled = 1"
        );
        $rows = $stmt->fetchAll();
        $totalWeight = array_sum(array_column($rows, 'weight'));
        if ($totalWeight <= 0) $totalWeight = 1; // guard against all-zero config

        foreach ($rows as $row) {
            $this->weights[$row['factor_key']] = [
                'label' => $row['factor_label'],
                'description' => $row['description'],
                'direction' => $row['direction'],
                'weight' => $row['weight'] / $totalWeight,
                'raw_weight' => (int)$row['weight'],
            ];
        }
    }

    public function getWeightConfig(): array
    {
        return $this->weights;
    }

    /**
     * Core entry point: recommend the best committee member for a new
     * task on the given committee. Returns null if the committee has
     * no active members to score.
     */
    public function recommend(int $committeeId, ?int $currentUserId = null): ?array
    {
        $candidates = $this->collectCandidates($committeeId);
        if (empty($candidates)) return null;

        $normalized = $this->normalizeFactors($candidates);
        $scored = $this->scoreAndRank($normalized);

        $top = $scored[0];
        $alternatives = array_slice($scored, 1);

        $confidence = $this->estimateConfidence($scored, $top);

        return [
            'committee_id' => $committeeId,
            'recommended' => $top,
            'alternatives' => $alternatives,
            'confidence' => $confidence,
            'weights_used' => $this->weights,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Gather every active member of the committee along with the raw,
     * un-normalized value of every factor the engine currently knows
     * how to compute.
     */
    private function collectCandidates(int $committeeId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT cm.committee_member_id, cm.member_role, cm.user_id, u.full_name
             FROM committee_members cm
             INNER JOIN users u ON u.id = cm.user_id
             WHERE cm.committee_id = :cid AND cm.status = 'Active'"
        );
        $stmt->execute([':cid' => $committeeId]);
        $members = $stmt->fetchAll();

        $candidates = [];
        foreach ($members as $m) {
            $cmId = (int)$m['committee_member_id'];
            $userId = (int)$m['user_id'];

            $candidates[] = [
                'committee_member_id' => $cmId,
                'user_id' => $userId,
                'full_name' => $m['full_name'],
                'member_role' => $m['member_role'],
                'raw' => [
                    'current_workload'   => $this->currentWorkload($cmId),
                    'active_committees'  => $this->activeCommitteeCount($userId),
                    'completion_rate'    => $this->completionRate($userId),
                    'timeliness'         => $this->timeliness($userId),
                    'overdue_count'      => $this->overdueCount($cmId),
                    'assignment_recency' => $this->assignmentRecencyDays($cmId),
                    'role_weight'        => $this->roleWeight($m['member_role']),
                ],
            ];
        }
        return $candidates;
    }

    private function currentWorkload(int $committeeMemberId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(workload_points), 0) FROM workload_assignments
             WHERE committee_member_id = :id AND status IN ('Pending','In Progress')"
        );
        $stmt->execute([':id' => $committeeMemberId]);
        return (float)$stmt->fetchColumn();
    }

    private function activeCommitteeCount(int $userId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM committee_members WHERE user_id = :uid AND status = 'Active'"
        );
        $stmt->execute([':uid' => $userId]);
        return (float)$stmt->fetchColumn();
    }

    private function completionRate(int $userId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(wa.workload_id) AS total,
                SUM(CASE WHEN wa.status = 'Completed' THEN 1 ELSE 0 END) AS completed
             FROM workload_assignments wa
             INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
             WHERE cm.user_id = :uid"
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        $total = (int)($row['total'] ?? 0);
        if ($total === 0) return 50.0; // neutral score for a member with no history yet
        return round(((int)$row['completed'] / $total) * 100, 1);
    }

    private function timeliness(int $userId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS total_completed,
                SUM(CASE WHEN wa.completion_date <= wa.due_date OR wa.due_date IS NULL THEN 1 ELSE 0 END) AS on_time
             FROM workload_assignments wa
             INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
             WHERE cm.user_id = :uid AND wa.status = 'Completed'"
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        $total = (int)($row['total_completed'] ?? 0);
        if ($total === 0) return 50.0; // neutral score, no completed-task history yet
        return round(((int)$row['on_time'] / $total) * 100, 1);
    }

    private function overdueCount(int $committeeMemberId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM workload_assignments
             WHERE committee_member_id = :id AND status != 'Completed'
               AND due_date IS NOT NULL AND due_date < CURDATE()"
        );
        $stmt->execute([':id' => $committeeMemberId]);
        return (float)$stmt->fetchColumn();
    }

    private function assignmentRecencyDays(int $committeeMemberId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT MAX(assigned_date) FROM workload_assignments WHERE committee_member_id = :id"
        );
        $stmt->execute([':id' => $committeeMemberId]);
        $last = $stmt->fetchColumn();
        if (!$last) return 999.0; // never assigned anything -> maximally "due" for fair rotation
        return (float)((new DateTime())->diff(new DateTime($last))->days);
    }

    /**
     * Base trust value by committee role. This mapping itself is not
     * yet admin-configurable (only the overall factor weight is) —
     * documented as a known follow-up in docs/AI_WORKLOAD_ALGORITHM.md.
     */
    private function roleWeight(string $role): float
    {
        return match ($role) {
            'Chairperson' => 100.0,
            'Vice Chairperson' => 75.0,
            default => 50.0,
        };
    }

    /**
     * Min-max normalize every factor to 0-100 within this candidate
     * pool, flipping the scale for "lower_is_better" factors so that
     * 100 always means "best" after normalization.
     */
    private function normalizeFactors(array $candidates): array
    {
        $factorKeys = array_keys($this->weights);
        $minMax = [];
        foreach ($factorKeys as $key) {
            $values = array_column(array_column($candidates, 'raw'), $key);
            $minMax[$key] = ['min' => min($values), 'max' => max($values)];
        }

        foreach ($candidates as &$c) {
            $c['normalized'] = [];
            foreach ($factorKeys as $key) {
                $val = $c['raw'][$key];
                $min = $minMax[$key]['min'];
                $max = $minMax[$key]['max'];
                if ($max - $min == 0) {
                    $norm = 100.0; // every candidate is tied on this factor -> treat as equally good
                } else {
                    $norm = ($val - $min) / ($max - $min) * 100.0;
                    if ($this->weights[$key]['direction'] === 'lower_is_better') {
                        $norm = 100.0 - $norm;
                    }
                }
                $c['normalized'][$key] = round($norm, 1);
            }
        }
        unset($c);
        return $candidates;
    }

    private function scoreAndRank(array $candidates): array
    {
        foreach ($candidates as &$c) {
            $score = 0.0;
            foreach ($this->weights as $key => $w) {
                $score += ($c['normalized'][$key] ?? 0) * $w['weight'];
            }
            $c['suitability_score'] = round($score, 1);
            $c['reasoning'] = $this->buildReasoning($c);
        }
        unset($c);

        usort($candidates, fn($a, $b) => $b['suitability_score'] <=> $a['suitability_score']);
        return $candidates;
    }

    /**
     * Plain-language explanation built from each candidate's strongest
     * and weakest normalized factors, so "View AI Explanation" never
     * shows a bare number with no justification.
     */
    private function buildReasoning(array $candidate): array
    {
        $norm = $candidate['normalized'];
        arsort($norm);
        $sorted = array_keys($norm);
        $strongest = array_slice($sorted, 0, 2);
        $weakest = array_slice($sorted, -1);

        $reasons = [];
        foreach ($strongest as $key) {
            $reasons[] = 'Strong on ' . $this->weights[$key]['label'] . ' (' . $norm[$key] . '/100).';
        }
        foreach ($weakest as $key) {
            if ($norm[$key] < 50) {
                $reasons[] = 'Weaker on ' . $this->weights[$key]['label'] . ' (' . $norm[$key] . '/100).';
            }
        }
        return $reasons;
    }

    /**
     * Confidence reflects two things: how clearly the top candidate
     * beats the runner-up (a wide gap = high confidence), and whether
     * the top candidate actually has task history to score against
     * (a brand-new member sitting at neutral defaults is a lower-
     * confidence recommendation even with a decent score).
     */
    private function estimateConfidence(array $ranked, array $top): string
    {
        if (count($ranked) < 2) return 'Medium'; // only one candidate, nothing to compare against

        $gap = $top['suitability_score'] - $ranked[1]['suitability_score'];
        $hasHistory = ($top['raw']['completion_rate'] ?? 50) != 50.0 || ($top['raw']['timeliness'] ?? 50) != 50.0;

        if ($gap >= 15 && $hasHistory) return 'High';
        if ($gap >= 6) return 'Medium';
        return 'Low';
    }

    /**
     * Persist a generated recommendation to ai_recommendations for
     * audit/history purposes. Call this after recommend() if the
     * caller wants the recommendation logged (e.g. shown in the
     * Assign Task modal), not on every silent recalculation.
     */
    public function logRecommendation(array $result, int $generatedByUserId, ?int $workloadId = null): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO ai_recommendations
                (committee_id, workload_id, recommended_member_id, recommended_score, confidence, candidates_json, generated_by, generated_at)
             VALUES (:cid, :wid, :rmid, :score, :conf, :json, :by, NOW())"
        );
        $stmt->execute([
            ':cid' => $result['committee_id'],
            ':wid' => $workloadId,
            ':rmid' => $result['recommended']['committee_member_id'],
            ':score' => $result['recommended']['suitability_score'],
            ':conf' => $result['confidence'],
            ':json' => json_encode($result, JSON_UNESCAPED_UNICODE),
            ':by' => $generatedByUserId,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Marks a previously-logged recommendation as overridden once the
     * admin actually assigns the task to someone other than the top
     * recommendation, and records who they picked instead. Called from
     * ajax_save.php in the workload module once the task is saved.
     */
    public function markOutcome(int $recommendationId, int $finalCommitteeMemberId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE ai_recommendations
             SET final_member_id = :final,
                 was_overridden = (recommended_member_id IS NOT NULL AND recommended_member_id != :final2)
             WHERE recommendation_id = :id"
        );
        $stmt->execute([':final' => $finalCommitteeMemberId, ':final2' => $finalCommitteeMemberId, ':id' => $recommendationId]);
    }
}
