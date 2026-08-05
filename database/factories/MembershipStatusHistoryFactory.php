<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\MembershipStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipStatusHistory>
 */
final class MembershipStatusHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'from_status' => null,
            'to_status' => MemberStatus::Prospective,
            'reason' => fake()->sentence(),
            'effective_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'resolution_reference' => null,
        ];
    }
}
