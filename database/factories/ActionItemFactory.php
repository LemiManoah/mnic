<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActionItemStatus;
use App\Models\ActionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActionItem>
 */
final class ActionItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => null,
            'proposal_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'owner_member_id' => null,
            'due_on' => now()->addWeeks(2)->toDateString(),
            'status' => ActionItemStatus::Open,
            'completed_at' => null,
            'created_by_member_id' => null,
        ];
    }
}
