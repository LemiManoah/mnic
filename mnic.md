# Musuwa Nation Investment Club — Outstanding Work

Living backlog for the club management application. Updated 5 August 2026.

This document records **what is not yet done**. For what the application already
does, read the code — every rule lives in an Action class under `app/Actions`
with a matching test.

---

## 1. Status by phase

| Phase | Scope | State |
|---|---|---|
| **1. Foundation** | Roles, member registry, effective-dated settings, audit log | ✅ Built and verified (202 tests, 100% coverage) |
| **2. Contributions** | Periods, obligations, payments, verification, evidence, ledger | ✅ Built; formatting and tests run by the club |
| **3. Governance** | Meetings, attendance, minutes, proposals, votes, actions | ⚠️ Built, **not yet verified** — see §2 |
| **4. Finance & Reports** | Expenses, external accounts, reconciliation, monthly close, dashboards, monthly report | ⚠️ Built, **not verified**; controller tests and exports outstanding — see §7 |
| **5. Pilot & hardening** | UAT, security review, backup restore, mobile QA, training | ⬜ Not started |

---

## 2. Verify Phase 3 before relying on it

Phase 3 was written but never executed. Run the gates and expect fixes:

```bash
php artisan wayfinder:generate --with-form
php artisan migrate:fresh --seed
composer lint
bun run test:types
php artisan test --compact --testsuite=Unit,Feature
```

`migrate:fresh --seed` is mandatory — `SettingSeeder` gained `quorum_percent`
and `approval_percent`, and `OpenProposalVoting` throws without them.

---

## 3. The blocker: members created in the app cannot get a login

**This remains the most important gap**, though the seeder now softens it.
Self-registration is switched off (routes commented out in `routes/web.php`),
and nothing replaces it. Today:

- The 20 seeded founding members **do** have logins (see §3.1).
- But a member created afterwards through the Members screen has
  `user_id = null`: they cannot log in, vote, or see their ledger, and
  `AssignMemberRole` throws a `RuntimeException` for them.

**What is needed:** an invitation flow — Secretary creates the member, the
system emails a signed, expiring invite link, the member sets their own
password, and the `User` is linked to the `Member` on acceptance.

Related: decide whether officers can deactivate a login without exiting the
member.

### 3.1 Seeded credentials are placeholders

`MusuwaNationSeeder` creates the full founding roll of twenty with working
logins so the app is usable immediately. Before any real pilot:

- **Every account shares the password `password`.** Change them.
- Addresses use the reserved `.test` TLD (e.g.
  `ssekwayama.fredrick.01@musuwanation.test`) and can never receive mail, so
  password reset will not work. Collect real addresses. The exception is the
  treasurer, seeded as `lemi@gmail.com` on request.
- Phone numbers are sequential placeholders (`+256700000001`…).
- Members 11–20 are named "Member NN (placeholder)" — rename them in the
  Members screen as the real roll is confirmed.

Positions are seeded from the club's elected leadership, and permission roles
are mapped from them so maker-checker works immediately: the Assistant
Treasurer holds `treasurer` while the Chief Whips hold `financial-verifier`, so
whoever records a payment is never the one who verifies it. **That mapping is
an assumption** — if the club wants verification to sit elsewhere, change
`MusuwaNationSeeder::LEADERSHIP`.

---

## 4. Cross-cutting gaps

### Notifications — not started
Nothing notifies anyone of anything. The proposal calls for in-app plus optional
email on: period opened, deadline approaching, obligation overdue, payment
verified/rejected, meeting scheduled, agenda/minutes published, vote opened and
closing, action assigned/overdue, monthly report published. The `notifications`
table from the data model does not exist yet.

### Privacy gating of member data
`MemberPolicy::view` returns `true` for every authenticated member, so anyone can
see any member's phone number and emergency contact. The proposal asks for
contact details to be permission-restricted. Partial mitigation is in place —
`MemberController::show` hides the linked `User` record and only sends `email` to
officers — but the underlying policy decision has not been made. **Needs a
governance decision, not just code.**

### Concurrency hardening
`VerifyPayment` guards against double verification with a status check inside a
transaction, but takes no row lock. Under SQLite this is fine; if the club moves
to MySQL/Postgres with concurrent officers, add `lockForUpdate()` on the payment
and obligations. The proposal explicitly asks for concurrency tests preventing
double verification and duplicate allocation.

