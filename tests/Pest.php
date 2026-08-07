<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
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

        // Faked for every test, the same way stray HTTP and processes are.
        // Club-wide notifications go to the whole roll across two channels, and
        // the seeder opens eight periods, so a suite that really rendered them
        // spent longer sending mail nobody reads than running assertions.
        // Tests that care assert against the fake; tests that need real rows in
        // the notifications table write them directly.
        Notification::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

/**
 * @param  array<string, mixed>  $memberAttributes
 */
function memberWithRole(ClubRole $role, array $memberAttributes = []): Member
{
    (new RolePermissionSeeder)->run();

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
