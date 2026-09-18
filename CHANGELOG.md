# Changelog

## Unreleased — Role & Permission Revision (Administrator / Committee Chairperson / Committee Member)

Reworks the permission model across Committee Management, Workload
Distribution / Task Assignment, and Smart AI Settings. No UI, layout,
database structure, or unrelated feature was redesigned — existing pages,
tables, and modals are reused as-is; only role gating (and the "Legislative
Staff" role's display name) changed.

### Changed

- **Role renamed**: "Legislative Staff" → "Committee Chairperson"
  (`config/config.php`'s `ROLE_STAFF` constant value; `roles` table row,
  id unchanged). Existing databases: run
  `database/migration_role_rename.sql` once.
- **`canManage()`** (`includes/auth.php`) now means Committee Chairperson
  only (previously Administrator + Staff). This single change is what
  removes Administrator's ability to create/edit/delete committees, assign
  members, assign/edit/delete/complete tasks, and generate performance
  snapshots — every one of those actions across `modules/committees/`,
  `modules/workload/`, and `modules/performance/` was already gated by
  this one helper, so no template needed individual rewiring beyond that.
- **`modules/workload/index.php`**: the Smart AI Settings link and the
  Assign Task button used to be nested under the same `canManage()` check.
  Decoupled them — the AI Settings link now uses the new
  `canEditAiSettings()` (Administrator-only) helper directly, so it keeps
  showing for Administrator now that `canManage()` excludes Administrator.
  The Workload Recommendation panel (cross-member workload comparison) and
  the summary widgets are now scoped to the signed-in user's own tasks
  for Committee Member; Administrator and Committee Chairperson keep the
  full system-wide view.
- **`modules/workload/table.php`**: Committee Member now sees only tasks
  assigned to them, not the full task list.
- **`modules/workload/ajax_get.php`** / **`ajax_ai_recommend.php`**: added
  server-side ownership/role checks matching the above, so the same
  restriction holds even for direct API calls, not just hidden buttons.

### Added

- `includes/auth.php`: `canEditAiSettings()` (Administrator-only — gates
  `modules/workload/ai_settings.php`, which already required
  `ROLE_ADMIN` and needed no change) and `isCommitteeMember()`.
- `database/migration_role_rename.sql`.

### Not changed (out of scope for this revision)

- Jurisdictions and Committee Reports modules still require
  Administrator or Committee Chairperson for full access (unchanged
  `requireRole([ROLE_ADMIN, ROLE_STAFF])` gates) — these weren't part of
  the requested role table, so Administrator still has create/edit there.

## Unreleased — CMAS v1.1 Phase 1: Authentication & Account Security

Implements section 1 of the CMAS v1.1 revision spec. This is Phase 1 of 7 —
see the implementation plan below for what's still to come (UI/monitoring/
workload-logic/notifications/audit-trail phases).

### Added

- **Password policy** (§1.1): 8+ characters, 1 uppercase letter, 1 special
  character, enforced both server-side (`validatePasswordPolicy()` in
  `includes/functions.php`, wired into `pages/ajax_user_save.php` and
  `pages/ajax_change_password.php`) and client-side (live checklist via
  `initPasswordPolicyHint()` in `assets/js/app.js`). Server-side is the
  actual enforcement; the client-side checklist is UX only.
- **OTP verification step** (§1.4): `includes/OtpService.php` +
  `otp_verify.php` + `auth/process_otp.php` + `auth/resend_otp.php`. Login
  now issues a 6-digit OTP (hashed at rest, 30-second server-enforced
  expiry, one active OTP at a time, 5-attempt brute-force cap) instead of
  creating a session directly — the session is only created after OTP
  verification succeeds. **Delivery is in Demo Mode** (code shown on the
  page, not sent via SMS/email) since no provider is configured — see the
  docblock in `OtpService.php` for how to wire one up. `OTP_DELIVERY_MODE`
  in `config/config.php` controls this.
- **Account lockout** (§1.5): `includes/LoginSecurity.php` + `login_security`
  table. 3 consecutive failed attempts locks the account for 5 minutes,
  tracked entirely server-side. Counter resets on successful login.
- `database/migration_auth_security.sql` — adds `login_security` and
  `otp_verifications`. No existing table altered or dropped.

### Changed

- `auth/process_login.php` rewritten around the lockout + OTP flow (see
  above) instead of creating a session immediately after password check.
- `login.php` — removed the "System Features" icon-grid section (§1.6) and
  the last dead CSS for the already-removed Forgot Password link.

### Fixed during testing (caught by live end-to-end testing, not just linting)

