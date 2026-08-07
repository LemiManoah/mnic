<?php

declare(strict_types=1);

use App\Enums\ActionItemStatus;
use App\Enums\ClubPosition;
use App\Enums\ClubRole;
use App\Enums\ContributionPeriodStatus;
use App\Enums\ExpenseStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Enums\PositionPollStatus;
use App\Enums\ProposalStatus;
use App\Models\ActionItem;
use App\Models\ContributionPeriod;
use App\Models\Expense;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\MembershipStatusHistory;
use App\Models\Payment;
use App\Models\PositionHolding;
use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use App\Models\Proposal;
use App\Models\User;
use App\Models\Vote;
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
            ->toBe(1, 'Expected exactly one '.$position->value)
            ->and(PositionHolding::query()->where('position', $position->value)->whereNull('held_to')->count())
            ->toBe(1, 'Expected exactly one current holding for '.$position->value);
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

it('leaves the current month open', function (): void {
    $current = ContributionPeriod::query()
        ->orderByDesc('year')
        ->orderByDesc('month')
        ->first();

    expect($current?->status)->toBe(ContributionPeriodStatus::Open);
});

it('records verified payments so the ledger is not empty', function (): void {
    expect(Payment::query()->where('status', PaymentStatus::Verified->value)->count())
        ->toBeGreaterThan(0);
});

it('leaves payments in every reviewable state so the action buttons have work', function (): void {
    expect(Payment::query()->where('status', PaymentStatus::Submitted->value)->count())
        ->toBeGreaterThan(0, 'Expected payments awaiting verification')
        ->and(Payment::query()->where('status', PaymentStatus::Rejected->value)->count())
        ->toBeGreaterThan(0, 'Expected a rejected payment')
        ->and(Payment::query()->where('status', PaymentStatus::ReversalPending->value)->count())
        ->toBeGreaterThan(0, 'Expected a reversal awaiting a second officer');
});

it('spreads expenses across the whole approval chain', function (): void {
    foreach ([ExpenseStatus::Submitted, ExpenseStatus::Approved, ExpenseStatus::Paid, ExpenseStatus::Verified, ExpenseStatus::Rejected] as $status) {
        expect(Expense::query()->where('status', $status->value)->count())
            ->toBeGreaterThan(0, 'Expected an expense at '.$status->value);
    }
});

it('seeds a governance cycle with attendance, confirmed minutes and votes', function (): void {
    $held = Meeting::query()->where('reference', 'MTG-2026-001')->first();

    expect($held)->not->toBeNull()
        ->and($held?->attendances()->count())->toBe(20)
        ->and($held?->latestMinute()?->isConfirmed())->toBeTrue()
        // A future meeting too, so the dashboard's next-meeting card fills in.
        ->and(Meeting::query()->where('scheduled_for', '>', now())->exists())->toBeTrue()
        ->and(Proposal::query()->where('status', ProposalStatus::Passed->value)->exists())->toBeTrue()
        ->and(Proposal::query()->where('status', ProposalStatus::Open->value)->exists())->toBeTrue()
        ->and(Proposal::query()->where('status', ProposalStatus::Draft->value)->exists())->toBeTrue()
        ->and(Vote::query()->count())->toBeGreaterThan(0);
});

it('seeds action items in a spread of statuses', function (): void {
    foreach ([ActionItemStatus::Open, ActionItemStatus::InProgress, ActionItemStatus::Blocked, ActionItemStatus::Completed] as $status) {
        expect(ActionItem::query()->where('status', $status->value)->count())
            ->toBeGreaterThan(0, 'Expected an action item at '.$status->value);
    }
});

it('seeds a live, a decided and a draft election', function (): void {
    expect(PositionPoll::query()->where('status', PositionPollStatus::Open->value)->exists())->toBeTrue()
        ->and(PositionPoll::query()->where('status', PositionPollStatus::Draft->value)->exists())->toBeTrue();

    $decided = PositionPoll::query()->where('status', PositionPollStatus::Decided->value)->first();

    // The winner must actually hold the office, which is the whole point of
    // closing a poll rather than just recording the count.
    expect($decided)->not->toBeNull()
        ->and($decided?->winning_candidate_id)->not->toBeNull();

    $winner = PositionPollCandidate::query()->find($decided?->winning_candidate_id);

    expect(PositionHolding::query()
        ->where('position', $decided?->position->value)
        ->whereNull('held_to')
        ->value('member_id'))->toBe($winner?->member_id);
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
