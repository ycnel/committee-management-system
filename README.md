
# CMAS — Committee Management and Assignment System (Standalone)

A complete, self-contained web app — its own login, its own database, its own
dashboard — implementing all 6 modules of the Committee Management and
Assignment System with Smart Workload Distribution and Performance Monitoring.
No dependency on any other project. Native PHP + MySQL + Bootstrap 5, same
stack and coding conventions throughout.

## 1. Install

1. Copy this whole folder into your XAMPP `htdocs`, e.g.
   `C:\xampp\htdocs\committee-management-system\`
2. Import the database:
   ```
   mysql -u root -p < database/schema.sql
   ```
   or, in phpMyAdmin: create nothing manually — just open the **Import** tab
   and select `database/schema.sql`. It creates the `committee_management_db`
   database and every table itself.
3. Import the Smart AI Workload Distribution tables (adds `ai_weight_config`
   and `ai_recommendations` — nothing existing is touched):
   ```
   mysql -u root -p committee_management_db < database/migration_ai_workload.sql
   ```
4. Import the account-lockout and OTP-verification tables:
   ```
   mysql -u root -p committee_management_db < database/migration_auth_security.sql
   ```
5. Import recipient-scoped notifications (required for existing databases):
   ```
   mysql -u root -p committee_management_db < database/migration_notifications.sql
   ```
6. Import the committee group-chat tables (adds `committee_messages` and
   `committee_message_reads` — nothing existing is touched):
   ```
   mysql -u root -p committee_management_db < database/migration_committee_messages.sql
   ```
7. Import the two new legislative-authority roles (adds `Pro Tempore` and
   `Vice Mayor` to `roles` — nothing existing is renamed, removed, or
   touched otherwise):
   ```
   mysql -u root -p committee_management_db < database/migration_role_hierarchy_phase1.sql
   ```
8. Import the Member Acceptance Validation workflow (adds
   `workload_assignment_proposals` — `workload_assignments` itself is not
   touched, so every existing dashboard/report/query keeps working as-is):
   ```
   mysql -u root -p committee_management_db < database/migration_member_acceptance_workflow.sql
   ```
9. Import Availability / Pause Mode (adds `member_availability`):
   ```
   mysql -u root -p committee_management_db < database/migration_member_availability.sql
   ```
10. Configure the application base URL. For production, set the
   `CMAS_APP_URL` environment variable to your HTTPS domain. For local
   XAMPP, leave it unset and the application will use the current local URL.
   ```php
   CMAS_APP_URL=https://your-domain.example
   ```
11. Visit your configured application URL in your browser.
   `index.php` will send you straight to the login page. **You will now be
   asked for a 6-digit code after your password** — the configured SMTP
   provider sends the code to the user's email address.

## 2. Log in

Seeded accounts (all passwords should be changed after first login, via the
**Profile** page in the top-right user menu):

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@cmas.local` | `Admin@123` |
| Committee Chairperson | `staff@cmas.local` | `Staff@123` |
| Committee Member | `member1@cmas.local` | `Member@123` |
| Committee Member | `member2@cmas.local` | `Member@123` |

Use **User Management** (Admin only) to add more accounts — that's also how
you get new people available to assign to committees, since Committee
Members are just `users` rows with the "Committee Member" role.

## 3. What's included

