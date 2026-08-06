<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
final class MeetingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => fake()->unique()->bothify('MTG-2026-###'),
            'title' => fake()->sentence(4),
            'scheduled_for' => now()->addWeek(),
            'location' => fake()->city(),
            'agenda' => fake()->paragraph(),
            'status' => MeetingStatus::Scheduled,
            'scheduled_by_member_id' => null,
        ];
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MeetingStatus::Completed,
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MeetingStatus::Cancelled,
        ]);
    }
}
