<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Setting;
use App\Models\SettingVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SettingVersion>
 */
final class SettingVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'setting_id' => Setting::factory(),
            'value' => (string) fake()->numberBetween(1, 1000),
            'effective_from' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'created_by_member_id' => null,
        ];
    }
}
