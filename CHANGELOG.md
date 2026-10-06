# Changelog

## Unreleased — Legislative Committee Report Workflow

### Added
- Extended the existing `committee_report_drafts` records into formal
  legislative committee reports, with report numbers, referral and measure
  references, proceedings, findings, analysis, recommendations, action,
  signatures, appendices, and report-history records.
- Added role-checked draft, review, return, approval, and finalization steps.
  Administrator or Pro Tempore reviewers can approve, but cannot approve or
  finalize a report they created.
- Added editable AI-assisted section suggestions that retain source context;
  AI output is never treated as verified proceedings, legal authority, votes,
  amendments, or an official recommendation.
- Added a formal, draft-watermarked print/PDF view and CSV/Excel exports for
  report-record management. Existing operational report exports remain
  available through `operational.php`.
- Added a unique, auto-generated CMAS internal tracking reference for each
  report draft. The official legislative measure number remains a separate
  manually entered field.

### Database
- Fresh installations receive the updated report-draft and history schema from
  `database/schema.sql` or `database/migration_committee_report_drafts.sql`.
- Existing installations require the one-time
  `database/migration_committee_legislative_reports.sql` migration.
- Existing installations that already applied that migration also require
  `database/migration_committee_report_internal_references.sql`.

## Unreleased — Phase 3: Availability / Pause Mode

- Added append-only member availability history with Available, Unavailable,
  Idle/Paused, and Emergency states, optional reasons, and date ranges.
- Added self-service status updates, manager updates, and history viewing on
  committee rosters and the member dashboard.
- Excluded non-Available members from AI recommendation candidates while
  keeping manual assignment available and displaying member status.
- Added `database/migration_member_availability.sql` for existing databases.

## Unreleased — Role Hierarchy & LSMS Integration Revision, Phase 2: Member Acceptance Validation

Second phase of the broader CMAS role-hierarchy revision. Implements §9's
explicit requirement that **an AI recommendation (or a manually-chosen
candidate) is NOT an assignment** until the member has agreed to it and a
Chairperson/Administrator has given final approval:

```
Task → AI Evaluation → Multiple Recommendations → Chairperson Review →
Chairperson Selects Member → Member Accepts/Declines →
Chairperson Final Approval → Assignment Confirmed
```

### Design choice: a separate proposal table, not new columns on `workload_assignments`

19 existing files query `workload_assignments` (dashboards, performance
metrics, reports, the AI duplicate-check, the committee-deletion guard,
etc.). Overloading that table with pre-approval states would have meant
auditing and updating every one of them to filter out not-yet-approved
rows — large blast radius for a single phase. Instead, `workload_assignments`
is **completely untouched** in meaning: a new `workload_assignment_proposals`
table sits in front of it, and a real `workload_assignments` row is only
ever created at the moment of final approval. Every existing consumer of
`workload_assignments` keeps working exactly as before with **zero code
changes** — verified by confirming the existing Workload Distribution table
(`ajax_search.php`) only ever shows the one row that reached Approved, never
the three sibling proposals sitting in Awaiting Response/Declined/Stopped.

### Added

- `database/migration_member_acceptance_workflow.sql`: new
  `workload_assignment_proposals` table — one row per candidate per task,
  state machine `Awaiting Response → Accepted/Declined → Approved`, plus
  `Reassigned`/`Stopped` terminal states. A decline or reassignment never
  mutates a row in place: the old row is marked terminal and a **new**
  proposal row is created chained to it via `previous_proposal_id`, so the
  full reassignment history is a real, queryable chain of rows rather than
  being overwritten.
- `includes/workload_proposals.php`: shared helpers —
  `getProposalWithContext()`, `approveProposal()` (the one place that
  writes to `workload_assignments`, re-running the same duplicate-task
  guard `ajax_save.php` already used), `linkAiRecommendationOutcome()`
  (moved out of `ajax_save.php` so both the approval path and the
  existing direct-edit path share one implementation instead of two
  copies that could drift).