| Area | Files | Notes |
|---|---|---|
| Auth | `login.php`, `auth/process_login.php`, `logout.php`, `index.php` | Sessions, CSRF, `password_hash()`/`password_verify()` |
| Dashboard | `dashboard.php` | Committee/member/task/completion-rate summary, recent committees, upcoming tasks |
| **Module 1** Committee Formation | `modules/committees/` | Create/Edit/Delete/View, jurisdiction assignment, search/sort/paginate |
| **Module 2** Member Assignment | `modules/committees/view.php` + `ajax_member_*.php` | Assign/remove members, roles, one-Chairperson rule |
| **Module 3** Jurisdiction & Scope | `modules/jurisdictions/` | Create/Edit/Delete/Search |
| **Module 4** Smart Workload Distribution | `modules/workload/` | Task assignment, **Smart AI Workload Distribution recommendations** (see below), Pending/Completed views |
| **Module 5** Performance Monitoring | `modules/performance/` | Live KPI dashboard, charts, snapshot-to-history |
| **Module 6** Committee Reporting | `modules/committee_reports/` | Legislative report drafts, human review/approval, audit history, print/PDF, and CSV/Excel record exports; operational reports remain available |
| **Smart AI Workload Distribution** | `includes/WorkloadAI.php`, `modules/workload/ai_settings.php` | Rule-based weighted scoring engine that recommends who to assign a task to. Full writeup: `docs/AI_WORKLOAD_ALGORITHM.md` |
| User Management | `pages/users.php` | Admin-only account CRUD + role assignment |
| Audit Logs | `pages/activity_logs.php` | Admin-only audit trail |
| Profile | `pages/profile.php` | Change own password |
| **Messages** | `modules/messages/`, `includes/messages.php`, `assets/js/messages.js` | Real-time committee group chat — floating panel next to the notification bell, one conversation per committee the logged-in user belongs to. See below. |
| **System Overview / Backup & Restore** | `modules/system/`, `includes/db_backup.php` | Super Admin-only: system/DB health readout, accounts-by-role breakdown, and full database backup (download) / restore (upload). See §4 Access control. |
| **Task Proposals (Member Acceptance Validation)** | `modules/workload/proposal.php`, `ajax_member_respond.php`, `ajax_approve.php`, `ajax_reassign.php`, `ajax_stop_distribution.php`, `ajax_proposals.php`, `includes/workload_proposals.php` | A Chairperson/Admin-selected candidate must accept the task and then be given final approval before it becomes a real, confirmed assignment — an AI recommendation (or a manual pick) is never itself an assignment. See below. |
| **Availability / Pause Mode** | `modules/availability/`, `includes/availability.php`, `assets/js/availability.js` | Committee members can mark themselves Available, Unavailable, Idle/Paused, or Emergency; AI recommendations exclude anyone not Available. See below. |

**Design system:** every page shares `assets/css/style.css` plus
`layouts/header.php`/`sidebar.php`/`footer.php`, using the brand palette
Navy `#0B2E59` / Gold `#D4AF37` / Red `#C62828` / White, Inter typography,
and an Apple/Stripe-inspired minimalist visual language (soft shadows,
generous spacing, rounded corners). See `CHANGELOG.md` for the full list
of what changed in this revision.

