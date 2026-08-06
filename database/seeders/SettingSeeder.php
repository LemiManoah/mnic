<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SettingValueType;
use App\Models\Setting;
use App\Models\SettingVersion;
use Illuminate\Database\Seeder;

final class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'contribution_amount' => [
                'label' => 'Monthly Contribution Amount (UGX)',
                'type' => SettingValueType::Integer,
                'value' => '60000',
            ],
            'due_day' => [
                'label' => 'Contribution Due Day',
                'type' => SettingValueType::Integer,
                'value' => '5',
            ],
            'grace_day' => [
                'label' => 'Contribution Grace Day',
                'type' => SettingValueType::Integer,
                'value' => '10',
            ],
            'quorum_percent' => [
                'label' => 'Voting Quorum (% of eligible members)',
                'type' => SettingValueType::Integer,
                'value' => '50',
            ],
            'approval_percent' => [
                'label' => 'Approval Threshold (% of decisive votes)',
                'type' => SettingValueType::Integer,
                'value' => '50',
            ],
        ];

        foreach ($settings as $key => $definition) {
            $setting = Setting::query()->firstOrCreate(
                ['key' => $key],
                ['label' => $definition['label'], 'type' => $definition['type']],
            );

            if ($setting->currentVersion() === null) {
                SettingVersion::query()->create([
                    'setting_id' => $setting->id,
                    'value' => $definition['value'],
                    'effective_from' => now()->toDateString(),
                    'created_by_member_id' => null,
                ]);
            }
        }
    }
}
