<?php

declare(strict_types=1);

use App\Actions\OpenContributionPeriod;
use App\Actions\UpdateSetting;
use App\Enums\ContributionPeriodStatus;
use App\Enums\ObligationStatus;
use App\Models\AuditLog;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Setting;

it('opens a period and creates one obligation per active member', function (): void {
    seedClubSettings();

    $active = Member::factory()->count(3)->create();
    Member::factory()->suspended()->create();
    Member::factory()->exited()->create();

    $actor = Member::factory()->create();

    $period = resolve(OpenContributionPeriod::class)->handle(2026, 9, $actor, '127.0.0.1');

    expect($period)->toBeInstanceOf(ContributionPeriod::class)
        ->and($period->status)->toBe(ContributionPeriodStatus::Open)
        ->and($period->amount)->toBe(60000)
        ->and($period->due_date->toDateString())->toBe('2026-09-05')
        ->and($period->grace_ends_on->toDateString())->toBe('2026-09-10');

    // The actor is active too, so four active members in total.
    expect(MemberObligation::query()->where('contribution_period_id', $period->id)->count())
        ->toBe($active->count() + 1);

    $obligation = MemberObligation::query()->where('contribution_period_id', $period->id)->first();

    expect($obligation?->amount)->toBe(60000)
        ->and($obligation?->amount_paid)->toBe(0)
        ->and($obligation?->status)->toBe(ObligationStatus::Unpaid);

    $auditLog = AuditLog::query()->where('auditable_id', $period->id)->first();

    expect($auditLog?->event)->toBe('contribution_period.opened');
});

it('does not create obligations for members who are not active', function (): void {
    seedClubSettings();

    $suspended = Member::factory()->suspended()->create();

    $period = resolve(OpenContributionPeriod::class)->handle(2026, 9);

    expect(MemberObligation::query()->where('member_id', $suspended->id)->exists())->toBeFalse();
});

it('refuses to open the same period twice', function (): void {
    seedClubSettings();

    resolve(OpenContributionPeriod::class)->handle(2026, 9);
    resolve(OpenContributionPeriod::class)->handle(2026, 9);
})->throws(InvalidArgumentException::class);

it('fails when a required club setting is missing', function (): void {
    Setting::query()->delete();

    resolve(OpenContributionPeriod::class)->handle(2026, 9);
})->throws(RuntimeException::class);

it('uses the amount in force when the period opened, not the latest amount', function (): void {
    seedClubSettings();

    Member::factory()->create();

    $period = resolve(OpenContributionPeriod::class)->handle(2026, 9);

    $setting = Setting::query()->where('key', 'contribution_amount')->firstOrFail();

    resolve(UpdateSetting::class)->handle($setting, '90000', now()->toDateString());

    expect($period->fresh()?->amount)->toBe(60000)
        ->and(MemberObligation::query()->where('contribution_period_id', $period->id)->first()?->amount)
        ->toBe(60000);
});
