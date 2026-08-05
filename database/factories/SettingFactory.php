<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SettingValueType;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
final class SettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'label' => fake()->words(3, true),
            'type' => SettingValueType::Text,
        ];
    }
}
