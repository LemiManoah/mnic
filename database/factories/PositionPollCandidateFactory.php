<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PositionPollCandidate>
 */
final class PositionPollCandidateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'position_poll_id' => PositionPoll::factory(),
            'member_id' => Member::factory(),
            'nominated_by_member_id' => null,
            'manifesto' => fake()->paragraph(),
        ];
    }
}
