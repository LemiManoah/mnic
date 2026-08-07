<?php

declare(strict_types=1);

use App\Actions\CloseProposalVoting;
use App\Enums\ClubPosition;
use App\Enums\ProposalStatus;
use App\Enums\VoteChoice;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\PositionHolding;
use App\Models\Proposal;
use App\Models\Vote;

function castVotes(Proposal $proposal, int $for, int $against, int $abstain): void
{
    foreach ([[VoteChoice::For, $for], [VoteChoice::Against, $against], [VoteChoice::Abstain, $abstain]] as [$choice, $count]) {
        for ($i = 0; $i < $count; $i++) {
            Vote::factory()->create([
                'proposal_id' => $proposal->id,
                'member_id' => Member::factory()->create()->id,
                'choice' => $choice,
            ]);
        }
    }
}

it('passes a proposal that meets quorum and the approval threshold', function (): void {
    $proposal = Proposal::factory()->open(eligible: 5, quorum: 3)->create();

    castVotes($proposal, for: 3, against: 1, abstain: 0);

    $closed = resolve(CloseProposalVoting::class)->handle($proposal, Member::factory()->create(), '127.0.0.1');

    expect($closed->status)->toBe(ProposalStatus::Passed)
        ->and($closed->closed_at)->not->toBeNull()
        ->and($closed->outcome_note)->toContain('For 3, against 1');

    expect(AuditLog::query()->where('auditable_id', $proposal->id)
        ->where('event', 'proposal.voting_closed')->exists())->toBeTrue();
});

it('rejects a proposal that fails to reach quorum', function (): void {
    $proposal = Proposal::factory()->open(eligible: 10, quorum: 6)->create();

    castVotes($proposal, for: 3, against: 0, abstain: 0);

    $closed = resolve(CloseProposalVoting::class)->handle($proposal);

    expect($closed->status)->toBe(ProposalStatus::Rejected)
        ->and($closed->outcome_note)->toContain('not met');
});

it('counts abstentions towards quorum but not towards approval', function (): void {
    $proposal = Proposal::factory()->open(eligible: 5, quorum: 4)->create();

    // Turnout of 4 meets quorum; of the decisive votes, 2 for versus 1 against
    // clears a 50% approval threshold.
    castVotes($proposal, for: 2, against: 1, abstain: 1);

    $closed = resolve(CloseProposalVoting::class)->handle($proposal);

    expect($closed->status)->toBe(ProposalStatus::Passed)
        ->and($closed->outcome_note)->toContain('abstain 1');
});

it('transfers a position when an election proposal passes', function (): void {
    $current = Member::factory()->create(['position' => ClubPosition::Chairperson]);
    $successor = Member::factory()->create();

    PositionHolding::factory()->create([
        'member_id' => $current->id,
        'position' => ClubPosition::Chairperson,
        'held_from' => '2026-01-01',
        'held_to' => null,
    ]);

    $proposal = Proposal::factory()->open(eligible: 5, quorum: 3)->create([
        'election_position' => ClubPosition::Chairperson,
        'election_member_id' => $successor->id,
    ]);

    castVotes($proposal, for: 3, against: 1, abstain: 0);

    $closed = resolve(CloseProposalVoting::class)->handle($proposal, $current);

    expect($closed->status)->toBe(ProposalStatus::Passed)
        ->and($current->fresh()?->position)->toBeNull()
        ->and($successor->fresh()?->position)->toBe(ClubPosition::Chairperson)
        ->and(PositionHolding::query()
            ->where('member_id', $successor->id)
            ->where('elected_via_proposal_id', $proposal->id)
            ->whereNull('held_to')
            ->exists())->toBeTrue();
});

it('rejects a proposal where every vote is an abstention', function (): void {
    $proposal = Proposal::factory()->open(eligible: 3, quorum: 2)->create();

    castVotes($proposal, for: 0, against: 0, abstain: 3);

    $closed = resolve(CloseProposalVoting::class)->handle($proposal);

    expect($closed->status)->toBe(ProposalStatus::Rejected);
});

it('rejects a tied vote against a 50 percent threshold', function (): void {
    $proposal = Proposal::factory()->open(eligible: 4, quorum: 2)->create();

    castVotes($proposal, for: 2, against: 2, abstain: 0);

    $closed = resolve(CloseProposalVoting::class)->handle($proposal);

    // 2 of 4 decisive votes is exactly 50%, which meets a 50% threshold.
    expect($closed->status)->toBe(ProposalStatus::Passed);
});

it('refuses to close a proposal that is not open', function (): void {
    resolve(CloseProposalVoting::class)->handle(Proposal::factory()->create());
})->throws(InvalidArgumentException::class);
