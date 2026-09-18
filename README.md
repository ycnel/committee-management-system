
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
5. Configure the application base URL. For production, set the
   `CMAS_APP_URL` environment variable to your HTTPS domain. For local
   XAMPP, leave it unset and the application will use the current local URL.
   ```php
   CMAS_APP_URL=https://your-domain.example
   ```
6. Visit your configured application URL in your browser.
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
| **Module 6** Committee Reporting | `modules/committee_reports/` | Print / Export PDF / Export Excel for 3 report types |
| **Smart AI Workload Distribution** | `includes/WorkloadAI.php`, `modules/workload/ai_settings.php` | Rule-based weighted scoring engine that recommends who to assign a task to. Full writeup: `docs/AI_WORKLOAD_ALGORITHM.md` |
| User Management | `pages/users.php` | Admin-only account CRUD + role assignment |
| Activity Logs | `pages/activity_logs.php` | Admin-only audit trail |
| Profile | `pages/profile.php` | Change own password |

**Design system:** every page shares `assets/css/style.css` plus
`layouts/header.php`/`sidebar.php`/`footer.php`, using the brand palette
Navy `#0B2E59` / Gold `#D4AF37` / Red `#C62828` / White, Inter typography,
and an Apple/Stripe-inspired minimalist visual language (soft shadows,
generous spacing, rounded corners). See `CHANGELOG.md` for the full list
of what changed in this revision.

## 4. Access control

Role permissions were revised so Administrator is a view/system-administration
role and Committee Chairperson (formerly "Legislative Staff") is the sole
operational/management role for committees and workload:

- **Administrator** — view-only across Committee Management, Workload
  Distribution, Committee Performance, and system data. Cannot create/edit
  committees, cannot assign or manage tasks/workloads. Is the ONLY role that
  can access and edit **Smart AI Settings** (`modules/workload/ai_settings.php`).
- **Committee Chairperson** — manages committees (create/edit, assign/remove
  members), assigns and manages tasks/workloads, and uses Smart Workload
  Distribution (including "Generate with AI" in the Assign Task modal). Does
  NOT see the Smart AI Settings screen/button.
- **Committee Member** — view-only: sees all committees (read-only) and their
  own workload/tasks only (not other members' tasks). Cannot create/edit
  committees, cannot assign tasks, cannot access Smart AI Settings or the
  cross-member Workload Recommendation panel.
- **Jurisdictions / Committee Reports / User Management / Activity Logs** —
  unchanged by this update: Administrator/Committee Chairperson (User
  Management and Activity Logs remain Administrator-only). These modules
  were out of scope for the role revision above; ask if you'd like the same
  view-only treatment applied to Administrator here too.

If you're updating an existing installation (not a fresh `schema.sql` run),
apply `database/migration_role_rename.sql` once to rename the stored
"Legislative Staff" role to "Committee Chairperson".

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
  `activity_logs` and viewable under **Activity Logs**.
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
    Jurisdictions/Reports/Users/Activity Logs in the sidebar.

# Lungsod ng Manila Committee Manaagement and Assignment System
A web-based Committee Management and Assignment System (CMAS) designed for the City Council of Manila to streamline committee formation, member assignment, workload distribution, and performance monitoring.


10a497870570fa0d28dc2d011226fc3fb6d0fe78
