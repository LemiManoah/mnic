<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ContributionPeriod;
use App\Models\OpeningWithdrawalFee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OpeningWithdrawalFee> */
final class OpeningWithdrawalFeeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'contribution_period_id' => ContributionPeriod::factory(),
            'amount' => 20295,
            'paid_on' => '2026-08-31',
            'reference' => fake()->unique()->uuid(),
            'notes' => 'Unallocated withdrawal fees from an opening report.',
        ];
    }
}
