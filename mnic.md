# Musuwa Nation Investment Club — Status and Plan

Living document. Updated 7 August 2026.

Part one records **where the application actually stands**. Part two is the
**plan forward**, sequenced so each milestone unblocks the next. For how any
existing rule works, read the code — every business rule lives in an Action
class under `app/Actions` with a matching test.

---

# Part one — Where things stand

## 1. Health

| Gate | State |
|---|---|
| Test suite | **469 passing**, 1747 assertions (`--exclude-testsuite Browser`) |
| Rector + Pint | Clean |
| TypeScript | Clean |
| PHPStan | Level max, clean |
| **Line coverage** | **Fails the 100% gate** — see §5 |
| Browser suite | Cannot run: Playwright browsers not installed |

Run everything except coverage with:

```bash
composer lint && bun run test:types && php artisan test --compact --exclude-testsuite Browser
```

**The suite takes about 22 minutes.** `MusuwaNationSeederTest` alone accounts
for roughly eight of them, because `beforeEach` re-runs the whole seeder for
every test in the file. Worth fixing before the suite grows further — see §5.

## 2. Phases

| Phase | Scope | State |
|---|---|---|
| **1. Foundation** | Roles, member registry, effective-dated settings, audit log | ✅ Built and verified |
| **2. Contributions** | Periods, obligations, payments, verification, evidence, ledger | ✅ Built and verified |
| **3. Governance** | Meetings, attendance, minutes, proposals, votes, actions | ✅ Built and verified |
| **4. Finance & Reports** | Expenses, external accounts, reconciliation, monthly close, dashboards, monthly report | ✅ Built; two gaps in §4 |
| **5. Pilot & hardening** | UAT, security review, backup restore, mobile QA, training, deployment | ⬜ Not started |

## 3. Built since 5 August

- **Elected and transferable positions.** `position_holdings` carries the full
  history of who held which office and when. `members.position` is now a cached
  convenience column, not the source of truth — `Member::currentPosition()`
  reads the open holding.
- **Elections are polls, not proposals.** A `PositionPoll` names an office,
  takes nominations with manifestos, and members vote **for a candidate**. This
  replaced the earlier design where an election was a proposal with a single
  named person and a for/against/abstain ballot — that could not express a
  three-way race, and made "elect Fredrick" and "buy a plot" the same kind of
  object. Closing a poll transfers the office automatically.
- **Payment reversal with maker-checker.** One officer requests, a different one
  approves or declines. Approval unwinds the allocations and restores each
  obligation. `PaymentStatus::ReversalPending` sits between the two.
- **Waive or cancel an obligation** via `AdjustMemberObligation`, with a reason
  and an audit entry.
- **Administrator bypass narrowed.** `Gate::before` no longer grants blanket
  access. Policies implementing `EnforcesBusinessRules` are excluded, so an
  administrator still cannot verify an already-verified payment, vote twice, or
  edit a locked month. Previously the bypass made every such button render.
- **Debounced search and filters** on the index pages.
- **SQLite lock fix.** `config/database.php` had `busy_timeout => null`, so any
  concurrent reader made a write fail instantly. Now 5s with WAL journaling.
- **The seeder fills the screens.** Payments in every reviewable state,
  expenses at every stage, a governance cycle with attendance and confirmed
  minutes, and three elections. See §6.

## 4. Authorisation model

The application does not decide access by role name. Three layers, and the
distinction matters when adding anything new:

1. **Permissions** — `App\Enums\Permission`, a fixed catalogue in code. Policies
   reference these and nothing else. They stay in code because policies need
   something stable to point at.
2. **System roles** — rows in the Spatie tables. Fully CRUD-able by an
   administrator, with permissions attached at creation. `RolePermissionSeeder`
   seeds the catalogue plus six default bundles.
3. **Club positions** — the elected offices. A *title the membership voted for*,
   not a permission. Held through `position_holdings`.

**Administrators bypass policies via `Gate::before`, except where the policy
implements `EnforcesBusinessRules`.** That marker interface is the line between
*"are you allowed?"* (bypassable) and *"is this record in a state where that
makes sense?"* (never bypassable). Six policies carry it: payment, expense,
reconciliation, proposal, action item, and system role.

**Maker-checker rules are deliberately not permissions.** "You may not verify a
payment you recorded", "not the member who requested the expense", "not the
treasurer who prepared the reconciliation" are row-level rules enforced inside
the Actions as well as the policies. No permission grant and no administrator
bypass can switch them off. Keep it that way.

Likewise voting eligibility: the electorate is frozen when voting opens, so no
role and no bypass can add a voter to an open ballot.

## 5. The coverage gate — status unknown

**The list of "missing controller tests" that stood here until 7 August was
wrong.** It named eleven controllers as untested; all eleven have had feature
tests for some time. It was written when they were genuinely missing and never
re-checked. Every controller in `app/Http/Controllers` now has a matching
`tests/Feature/Controllers/*Test.php`.

So the gate may still fail, but **nobody has measured why**. Do that before
writing a line of test code:

```bash
XDEBUG_MODE=coverage vendor/bin/pest --parallel --coverage --exactly=100.0 --exclude-testsuite Browser
```

