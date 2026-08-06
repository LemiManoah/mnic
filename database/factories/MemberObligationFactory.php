<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ObligationStatus;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberObligation>
 */
final class MemberObligationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contribution_period_id' => ContributionPeriod::factory(),
            'member_id' => Member::factory(),
            'amount' => 60000,
            'amount_paid' => 0,
            'status' => ObligationStatus::Unpaid,
            'adjusted_by_member_id' => null,
            'adjusted_at' => null,
            'adjustment_reason' => null,
        ];
    }

    public function paid(): self
    {
        return $this->state(fn (array $attributes): array => [
            'amount_paid' => $attributes['amount'],
            'status' => ObligationStatus::Paid,
        ]);
    }

    public function waived(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ObligationStatus::Waived,
        ]);
    }
}
