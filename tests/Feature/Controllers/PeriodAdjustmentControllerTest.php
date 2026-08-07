<?php

declare(strict_types=1);

use App\Enums\AdjustmentStatus;
use App\Enums\ClubRole;
use App\Models\ContributionPeriod;
use App\Models\PeriodAdjustment;

it('lists adjustments for any authenticated member', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    PeriodAdjustment::factory()->create();

    $this->actingAs($actor->user)
        ->get(route('period-adjustment.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('period-adjustment/index')
            ->has('adjustments.data', 1)
            ->where('canCreate', false)
            ->where('adjustments.data.0.can_review', false));
});

it('lets the treasurer raise an adjustment against a closed month', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $period = ContributionPeriod::factory()->closed()->create();

    $this->actingAs($actor->user)
        ->post(route('period-adjustment.store'), [
            'contribution_period_id' => $period->id,
            'amount' => -60000,
            'reason' => 'A payment was counted twice.',
        ])
        ->assertRedirectToRoute('period-adjustment.index');

    expect(PeriodAdjustment::query()->where('contribution_period_id', $period->id)->exists())
        ->toBeTrue();
});

it('rejects an adjustment of zero at validation', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $period = ContributionPeriod::factory()->closed()->create();

    $this->actingAs($actor->user)
        ->post(route('period-adjustment.store'), [
            'contribution_period_id' => $period->id,
            'amount' => 0,
            'reason' => 'Corrects nothing.',
        ])
        ->assertSessionHasErrors('amount');
});

it('denies a plain member from raising an adjustment', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $period = ContributionPeriod::factory()->closed()->create();

    $this->actingAs($actor->user)
        ->post(route('period-adjustment.store'), [
            'contribution_period_id' => $period->id,
            'amount' => -60000,
            'reason' => 'Not mine to raise.',
        ])
        ->assertForbidden();
});

it('offers only closed months to adjust', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    ContributionPeriod::factory()->closed()->create();
    ContributionPeriod::factory()->create();

    $this->actingAs($actor->user)
        ->get(route('period-adjustment.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('canCreate', true)
            ->has('closedPeriods', 1));
});

it('lets a verifier approve somebody else adjustment', function (): void {
    $requester = memberWithRole(ClubRole::Treasurer);
    $approver = memberWithRole(ClubRole::FinancialVerifier);

    $adjustment = PeriodAdjustment::factory()->create([
        'requested_by_member_id' => $requester->id,
    ]);

    $this->actingAs($approver->user)
        ->put(route('period-adjustment-review.update', $adjustment))
        ->assertRedirectToRoute('period-adjustment.index');

    expect($adjustment->fresh()?->status)->toBe(AdjustmentStatus::Approved);
});

it('lets a verifier reject with a reason', function (): void {
    $requester = memberWithRole(ClubRole::Treasurer);
    $approver = memberWithRole(ClubRole::FinancialVerifier);

    $adjustment = PeriodAdjustment::factory()->create([
        'requested_by_member_id' => $requester->id,
    ]);

    $this->actingAs($approver->user)
        ->delete(route('period-adjustment-review.destroy', $adjustment), [
            'reason' => 'The original figure was right.',
        ])
        ->assertRedirectToRoute('period-adjustment.index');

    expect($adjustment->fresh()?->status)->toBe(AdjustmentStatus::Rejected);
});

it('requires a reason to reject', function (): void {
    $requester = memberWithRole(ClubRole::Treasurer);
    $approver = memberWithRole(ClubRole::FinancialVerifier);

    $adjustment = PeriodAdjustment::factory()->create([
        'requested_by_member_id' => $requester->id,
    ]);

    $this->actingAs($approver->user)
        ->delete(route('period-adjustment-review.destroy', $adjustment))
        ->assertSessionHasErrors('reason');
});

it('does not let the member who raised it approve it, even as an administrator', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);

    $adjustment = PeriodAdjustment::factory()->create([
        'requested_by_member_id' => $actor->id,
    ]);

    // The administrator bypass stops at EnforcesBusinessRules, so this is a
    // clean 403 rather than a 500 from the Action throwing.
    $this->actingAs($actor->user)
        ->put(route('period-adjustment-review.update', $adjustment))
        ->assertForbidden();
});

it('does not offer review on an adjustment already decided', function (): void {
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    PeriodAdjustment::factory()->approved()->create();

    $this->actingAs($actor->user)
        ->get(route('period-adjustment.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('adjustments.data.0.can_review', false));
});