### Scheduler and queue
No scheduled commands exist. At minimum an overdue sweep (§5) and later the
report generation jobs. The queue worker runs via `composer dev` but nothing
dispatches to it.

---

## 5. Phase 2 remainder (Contributions)

| Item | Detail |
|---|---|
| **Overdue marking** | `grace_ends_on` is stored on the period but nothing moves obligations to an overdue state after it passes. Needs a scheduled command. `ObligationStatus` currently has no `Overdue` case — add it, or derive overdue from the grace date at read time and document the choice. |
| **Closing a period** | `ContributionPeriodStatus::Closed` exists in the enum with no action or route to reach it. Closing should stop new obligations and feed the monthly reconciliation. |
| **Payment reversals** | `PaymentStatus::Reversed` exists and is unimplemented. Correcting a verified payment is impossible today. Per the acceptance criteria a correction must reference the original record and capture a reason — reversal must unwind `payment_allocations` and restore obligation `amount_paid`/status. |
| **Receipts** | No receipt is produced when a payment is verified. |
| **Arrears view** | Outstanding amounts are visible per member and per period, but there is no arrears report listing who owes what, aged. |
| **Waive / cancel an obligation** | `ObligationStatus::Waived` and `Cancelled` exist with no route to set them. Requires authority and audit evidence per the business rules. |

---

## 6. Phase 3 remainder (Governance)

| Item | Detail |
|---|---|
| **Resolutions register** | Deferred by design. A passed proposal plus confirmed minutes currently serve as the decision record. A dedicated `resolutions` table would give decisions a stable reference. |
| **`resolution_reference` is free text** | `membership_status_histories.resolution_reference` and `expenses` reference resolutions as strings. Once a resolutions register exists these should become real foreign keys. |
| **Cancelling a meeting** | `MeetingStatus::Cancelled` exists; no route sets it. |
| **Withdrawing a proposal** | `ProposalStatus::Withdrawn` exists; no route sets it. |
| **Minutes corrections** | Confirmed minutes are correctly immutable, but there is no corrective mechanism — currently the only path is a new meeting record. Decide the correction policy. |
| **Attendance for non-active members** | Only active members appear on the attendance screen. A member suspended mid-year who attends cannot be recorded. |

---

## 7. Phase 4 remainder (Finance & Reports)

**Built (unverified):** expenses with three-way separation of duties, external
accounts with masked identifiers, reconciliation with derived expected balance,
difference items, rejection and month lock, a combined member/officer
dashboard, and the monthly transparency report.

Still outstanding:

| Item | Detail |
|---|---|
| **Phase 4 controller tests** | The Actions are covered (`ExpenseWorkflowTest`, `ReconciliationWorkflowTest`) but no feature tests exist for the expense, reconciliation, item, rejection, external-account, dashboard or monthly-report controllers. The 100% coverage gate **will fail** until these are written. This is the largest single piece of remaining Phase 4 work. |
| **Reconciliation items UI** | The action, request, controller and routes exist (`reconciliation-item.store` / `.update`), but the reconciliation screen has no form to add or resolve an item yet. `ConfirmReconciliation` refuses to confirm while any item is unresolved, so a reconciliation with a difference is currently blocked from the UI side. |
| **Statement attachment** | The proposal asks for the external statement to be attached to the reconciliation. Only expense and payment evidence upload exists. |
| **Adjustments after lock** | A locked month is correctly immutable, but the "controlled adjustment" path the proposal calls for does not exist. |
| **Remaining reports and exports** | The monthly transparency report is built as an on-screen report. Still missing from §16 of the proposal: member statement, contribution collection, arrears ageing, expense report, governance report, audit export — and there is no CSV or PDF output anywhere. |
| **Budgets** | Not started, and not scheduled.

---

## 7.1 Requested but not built: elections, user and role management

Four requirements were added on 5 August 2026. None are built yet. They are
recorded here in enough detail to start from, and they are **larger than they
look** — the third one in particular changes how authorisation works.

### a. Positions must be elected and transferable

Today `members.position` is a single nullable enum column: an office is a fact
about a member, with no history and no way to hand it over.

