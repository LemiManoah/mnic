<?php

declare(strict_types=1);

use App\Actions\CreateActionItem;
use App\Actions\NotifyMembers;
use App\Actions\OpenProposalVoting;
use App\Actions\PublishMinutes;
use App\Actions\RejectPayment;
use App\Actions\ScheduleMeeting;
use App\Actions\VerifyPayment;
use App\Enums\MemberStatus;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Proposal;
use App\Notifications\ActionItemAssigned;
use App\Notifications\MeetingScheduled;
use App\Notifications\MinutesPublished;
use App\Notifications\PaymentRejected;
use App\Notifications\PaymentVerified;
use App\Notifications\ProposalVotingOpened;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
});

it('tells a member when their payment is verified', function (): void {
    $member = memberWithRole(App\Enums\ClubRole::Member);
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
    $member = memberWithRole(App\Enums\ClubRole::Member);
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
    $withLogin = memberWithRole(App\Enums\ClubRole::Member);
    $inactive = memberWithRole(App\Enums\ClubRole::Member, ['status' => MemberStatus::Exited]);

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
    $voter = memberWithRole(App\Enums\ClubRole::Member);
    $proposal = Proposal::factory()->create();

    resolve(OpenProposalVoting::class)
        ->handle($proposal, now()->addWeek()->toDateTimeString());

    Notification::assertSentTo($voter->user, ProposalVotingOpened::class);
});

it('tells only the owner when an action is assigned', function (): void {
    $owner = memberWithRole(App\Enums\ClubRole::Member);
    $bystander = memberWithRole(App\Enums\ClubRole::Member);

    resolve(CreateActionItem::class)->handle([
        'title' => 'Collect the land search report',
        'owner_member_id' => $owner->id,
    ]);

    Notification::assertSentTo($owner->user, ActionItemAssigned::class);
    Notification::assertNotSentTo($bystander->user, ActionItemAssigned::class);
});

it('sends nothing when an action has no owner', function (): void {
    memberWithRole(App\Enums\ClubRole::Member);

    resolve(CreateActionItem::class)->handle(['title' => 'Unowned note']);

    Notification::assertNothingSent();
});

it('tells the active roll when minutes are published', function (): void {
    $member = memberWithRole(App\Enums\ClubRole::Member);
    $meeting = Meeting::factory()->create();

    resolve(PublishMinutes::class)->handle($meeting, 'The meeting opened at 18:00.');

    Notification::assertSentTo($member->user, MinutesPublished::class);
});

it('sends each recipient once even when a member is listed twice', function (): void {
    $member = memberWithRole(App\Enums\ClubRole::Member);
    $meeting = Meeting::factory()->create();

    resolve(NotifyMembers::class)->handle(
        [$member, $member],
        new MeetingScheduled($meeting),
    );

    Notification::assertSentToTimes($member->user, MeetingScheduled::class, 1);
});
