<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ObligationStatus;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;

it('lists periods for any authenticated member', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    ContributionPeriod::factory()->forMonth(2026, 9)->create();

    $response = $this->actingAs($actor->user)->get(route('contribution-period.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('contribution-period/index')
            ->where('canOpenPeriod', false)
            ->has('periods.data', 1));
});

it('shows the treasurer they can open a period', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);

    $response = $this->actingAs($actor->user)->get(route('contribution-period.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->where('canOpenPeriod', true));
});

it('excludes waived and cancelled obligations from period expected totals', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'amount' => 60000,
        'status' => ObligationStatus::Unpaid,
    ]);

    MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'amount' => 60000,
        'status' => ObligationStatus::Waived,
    ]);

    MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'amount' => 60000,
        'status' => ObligationStatus::Cancelled,
    ]);

    $response = $this->actingAs($actor->user)->get(route('contribution-period.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('periods.data.0.expected_total', 60000));
});

it('shows a period with its obligations', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();
    $member = Member::factory()->create(['full_name' => 'Ledger Member']);

    MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
    ]);

    $response = $this->actingAs($actor->user)->get(route('contribution-period.show', $period));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('contribution-period/show')
            ->where('label', '2026-09')
            ->has('obligations', 1)
            ->where('obligations.0.member_name', 'Ledger Member')
            ->where('obligations.0.adjustment_reason', null)
            ->where('obligations.0.can_adjust', false));
});

it('shows treasurers which period obligations can be adjusted', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'status' => ObligationStatus::Unpaid,
    ]);

    $response = $this->actingAs($actor->user)->get(route('contribution-period.show', $period));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('obligations.0.can_adjust', true));
});

it('allows a treasurer to open a period', function (): void {
    seedClubSettings();
    $actor = memberWithRole(ClubRole::Treasurer);

    $response = $this->actingAs($actor->user)->post(route('contribution-period.store'), [
        'year' => 2026,
        'month' => 9,
    ]);

    $response->assertRedirectToRoute('contribution-period.index');

    expect(ContributionPeriod::query()->where('year', 2026)->where('month', 9)->exists())->toBeTrue();
});

it('denies a plain member from opening a period', function (): void {
    seedClubSettings();
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->post(route('contribution-period.store'), [
        'year' => 2026,
        'month' => 9,
    ]);

    $response->assertForbidden();

    expect(ContributionPeriod::query()->count())->toBe(0);
});

it('rejects opening a duplicate period', function (): void {
    seedClubSettings();
    $actor = memberWithRole(ClubRole::Treasurer);
    ContributionPeriod::factory()->forMonth(2026, 9)->create();

    $response = $this->actingAs($actor->user)->post(route('contribution-period.store'), [
        'year' => 2026,
        'month' => 9,
    ]);

    $response->assertSessionHasErrors('month');
});

it('rejects an out of range month', function (): void {
    seedClubSettings();
    $actor = memberWithRole(ClubRole::Treasurer);

    $response = $this->actingAs($actor->user)->post(route('contribution-period.store'), [
        'year' => 2026,
        'month' => 13,
    ]);

    $response->assertSessionHasErrors('month');
});
