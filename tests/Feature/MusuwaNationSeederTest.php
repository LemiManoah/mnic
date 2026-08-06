<?php

declare(strict_types=1);

use App\Enums\ClubPosition;
use App\Enums\ClubRole;
use App\Enums\ContributionPeriodStatus;
use App\Enums\ExpenseStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\Member;
use App\Models\MembershipStatusHistory;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\MusuwaNationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    (new RolePermissionSeeder)->run();
    (new SettingSeeder)->run();
    (new MusuwaNationSeeder)->run();
});

it('seeds the full active roll of twenty', function (): void {
    expect(Member::query()->count())->toBe(20)
        ->and(Member::query()->where('status', MemberStatus::Active->value)->count())->toBe(20);
});

it('places the named leadership in the right offices', function (): void {
    $expected = [
        'Fredrick Ssekweyama' => ClubPosition::Chairperson,
        'Conrad Tumwijukye' => ClubPosition::ViceChairperson,
        'Shaun Ariko' => ClubPosition::GeneralSecretary,
        'Michael Nuwagaba' => ClubPosition::AssistantGeneralSecretary,
        'Lemi Manoah' => ClubPosition::Treasurer,
        'James Benjamin Lubega' => ClubPosition::AssistantTreasurer,
        'Feta Jeff Owen' => ClubPosition::Mobilizer,
        'John Esau Tumwine' => ClubPosition::AssistantMobilizer,
        'Alfred Musimenta' => ClubPosition::ChiefWhip,
        'Mark Ishimwe' => ClubPosition::AssistantChiefWhip,
    ];

    foreach ($expected as $name => $position) {
        expect(Member::query()->where('full_name', $name)->first()?->position)
            ->toBe($position, sprintf('%s should hold %s', $name, $position->value));
    }
});

it('seeds every elected office exactly once', function (): void {
    foreach (ClubPosition::cases() as $position) {
        expect(Member::query()->where('position', $position->value)->count())
            ->toBe(1, 'Expected exactly one '.$position->value);
    }
});

it('keeps the requested login for the treasurer', function (): void {
    $user = User::query()->where('email', 'lemi@gmail.com')->first();

    expect($user)->not->toBeNull()
        ->and(Hash::check('password', (string) $user?->password))->toBeTrue()
        ->and($user?->hasRole(ClubRole::Administrator->value))->toBeTrue();
});

it('separates recording from verifying so maker-checker works out of the box', function (): void {
    $treasurer = Member::query()->where('position', ClubPosition::AssistantTreasurer->value)->first();
    $whip = Member::query()->where('position', ClubPosition::ChiefWhip->value)->first();

    expect($treasurer?->user?->hasRole(ClubRole::Treasurer->value))->toBeTrue()
        ->and($whip?->user?->hasRole(ClubRole::FinancialVerifier->value))->toBeTrue()
        ->and($treasurer?->id)->not->toBe($whip?->id);
});

it('gives every member a login and an admission history entry', function (): void {
    expect(Member::query()->whereNull('user_id')->count())->toBe(0)
        ->and(MembershipStatusHistory::query()->count())->toBe(20);
});

it('opens a contribution period for every month since the club was founded', function (): void {
    // Founding month through the current month, inclusive.
    $expected = (int) abs(CarbonImmutable::parse('2026-01-01')->diffInMonths(CarbonImmutable::now())) + 1;

    expect(ContributionPeriod::query()->count())->toBe($expected)
        ->and(ContributionPeriod::query()->where('year', 2026)->where('month', 1)->exists())->toBeTrue();
});

it('settles past months but leaves the current one open', function (): void {
    $current = ContributionPeriod::query()
        ->orderByDesc('year')
        ->orderByDesc('month')
        ->first();

    expect($current?->status)->toBe(ContributionPeriodStatus::Open)
        ->and($current?->obligations()->where('amount_paid', '>', 0)->count())->toBe(0);
});

it('records verified payments so the ledger is not empty', function (): void {
    expect(Payment::query()->count())->toBeGreaterThan(0)
        ->and(Payment::query()->where('status', '!=', PaymentStatus::Verified->value)->count())->toBe(0);
});

it('leaves a few members in arrears so the arrears figures are meaningful', function (): void {
    $behind = Member::query()
        ->whereHas('obligations', fn ($query) => $query->where('amount_paid', 0))
        ->count();

    expect($behind)->toBeGreaterThan(0);
});

it('takes one expense all the way through to verification', function (): void {
    $expense = Expense::query()->where('reference', 'EXP-0001')->first();

    expect($expense?->status)->toBe(ExpenseStatus::Verified)
        ->and($expense?->requested_by_member_id)->not->toBe($expense?->approved_by_member_id)
        ->and($expense?->verified_by_member_id)->not->toBe($expense?->approved_by_member_id);
});

it('is idempotent', function (): void {
    $periods = ContributionPeriod::query()->count();
    $payments = Payment::query()->count();

    (new MusuwaNationSeeder)->run();

    expect(Member::query()->count())->toBe(20)
        ->and(User::query()->count())->toBe(20)
        ->and(ContributionPeriod::query()->count())->toBe($periods)
        ->and(Payment::query()->count())->toBe($payments);
});

it('labels every club position', function (): void {
    foreach (ClubPosition::cases() as $position) {
        expect($position->label())->not->toBe('');
    }
});
