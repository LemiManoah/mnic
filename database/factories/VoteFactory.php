<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VoteChoice;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vote>
 */
final class VoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'member_id' => Member::factory(),
            'choice' => VoteChoice::For,
            'has_conflict' => false,
            'conflict_note' => null,
            'cast_at' => now(),
        ];
    }
}
