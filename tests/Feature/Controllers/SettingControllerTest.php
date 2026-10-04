<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Models\Setting;
use App\Models\SettingVersion;

it('lists settings with their current and historical versions', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $setting = Setting::factory()->create();

    SettingVersion::factory()->create([
        'setting_id' => $setting->id,
        'value' => '60000',
        'effective_from' => now()->subMonth()->toDateString(),
    ]);

    SettingVersion::factory()->create([
        'setting_id' => $setting->id,
        'value' => '70000',
        'effective_from' => now()->addMonth()->toDateString(),
    ]);

    $response = $this->actingAs($actor->user)->get(route('setting.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('setting/index')
            ->where('settings.0.current.value', '60000')
            ->where('settings.0.can_update', false)
            ->has('settings.0.versions', 2));
});

it('allows an administrator to add a new setting version', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $setting = Setting::factory()->create();

    $response = $this->actingAs($actor->user)->put(route('setting.update', $setting), [
        'value' => '70000',
        'effective_from' => now()->addMonth()->toDateString(),
    ]);

    $response->assertRedirectToRoute('setting.index');

    expect($setting->versions()->where('value', '70000')->exists())->toBeTrue();
});

it('denies a non-administrator from updating a setting', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $setting = Setting::factory()->create();

    $response = $this->actingAs($actor->user)->put(route('setting.update', $setting), [
        'value' => '70000',
        'effective_from' => now()->addMonth()->toDateString(),
    ]);

    $response->assertForbidden();
});

it('rejects invalid numeric club settings', function (string $key, string $value): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $setting = Setting::factory()->create(['key' => $key]);

    $this->actingAs($actor->user)->put(route('setting.update', $setting), [
        'value' => $value,
        'effective_from' => now()->toDateString(),
    ])->assertSessionHasErrors('value');

    expect($setting->versions()->count())->toBe(0);
})->with([
    ['contribution_amount', 'not a number'],
    ['contribution_amount', '0'],
    ['due_day', '0'],
    ['due_day', '31'],
    ['grace_day', '29'],
    ['approval_percent', '101'],
    ['quorum_percent', '0'],
]);

it('allows an administrator to see setting controls', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    Setting::factory()->create();

    $this->actingAs($actor->user)->get(route('setting.index'))
        ->assertOk()->assertInertia(fn ($page) => $page
        ->where('today', now()->toDateString())
        ->where('settings.0.can_update', true));
});
