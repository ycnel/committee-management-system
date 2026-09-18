<?php
/**
 * includes/OllamaAI.php (Gemini provider)
 * ------------------------------------------------------------------
 * Google Gemini Task-Generation service for Smart Workload Distribution.
 *
 * CHANGED WORKFLOW: this class no longer scores candidates that were
 * already ranked by WorkloadAI's rule-based engine. Instead it is
 * given the Task Title plus the ACTUAL, real-time committee/member/
 * workload data (gathered directly by ajax_ai_recommend.php), and is
 * responsible for generating the whole task suggestion in one shot:
 * description, recommended member, priority, workload points, due
 * date, status, and its reasoning. WorkloadAI.php itself is untouched
 * and unused by this workflow — it's left in place for anything else
 * in the system that still depends on it.
 *
 * Hard guarantees (unchanged in spirit from before):
 *   - Sends only the prepared workload payload to the configured Gemini API.
 *   - Never invents a member — recommended_member_id is rejected
 *     unless it is one of the member_id values actually sent to it.
 *   - Never lets AI output reach the database or UI without passing
 *     strict JSON-shape + value validation first (priority/status
 *     enums, numeric ranges, valid non-past due dates).
 *   - Never throws out of generateTaskRecommendation() — every failure
 *     mode (API unavailable, model missing, timeout,
 *     malformed JSON, invalid member) degrades to a structured
 *     fallback so the Assign Task modal always stays usable.
 * ------------------------------------------------------------------
 */

class GeminiAI
{
    private PDO $pdo;
    private bool $enabled;
    private string $model;
    private string $apiKey;
    private int $timeout;
    private int $connectTimeout;

