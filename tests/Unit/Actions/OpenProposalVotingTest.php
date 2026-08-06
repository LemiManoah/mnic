<?php

declare(strict_types=1);

use App\Actions\OpenProposalVoting;
use App\Actions\UpdateSetting;
use App\Enums\ProposalStatus;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\ProposalEligibleVoter;
use App\Models\Setting;

it('freezes the electorate and thresholds when voting opens', function (): void {
    seedClubSettings();

    Member::factory()->count(4)->create();
    Member::factory()->suspended()->create();

    $proposal = Proposal::factory()->create();
    $actor = Member::factory()->create();

    $opened = resolve(OpenProposalVoting::class)
        ->handle($proposal, now()->addWeek()->toDateTimeString(), $actor, '127.0.0.1');

    // Four members plus the actor are active; the suspended one is excluded.
    expect($opened->status)->toBe(ProposalStatus::Open)
        ->and($opened->eligible_voter_count)->toBe(5)
        ->and($opened->quorum_required)->toBe(3)
        ->and($opened->approval_percent)->toBe(50);

    expect(ProposalEligibleVoter::query()->where('proposal_id', $proposal->id)->count())->toBe(5);

    expect(AuditLog::query()->where('auditable_id', $proposal->id)
        ->where('event', 'proposal.voting_opened')->exists())->toBeTrue();
});

it('does not enrol a suspended member in the electorate', function (): void {
    seedClubSettings();

    $suspended = Member::factory()->suspended()->create();
    $proposal = Proposal::factory()->create();

    resolve(OpenProposalVoting::class)->handle($proposal, now()->addWeek()->toDateTimeString());

    expect(ProposalEligibleVoter::query()
        ->where('proposal_id', $proposal->id)
        ->where('member_id', $suspended->id)
        ->exists())->toBeFalse();
});

it('keeps the frozen thresholds when club settings change afterwards', function (): void {
    seedClubSettings();

    Member::factory()->count(3)->create();
    $proposal = Proposal::factory()->create();

    resolve(OpenProposalVoting::class)->handle($proposal, now()->addWeek()->toDateTimeString());

    $frozenQuorum = $proposal->fresh()?->quorum_required;

    $setting = Setting::query()->where('key', 'quorum_percent')->firstOrFail();
    resolve(UpdateSetting::class)->handle($setting, '90', now()->toDateString());

    expect($proposal->fresh()?->quorum_required)->toBe($frozenQuorum);
});

it('refuses to open voting on a proposal that is not a draft', function (): void {
    seedClubSettings();

    $proposal = Proposal::factory()->open()->create();

    resolve(OpenProposalVoting::class)->handle($proposal, now()->addWeek()->toDateTimeString());
})->throws(InvalidArgumentException::class);

it('fails when a governance setting is missing', function (): void {
    Setting::query()->delete();

    resolve(OpenProposalVoting::class)
        ->handle(Proposal::factory()->create(), now()->addWeek()->toDateTimeString());
})->throws(RuntimeException::class);
