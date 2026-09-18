# Hybrid Smart Workload Distribution — Architecture

This extends the existing rule-based engine (`includes/WorkloadAI.php`,
documented in `AI_WORKLOAD_ALGORITHM.md`) with an optional local AI
decision-support layer. **The rule-based engine is unchanged and remains
authoritative.** The AI layer is additive, best-effort, and fails safe.

```
MySQL Database
      |
Existing WorkloadAI.php  (unchanged)
      |
Quantitative Candidate Analysis + Suitability Scores
      |
Structured candidate payload (member_id, metrics, rule_based_score only)
      |
includes/OllamaAI.php  --->  Ollama Local API (http://localhost:11434)
      |                              |
      |                        Qwen2.5 3B (format=json)
      |                              |
      +------ validated JSON --------+
      |
AI Recommendation + Reasoning + Risk Assessment (or fallback)
      |
Rule-Based vs. AI comparison panel  ->  Administrator decides
```

## What's new

| File | Purpose |
|---|---|
| `includes/ai_config.php` | Default Ollama constants (fallback only). |
| `includes/OllamaAI.php` | The AI service: availability check, prompt building, strict JSON validation, fallback handling. |
| `database/migration_ai_hybrid.sql` | Adds `ai_system_settings` table; extends `ai_recommendations` with AI columns. |
| `modules/workload/ajax_ai_recommend.php` | Now runs the rule-based engine (unchanged) **then** calls `OllamaAI::analyze()`, computes agreement, logs both sides. |
| `modules/workload/ajax_test_ai_connection.php` | Admin "Test AI Connection" button. |
| `modules/workload/ajax_save_ai_settings.php` | Admin saves Ollama URL/model/timeout/enabled. |
| `modules/workload/ai_settings.php` | Adds the "Local AI (Ollama)" panel and an AI-aware recommendations log. |
| `modules/workload/index.php` | Assign Task modal now shows a two-column Rule-Based vs. AI panel with an agreement badge. |
| `assets/js/workload.js` | Renders the two-column panel; forwards in-progress task fields (title/priority/points/due date) as read-only context to the AI call. |
| `assets/js/ai-ollama-settings.js` | New — saves settings and drives the Test Connection button. |

## What did NOT change

- `includes/WorkloadAI.php` — byte-for-byte the same rule-based engine.
- The Suitability Score, normalization, ranking, and confidence logic.
- `ai_weight_config` and the existing factor-weight admin UI.
- Any authentication, routing, or unrelated pages.

## Fail-safe behavior

`OllamaAI::analyze()` never throws. Every failure path (disabled, server
unreachable, model not pulled, request timeout, malformed/invalid JSON)
returns a structured `{ai_available: false, warning: "..."}` object. The
rule-based recommendation from `WorkloadAI` is always returned and always
usable regardless of what the AI does. The UI shows:

> "AI analysis is currently unavailable. Recommendation generated using
> the rule-based workload scoring engine."

## Security notes

- The AI only ever receives: member_id, name, and the seven numeric/role
  metrics already computed by the rule-based engine, plus the in-progress
  task's title/priority/points/due date. No emails, no free-text task
  descriptions, no other PII.
- `OllamaAI` talks only to the configured local `OLLAMA_URL`; there is no
  code path to any external/cloud endpoint.
- AI output is never executed, never used to build SQL, and never written
  to the database until `validateAndSanitize()` confirms the recommended
  member id (and every alternative id) was actually present in the
  candidate pool sent to it. Anything else is discarded.

## Two integration points you'll need to wire up in your own copies

I didn't have these files, so I couldn't edit them directly — but the
hooks are small:

1. **`includes/auth.php`** — `ajax_save_ai_settings.php` calls
   `requireCsrf()`. If your CSRF helper has a different name (e.g. it's
   normally checked inline in each `ajax_save_*.php`), swap the call to
   match your existing pattern — every other `ajax_*.php` you provided
   does its own thing here, so I couldn't guess the exact one safely.
2. **`ajax_save.php`** (task save handler, not provided) — once a task is
   actually saved, call `WorkloadAI::markOutcome()` (unchanged) as before,
   and additionally set `admin_followed_ai` on the same `ai_recommendations`
   row:
   ```php
   $pdo->prepare("UPDATE ai_recommendations SET admin_followed_ai = (ai_recommended_member_id IS NOT NULL AND ai_recommended_member_id = :final) WHERE recommendation_id = :id")
       ->execute([':final' => $finalCommitteeMemberId, ':id' => $recommendationId]);
   ```

## Setup

```bash
# 1. Install Ollama and pull the model (one-time, local machine)
ollama pull qwen2.5:1.5b

# 2. Run the migration
mysql -u root -p committee_management_db < database/migration_ai_hybrid.sql

# 3. Drop in the new/changed files listed above.

# 4. As Administrator: Workload Distribution -> Smart AI Settings ->
#    Local AI (Ollama) -> confirm URL/model -> Test AI Connection.
```

If Ollama isn't installed or running, the workload module keeps working
exactly as it did before this change — the AI panel simply shows the
fallback message and the rule-based recommendation is used as-is.
