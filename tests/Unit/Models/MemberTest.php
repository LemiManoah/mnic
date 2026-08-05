<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\MembershipStatusHistory;
use App\Models\User;

test('to array', function (): void {
    $member = Member::factory()->create()->refresh();

    expect(array_keys($member->toArray()))
        ->toBe([
            'id',
            'user_id',
            'referred_by_member_id',
            'member_number',
            'full_name',
            'phone',
            'emergency_contact',
            'joined_at',
            'status',
            'created_at',
            'updated_at',
        ]);
});

it('casts status to an enum', function (): void {
    $member = Member::factory()->prospective()->create();

    expect($member->status)->toBe(MemberStatus::Prospective);
});

it('belongs to a user', function (): void {
    $user = User::factory()->create();
    $member = Member::factory()->create(['user_id' => $user->id]);

    expect($member->user->is($user))->toBeTrue();
});

it('may have no linked user', function (): void {
    $member = Member::factory()->create();

    expect($member->user)->toBeNull();
});

it('may reference the member who referred it', function (): void {
    $referrer = Member::factory()->create();
    $member = Member::factory()->create(['referred_by_member_id' => $referrer->id]);

    expect($member->referredBy->is($referrer))->toBeTrue();
});

it('has many status histories', function (): void {
    $member = Member::factory()->create();

    MembershipStatusHistory::factory()->count(2)->create(['member_id' => $member->id]);

    expect($member->statusHistories)->toHaveCount(2);
});
