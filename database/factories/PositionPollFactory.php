<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClubPosition;
use App\Enums\PositionPollStatus;
use App\Models\PositionPoll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PositionPoll>
 */
final class PositionPollFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'position' => ClubPosition::Chairperson,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => PositionPollStatus::Draft,
            'opened_at' => null,
            'closes_at' => null,
            'closed_at' => null,
            'eligible_voter_count' => 0,
            'quorum_required' => 0,
            'winning_candidate_id' => null,
            'outcome_note' => null,
            'created_by_member_id' => null,
        ];
    }

    /**
     * An open poll with the electorate already frozen.
     */
    public function open(int $eligible = 4, int $quorum = 2): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PositionPollStatus::Open,
            'opened_at' => now(),
            'closes_at' => now()->addWeek(),
            'eligible_voter_count' => $eligible,
            'quorum_required' => $quorum,
        ]);
    }
}
