<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

final class MusuwaNationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'lemi@gmail.com'],
            [
                'name' => 'Lemi Manoah',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        Member::query()->firstOrCreate(
            ['member_number' => 'MN-0001'],
            [
                'user_id' => $user->id,
                'full_name' => 'Lemi Manoah',
                'phone' => '+256700000001',
                'joined_at' => now()->toDateString(),
                'status' => MemberStatus::Active,
            ],
        );

        $user->syncRoles([ClubRole::Administrator->value]);
    }
}
