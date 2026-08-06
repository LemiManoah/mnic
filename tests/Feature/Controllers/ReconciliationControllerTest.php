<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Models\ContributionPeriod;
use App\Models\ExternalAccount;
use App\Models\Reconciliation;
use App\Models\ReconciliationItem;

it('lists reconciliations and their difference items for a treasurer', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $reconciliation = Reconciliation::factory()->create(['difference' => 5000]);

    ReconciliationItem::factory()->create([
        'reconciliation_id' => $reconciliation->id,
        'description' => 'Statement fee',
        'amount' => -5000,
    ]);

    $response = $this->actingAs($actor->user)->get(route('reconciliation.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('reconciliation/index')
            ->where('canCreate', true)
            ->has('reconciliations.data', 1)
            ->where('reconciliations.data.0.items.0.description', 'Statement fee')
            ->has('periods')
            ->has('externalAccounts')
            ->has('members'));
});

it('allows a treasurer to start a reconciliation', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $period = ContributionPeriod::factory()->create();
    $account = ExternalAccount::factory()->create();

    $response = $this->actingAs($actor->user)->post(route('reconciliation.store'), [
        'contribution_period_id' => $period->id,
        'external_account_id' => $account->id,
        'opening_balance' => 100000,
        'statement_closing_balance' => 160000,
        'notes' => 'Statement checked',
    ]);

    $response->assertRedirectToRoute('reconciliation.index');

    expect(Reconciliation::query()->where('contribution_period_id', $period->id)->exists())->toBeTrue();
});

it('denies a plain member from starting a reconciliation', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $period = ContributionPeriod::factory()->create();

    $response = $this->actingAs($actor->user)->post(route('reconciliation.store'), [
        'contribution_period_id' => $period->id,
        'opening_balance' => 100000,
        'statement_closing_balance' => 160000,
    ]);

    $response->assertForbidden();
});