**Needed:** a `position_holdings` table — `member_id`, `position`, `held_from`,
`held_to` (null while current), and `elected_via_proposal_id` linking to the
vote that put them there. `Member::currentPosition()` reads the open holding.
A `TransferPosition` action closes the incumbent's holding and opens the
successor's inside one transaction, refusing to leave two people in one office.
Phase 3's proposals and votes already provide the election mechanism — an
election is a proposal whose passing triggers the transfer.

Migrating the existing column into the new table is a data migration, not just
a schema one.

### b. User management

**Needed:** a screen listing members, showing who does and does not have a
login, with an action to create one for a member and assign their role. This is
the practical half of the §3 blocker: it does not need email invitations to be
useful, since an officer can create the account and hand over the password —
though invitations remain the better answer.

Must respect: one user per member (`members.user_id` is unique), and creating a
login should be audited like every other sensitive action.

### c. Role management with permissions — **this is a refactor, not a feature**

The request is CRUD for roles with permissions attached at creation, plus a
`RolePermissionSeeder`.

The obstacle: `App\Enums\ClubRole` is a hard-coded enum, and **every policy in
the application checks role names against it** (`$user->hasRole(ClubRole::
Administrator->value)`). Roles cannot become user-editable while policies ask
"is this person an administrator?" — the moment somebody creates a "Deputy
Treasurer" role, no policy grants it anything.

The correct shape:

1. Define a fixed **permission** catalogue (`members.create`,
   `payments.verify`, `expenses.approve`, `reconciliation.confirm`, …) — these
   stay in code, because policies must reference something stable.
2. Rewrite every policy to check `$user->can('payments.verify')` instead of
   `hasRole(...)`.
3. Make roles data: CRUD screens, with permissions attached at creation.
4. `RolePermissionSeeder` seeds the catalogue plus the default club roles as
   starting bundles.

Keep the maker-checker rules where they are. They are **not** permissions —
"cannot verify a payment you recorded" is a row-level rule enforced in the
Action, and no permission grant should ever be able to switch it off.

Do (c) before (b), or the user management screen will be built against an
authorisation model that is about to change.

---

## 8. Phase 5 — Pilot and hardening

None of this has been started.

- **Browser tests do not run.** The `Browser` suite hangs because Playwright
  browsers are not installed (`bunx playwright install`). `composer test`
  therefore cannot complete — use `--testsuite=Unit,Feature`.
- Security review: rate limiting beyond login, session settings, file-type
  validation hardening, dependency audit (`composer audit` currently reports
  advisories).
- Backup and restore: no backup configured, and the restore has never been
  tested. The proposal is explicit that untested backups do not count.
- Mobile QA across the real screens on a phone.
- UAT scripts per role, and training for the officers.
- Deployment: HTTPS, secrets, queue worker, scheduler, health checks, error
  monitoring. Nothing is deployed.

---

## 9. Technical debt and decisions taken

- **Registration is commented out, not deleted.** `UserController::create`/
  `store`, the register routes, `user/create.tsx` and the registration tests are
  all commented with restore instructions. `CreateUserRequest` is kept intact
  and covered by `tests/Unit/Requests/CreateUserRequestTest.php` because the
  Pest `laravel` arch preset requires every FormRequest to have `rules()`.
- **`nav-main.tsx` and `nav-footer.tsx` are dead** since the sidebar was rebuilt
  on shadcn `sidebar-03`. Safe to delete.
- **SSR is disabled for tests** via `INERTIA_SSR_ENABLED=false` in `phpunit.xml`.
  Without it every full-page test 500s. This was a pre-existing fault.
- **Money is stored as whole UGX integers.** No minor units, no decimals. If the
  club ever needs cents this is a migration.
- **100% line coverage is enforced.** Unreachable branches fail the build — the
  usual culprits are null checks a policy already guarantees, and `->map()`
  closures over collections no test populates.
- **`package.json` must not be bumped casually.** `composer require` triggers
  this starter kit's `post-update-cmd`, which runs `npm-check-updates -u` and
  rewrites every frontend dependency. Revert that hunk after adding any PHP
  package.

---

## 10. Later phases (not scheduled)

Multi-club tenancy · Mobile Money and bank statement integration with automated
matching · investment portfolio, capital calls, unit ownership and valuations ·
member loans (needs separate legal, risk and accounting design) · budgeting and
procurement · native mobile app with offline capture · electronic signatures ·
advanced analytics and an external audit workspace.