    private const VALID_PRIORITY = ['Low', 'Medium', 'High', 'Urgent'];
    private const VALID_STATUS = ['Pending', 'In Progress', 'Completed', 'Overdue'];
    private const MAX_DESCRIPTION_LEN = 1000;
    private const MAX_REASONING_LEN = 500;
    private const MIN_POINTS = 1;
    private const MAX_POINTS = 100;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->loadSettings();
    }

    /**
     * Read runtime overrides from ai_system_settings; anything not yet
     * saved there falls back to the constants in includes/ai_config.php.
     */
    private function loadSettings(): void
    {
        require_once __DIR__ . '/ai_config.php';

        $defaults = [
            'gemini_enabled' => GEMINI_ENABLED ? '1' : '0',
            'gemini_model'   => GEMINI_MODEL,
            'gemini_timeout' => (string)GEMINI_TIMEOUT,
        ];

        $stored = [];
        try {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM ai_system_settings");
            foreach ($stmt->fetchAll() as $row) {
                $stored[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            // Table may not exist yet if the migration hasn't been run.
            // Fall back to constants silently — this must never break the page.
        }

        $merged = array_merge($defaults, $stored);

        $this->enabled        = ($merged['gemini_enabled'] ?? '1') === '1';
        $this->model           = $merged['gemini_model'] ?? GEMINI_MODEL;
        if (in_array($this->model, ['gemini-2.5-flash', 'qwen2.5:1.5b'], true)) {
            $this->model = GEMINI_MODEL;
        }
        $this->apiKey          = trim((string)getenv('GEMINI_API_KEY'));
        $this->timeout         = max(5, (int)($merged['gemini_timeout'] ?? GEMINI_TIMEOUT));
        $this->connectTimeout = GEMINI_CONNECT_TIMEOUT;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getConfig(): array
    {
        return [
            'enabled' => $this->enabled,
            'model' => $this->model,
            'timeout' => $this->timeout,
        ];
    }

    /**
    * Persist a single setting (admin panel -> Save AI Settings).
    * Keys: gemini_enabled ('1'/'0'), gemini_model, gemini_timeout.
     */
    public static function saveSetting(PDO $pdo, string $key, string $value, ?int $updatedBy = null): void
    {
        $stmt = $pdo->prepare(
            "INSERT INTO ai_system_settings (setting_key, setting_value, updated_by, updated_at)
             VALUES (:k, :v, :by, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value),
                                     updated_by = VALUES(updated_by),
                                     updated_at = NOW()"
        );
        $stmt->execute([':k' => $key, ':v' => $value, ':by' => $updatedBy]);
    }

    /**
     * Lightweight "is the server up and does it know about our model"
     * check, used by both the Test Connection button and
     * generateTaskRecommendation()'s pre-flight guard. Runs no inference.
     */
    public function checkAvailability(): array
    {
        if (!$this->enabled) {
            return ['online' => false, 'model_found' => false, 'message' => 'Gemini AI is disabled in Smart AI Settings.'];
        }
        if ($this->apiKey === '') {
            return ['online' => false, 'model_found' => false, 'message' => 'GEMINI_API_KEY is not configured on the server.'];
        }

        $start = microtime(true);
        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models?key=' . rawurlencode($this->apiKey));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT => $this->connectTimeout + 2,
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $elapsedMs = (int)round((microtime(true) - $start) * 1000);

        if ($body === false || $httpCode !== 200) {
            return [
                'online' => false,
                'model_found' => false,
                'message' => $err ? "Cannot reach Gemini API ({$err})." : "Gemini API did not respond.",
                'response_time_ms' => $elapsedMs,
            ];
        }

        $data = json_decode($body, true);
        $models = array_map(function ($model) {
            return preg_replace('/^models\//', '', (string)($model['name'] ?? ''));
        }, $data['models'] ?? []);
        $modelFound = false;
        foreach ($models as $m) {
            if ($m === $this->model || strpos($m, $this->model) === 0) { $modelFound = true; break; }
        }

        return [
            'online' => true,
            'model_found' => $modelFound,
            'message' => $modelFound
                ? "Gemini API is online and \"{$this->model}\" is available."
                : "Gemini API is online, but \"{$this->model}\" was not found.",
            'response_time_ms' => $elapsedMs,
            'available_models' => $models,
        ];
    }

    /**
     * Public wrapper for the admin "Test AI Connection" button.
     */
    public function testConnection(): array
    {
        $status = $this->checkAvailability();
        $status['model'] = $this->model;
        $status['checked_at'] = date('Y-m-d H:i:s');
        return $status;
    }

    /**
     * Main entry point for the new workflow.
     *
     * $taskTitle   - what the admin typed into Task Title.
     * $committeeName - for prompt context only.
     * $members     - array of REAL active committee members, each with
     *                real workload metrics, gathered directly from the
     *                database by the caller (ajax_ai_recommend.php).
     *                Shape per member:
     *                  ['member_id'=>int,'name'=>string,'role'=>string,
     *                   'current_workload'=>float,'active_committees'=>float,
     *                   'completion_rate'=>float,'on_time_rate'=>float,
     *                   'overdue_tasks'=>float,'days_since_last_assignment'=>float]
     *
     * Always returns a well-formed array with an 'ai_available' key,
     * even on total failure, so the caller never has to special-case
     * "AI crashed" vs. "AI declined."
     */
    public function generateTaskRecommendation(string $taskTitle, string $committeeName, array $members): array
    {
        $start = microtime(true);

        if (!$this->enabled) {
            return $this->fallback('Local AI is disabled in Smart AI Settings.', $start);
        }
        if (trim($taskTitle) === '') {
            return $this->fallback('A task title is required before AI can generate a recommendation.', $start);
        }
        if (empty($members)) {
            return $this->fallback('This committee has no active members to consider.', $start);
        }

        $availability = $this->checkAvailability();
        if (!$availability['online']) {
            return $this->fallback($availability['message'], $start);
        }
        if (!$availability['model_found']) {
            return $this->fallback($availability['message'], $start);
        }

        $validIds = array_map('intval', array_column($members, 'member_id'));

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($taskTitle, $committeeName, $members);

        try {
            $raw = $this->callGemini($systemPrompt, $userPrompt);
        } catch (Throwable $e) {
            error_log('GeminiAI request error: ' . $e->getMessage());
            return $this->fallback('The Gemini API request failed: ' . $e->getMessage(), $start);
        }

        $responseTimeMs = (int)round((microtime(true) - $start) * 1000);

        $validated = $this->validateAndSanitize($raw, $validIds);
        if ($validated === null) {
            return $this->fallback('The local AI returned a response that could not be validated.', $start, $responseTimeMs);
        }

        $validated['ai_available'] = true;
        $validated['model_used'] = $this->model;
        $validated['response_time_ms'] = $responseTimeMs;
        $validated['warning'] = $validated['warning'] ?? null;
        return $validated;
    }

    private function fallback(string $reason, float $start, ?int $responseTimeMs = null): array
    {
        return [
            'ai_available' => false,
            'recommended_member_id' => null,
            'description' => '',
            'priority' => null,
            'workload_points' => null,
            'due_date' => null,
            'status' => null,
            'reasoning' => '',
            'warning' => 'AI task generation is currently unavailable. Please fill in the task details manually.',
            'error_detail' => $reason,
            'model_used' => $this->model,
            'response_time_ms' => $responseTimeMs ?? (int)round((microtime(true) - $start) * 1000),
        ];
    }

    private function buildSystemPrompt(): string
    {
        $today = date('Y-m-d');
        return <<<PROMPT
You are an AI Task Assignment Assistant for a Committee Management and Assignment System (CMAS).

Given a task title and the ACTUAL active members of a committee along with their real, current workload data, you generate a complete task suggestion.

Rules:
1. Never invent a member. recommended_member_id MUST be exactly one of the member_id values given to you.
2. Never invent numbers — base workload_points and your reasoning on the real data provided, not on assumptions.
3. Prefer members with lower current_workload, fewer overdue_tasks, and fewer active_committees.
4. Prefer members with higher completion_rate and higher on_time_rate.
5. Consider fairness: a member with a higher days_since_last_assignment has gone longer without new work and should be favored, all else being roughly equal.
6. Write a concise, professional task description (1-3 sentences) appropriate to the task title. Do not include placeholders like "[insert here]".
7. priority must be exactly one of: Low, Medium, High, Urgent.
8. status must be exactly one of: Pending, In Progress, Completed, Overdue — for a brand-new task this should almost always be "Pending".
9. workload_points must be a whole number from 1 to 100, proportional to how demanding the task sounds.
10. due_date must be in YYYY-MM-DD format, must be a real calendar date, and must NOT be before today ({$today}). Pick a reasonable deadline based on priority (e.g. Urgent = a few days out, Low = a few weeks out).
11. reasoning should be 1-3 short sentences explaining why you picked that member and those values, referencing the actual data (e.g. workload, completion rate, overdue tasks).
12. Return valid JSON only. No markdown, no code fences, no text outside the JSON object.

Respond with a single JSON object matching exactly this shape:
{
  "recommended_member_id": <int, must be one of the member_id values given>,
  "description": "<string>",
  "priority": "Low" | "Medium" | "High" | "Urgent",
  "workload_points": <int 1-100>,
  "due_date": "YYYY-MM-DD",
  "status": "Pending" | "In Progress" | "Completed" | "Overdue",
  "reasoning": "<string>"
}
PROMPT;
    }

    private function buildUserPrompt(string $taskTitle, string $committeeName, array $members): string
    {
        $payload = [
            'today' => date('Y-m-d'),
            'committee_name' => $committeeName,
            'task_title' => $taskTitle,
            'members' => array_map(function ($m) {
                return [
                    'member_id' => (int)$m['member_id'],
                    'name' => (string)$m['name'],
                    'role' => (string)$m['role'],
                    'current_workload' => $m['current_workload'],
                    'active_committees' => $m['active_committees'],
                    'completion_rate' => $m['completion_rate'],
                    'on_time_rate' => $m['on_time_rate'],
                    'overdue_tasks' => $m['overdue_tasks'],
                    'days_since_last_assignment' => $m['days_since_last_assignment'],
                ];
            }, $members),
        ];

        return "Generate the task recommendation JSON for this data:\n" .
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
    * Calls Gemini's generateContent endpoint with JSON response mode.
     */
    private function callGemini(string $systemPrompt, string $userPrompt): string
    {
        $body = [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $userPrompt]],
            ]],
            'generationConfig' => ['temperature' => 0.3, 'responseMimeType' => 'application/json'],
        ];

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($this->model) . ':generateContent?key=' . rawurlencode($this->apiKey);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno === CURLE_OPERATION_TIMEDOUT) {
            throw new RuntimeException('Gemini request timed out after ' . $this->timeout . 's.');
        }
        if ($response === false) {
            throw new RuntimeException('Could not connect to Gemini: ' . $err);
        }
        if ($httpCode !== 200) {
            throw new RuntimeException("Gemini returned HTTP {$httpCode}.");
        }

        $decoded = json_decode($response, true);
        $content = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('Gemini response had no message content.');
        }
        return $content;
    }

    /**
     * Strict validation of the model's JSON output. Returns null only
     * when the response cannot be trusted at all (bad shape, or a
     * member id that was never offered to it) — in which case the
     * caller falls back to a manual-entry state. Individually invalid
     * secondary fields (bad due date, out-of-range points, etc.) are
     * corrected to safe defaults rather than discarding the whole
     * response, and a 'warning' note is attached so the admin can see
     * what was adjusted.
     */
    private function validateAndSanitize(string $rawText, array $validMemberIds): ?array
    {
        $clean = trim($rawText);
        $clean = preg_replace('/^```json\s*|```$/i', '', $clean);
        $clean = trim($clean);

        $data = json_decode($clean, true);
        if (!is_array($data)) return null;

        if (!array_key_exists('recommended_member_id', $data)) return null;
        $recommendedId = (int)$data['recommended_member_id'];
        if (!in_array($recommendedId, $validMemberIds, true)) return null; // never trust an invented member

        $warnings = [];

        $description = is_string($data['description'] ?? null) ? trim(strip_tags($data['description'])) : '';
        if ($description === '') { $description = 'No description generated — please write one.'; $warnings[] = 'description was empty'; }
        $description = mb_substr($description, 0, self::MAX_DESCRIPTION_LEN);

        $priority = $data['priority'] ?? null;
        if (!in_array($priority, self::VALID_PRIORITY, true)) { $priority = 'Medium'; $warnings[] = 'priority defaulted to Medium'; }

        $status = $data['status'] ?? null;
        if (!in_array($status, self::VALID_STATUS, true)) { $status = 'Pending'; $warnings[] = 'status defaulted to Pending'; }

        $points = is_numeric($data['workload_points'] ?? null) ? (int)round((float)$data['workload_points']) : null;
        if ($points === null || $points < self::MIN_POINTS || $points > self::MAX_POINTS) {
            $points = max(self::MIN_POINTS, min(self::MAX_POINTS, $points ?? 5));
            $warnings[] = 'workload_points adjusted to a valid range';
        }

        $dueDate = is_string($data['due_date'] ?? null) ? trim($data['due_date']) : '';
        $today = new DateTime(date('Y-m-d'));
        $validDate = null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $d = DateTime::createFromFormat('Y-m-d', $dueDate);
            if ($d && $d->format('Y-m-d') === $dueDate && $d >= $today) {
                $validDate = $dueDate;
            }
        }
        if ($validDate === null) {
            $fallbackDue = (clone $today)->modify('+7 days');
            $validDate = $fallbackDue->format('Y-m-d');
            $warnings[] = 'due_date was missing/invalid/in the past — defaulted to 7 days from today';
        }

        $reasoning = is_string($data['reasoning'] ?? null) ? trim(strip_tags($data['reasoning'])) : '';
        $reasoning = mb_substr($reasoning, 0, self::MAX_REASONING_LEN);

            return [
            'recommended_member_id' => $recommendedId,
            'description' => $description,
            'priority' => $priority,
            'workload_points' => $points,
            'due_date' => $validDate,
            'status' => $status,
            'reasoning' => $reasoning,
            'warning' => !empty($warnings) ? ('AI response needed minor corrections: ' . implode('; ', $warnings) . '.') : null,
        ];
    }
}
