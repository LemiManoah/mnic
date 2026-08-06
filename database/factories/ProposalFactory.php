<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
final class ProposalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => null,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'status' => ProposalStatus::Draft,
            'opened_at' => null,
            'closes_at' => null,
            'closed_at' => null,
            'eligible_voter_count' => 0,
            'quorum_required' => 0,
            'approval_percent' => 50,
            'outcome_note' => null,
            'created_by_member_id' => null,
        ];
    }

    /**
     * An open proposal with the electorate already frozen.
     */
    public function open(int $eligible = 4, int $quorum = 2): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProposalStatus::Open,
            'opened_at' => now(),
            'closes_at' => now()->addWeek(),
            'eligible_voter_count' => $eligible,
            'quorum_required' => $quorum,
        ]);
    }
}
