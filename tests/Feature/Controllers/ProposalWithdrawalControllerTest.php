<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ProposalStatus;
use App\Models\Proposal;

it('lets the secretary withdraw a draft proposal', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $proposal = Proposal::factory()->create();

    $response = $this->actingAs($actor->user)
        ->put(route('proposal-withdrawal.update', $proposal), [
            'reason' => 'Superseded by a later motion.',
        ]);

    $response->assertRedirectToRoute('proposal.show', $proposal);

    expect($proposal->fresh()?->status)->toBe(ProposalStatus::Withdrawn);
});

it('requires a reason to withdraw', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $proposal = Proposal::factory()->create();

    $this->actingAs($actor->user)
        ->put(route('proposal-withdrawal.update', $proposal), [])
        ->assertSessionHasErrors('reason');
});

it('denies a plain member from withdrawing a proposal', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $proposal = Proposal::factory()->create();

    $this->actingAs($actor->user)
        ->put(route('proposal-withdrawal.update', $proposal), ['reason' => 'No.'])
        ->assertForbidden();
});

it('refuses to withdraw a proposal that already has a result', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $proposal = Proposal::factory()->create(['status' => ProposalStatus::Passed]);

    $this->actingAs($actor->user)
        ->put(route('proposal-withdrawal.update', $proposal), ['reason' => 'Too late.'])
        ->assertForbidden();
});

it('does not offer withdrawal on a decided proposal', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $proposal = Proposal::factory()->create(['status' => ProposalStatus::Rejected]);

    $this->actingAs($actor->user)
        ->get(route('proposal.show', $proposal))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('canWithdraw', false));
});
