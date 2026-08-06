<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
final class MemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_number' => fake()->unique()->numerify('MN-####'),
            'full_name' => fake()->name(),
            'position' => null,
            'phone' => fake()->phoneNumber(),
            'emergency_contact' => fake()->phoneNumber(),
            'joined_at' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
            'status' => MemberStatus::Active,
        ];
    }

    public function prospective(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MemberStatus::Prospective,
        ]);
    }

    public function suspended(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MemberStatus::Suspended,
        ]);
    }

    public function exited(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MemberStatus::Exited,
        ]);
    }

    public function removed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MemberStatus::Removed,
        ]);
    }
}
