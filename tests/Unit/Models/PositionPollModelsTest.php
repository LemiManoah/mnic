<?php

declare(strict_types=1);

use App\Enums\PositionPollStatus;
use App\Models\Member;
use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use App\Models\PositionPollEligibleVoter;
use App\Models\PositionPollVote;

it('relates a poll to the member who called it and the candidate who won', function (): void {
    $caller = Member::factory()->create();
    $poll = PositionPoll::factory()->create(['created_by_member_id' => $caller->id]);
    $winner = PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    $poll->update(['winning_candidate_id' => $winner->id]);

    expect($poll->createdByMember->is($caller))->toBeTrue()
        ->and($poll->fresh()?->winningCandidate?->is($winner))->toBeTrue();
});

it('knows whether a poll is open or settled', function (): void {
    $open = PositionPoll::factory()->create(['status' => PositionPollStatus::Open]);
    $decided = PositionPoll::factory()->create(['status' => PositionPollStatus::Decided]);
    $failed = PositionPoll::factory()->create(['status' => PositionPollStatus::Failed]);
    $draft = PositionPoll::factory()->create(['status' => PositionPollStatus::Draft]);

    expect($open->isOpen())->toBeTrue()
        ->and($draft->isOpen())->toBeFalse()
        // A poll is settled whether somebody won or nobody did.
        ->and($decided->isClosed())->toBeTrue()
        ->and($failed->isClosed())->toBeTrue()
        ->and($open->isClosed())->toBeFalse();
});

it('relates a candidate to its poll, member, nominator and votes', function (): void {
    $poll = PositionPoll::factory()->create();
    $standing = Member::factory()->create();
    $nominator = Member::factory()->create();

    $candidate = PositionPollCandidate::factory()->create([
        'position_poll_id' => $poll->id,
        'member_id' => $standing->id,
        'nominated_by_member_id' => $nominator->id,
    ]);

    PositionPollVote::factory()->count(2)->create([
        'position_poll_id' => $poll->id,
        'position_poll_candidate_id' => $candidate->id,
    ]);

    expect($candidate->positionPoll->is($poll))->toBeTrue()
        ->and($candidate->member->is($standing))->toBeTrue()
        ->and($candidate->nominatedByMember?->is($nominator))->toBeTrue()
        ->and($candidate->votes)->toHaveCount(2);
});

it('relates an eligible voter back to the poll and the member', function (): void {
    $poll = PositionPoll::factory()->create();
    $member = Member::factory()->create();

    $eligible = PositionPollEligibleVoter::factory()->create([
        'position_poll_id' => $poll->id,
        'member_id' => $member->id,
    ]);

    expect($eligible->positionPoll->is($poll))->toBeTrue()
        ->and($eligible->member->is($member))->toBeTrue();
});

it('relates a vote to its poll, voter and chosen candidate', function (): void {
    $poll = PositionPoll::factory()->create();
    $voter = Member::factory()->create();
    $candidate = PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    $vote = PositionPollVote::factory()->create([
        'position_poll_id' => $poll->id,
        'member_id' => $voter->id,
        'position_poll_candidate_id' => $candidate->id,
    ]);

    expect($vote->positionPoll->is($poll))->toBeTrue()
        ->and($vote->member->is($voter))->toBeTrue()
        ->and($vote->candidate->is($candidate))->toBeTrue();
});
