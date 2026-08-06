# Musuwa Nation Investment Club — Status and Plan

Living document. Updated 5 August 2026.

Part one records **where the application actually stands**. Part two is the
**plan forward**, sequenced so each milestone unblocks the next. For how any
existing rule works, read the code — every business rule lives in an Action
class under `app/Actions` with a matching test.

---

# Part one — Where things stand

## 1. Health

| Gate | State |
|---|---|
| Test suite | **370 passing**, 1226 assertions (`--testsuite=Unit,Feature`) |
| Rector + Pint | Clean |
| TypeScript | Clean |
| PHPStan | Level max, clean |
| **Line coverage** | **Fails the 100% gate** — see §4 |
| Browser suite | Cannot run: Playwright browsers not installed |

Run everything except coverage with:

```bash
composer lint && bun run test:types && php artisan test --compact --testsuite=Unit,Feature
```

## 2. Phases

| Phase | Scope | State |
|---|---|---|
| **1. Foundation** | Roles, member registry, effective-dated settings, audit log | ✅ Built and verified |
| **2. Contributions** | Periods, obligations, payments, verification, evidence, ledger | ✅ Built and verified |
| **3. Governance** | Meetings, attendance, minutes, proposals, votes, actions | ✅ Built and verified |
| **4. Finance & Reports** | Expenses, external accounts, reconciliation, monthly close, dashboards, monthly report | ✅ Built and verified; controller tests outstanding (§4) |
| **5. Pilot & hardening** | UAT, security review, backup restore, mobile QA, training, deployment | ⬜ Not started |

## 3. Authorisation model (rebuilt 5 August)

The application no longer decides access by role name. Three layers, and the
distinction matters when adding anything new:

1. **Permissions** — `App\Enums\Permission`, a fixed catalogue in code. Policies
   reference these and nothing else. They stay in code because policies need
   something stable to point at.
2. **System roles** — rows in the Spatie tables. Fully CRUD-able by an
   administrator, with permissions attached at creation. `RolePermissionSeeder`
   seeds the catalogue plus six default bundles reproducing the behaviour the
   app had when roles were an enum.
3. **Club positions** — the elected offices (Chairperson, Treasurer, Chief
   Whip…). A *title the membership voted for*, not a permission. Currently a
   single enum column on `members`; §6 replaces that.

**Administrators bypass every policy** via `Gate::before`, with one deliberate
exception: abilities whose subject is a `Role`. Without that exception the
structural guards on the administrator role (it can never be renamed or
deleted — `Gate::before` itself depends on it existing) would be skipped and
fail later as a 500 rather than a clean 403.

**Maker-checker rules are deliberately not permissions.** "You may not verify a
payment you recorded", "not the member who requested the expense", "not the
treasurer who prepared the reconciliation" are row-level rules enforced inside
the Actions. No permission grant and no administrator bypass can switch them
off. Keep it that way.

Likewise `ProposalPolicy::vote`: eligibility comes from the electorate frozen
when voting opened, so no role and no bypass can add a voter to an open ballot.

## 4. The coverage gate fails

`pest --coverage --exactly=100.0` will not pass. Missing feature tests for:

- `ExpenseController`, `ExpenseApprovalController`, `ExpensePaymentController`,
  `ExpenseVerificationController`
- `ReconciliationController`, `ReconciliationReviewController`,
  `ReconciliationItemController`, `ReconciliationRejectionController`
- `ExternalAccountController`, `DashboardController`, `MonthlyReportController`

The Actions behind all of these *are* covered
(`ExpenseWorkflowTest`, `ReconciliationWorkflowTest`) — it is the HTTP layer and
its permission boundaries that have no tests.

Watch for the two traps this repo sets: an unreachable branch fails the gate
(prefer `firstOrFail()` over a null-check-and-throw the policy already
guarantees), and a `->map()` closure over a collection no test populates counts
as uncovered.

## 5. Seeded data is placeholder

`MusuwaNationSeeder` creates the real 20-person roll, opens every month since
1 January 2026, settles all but the current one, leaves three members in
arrears, and takes one expense through to verification. Before any real use:

- **Every account shares the password `password`.**
- Emails are on the reserved `.test` TLD and can never receive mail, so password
  reset will not work. The exception is `lemi@gmail.com`.
- Phone numbers are sequential placeholders.
- **Unresolved:** the earlier leadership list named *Ndyomugabe Michael* as
  Assistant General Secretary, but the active roll has *Michael Nuwagaba* and no
  Ndyomugabe. The seeder uses Michael Nuwagaba. If they are different people the
  roll is missing someone.

---

# Part two — Plan forward

Six milestones. Each has an exit condition; do them in order, because each
removes an obstacle for the next.

## M1 — Elected and transferable positions

**Why first:** it is the last piece of the governance model, and it is a data
migration. Do it while the schema is small and the seeded data is disposable.

Today `members.position` is one nullable enum column: an office is a fact about
a member, with no history and no way to hand it over.

Build:

- `position_holdings` — `member_id`, `position`, `held_from`, `held_to` (null
  while current), `elected_via_proposal_id`.
- A **data migration** moving the existing column into the new table without
  losing the seeded leadership, then dropping the column.
- `Member::currentPosition()` reading the open holding.
- `TransferPosition` — closes the incumbent's holding and opens the successor's
  in one transaction, refusing to leave two people in one office or to leave a
  gap unrecorded.
- Wire it to Phase 3: an election is a proposal whose passing triggers the
  transfer. `Permission::PositionsManage` already exists for the manual path.
- A positions screen showing who holds what, with history.

**Exit:** an office can be transferred by a passed vote, and last year's holder
is still visible in the record.

## M2 — Restore the coverage gate

