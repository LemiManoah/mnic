<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ExternalAccountType;
use App\Models\ExternalAccount;

it('lists external accounts for a treasurer', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    ExternalAccount::factory()->create(['name' => 'Mobile Money Till']);

    $response = $this->actingAs($actor->user)->get(route('external-account.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('external-account/index')
            ->where('canCreate', false)
            ->has('accounts', 1)
            ->where('accounts.0.name', 'Mobile Money Till')
            ->has('typeOptions', count(ExternalAccountType::cases())));
});

it('denies account listing to members without account permission', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->get(route('external-account.index'));

    $response->assertForbidden();
});

it('allows an administrator to create an external account', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);

    $response = $this->actingAs($actor->user)->post(route('external-account.store'), [
        'name' => 'Bank Account',
        'type' => ExternalAccountType::Bank->value,
        'institution' => 'Test Bank',
        'masked_identifier' => '****1234',
    ]);

    $response->assertRedirectToRoute('external-account.index');

    expect(ExternalAccount::query()->where('name', 'Bank Account')->exists())->toBeTrue();
});
