<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use App\Models\Member;

it('allows a secretary to change member status', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $member = Member::factory()->create(['status' => MemberStatus::Active]);

    $response = $this->actingAs($actor->user)->put(route('member-status.update', $member), [
        'to_status' => MemberStatus::Suspended->value,
        'reason' => 'Missed contributions',
        'effective_date' => now()->toDateString(),
    ]);

    $response->assertRedirectToRoute('member.index');

    expect($member->fresh()?->status)->toBe(MemberStatus::Suspended);
});

it('denies plain members from changing member status', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $member = Member::factory()->create(['status' => MemberStatus::Active]);

    $response = $this->actingAs($actor->user)->put(route('member-status.update', $member), [
        'to_status' => MemberStatus::Suspended->value,
        'reason' => 'Missed contributions',
        'effective_date' => now()->toDateString(),
    ]);

    $response->assertForbidden();

    expect($member->fresh()?->status)->toBe(MemberStatus::Active);
});

it('rejects a disallowed status transition with a validation error', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $member = Member::factory()->create(['status' => MemberStatus::Exited]);

    $response = $this->actingAs($actor->user)->put(route('member-status.update', $member), [
        'to_status' => MemberStatus::Active->value,
        'reason' => 'Reactivating',
        'effective_date' => now()->toDateString(),
    ]);

    $response->assertSessionHasErrors('to_status');

    expect($member->fresh()?->status)->toBe(MemberStatus::Exited);
});

it('requires a valid status value', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $member = Member::factory()->create(['status' => MemberStatus::Active]);

    $response = $this->actingAs($actor->user)->put(route('member-status.update', $member), [
        'to_status' => 'not-a-real-status',
        'reason' => 'Testing',
        'effective_date' => now()->toDateString(),
    ]);

    $response->assertSessionHasErrors('to_status');

    expect($member->fresh()?->status)->toBe(MemberStatus::Active);
});
