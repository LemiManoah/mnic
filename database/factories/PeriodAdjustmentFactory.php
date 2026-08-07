<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AdjustmentStatus;
use App\Models\ContributionPeriod;
use App\Models\PeriodAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodAdjustment>
 */
final class PeriodAdjustmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contribution_period_id' => ContributionPeriod::factory()->closed(),
            'amount' => fake()->numberBetween(1000, 100000),
            'reason' => fake()->sentence(),
            'status' => AdjustmentStatus::Pending,
            'requested_by_member_id' => null,
            'requested_at' => now(),
            'reviewed_by_member_id' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AdjustmentStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AdjustmentStatus::Rejected,
            'reviewed_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
