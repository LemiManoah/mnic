<?php

declare(strict_types=1);

use App\Enums\ClubPosition;
use App\Enums\ClubRole;
use App\Enums\PositionPollStatus;
use App\Models\Member;
use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use App\Models\PositionPollEligibleVoter;
use App\Models\PositionPollVote;

it('lists polls for any authenticated member', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    PositionPoll::factory()->create();

    $response = $this->actingAs($actor->user)->get(route('position-poll.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('position-poll/index')
            ->where('canCreate', false)
            ->has('polls.data', 1));
});

it('filters polls by search, status and office', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);

    PositionPoll::factory()->create([
        'title' => 'Treasurer race',
        'position' => ClubPosition::Treasurer,
    ]);
    PositionPoll::factory()->open()->create([
        'title' => 'Chairperson race',
        'position' => ClubPosition::Chairperson,
    ]);

    $this->actingAs($actor->user)
        ->get(route('position-poll.index', ['search' => 'Treasurer']))
        ->assertInertia(fn ($page) => $page->has('polls.data', 1)
            ->where('polls.data.0.title', 'Treasurer race'));

    $this->actingAs($actor->user)
        ->get(route('position-poll.index', ['status' => PositionPollStatus::Open->value]))
        ->assertInertia(fn ($page) => $page->has('polls.data', 1)
            ->where('polls.data.0.title', 'Chairperson race'));

    $this->actingAs($actor->user)
        ->get(route('position-poll.index', ['position' => ClubPosition::Treasurer->value]))
        ->assertInertia(fn ($page) => $page->has('polls.data', 1));
});

it('allows an administrator to create a draft poll', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);

    $response = $this->actingAs($actor->user)->post(route('position-poll.store'), [
        'position' => ClubPosition::Treasurer->value,
        'title' => 'Treasurer election 2027',
        'description' => 'Two-year term.',
    ]);

    $poll = PositionPoll::query()->where('title', 'Treasurer election 2027')->first();

    $response->assertRedirectToRoute('position-poll.show', $poll);

    expect($poll?->status)->toBe(PositionPollStatus::Draft)
        ->and($poll?->position)->toBe(ClubPosition::Treasurer)
        ->and($poll?->created_by_member_id)->toBe($actor->id);
});

it('denies a plain member from creating a poll', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->post(route('position-poll.store'), [
        'position' => ClubPosition::Treasurer->value,
        'title' => 'Unauthorised',
    ]);

    $response->assertForbidden();
});

it('hides the running count while voting is open', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $poll = PositionPoll::factory()->open()->create();
    $candidate = PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    PositionPollVote::factory()->create([
        'position_poll_id' => $poll->id,
        'position_poll_candidate_id' => $candidate->id,
    ]);

    $response = $this->actingAs($actor->user)->get(route('position-poll.show', $poll));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('position-poll/show')
            ->where('resultsVisible', false)
            ->where('candidates.0.votes', null));
});

it('reveals the count once the poll is closed', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $poll = PositionPoll::factory()->create(['status' => PositionPollStatus::Decided]);
    $candidate = PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    PositionPollVote::factory()->create([
        'position_poll_id' => $poll->id,
        'position_poll_candidate_id' => $candidate->id,
    ]);

    $response = $this->actingAs($actor->user)->get(route('position-poll.show', $poll));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->where('resultsVisible', true)
            ->where('candidates.0.votes', 1));
});

it('allows an administrator to nominate a candidate on a draft', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $poll = PositionPoll::factory()->create();
    $candidate = Member::factory()->create();

    $response = $this->actingAs($actor->user)
        ->post(route('position-poll-candidate.store', $poll), [
            'member_id' => $candidate->id,
            'manifesto' => 'I will keep clean books.',
        ]);

    $response->assertRedirectToRoute('position-poll.show', $poll);

    expect(PositionPollCandidate::query()
        ->where('position_poll_id', $poll->id)
        ->where('member_id', $candidate->id)
        ->exists())->toBeTrue();
});

it('denies nominating once voting has opened', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $poll = PositionPoll::factory()->open()->create();

    $response = $this->actingAs($actor->user)
        ->post(route('position-poll-candidate.store', $poll), [
            'member_id' => Member::factory()->create()->id,
        ]);

    $response->assertForbidden();
});

