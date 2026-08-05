<?php

declare(strict_types=1);

use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;

test('to array', function (): void {
    $period = ContributionPeriod::factory()->create()->refresh();

    expect(array_keys($period->toArray()))
        ->toBe([
            'id',
            'year',
            'month',
            'amount',
            'due_date',
            'grace_ends_on',
            'status',
            'opened_by_member_id',
            'created_at',
            'updated_at',
        ]);
});

it('formats a zero padded label', function (): void {
    $period = ContributionPeriod::factory()->forMonth(2026, 3)->create();

    expect($period->label())->toBe('2026-03');
});

it('has many obligations and belongs to the member who opened it', function (): void {
    $opener = Member::factory()->create();
    $period = ContributionPeriod::factory()->create(['opened_by_member_id' => $opener->id]);

    MemberObligation::factory()->count(2)->create(['contribution_period_id' => $period->id]);

    expect($period->obligations)->toHaveCount(2)
        ->and($period->openedByMember->is($opener))->toBeTrue();
});
