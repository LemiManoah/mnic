<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Reconciliation;
use App\Models\ReconciliationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReconciliationItem>
 */
final class ReconciliationItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reconciliation_id' => Reconciliation::factory(),
            'description' => fake()->sentence(4),
            'amount' => fake()->numberBetween(-50000, 50000),
            'is_resolved' => false,
            'assigned_to_member_id' => null,
        ];
    }

    public function resolved(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_resolved' => true,
        ]);
    }
}