Coverage is per line, not per file, and feature tests exercise the Actions
behind them — so "this Action has no dedicated unit test" does not mean it is
uncovered. Only the report knows.

Watch for the two traps this repo sets: an unreachable branch fails the gate
(prefer `firstOrFail()` over a null-check-and-throw the policy already
guarantees), and a `->map()` closure over a collection no test populates counts
as uncovered.

Separately, `MusuwaNationSeederTest` re-runs the whole seeder in `beforeEach`,
so it seeds eighteen times and takes about eight minutes of the suite. The fix
is a trade-off rather than a free win — `RefreshDatabase` rebuilds the schema
per test, so seeding once means either consolidating the eighteen tests into
fewer, larger ones (cheaper, worse diagnostics) or building a snapshot
mechanism. Decide with the coverage numbers in hand.

## 6. Seeded data is placeholder

`MusuwaNationSeeder` creates the real 20-person roll, opens every month since
1 January 2026, and now seeds enough activity that every screen has something on
it and every action button has a row to act on. Before any real use:

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

Seven milestones, in order. Each removes an obstacle for the next.

## M1 — Restore the coverage gate

**Why first:** every later milestone adds code, and the longer the gate stays
red the more untested surface accumulates behind it. It has already absorbed
elections, reversal approval and the filters.

Write the feature tests listed in §5 — one per permission boundary per route,
matching the pattern in `PaymentControllerTest`. Fix the seeder test's
`beforeEach` at the same time. Then confirm:

```bash
XDEBUG_MODE=coverage vendor/bin/pest --parallel --coverage --exactly=100.0 --exclude-testsuite Browser
```

**Exit:** `composer test` passes end to end, Browser suite excepted.

## M2 — Close the functional dead ends ✅ mostly done

Three of the four items listed here on 7 August turned out to be **already
built** — the same stale-doc problem as §5. Corrected record:

- **Reconciliation differences** — already built. `AddItemDialog` and the
  per-item Resolve button are in `resources/js/pages/reconciliation/index.tsx`.
  The claim that the form did not exist dated from before it was written.
- **Cancel an action item** — already reachable. `ActionItemController` passes
  every `ActionItemStatus` case as `statusOptions` and the UI renders them all,
  so `Cancelled` has always been selectable.
- **Withdraw a proposal** — built 7 August. `WithdrawProposal`,
  `ProposalWithdrawalController`, `ProposalPolicy::withdraw`, a reason field and
  an audit entry. Votes already cast are deliberately kept: a withdrawn
  proposal that people had already voted on is part of the record.
- **Cancel a meeting** — built 7 August. `CancelMeeting`,
  `MeetingCancellationController`, `MeetingPolicy::cancel`. Only a *scheduled*
  meeting can be cancelled; one already held has attendance and minutes hanging
  off it. `MeetingPolicy` now carries `EnforcesBusinessRules` so the
  administrator bypass cannot make the button appear on a meeting that happened.
- **Minutes corrections** — built 7 August. The club chose a **superseding
  version**: `CorrectMinutes` writes version N+1 carrying `correction_reason`
  and `corrects_minute_id`, leaves the confirmed version untouched, and the
  correction must itself be confirmed before it is official.

Still outstanding:

- **Controlled adjustment after a month is locked.** A locked month is correctly
  immutable, but the proposal's "controlled adjustment" path does not exist.

**Exit:** every state in every status enum is reachable, or documented as
deliberately unreachable.

## M3 — Set the clock and the mail correctly ✅ code done

- **Timezone** — `config/app.php` now reads `env('APP_TIMEZONE', 'Africa/Kampala')`.
  Every due date, grace date and overdue comparison was three hours out on UTC;
  nothing had surfaced it because nothing acted on `grace_ends_on` until M4.
- **Channel: email**, chosen 7 August. Notifications go to `mail` *and*
  `database`, so there is an in-app record when an email is missed, filtered, or
  sent to one of the placeholder `.test` addresses.

**Still outstanding:** `MAIL_MAILER=log` in `.env`, so nothing is actually
delivered yet. Point it at a real transport and prove a message arrives. That is
deployment configuration, not code.

**Exit:** a test message reaches a real member on a real device.

## M4 — Automation and notifications ✅ mostly done

**Overdue is derived, not stored.** `MemberObligation::isOverdue()` and the
`overdue()` scope compare the period's `grace_ends_on` against today. A stored
status would need one sweep to set it and another to clear it when somebody
pays, and would be wrong in between; deriving means the answer is always current
and changing the grace day needs no migration.

**Built:** `notifications` table · `NotifyMembers` (skips members with no login,
de-duplicates recipients) · `ClubNotification` trait giving every notification
the same mail + database shape · nine notifications — payment verified,
payment rejected, obligation overdue, contribution period opened, meeting
scheduled, proposal voting opened, proposal voting closing, action item
assigned, action item overdue, minutes published/corrected.

**`club:sweep-overdue`** runs daily at 09:00 Kampala time: overdue
contributions, overdue actions, and votes closing within a day. The vote
reminder goes only to members on the frozen electorate who have **not** voted —
reminding somebody who already voted is how people learn to ignore the emails.

