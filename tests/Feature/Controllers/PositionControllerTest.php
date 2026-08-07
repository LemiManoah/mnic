<?php

declare(strict_types=1);

use App\Enums\ClubPosition;
use App\Enums\ClubRole;
use App\Models\Member;
use App\Models\PositionHolding;

it('lists current positions and their history', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $holder = Member::factory()->create(['full_name' => 'Current Chair']);

    PositionHolding::factory()->create([
        'member_id' => $holder->id,
        'position' => ClubPosition::Chairperson,
        'held_from' => '2026-01-01',
        'held_to' => null,
    ]);

    $response = $this->actingAs($actor->user)->get(route('position.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('position/index')
            ->where('positions.0.holder', 'Current Chair')
            ->where('canTransfer', false)
            ->has('history', 1));
});

it('allows an administrator to manually transfer a position', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $successor = Member::factory()->create(['full_name' => 'Next Treasurer']);

    $response = $this->actingAs($actor->user)->post(route('position.store'), [
        'member_id' => $successor->id,
        'position' => ClubPosition::Treasurer->value,
        'held_from' => '2026-08-06',
        'reason' => 'Manual correction after AGM',
    ]);

    $response->assertRedirectToRoute('position.index');

    expect($successor->fresh()?->position)->toBe(ClubPosition::Treasurer)
        ->and(PositionHolding::query()
            ->where('member_id', $successor->id)
            ->where('position', ClubPosition::Treasurer->value)
            ->whereNull('held_to')
            ->exists())->toBeTrue();
});

it('denies a plain member from manually transferring a position', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $successor = Member::factory()->create();

    $response = $this->actingAs($actor->user)->post(route('position.store'), [
        'member_id' => $successor->id,
        'position' => ClubPosition::Treasurer->value,
        'held_from' => '2026-08-06',
        'reason' => 'Not permitted',
    ]);

    $response->assertForbidden();
});