**Why second:** every later milestone adds code, and the longer the gate stays
red the more untested surface accumulates behind it.

Write the feature tests listed in §4 — one per permission boundary per route,
matching the pattern in `PaymentControllerTest`. Then confirm:

```bash
XDEBUG_MODE=coverage vendor/bin/pest --parallel --coverage --exactly=100.0 --testsuite=Unit,Feature
```

**Exit:** `composer test` passes end to end, Browser suite excepted.

## M3 — Correction paths

**Why third:** the club will make mistakes, and right now several of them are
unfixable in the application. This is the largest remaining *functional* gap.

- **Payment reversal.** `PaymentStatus::Reversed` exists and is unimplemented.
  A reversal must unwind `payment_allocations`, restore each obligation's
  `amount_paid` and status, reference the original record and capture a reason.
- **Waive or cancel an obligation.** `ObligationStatus::Waived` and `Cancelled`
  exist with no route. Requires authority and audit evidence.
- **Adjustments after lock.** A locked month is correctly immutable, but the
  "controlled adjustment" the proposal calls for does not exist.
- **Withdraw a proposal / cancel a meeting.** Both enum cases exist, neither has
  a route.
- **Minutes corrections.** Confirmed minutes are immutable and there is no
  corrective mechanism. Decide the policy.

**Exit:** every state in every status enum is reachable, or documented as
deliberately unreachable.

## M4 — Automation and notifications

**Why fourth:** it depends on M3, because a notification that fires on a state
nobody can correct is worse than none.

- **Overdue sweep.** `grace_ends_on` is stored but nothing acts on it. Needs a
  scheduled command, and a decision: add `ObligationStatus::Overdue`, or derive
  overdue from the grace date at read time.
- **Notifications** — nothing notifies anyone of anything today. The
  `notifications` table from the data model does not exist. Cover: period
  opened, deadline approaching, obligation overdue, payment verified/rejected,
  meeting scheduled, minutes published, vote opened and closing, action
  assigned/overdue, monthly report published.
- Nothing currently dispatches to the queue, though the worker runs under
  `composer dev`.

**Exit:** a member learns they are in arrears without anyone telling them.

## M5 — Reports and exports

The monthly transparency report exists on screen. Still missing from §16 of the
proposal: member statement, contribution collection, arrears ageing, expense
report, governance report, audit export — and there is **no CSV or PDF output
anywhere**. Receipts are not produced when a payment is verified.

Also outstanding: the reconciliation screen has no form to add or resolve a
difference item, though the action, request, controller and routes all exist.
Until that is built, a reconciliation *with* a difference cannot be confirmed
through the UI.

**Exit:** the club can hand a member a statement and a monthly report without
opening the application.

## M6 — Pilot and hardening

Nothing here has been started, and none of it is optional before real money is
tracked.

- **Replace the seeded credentials** (§5) — real emails, real phone numbers,
  individual passwords.
- **Privacy decision.** `MemberPolicy::view` returns `true` for everyone, so any
  member can see any member's phone and emergency contact. The proposal asks for
  contact details to be permission-gated. Partial mitigation is in place
  (`MemberController::show` hides the linked `User` and only sends `email` to
  officers) but the underlying rule is a governance decision, not a code one.
- **Concurrency.** `VerifyPayment` guards double verification with a status check
  inside a transaction but takes no row lock. Fine on SQLite; add
  `lockForUpdate()` before moving to MySQL/Postgres with concurrent officers.
- **Browser tests.** `bunx playwright install`, then the Browser suite can run
  and `composer test` completes.
- Security review: rate limiting beyond login, session settings, file-type
  validation, `composer audit` (currently reports advisories).
- Backups: none configured, restore never tested. Untested backups do not count.
- Mobile QA on a real phone; UAT per role; officer training.
- Deployment: HTTPS, secrets, queue worker, scheduler, health checks, error
  monitoring. Nothing is deployed.

**Exit:** the founding members sign off a pilot release.

---

# Reference

## Technical debt and decisions taken

- **Self-registration is commented out, not deleted.** `UserController::create`/
  `store`, the register routes, `user/create.tsx` and the registration tests all
  carry restore instructions. `CreateUserRequest` is kept intact and tested
  because the Pest `laravel` arch preset requires every FormRequest to have
  `rules()`. Members now get logins through the user management screen instead.
- **`database/seeders/RoleSeeder.php` is dead** — superseded by
  `RolePermissionSeeder`, all references repointed. Safe to delete.
- **`ClubRole` is no longer load-bearing.** It survives only to name the default
  seeded roles and in a few tests. Authorisation goes through permissions.
- **SSR is disabled for tests** via `INERTIA_SSR_ENABLED=false` in `phpunit.xml`.
  Without it every full-page test 500s.
- **Money is whole UGX integers.** No minor units. Adding cents is a migration.
- **`package.json` must not be bumped casually.** `composer require` triggers
  this starter kit's `post-update-cmd`, which runs `npm-check-updates -u` and
  rewrites every frontend dependency. Revert that hunk after adding any PHP
  package.
- **shadcn blocks overwrite files.** `sidebar-03` and `dashboard-01` each
  replaced `app-sidebar.tsx` (and `dashboard-01` also `dashboard.tsx`) with
  their sample data. Their nav components ship plain `<a href>` tags that break
  Inertia navigation — convert to `Link` after any block install.

## Later phases (not scheduled)

Multiple independent clubs with tenant isolation · Mobile Money and bank
statement integration with automated matching · investment portfolio, capital
calls, unit ownership and valuations · member loans (needs separate legal, risk
and accounting design) · budgeting and procurement · native mobile app with
offline capture · electronic signatures · advanced analytics and an external
audit workspace.