Notifications fire **after** the transaction commits, never inside it, so a
rollback cannot leave a member holding an email about something that did not
happen.

Still outstanding:

- Expense approved/rejected and monthly report published have no notification.
- Position polls notify nobody — only proposals do.
- Nothing yet warns that a deadline is *approaching*, only that it has passed.

**Exit:** a member learns they are in arrears without anyone telling them.

## M5 — Reports, exports and receipts

The monthly transparency report exists on screen. **There is no CSV or PDF
output anywhere in the application.** Still missing from §16 of the proposal:
member statement, contribution collection report, arrears ageing, expense
report, governance report, audit export. No receipt is produced when a payment
is verified.

**Exit:** the club can hand a member a statement and a monthly report without
opening the application.

## M6 — Pilot and hardening

Nothing here has been started, and none of it is optional before real money is
tracked.

- **Replace the seeded credentials** (§6) — real emails, real phone numbers,
  individual passwords. Needs an invite or first-login flow; there is none.
- **Privacy decision.** `MemberPolicy::view` returns `true` for everyone, so any
  member can see any member's phone and emergency contact. The proposal asks for
  contact details to be permission-gated. Partial mitigation is in place
  (`MemberController::show` hides the linked `User` and only sends `email` to
  officers) but the underlying rule is a governance decision, not a code one.
- **Concurrency.** `VerifyPayment` guards double verification with a status check
  inside a transaction but takes no row lock. Fine on SQLite; add
  `lockForUpdate()` before moving to MySQL/Postgres with concurrent officers.
- **Bulk payment entry.** The treasurer records roughly seventeen payments by
  hand every month, one form at a time. Not a correctness gap, but it is the
  single most repeated task in the application and the most likely source of
  pilot friction.
- **Browser tests.** `bunx playwright install`, then the Browser suite can run
  and `composer test` completes.
- Security review: rate limiting beyond login, session settings, file-type
  validation on evidence upload, `composer audit` (currently reports
  advisories).
- Backups: none configured, restore never tested. Untested backups do not count.
- Mobile QA on a real phone; UAT per role; officer training.
- Deployment: HTTPS, secrets, queue worker, scheduler, health checks, error
  monitoring. Nothing is deployed.

**Exit:** the founding members sign off a pilot release.

## M7 — Multiple clubs

Deliberately last, and only if a second club is actually coming. The sidebar
takes its name and logo from config, which looks like tenancy but is not: there
is no tenant model, no admin screen for changing club identity, no logo upload
and no data isolation. Retrofitting real tenancy touches every query in the
application, so it is not worth starting on the strength of one club.

**Exit:** two clubs share a deployment and neither can see the other's data.

---

# Reference

## Technical debt and decisions taken

- **Self-registration is commented out, not deleted.** `UserController::create`/
  `store`, the register routes, `user/create.tsx` and the registration tests all
  carry restore instructions. `CreateUserRequest` is kept intact and tested
  because the Pest `laravel` arch preset requires every FormRequest to have
  `rules()`. Members get logins through the user management screen instead.
- **`database/seeders/RoleSeeder.php` is dead** — superseded by
  `RolePermissionSeeder`, all references repointed. Safe to delete.
- **`ClubRole` is no longer load-bearing.** It survives only to name the default
  seeded roles and in a few tests. Authorisation goes through permissions.
- **`members.position` is a cache, not the truth.** `position_holdings` is
  authoritative. Anything reading the column directly will miss history and will
  be wrong the moment an office changes hands mid-query.
- **SSR is disabled for tests** via `INERTIA_SSR_ENABLED=false` in `phpunit.xml`.
  Without it every full-page test 500s.
- **Money is whole UGX integers.** No minor units. Adding cents is a migration.
- **`package.json` must not be bumped casually.** `composer require` triggers
  this starter kit's `post-update-cmd`, which runs `npm-check-updates -u` and
  rewrites every frontend dependency. Revert that hunk after adding any PHP
  package.
- **`wayfinder:generate` needs `--with-form`.** `vite.config.ts` sets
  `formVariants: true`, but the Artisan command defaults to off. Running it bare
  regenerates every file under `resources/js/actions` and `resources/js/routes`
  without `.form()`, producing ~60 typecheck errors in pages nobody touched. If
  a lint run suddenly reports `Property 'form' does not exist` across unrelated
  files, that is the cause — rerun with the flag, do not edit the pages.
- **shadcn blocks overwrite files.** `sidebar-03` and `dashboard-01` each
  replaced `app-sidebar.tsx` (and `dashboard-01` also `dashboard.tsx`) with
  their sample data. Their nav components ship plain `<a href>` tags that break
  Inertia navigation — convert to `Link` after any block install.

## Later phases (not scheduled)

Mobile Money and bank statement integration with automated matching · investment
portfolio, capital calls, unit ownership and valuations · member loans (needs
separate legal, risk and accounting design) · budgeting and procurement · native
mobile app with offline capture · electronic signatures · advanced analytics and
an external audit workspace.
