<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Member;
use App\Models\Setting;
use App\Models\SettingVersion;
use Illuminate\Support\Facades\DB;

final readonly class UpdateSetting
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(
        Setting $setting,
        string $value,
        string $effectiveFrom,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): SettingVersion {
        return DB::transaction(function () use ($setting, $value, $effectiveFrom, $actor, $ipAddress): SettingVersion {
            $before = $setting->currentVersion()?->toArray();

            $version = SettingVersion::query()->create([
                'setting_id' => $setting->id,
                'value' => $value,
                'effective_from' => $effectiveFrom,
                'created_by_member_id' => $actor?->id,
            ]);

            $this->recordAuditEvent->handle('setting.version_created', $setting, $actor, $before, $version->toArray(), $ipAddress);

            return $version;
        });
    }
}
