<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ObligationStatus;
use App\Models\MemberObligation;

it('allows a treasurer to waive an unpaid obligation', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $obligation = MemberObligation::factory()->create();

    $response = $this->actingAs($actor->user)->put(route('member-obligation-adjustment.update', $obligation), [
        'status' => ObligationStatus::Waived->value,
        'reason' => 'Member waiver approved',
    ]);

    $response->assertRedirect();

    expect($obligation->fresh()?->status)->toBe(ObligationStatus::Waived)
        ->and($obligation->fresh()?->adjustment_reason)->toBe('Member waiver approved')
        ->and($obligation->fresh()?->adjusted_by_member_id)->toBe($actor->id);
});

it('allows a treasurer to cancel an unpaid obligation', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $obligation = MemberObligation::factory()->create();

    $response = $this->actingAs($actor->user)->put(route('member-obligation-adjustment.update', $obligation), [
        'status' => ObligationStatus::Cancelled->value,
        'reason' => 'Duplicate obligation',
    ]);

    $response->assertRedirect();

    expect($obligation->fresh()?->status)->toBe(ObligationStatus::Cancelled)
        ->and($obligation->fresh()?->adjustment_reason)->toBe('Duplicate obligation');
});

it('denies a plain member from adjusting an obligation', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $obligation = MemberObligation::factory()->create();

    $response = $this->actingAs($actor->user)->put(route('member-obligation-adjustment.update', $obligation), [
        'status' => ObligationStatus::Waived->value,
        'reason' => 'Not permitted',
    ]);

    $response->assertForbidden();

    expect($obligation->fresh()?->status)->toBe(ObligationStatus::Unpaid);
});

it('requires an adjustment reason', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $obligation = MemberObligation::factory()->create();

    $response = $this->actingAs($actor->user)->put(route('member-obligation-adjustment.update', $obligation), [
        'status' => ObligationStatus::Waived->value,
        'reason' => '',
    ]);

    $response->assertSessionHasErrors('reason');
});
