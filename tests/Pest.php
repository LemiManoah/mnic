<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

/**
 * @param  array<string, mixed>  $memberAttributes
 */
function memberWithRole(ClubRole $role, array $memberAttributes = []): Member
{
    (new RoleSeeder)->run();

    $user = User::factory()->withoutTwoFactor()->create();

    $member = Member::factory()->create([
        ...$memberAttributes,
        'user_id' => $user->id,
    ]);

    $user->assignRole($role->value);

    return $member->fresh('user') ?? $member;
}

/**
 * Seeds the effective-dated club settings the contribution engine reads
 * (contribution amount, due day, grace day).
 */
function seedClubSettings(): void
{
    (new SettingSeeder)->run();
}
