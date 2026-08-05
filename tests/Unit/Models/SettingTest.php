<?php

declare(strict_types=1);

use App\Models\Member;
use App\Models\Setting;
use App\Models\SettingVersion;

test('to array', function (): void {
    $setting = Setting::factory()->create()->refresh();

    expect(array_keys($setting->toArray()))
        ->toBe([
            'id',
            'key',
            'label',
            'type',
            'created_at',
            'updated_at',
        ]);
});

it('returns null current version when none exist', function (): void {
    $setting = Setting::factory()->create();

    expect($setting->currentVersion())->toBeNull();
});

it('returns the latest version that has taken effect', function (): void {
    $setting = Setting::factory()->create();

    $past = SettingVersion::factory()->create([
        'setting_id' => $setting->id,
        'value' => '10',
        'effective_from' => now()->subDays(10)->toDateString(),
    ]);

    $current = SettingVersion::factory()->create([
        'setting_id' => $setting->id,
        'value' => '20',
        'effective_from' => now()->toDateString(),
    ]);

    SettingVersion::factory()->create([
        'setting_id' => $setting->id,
        'value' => '30',
        'effective_from' => now()->addDays(10)->toDateString(),
    ]);

    expect($setting->currentVersion()?->is($current))->toBeTrue()
        ->and($setting->currentVersion()?->is($past))->toBeFalse();
});

it('version belongs to its setting and creator', function (): void {
    $setting = Setting::factory()->create();
    $creator = Member::factory()->create();

    $version = SettingVersion::factory()->create([
        'setting_id' => $setting->id,
        'created_by_member_id' => $creator->id,
    ]);

    expect($version->setting->is($setting))->toBeTrue()
        ->and($version->createdByMember->is($creator))->toBeTrue();
});
