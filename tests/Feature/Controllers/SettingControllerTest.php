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