it('opens and closes voting, transferring the office to the winner', function (): void {
    seedClubSettings();
    $actor = memberWithRole(ClubRole::Administrator);
    $poll = PositionPoll::factory()->create(['position' => ClubPosition::Treasurer]);

    $winner = Member::factory()->create();
    PositionPollCandidate::factory()->create([
        'position_poll_id' => $poll->id,
        'member_id' => $winner->id,
    ]);

    $this->actingAs($actor->user)
        ->post(route('position-poll-voting.store', $poll), [
            'closes_at' => now()->addWeek()->toDateTimeString(),
        ])
        ->assertRedirectToRoute('position-poll.show', $poll);

    expect($poll->fresh()?->status)->toBe(PositionPollStatus::Open);

    $candidate = PositionPollCandidate::query()->where('position_poll_id', $poll->id)->firstOrFail();

    $this->actingAs($actor->user)->post(route('position-poll-vote.store', $poll), [
        'position_poll_candidate_id' => $candidate->id,
    ])->assertRedirectToRoute('position-poll.show', $poll);

    $this->actingAs($actor->user)
        ->put(route('position-poll-voting.update', $poll))
        ->assertRedirectToRoute('position-poll.show', $poll);

    expect($poll->fresh()?->status)->toBe(PositionPollStatus::Decided)
        ->and($winner->fresh()?->currentPosition())->toBe(ClubPosition::Treasurer);
});

it('denies a plain member from opening voting', function (): void {
    seedClubSettings();
    $actor = memberWithRole(ClubRole::Member);
    $poll = PositionPoll::factory()->create();
    PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    $response = $this->actingAs($actor->user)
        ->post(route('position-poll-voting.store', $poll), [
            'closes_at' => now()->addWeek()->toDateTimeString(),
        ]);

    $response->assertForbidden();
});

it('requires a future closing time', function (): void {
    seedClubSettings();
    $actor = memberWithRole(ClubRole::Administrator);
    $poll = PositionPoll::factory()->create();
    PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    $response = $this->actingAs($actor->user)
        ->post(route('position-poll-voting.store', $poll), [
            'closes_at' => now()->subDay()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors('closes_at');
});

it('lets an eligible member vote exactly once', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $poll = PositionPoll::factory()->open()->create();
    $candidate = PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    PositionPollEligibleVoter::factory()->create([
        'position_poll_id' => $poll->id,
        'member_id' => $actor->id,
    ]);

    $this->actingAs($actor->user)->post(route('position-poll-vote.store', $poll), [
        'position_poll_candidate_id' => $candidate->id,
    ])->assertRedirectToRoute('position-poll.show', $poll);

    // A second attempt is blocked by the frozen-electorate policy.
    $this->actingAs($actor->user)->post(route('position-poll-vote.store', $poll), [
        'position_poll_candidate_id' => $candidate->id,
    ])->assertForbidden();

    expect(PositionPollVote::query()->where('position_poll_id', $poll->id)->count())->toBe(1);
});

it('blocks a member outside the frozen electorate from voting', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $poll = PositionPoll::factory()->open()->create();
    $candidate = PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    $response = $this->actingAs($actor->user)->post(route('position-poll-vote.store', $poll), [
        'position_poll_candidate_id' => $candidate->id,
    ]);

    $response->assertForbidden();
});

it('blocks voting on a poll that is not open', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $poll = PositionPoll::factory()->create();
    $candidate = PositionPollCandidate::factory()->create(['position_poll_id' => $poll->id]);

    PositionPollEligibleVoter::factory()->create([
        'position_poll_id' => $poll->id,
        'member_id' => $actor->id,
    ]);

    $response = $this->actingAs($actor->user)->post(route('position-poll-vote.store', $poll), [
        'position_poll_candidate_id' => $candidate->id,
    ]);

    $response->assertForbidden();
});

it('rejects a candidate standing in a different poll', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $poll = PositionPoll::factory()->open()->create();
    $otherCandidate = PositionPollCandidate::factory()->create();

    PositionPollEligibleVoter::factory()->create([
        'position_poll_id' => $poll->id,
        'member_id' => $actor->id,
    ]);

    $response = $this->actingAs($actor->user)->post(route('position-poll-vote.store', $poll), [
        'position_poll_candidate_id' => $otherCandidate->id,
    ]);

    $response->assertSessionHasErrors('position_poll_candidate_id');
});

it('denies a plain member from closing voting', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $poll = PositionPoll::factory()->open()->create();

    $response = $this->actingAs($actor->user)
        ->put(route('position-poll-voting.update', $poll));

    $response->assertForbidden();
});

it('labels every poll status', function (): void {
    foreach (PositionPollStatus::cases() as $status) {
        expect($status->label())->not->toBe('');
    }
});
