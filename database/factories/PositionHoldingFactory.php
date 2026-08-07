<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClubPosition;
use App\Models\Member;
use App\Models\PositionHolding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PositionHolding>
 */
final class PositionHoldingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'position' => ClubPosition::Chairperson,
            'held_from' => now()->toDateString(),
            'held_to' => null,
            'elected_via_position_poll_id' => null,
            'appointed_by_member_id' => null,
            'transfer_reason' => fake()->sentence(),
        ];
    }
}
