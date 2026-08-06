<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReconciliationStatus;
use App\Models\ContributionPeriod;
use App\Models\Reconciliation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reconciliation>
 */
final class ReconciliationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contribution_period_id' => ContributionPeriod::factory(),
            'external_account_id' => null,
            'opening_balance' => 0,
            'statement_closing_balance' => 0,
            'expected_closing_balance' => 0,
            'difference' => 0,
            'status' => ReconciliationStatus::Draft,
            'notes' => null,
            'prepared_by_member_id' => null,
            'submitted_at' => null,
            'confirmed_by_member_id' => null,
            'confirmed_at' => null,
            'locked_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function submitted(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReconciliationStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function confirmed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReconciliationStatus::Confirmed,
            'submitted_at' => now(),
            'confirmed_at' => now(),
        ]);
    }

    public function locked(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReconciliationStatus::Locked,
            'locked_at' => now(),
        ]);
    }
}
