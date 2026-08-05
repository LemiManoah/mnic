<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContributionPeriodStatus;
use App\Models\ContributionPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContributionPeriod>
 */
final class ContributionPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) now()->year;
        $month = fake()->unique()->numberBetween(1, 12);

        return [
            'year' => $year,
            'month' => $month,
            'amount' => 60000,
            'due_date' => sprintf('%04d-%02d-05', $year, $month),
            'grace_ends_on' => sprintf('%04d-%02d-10', $year, $month),
            'status' => ContributionPeriodStatus::Open,
            'opened_by_member_id' => null,
        ];
    }

    public function closed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ContributionPeriodStatus::Closed,
        ]);
    }

    public function forMonth(int $year, int $month): self
    {
        return $this->state(fn (array $attributes): array => [
            'year' => $year,
            'month' => $month,
            'due_date' => sprintf('%04d-%02d-05', $year, $month),
            'grace_ends_on' => sprintf('%04d-%02d-10', $year, $month),
        ]);
    }
}
