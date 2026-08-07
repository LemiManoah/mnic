# Musuwa Nation — How the System Works

A guide for club members and officers. No technical knowledge assumed.

If you only read one section, read **§3 — The two-person rule**. It explains
why the system sometimes refuses to let you do something, and that refusal is
the whole point of having it.

---

## Contents

1. [What this system is, and what it is not](#1-what-this-system-is-and-what-it-is-not)
2. [Getting in](#2-getting-in)
3. [The two-person rule](#3-the-two-person-rule)
4. [Two kinds of "role"](#4-two-kinds-of-role)
5. [Who can do what](#5-who-can-do-what)
6. [The words the system uses](#6-the-words-the-system-uses)
7. [The month, start to finish](#7-the-month-start-to-finish)
8. [Walkthroughs — Every member](#8-walkthroughs--every-member)
9. [Walkthroughs — Treasurer](#9-walkthroughs--treasurer)
10. [Walkthroughs — Chief Whip (verifier)](#10-walkthroughs--chief-whip-verifier)
11. [Walkthroughs — Secretary](#11-walkthroughs--secretary)
12. [Walkthroughs — Chairperson](#12-walkthroughs--chairperson)
13. [Walkthroughs — Elections and positions](#13-walkthroughs--elections-and-positions)
14. [Walkthroughs — Administrator](#14-walkthroughs--administrator)
15. [Things the system will refuse to do](#15-things-the-system-will-refuse-to-do)
16. [What does not work yet](#16-what-does-not-work-yet)
17. [Common questions](#17-common-questions)

---

## 1. What this system is, and what it is not

**It is a shared record book.** Every member sees the same numbers. When Conrad
asks "how much has the club collected this year?", nobody has to scroll back
through WhatsApp — the answer is on the screen, and it is the same answer
everyone else sees.

**It does not hold money.** This matters, so it is worth being blunt about it.
The club's money sits where it always sat: in the Mobile Money account and the
bank. This system records *that* money moved. It cannot move money itself, and
a number on this screen is not proof that cash exists — which is exactly why
the reconciliation step in §9 exists.

**It replaces arguments with a record.** Who paid, when, who confirmed it, who
voted which way, what was decided at the meeting, who was supposed to follow
up. All of it is written down, with a name and a timestamp attached, and none
of it can be quietly edited later.

> **As a member,** I want to see the club's real position without asking
> anybody, **so that** I can trust the figures instead of taking someone's word.

---

## 2. Getting in

There is **no "Sign up" button**, and that is deliberate. The club decides who
is a member; you cannot add yourself.

**How you get an account:** the Secretary or the Administrator creates it for
you and hands you a temporary password. Change it once you are in
(your name in the bottom-left corner → **Settings** → **Password**).

**If you forget your password**, ask the Administrator. Do not expect the
"Forgot password" email to reach you yet — see §16.

> **As a new member,** I want the Secretary to hand me a login, **so that** I
> can see my own contribution record from day one.

---

## 3. The two-person rule

This is the single most important idea in the system.

**Nobody can both do a thing and confirm the thing.**

The Treasurer records that Honest paid UGX 60,000. That payment then sits there
marked **submitted** — it does not count towards anything yet. A Chief Whip
opens it, looks at the Mobile Money screenshot, and clicks **Verify**. Only then
does it count.

If the Treasurer tries to verify their own entry, the system refuses. Not
"hides the button" — refuses, at the deepest level, every time, for everyone.
Even the Administrator cannot get around it.

The same shape repeats everywhere money or authority is involved:

| Thing | Who does it | Who confirms it |
|---|---|---|
| A payment | Treasurer (or the member themselves) | Chief Whip / financial verifier — **never the recorder** |
| An expense | Whoever requests it | Chairperson approves → then a **third person** verifies |
| Monthly reconciliation | Treasurer prepares | Chief Whip confirms — **never the preparer** |

Notice the expense chain needs **three** different people. The person who asked
for the money cannot approve it, and neither the requester nor the approver can
be the one who later confirms it was properly spent.

> **As a member who is not an officer,** I want to know that no single person
> can move club money on their own say-so, **so that** I do not have to rely on
> trusting one individual.

**Why it feels annoying sometimes:** if you are the Treasurer and you recorded
a payment, you will find you cannot verify it even though you know it is
correct. That is not a bug. Ask a Chief Whip to look at it.

---

## 4. Two kinds of "role"

The system uses the word "role" for two different things, and mixing them up
causes confusion. They are kept separate on purpose.

### Club position — *what the members elected you to be*

Chairperson. Vice Chairperson. General Secretary. Treasurer. Mobilizer. Chief
Whip. And the assistants to each.

This is your **title in the club**. It comes from a vote of the membership. It
appears on your member profile. Some positions carry no special powers in the
software at all — a Mobilizer's job is to mobilise people, which is not
something a computer can help with.

### System role — *what the software lets you click*

`treasurer`, `secretary`, `financial-verifier`, `administrator`, `member`.

This is purely about buttons and screens. It is a bundle of permissions.

### Why separate them?

Because they genuinely are different. Consider:

- **Alfred Musimenta** holds the club position **Chief Whip**. His system role
  is `financial-verifier` — because the club decided the whips should be the
  ones checking the Treasurer's work.
- **Feta Jeff Owen** holds the club position **Mobilizer**. His system role is
  just `member`, because mobilising people needs no special screens.
- The **Administrator** is a system role with no club position at all. It is a
  caretaker job, not an office anybody was elected to.

If the club elects a new Treasurer next year, the *position* moves to the new
person, and the Administrator moves the *system role* to match. Two separate
acts, because they are two separate facts.

> **As the Chairperson,** I want the elected offices recorded separately from
> software permissions, **so that** changing who holds an office does not
> accidentally give somebody the wrong buttons.

---

## 5. Who can do what

Plain-language summary. The Administrator can do everything except break the
two-person rule.

| | Member | Treasurer | Chief Whip | Secretary | Chairperson | Admin |
|---|---|---|---|---|---|---|
| See the roster, periods, reports | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Record own payment | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Record a payment for someone else | — | ✅ | — | — | — | ✅ |
| **Verify** a payment | — | — | ✅ | — | — | ✅ |
| Open a contribution period | — | ✅ | — | — | — | ✅ |
| Request an expense | — | ✅ | — | ✅ | ✅ | ✅ |
| **Approve** an expense | — | — | — | — | ✅ | ✅ |
| Record that an expense was paid | — | ✅ | — | — | — | ✅ |
| **Verify** an expense | — | — | ✅ | — | — | ✅ |
| Prepare a reconciliation | — | ✅ | — | — | — | ✅ |
| **Confirm** a reconciliation | — | — | ✅ | — | — | ✅ |
| Lock the month | — | ✅ | — | — | — | ✅ |
| Add or edit members | — | — | — | ✅ | — | ✅ |
| Schedule meetings | — | — | — | ✅ | ✅ | ✅ |
| Record attendance and minutes | — | — | — | ✅ | — | ✅ |
| Raise a proposal, open/close voting | — | — | — | ✅ | ✅ | ✅ |
| Vote | ✅ (if eligible) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Request a payment reversal | — | ✅ | ✅ | — | — | ✅ |
| **Approve** a reversal | — | — | ✅ | — | — | ✅ |
| Waive or cancel an obligation | — | ✅ | — | — | — | ✅ |
| Start an election, open/close its voting | — | — | — | ✅ | ✅ | ✅ |
| Vote in an election | ✅ (if eligible) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Transfer an office directly | — | — | — | — | ✅ | ✅ |
| Assign actions | — | — | — | ✅ | ✅ | ✅ |
| Update **your own** action | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Read the audit log | — | — | — | ✅ | — | ✅ |
| Change club settings, roles, logins | — | — | — | — | — | ✅ |

These bundles are **not fixed in stone**. The Administrator can create new
system roles and choose exactly which permissions go in them — see §13.

---

## 6. The words the system uses

Six terms do most of the work. Learn these and the screens read easily.

**Contribution period** — one month. "2026-08" is August 2026. Opening a period
creates one obligation for every active member.

**Obligation** — what one member owes for one month. Currently UGX 60,000. It
starts *unpaid*, becomes *partially paid* if some money arrives, and *paid* when
it is fully settled.

**Payment** — money that actually moved. A payment is not tied to a month when
you record it; the system works out which months it settles when it is verified.

**Allocation** — the link between a payment and the obligations it settled. If
Ronald owes for June and July and pays UGX 120,000, the system creates two
allocations of UGX 60,000 each. **Oldest month first, always** — arrears clear
before you run ahead.

**Advance** — money left over after every outstanding obligation is settled.
If Ronald pays UGX 150,000 and only owes UGX 120,000, the extra UGX 30,000 sits
as an advance against future months. It is his money, it is recorded, and it
does not give him any extra vote or extra ownership.

**Reconciliation** — the monthly check that the system's numbers match the real
Mobile Money statement. Until this is done and confirmed, treat the figures as
provisional. The dashboard says **unreconciled** in red when it has not been
done.

---

## 7. The month, start to finish

The rhythm the club settles into:

| When | What happens | Who |
|---|---|---|
| 1st | Open the contribution period. Every active member gets an obligation. | Treasurer |
| 1st–5th | Members pay through Mobile Money and submit evidence. | Everyone |
| By the 5th | **Due date.** | — |
| 5th–10th | Grace period. Payments still arrive; Treasurer records them. | Treasurer |
| Throughout | Each payment is verified by someone who did not record it. | Chief Whip |
| 11th | Anyone unpaid is now in arrears. | — |
| Month end | Prepare the reconciliation against the real statement. | Treasurer |
| Month end | Confirm the reconciliation. | Chief Whip |
| Month end | Lock the month. The record becomes permanent. | Treasurer |
| After locking | The monthly report is trustworthy — share it. | Everyone reads |

The due day (5th), grace day (10th) and amount (UGX 60,000) are all settings the
Administrator can change — see §13 for the important caveat about history.

---

## 8. Walkthroughs — Every member

### "What do I owe right now?"

> **As a member,** I want to see what I owe and what has been confirmed,
> **so that** I am never surprised by a demand for money.

Log in. The **Dashboard** opens with three cards at the top:

- **You owe** — everything outstanding across every open month. Shows a red
  *due* badge if it is above zero.
- **Verified to date** — everything you have paid that has been confirmed by a
  second person. This is the number that counts.
- **Unapplied advance** — money you have paid ahead.

For the detail, go to **Members**, find yourself, open the **Contributions**
tab. Every month is listed with what was expected, what you paid, and what is
still outstanding.

### "I have paid — how do I tell the club?"

> **As a member,** I want to submit my own payment with proof, **so that** I do
> not have to wait for the Treasurer to notice and my evidence is on record.

1. Send the money by Mobile Money as usual.
2. Screenshot the confirmation.
3. **Payments** → **Record payment**.
4. Fill in: yourself as the member, the amount, the date, the method, and the
   **transaction reference from the Mobile Money message**.
5. Attach the screenshot under Evidence.
6. Submit.

Your payment now reads **submitted**. It does not count yet. A Chief Whip will
compare it against the statement and verify it — then it counts, your
obligations update automatically, and the status turns to **verified**.

**About the reference:** the system refuses two payments with the same
reference. This is on purpose — it stops the same Mobile Money message being
entered twice, whether by accident or otherwise.

**You can only record payments for yourself.** If you try to record one for
somebody else, the system will stop you. Only the Treasurer can do that.

### "There is a vote on — how do I take part?"

> **As a member,** I want to vote on club decisions, **so that** decisions are
> genuinely the membership's and not a few people's.

**Proposals** → open the one marked **open** → read it → choose **For**,
**Against** or **Abstain** → submit.

Three things worth understanding:

- **One member, one vote.** Paying more does not buy more say.
- **You cannot see how others voted while voting is open.** The tally is hidden
  until the vote closes, so nobody can watch the numbers and vote tactically.
  After it closes, every vote is visible with the member's name.
- **If you have a conflict of interest, declare it** using the checkbox. Your
  vote still counts; the conflict is recorded beside it, permanently.

**"Why can't I vote?"** The list of who may vote is frozen at the moment voting
opens. If you joined after that, or were suspended at that moment, you are not
on the list for that particular vote. This protects old votes from being
rewritten by later membership changes.

### "What was decided at the last meeting?"

> **As a member who missed a meeting,** I want to read what was decided,
> **so that** I am not left guessing.

**Meetings** → pick the meeting → four tabs: **Agenda**, **Attendance**,
**Minutes**, **Decisions**.

Minutes are **version controlled**. If the Secretary revises them, you see
version 2 and version 1 still exists. Once minutes are **confirmed**, they can
never be edited — that is what makes them the official record.

---

## 9. Walkthroughs — Treasurer

### Opening the month

> **As Treasurer,** I want to open the month in one action, **so that** every
> member's obligation is created consistently and nobody is forgotten.

**Contribution periods** → **Open period** → choose year and month.

The system creates one obligation per **active** member, at the amount in force
today, and **writes that amount onto the period**. If the club later raises the
monthly contribution, this month keeps the old figure. History does not get
rewritten.

Suspended and exited members are skipped automatically.

You cannot open the same month twice.

### Recording payments for members

> **As Treasurer,** I want to record payments on behalf of members who do not
> use the app, **so that** nobody is left out of the record.

**Payments** → **Record payment** → pick the member.

Same form as §8, but you may choose anybody. Enter the real Mobile Money
reference and attach evidence where you have it.

**Then stop.** You cannot verify what you recorded. Tell a Chief Whip there is
a queue waiting.

### Spending club money

> **As Treasurer,** I want a clear trail for every shilling that leaves the
> club, **so that** no expense can be questioned later without an answer.

The chain is four steps and at least three people:

1. **Request** — **Expenses** → **Request expense**. Purpose, category, payee,
   amount, date, and the meeting resolution that authorised it. Attach the
   invoice.
2. **Approve** — the Chairperson approves or rejects with a reason. Not you.
3. **Record payment** — once approved, you record which account it was paid
   from, the payment reference and the date.
4. **Verify** — a Chief Whip confirms the evidence matches. Neither the
   requester nor the approver may do this.

Only at step 4 does the expense count as fully settled.

### Excusing somebody a month

> **As the Treasurer,** I want to record that the club agreed to excuse a
> member, **so that** the arrears figures reflect what was actually decided
> rather than showing a debt nobody intends to collect.

Open the member's profile → the **Contributions** tab → the month in question →
**Adjust**. You can mark it:

- **Waived** — the club agreed to let it go. The obligation is settled without
  money changing hands.
- **Cancelled** — it should never have been raised in the first place.

Either way a reason is required, and both the reason and your name go on the
record. Only an unpaid or part-paid month can be adjusted; one that is already
settled cannot.

This is a club decision, not a treasurer's discretion. Get it minuted first.

### Closing the month

> **As Treasurer,** I want to prove the club's records match the real bank
> statement, **so that** the numbers we publish are worth believing.

This is the most important thing you do all month.

1. Get the real Mobile Money / bank statement for the month.
2. **Reconciliation** → **Start reconciliation** → choose the period and
   account, enter the **opening balance** and the **closing balance the
   statement actually shows**.
3. Click **Submit**. The system now calculates what it *expected* the closing
   balance to be — opening, plus every verified payment that month, minus every
   expense actually paid — and shows you the **difference**.
4. **If the difference is zero**, hand it to a Chief Whip to confirm.
5. **If there is a difference**, it must be explained before anyone can confirm
   it. Each explanation is recorded as a reconciliation item and must be marked
   resolved. *(See §16 — the screen for this is not built yet.)*
6. Once a Chief Whip has confirmed it, **Lock** the month.

**Locking is permanent.** A locked month cannot be edited by anyone, including
the Administrator. Be sure before you click it.

---

## 10. Walkthroughs — Chief Whip (verifier)

Your job is to be the second pair of eyes. The system is built around the
assumption that you actually look.

### Verifying payments

> **As a Chief Whip,** I want to confirm each payment against real evidence,
> **so that** the club's income figures reflect money that genuinely arrived.

**Payments** → the queue shows everything marked **submitted**.

For each one: open the evidence, check the amount, date and reference against
the actual Mobile Money statement. Then:

- **Verify** — the payment counts. The system immediately settles the member's
  obligations, oldest month first, and holds any excess as an advance.
- **Reject** — you must give a reason. The reason is stored on the payment and
  in the audit log, and the member can see it.

**If the Verify button is missing**, it is because you recorded that payment
yourself, or because the payment has already been dealt with. Both are working
as intended — the button only appears when there is genuinely a decision for you
to make.

### Undoing a payment that was verified in error

> **As a Chief Whip,** I want a verified payment to be reversible, **but** I do
> not want any one officer able to quietly undo income on their own.

It takes two people, the same as verifying did.

1. Open the payment → **Request reversal**, and give a reason. The payment moves
   to **reversal pending**. It still counts for now — nothing has been undone.
2. A **different** officer opens it and either **Approves** or **Declines** the
   reversal.

On approval the system unwinds everything the verification did: the money is
taken back off each month it settled, and those obligations return to unpaid or
part-paid. The payment ends as **reversed**, with both names, both timestamps
and the reason on the record.

Nothing is deleted. A reversed payment stays visible for good — that is the
point of a reversal rather than a delete.

### Verifying expenses

**Expenses** → anything marked **paid** is waiting for you. Check the invoice
and the payment reference before verifying.

You will find you cannot verify an expense you requested, or one you approved.

### Confirming the reconciliation

> **As a Chief Whip,** I want to confirm the month's reconciliation
> independently, **so that** the published report carries somebody's signature
> other than the Treasurer's.

**Reconciliation** → the submitted one → review the opening balance, the
expected closing balance, the statement closing balance and the difference.

- If it is right, **Confirm**.
- If it is not, **Reject** with a reason. It goes back to the Treasurer for
  correction.

**You cannot confirm one you prepared.** And the system will not let you confirm
while any difference remains unexplained.

---

## 11. Walkthroughs — Secretary

### Adding a member

> **As Secretary,** I want to onboard a new member properly, **so that** their
> admission is documented from the start.

**Members** → **Add member** → member number, full name, phone, emergency
contact, joining date.

They start as **prospective**. The system writes their admission into their
status history automatically.

To make them active — which is what makes them eligible for obligations and
votes — open their profile, go to the edit screen, and change status with a
reason and effective date.

**The status path is enforced:** prospective → active → suspended → active or
exited. You cannot bring an exited member back without readmitting them
properly, and every change demands a reason.

### Giving a member a login

> **As Secretary,** I want to give a new member access, **so that** they can see
> their own record without going through me.

**Login accounts** → **Create login** → pick the member, enter their email,
choose their system role (usually `member`).

The system generates a password and **shows it once**. Copy it and hand it over.
It cannot be retrieved afterwards.

### Meetings and minutes

> **As Secretary,** I want the meeting record to be complete and final,
> **so that** decisions cannot be disputed months later.

1. **Meetings** → **Schedule meeting** — reference (e.g. MTG-2026-009), title,
   date and time, location, agenda. Do this in advance so members can prepare.
2. After the meeting: **Attendance** tab → mark each member present, apologies
   or absent → Save. The meeting becomes *completed*.
3. **Minutes** tab → write them → **Publish**. This creates version 1.
4. Corrections? Publish again — that creates version 2. Version 1 is kept.
5. When the membership accepts them, **Confirm these minutes**.

**Once confirmed, minutes cannot be edited.** Confirm only when the club has
actually accepted them. If a mistake turns up afterwards, see below.

### Correcting minutes that were already confirmed

> **As Secretary,** I want to fix an error in a confirmed record, **but** I do
> not want it to look as though the original said something it did not.

A correction is a **superseding version**, not an edit.

**Minutes** tab → **Correct these minutes**. The current text is pre-filled;
change what is wrong, say what was wrong with it, and publish.

What happens:

- The confirmed version stays in the record exactly as it was. Nothing is
  overwritten and nothing is deleted.
- Your correction becomes the next version, and it carries your reason.
- **It is not official until it is confirmed on its own account.** The club
  accepts a correction the same way it accepted the original.

Both versions show in the version history, with the reason attached to the
replacement. Anyone reading back can see what was originally agreed, what
replaced it, and why.

### Calling off a meeting

**Meetings** → open it → **Cancel meeting**, with a reason. The reason shows on
the meeting from then on.

Only a meeting that **has not happened yet** can be cancelled. Once attendance
or minutes exist, cancelling would strand them, so the system refuses.

---

## 12. Walkthroughs — Chairperson

### Putting a decision to the membership

> **As Chairperson,** I want decisions taken by a proper vote with a proper
> record, **so that** nobody can later claim the club never agreed.

1. **Proposals** → **New proposal** → title, description, and optionally the
   meeting it belongs to. It is created as a **draft** — nothing is public yet.
2. When ready: **Open voting**, and set a closing date and time.

**Opening voting freezes three things permanently:**

- **who may vote** — every active member at that instant, and nobody else
- **the quorum** — how many must take part for the result to count
- **the approval threshold** — what share of decisive votes is needed

Change the club's rules afterwards and this vote is unaffected. Admit a new
member tomorrow and they cannot vote on it. That is the point.

3. Members vote. You cannot see individual votes while it is open.
4. **Close voting and record result.**

The system then works out the outcome and writes a plain sentence explaining it,
for example: *"For 12, against 3, abstain 2. Turnout 17 of 20 eligible (quorum
10, met). Approval threshold 50%."*

**How abstentions count:** an abstention counts towards **quorum** — you turned
up — but is excluded from the **approval** calculation. So 12 for, 3 against and
2 abstentions passes on 12 out of 15 decisive votes, not 12 out of 17.

**If quorum is not met, the proposal is rejected** regardless of how the votes
split. Not enough of the membership took part for it to be the club's decision.

### Approving expenses

**Expenses** → anything **submitted** is waiting.

Check the purpose is real, the amount is reasonable, and it is covered by a
resolution. **Approve**, or **Reject** with a reason.

You cannot approve an expense you requested yourself.

### Following up

**Actions** → **Assign action** — title, owner, due date, and the meeting it
came from.

Owners update their own actions. You can update anybody's.

### Taking a proposal back off the table

> **As the Chairperson,** I want to pull a motion that has been overtaken by
> events, **so that** the club is not voting on something nobody intends to act
> on.

Open the proposal → **Withdraw this proposal**, and give a reason.

You can only withdraw a proposal that has **not yet reached a result** — a draft
or one where voting is still open. Once it has passed or been rejected, the
membership has spoken, and the way to undo that is another proposal.

If people had already voted before you withdrew it, **their votes stay in the
record**. That is deliberate: the club should be able to see that members had
committed a position before the motion was pulled.

---

## 13. Walkthroughs — Elections and positions

An **office** is something the membership votes you into. It is not the same as
what the software lets you click — see §4 if that distinction is not yet clear.

### "Who holds what office?"

**Positions** shows every office and who currently holds it, plus the full
history underneath: who held it before, from when to when, and what put them
there. Nobody is ever quietly removed from the record. If an office is empty it
says **Vacant**.

### Running an election

*Story: the Mobilizer's term is up. Shaun, the Secretary, runs the election.*

1. **Positions** → **Elections** → **Start an election**. Pick the office, give
   it a title, say what the office involves.
2. It opens as a **draft**. Nothing is visible to voters yet.
3. **Nominate** each candidate, with a short manifesto — a sentence or two on
   why they should get it. A member can only be nominated once per election.
4. When nominations are done, **Open voting** and set a closing time.

The moment voting opens, the electorate is **frozen**. Whoever is an active
member at that instant is who may vote — nobody added afterwards can join the
ballot, and nobody removed afterwards loses their vote. This is what stops an
election being influenced by who joined last week.

### "There is an election on — how do I vote?"

**Positions** → the open election. You see each candidate and their manifesto,
and you pick **one**. That is the whole ballot.

You cannot see the running tally while voting is open. This is deliberate: if
you could watch it, late voters would vote tactically rather than honestly.

You vote once. There is no changing it afterwards, so read the manifestos first.

### Closing an election

The Secretary or Chairperson closes it. At that moment:

- The counts become visible, per candidate.
- The winner is recorded, along with an outcome note explaining the result.
- **The office transfers automatically.** The previous holder's term is closed
  off with today's date, the winner's begins, and both stay in the history.

If the sitting holder wins, nothing moves — they simply continue, and the
election is recorded as a confirmation.

### Handing an office over without an election

Sometimes an office changes hands between elections — somebody resigns, or
travels. **Positions** → **Transfer office**: pick the office, the new holder,
the date it takes effect, and a reason.

The reason is required. An office moving without an explanation is exactly the
kind of thing the club will want to look back at.

You cannot transfer an office to the person who already holds it, and one person
cannot hold two offices at once — taking a new one closes the old.

---

## 14. Walkthroughs — Administrator

### Club settings

> **As Administrator,** I want to change the contribution amount without
> corrupting past records, **so that** last year's accounts still say what they
> said last year.

**Club settings** → each setting shows its current value and history.

To change one, enter the new value **and the date it takes effect**. The old
value is not overwritten — a new version is added.

**This is why history stays honest.** Raise the contribution from UGX 60,000 to
UGX 75,000 effective 1 January, and every month before that still says 60,000.
Every already-opened period keeps the figure it was opened with.

Settings include the contribution amount, due day, grace day, voting quorum
percentage, and approval threshold percentage.

### System roles

> **As Administrator,** I want to create a role that fits how the club actually
> works, **so that** we are not stuck with whatever roles the software shipped
> with.

**System roles** → **New role** → give it a lowercase hyphenated name (e.g.
`deputy-treasurer`) and tick the permissions it should carry. Permissions are
grouped by area — Members, Payments, Expenses, Reconciliations and so on.

Editing a role takes effect immediately for everyone holding it.

**Two things you cannot do:**

- **Delete or rename the `administrator` role.** The whole permission system
  hangs off it.
- **Delete a role somebody still holds.** Move them to a different role first,
  so nobody silently loses access.

**What you cannot grant, ever:** the two-person rules. There is no permission
called "verify your own payments", because no such thing may exist.

### The audit log

> **As Administrator,** I want to see who did what, **so that** any question
> about the record has an answer.

**Audit log** lists every sensitive action: member added, status changed, role
assigned, payment verified or rejected, expense approved, reconciliation locked,
setting changed, minutes confirmed. Each entry records who, what, when and from
what address.

**Nothing can be deleted from it**, including by you.

---

## 14a. What the system tells you, and when

Everything below lands in two places: your **Notifications** page in the app,
and — once email is switched on (see §16) — your inbox.

**Notifications** is the first item under Dashboard in the sidebar, with a
count next to it when you have unread ones. Open it to see everything, filter to
unread only, jump straight to whatever a notification is about, and mark items
read one at a time or all at once.

Right now the in-app page is the one that actually works, because most seeded
email addresses are placeholders that cannot receive mail.

| You are told when… | Who hears |
|---|---|
| A new month opens for contributions | Every active member |
| Your payment is verified | Just you |
| Your payment is rejected, and why | Just you |
| Your grace period closed and you still owe | Just you |
| A meeting is scheduled | Every active member |
| Minutes are published, or corrected | Every active member |
| Voting opens on a proposal | Everyone on the frozen roll for that vote |
| A vote closes within a day **and you have not voted** | Only the members still to vote |
| You are given an action | Just the owner |
| Your action is past its due date | Just the owner |

Two deliberate choices worth knowing:

**The closing-vote reminder skips people who already voted.** Being nagged about
something you have done is how people learn to ignore the emails entirely.

**You are only chased once the grace period has actually closed.** The system
does not badger anybody who is merely close to the deadline.

The chase runs once a day, mid-morning.

---

## 15. Things the system will refuse to do

When you hit one of these, the system is working correctly.

| You try to… | It refuses because… |
|---|---|
| Verify a payment you recorded | Two-person rule |
| Approve an expense you requested | Separation of duties |
| Verify an expense you requested or approved | Three people required |
| Confirm a reconciliation you prepared | Two-person rule |
| Record a payment for another member | Only the Treasurer may |
| Enter a payment reference already used | Prevents double-entry |
| Enter a zero or negative payment | Meaningless |
| Vote twice on one proposal or election | One member, one vote |
| Vote when you were not on the frozen list | Electorate fixed when voting opened |
| Vote after the closing time | Voting closed |
| Approve a reversal you requested yourself | Two-person rule |
| Reverse a payment that is not verified | There is nothing to unwind |
| Adjust a month that is already settled | Only unpaid or part-paid months |
| Edit a closed month | Raise an adjustment beside it instead |
| Adjust a month that is still open | Fix it at source; adjustments are for closed months |
| Approve an adjustment you raised yourself | Two-person rule |
| Download another member's statement | Only officers may |
| Stand twice in the same election | One candidate entry per member |
| Transfer an office to whoever already holds it | Nothing would change |
| Hold two offices at once | Taking a new one closes the old |
| Edit confirmed minutes | Confirmed minutes are the official record — publish a correction instead |
| Correct minutes that were never confirmed | Nothing official to supersede; republish instead |
| Withdraw a proposal that already passed or was rejected | The membership has spoken; raise a new proposal |
| Cancel a meeting that has already been held | Attendance and minutes hang off it |
| Edit a locked month | Locked is permanent |
| Confirm a reconciliation with unexplained differences | Every difference must be documented |
| Open the same contribution month twice | One period per month |
| Move a member from exited straight to active | Readmission must be deliberate |
| Delete the administrator role | The permission system depends on it |
| Delete a role people still hold | They would silently lose access |

---

## 16. What does not work yet

Being straight about the gaps, so nobody is caught out.

**Email is not switched on yet.** Notifications appear on your **Notifications**
page in the app straight away, but the mail transport is still pointed at a log
file, so nothing reaches your inbox. Until an administrator configures it, you
have to open the app to see them — and the club still needs its WhatsApp group
for anything urgent.

**Most seeded email addresses cannot receive mail anyway.** Even once email is
switched on, the placeholder `.test` addresses will bounce. Real addresses have
to be loaded first.

**Not everything notifies yet.** Expenses being approved or rejected, monthly
reports being published, and elections opening send nothing. Reminders also only
go out *after* a deadline has passed — nothing warns you it is approaching.

**Some downloads are spreadsheets rather than documents.** Your statement, the
monthly report and payment receipts come as proper PDFs you can print or send
on. The arrears list, payments, expenses, governance and audit exports come as
CSV, which opens in Excel and Google Sheets — those are working files for
sorting and filtering rather than documents to hand over.

**Password reset does not work for seeded accounts.** Most seeded email
addresses are placeholders that cannot receive mail. Ask the Administrator.

**The seeded data is not real.** Every seeded account shares the password
`password` and phone numbers are sequential placeholders. The names, offices and
the year's contribution history are realistic so the screens make sense, but
none of the money is. All of this must be replaced before the club relies on it.

**Only one club.** The system holds Musuwa Nation and nothing else. There is no
way to run a second club alongside it.

---

## 17. Common questions

**"I paid but the dashboard still says I owe."**
Your payment is recorded but not yet verified. It only counts once a Chief Whip
confirms it. Check **Payments** — if it says *submitted*, it is waiting.

**"I paid extra last month. Where did it go?"**
It is held as an **unapplied advance** and will settle future months
automatically. You can see it on your dashboard and on the Contributions tab of
your profile.

**"Why did my payment settle June when I meant it for August?"**
Payments always clear the oldest unpaid month first. This is deliberate — it
stops arrears being left behind while newer months are paid.

**"The club position figure looks wrong."**
Check whether the dashboard says **unreconciled** in red. If so, the month has
not been checked against the real statement and the figure is provisional.

**"I cannot see the Verify button."**
Either you do not have the verifier role, or you recorded that item yourself.
Both are working as intended.

**"Someone left the club. Do their old payments disappear?"**
No. Exited members keep their full history and their past votes remain in the
record. The club's history stays intact.

**"Can the Administrator change the numbers?"**
The Administrator can change settings and roles, but cannot verify their own
entries, cannot edit a locked month, cannot edit confirmed minutes, and cannot
remove anything from the audit log. Every action they take is logged under their
name.

**"What if the system and the bank disagree?"**
That is what reconciliation is for. The difference gets documented and
investigated rather than quietly ignored. Until it is resolved and confirmed,
the month cannot be closed.