- Five new endpoints in `modules/workload/`: `ajax_member_respond.php`
  (accept/decline — server-side verifies the caller *is* the proposed
  member, not the Chairperson or anyone else), `ajax_approve.php`
  (`canManage()`-gated; only works on an Accepted proposal),
  `ajax_reassign.php` (moves an open/declined proposal to a new candidate
  in the same committee, chaining history), `ajax_stop_distribution.php`,
  and `ajax_proposals.php` (list — the Chairperson/Admin "needs my
  attention" queue, or a Committee Member's own proposals).
- `modules/workload/proposal.php` + `assets/js/workload-proposal.js`:
  the task-proposal detail page — Accept/Decline for the assigned member,
  Approve/Reassign/Stop Distribution for Administrator/Chairperson, and a
  rendered reassignment-history chain when applicable.
- "Pending Task Proposals" widget on `dashboard.php` (Administrator/
  Chairperson/Super Admin/Pro Tempore/Vice Mayor — read-only for the
  latter three per Phase 1's `canManage()` boundary) and "Awaiting Your
  Response" on `dashboard_member.php`, both via the new
  `assets/js/dashboard-proposals.js`. Self-loading widgets added as
  container `<div>`s with their own script, so neither dashboard's
  existing PHP/SQL needed to change.
- `includes/functions.php`'s `statusBadge()` gained the six proposal
  states (`Awaiting Response`, `Accepted`, `Declined`, `Approved` already
  existed, `Reassigned`, `Stopped`) so the new UI reuses the existing
  badge styling instead of introducing a parallel helper.

### Changed

- `modules/workload/ajax_save.php`'s **create** branch (`id=0`) now
  inserts into `workload_assignment_proposals` instead of
  `workload_assignments` directly, and added a duplicate-*proposal* guard
  (same title/member/due-date already Awaiting Response or Accepted)
  alongside the existing duplicate-*assignment* guard. The **update**
  branch (editing an already-approved task) is unchanged. The existing
  "Assign Task" modal and its JS needed no changes — same endpoint, same
  request shape, same success-response shape; a newly-proposed task
  simply doesn't appear in the main table until approved, which is the
  correct behavior.

### Verified

- Full happy path via a live sandbox: Chairperson proposes → confirmed
  zero rows in `workload_assignments` → Member accepts → confirmed still
  zero rows → Chairperson approves → confirmed exactly one real row
  appears, correctly linked back to the proposal.
- Ownership enforced server-side: a different user (tested: Administrator)
  cannot accept/decline on the actual assignee's behalf; a Member cannot
  call the approval endpoint themselves (`canManage()` gate).
- Terminal-state guards: approving/reassigning an already-Approved,
  Reassigned, or Stopped proposal is rejected with a clear message.
- Decline → Reassign chain: declined proposal stays `Declined` (accurate
  history), new proposal created for the new candidate with
  `previous_proposal_id` pointing back; the proposal detail page renders
  this chain correctly.
- Super Admin, Pro Tempore, and Vice Mayor all still rejected by
  `ajax_save.php`'s `canManage()` check when attempting to create a
  proposal — Phase 1's read/write boundary holds unchanged under this
  phase's new code path too.
- Confirmed zero interference: the existing Workload Distribution table
  lists only the one proposal that reached Approved; the three sibling
  proposals (Declined, Stopped, Awaiting Response) never appear there.

## Unreleased — Role Hierarchy & LSMS Integration Revision, Phase 1: Roles & Permissions

First phase of the broader CMAS role-hierarchy revision (legislative
authority vs. system authority, per the LSMS Subsystem #4 architecture
document). This phase covers the foundational role/permission layer only;
the Member Acceptance Validation workflow, availability/pause mode,
performance-monitoring changes, and LSMS integration stubs are separate,
later phases — see the assessment shared before this phase for the full
breakdown.

No existing role was renamed or removed, no existing capability was taken
away from Administrator or Committee Chairperson, and no prohibited roles
(Secretariat, Authorized Staff, etc.) were introduced.

### Added

- `database/migration_role_hierarchy_phase1.sql`: adds the two missing
  LEGISLATIVE AUTHORITY roles, `Pro Tempore` (id 5) and `Vice Mayor` (id 6),
  to the `roles` table. Idempotent; nothing existing is touched.
- `config/config.php`: new role constants `ROLE_SUPER_ADMIN`,
  `ROLE_PRO_TEMPORE`, `ROLE_VICE_MAYOR`, and `LEGISLATIVE_OVERSIGHT_ROLES`
  (`[ROLE_PRO_TEMPORE, ROLE_VICE_MAYOR]`, reused everywhere those two are
  granted the same read/oversight access).
- `includes/auth.php`: new helpers `isSuperAdmin()`, `isSystemAuthority()`
  (Administrator or Super Admin), `isLegislativeOversight()` (Pro Tempore
  or Vice Mayor), `requireSuperAdmin()`, `canManageAdminAccounts()` (Super
  Admin only — guards administrator-level account management).
  `canManage()` — the write-access gate for committees/workload/
  performance — is deliberately **unchanged**: Super Admin, Pro Tempore,
  and Vice Mayor get read access to those modules (see below) but never
  gain the ability to create/edit/delete records through it. A "higher"
  position does not imply unrestricted operational authority.
  `canEditAiSettings()` now covers both SYSTEM AUTHORITY roles instead of
  Administrator only.
- **26 existing page/AJAX files** had their `requireRole([...])` gate
  expanded to add `ROLE_SUPER_ADMIN` and/or `...LEGISLATIVE_OVERSIGHT_ROLES`
  — Committee Management, Workload Distribution, Committee Performance,
  Jurisdictions, Committee Reports, and Reports & Analytics are now
  readable (never writable) by Pro Tempore, Vice Mayor, and Super Admin;
  Audit Logs, User Management, and Smart AI Settings are now reachable by
  Super Admin in addition to Administrator. `ajax_ai_recommend.php` (the
  actual AI-recommend write action) was intentionally left on its existing
  `canManage()` gate.
- **Administrator-account privilege-escalation guard**
  (`pages/ajax_user_save.php`, `pages/ajax_user_delete.php`): a plain
  Administrator can no longer create, edit, or delete an account whose
  role is Administrator or Super Admin — "managing administrator-level
  accounts" is Super Admin-exclusive per the revision, while every other
  role (Chairperson, Member, Pro Tempore, Vice Mayor) remains fully
  manageable by Administrator as before. `pages/users.php` hides those two
  roles from the create/edit dropdown for non-Super-Admin viewers, and
  `pages/users_table.php` hides the Edit/Delete buttons on those rows —
  defense in depth on top of the server-side check.
- `layouts/sidebar.php`: full 6-role menu, plus a new **System
  Administration** section (Super-Admin-only: System Overview,
  Backup & Restore) and an **Administration** section header (Audit Logs,
  User Management, Smart AI Settings). Completed an unused
  `.nav-section-label` CSS rule that was already sitting in this file from
  an earlier iteration rather than duplicating it.
- `modules/system/index.php` (System Overview) and
  `modules/system/backup.php` + `ajax_backup.php` + `ajax_restore.php`
  (Backup & Restore), all Super-Admin-only: database size/table count,
  DB/PHP version, security & session configuration readout, accounts-by-role
  breakdown with administrator-level accounts flagged, and recent
  account/security/backup activity.
- `includes/db_backup.php`: full database backup/restore implemented in
  pure PDO rather than shelling out to `mysqldump` — `exec()`/
  `shell_exec()` are disabled on a lot of shared hosting, and a missing or
  mismatched `mysqldump` path is a common deployment footgun. Backup
  streams a schema+data `.sql` dump of every table; restore executes an
  uploaded `.sql` file inside a transaction (see the Fixed section for two
  real bugs found and fixed here during testing) and is logged either way.
- `assets/js/system-backup.js`: Backup & Restore page behavior — one-click
  backup download, and a Restore button that stays disabled until the
  person types `RESTORE` into a confirmation field, plus a SweetAlert2
  confirmation dialog before the upload actually submits.

### Fixed (found during this phase's own testing)

- `includes/db_backup.php`'s SQL-statement splitter was treating a
  comment line glued directly in front of a real statement (nothing but a
  newline separates a `-- Table: x` header from the `DROP TABLE...;` that
  follows it in the generated dump) as a comment-only chunk and silently
  discarding the whole thing — including the `DROP TABLE`. The following
  `CREATE TABLE` then failed with "table already exists" on any restore.
  Fixed by stripping full-line comments before splitting.
- The same function called `$pdo->commit()` unconditionally after a fully
  successful restore. Because `DROP TABLE`/`CREATE TABLE` cause an
  implicit commit in MySQL/MariaDB, the PDO transaction was frequently
  already closed by the time execution reached that line, so `commit()`
  threw `"There is no active transaction"` and reported an otherwise
  100%-successful restore as a failure. Fixed by only committing when
  `$pdo->inTransaction()` is still true.
- Both were caught by an actual sabotage-then-restore test (rename/delete
  live rows, restore from a fresh backup, verify the rows come back) run
  against the sandboxed test database — not just code review.

### Known limitation (by design, not a bug)

- Restoring into a **completely empty** database can't work through the
  web UI: the UI itself needs a `users` table to authenticate the Super
  Admin session that triggers the restore in the first place. True
  disaster-recovery-from-nothing needs a CLI/phpMyAdmin restore first, the
  same as any web-hosted backup tool. Restoring over an existing
  (non-empty) database — the common case, undoing a mistake or bad
  migration — works fully through the UI and was the scenario tested.

### Verified

- Full 6-role × 12-page access matrix (Administrator, Super Admin,
  Chairperson, Committee Member, Pro Tempore, Vice Mayor ×
  Dashboard/Committees/Workload/Performance/Jurisdictions/Committee
  Reports/Reports & Analytics/Audit Logs/User Management/AI Settings/
  System Overview/Backup) — every cell matches the intended design.
- Write actions (e.g. `modules/committees/ajax_save.php`) confirmed still
  rejecting Super Admin, Pro Tempore, Vice Mayor, and Committee Member —
  page-level read access does not imply `canManage()`.
- Privilege-escalation guard confirmed in both directions: a plain
  Administrator is rejected creating/editing/deleting an Administrator or
  Super Admin account (server-side, not just UI); Super Admin can.
- Backup produces a complete, correctly-ordered `.sql` dump of a real
  16-table database; restore correctly reverts a live sabotage test
  (renamed + deleted rows) with all tables and row counts intact.

## Unreleased — Real-Time Committee Messaging

Adds an account-based, real-time group chat per committee: a chat-bubble
button beside the notification bell opens a floating Messenger-style panel
(the page underneath never navigates away). Every user automatically sees
one conversation per committee they actively belong to — derived entirely
from the existing `committee_members` table, so no new user/committee
records are created and no membership needs configuring separately.

### Added

- `database/migration_committee_messages.sql`: two new tables —
  `committee_messages` (one row per message: committee, sender, body,
  timestamp) and `committee_message_reads` (per-user "read up to" cursor,
  used only to compute unread counts/badges). `sender_user_id` is
  `ON DELETE SET NULL` (not `RESTRICT`/`CASCADE`) so a hard-deleted user
  (`pages/ajax_user_delete.php`) can never be blocked from deletion by a
  past message, and the committee's history isn't erased either — the
  message just renders as authored by "Former member".
- `includes/messages.php`: shared helpers — `userActiveCommitteeIds()`,
  `isActiveCommitteeMember()`, `requireCommitteeAccess()` (403s a request
  outside the caller's own committees), `committeeConversationsForUser()`,
  `totalUnreadMessagesForUser()`, `markCommitteeRead()`. Wired into
  `includes/auth.php` alongside the existing `notifications.php` include so
  every authenticated page has it available.
- `modules/messages/ajax_conversations.php`,
  `modules/messages/ajax_messages.php`,
  `modules/messages/ajax_send.php`,
  `modules/messages/ajax_unread_count.php`: the feature's whole server
  surface. Every one of them re-derives the sender/committee from the
  session and `committee_members`, never trusts an id from the request
  body/query string without checking it against that table first, and
  `ajax_send.php` requires `requireCsrf()` like every other write endpoint
  in the app. `ajax_messages.php` also doubles as the polling endpoint
  (`?after_id=`) and the "load earlier history" endpoint (`?before_id=`).
- `assets/js/messages.js` / `assets/css/messages.css`: the floating panel
  itself — conversation list ↔ thread view (with a back button), composer,
  and the polling loop that makes new messages appear without a refresh
  (3s while a thread is open, 15s for the header badge otherwise — the
  same short-polling approach `assets/js/app.js` already uses for the
  session heartbeat, so no new server infrastructure is needed). Renders
  message bodies via `textContent`, never `innerHTML`, as defense in depth
  alongside the server-side `clean()` call already applied on send.
- `layouts/content-topbar.php`: a `topbar-icon-btn` chat-bubble button
  (same size/style as the bell) plus its unread badge and the panel's
  markup, right after `</nav>` so it floats over the page instead of
  scrolling with the topbar. Included on every page that already renders
  the topbar — nothing else in that file was restructured, and the
  notification bell/dropdown are untouched.

### Notes

- Access is enforced server-side on every request
  (`requireCommitteeAccess()`), not just hidden in the UI: a member of one
  committee gets a 403 calling another committee's endpoints directly.
- A user can belong to more than one committee (the existing schema already
  allows this); the panel lists every one of them as a separate
  conversation, most-recently-active first, rather than assuming exactly
  one. A user with no Active committee membership (e.g. some Administrator
  accounts) sees a plain "not currently assigned to a committee" state
  instead of an error.
- README.md: install step 6 (`migration_committee_messages.sql`), a
  **Messages** write-up under §3, an existing-installations note under §4,
  and a testing-checklist step under §6.

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
