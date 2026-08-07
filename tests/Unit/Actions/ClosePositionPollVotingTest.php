<?php

declare(strict_types=1);

use App\Actions\ClosePositionPollVoting;
use App\Actions\CreatePositionPoll;
use App\Actions\NominatePositionPollCandidate;
use App\Actions\OpenPositionPollVoting;
use App\Enums\ClubPosition;
use App\Enums\MemberStatus;
use App\Enums\PositionPollStatus;
use App\Models\Member;
use App\Models\PositionHolding;
use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use App\Models\PositionPollVote;

/**
 * @param  array<int, int>  $votesPerCandidate
 */
function pollWithVotes(array $votesPerCandidate, int $quorum = 0): PositionPoll
{
    $poll = PositionPoll::factory()->open(array_sum($votesPerCandidate), $quorum)->create([
        'position' => ClubPosition::Treasurer,
    ]);

    foreach ($votesPerCandidate as $count) {
        $candidate = PositionPollCandidate::factory()->create([
            'position_poll_id' => $poll->id,
        ]);

        for ($i = 0; $i < $count; $i++) {
            PositionPollVote::factory()->create([
                'position_poll_id' => $poll->id,
                'position_poll_candidate_id' => $candidate->id,
                'member_id' => Member::factory(),
            ]);
        }
    }

    return $poll;
}

it('declares the candidate with the most votes and hands over the office', function (): void {
    $poll = pollWithVotes([3, 1]);

    $closed = resolve(ClosePositionPollVoting::class)->handle($poll);

    $winner = PositionPollCandidate::query()->find($closed->winning_candidate_id);

    expect($closed->status)->toBe(PositionPollStatus::Decided)
        ->and($closed->winning_candidate_id)->not->toBeNull()
        ->and(PositionHolding::query()
            ->where('position', ClubPosition::Treasurer->value)
            ->whereNull('held_to')
            ->value('member_id'))->toBe($winner?->member_id);
});

it('fails the poll when the leaders tie and leaves the office alone', function (): void {
    $incumbent = Member::factory()->create();
    PositionHolding::factory()->create([
        'member_id' => $incumbent->id,
        'position' => ClubPosition::Treasurer,
        'held_to' => null,
    ]);

    $poll = pollWithVotes([2, 2]);

    $closed = resolve(ClosePositionPollVoting::class)->handle($poll);

    expect($closed->status)->toBe(PositionPollStatus::Failed)
        ->and($closed->winning_candidate_id)->toBeNull()
        ->and($closed->outcome_note)->toContain('tied')
        ->and(PositionHolding::query()
            ->where('position', ClubPosition::Treasurer->value)
            ->whereNull('held_to')
            ->value('member_id'))->toBe($incumbent->id);
});

it('fails the poll when turnout misses quorum', function (): void {
    $poll = pollWithVotes([2, 1], quorum: 10);

    $closed = resolve(ClosePositionPollVoting::class)->handle($poll);

    expect($closed->status)->toBe(PositionPollStatus::Failed)
        ->and($closed->outcome_note)->toContain('did not meet the quorum');
});

it('fails the poll when nobody voted at all', function (): void {
    $poll = PositionPoll::factory()->open(4, 2)->create();
    PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    $closed = resolve(ClosePositionPollVoting::class)->handle($poll);

    expect($closed->status)->toBe(PositionPollStatus::Failed);
});

it('leaves the holding in place when the incumbent wins re-election', function (): void {
    $poll = PositionPoll::factory()->open(2, 0)->create([
        'position' => ClubPosition::Treasurer,
    ]);
    $incumbent = Member::factory()->create();

    $holding = PositionHolding::factory()->create([
        'member_id' => $incumbent->id,
        'position' => ClubPosition::Treasurer,
        'held_to' => null,
    ]);

    $candidate = PositionPollCandidate::factory()->create([
        'position_poll_id' => $poll->id,
        'member_id' => $incumbent->id,
    ]);

    PositionPollVote::factory()->create([
        'position_poll_id' => $poll->id,
        'position_poll_candidate_id' => $candidate->id,
        'member_id' => Member::factory(),
    ]);

    $closed = resolve(ClosePositionPollVoting::class)->handle($poll);

    expect($closed->status)->toBe(PositionPollStatus::Decided)
        ->and(PositionHolding::query()->where('position', ClubPosition::Treasurer->value)->count())
        ->toBe(1)
        ->and($holding->fresh()?->held_to)->toBeNull();
});

