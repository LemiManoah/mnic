<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Models\Reconciliation;
use App\Models\ReconciliationItem;

it('allows a treasurer to record a reconciliation difference item', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $assignee = memberWithRole(ClubRole::FinancialVerifier);
    $reconciliation = Reconciliation::factory()->create(['prepared_by_member_id' => $actor->id]);

    $response = $this->actingAs($actor->user)->post(route('reconciliation-item.store', $reconciliation), [
        'description' => 'Unposted bank fee',
        'amount' => -5000,
        'assigned_to_member_id' => $assignee->id,
    ]);

    $response->assertRedirectToRoute('reconciliation.index');

    expect($reconciliation->items()->where('description', 'Unposted bank fee')->exists())->toBeTrue();
});

it('allows a treasurer to resolve a reconciliation item', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $reconciliation = Reconciliation::factory()->create(['prepared_by_member_id' => $actor->id]);
    $item = ReconciliationItem::factory()->create(['reconciliation_id' => $reconciliation->id]);

    $response = $this->actingAs($actor->user)->put(route('reconciliation-item.update', [$reconciliation, $item]));

    $response->assertRedirectToRoute('reconciliation.index');

    expect($item->fresh()?->is_resolved)->toBeTrue();
});

it('denies members without update permission from recording items', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $reconciliation = Reconciliation::factory()->create();

    $response = $this->actingAs($actor->user)->post(route('reconciliation-item.store', $reconciliation), [
        'description' => 'Unposted bank fee',
        'amount' => -5000,
    ]);

    $response->assertForbidden();
});
