<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
final class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'amount' => 60000,
            'unapplied_amount' => 0,
            'withdrawal_fee_amount' => 0,
            'contribution_due_amount' => null,
            'paid_on' => now()->toDateString(),
            'method' => PaymentMethod::MobileMoney,
            'reference' => fake()->unique()->bothify('MM-########'),
            'notes' => null,
            'status' => PaymentStatus::Submitted,
            'recorded_by_member_id' => null,
            'reviewed_by_member_id' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
            'reversed_by_member_id' => null,
            'reversed_at' => null,
            'reversal_reason' => null,
            'reversal_requested_by_member_id' => null,
            'reversal_requested_at' => null,
        ];
    }

    public function verified(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Verified,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Rejected,
            'reviewed_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Verified, with a reversal asked for but not yet decided.
     */
    public function reversalPending(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::ReversalPending,
            'reviewed_at' => now(),
            'reversal_requested_by_member_id' => Member::factory(),
            'reversal_requested_at' => now(),
            'reversal_reason' => fake()->sentence(),
        ]);
    }

    public function reversed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Reversed,
            'reversed_at' => now(),
            'reversal_reason' => fake()->sentence(),
        ]);
    }
}