**Messages:** a Messenger-style floating panel, opened from the chat-bubble
button beside the notification bell (`layouts/content-topbar.php`). Every
user sees one conversation per committee they have an Active row for in
`committee_members` — nothing configured separately, and nobody outside
that committee can read or post in it (enforced in
`includes/messages.php::requireCommitteeAccess()` on every request, not
just hidden in the UI). New messages arrive without a page refresh via
short polling (every 3s while a conversation is open, every 15s for the
header's unread badge otherwise) — the same lightweight approach the app
already uses for its session heartbeat, so no extra server (WebSockets,
queues, etc.) is required.

**Task Proposals:** when a Chairperson/Administrator assigns a task
(directly or via "Generate with AI"), nothing is written to
`workload_assignments` yet — a `workload_assignment_proposals` row is
created instead, and the selected member gets a notification linking to
`modules/workload/proposal.php?id=…` to Accept or Decline. Accepting does
**not** confirm the assignment: the Chairperson/Administrator still has to
give final approval (same page, "Give Final Approval" button) before a
real `workload_assignments` row is created — that's the only place one
gets written as a result of this workflow. A decline can be followed by
**Reassign** (picks a new candidate in the same committee, chains the new
proposal to the old one so the full history stays visible) or **Stop
Distribution** (abandons the task). "Pending Task Proposals" /
"Awaiting Your Response" widgets on the dashboards surface what needs
attention; every action is re-validated server-side (the assigned member
can't be impersonated, `canManage()` still gates approve/reassign/stop) —
see `includes/workload_proposals.php` and the five
`modules/workload/ajax_*.php` endpoints it backs.

**Availability:** use the status badge beside a member on the committee
roster, or beside your committee on the member dashboard, to view status
history and (for yourself or a manager) set Available, Unavailable,
Idle/Paused, or Emergency with an optional reason and date range. Each
change is recorded as a new history entry. The AI candidate pool excludes
members who are not currently Available; manual member selection remains
allowed and shows their status.

## 4. Access control

Two separate authority layers (role-hierarchy revision, Phase 1) — a role
from one layer never implies privileges from the other:

**SYSTEM AUTHORITY** (technical/operational access to CMAS itself)
- **Super Admin** — the two Super-Admin-exclusive **System Overview** and
  **Backup & Restore** screens (`modules/system/`), plus everything
  Administrator can reach: User Management, Audit Logs, Smart AI Settings,
  and read-only access to every operational module (Committee Management,
  Workload Distribution, Committee Performance, Jurisdictions, Committee
  Reports, Reports & Analytics). Does **not** get `canManage()`'s write
  access to those operational modules — Super Admin cannot create/edit/
  delete a committee, task, or jurisdiction; that stays with Administrator
  and Committee Chairperson only. Is the only role that can create, edit,
  or delete another Administrator/Super Admin account (see below).
- **Administrator** — unchanged from before this revision: full read/write
  access to every operational module (Committee Management, Workload
  Distribution, Committee Performance, Jurisdictions, Committee Reports),
  plus **User Management**, **Audit Logs**, and **Smart AI Settings**. Can
  manage any account **except** one whose role is Administrator or Super
  Admin — creating, editing, or deleting an admin-level account is
  Super-Admin-exclusive (`pages/ajax_user_save.php` /
  `ajax_user_delete.php` reject it server-side; the role dropdown and
  Edit/Delete buttons for those rows are also hidden from Administrator in
  `pages/users.php`).

**LEGISLATIVE AUTHORITY** (committee workflow authority, highest first)
- **Pro Tempore** / **Vice Mayor** — read-only oversight of every
  operational module (Committee Management, Workload Distribution,
  Committee Performance, Jurisdictions, Committee Reports, Reports &
  Analytics). Never `canManage()`'s write access, never User Management/
  Audit Logs/AI Settings/System Overview/Backup. Both roles currently have
  identical permissions; if they should later differ (e.g. Vice Mayor
  reviewing escalated matters Pro Tempore doesn't), that's a Phase 2+
  change, not part of this phase.
- **Committee Chairperson** — manages committees (create/edit, assign/
  remove members), assigns and manages tasks/workloads, and uses Smart
  Workload Distribution (including "Generate with AI" in the Assign Task
  modal). Does **not** see the Smart AI Settings screen/button, User
  Management, Audit Logs, or the System Administration section.
- **Committee Member** — view-only: sees the committees they belong to
  (read-only) and their own workload/tasks only (not other members'
  tasks). Cannot create/edit committees, cannot assign tasks, cannot
  access Smart AI Settings or the cross-member Workload Recommendation
  panel.

If you're updating an existing installation (not a fresh `schema.sql` run),
apply `database/migration_role_rename.sql` once to rename the stored
"Legislative Staff" role to "Committee Chairperson", and
`database/migration_role_hierarchy_phase1.sql` once to add the Pro Tempore
and Vice Mayor roles.

Existing installations must also apply `database/migration_notifications.sql`
once so the notification bell can store and filter recipient-specific records.

Existing installations must also apply `database/migration_committee_messages.sql`
once to add the committee group-chat tables (see **Messages** above).

Existing installations must also apply `database/migration_member_acceptance_workflow.sql`
once to add the Member Acceptance Validation workflow (see **Task Proposals** below).

Existing installations must also apply `database/migration_member_availability.sql`
once to add Availability / Pause Mode (see **Availability** above).

Existing installations must apply `database/migration_committee_report_draft_jurisdiction.sql`
once to save a single selected jurisdiction on each Committee Report draft.

## 5. Notes

- This is a **separate project** from any other system you may have — it has
  its own `committee_management_db` database, its own `users`/`roles`
  tables, its own session cookie name (`cmas_session`). It will not conflict
  with another PHP app running on the same XAMPP instance as long as the
  folder names differ.
- Bootstrap/jQuery/Chart.js/SweetAlert2/DataTables load from CDN by default
  (needs internet). `includes/functions.php::vendorAsset()` will
  automatically switch to local copies if you ever populate
  `assets/vendor/`, but that's optional — the app works out of the box.
- Every write action (Insert/Update/Delete/Export/Login) is logged to
  `activity_logs` and viewable under **Audit Logs**.
- All SQL uses prepared statements; all output is escaped via `e()`; all
  state-changing requests require a valid CSRF token
  (`includes/functions.php::requireCsrf()`).

## 6. Testing checklist

1. Log in as `admin@cmas.local`.
2. **User Management** → confirm the 4 seeded accounts appear.
3. **Jurisdictions** → the 4 seeded jurisdictions should already be there.
4. **Committee Management** → create a committee, assign a jurisdiction.
5. Open the committee → **Assign Member** → pick `member1@cmas.local` as
   Chairperson, `member2@cmas.local` as Member.
6. **Workload Distribution** → click **Assign Task**, select a committee →
   confirm the **Smart AI Recommendation** panel appears with a recommended
   member, suitability score, reasoning, and alternatives → click **Use
   This** on an alternative candidate → confirm it selects them in the
   "Assign To" dropdown → save the task.
7. **Smart AI Settings** (Admin only) → confirm the 7 scoring factors
   appear with sliders → change a weight → **Save Weights** → confirm the
   success toast → check **Recent AI Recommendations** shows the task you
   just assigned, marked **Overridden: Yes** if you picked an alternative.
8. **Dashboard** → confirm the **Smart AI Insights** widget shows the
   recommendation count that now matches what you generated.
9. **Committee Performance** → confirm the completion rate matches, click
   **Snapshot**.
10. **Committee Reports** → try all 3 report types, Print, Export PDF, Export
    Excel.
11. Log out, log back in as `member1@cmas.local` → confirm they can see
    Dashboard/Committees/Workload/Performance but no Create/Edit/Delete
    buttons, no **Smart AI Settings** link, and no
    Jurisdictions/Reports/Users/Audit Logs in the sidebar.
12. Click the chat-bubble **Messages** button beside the notification bell →
    confirm it opens a floating panel (page underneath stays put) listing
    only the committee(s) that account belongs to. Open one, send a
    message, then log in as another member of that same committee in a
    second browser/incognito window → confirm the message appears there
    without refreshing, and that a member of a *different* committee never
    sees this conversation at all.
13. Log in as a **Pro Tempore** or **Vice Mayor** account → confirm the
    sidebar shows Dashboard/Committees/Workload/Performance/Jurisdictions/
    Committee Reports/Reports & Analytics with no Create/Edit/Delete
    buttons anywhere, and no Administration or System Administration
    section at all.
14. Log in as **Super Admin** → confirm the sidebar additionally shows a
    **System Administration** section (System Overview, Backup & Restore)
    that Administrator does not have. On **System Overview**, confirm the
    database/PHP stats and accounts-by-role table render. On
    **Backup & Restore**, click **Download Backup Now** and confirm a
    `.sql` file downloads; the **Upload & Restore** button should stay
    disabled until you type `RESTORE` into the confirmation field.
15. Still as Administrator (not Super Admin), open **User Management** →
    **New User** → confirm the Role dropdown does **not** offer
    "Administrator" or "Super Admin", and that any existing Administrator/
    Super Admin row in the table has no Edit/Delete buttons. Confirm
    directly hitting `ajax_user_save.php` with `role_id` set to
    Administrator/Super Admin is rejected server-side regardless.
16. As Chairperson, open **Workload Distribution** → **Assign Task** for a
    Committee Member → confirm the success message says the task is
    "awaiting the member's response" and that it does **not** appear in
    the main task table. Log in as that member → confirm a
    "New task proposed" notification and the **Awaiting Your Response**
    dashboard widget both link to `modules/workload/proposal.php` with
    working **Accept**/**Decline** buttons. After accepting, log back in
    as the Chairperson → confirm the task now appears under **Pending
    Task Proposals** with a **Give Final Approval** button, and that
    clicking it is what finally makes the task show up in the main
    Workload Distribution table.
17. Repeat, but **Decline** instead of Accept → confirm the Chairperson
    sees a **Reassign** option (pick a different member in the same
    committee) and that `modules/workload/proposal.php` for the new
    proposal shows "This task was reassigned…" with a link back to the
    original. Separately, confirm **Stop Distribution** on an open
    proposal marks it `Stopped` and it drops out of both dashboards'
    widgets.
