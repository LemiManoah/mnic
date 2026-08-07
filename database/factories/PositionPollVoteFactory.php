<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use App\Models\PositionPollVote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PositionPollVote>
 */
final class PositionPollVoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'position_poll_id' => PositionPoll::factory(),
            'member_id' => Member::factory(),
            'position_poll_candidate_id' => PositionPollCandidate::factory(),
            'cast_at' => now(),
        ];
    }
}