it('refuses to close a poll that is not open', function (): void {
    $poll = PositionPoll::factory()->create();

    resolve(ClosePositionPollVoting::class)->handle($poll);
})->throws(InvalidArgumentException::class, 'Only an open poll can be closed.');

it('refuses a second live poll for the same office', function (): void {
    resolve(CreatePositionPoll::class)->handle(ClubPosition::Treasurer, 'First');

    resolve(CreatePositionPoll::class)->handle(ClubPosition::Treasurer, 'Second');
})->throws(InvalidArgumentException::class, 'There is already a poll under way for this office.');

it('refuses to nominate once voting has opened', function (): void {
    seedClubSettings();
    $poll = PositionPoll::factory()->open()->create();

    resolve(NominatePositionPollCandidate::class)->handle($poll, Member::factory()->create());
})->throws(InvalidArgumentException::class, 'Candidates can only be nominated while the poll is a draft.');

it('refuses to nominate a member who is not active', function (): void {
    $poll = PositionPoll::factory()->create();
    $member = Member::factory()->create(['status' => MemberStatus::Suspended]);

    resolve(NominatePositionPollCandidate::class)->handle($poll, $member);
})->throws(InvalidArgumentException::class, 'Only an active member can stand for office.');

it('refuses to nominate the same member twice', function (): void {
    $poll = PositionPoll::factory()->create();
    $member = Member::factory()->create();

    resolve(NominatePositionPollCandidate::class)->handle($poll, $member);
    resolve(NominatePositionPollCandidate::class)->handle($poll, $member);
})->throws(InvalidArgumentException::class, 'This member is already standing in this poll.');

it('refuses to open voting without a candidate', function (): void {
    seedClubSettings();
    $poll = PositionPoll::factory()->create();

    resolve(OpenPositionPollVoting::class)->handle($poll, now()->addWeek()->toDateTimeString());
})->throws(InvalidArgumentException::class, 'A poll needs at least one candidate before voting can open.');

it('refuses to open voting on a poll that is not a draft', function (): void {
    seedClubSettings();
    $poll = PositionPoll::factory()->open()->create();

    resolve(OpenPositionPollVoting::class)->handle($poll, now()->addWeek()->toDateTimeString());
})->throws(InvalidArgumentException::class, 'Only a draft poll can be opened for voting.');

it('freezes the electorate and snapshots the quorum when voting opens', function (): void {
    seedClubSettings();
    Member::factory()->count(3)->create(['status' => MemberStatus::Active]);
    Member::factory()->create(['status' => MemberStatus::Suspended]);

    $poll = PositionPoll::factory()->create();
    PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    $opened = resolve(OpenPositionPollVoting::class)->handle(
        $poll,
        now()->addWeek()->toDateTimeString(),
    );

    // The candidate's own member row is active too, so four are eligible.
    expect($opened->status)->toBe(PositionPollStatus::Open)
        ->and($opened->eligible_voter_count)->toBe(4)
        ->and($opened->eligibleVoters()->count())->toBe(4)
        ->and($opened->quorum_required)->toBeGreaterThan(0);
});

it('raises a clear error when the quorum setting is missing', function (): void {
    $poll = PositionPoll::factory()->create();
    PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    resolve(OpenPositionPollVoting::class)->handle($poll, now()->addWeek()->toDateTimeString());
})->throws(RuntimeException::class, 'Missing club setting [quorum_percent].');
