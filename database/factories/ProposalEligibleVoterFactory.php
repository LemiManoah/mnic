<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\Proposal;
use App\Models\ProposalEligibleVoter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposalEligibleVoter>
 */
final class ProposalEligibleVoterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'member_id' => Member::factory(),
        ];
    }
}
