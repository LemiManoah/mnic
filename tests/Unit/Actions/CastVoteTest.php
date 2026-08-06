<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Enums\VoteChoice;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\ProposalEligibleVoter;
use App\Models\Vote;

function eligibleVoterFor(Proposal $proposal): Member
{
    $member = Member::factory()->create();

    ProposalEligibleVoter::factory()->create([
        'proposal_id' => $proposal->id,
        'member_id' => $member->id,
    ]);

    return $member;
}

it('records an eligible vote', function (): void {
    $proposal = Proposal::factory()->open()->create();
    $member = eligibleVoterFor($proposal);

    $vote = resolve(CastVote::class)->handle($proposal, $member, VoteChoice::For);

    expect($vote)->toBeInstanceOf(Vote::class)
        ->and($vote->choice)->toBe(VoteChoice::For)
        ->and($vote->has_conflict)->toBeFalse();
});

it('records a declared conflict of interest alongside the vote', function (): void {
    $proposal = Proposal::factory()->open()->create();
    $member = eligibleVoterFor($proposal);

    $vote = resolve(CastVote::class)
        ->handle($proposal, $member, VoteChoice::Abstain, true, 'Related to the supplier');

    expect($vote->has_conflict)->toBeTrue()
        ->and($vote->conflict_note)->toBe('Related to the supplier');
});

it('refuses a vote when the proposal is not open', function (): void {
    $proposal = Proposal::factory()->create();
    $member = eligibleVoterFor($proposal);

    resolve(CastVote::class)->handle($proposal, $member, VoteChoice::For);
})->throws(InvalidArgumentException::class);

it('refuses a vote after the closing time', function (): void {
    $proposal = Proposal::factory()->open()->create(['closes_at' => now()->subDay()]);
    $member = eligibleVoterFor($proposal);

    resolve(CastVote::class)->handle($proposal, $member, VoteChoice::For);
})->throws(InvalidArgumentException::class);

it('refuses a vote from somebody outside the frozen electorate', function (): void {
    $proposal = Proposal::factory()->open()->create();

    resolve(CastVote::class)->handle($proposal, Member::factory()->create(), VoteChoice::For);
})->throws(InvalidArgumentException::class);

it('refuses a second vote from the same member', function (): void {
    $proposal = Proposal::factory()->open()->create();
    $member = eligibleVoterFor($proposal);

    resolve(CastVote::class)->handle($proposal, $member, VoteChoice::For);
    resolve(CastVote::class)->handle($proposal, $member, VoteChoice::Against);
})->throws(InvalidArgumentException::class);
