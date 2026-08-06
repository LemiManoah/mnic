<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
final class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => fake()->unique()->bothify('EXP-####'),
            'purpose' => fake()->sentence(4),
            'category' => ExpenseCategory::Meeting,
            'payee' => fake()->company(),
            'amount' => 50000,
            'incurred_on' => now()->toDateString(),
            'status' => ExpenseStatus::Submitted,
            'resolution_reference' => null,
            'external_account_id' => null,
            'payment_reference' => null,
            'paid_on' => null,
            'requested_by_member_id' => null,
            'approved_by_member_id' => null,
            'approved_at' => null,
            'verified_by_member_id' => null,
            'verified_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExpenseStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function paid(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExpenseStatus::Paid,
            'approved_at' => now(),
            'paid_on' => now()->toDateString(),
            'payment_reference' => fake()->bothify('PAY-####'),
        ]);
    }
}
