<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ClubPosition;
use App\Enums\ProposalStatus;
use App\Enums\VoteChoice;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\ProposalEligibleVoter;
use App\Models\Vote;

it('lists proposals for any authenticated member', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    Proposal::factory()->create();

    $response = $this->actingAs($actor->user)->get(route('proposal.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('proposal/index')
            ->where('canCreate', false)
            ->has('proposals.data', 1));
});

it('allows the chairperson to create a draft proposal', function (): void {
    $actor = memberWithRole(ClubRole::InterimChairperson);

    $response = $this->actingAs($actor->user)->post(route('proposal.store'), [
        'title' => 'Buy a plot',
        'description' => 'Acquire land in Wakiso.',
    ]);

    $proposal = Proposal::query()->where('title', 'Buy a plot')->first();

    $response->assertRedirectToRoute('proposal.show', $proposal);

    expect($proposal?->status)->toBe(ProposalStatus::Draft)
        ->and($proposal?->created_by_member_id)->toBe($actor->id);
});

it('allows a chairperson to create an election proposal', function (): void {
    $actor = memberWithRole(ClubRole::InterimChairperson);
    $candidate = Member::factory()->create();

    $response = $this->actingAs($actor->user)->post(route('proposal.store'), [
        'title' => 'Elect the Treasurer',
        'description' => 'Nominate a new Treasurer.',
        'election_position' => ClubPosition::Treasurer->value,
        'election_member_id' => $candidate->id,
    ]);

    $proposal = Proposal::query()->where('title', 'Elect the Treasurer')->first();

    $response->assertRedirectToRoute('proposal.show', $proposal);

    expect($proposal?->election_position)->toBe(ClubPosition::Treasurer)
        ->and($proposal?->election_member_id)->toBe($candidate->id);
});

it('denies a plain member from creating a proposal', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->post(route('proposal.store'), [
        'title' => 'Unauthorised',
        'description' => 'Nope.',
    ]);

    $response->assertForbidden();
});

it('hides individual votes while voting is open', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $proposal = Proposal::factory()->open()->create();

    Vote::factory()->create(['proposal_id' => $proposal->id]);

    $response = $this->actingAs($actor->user)->get(route('proposal.show', $proposal));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('proposal/show')
            ->where('votes', [])
            ->where('tally.for', 1));
});

it('reveals individual votes once the result is recorded', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $proposal = Proposal::factory()->create(['status' => ProposalStatus::Passed]);
    $voter = Member::factory()->create(['full_name' => 'Voting Member']);

    Vote::factory()->create([
        'proposal_id' => $proposal->id,
        'member_id' => $voter->id,
        'choice' => VoteChoice::For,
    ]);

    $response = $this->actingAs($actor->user)->get(route('proposal.show', $proposal));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->has('votes', 1)
            ->where('votes.0.member_name', 'Voting Member'));
});

it('allows the secretary to open and close voting', function (): void {
    seedClubSettings();
    $actor = memberWithRole(ClubRole::Secretary);
    $proposal = Proposal::factory()->create();

    $this->actingAs($actor->user)
        ->post(route('proposal-voting.store', $proposal), [
            'closes_at' => now()->addWeek()->toDateTimeString(),
        ])
        ->assertRedirectToRoute('proposal.show', $proposal);

    expect($proposal->fresh()?->status)->toBe(ProposalStatus::Open);

    $this->actingAs($actor->user)
        ->put(route('proposal-voting.update', $proposal))
        ->assertRedirectToRoute('proposal.show', $proposal);

    expect($proposal->fresh()?->closed_at)->not->toBeNull();
});

it('denies a plain member from opening voting', function (): void {
    seedClubSettings();
    $actor = memberWithRole(ClubRole::Member);
    $proposal = Proposal::factory()->create();

    $response = $this->actingAs($actor->user)
        ->post(route('proposal-voting.store', $proposal), [
            'closes_at' => now()->addWeek()->toDateTimeString(),
        ]);

    $response->assertForbidden();
});

it('requires a future closing time', function (): void {
    seedClubSettings();
    $actor = memberWithRole(ClubRole::Secretary);
    $proposal = Proposal::factory()->create();

    $response = $this->actingAs($actor->user)
        ->post(route('proposal-voting.store', $proposal), [
            'closes_at' => now()->subDay()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors('closes_at');
});

it('lets an eligible member cast a vote', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $proposal = Proposal::factory()->open()->create();

    ProposalEligibleVoter::factory()->create([
        'proposal_id' => $proposal->id,
        'member_id' => $actor->id,
    ]);

    $response = $this->actingAs($actor->user)->post(route('vote.store', $proposal), [
        'choice' => VoteChoice::For->value,
    ]);

    $response->assertRedirectToRoute('proposal.show', $proposal);

    expect(Vote::query()->where('proposal_id', $proposal->id)
        ->where('member_id', $actor->id)->exists())->toBeTrue();
});

it('blocks a member outside the frozen electorate from voting', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $proposal = Proposal::factory()->open()->create();

    $response = $this->actingAs($actor->user)->post(route('vote.store', $proposal), [
        'choice' => VoteChoice::For->value,
    ]);

    $response->assertForbidden();
});

it('blocks a second vote from the same member', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $proposal = Proposal::factory()->open()->create();

    ProposalEligibleVoter::factory()->create([
        'proposal_id' => $proposal->id,
        'member_id' => $actor->id,
    ]);
    Vote::factory()->create([
        'proposal_id' => $proposal->id,
        'member_id' => $actor->id,
    ]);

    $response = $this->actingAs($actor->user)->post(route('vote.store', $proposal), [
        'choice' => VoteChoice::Against->value,
    ]);

    $response->assertForbidden();
});

it('blocks voting on a proposal that is not open', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $proposal = Proposal::factory()->create();

    ProposalEligibleVoter::factory()->create([
        'proposal_id' => $proposal->id,
        'member_id' => $actor->id,
    ]);

    $response = $this->actingAs($actor->user)->post(route('vote.store', $proposal), [
        'choice' => VoteChoice::For->value,
    ]);

    $response->assertForbidden();
});
