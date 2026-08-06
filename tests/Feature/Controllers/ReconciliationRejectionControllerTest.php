<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ReconciliationStatus;
use App\Models\Reconciliation;

it('allows a financial verifier to reject a submitted reconciliation', function (): void {
    $preparer = memberWithRole(ClubRole::Treasurer);
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    $reconciliation = Reconciliation::factory()->submitted()->create([
        'prepared_by_member_id' => $preparer->id,
    ]);

    $response = $this->actingAs($actor->user)->put(route('reconciliation-rejection.update', $reconciliation), [
        'reason' => 'Statement closing balance does not match',
    ]);

    $response->assertRedirectToRoute('reconciliation.index');

    expect($reconciliation->fresh()?->status)->toBe(ReconciliationStatus::Rejected)
        ->and($reconciliation->fresh()?->rejection_reason)->toBe('Statement closing balance does not match');
});
