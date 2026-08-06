<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ReconciliationStatus;
use App\Models\Reconciliation;

it('allows a treasurer to submit a draft reconciliation', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $reconciliation = Reconciliation::factory()->create(['prepared_by_member_id' => $actor->id]);

    $response = $this->actingAs($actor->user)->post(route('reconciliation-review.store', $reconciliation));

    $response->assertRedirectToRoute('reconciliation.index');

    expect($reconciliation->fresh()?->status)->toBe(ReconciliationStatus::Submitted);
});

it('allows a different financial verifier to confirm a submitted reconciliation', function (): void {
    $preparer = memberWithRole(ClubRole::Treasurer);
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    $reconciliation = Reconciliation::factory()->submitted()->create([
        'prepared_by_member_id' => $preparer->id,
    ]);

    $response = $this->actingAs($actor->user)->put(route('reconciliation-review.update', $reconciliation));

    $response->assertRedirectToRoute('reconciliation.index');

    expect($reconciliation->fresh()?->status)->toBe(ReconciliationStatus::Confirmed);
});

it('prevents the preparer from confirming their own reconciliation', function (): void {
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    $reconciliation = Reconciliation::factory()->submitted()->create([
        'prepared_by_member_id' => $actor->id,
    ]);

    $response = $this->actingAs($actor->user)->put(route('reconciliation-review.update', $reconciliation));

    $response->assertForbidden();
});

it('allows a treasurer to lock a confirmed reconciliation', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $reconciliation = Reconciliation::factory()->confirmed()->create();

    $response = $this->actingAs($actor->user)->delete(route('reconciliation-review.destroy', $reconciliation));

    $response->assertRedirectToRoute('reconciliation.index');

    expect($reconciliation->fresh()?->status)->toBe(ReconciliationStatus::Locked);
});
