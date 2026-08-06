<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExternalAccountType;
use App\Models\ExternalAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalAccount>
 */
final class ExternalAccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Club Current Account',
            'type' => ExternalAccountType::Bank,
            'institution' => fake()->company(),
            'masked_identifier' => '****'.fake()->numerify('####'),
            'is_active' => true,
        ];
    }
}