- `LoginSecurity`: the lock-expiry timestamp and the "last attempt" timestamp
  were originally computed from two different clocks (PHP's configured
  timezone vs. MySQL's `NOW()`), which didn't break the actual lock
  enforcement (verified correct) but made the raw database values
  misleading for anyone querying them directly. Both are now computed from
  the same PHP-side clock.
- The message shown on the *exact* login attempt that triggers a lock
  incorrectly said "N attempts remaining" instead of announcing the lock,
  because it read a failure counter that locking had already reset to 0.
  Fixed by checking the actual lock state directly instead.

### Verification performed

- `php -l` across the entire project — zero syntax errors.
- Live end-to-end test against a real MySQL instance: full login → OTP
  flow (wrong OTP correctly blocks dashboard access; correct OTP creates
  the session), resend-before-expiry correctly rejected server-side with
  the exact remaining seconds, resend-after-expiry correctly succeeds,
  3-failed-attempt lockout correctly triggers, and a 4th attempt with the
  *correct* password is still correctly rejected while locked.

### Not yet implemented (see plan in the conversation — phases 2-7)

UI consistency (decorative card lines, clickable table View actions),
admin monitoring drill-down, extended workload/performance scoring
factors, notification system, and expanded audit trail UI are still
pending.

## Unreleased — UI Redesign + Smart AI Workload Distribution

This revision preserves all existing functionality, database tables, and
business logic from the previous build. Nothing was removed; the changes
below are additive (new tables, new files) or purely cosmetic (color
values, spacing, typography).

### Added

- **Smart AI Workload Distribution** (rule-based weighted scoring, not
  generative AI) — see `docs/AI_WORKLOAD_ALGORITHM.md` for the full
  writeup.
  - `includes/WorkloadAI.php` — the scoring engine.
  - `modules/workload/ajax_ai_recommend.php` — runs the engine for a
    committee and logs the result.
  - `modules/workload/ai_settings.php` + `ajax_ai_weights_save.php` +
    `assets/js/ai-settings.js` — admin controls to adjust factor weights,
    enable/disable factors, and review recent recommendations.
  - Assign Task modal (`modules/workload/index.php`) now shows a live
    "Smart AI Recommendation" panel — recommended member, suitability
    score, plain-language reasoning, alternative candidates, confidence
    level, and a "Recalculate" button — as soon as a committee is
    selected. Any candidate (recommended or alternative) can be applied
    to the assignment with one click; nothing is auto-selected.
  - Dashboard now shows a "Smart AI Insights" widget: total
    recommendations generated, how many were high-confidence, how many
    were overridden by an admin, and the 4 most recent.
  - `database/migration_ai_workload.sql` — adds `ai_weight_config` and
    `ai_recommendations`. No existing table was altered or dropped.
- `docs/AI_WORKLOAD_ALGORITHM.md` — full algorithm documentation,
  including which of the originally-requested scoring factors are
  data-backed today vs. explicitly deferred (and why).

### Changed — Visual redesign

- **Brand palette** applied consistently across every shared UI surface:
  Navy `#0B2E59`, Gold `#D4AF37`, Red `#C62828`, White `#FFFFFF`
  (previously an unrelated "Coastal Blue" navy/gold placeholder theme on
  `header.php`/`sidebar.php`, and a separate blue/yellow palette on
  `login.php`).
- `assets/css/style.css` rewritten as a small design system: Inter
  typography (SF Pro Display / system-font fallback), a neutral
  background/border/text scale so the three brand colors have room to
  breathe, softened shadows, larger corner radii, and refined
  cards/buttons/tables/forms/badges/modals/dropdowns/pagination —
  applies automatically to every page that already includes this
  stylesheet (i.e. every page in the app).
- `layouts/header.php`, `layouts/sidebar.php` — same brand colors applied
  to their embedded styles (both files ship their own self-contained
  `<style>` blocks, updated in place rather than rewritten).
- `login.php` — color palette aligned to brand, Inter font applied.
- `dashboard.php` — added the Smart AI Insights widget (see above); no
  other structural changes.

### Explicitly not changed

- Database schema for every pre-existing table (`users`, `roles`,
  `committees`, `committee_members`, `jurisdictions`,
  `workload_assignments`, `committee_performance`, `committee_reports`,
  `activity_logs`) — untouched.
- Business logic in every module's `ajax_save.php`/`ajax_delete.php`/etc.
  — untouched, except `modules/workload/ajax_save.php`, which gained a
  few additive lines to link a saved task back to the AI recommendation
  it came from (if any) and record whether it was followed or overridden.
- Individual module page markup (Committees, Jurisdictions, Performance,
  Committee Reports, User Management) — not hand-rewritten. These inherit
  the new visual language automatically through the shared stylesheet and
  layout changes above, since they already build on Bootstrap 5 classes
  styled by `style.css`.

### Verification performed

- `php -l` across every PHP file in the project — zero syntax errors.
- Live end-to-end test against a real MySQL instance with seeded data:
  schema + AI migration imported cleanly, login flow verified, and every
  key page (`dashboard.php`, `modules/workload/index.php`,
  `modules/workload/ai_settings.php`, `modules/committees/index.php`,
  `pages/users.php`, `pages/profile.php`) confirmed to return HTTP 200
  with no fatal/parse errors.
- `WorkloadAI::recommend()` exercised against all 4 seeded committees —
  produced sane, differentiated rankings with correct reasoning and
  confidence levels; recommendation logging and override-tracking
  verified by inspecting the resulting database rows directly.
- AJAX endpoints (`ajax_ai_recommend.php`, `ajax_ai_weights_save.php`)
  tested live over HTTP with a real authenticated session and CSRF
  tokens, including confirming weight changes actually persist to the
  database.
