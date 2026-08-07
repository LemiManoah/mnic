<?php

declare(strict_types=1);

use App\Actions\WithdrawProposal;
use App\Enums\ProposalStatus;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\Vote;

it('withdraws a draft proposal and records why', function (): void {
    $actor = Member::factory()->create();
    $proposal = Proposal::factory()->create();

    $withdrawn = resolve(WithdrawProposal::class)
        ->handle($proposal, 'Superseded by the land acquisition motion.', $actor, '127.0.0.1');

    expect($withdrawn->status)->toBe(ProposalStatus::Withdrawn)
        ->and($withdrawn->withdrawal_reason)->toBe('Superseded by the land acquisition motion.')
        ->and($withdrawn->closed_at)->not->toBeNull()
        ->and($withdrawn->outcome_note)->toContain('Superseded by the land acquisition motion.');

    expect(AuditLog::query()
        ->where('auditable_id', $proposal->id)
        ->where('event', 'proposal.withdrawn')
        ->exists())->toBeTrue();
});

it('withdraws an open proposal without discarding the votes already cast', function (): void {
    $proposal = Proposal::factory()->open()->create();
    Vote::factory()->count(3)->create(['proposal_id' => $proposal->id]);

    resolve(WithdrawProposal::class)->handle($proposal, 'Withdrawn at the chair.');

    expect($proposal->fresh()?->status)->toBe(ProposalStatus::Withdrawn)
        ->and(Vote::query()->where('proposal_id', $proposal->id)->count())->toBe(3);
});

it('refuses to withdraw a proposal that already has a result', function (): void {
    $proposal = Proposal::factory()->create(['status' => ProposalStatus::Passed]);

    resolve(WithdrawProposal::class)->handle($proposal, 'Too late.');
})->throws(InvalidArgumentException::class);
