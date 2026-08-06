<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\Minute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Minute>
 */
final class MinuteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'version' => 1,
            'body' => fake()->paragraphs(3, true),
            'published_by_member_id' => null,
            'confirmed_at' => null,
            'confirmed_by_member_id' => null,
        ];
    }

    public function confirmed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'confirmed_at' => now(),
        ]);
    }
}
