<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuditLog>
 */
final class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_member_id' => Member::factory(),
            'event' => fake()->word(),
            'auditable_type' => Member::class,
            'auditable_id' => (string) Str::uuid(),
            'before' => null,
            'after' => null,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
