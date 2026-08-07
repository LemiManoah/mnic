<?php

declare(strict_types=1);

use App\Enums\ActionItemStatus;
use App\Enums\ClubRole;
use App\Enums\ObligationStatus;
use App\Models\ActionItem;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Proposal;
use App\Models\ProposalEligibleVoter;
use App\Models\Vote;
use App\Notifications\ActionItemOverdue;
use App\Notifications\ObligationOverdue;
use App\Notifications\ProposalVotingClosing;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
});

it('chases a member whose grace period has closed', function (): void {
    $member = memberWithRole(ClubRole::Member);

    $period = ContributionPeriod::factory()->create([
        'grace_ends_on' => today()->subWeek(),
    ]);

    MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
        'status' => ObligationStatus::Unpaid,
    ]);

    $this->artisan('club:sweep-overdue')->assertSuccessful();

    Notification::assertSentTo($member->user, ObligationOverdue::class);
});

it('leaves alone a member still inside the grace period', function (): void {
    $member = memberWithRole(ClubRole::Member);

    $period = ContributionPeriod::factory()->create([
        'grace_ends_on' => today()->addWeek(),
    ]);

    MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
        'status' => ObligationStatus::Unpaid,
    ]);

    $this->artisan('club:sweep-overdue')->assertSuccessful();

    Notification::assertNotSentTo($member->user, ObligationOverdue::class);
});

it('leaves alone a month that was waived', function (): void {
    $member = memberWithRole(ClubRole::Member);

    $period = ContributionPeriod::factory()->create([
        'grace_ends_on' => today()->subWeek(),
    ]);

    MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
        'status' => ObligationStatus::Waived,
    ]);

    $this->artisan('club:sweep-overdue')->assertSuccessful();

    Notification::assertNotSentTo($member->user, ObligationOverdue::class);
});

it('chases the owner of an overdue action', function (): void {
    $owner = memberWithRole(ClubRole::Member);

    ActionItem::factory()->create([
        'owner_member_id' => $owner->id,
        'status' => ActionItemStatus::Open,
        'due_on' => today()->subWeek(),
    ]);

    $this->artisan('club:sweep-overdue')->assertSuccessful();

    Notification::assertSentTo($owner->user, ActionItemOverdue::class);
});

it('leaves alone an action that is already completed', function (): void {
    $owner = memberWithRole(ClubRole::Member);

    ActionItem::factory()->create([
        'owner_member_id' => $owner->id,
        'status' => ActionItemStatus::Completed,
        'due_on' => today()->subWeek(),
    ]);

    $this->artisan('club:sweep-overdue')->assertSuccessful();

    Notification::assertNotSentTo($owner->user, ActionItemOverdue::class);
});

it('skips an overdue action nobody owns', function (): void {
    ActionItem::factory()->create([
        'owner_member_id' => null,
        'status' => ActionItemStatus::Open,
        'due_on' => today()->subWeek(),
    ]);

    $this->artisan('club:sweep-overdue')->assertSuccessful();

    Notification::assertNothingSent();
});

it('reminds only the members who have not voted yet', function (): void {
    $voted = memberWithRole(ClubRole::Member);
    $silent = memberWithRole(ClubRole::Member);

    $proposal = Proposal::factory()->open()->create([
        'closes_at' => now()->addHours(6),
    ]);

    foreach ([$voted, $silent] as $member) {
        ProposalEligibleVoter::factory()->create([
            'proposal_id' => $proposal->id,
            'member_id' => $member->id,
        ]);
    }

    Vote::factory()->create([
        'proposal_id' => $proposal->id,
        'member_id' => $voted->id,
    ]);

    $this->artisan('club:sweep-overdue')->assertSuccessful();

    Notification::assertSentTo($silent->user, ProposalVotingClosing::class);
    // Nagging somebody who already voted is how people learn to ignore these.
    Notification::assertNotSentTo($voted->user, ProposalVotingClosing::class);
});

it('ignores a vote that is not closing soon', function (): void {
    $member = memberWithRole(ClubRole::Member);

    $proposal = Proposal::factory()->open()->create([
        'closes_at' => now()->addWeeks(2),
    ]);

    ProposalEligibleVoter::factory()->create([
        'proposal_id' => $proposal->id,
        'member_id' => $member->id,
    ]);

    $this->artisan('club:sweep-overdue')->assertSuccessful();

    Notification::assertNotSentTo($member->user, ProposalVotingClosing::class);
});

it('derives overdue from the grace date rather than a stored flag', function (): void {
    $past = ContributionPeriod::factory()->create(['grace_ends_on' => today()->subDay()]);
    $future = ContributionPeriod::factory()->create(['grace_ends_on' => today()->addDay()]);

    $overdue = MemberObligation::factory()->create([
        'contribution_period_id' => $past->id,
        'status' => ObligationStatus::PartiallyPaid,
    ]);

    $current = MemberObligation::factory()->create([
        'contribution_period_id' => $future->id,
        'status' => ObligationStatus::Unpaid,
    ]);

    $settled = MemberObligation::factory()->create([
        'contribution_period_id' => $past->id,
        'status' => ObligationStatus::Paid,
    ]);

    expect($overdue->isOverdue())->toBeTrue()
        ->and($current->isOverdue())->toBeFalse()
        ->and($settled->isOverdue())->toBeFalse()
        ->and(MemberObligation::query()->overdue()->pluck('id')->all())->toBe([$overdue->id]);
});
