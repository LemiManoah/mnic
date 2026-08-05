<?php

declare(strict_types=1);

use App\Actions\UpdateSetting;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Setting;
use App\Models\SettingVersion;

it('creates a new version without touching the previous one', function (): void {
    $setting = Setting::factory()->create();

    $original = SettingVersion::factory()->create([
        'setting_id' => $setting->id,
        'value' => '60000',
        'effective_from' => now()->subMonth()->toDateString(),
    ]);

    $actor = Member::factory()->create();

    $action = resolve(UpdateSetting::class);

    $version = $action->handle($setting, '70000', now()->addMonth()->toDateString(), $actor, '127.0.0.1');

    expect($version)->toBeInstanceOf(SettingVersion::class)
        ->and($version->value)->toBe('70000')
        ->and($original->fresh()?->value)->toBe('60000');

    expect(SettingVersion::query()->where('setting_id', $setting->id)->count())->toBe(2);

    $auditLog = AuditLog::query()->where('auditable_id', $setting->id)->first();

    expect($auditLog?->event)->toBe('setting.version_created')
        ->and($auditLog?->actor_member_id)->toBe($actor->id);
});
