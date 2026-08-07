<?php

declare(strict_types=1);

use App\Actions\ApproveExpense;
use App\Actions\CloseMonth;
use App\Actions\CreateActionItem;
use App\Actions\NotifyMembers;
use App\Actions\OpenPositionPollVoting;
use App\Actions\OpenProposalVoting;
use App\Actions\PublishMinutes;
use App\Actions\RejectExpense;
use App\Actions\RejectPayment;
use App\Actions\ScheduleMeeting;
use App\Actions\VerifyPayment;
use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use App\Models\Expense;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use App\Models\Proposal;
use App\Models\Reconciliation;
use App\Notifications\ActionItemAssigned;
use App\Notifications\ExpenseDecided;
use App\Notifications\MeetingScheduled;
use App\Notifications\MinutesPublished;
use App\Notifications\MonthClosed;
use App\Notifications\PaymentRejected;
use App\Notifications\PaymentVerified;
use App\Notifications\PositionPollVotingOpened;
use App\Notifications\ProposalVotingOpened;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
});

it('tells a member when their payment is verified', function (): void {
    $member = memberWithRole(ClubRole::Member);
    $recorder = Member::factory()->create();
    $verifier = Member::factory()->create();

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'recorded_by_member_id' => $recorder->id,
    ]);

    resolve(VerifyPayment::class)->handle($payment, $verifier);

    Notification::assertSentTo($member->user, PaymentVerified::class);
});

it('tells a member why their payment was rejected', function (): void {
    $member = memberWithRole(ClubRole::Member);
    $recorder = Member::factory()->create();
    $verifier = Member::factory()->create();

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'recorded_by_member_id' => $recorder->id,
    ]);

    resolve(RejectPayment::class)->handle($payment, $verifier, 'No evidence attached.');

    Notification::assertSentTo($member->user, PaymentRejected::class);
});

it('does not fall over notifying a member who has no login', function (): void {
    $member = Member::factory()->create(['user_id' => null]);
    $recorder = Member::factory()->create();
    $verifier = Member::factory()->create();

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'recorded_by_member_id' => $recorder->id,
    ]);

    resolve(VerifyPayment::class)->handle($payment, $verifier);

    Notification::assertNothingSent();
});

it('tells the active roll when a meeting is scheduled', function (): void {
    $withLogin = memberWithRole(ClubRole::Member);
    $inactive = memberWithRole(ClubRole::Member, ['status' => MemberStatus::Exited]);

    resolve(ScheduleMeeting::class)->handle([
        'reference' => 'MTG-2026-050',
        'title' => 'Special general meeting',
        'scheduled_for' => now()->addWeek()->toDateTimeString(),
    ]);

    Notification::assertSentTo($withLogin->user, MeetingScheduled::class);
    Notification::assertNotSentTo($inactive->user, MeetingScheduled::class);
});

it('tells the frozen electorate when voting opens', function (): void {
    seedClubSettings();
    $voter = memberWithRole(ClubRole::Member);
    $proposal = Proposal::factory()->create();

    resolve(OpenProposalVoting::class)
        ->handle($proposal, now()->addWeek()->toDateTimeString());

    Notification::assertSentTo($voter->user, ProposalVotingOpened::class);
});

it('tells only the owner when an action is assigned', function (): void {
    $owner = memberWithRole(ClubRole::Member);
    $bystander = memberWithRole(ClubRole::Member);

    resolve(CreateActionItem::class)->handle([
        'title' => 'Collect the land search report',
        'owner_member_id' => $owner->id,
    ]);

    Notification::assertSentTo($owner->user, ActionItemAssigned::class);
    Notification::assertNotSentTo($bystander->user, ActionItemAssigned::class);
});

it('sends nothing when an action has no owner', function (): void {
    memberWithRole(ClubRole::Member);

    resolve(CreateActionItem::class)->handle(['title' => 'Unowned note']);

    Notification::assertNothingSent();
});

it('tells the active roll when minutes are published', function (): void {
    $member = memberWithRole(ClubRole::Member);
    $meeting = Meeting::factory()->create();

    resolve(PublishMinutes::class)->handle($meeting, 'The meeting opened at 18:00.');

    Notification::assertSentTo($member->user, MinutesPublished::class);
});

it('tells the requester when an expense is approved or declined', function (): void {
    $requester = memberWithRole(ClubRole::Member);
    $chair = Member::factory()->create();

    $approved = Expense::factory()->create(['requested_by_member_id' => $requester->id]);
    resolve(ApproveExpense::class)->handle($approved, $chair);

    $declined = Expense::factory()->create(['requested_by_member_id' => $requester->id]);
    resolve(RejectExpense::class)->handle($declined, $chair, 'Not budgeted.');

    Notification::assertSentToTimes($requester->user, ExpenseDecided::class, 2);
});

it('tells the active roll when a month is closed', function (): void {
    $member = memberWithRole(ClubRole::Member);
    $reconciliation = Reconciliation::factory()->confirmed()->create();

    resolve(CloseMonth::class)->handle($reconciliation);

    // The report has no publishing step, so closing is the moment the figures
    // become final and therefore the event worth announcing.
    Notification::assertSentTo($member->user, MonthClosed::class);
});

it('tells the frozen roll when an election opens', function (): void {
    seedClubSettings();
    $voter = memberWithRole(ClubRole::Member);
    $poll = PositionPoll::factory()->create();

    PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    resolve(OpenPositionPollVoting::class)
        ->handle($poll, now()->addWeek()->toDateTimeString());

    Notification::assertSentTo($voter->user, PositionPollVotingOpened::class);
});

it('stores the subject, body and link the inbox page reads', function (): void {
    $member = memberWithRole(ClubRole::Member);
    $meeting = Meeting::factory()->create(['title' => 'Half-year review']);

    resolve(NotifyMembers::class)->handle([$member], new MeetingScheduled($meeting));

    // NotificationControllerTest writes rows in this shape by hand, so if the
    // trait ever changes it, this is what catches the drift.
    Notification::assertSentTo(
        $member->user,
        MeetingScheduled::class,
        function (MeetingScheduled $notification) use ($member): bool {
            $data = $notification->toArray($member->user);

            return array_keys($data) === ['subject', 'body', 'url']
                && str_contains((string) $data['subject'], 'Half-year review');
        },
    );
});

it('sends each recipient once even when a member is listed twice', function (): void {
    $member = memberWithRole(ClubRole::Member);
    $meeting = Meeting::factory()->create();

    resolve(NotifyMembers::class)->handle(
        [$member, $member],
        new MeetingScheduled($meeting),
    );

    Notification::assertSentToTimes($member->user, MeetingScheduled::class, 1);
});
